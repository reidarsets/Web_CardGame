<?php
class WebsocketServer
{
    public function __construct($config) {
        $this->config = $config;
    }

    public function start() {
        //открываем серверный сокет
        $server = stream_socket_server("tcp://{$this->config['host']}:{$this->config['port']}", $errorNumber, $errorString);

        if (!$server) {
            die("error: stream_socket_server: $errorString ($errorNumber)\r\n");
        }

        list($pid, $master, $workers) = $this->spawnWorkers();//создаём дочерние процессы

        if ($pid) {//мастер
            fclose($server);//мастер не будет обрабатывать входящие соединения на основном сокете
            $WebsocketMaster = new WebsocketMaster($workers);//мастер будет пересылать сообщения между воркерами
            $WebsocketMaster->start();
        } else {//воркер
            $WebsocketHandler = new WebsocketHandler($server, $master);
            $WebsocketHandler->start();
        }
    }

    protected function spawnWorkers() {
        $master = null;
        $workers = array();
        $i = 0;
        while ($i < $this->config['workers']) {
            $i++;
            //создаём парные сокеты, через них будут связываться мастер и воркер
            $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

            $pid = pcntl_fork();//создаём форк
            if ($pid == -1) {
                die("error: pcntl_fork\r\n");
            } elseif ($pid) { //мастер
                fclose($pair[0]);
                $workers[$pid] = $pair[1];//один из пары будет в мастере
            } else { //воркер
                fclose($pair[1]);
                $master = $pair[0];//второй в воркере
                break;
            }
        }

        return array($pid, $master, $workers);
    }
}

class WebsocketMaster
{
    protected $workers = array();
    protected $clients = array();

    public function __construct($workers) {
        $this->clients = $this->workers = $workers;
    }

    public function start() {
        while (true) {
            //подготавливаем массив всех сокетов, которые нужно обработать
            $read = $this->clients;

            stream_select($read, $write, $except, null);//обновляем массив сокетов, которые можно обработать

            if ($read) {//пришли данные от подключенных клиентов
                foreach ($read as $client) {
                    $data = fread($client, 1000);

                    if (!$data) { //соединение было закрыто
                        unset($this->clients[intval($client)]);
                        @fclose($client);
                        continue;
                    }

                    foreach ($this->workers as $worker) {//пересылаем данные во все воркеры
                        if ($worker !== $client) {
                            fwrite($worker, $data);
                        }
                    }
                }
            }
        }
    }
}

abstract class WebsocketWorker
{
    protected $clients = array();
    protected $server;
    protected $master;
    protected $pid;
    protected $handshakes = array();
    protected $ips = array();

    public function __construct($server, $master) {
        $this->server = $server;
        $this->master = $master;
        $this->pid = posix_getpid();
    }

