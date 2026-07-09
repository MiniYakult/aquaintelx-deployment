-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: localhost    Database: aquaintelx
-- ------------------------------------------------------
-- Server version	8.0.41

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `live_readings`
--

DROP TABLE IF EXISTS `live_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `live_readings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sensor_node` varchar(50) NOT NULL,
  `temperature` decimal(10,2) DEFAULT NULL,
  `temperature_valid` tinyint(1) NOT NULL DEFAULT '1',
  `ph` decimal(10,2) DEFAULT NULL,
  `ph_raw` decimal(10,2) DEFAULT NULL,
  `ph_valid` tinyint(1) NOT NULL DEFAULT '1',
  `turbidity` decimal(10,2) DEFAULT NULL,
  `tds` decimal(10,2) DEFAULT NULL,
  `risk_level` varchar(50) DEFAULT 'Low Risk',
  `system_state` varchar(50) DEFAULT 'READING',
  `message` varchar(255) DEFAULT 'Live preview reading',
  `seconds_remaining` int DEFAULT '0',
  `reading_time` datetime DEFAULT NULL,
  `data_type` varchar(50) DEFAULT 'live',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sensor_node` (`sensor_node`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `live_readings`
--

LOCK TABLES `live_readings` WRITE;
/*!40000 ALTER TABLE `live_readings` DISABLE KEYS */;
INSERT INTO `live_readings` VALUES (1,'NODE-01',28.50,1,7.20,7.20,1,0.80,180.00,'Low Risk','READING','Demo safe reading',300,'2026-07-09 12:29:38','live','2026-07-09 04:29:38');
/*!40000 ALTER TABLE `live_readings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_logs`
--

LOCK TABLES `login_logs` WRITE;
/*!40000 ALTER TABLE `login_logs` DISABLE KEYS */;
INSERT INTO `login_logs` VALUES (11,NULL,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','failed','2026-06-13 11:51:21'),(12,NULL,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','failed','2026-06-13 11:51:29'),(13,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','success','2026-06-13 12:05:37'),(14,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','success','2026-06-13 12:17:56'),(15,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','success','2026-06-13 13:23:53'),(16,1,'admin@aquaintelx.com','192.168.100.2','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','success','2026-06-13 17:10:13'),(17,1,'admin@aquaintelx.com','192.168.100.53','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','success','2026-06-13 17:38:53'),(18,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','success','2026-06-14 03:51:49'),(19,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','success','2026-06-15 15:29:48'),(20,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','success','2026-06-15 18:11:49'),(21,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','success','2026-06-15 19:02:01'),(22,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-08 17:23:00'),(23,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-08 17:41:06'),(24,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-08 18:31:30'),(25,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-08 19:25:38'),(26,NULL,'paullourence@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','failed','2026-07-08 19:29:44'),(27,NULL,'paullourence@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','failed','2026-07-08 19:29:49'),(28,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-08 19:30:14'),(29,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-08 19:30:31'),(30,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-08 19:36:42'),(31,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-08 19:36:52'),(32,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-08 19:37:02'),(33,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-08 19:50:18'),(34,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-08 19:50:29'),(35,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 01:07:50'),(36,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 01:20:19'),(37,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 01:53:03'),(38,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 01:53:10'),(39,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 02:27:48'),(40,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 02:28:27'),(41,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 02:45:22'),(42,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 02:45:35'),(43,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 02:51:05'),(44,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 02:51:51'),(45,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 02:52:00'),(46,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 02:58:35'),(47,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 02:58:56'),(48,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 03:35:53'),(49,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 03:36:04'),(50,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 03:50:56'),(51,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 03:51:11'),(52,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:05:08'),(53,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:26:54'),(54,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:27:17'),(55,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:27:26'),(56,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:34:04'),(57,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:34:20'),(58,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:34:49'),(59,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36','success','2026-07-09 04:37:03'),(60,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Mobile Safari/537.36','success','2026-07-09 04:38:01'),(61,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:40:50'),(62,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:41:17'),(63,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:43:57'),(64,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:46:33'),(65,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:50:17'),(66,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 04:55:51'),(67,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 05:04:05'),(68,2,'paullourece@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 05:06:24'),(69,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 05:15:57'),(70,1,'admin@aquaintelx.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','success','2026-07-09 05:18:47');
/*!40000 ALTER TABLE `login_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `caption` varchar(200) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sensor_readings`
--

DROP TABLE IF EXISTS `sensor_readings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sensor_readings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sensor_node` varchar(50) NOT NULL DEFAULT 'NODE-01',
  `owner_user_id` int DEFAULT NULL,
  `owner_email` varchar(190) DEFAULT NULL,
  `temperature` decimal(6,2) DEFAULT NULL,
  `turbidity` decimal(6,2) DEFAULT NULL,
  `tds` decimal(8,2) DEFAULT NULL,
  `ph` decimal(5,2) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'normal',
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sensor_readings_owner_user_id` (`owner_user_id`),
  KEY `idx_sensor_readings_owner_email` (`owner_email`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sensor_readings`
--

LOCK TABLES `sensor_readings` WRITE;
/*!40000 ALTER TABLE `sensor_readings` DISABLE KEYS */;
INSERT INTO `sensor_readings` VALUES (1,'NODE-01',NULL,NULL,27.50,1.20,320.00,7.10,'normal','2026-06-13 12:18:13'),(2,'NODE-01',NULL,NULL,26.00,1.00,150.00,7.10,'normal','2026-06-13 13:36:16'),(3,'NODE-01',NULL,NULL,29.00,3.50,350.00,6.50,'warning','2026-06-13 15:28:48'),(4,'NODE-01',NULL,NULL,26.00,1.00,150.00,7.10,'normal','2026-06-13 16:17:08'),(5,'NODE-01',NULL,NULL,35.00,10.00,850.00,5.20,'critical','2026-06-13 16:18:02'),(6,'NODE-01',NULL,NULL,NULL,8.00,650.00,7.00,'critical','2026-06-13 16:22:43'),(7,'NODE-01',NULL,NULL,34.00,8.00,650.00,7.00,'critical','2026-06-13 16:23:08'),(8,'NODE-01',NULL,NULL,24.00,5.00,250.00,7.16,'warning','2026-06-13 16:46:27'),(9,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.39,'normal','2026-06-13 17:24:48'),(10,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.42,'normal','2026-06-13 17:24:53'),(11,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.41,'normal','2026-06-13 17:24:58'),(12,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.43,'normal','2026-06-13 17:25:03'),(13,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.42,'normal','2026-06-13 17:25:08'),(14,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.41,'normal','2026-06-13 17:25:13'),(15,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.39,'normal','2026-06-13 17:25:18'),(16,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.43,'normal','2026-06-13 17:25:23'),(17,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.40,'normal','2026-06-13 17:25:28'),(18,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.40,'normal','2026-06-13 17:25:33'),(19,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.40,'normal','2026-06-13 17:25:38'),(20,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.24,'normal','2026-06-13 17:25:43'),(21,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.40,'normal','2026-06-13 17:25:48'),(22,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.44,'normal','2026-06-13 17:25:53'),(23,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.38,'normal','2026-06-13 17:25:58'),(24,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.40,'normal','2026-06-13 17:26:03'),(25,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.41,'normal','2026-06-13 17:26:08'),(26,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.41,'normal','2026-06-13 17:26:13'),(27,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.36,'normal','2026-06-13 17:26:18'),(28,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.43,'normal','2026-06-13 17:26:23'),(29,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.40,'normal','2026-06-13 17:26:28'),(30,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.04,'normal','2026-06-13 17:26:33'),(31,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.40,'normal','2026-06-13 17:26:38'),(32,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.40,'normal','2026-06-13 17:26:43'),(33,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.41,'normal','2026-06-13 17:26:48'),(34,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.43,'normal','2026-06-13 17:26:53'),(35,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.42,'normal','2026-06-13 17:26:58'),(36,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.42,'normal','2026-06-13 17:27:03'),(37,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.38,'normal','2026-06-13 17:27:08'),(38,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.38,'normal','2026-06-13 17:27:13'),(39,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.43,'normal','2026-06-13 17:27:18'),(40,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.42,'normal','2026-06-13 17:27:23'),(41,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.41,'normal','2026-06-13 17:27:28'),(42,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.40,'normal','2026-06-13 17:27:33'),(43,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.40,'normal','2026-06-13 17:27:38'),(44,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.39,'normal','2026-06-13 17:27:43'),(45,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.43,'normal','2026-06-13 17:27:48'),(46,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.42,'normal','2026-06-13 17:27:53'),(47,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.36,'normal','2026-06-13 17:27:58'),(48,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.40,'normal','2026-06-13 17:28:03'),(49,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.39,'normal','2026-06-13 17:28:08'),(50,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.36,'normal','2026-06-13 17:28:13'),(51,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.42,'normal','2026-06-13 17:28:18'),(52,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.38,'normal','2026-06-13 17:28:23'),(53,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.38,'normal','2026-06-13 17:28:28'),(54,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.40,'normal','2026-06-13 17:28:33'),(55,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.39,'normal','2026-06-13 17:28:38'),(56,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.39,'normal','2026-06-13 17:28:43'),(57,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.43,'normal','2026-06-13 17:28:48'),(58,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.36,'normal','2026-06-13 17:28:53'),(59,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.37,'normal','2026-06-13 17:28:58'),(60,'NODE-01',NULL,NULL,NULL,NULL,0.00,8.37,'normal','2026-06-13 17:29:03'),(61,'NODE-01',NULL,NULL,28.30,171.30,0.00,8.39,'critical','2026-06-13 17:30:09'),(62,'NODE-01',NULL,NULL,28.20,166.00,0.00,8.19,'critical','2026-06-13 17:30:14'),(63,'NODE-01',NULL,NULL,28.20,172.80,0.00,8.37,'critical','2026-06-13 17:30:15'),(64,'NODE-01',NULL,NULL,28.20,171.30,0.00,8.42,'critical','2026-06-13 17:30:20'),(65,'NODE-01',NULL,NULL,28.20,161.50,0.00,8.41,'critical','2026-06-13 17:30:25'),(66,'NODE-01',NULL,NULL,28.20,170.50,0.00,8.35,'critical','2026-06-13 17:30:30'),(67,'NODE-01',NULL,NULL,28.20,169.80,0.00,8.38,'critical','2026-06-13 17:30:35'),(68,'NODE-01',NULL,NULL,28.30,177.30,0.00,8.37,'critical','2026-06-13 17:30:40'),(69,'NODE-01',NULL,NULL,28.20,170.50,0.00,8.35,'critical','2026-06-13 17:30:44'),(70,'NODE-01',NULL,NULL,28.30,173.50,0.00,8.41,'critical','2026-06-13 17:30:50'),(71,'NODE-01',NULL,NULL,28.20,178.80,0.00,8.36,'critical','2026-06-13 17:30:55'),(72,'NODE-01',NULL,NULL,28.20,175.80,0.00,8.37,'critical','2026-06-13 17:31:00'),(73,'NODE-01',NULL,NULL,28.30,167.50,0.00,8.37,'critical','2026-06-13 17:31:05'),(74,'NODE-01',NULL,NULL,28.20,202.00,0.00,8.36,'critical','2026-06-13 17:31:10'),(75,'NODE-01',NULL,NULL,28.20,168.30,0.00,8.33,'critical','2026-06-13 17:31:15'),(76,'NODE-01',NULL,NULL,28.20,176.50,0.00,8.41,'critical','2026-06-13 17:31:20'),(77,'NODE-01',NULL,NULL,28.20,174.30,0.00,8.42,'critical','2026-06-13 17:31:25'),(78,'NODE-01',NULL,NULL,28.20,165.20,0.00,8.39,'critical','2026-06-13 17:31:30'),(79,'NODE-01',NULL,NULL,28.20,172.00,0.00,8.37,'critical','2026-06-13 17:31:35'),(80,'NODE-01',NULL,NULL,28.20,164.50,0.00,8.36,'critical','2026-06-13 17:31:40'),(81,'NODE-01',NULL,NULL,28.20,170.50,0.00,8.37,'critical','2026-06-13 17:31:45'),(82,'NODE-01',NULL,NULL,28.20,173.50,0.00,8.39,'critical','2026-06-13 17:31:50'),(83,'NODE-01',NULL,NULL,28.20,163.00,0.00,8.34,'critical','2026-06-13 17:31:55'),(84,'NODE-01',NULL,NULL,28.20,166.00,0.00,8.39,'critical','2026-06-13 17:32:00'),(85,'NODE-01',NULL,NULL,28.20,169.00,0.00,8.40,'critical','2026-06-13 17:32:05'),(86,'NODE-01',NULL,NULL,28.20,167.50,0.00,8.32,'critical','2026-06-13 17:32:10'),(87,'NODE-01',NULL,NULL,28.20,171.30,0.00,8.39,'critical','2026-06-13 17:32:15'),(88,'NODE-01',NULL,NULL,28.20,175.80,0.00,8.41,'critical','2026-06-13 17:32:20'),(89,'NODE-01',NULL,NULL,28.30,176.50,0.00,8.36,'critical','2026-06-13 17:32:25'),(90,'NODE-01',NULL,NULL,26.50,1.20,180.00,7.10,'normal','2026-06-15 19:08:18'),(91,'NODE-01',NULL,NULL,29.50,10.00,250.00,8.20,'critical','2026-07-09 02:14:58'),(92,'NODE-01',NULL,NULL,30.10,1.20,280.00,9.20,'critical','2026-07-09 04:25:13'),(93,'NODE-01',NULL,NULL,28.50,0.80,180.00,7.20,'normal','2026-07-09 04:29:38');
/*!40000 ALTER TABLE `sensor_readings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'viewer',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'AquaIntelX Admin','admin@aquaintelx.com','$2y$10$RYHQbSmpTxkKWP/uOr9.m.4J5jEkUI3aaKxC8YiBT3VYm/a2Uj3Se','admin',NULL,'2026-06-13 10:53:47',1),(2,'Paul Lourence Calicoy','paullourece@gmail.com','$2y$10$l74lNpDFqLwkV2lTJgKTi.XNxtLmv7q6iEKEtV/W8v2jYGiHdE/x2','viewer','uploads/profile_images/user_2_fd973f8e3e1a1d25.png','2026-07-08 19:17:04',1);
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

-- Dump completed on 2026-07-09 13:27:33
