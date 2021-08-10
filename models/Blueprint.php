<?php
    abstract class Blueprint {
        public $database;

        public function __construct($table) {
            $this->setTable($table);
            $this->setConnection();
        }

        function setTable($table) {
            $this->table = $table;
        }

        function setConnection() {
            $this->database = new DatabaseConnection('127.0.0.1', null, 'root', '', 'card_game');
        }
    }
