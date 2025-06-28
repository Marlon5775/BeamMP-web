/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.11-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: beammp_db
-- ------------------------------------------------------
-- Server version	10.11.11-MariaDB-0+deb12u1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `beammp_db`
--

/*!40000 DROP DATABASE IF EXISTS `beammp_db`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `beammp_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `beammp_db`;

--
-- Table structure for table `beammp`
--

DROP TABLE IF EXISTS `beammp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `beammp` (
  `nom` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `chemin` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `id_map` varchar(255) DEFAULT NULL,
  `map_active` tinyint(4) DEFAULT NULL,
  `map_officielle` tinyint(4) DEFAULT NULL,
  `mod_actif` tinyint(4) DEFAULT NULL,
  `vehicule_type` varchar(255) DEFAULT NULL,
  `car_type` varchar(255) DEFAULT NULL,
  `archive` varchar(255) DEFAULT NULL,
  `link` text DEFAULT NULL,
  `date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`nom`),
  UNIQUE KEY `chemin` (`chemin`),
  UNIQUE KEY `image` (`image`),
  UNIQUE KEY `id_map` (`id_map`),
  UNIQUE KEY `archive` (`archive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `beammp`
--

LOCK TABLES `beammp` WRITE;
/*!40000 ALTER TABLE `beammp` DISABLE KEYS */;
INSERT INTO `beammp` VALUES
('Automation Test Track','/descriptions/Automation_Test_Track.txt','map',NULL,'/images/Automation_Test_Track.jpg','automation_test_track',1,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Centre de formation des pilotes ETK','/descriptions/Centre_de_formation_des_pilotes_ETK.txt','map',NULL,'/images/Centre_de_formation_des_pilotes_ETK.jpg','driver_training',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Circuit Hirochi','/descriptions/Circuit_Hirochi.txt','map',NULL,'/images/Circuit_Hirochi.jpg','hirochi_raceway',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Côte Est, USA','/descriptions/Cote_Est,_USA.txt','map',NULL,'/images/Cote_Est,_USA.jpg','east_coast_usa',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Côte Ouest, USA','/descriptions/Cote_Ouest,_USA.txt','map',NULL,'/images/Cote_Ouest,_USA.jpg','west_coast_usa',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Derby','/descriptions/Derby.txt','map',NULL,'/images/Derby.jpg','derby',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Gridmap v2','/descriptions/Gridmap_v2.txt','map',NULL,'/images/Gridmap_v2.jpg','gridmap_v2',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Ile Jungle Rock','/descriptions/Ile_Jungle_Rock.txt','map',NULL,'/images/Ile_Jungle_Rock.jpg','jungle_rock_island',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Ile Small, USA','/descriptions/Ile_Small,_USA.txt','map',NULL,'/images/Ile_Small,_USA.jpg','small_island',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Italie','/descriptions/Italie.txt','map',NULL,'/images/Italie.jpg','italy',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Johnson Valley','/descriptions/Johnson_Valley.txt','map',NULL,'/images/Johnson_Valley.jpg','johnson_valley',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Petite grille','/descriptions/Petite_grille.txt','map',NULL,'/images/Petite_grille.jpg','smallgrid',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Site industriel','/descriptions/Site_industriel.txt','map',NULL,'/images/Site_industriel.jpg','industrial',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17'),
('Utah, USA','/descriptions/Utah,_USA.txt','map',NULL,'/images/Utah,_USA.jpg','utah',0,1,1,NULL,NULL,NULL,NULL,'2024-12-09 18:54:17');
/*!40000 ALTER TABLE `beammp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `beammp_users`
--

DROP TABLE IF EXISTS `beammp_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `beammp_users` (
  `username` varchar(50) NOT NULL,
  `connection_count` int(11) DEFAULT 0,
  `last_connect` datetime DEFAULT NULL,
  `last_disconnect` datetime DEFAULT NULL,
  `total_time` int(11) DEFAULT 0,
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `beammp_users`
--

LOCK TABLES `beammp_users` WRITE;
/*!40000 ALTER TABLE `beammp_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `beammp_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `role` enum('SuperAdmin','Admin') NOT NULL DEFAULT 'SuperAdmin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-26 23:40:24
