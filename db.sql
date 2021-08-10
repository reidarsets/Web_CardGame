CREATE DATABASE IF NOT EXISTS card_game;

USE card_game;

CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT KEY,
    login VARCHAR(15) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    name TEXT NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    win INT NOT NULL DEFAULT 0,
    lose INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS online_users (
    id INT NOT NULL KEY,
    login VARCHAR(15) NULL UNIQUE,
    name TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS search_lobby (
    serv_id INT NOT NULL,
    hero VARCHAR(15) NOT NULL,
    FOREIGN KEY (serv_id) REFERENCES online_users (id) ON DELETE CASCADE
);