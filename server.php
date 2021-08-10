<?php
require_once(__DIR__ . "/models/connection/DatabaseConnection.php");
require_once(__DIR__ . "/models/Blueprint.php");
require_once(__DIR__ . "/view/View.php");
require_once(__DIR__ . "/controller/Controller.php");
require __DIR__ . "/WebsocketServer.php";

$config = array(
    'host' => '0.0.0.0',
    'port' => 8000,
    'workers' => 1,
);

$WebsocketServer = new WebsocketServer($config);
$WebsocketServer->start();
