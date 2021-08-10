<?php
    class DatabaseConnection {
        public $connection;

        public function __construct($host, $port, $username, $password, $database) {
            try {
                $this->connection = new PDO("mysql:host=$host;dbname=$database", $username, $password);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                echo $e->getMessage() . "\n";
            }
        }
        public function __destruct() {
            $this->connection = null;
        }
        public function getConnectionStatus() {
            return isset($this->connection);
        }
    }
?>