    public function start() {
        while (true) {
            //подготавливаем массив всех сокетов, которые нужно обработать
            $read = $this->clients;
            $read[] = $this->server;
            $read[] = $this->master;

            $write = array();
            if ($this->handshakes) {
                foreach ($this->handshakes as $clientId => $clientInfo) {
                    if ($clientInfo) {
                        $write[] = $this->clients[$clientId];
                    }
                }
            }

            stream_select($read, $write, $except, null);//обновляем массив сокетов, которые можно обработать

            if (in_array($this->server, $read)) { //на серверный сокет пришёл запрос от нового клиента
                //подключаемся к нему и делаем рукопожатие, согласно протоколу вебсокета
                if ($client = stream_socket_accept($this->server, -1)) {
                    $address = explode(':', stream_socket_get_name($client, true));
                    if (isset($this->ips[$address[0]]) && $this->ips[$address[0]] > 5) {//блокируем более пяти соединий с одного ip
                        @fclose($client);
                    } else {
                        @$this->ips[$address[0]]++;

                        $this->clients[intval($client)] = $client;
                        $this->handshakes[intval($client)] = array();//отмечаем, что нужно сделать рукопожатие
                    }
                }

                //удаляем сервеный сокет из массива, чтобы не обработать его в этом цикле ещё раз
                unset($read[array_search($this->server, $read)]);
            }

            if (in_array($this->master, $read)) { //пришли данные от мастера
                $data = fread($this->master, 1000);

                $this->onSend($data);//вызываем пользовательский сценарий

                //удаляем мастера из массива, чтобы не обработать его в этом цикле ещё раз
                unset($read[array_search($this->master, $read)]);
            }

            if ($read) {//пришли данные от подключенных клиентов
                foreach ($read as $client) {
                    if (isset($this->handshakes[intval($client)])) {
                        if ($this->handshakes[intval($client)]) {//если уже было получено рукопожатие от клиента
                            continue;//то до отправки ответа от сервера читать здесь пока ничего не надо
                        }

                        if (!$this->handshake($client)) {
                            unset($this->clients[intval($client)]);
                            unset($this->handshakes[intval($client)]);
                            $address = explode(':', stream_socket_get_name($client, true));
                            if (isset($this->ips[$address[0]]) && $this->ips[$address[0]] > 0) {
                                @$this->ips[$address[0]]--;
                            }
                            @fclose($client);
                        }
                    } else {
                        $data = fread($client, 1000);

                        if (!$data) { //соединение было закрыто
                            unset($this->clients[intval($client)]);
                            unset($this->handshakes[intval($client)]);
                            $address = explode(':', stream_socket_get_name($client, true));
                            if (isset($this->ips[$address[0]]) && $this->ips[$address[0]] > 0) {
                                @$this->ips[$address[0]]--;
                            }
                            @fclose($client);
                            $this->onClose($client);//вызываем пользовательский сценарий
                            continue;
                        }

                        $this->onMessage($client, $data);//вызываем пользовательский сценарий
                    }
                }
            }

            if ($write) {
                foreach ($write as $client) {
                    if (!$this->handshakes[intval($client)]) {//если ещё не было получено рукопожатие от клиента
                        continue;//то отвечать ему рукопожатием ещё рано
                    }
                    $info = $this->handshake($client);
                    $this->onOpen($client, $info);//вызываем пользовательский сценарий
                }
            }
        }
    }

    protected function handshake($client) {
        $key = $this->handshakes[intval($client)];

        if (!$key) {
            //считываем загаловки из соединения
            $headers = fread($client, 10000);
            preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $match);

            if (empty($match[1])) {
                return false;
            }

            $key = $match[1];

            $this->handshakes[intval($client)] = $key;
        } else {
            //отправляем заголовок согласно протоколу вебсокета
            $SecWebSocketAccept = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                "Sec-WebSocket-Accept:$SecWebSocketAccept\r\n\r\n";
            fwrite($client, $upgrade);
            unset($this->handshakes[intval($client)]);
        }

        return $key;
    }

    protected function encode($payload, $type = 'text', $masked = false)
    {
        $frameHead = array();
        $payloadLength = strlen($payload);

        switch ($type) {
            case 'text':
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = 129;
                break;

            case 'close':
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = 136;
                break;

            case 'ping':
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = 137;
                break;

            case 'pong':
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = 138;
                break;
        }

        // set mask and payload length (using 1, 3 or 9 bytes)
        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            // most significant bit MUST be 0
            if ($frameHead[2] > 127) {
                return array('type' => '', 'payload' => '', 'error' => 'frame too large (1004)');
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }

        // convert frame-head to string:
        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }
        if ($masked === true) {
            // generate a random mask:
            $mask = array();
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }

            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);

        // append payload to frame:
        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }

    protected function decode($data)
    {
        $unmaskedPayload = '';
        $decodedData = array();

        // estimate frame type:
        $firstByteBinary = sprintf('%08b', ord($data[0]));
        $secondByteBinary = sprintf('%08b', ord($data[1]));
        $opcode = bindec(substr($firstByteBinary, 4, 4));
        $isMasked = ($secondByteBinary[0] == '1') ? true : false;
        $payloadLength = ord($data[1]) & 127;

        // unmasked frame is received:
        if (!$isMasked) {
            return array('type' => '', 'payload' => '', 'error' => 'protocol error (1002)');
        }

        switch ($opcode) {
            // text frame:
            case 1:
                $decodedData['type'] = 'text';
                break;

            case 2:
                $decodedData['type'] = 'binary';
                break;

            // connection close frame:
            case 8:
                $decodedData['type'] = 'close';
                break;

            // ping frame:
            case 9:
                $decodedData['type'] = 'ping';
                break;

            // pong frame:
            case 10:
                $decodedData['type'] = 'pong';
                break;

            default:
                return array('type' => '', 'payload' => '', 'error' => 'unknown opcode (1003)');
        }

        if ($payloadLength === 126) {
            $mask = substr($data, 4, 4);
            $payloadOffset = 8;
            $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
        } elseif ($payloadLength === 127) {
            $mask = substr($data, 10, 4);
            $payloadOffset = 14;
            $tmp = '';
            for ($i = 0; $i < 8; $i++) {
                $tmp .= sprintf('%08b', ord($data[$i + 2]));
            }
            $dataLength = bindec($tmp) + $payloadOffset;
            unset($tmp);
        } else {
            $mask = substr($data, 2, 4);
            $payloadOffset = 6;
            $dataLength = $payloadLength + $payloadOffset;
        }

        /*
         * We have to check for large frames here. socket_recv cuts at 1024 bytes
         * so if websocket-frame is > 1024 bytes we have to wait until whole
         * data is transferd.
         */
        if (strlen($data) < $dataLength) {
            return false;
        }

        if ($isMasked) {
            for ($i = $payloadOffset; $i < $dataLength; $i++) {
                $j = $i - $payloadOffset;
                if (isset($data[$i])) {
                    $unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
                }
            }
            $decodedData['payload'] = $unmaskedPayload;
        } else {
            $payloadOffset = $payloadOffset - 4;
            $decodedData['payload'] = substr($data, $payloadOffset);
        }

        return $decodedData;
    }

    abstract protected function onOpen($client, $info);

    abstract protected function onClose($client);

    abstract protected function onMessage($client, $data);

    abstract protected function onSend($data);

    abstract protected function send($data);
}

