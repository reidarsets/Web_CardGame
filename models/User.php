<?php
  class User extends Blueprint {
    public $login, $name, $email, $win, $lose;

    public function __construct($table, $login) {
        parent::__construct($table);
        $this->login = trim($login);
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

            $statement = $this->database->connection->query("SELECT * FROM users WHERE login='$this->login'");
            $fetch = $statement->fetch(PDO::FETCH_ASSOC);
            if ($fetch) {
                $this->name = $fetch["name"];
                $this->email = $fetch["email"];
                $this->win = (int)$fetch["win"];
                $this->lose = (int)$fetch["lose"];
            }
        }
    }

    public function Update($win, $lose) {

    }
  }
