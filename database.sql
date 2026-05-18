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

CREATE TABLE IF NOT EXISTS competitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(255) NOT NULL,
    data_start DATE NOT NULL,
    data_end DATE NOT NULL,
    locatie VARCHAR(255) NOT NULL,
    tip ENUM('online', 'fizic') DEFAULT 'fizic'
);

CREATE TABLE IF NOT EXISTS competition_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_competitie INT NOT NULL,
    id_membru INT NOT NULL,
    rezultat_loc INT,
    punctaj DECIMAL(5,2),
    FOREIGN KEY (id_competitie) REFERENCES competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (id_membru) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS prizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_competitie INT NOT NULL,
    id_membru INT NOT NULL,
    nume_premiu VARCHAR(255) NOT NULL,
    valoare_premiu DECIMAL(10,2) DEFAULT NULL,
    FOREIGN KEY (id_competitie) REFERENCES competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (id_membru) REFERENCES members(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS travels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destinatie VARCHAR(255) NOT NULL,
    scop VARCHAR(255) NOT NULL,
    data_plecare DATE NOT NULL,
    data_intoarcere DATE NOT NULL
);

CREATE TABLE IF NOT EXISTS travel_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_travel INT NOT NULL,
    nume_participant VARCHAR(255) NOT NULL,
    cost_transport DECIMAL(10,2) DEFAULT 0.00,
    cost_cazare DECIMAL(10,2) DEFAULT 0.00,
    cost_masa DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (id_travel) REFERENCES travels(id) ON DELETE CASCADE
);