//пример реализации чата
class WebsocketHandler extends WebsocketWorker
{
    protected function onOpen($client, $info) { //вызывается при соединении с новым клиентом
        $uid = intval($client);
        echo "New connection! Client #" . $uid . "\n";
    }

    protected function onClose($client) {//вызывается при закрытии соединения клиентом
        $uid = intval($client);
        echo "Client #" . $uid . " closed connection\n";
        $database = new DatabaseConnection('127.0.0.1', null, 'root', '', 'card_game');
        if ($database->getConnectionStatus()) {
            $statement = $database->connection->query("DELETE FROM online_users WHERE id=$uid");
            if (!$statement)
                echo "Error occured during user deleting!\n";
        }
        else
            echo "Failed connecting to the database during user disconnection!\n";
    }

    protected function onMessage($client, $data) {//вызывается при получении сообщения от клиента
        $data = $this->decode($data);

        if (!$data['payload'])
            return;

        if (!mb_check_encoding($data['payload'], 'utf-8'))
            return;
        
        //var_export($data);
        //шлем всем сообщение, о том, что пишет один из клиентов
        $message = 'пользователь #' . intval($client) . ' (' . $this->pid . '): ' . strip_tags($data['payload']);

        $this->send($message);
        
        $this->sendHelper(strip_tags($data['payload']), intval($client));
    }

    protected function onSend($data) {//вызывается при получении сообщения от мастера
        $this->sendHelper($data);
    }

    protected function send($message) {//отправляем сообщение на мастер, чтобы он разослал его на все воркеры
        @fwrite($this->master, $message);
    }

    private function sendHelper($data, $from_id) {
        $content = json_decode($data, true);

        $write = $this->clients;
        if (stream_select($read, $write, $except, 0)) {
            foreach ($write as $client) {
                if (intval($client) === $from_id)
                    $this->Treat($content, $client);
            }
        }
    }

    private function Treat($data, $from) {
        switch ($data["operation"]) {
            case "playCard":
                $this->PlayCard($data, $from);
                break;
            case "EndTurn":
                $this->EndTurn($data, $from);
                break;
            case "Delete":
                $this->Delete($data, $from);
                break;
            case "UpdateID":
                $this->UpdateID($data, $from);
                break;
            case "BattleFinish":
                $this->BattleFinish($data, $from);
                break;
            case "registration":
                $this->Register($data, $from);
                break;
            case "authorization":
                $this->Login($data, $from);
                break;
            case "remind":
                $this->Remind($data, $from);
                break;
            case "GETinfo":
                $this->GETinfo($data, $from);
                break;
            case "MoveToSearchLobby":
                $this->MoveToSearchLobby($data, $from);
                break;
            default:
                break;
        }
    }

    private function Delete($data, $from) {
        $database = new DatabaseConnection('127.0.0.1', null, 'root', '', 'card_game');
        if ($database->getConnectionStatus()) {
            $table = $data["from"];
            $subject = $data["subject"];
            $condition = "";
            switch ($data["condition"]) {
                case "myID":
                    $condition = intval($from);
                    break;
                default:
                    break;
            }
            $database->connection->query("DELETE FROM $table WHERE $subject=$condition");
        }
        else
            echo "Failed to connect to the database during deletion!\n";
    }

