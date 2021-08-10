<?php
    class Registration extends Blueprint {
        private $login, $password, $name, $email;

        public function __construct($table, $login, $password, $name, $email) {
            parent::__construct($table);
            $this->login = trim($login);
            $this->password = trim($password);
            $this->name = trim($name);
            $this->email = trim($email);
        }

        function create() {
            $this->database = new DatabaseConnection("127.0.0.1", null, 'root', '', "card_game");

            if ($this->database->getConnectionStatus()) {
                $this->database->connection->query("CREATE TABLE IF NOT EXISTS users (
                    id INT NOT NULL AUTO_INCREMENT KEY,
                    login VARCHAR(15) NOT NULL UNIQUE,
                    password VARCHAR(100) NOT NULL,
                    name TEXT NOT NULL,
                    email VARCHAR(50) NOT NULL UNIQUE,
                    win INT NOT NULL DEFAULT 0,
                    lose INT NOT NULL DEFAULT 0
                );");
                try {
                    $statement = $this->database->connection->prepare("INSERT INTO users
                    (login, password, name, email) VALUES (:login, :password, :name, :email);");
                    $this->database->connection->beginTransaction();
                    $statement->execute(['login'=>$this->login, 'password'=>$this->password,
                        'name'=>$this->name, 'email'=>$this->email ]);
                    $this->database->connection->commit();
                    return 0;
                } 
                catch (\Exception $e) {
                    if ($this->database->connection->inTransaction())
                        $this->database->connection->rollback();
                    return "User with such login or email already exists!";
                }
            }
        }
    }