<<<<<<< Updated upstream
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
=======
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 26, 2026 at 08:05 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `esc_chess_club`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `id_sala` int(11) NOT NULL,
  `nume_activitate` varchar(255) NOT NULL,
  `data` date NOT NULL,
  `ora_start` time NOT NULL,
  `ora_end` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `awards`
--

DROP TABLE IF EXISTS `awards`;
CREATE TABLE `awards` (
  `id` int(11) NOT NULL,
  `nume` varchar(255) NOT NULL,
  `descriere` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coaches`
--

DROP TABLE IF EXISTS `coaches`;
CREATE TABLE `coaches` (
  `id` int(11) NOT NULL,
  `nume` varchar(255) NOT NULL,
  `specializare` varchar(255) NOT NULL,
  `disponibilitate` varchar(255) NOT NULL,
  `rol` varchar(255) NOT NULL,
  `grupa_asignata` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `competitions`
--

DROP TABLE IF EXISTS `competitions`;
CREATE TABLE `competitions` (
  `id` int(11) NOT NULL,
  `nume` varchar(255) NOT NULL,
  `data_start` date NOT NULL,
  `data_end` date NOT NULL,
  `locatie` varchar(255) NOT NULL,
  `tip` enum('online','fizic') DEFAULT 'fizic',
  `data` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `competition_participants`
--

DROP TABLE IF EXISTS `competition_participants`;
CREATE TABLE `competition_participants` (
  `id` int(11) NOT NULL,
  `id_competitie` int(11) NOT NULL,
  `id_membru` int(11) NOT NULL,
  `punctaj_obtinut` decimal(8,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `nume` varchar(255) NOT NULL,
  `tip_membru` enum('junior','senior','amator','pro') NOT NULL,
  `nivel_joc` varchar(100) DEFAULT NULL,
  `id_antrenor_asociat` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_awards`
--

DROP TABLE IF EXISTS `member_awards`;
CREATE TABLE `member_awards` (
  `id` int(11) NOT NULL,
  `id_award` int(11) NOT NULL,
  `id_membru` int(11) NOT NULL,
  `id_competitie` int(11) DEFAULT NULL,
  `data_acordare` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `nume` varchar(255) NOT NULL,
  `capacitate` int(11) NOT NULL,
  `dotari` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_expenses`
--

DROP TABLE IF EXISTS `travel_expenses`;
CREATE TABLE `travel_expenses` (
  `id` int(11) NOT NULL,
  `destinatie` varchar(255) DEFAULT NULL,
  `data` date DEFAULT NULL,
  `scop` varchar(255) DEFAULT NULL,
  `cost_transport` decimal(10,2) DEFAULT 0.00,
  `cost_cazare` decimal(10,2) DEFAULT 0.00,
  `cost_masa` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'member',
  `id_membru` int(11) DEFAULT NULL,
  `id_antrenor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `id_membru`, `id_antrenor`) VALUES
(1, 'admin', '$2y$10$uFD1dCI9GnI1JDJdoc3raOKyFHuYdFS2kuY6vX3X4y9hqI.MmPaKG', 'admin', NULL, NULL),
(2, 'antrenor', '$2y$10$6Qg/YrBwo8T25qcbTkz5LufdzJRoVXho9wFVdJsOnMk4hPbTKLlRK', 'coach', NULL, 1),
(3, 'membru', '$2y$10$v2lOU0ZzRr915sFeQ2GfmOytyHqbioLkTHhuBM7l6Wq89ZjYSpuHG', 'member', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_sala` (`id_sala`);

--
-- Indexes for table `awards`
--
ALTER TABLE `awards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coaches`
--
ALTER TABLE `coaches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `competitions`
--
ALTER TABLE `competitions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `competition_participants`
--
ALTER TABLE `competition_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_competition_member` (`id_competitie`,`id_membru`),
  ADD KEY `id_membru` (`id_membru`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_antrenor_asociat` (`id_antrenor_asociat`);

--
-- Indexes for table `member_awards`
--
ALTER TABLE `member_awards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_member_awards_member` (`id_membru`),
  ADD KEY `idx_member_awards_competition` (`id_competitie`),
  ADD KEY `fk_member_awards_award` (`id_award`);

-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `travel_expenses`
--
ALTER TABLE `travel_expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `awards`
--
ALTER TABLE `awards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coaches`
--
ALTER TABLE `coaches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competitions`
--
ALTER TABLE `competitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `competition_participants`
--
ALTER TABLE `competition_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_awards`
--
ALTER TABLE `member_awards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `travel_expenses`
--
ALTER TABLE `travel_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`id_sala`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `competition_participants`
--
ALTER TABLE `competition_participants`
  ADD CONSTRAINT `competition_participants_ibfk_1` FOREIGN KEY (`id_competitie`) REFERENCES `competitions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `competition_participants_ibfk_2` FOREIGN KEY (`id_membru`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`id_antrenor_asociat`) REFERENCES `coaches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `member_awards`
--
ALTER TABLE `member_awards`
  ADD CONSTRAINT `fk_member_awards_award` FOREIGN KEY (`id_award`) REFERENCES `awards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_member_awards_competition` FOREIGN KEY (`id_competitie`) REFERENCES `competitions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_member_awards_member` FOREIGN KEY (`id_membru`) REFERENCES `members` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
>>>>>>> Stashed changes