    private function Register($data, $from) {
        require_once(__DIR__ . "/models/Registration.php");
        $reg_entity = new Registration("card_game", $data["username"], $data["password"], $data["name"], $data["email"]);
        $res = $reg_entity->create();
        if ($res) {
            $answer = array('status'=>'FAIL', 'message'=>$res);
            $answer = $this->encode(json_encode($answer));
            @fwrite($from, $answer);
        }
        else {
            $answer = array('status'=>'SUCCESS');
            $answer = $this->encode(json_encode($answer));
            @fwrite($from, $answer);
        }
    }

    private function Login($data, $from) {
        require_once(__DIR__ . "/models/Login.php");
        $log_entity = new Login("card_game", $data["login"], $data["password"]);
        $res = $log_entity->enter();
        if ($res) {
            $answer = array('status'=>'FAIL', 'message'=>$res);
            $answer = $this->encode(json_encode($answer));
            @fwrite($from, $answer);
        }
        else {
            $answer = array('status'=>'SUCCESS');
            $answer = $this->encode(json_encode($answer));
            @fwrite($from, $answer);
        }
    }

    private function Remind($data, $from) {
        require_once(__DIR__ . "/models/Remind.php");
        $remind_entity = new Remind("card_game", $data["username"]);
        $res = $remind_entity->send();
        if ($res) {
            $answer = array('status'=>'FAIL', 'message'=>$res);
            $answer = $this->encode(json_encode($answer));
            @fwrite($from, $answer);
        }
        else {
            $answer = array('status'=>'SUCCESS');
            $answer = $this->encode(json_encode($answer));
            @fwrite($from, $answer);
        }
    }

    private function GETinfo($data, $from) {
        require_once(__DIR__ . "/models/User.php");
        $user_entity = new User("card_game", $data["target"]);
        $answer = array("operation"=>"InfoRespond", "name"=>$user_entity->name, "email"=>$user_entity->email, "win"=>$user_entity->win,
            "lose"=>$user_entity->lose);
        $answer = $this->encode(json_encode($answer));
        @fwrite($from, $answer);

        $database = new DatabaseConnection('127.0.0.1', null, 'root', '', 'card_game');
        if ($database->getConnectionStatus()) {
            $uid = intval($from);
            $database->connection->query("CREATE TABLE IF NOT EXISTS online_users (
                id INT NOT NULL KEY,
                login VARCHAR(15) NULL UNIQUE,
                name TEXT NOT NULL
            );");
            $statement = $database->connection->query("INSERT IGNORE INTO online_users (id, login, name) VALUES($uid, '$user_entity->login', '$user_entity->name')");
            if (!$statement)
                echo "Error occured during user inserting!\n";
        }
        else
            echo "Failed connecting to the database during user connection!\n";
    }

