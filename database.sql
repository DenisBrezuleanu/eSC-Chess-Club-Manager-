CREATE DATABASE IF NOT EXISTS `esc_chess_club` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `esc_chess_club`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `member_awards`;
DROP TABLE IF EXISTS `competition_participants`;
DROP TABLE IF EXISTS `travel_expenses`;
DROP TABLE IF EXISTS `activities`;
DROP TABLE IF EXISTS `members`;
DROP TABLE IF EXISTS `awards`;
DROP TABLE IF EXISTS `competitions`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `coaches`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `coaches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nume` varchar(255) NOT NULL,
  `specializare` varchar(255) NOT NULL,
  `disponibilitate` varchar(255) NOT NULL,
  `rol` varchar(255) NOT NULL,
  `grupa_asignata` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nume` varchar(255) NOT NULL,
  `capacitate` int(11) NOT NULL,
  `dotari` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'member',
  `id_membru` int(11) DEFAULT NULL,
  `id_antrenor` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nume` varchar(255) NOT NULL,
  `tip_membru` enum('junior','senior','amator','pro') NOT NULL,
  `nivel_joc` varchar(100) DEFAULT NULL,
  `id_antrenor_asociat` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_members_coach` (`id_antrenor_asociat`),
  CONSTRAINT `fk_members_coach` FOREIGN KEY (`id_antrenor_asociat`) REFERENCES `coaches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_sala` int(11) NOT NULL,
  `nume_activitate` varchar(255) NOT NULL,
  `data` date NOT NULL,
  `ora_start` time NOT NULL,
  `ora_end` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activities_room_date` (`id_sala`,`data`),
  CONSTRAINT `fk_activities_room` FOREIGN KEY (`id_sala`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `competitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nume` varchar(255) NOT NULL,
  `data` date NOT NULL,
  `locatie` varchar(255) NOT NULL,
  `tip` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_competitions_date` (`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `competition_participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_competitie` int(11) NOT NULL,
  `id_membru` int(11) NOT NULL,
  `punctaj_obtinut` decimal(8,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_competition_member` (`id_competitie`,`id_membru`),
  KEY `idx_participants_member` (`id_membru`),
  CONSTRAINT `fk_participants_competition` FOREIGN KEY (`id_competitie`) REFERENCES `competitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_participants_member` FOREIGN KEY (`id_membru`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `awards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nume` varchar(255) NOT NULL,
  `descriere` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `member_awards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_award` int(11) NOT NULL,
  `id_membru` int(11) NOT NULL,
  `id_competitie` int(11) DEFAULT NULL,
  `data_acordare` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_member_awards_member` (`id_membru`),
  KEY `idx_member_awards_competition` (`id_competitie`),
  KEY `idx_member_awards_award` (`id_award`),
  CONSTRAINT `fk_member_awards_award` FOREIGN KEY (`id_award`) REFERENCES `awards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_member_awards_member` FOREIGN KEY (`id_membru`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_member_awards_competition` FOREIGN KEY (`id_competitie`) REFERENCES `competitions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `travel_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `destinatie` varchar(255) NOT NULL,
  `data` date NOT NULL,
  `scop` varchar(255) NOT NULL,
  `cost_transport` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_cazare` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_masa` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_travel_expenses_date` (`data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `id_membru`, `id_antrenor`) VALUES
(1, 'admin', '$2y$10$uFD1dCI9GnI1JDJdoc3raOKyFHuYdFS2kuY6vX3X4y9hqI.MmPaKG', 'admin', NULL, NULL),
(2, 'antrenor', '$2y$10$6Qg/YrBwo8T25qcbTkz5LufdzJRoVXho9wFVdJsOnMk4hPbTKLlRK', 'coach', NULL, 1),
(3, 'membru', '$2y$10$v2lOU0ZzRr915sFeQ2GfmOytyHqbioLkTHhuBM7l6Wq89ZjYSpuHG', 'member', 1, NULL);

ALTER TABLE `users` AUTO_INCREMENT = 4;
