CREATE DATABASE IF NOT EXISTS esc_chess_club;
USE esc_chess_club;

CREATE TABLE IF NOT EXISTS coaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(255) NOT NULL,
    specializare VARCHAR(255) NOT NULL,
    disponibilitate VARCHAR(255) NOT NULL,
    rol VARCHAR(255) NOT NULL,
    grupa_asignata VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(255) NOT NULL,
    capacitate INT NOT NULL,
    dotari TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_sala INT NOT NULL,
    nume_activitate VARCHAR(255) NOT NULL,
    data DATE NOT NULL,
    ora_start TIME NOT NULL,
    ora_end TIME NOT NULL,
    FOREIGN KEY (id_sala) REFERENCES rooms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user'
);

CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(255) NOT NULL,
    tip_membru ENUM('junior', 'senior', 'amator', 'pro') NOT NULL,
    nivel_joc VARCHAR(100),
    id_antrenor_asociat INT DEFAULT NULL,
    FOREIGN KEY (id_antrenor_asociat) REFERENCES coaches(id) ON DELETE SET NULL
);