    private function MoveToSearchLobby($data, $from) {
        $database = new DatabaseConnection('127.0.0.1', null, 'root', '', 'card_game');
        if ($database->getConnectionStatus()) {
            $database->connection->query("CREATE TABLE IF NOT EXISTS search_lobby (
                serv_id INT NOT NULL,
                hero VARCHAR(15) NOT NULL,
                FOREIGN KEY (serv_id) REFERENCES online_users (id) ON DELETE CASCADE
            );");
            $statement = $database->connection->query("SELECT * FROM search_lobby LIMIT 1");
            $fetch = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$fetch) { // No one is searching for a battle
                $serv_id = intval($from);
                $hero = $data["hero"];
                $statement = $database->connection->query("INSERT IGNORE INTO search_lobby (serv_id, hero) VALUES($serv_id, $hero)");
                if (!$statement)
                    echo "Error!\n";
                else {
                    $answer = array("operation"=>"Searching");
                    $answer = $this->encode(json_encode($answer));
                    @fwrite($from, $answer);
                }
            }
            else {
                $turn = rand(1, 100);
                if ($turn > 50)
                    $turn = 2;
                else
                    $turn = 1;

                $serv_id = $fetch['serv_id'];
                $statement = $database->connection->query("SELECT * FROM search_lobby WHERE serv_id=$serv_id");
                $fetch = $statement->fetch(PDO::FETCH_ASSOC);
                $avatar = $fetch["hero"];

                $statement = $database->connection->query("SELECT * FROM online_users WHERE id=$serv_id");
                $fetch = $statement->fetch(PDO::FETCH_ASSOC);
                $answer = array("operation"=>"OponentInfo", "OponentID"=>$serv_id, "OponentLogin"=>$fetch["login"], "OponentName"=>$fetch["name"], "avatar"=>$avatar, "Turn"=>$turn);
                $answer = $this->encode(json_encode($answer));
                @fwrite($from, $answer);
                $database->connection->query("DELETE FROM search_lobby WHERE serv_id=$serv_id");
                
                if ($turn === 2)
                    $turn = 1;
                else
                    $turn = 2;

                 $my_id = intval($from);
                // $statement = $database->connection->query("SELECT * FROM search_lobby WHERE serv_id=$my_id");
                // $fetch = $statement->fetch(PDO::FETCH_ASSOC);
                $avatar = $data["hero"];//$fetch["hero"];

                $statement = $database->connection->query("SELECT * FROM online_users WHERE id=$my_id");
                $fetch = $statement->fetch(PDO::FETCH_ASSOC);
                $answer = array("operation"=>"OponentInfo", "OponentID"=>$my_id, "OponentLogin"=>$fetch["login"], "OponentName"=>$fetch["name"], "avatar"=>$avatar, "Turn"=>$turn);
                $answer = $this->encode(json_encode($answer));

                $write = $this->clients;
                foreach ($this->clients as $client) 
                    if (intval($client) === (int)$serv_id) {
                        @fwrite($client, $answer);
                        break;
                    }
            }
        }
    }

    private function UpdateID($data, $from) {
        $database = new DatabaseConnection('127.0.0.1', null, 'root', '', 'card_game');
        if ($database->getConnectionStatus()) {
            $login = $data["login"];
            $name = $data["name"];
            $uid = intval($from);
            $database->connection->query("INSERT IGNORE INTO online_users (id, login, name) VALUES($uid, '$login', '$name')");
        }
    }

    private function BattleFinish($data, $from) {
        $database = new DatabaseConnection('127.0.0.1', null, 'root', '', 'card_game');
        if ($database->getConnectionStatus()) {
            $winner = $data["winner"];
            $loser = $data["loser"];
            $database->connection->query("UPDATE users SET win=win + 1 WHERE login='$winner'");
            $database->connection->query("UPDATE users SET lose=lose + 1 WHERE login='$loser'");

            $answer = array("operation"=>"BattleFinish", "status"=>"win");
            $answer = $this->encode(json_encode($answer));
            $statement = $database->connection->query("SELECT id FROM online_users WHERE login='$winner'");
            $fetch = $statement->fetch(PDO::FETCH_ASSOC);
            foreach ($this->clients as $client)
                if (intval($client) == $fetch["id"]) {
                    @fwrite($client, $answer);
                    break;
                }
            
            $answer = array("operation"=>"BattleFinish", "status"=>"lose");
            $answer = $this->encode(json_encode($answer));
            $statement = $database->connection->query("SELECT id FROM online_users WHERE login='$loser'");
            $fetch = $statement->fetch(PDO::FETCH_ASSOC);
            foreach ($this->clients as $client)
                if (intval($client) == $fetch["id"]) {
                    @fwrite($client, $answer);
                    break;
                }
        }
    }

    private function PlayCard($data, $from) {
        $database = new DatabaseConnection('127.0.0.1', null, 'root', '', 'card_game');
        if ($database->getConnectionStatus()) {
            $target = $data['player'];
            $statement = $database->connection->query("SELECT id FROM online_users WHERE login='$target'");
            $fetch = $statement->fetch(PDO::FETCH_ASSOC);
            foreach ($this->clients as $client)
                if (intval($client) == $fetch["id"]) {
                    $answer = $this->encode(json_encode($data));
                    @fwrite($client, $answer);
                    break;
                }
        }
    }

    private function EndTurn($data, $from) {
        $database = new DatabaseConnection('127.0.0.1', null, 'root', '', 'card_game');
        if ($database->getConnectionStatus()) {
            $target = $data['player'];
            $statement = $database->connection->query("SELECT id FROM online_users WHERE login='$target'");
            $fetch = $statement->fetch(PDO::FETCH_ASSOC);
            foreach ($this->clients as $client)
                if (intval($client) == $fetch["id"]) {
                    $answer = $this->encode(json_encode($data));
                    @fwrite($client, $answer);
                    break;
                }
        }
    }
}
