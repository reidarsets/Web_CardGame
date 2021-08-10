<?php
  class Remind extends Blueprint {
    private $login;

    public function __construct($table, $login) {
        parent::__construct($table);
        $this->login = trim($login);
    }

    public function send() {
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
        $statement = $this->database->connection->query("SELECT password, email FROM users WHERE login='$this->login'");
        $fetch = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$fetch)
            return "Such user does not exists";
        else {
            $status = mail($fetch["email"], "Password reminder", $fetch["password"]);
            if (!$status)
                return "Something went wrong";
        }
      }
    }
  }
