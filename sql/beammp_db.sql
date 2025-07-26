CREATE DATABASE IF NOT EXISTS beammp_db
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE beammp_db;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `role` enum('SuperAdmin','Admin') NOT NULL DEFAULT 'SuperAdmin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
