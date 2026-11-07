-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: payrow
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `accounting_transactions`
--

DROP TABLE IF EXISTS `accounting_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'ZMW',
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_transactions_recorded_by_foreign` (`recorded_by`),
  KEY `accounting_transactions_company_id_transaction_date_index` (`company_id`,`transaction_date`),
  KEY `accounting_transactions_company_id_transaction_type_index` (`company_id`,`transaction_type`),
  KEY `accounting_transactions_source_type_source_id_index` (`source_type`,`source_id`),
  CONSTRAINT `accounting_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_transactions_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_transactions`
--

LOCK TABLES `accounting_transactions` WRITE;
/*!40000 ALTER TABLE `accounting_transactions` DISABLE KEYS */;
INSERT INTO `accounting_transactions` VALUES (1,1,'2026-10-08','payment','878788','Subscription payment',200.00,'ZMW','App\\Models\\SubscriptionPayment',9,5,NULL,'2026-10-08 14:11:25','2026-10-08 14:11:25');
/*!40000 ALTER TABLE `accounting_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_device_employees`
--

DROP TABLE IF EXISTS `attendance_device_employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_device_employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `attendance_device_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `device_user_id` varchar(150) NOT NULL,
  `device_username` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_device_employees_device_user_unique` (`attendance_device_id`,`device_user_id`),
  UNIQUE KEY `attendance_device_employees_device_employee_unique` (`attendance_device_id`,`employee_id`),
  KEY `attendance_device_employees_employee_id_foreign` (`employee_id`),
  KEY `attendance_device_employees_company_employee_idx` (`company_id`,`employee_id`),
  KEY `attendance_device_employees_device_active_idx` (`attendance_device_id`,`is_active`),
  CONSTRAINT `attendance_device_employees_attendance_device_id_foreign` FOREIGN KEY (`attendance_device_id`) REFERENCES `attendance_devices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_device_employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_device_employees_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_device_employees`
--

LOCK TABLES `attendance_device_employees` WRITE;
/*!40000 ALTER TABLE `attendance_device_employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_device_employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_devices`
--

DROP TABLE IF EXISTS `attendance_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `device_type` varchar(50) NOT NULL DEFAULT 'biometric',
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(150) DEFAULT NULL,
  `connection_type` varchar(30) NOT NULL DEFAULT 'api',
  `ip_address` varchar(45) DEFAULT NULL,
  `port` int(10) unsigned DEFAULT NULL,
  `api_url` varchar(255) DEFAULT NULL,
  `username` text DEFAULT NULL,
  `password` text DEFAULT NULL,
  `timezone` varchar(100) NOT NULL DEFAULT 'Africa/Lusaka',
  `sync_method` varchar(30) NOT NULL DEFAULT 'pull',
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_devices_company_serial_unique` (`company_id`,`serial_number`),
  KEY `attendance_devices_company_active_idx` (`company_id`,`is_active`),
  CONSTRAINT `attendance_devices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_devices`
--

LOCK TABLES `attendance_devices` WRITE;
/*!40000 ALTER TABLE `attendance_devices` DISABLE KEYS */;
INSERT INTO `attendance_devices` VALUES (1,1,'Office Biometric','biometric','ZKTeco','ZKTeco K40','TEST-002','api','192.168.1.100',4371,NULL,'password','eyJpdiI6InpOOStlZnd5aTgxL3pmcEo5RVBRbFE9PSIsInZhbHVlIjoiQzBNZXE2aTFYN3VPeFFWc2QyK3I5UT09IiwibWFjIjoiMjIyN2YzMDNiNmJmYTQwYjliNDNmMzVlMDNhNDA4YjQ0NDM4ODRiYTg3OTdlNDk5NzUzYTE3NzA1MGIxOGY4MiIsInRhZyI6IiJ9','Africa/Lusaka','pull',NULL,1,NULL,'2026-10-18 09:15:52','2026-10-19 07:31:00');
/*!40000 ALTER TABLE `attendance_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_punches`
--

DROP TABLE IF EXISTS `attendance_punches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_punches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `attendance_device_id` bigint(20) unsigned DEFAULT NULL,
  `punch_date` date NOT NULL,
  `punched_at` datetime NOT NULL,
  `punch_type` varchar(30) NOT NULL DEFAULT 'unknown',
  `source` varchar(30) NOT NULL DEFAULT 'device',
  `external_id` varchar(150) DEFAULT NULL,
  `device_user_id` varchar(150) DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_data`)),
  `is_processed` tinyint(1) NOT NULL DEFAULT 0,
  `processed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_punches_device_external_unique` (`attendance_device_id`,`external_id`),
  KEY `attendance_punches_employee_id_foreign` (`employee_id`),
  KEY `attendance_punches_company_employee_date_idx` (`company_id`,`employee_id`,`punch_date`),
  KEY `attendance_punches_device_datetime_idx` (`attendance_device_id`,`punched_at`),
  KEY `attendance_punches_company_datetime_idx` (`company_id`,`punched_at`),
  KEY `attendance_punches_processing_idx` (`company_id`,`is_processed`),
  CONSTRAINT `attendance_punches_attendance_device_id_foreign` FOREIGN KEY (`attendance_device_id`) REFERENCES `attendance_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_punches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_punches_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_punches`
--

LOCK TABLES `attendance_punches` WRITE;
/*!40000 ALTER TABLE `attendance_punches` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_punches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_records`
--

DROP TABLE IF EXISTS `attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'present',
  `clock_in_at` datetime DEFAULT NULL,
  `clock_out_at` datetime DEFAULT NULL,
  `worked_hours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `scheduled_hours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `overtime_hours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `late_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `early_departure_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `break_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `source` varchar(30) NOT NULL DEFAULT 'manual',
  `attendance_device_id` bigint(20) unsigned DEFAULT NULL,
  `is_manual_adjustment` tinyint(1) NOT NULL DEFAULT 0,
  `adjusted_by` bigint(20) unsigned DEFAULT NULL,
  `adjusted_at` timestamp NULL DEFAULT NULL,
  `adjustment_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_records_company_employee_date_unique` (`company_id`,`employee_id`,`attendance_date`),
  KEY `attendance_records_attendance_device_id_foreign` (`attendance_device_id`),
  KEY `attendance_records_adjusted_by_foreign` (`adjusted_by`),
  KEY `attendance_records_company_date_idx` (`company_id`,`attendance_date`),
  KEY `attendance_records_company_status_idx` (`company_id`,`status`),
  KEY `attendance_records_employee_date_idx` (`employee_id`,`attendance_date`),
  CONSTRAINT `attendance_records_adjusted_by_foreign` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_records_attendance_device_id_foreign` FOREIGN KEY (`attendance_device_id`) REFERENCES `attendance_devices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_records_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_records_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_records`
--

LOCK TABLES `attendance_records` WRITE;
/*!40000 ALTER TABLE `attendance_records` DISABLE KEYS */;
INSERT INTO `attendance_records` VALUES (1,1,3,'2026-10-17','late','2026-10-17 07:00:00','2026-10-17 14:00:00',7.00,0.00,7.00,0,0,0,'manual',NULL,1,8,'2026-10-17 13:55:38',NULL,NULL,'2026-10-17 10:37:12','2026-10-17 13:55:38'),(2,1,3,'2026-10-16','present','2026-10-16 08:10:00','2026-10-16 17:30:00',9.33,0.00,0.00,0,0,0,'manual',NULL,1,8,'2026-10-17 11:20:36',NULL,NULL,'2026-10-17 11:20:36','2026-10-17 11:20:36'),(5,1,3,'2026-10-15','present','2026-10-15 08:10:00','2026-10-15 17:30:00',8.33,8.00,0.00,0,0,60,'manual',NULL,1,8,'2026-10-17 12:35:40',NULL,NULL,'2026-10-17 12:32:30','2026-10-17 12:35:40'),(6,2,4,'2026-11-02','absent','2026-11-02 08:00:00','2026-11-02 17:00:00',8.00,8.00,0.00,0,0,60,'manual',NULL,1,8,'2026-11-05 16:08:29',NULL,NULL,'2026-11-02 18:45:02','2026-11-05 16:08:29'),(7,2,4,'2026-11-03','present','2026-11-03 08:20:00','2026-11-03 17:00:00',7.67,8.00,0.00,20,0,60,'manual',NULL,1,8,'2026-11-03 10:07:33',NULL,NULL,'2026-11-02 18:48:04','2026-11-03 10:07:59'),(8,2,4,'2026-11-04','present','2026-11-04 08:00:00','2026-11-04 17:30:00',8.50,8.00,0.33,0,0,60,'manual',NULL,1,8,'2026-11-03 08:19:09',NULL,NULL,'2026-11-03 08:09:44','2026-11-03 08:19:09'),(9,2,4,'2026-11-05','present','2026-11-05 08:00:00','2026-11-05 16:30:00',7.50,8.00,0.00,0,30,60,'manual',NULL,1,8,'2026-11-03 10:32:09',NULL,NULL,'2026-11-03 08:13:07','2026-11-03 10:32:09'),(11,2,4,'2026-12-01','present','2026-12-01 08:00:00','2026-12-01 16:30:00',7.50,8.00,0.00,0,30,60,'manual',NULL,1,8,'2026-11-05 12:55:07','Temporary payroll early-departure calculation test',NULL,'2026-11-05 10:34:49','2026-11-05 13:01:18'),(12,2,4,'2026-12-03','present','2026-12-03 08:00:00','2026-12-03 17:00:00',8.00,8.00,0.00,0,0,60,'manual',NULL,1,8,'2026-11-05 11:58:50',NULL,NULL,'2026-11-05 10:41:54','2026-11-05 11:58:50');
/*!40000 ALTER TABLE `attendance_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('laravel-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:5:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";s:1:\"j\";s:7:\"team_id\";}s:11:\"permissions\";a:22:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:14:\"companies.view\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:16:\"companies.create\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:16:\"companies.update\";s:1:\"c\";s:3:\"web\";}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:10:\"users.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:10;i:1;i:11;i:2;i:18;i:3;i:19;i:4;i:26;i:5;i:27;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:12:\"users.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:10;i:1;i:18;i:2;i:26;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:12:\"users.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:10;i:1;i:18;i:2;i:26;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:12:\"users.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:10;i:1;i:18;i:2;i:26;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:17:\"users.assign_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:10;i:1;i:18;i:2;i:26;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:14:\"employees.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:24:{i:0;i:10;i:1;i:11;i:2;i:12;i:3;i:13;i:4;i:14;i:5;i:15;i:6;i:16;i:7;i:17;i:8;i:18;i:9;i:19;i:10;i:20;i:11;i:21;i:12;i:22;i:13;i:23;i:14;i:24;i:15;i:25;i:16;i:26;i:17;i:27;i:18;i:28;i:19;i:29;i:20;i:30;i:21;i:31;i:22;i:32;i:23;i:33;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:16:\"employees.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:10;i:1;i:11;i:2;i:12;i:3;i:18;i:4;i:19;i:5;i:20;i:6;i:26;i:7;i:27;i:8;i:28;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:16:\"employees.update\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:10;i:1;i:11;i:2;i:12;i:3;i:18;i:4;i:19;i:5;i:20;i:6;i:26;i:7;i:27;i:8;i:28;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:16:\"employees.delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:10;i:1;i:11;i:2;i:18;i:3;i:19;i:4;i:26;i:5;i:27;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:10:\"leave.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:18:{i:0;i:10;i:1;i:11;i:2;i:12;i:3;i:15;i:4;i:16;i:5;i:17;i:6;i:18;i:7;i:19;i:8;i:20;i:9;i:23;i:10;i:24;i:11;i:25;i:12;i:26;i:13;i:27;i:14;i:28;i:15;i:31;i:16;i:32;i:17;i:33;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:12:\"leave.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:10;i:1;i:11;i:2;i:12;i:3;i:18;i:4;i:19;i:5;i:20;i:6;i:26;i:7;i:27;i:8;i:28;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:15:\"attendance.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:18:{i:0;i:10;i:1;i:11;i:2;i:12;i:3;i:15;i:4;i:16;i:5;i:17;i:6;i:18;i:7;i:19;i:8;i:20;i:9;i:23;i:10;i:24;i:11;i:25;i:12;i:26;i:13;i:27;i:14;i:28;i:15;i:31;i:16;i:32;i:17;i:33;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:17:\"attendance.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:12:{i:0;i:10;i:1;i:11;i:2;i:12;i:3;i:15;i:4;i:18;i:5;i:19;i:6;i:20;i:7;i:23;i:8;i:26;i:9;i:27;i:10;i:28;i:11;i:31;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:12:\"payroll.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:12:{i:0;i:10;i:1;i:13;i:2;i:14;i:3;i:17;i:4;i:18;i:5;i:21;i:6;i:22;i:7;i:25;i:8;i:26;i:9;i:29;i:10;i:30;i:11;i:33;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:14:\"payroll.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:10;i:1;i:13;i:2;i:14;i:3;i:18;i:4;i:21;i:5;i:22;i:6;i:26;i:7;i:29;i:8;i:30;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:15:\"payroll.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:6:{i:0;i:10;i:1;i:13;i:2;i:18;i:3;i:21;i:4;i:26;i:5;i:29;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:14:\"documents.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:15:{i:0;i:10;i:1;i:11;i:2;i:12;i:3;i:16;i:4;i:17;i:5;i:18;i:6;i:19;i:7;i:20;i:8;i:24;i:9;i:25;i:10;i:26;i:11;i:27;i:12;i:28;i:13;i:32;i:14;i:33;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:16:\"documents.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:9:{i:0;i:10;i:1;i:11;i:2;i:12;i:3;i:18;i:4;i:19;i:5;i:20;i:6;i:26;i:7;i:27;i:8;i:28;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:12:\"reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:15:{i:0;i:10;i:1;i:11;i:2;i:13;i:3;i:14;i:4;i:17;i:5;i:18;i:6;i:19;i:7;i:21;i:8;i:22;i:9;i:25;i:10;i:26;i:11;i:27;i:12;i:29;i:13;i:30;i:14;i:33;}}}s:5:\"roles\";a:24:{i:0;a:4:{s:1:\"a\";i:10;s:1:\"j\";i:1;s:1:\"b\";s:13:\"company_admin\";s:1:\"c\";s:3:\"web\";}i:1;a:4:{s:1:\"a\";i:11;s:1:\"j\";i:1;s:1:\"b\";s:10:\"hr_manager\";s:1:\"c\";s:3:\"web\";}i:2;a:4:{s:1:\"a\";i:18;s:1:\"j\";i:2;s:1:\"b\";s:13:\"company_admin\";s:1:\"c\";s:3:\"web\";}i:3;a:4:{s:1:\"a\";i:19;s:1:\"j\";i:2;s:1:\"b\";s:10:\"hr_manager\";s:1:\"c\";s:3:\"web\";}i:4;a:4:{s:1:\"a\";i:26;s:1:\"j\";i:3;s:1:\"b\";s:13:\"company_admin\";s:1:\"c\";s:3:\"web\";}i:5;a:4:{s:1:\"a\";i:27;s:1:\"j\";i:3;s:1:\"b\";s:10:\"hr_manager\";s:1:\"c\";s:3:\"web\";}i:6;a:4:{s:1:\"a\";i:12;s:1:\"j\";i:1;s:1:\"b\";s:10:\"hr_officer\";s:1:\"c\";s:3:\"web\";}i:7;a:4:{s:1:\"a\";i:13;s:1:\"j\";i:1;s:1:\"b\";s:15:\"payroll_manager\";s:1:\"c\";s:3:\"web\";}i:8;a:4:{s:1:\"a\";i:14;s:1:\"j\";i:1;s:1:\"b\";s:10:\"accountant\";s:1:\"c\";s:3:\"web\";}i:9;a:4:{s:1:\"a\";i:15;s:1:\"j\";i:1;s:1:\"b\";s:10:\"supervisor\";s:1:\"c\";s:3:\"web\";}i:10;a:4:{s:1:\"a\";i:16;s:1:\"j\";i:1;s:1:\"b\";s:8:\"employee\";s:1:\"c\";s:3:\"web\";}i:11;a:4:{s:1:\"a\";i:17;s:1:\"j\";i:1;s:1:\"b\";s:7:\"auditor\";s:1:\"c\";s:3:\"web\";}i:12;a:4:{s:1:\"a\";i:20;s:1:\"j\";i:2;s:1:\"b\";s:10:\"hr_officer\";s:1:\"c\";s:3:\"web\";}i:13;a:4:{s:1:\"a\";i:21;s:1:\"j\";i:2;s:1:\"b\";s:15:\"payroll_manager\";s:1:\"c\";s:3:\"web\";}i:14;a:4:{s:1:\"a\";i:22;s:1:\"j\";i:2;s:1:\"b\";s:10:\"accountant\";s:1:\"c\";s:3:\"web\";}i:15;a:4:{s:1:\"a\";i:23;s:1:\"j\";i:2;s:1:\"b\";s:10:\"supervisor\";s:1:\"c\";s:3:\"web\";}i:16;a:4:{s:1:\"a\";i:24;s:1:\"j\";i:2;s:1:\"b\";s:8:\"employee\";s:1:\"c\";s:3:\"web\";}i:17;a:4:{s:1:\"a\";i:25;s:1:\"j\";i:2;s:1:\"b\";s:7:\"auditor\";s:1:\"c\";s:3:\"web\";}i:18;a:4:{s:1:\"a\";i:28;s:1:\"j\";i:3;s:1:\"b\";s:10:\"hr_officer\";s:1:\"c\";s:3:\"web\";}i:19;a:4:{s:1:\"a\";i:29;s:1:\"j\";i:3;s:1:\"b\";s:15:\"payroll_manager\";s:1:\"c\";s:3:\"web\";}i:20;a:4:{s:1:\"a\";i:30;s:1:\"j\";i:3;s:1:\"b\";s:10:\"accountant\";s:1:\"c\";s:3:\"web\";}i:21;a:4:{s:1:\"a\";i:31;s:1:\"j\";i:3;s:1:\"b\";s:10:\"supervisor\";s:1:\"c\";s:3:\"web\";}i:22;a:4:{s:1:\"a\";i:32;s:1:\"j\";i:3;s:1:\"b\";s:8:\"employee\";s:1:\"c\";s:3:\"web\";}i:23;a:4:{s:1:\"a\";i:33;s:1:\"j\";i:3;s:1:\"b\";s:7:\"auditor\";s:1:\"c\";s:3:\"web\";}}}',1794043676);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `registration_number` varchar(255) DEFAULT NULL,
  `tax_number` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'Zambia',
  `organisation_type` varchar(100) DEFAULT NULL,
  `annual_turnover` decimal(15,2) DEFAULT NULL,
  `is_sdl_exempt` tinyint(1) NOT NULL DEFAULT 0,
  `sdl_exemption_reason` varchar(255) DEFAULT NULL,
  `sdl_exemption_reference` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'ZMW',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `companies`
--

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'JM CON','JM Construction','jm-con','222334456','555667','danielhashkuch@gmail.com','1234567890','Matero Chidanda Road','Lusaka','Zambia',NULL,NULL,0,NULL,NULL,'companies/logos/kofuvhX2H5BkriMeiIoOKicbMJQD1rnpEKNmLTby.jpg','ZMW',1,'2026-10-06 07:46:04','2026-10-07 10:06:02',NULL),(2,'UNICON','UNICON','unicon','3233234','123345','danielhashkuch@gmail.com','0975063300','Matero Chidanda Road','Lusaka','Zambia',NULL,NULL,0,'Audit trail test','TEST-001','companies/logos/R8rnjPjQX2a454nLZRVTSXgVJYOkzGW51tBGwPKR.jpg','ZMW',1,'2026-10-07 12:56:43','2026-11-06 12:06:48',NULL),(3,'BGR','BALUBA GAME RESORT','bgr','12345','556678','BGR@gmail.com','345567890','Matero Chidanda Road','Ndola','Zambia',NULL,NULL,0,NULL,NULL,'companies/logos/cho6l04FH37S0WJS9F6uQgHmSQtIKmnoXhykMJbt.jpg','ZMW',1,'2026-10-07 19:22:18','2026-10-07 19:22:18',NULL);
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_subscriptions`
--

DROP TABLE IF EXISTS `company_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `subscription_plan_id` bigint(20) unsigned NOT NULL,
  `status` enum('trial','active','past_due','suspended','cancelled','expired') NOT NULL DEFAULT 'trial',
  `billing_cycle` enum('monthly','annual') NOT NULL DEFAULT 'monthly',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_subscriptions_subscription_plan_id_foreign` (`subscription_plan_id`),
  KEY `company_subscriptions_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `company_subscriptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_subscriptions_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_subscriptions`
--

LOCK TABLES `company_subscriptions` WRITE;
/*!40000 ALTER TABLE `company_subscriptions` DISABLE KEYS */;
INSERT INTO `company_subscriptions` VALUES (1,1,2,'expired','monthly',200.00,'2026-10-07 22:00:00','2026-11-07 22:00:00',NULL,'878788','2026-10-07 22:00:00','2026-10-08 06:57:58','2026-10-09 08:04:41'),(2,3,2,'suspended','monthly',200.00,'2027-01-07 22:00:00','2027-02-07 22:00:00',NULL,'56567','2026-10-06 22:00:00','2026-10-08 07:38:34','2026-10-08 18:38:00'),(3,1,2,'active','monthly',200.00,'2026-10-08 22:00:00','2026-11-08 22:00:00','2026-11-08 22:00:00','667687yy87','2026-10-08 22:00:00','2026-10-09 08:04:41','2026-10-09 11:17:19'),(4,2,1,'active','monthly',100.00,'2026-10-30 22:00:00','2026-10-31 22:00:00','2026-11-29 22:00:00','3244','2026-10-30 22:00:00','2026-10-31 20:38:16','2026-10-31 20:38:16');
/*!40000 ALTER TABLE `company_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company_user`
--

DROP TABLE IF EXISTS `company_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `joined_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_user_company_id_user_id_unique` (`company_id`,`user_id`),
  KEY `company_user_user_id_foreign` (`user_id`),
  CONSTRAINT `company_user_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_user`
--

LOCK TABLES `company_user` WRITE;
/*!40000 ALTER TABLE `company_user` DISABLE KEYS */;
INSERT INTO `company_user` VALUES (3,2,8,1,'2026-10-11 21:24:20','2026-10-07 17:45:12','2026-10-11 21:24:20'),(4,1,8,1,'2026-10-11 21:24:20','2026-10-07 19:18:15','2026-10-11 21:24:20'),(6,3,9,1,'2026-10-07 19:55:28','2026-10-07 19:55:28','2026-10-07 19:55:28'),(7,3,10,1,'2026-10-09 12:01:53','2026-10-09 12:01:53','2026-10-09 12:01:53'),(8,2,11,1,'2026-10-11 21:35:35','2026-10-11 21:35:35','2026-10-11 21:35:35');
/*!40000 ALTER TABLE `company_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `departments_company_id_code_unique` (`company_id`,`code`),
  KEY `departments_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `departments_manager_id_foreign` (`manager_id`),
  CONSTRAINT `departments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `departments_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (2,3,'Human Resources','Hr',NULL,NULL,1,'2026-10-08 06:13:10','2026-10-08 06:13:10'),(3,3,'Finance','Finance',NULL,NULL,1,'2026-10-08 06:13:34','2026-10-08 06:13:34');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_final_settlements`
--

DROP TABLE IF EXISTS `employee_final_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_final_settlements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_separation_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `last_working_date` date NOT NULL,
  `settlement_date` date DEFAULT NULL,
  `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `salary_due` decimal(15,2) NOT NULL DEFAULT 0.00,
  `leave_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(15,2) NOT NULL DEFAULT 0.00,
  `overtime` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(15,2) NOT NULL DEFAULT 0.00,
  `commission` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notice_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `gratuity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `severance_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_earnings` decimal(15,2) NOT NULL DEFAULT 0.00,
  `loan_deduction` decimal(15,2) NOT NULL DEFAULT 0.00,
  `advance_deduction` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notice_deduction` decimal(15,2) NOT NULL DEFAULT 0.00,
  `statutory_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `gross_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','review','approved','paid','cancelled') NOT NULL DEFAULT 'draft',
  `prepared_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `paid_by` bigint(20) unsigned DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_final_settlements_employee_separation_id_foreign` (`employee_separation_id`),
  KEY `employee_final_settlements_employee_id_foreign` (`employee_id`),
  KEY `employee_final_settlements_prepared_by_foreign` (`prepared_by`),
  KEY `employee_final_settlements_reviewed_by_foreign` (`reviewed_by`),
  KEY `employee_final_settlements_approved_by_foreign` (`approved_by`),
  KEY `employee_final_settlements_paid_by_foreign` (`paid_by`),
  KEY `efs_company_employee_idx` (`company_id`,`employee_id`),
  KEY `efs_company_status_idx` (`company_id`,`status`),
  KEY `efs_company_separation_idx` (`company_id`,`employee_separation_id`),
  CONSTRAINT `employee_final_settlements_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_final_settlements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_final_settlements_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_final_settlements_employee_separation_id_foreign` FOREIGN KEY (`employee_separation_id`) REFERENCES `employee_separations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_final_settlements_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_final_settlements_prepared_by_foreign` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_final_settlements_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_final_settlements`
--

LOCK TABLES `employee_final_settlements` WRITE;
/*!40000 ALTER TABLE `employee_final_settlements` DISABLE KEYS */;
INSERT INTO `employee_final_settlements` VALUES (1,1,2,1,'2026-10-11',NULL,20000.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,'cancelled',8,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-10 11:46:08','2026-10-10 13:38:23'),(2,1,3,2,'2026-11-28','2026-10-10',400000.00,10000.00,5000.00,0.00,0.00,0.00,0.00,0.00,12000.00,5000.00,0.00,15000.00,3000.00,0.00,50000.00,0.00,32000.00,68000.00,0.00,'paid',8,NULL,NULL,8,'2026-10-10 21:35:21',8,'2026-10-10 21:35:51','122334567','bank_transfer','nin','2026-10-10 13:49:56','2026-10-10 21:35:51');
/*!40000 ALTER TABLE `employee_final_settlements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_payroll_components`
--

DROP TABLE IF EXISTS `employee_payroll_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_payroll_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) unsigned NOT NULL,
  `payroll_component_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `percentage` decimal(8,2) DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_component_effective_unique` (`employee_id`,`payroll_component_id`,`effective_from`),
  KEY `employee_payroll_components_employee_id_is_active_index` (`employee_id`,`is_active`),
  KEY `employee_payroll_components_payroll_component_id_is_active_index` (`payroll_component_id`,`is_active`),
  CONSTRAINT `employee_payroll_components_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_payroll_components_payroll_component_id_foreign` FOREIGN KEY (`payroll_component_id`) REFERENCES `payroll_components` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_payroll_components`
--

LOCK TABLES `employee_payroll_components` WRITE;
/*!40000 ALTER TABLE `employee_payroll_components` DISABLE KEYS */;
INSERT INTO `employee_payroll_components` VALUES (1,4,1,1000.00,NULL,'2026-11-01',NULL,1,NULL,'2026-11-01 13:17:50','2026-11-01 13:23:19'),(2,4,2,500.00,NULL,'2026-11-01',NULL,1,NULL,'2026-11-01 13:18:14','2026-11-01 13:18:14');
/*!40000 ALTER TABLE `employee_payroll_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_separation_clearances`
--

DROP TABLE IF EXISTS `employee_separation_clearances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_separation_clearances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_separation_id` bigint(20) unsigned NOT NULL,
  `category` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `responsible_user_id` bigint(20) unsigned DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('pending','cleared','rejected') NOT NULL DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `cleared_by` bigint(20) unsigned DEFAULT NULL,
  `cleared_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_separation_clearances_employee_separation_id_foreign` (`employee_separation_id`),
  KEY `employee_separation_clearances_department_id_foreign` (`department_id`),
  KEY `employee_separation_clearances_responsible_user_id_foreign` (`responsible_user_id`),
  KEY `employee_separation_clearances_cleared_by_foreign` (`cleared_by`),
  KEY `esc_company_separation_idx` (`company_id`,`employee_separation_id`),
  KEY `esc_company_status_idx` (`company_id`,`status`),
  CONSTRAINT `employee_separation_clearances_cleared_by_foreign` FOREIGN KEY (`cleared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_separation_clearances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_separation_clearances_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_separation_clearances_employee_separation_id_foreign` FOREIGN KEY (`employee_separation_id`) REFERENCES `employee_separations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_separation_clearances_responsible_user_id_foreign` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_separation_clearances`
--

LOCK TABLES `employee_separation_clearances` WRITE;
/*!40000 ALTER TABLE `employee_separation_clearances` DISABLE KEYS */;
INSERT INTO `employee_separation_clearances` VALUES (1,1,1,'HR','HR Records Clearance','Confirm that employee HR records, contracts and personnel documents are complete.',NULL,NULL,1,'cleared',NULL,8,'2026-10-09 22:17:56','2026-10-09 21:36:46','2026-10-09 22:17:56'),(2,1,1,'HR','Exit Interview','Complete the employee exit interview and record any relevant feedback.',NULL,NULL,1,'cleared',NULL,8,'2026-10-09 22:17:52','2026-10-09 21:36:46','2026-10-09 22:17:52'),(3,1,1,'Department','Department Handover','Confirm that duties, responsibilities and outstanding work have been handed over.',NULL,NULL,1,'cleared','ok',8,'2026-10-09 22:05:09','2026-10-09 21:36:46','2026-10-09 22:05:09'),(4,1,1,'Finance','Finance Clearance','Confirm outstanding advances, loans, deductions and other financial obligations.',NULL,NULL,1,'cleared',NULL,8,'2026-10-09 22:17:45','2026-10-09 21:36:46','2026-10-09 22:17:45'),(5,1,1,'IT','IT Clearance','Disable system access and confirm return of IT equipment and credentials.',NULL,NULL,1,'cleared',NULL,8,'2026-10-09 22:18:02','2026-10-09 21:36:46','2026-10-09 22:18:02'),(6,1,1,'Property','Company Property Return','Confirm that company property, equipment, keys, cards and other assets have been returned.',NULL,NULL,1,'cleared',NULL,8,'2026-10-09 22:18:07','2026-10-09 21:36:46','2026-10-09 22:18:07'),(7,1,1,'Documents','Document Clearance','Confirm that required employee documents and exit documentation are complete.',NULL,NULL,1,'cleared','ok',8,'2026-10-09 22:17:39','2026-10-09 21:36:46','2026-10-09 22:17:39'),(8,1,2,'HR','HR Records Clearance','Confirm that employee HR records, contracts and personnel documents are complete.',NULL,NULL,1,'cleared',NULL,8,'2026-10-09 23:03:23','2026-10-09 23:02:24','2026-10-09 23:03:23'),(9,1,2,'HR','Exit Interview','Complete the employee exit interview and record any relevant feedback.',NULL,NULL,1,'cleared',NULL,8,'2026-10-09 23:03:17','2026-10-09 23:02:24','2026-10-09 23:03:17'),(10,1,2,'Department','Department Handover','Confirm that duties, responsibilities and outstanding work have been handed over.',NULL,NULL,1,'cleared',NULL,8,'2026-10-09 23:03:01','2026-10-09 23:02:24','2026-10-09 23:03:01'),(11,1,2,'Finance','Finance Clearance','Confirm outstanding advances, loans, deductions and other financial obligations.',NULL,NULL,1,'cleared',NULL,8,'2026-10-09 23:03:12','2026-10-09 23:02:24','2026-10-09 23:03:12'),(12,1,2,'IT','IT Clearance','Disable system access and confirm return of IT equipment and credentials.',NULL,NULL,1,'cleared',NULL,8,'2026-10-09 23:03:44','2026-10-09 23:02:24','2026-10-09 23:03:44'),(13,1,2,'Property','Company Property Return','Confirm that company property, equipment, keys, cards and other assets have been returned.',NULL,NULL,1,'cleared',NULL,8,'2026-10-09 23:03:50','2026-10-09 23:02:24','2026-10-09 23:03:50'),(14,1,2,'Documents','Document Clearance','Confirm that required employee documents and exit documentation are complete.',NULL,NULL,1,'cleared',NULL,8,'2026-10-09 23:03:06','2026-10-09 23:02:24','2026-10-09 23:03:06'),(15,1,3,'HR','HR Records Clearance','Confirm that employee HR records, contracts and personnel documents are complete.',NULL,NULL,1,'cleared',NULL,8,'2026-10-10 13:49:23','2026-10-10 13:48:11','2026-10-10 13:49:23'),(16,1,3,'HR','Exit Interview','Complete the employee exit interview and record any relevant feedback.',NULL,NULL,1,'cleared',NULL,8,'2026-10-10 13:49:19','2026-10-10 13:48:11','2026-10-10 13:49:19'),(17,1,3,'Department','Department Handover','Confirm that duties, responsibilities and outstanding work have been handed over.',NULL,NULL,1,'cleared',NULL,8,'2026-10-10 13:49:03','2026-10-10 13:48:11','2026-10-10 13:49:03'),(18,1,3,'Finance','Finance Clearance','Confirm outstanding advances, loans, deductions and other financial obligations.',NULL,NULL,1,'cleared',NULL,8,'2026-10-10 13:49:12','2026-10-10 13:48:11','2026-10-10 13:49:12'),(19,1,3,'IT','IT Clearance','Disable system access and confirm return of IT equipment and credentials.',NULL,NULL,1,'cleared',NULL,8,'2026-10-10 13:49:29','2026-10-10 13:48:11','2026-10-10 13:49:29'),(20,1,3,'Property','Company Property Return','Confirm that company property, equipment, keys, cards and other assets have been returned.',NULL,NULL,1,'cleared',NULL,8,'2026-10-10 13:49:36','2026-10-10 13:48:11','2026-10-10 13:49:36'),(21,1,3,'Documents','Document Clearance','Confirm that required employee documents and exit documentation are complete.',NULL,NULL,1,'cleared',NULL,8,'2026-10-10 13:49:08','2026-10-10 13:48:11','2026-10-10 13:49:08');
/*!40000 ALTER TABLE `employee_separation_clearances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_separations`
--

DROP TABLE IF EXISTS `employee_separations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_separations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `separation_type` enum('resignation','termination','redundancy','retirement','end_of_contract','dismissal','death','abscondment','mutual_separation','other') NOT NULL,
  `notice_date` date DEFAULT NULL,
  `effective_date` date NOT NULL,
  `last_working_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','approved','clearance','final_payroll','completed','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_separations_employee_id_foreign` (`employee_id`),
  KEY `employee_separations_approved_by_foreign` (`approved_by`),
  KEY `employee_separations_created_by_foreign` (`created_by`),
  KEY `employee_separations_company_id_employee_id_index` (`company_id`,`employee_id`),
  KEY `employee_separations_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `employee_separations_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_separations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_separations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_separations_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_separations`
--

LOCK TABLES `employee_separations` WRITE;
/*!40000 ALTER TABLE `employee_separations` DISABLE KEYS */;
INSERT INTO `employee_separations` VALUES (1,1,1,'resignation','2026-10-10','2026-10-31','2026-10-10','i dont kwon','hjhjkuiu','completed',8,'2026-10-09 19:09:04',8,'2026-10-09 19:04:33','2026-10-09 22:23:30'),(2,1,1,'termination','2026-10-11','2026-10-18','2026-10-11','fired',NULL,'final_payroll',8,'2026-10-09 22:52:23',8,'2026-10-09 22:50:48','2026-10-10 11:46:08'),(3,1,2,'redundancy','2026-10-24','2026-11-28','2026-11-28','not sure',NULL,'completed',8,'2026-10-10 13:47:59',8,'2026-10-10 13:46:18','2026-10-10 21:44:28');
/*!40000 ALTER TABLE `employee_separations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_work_schedules`
--

DROP TABLE IF EXISTS `employee_work_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_work_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `work_schedule_id` bigint(20) unsigned NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_work_schedules_assigned_by_foreign` (`assigned_by`),
  KEY `employee_work_schedules_company_employee_idx` (`company_id`,`employee_id`),
  KEY `employee_work_schedules_employee_dates_idx` (`employee_id`,`effective_from`,`effective_to`),
  KEY `employee_work_schedules_schedule_date_idx` (`work_schedule_id`,`effective_from`),
  CONSTRAINT `employee_work_schedules_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employee_work_schedules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_work_schedules_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `employee_work_schedules_work_schedule_id_foreign` FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_work_schedules`
--

LOCK TABLES `employee_work_schedules` WRITE;
/*!40000 ALTER TABLE `employee_work_schedules` DISABLE KEYS */;
INSERT INTO `employee_work_schedules` VALUES (1,1,3,2,'2026-10-15','2026-10-15',1,NULL,8,'2026-10-15 11:43:54','2026-10-15 12:43:31'),(4,1,3,2,'2026-10-16','2026-10-19',0,NULL,8,'2026-10-15 18:47:01','2026-10-15 19:53:54'),(5,1,3,3,'2026-10-20','2026-10-30',1,NULL,8,'2026-10-15 19:53:54','2026-10-15 19:53:54'),(6,2,4,8,'2026-10-01','2026-12-31',1,NULL,8,'2026-11-02 18:43:18','2026-11-05 11:58:32');
/*!40000 ALTER TABLE `employee_work_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `employee_number` varchar(255) NOT NULL,
  `national_id` varchar(255) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `preferred_name` varchar(255) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `marital_status` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) NOT NULL DEFAULT 'Zambian',
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `alternative_phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'Zambia',
  `hire_date` date DEFAULT NULL,
  `probation_end_date` date DEFAULT NULL,
  `confirmation_date` date DEFAULT NULL,
  `contract_start_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `employment_type` enum('permanent','contract','temporary','casual','intern','part_time') NOT NULL DEFAULT 'permanent',
  `employment_status` enum('active','on_leave','suspended','separated') NOT NULL DEFAULT 'active',
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `position_id` bigint(20) unsigned DEFAULT NULL,
  `supervisor_id` bigint(20) unsigned DEFAULT NULL,
  `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `salary_currency` varchar(3) NOT NULL DEFAULT 'ZMW',
  `pay_frequency` enum('monthly','weekly','bi_weekly','daily') NOT NULL DEFAULT 'monthly',
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_branch` varchar(255) DEFAULT NULL,
  `bank_account_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(255) DEFAULT NULL,
  `bank_branch_code` varchar(255) DEFAULT NULL,
  `tpin` varchar(255) DEFAULT NULL,
  `napsa_number` varchar(255) DEFAULT NULL,
  `nhima_number` varchar(255) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_relationship` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(255) DEFAULT NULL,
  `emergency_contact_address` text DEFAULT NULL,
  `separation_date` date DEFAULT NULL,
  `separation_type` varchar(255) DEFAULT NULL,
  `separation_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_company_id_employee_number_unique` (`company_id`,`employee_number`),
  KEY `employees_user_id_foreign` (`user_id`),
  KEY `employees_department_id_foreign` (`department_id`),
  KEY `employees_position_id_foreign` (`position_id`),
  KEY `employees_supervisor_id_foreign` (`supervisor_id`),
  KEY `employees_company_id_employment_status_index` (`company_id`,`employment_status`),
  KEY `employees_company_id_department_id_index` (`company_id`,`department_id`),
  KEY `employees_company_id_position_id_index` (`company_id`,`position_id`),
  KEY `employees_national_id_index` (`national_id`),
  KEY `employees_email_index` (`email`),
  KEY `employees_tpin_index` (`tpin`),
  KEY `employees_napsa_number_index` (`napsa_number`),
  KEY `employees_nhima_number_index` (`nhima_number`),
  CONSTRAINT `employees_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_position_id_foreign` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `employees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,1,NULL,'E67667','6778','Daniel',NULL,'Hash',NULL,NULL,'2000-12-10','married','Zambian','danielhashkuch@gmail.com','097654',NULL,'Matero Chidanda Road','Lusaka','Zambia','2026-10-10','2026-10-14','2026-10-13','2026-10-15',NULL,'permanent','separated',NULL,NULL,NULL,20000.00,'USD','monthly','ECO','ndola','1223344555667','1223344555667','7y879u98','23445567','567778','6787887','Hash','Mother','887765443','Matero Chidanda Road',NULL,NULL,NULL,NULL,1,'2026-10-09 11:38:47','2026-10-09 22:23:30',NULL),(2,1,NULL,'EM34345','655478/09/12','Quan','Dee','Hash',NULL,'male','1994-06-09','married','Zambian','hashkuch@gmail.com','9090809776',NULL,'Matero Road','Lusaka','Zambia','2026-10-09','2025-02-16','2026-07-03',NULL,NULL,'contract','separated',NULL,NULL,1,400000.00,'ZMW','monthly','Eccco','55667','87989898080','87879','79707070','757687687698798','86879979','687797','Daniel Hash','Father','0975667477',NULL,'2026-11-28','redundancy','not sure',NULL,0,'2026-10-09 12:25:18','2026-10-10 21:44:28',NULL),(3,1,NULL,'4465666','344354/10/56','Daniel',NULL,'kuch',NULL,'male','2025-02-12',NULL,'Zambian','danielhashkuch@gmail.com','09765432211',NULL,'Matero Chidanda Road','Lusaka','Zambia','2026-01-18','2026-02-08','2026-08-13',NULL,NULL,'permanent','active',NULL,NULL,NULL,10000.00,'ZMW','monthly','Ecoo','Ndola','hash','3456789087','6768','3456789','2345678','23456789','Daniel Hash','Father','097540867','Matero Chidanda Road',NULL,NULL,NULL,NULL,1,'2026-10-12 11:13:35','2026-10-12 11:13:35',NULL),(4,2,NULL,'UNCON01','112233/30/3','John',NULL,'Banda',NULL,'male','2001-02-17',NULL,'Zambian','john@mail.com','097654321',NULL,NULL,'Ndola','Zambia','2026-10-01','2026-10-01','2026-11-05','2026-10-31',NULL,'permanent','active',NULL,NULL,NULL,5000.00,'ZMW','monthly','Indo bank','town','John Bank','09876543322','rfefrferf','222344','9090','909',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-10-31 20:42:47','2026-10-31 20:42:47',NULL);
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES (15,'default','{\"uuid\":\"8214b340-8be3-4eaa-a692-5d278afdb3a2\",\"displayName\":\"App\\\\Notifications\\\\SubscriptionNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":3:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:8;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:42:\\\"App\\\\Notifications\\\\SubscriptionNotification\\\":4:{s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:30:\\\"App\\\\Models\\\\CompanySubscription\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:2:{i:0;s:7:\\\"company\\\";i:1;s:4:\\\"plan\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:5:\\\"event\\\";s:8:\\\"assigned\\\";s:13:\\\"daysRemaining\\\";N;s:2:\\\"id\\\";s:36:\\\"11b9dc63-ecc9-436f-9884-394cf88c39e3\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:8:\\\"database\\\";}}\",\"batchId\":null},\"createdAt\":1793486298,\"delay\":null}',0,NULL,1793486298,1793486298),(16,'default','{\"uuid\":\"26a58eef-1391-4d5e-9396-42afe3b960b4\",\"displayName\":\"App\\\\Notifications\\\\SubscriptionNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":3:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:8;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:42:\\\"App\\\\Notifications\\\\SubscriptionNotification\\\":4:{s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:30:\\\"App\\\\Models\\\\CompanySubscription\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:2:{i:0;s:7:\\\"company\\\";i:1;s:4:\\\"plan\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:5:\\\"event\\\";s:8:\\\"assigned\\\";s:13:\\\"daysRemaining\\\";N;s:2:\\\"id\\\";s:36:\\\"11b9dc63-ecc9-436f-9884-394cf88c39e3\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}}\",\"batchId\":null},\"createdAt\":1793486299,\"delay\":null}',0,NULL,1793486299,1793486299),(17,'default','{\"uuid\":\"20630f38-9324-4337-8932-24ddff87d67a\",\"displayName\":\"App\\\\Notifications\\\\SubscriptionNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":3:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:11;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:42:\\\"App\\\\Notifications\\\\SubscriptionNotification\\\":4:{s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:30:\\\"App\\\\Models\\\\CompanySubscription\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:2:{i:0;s:7:\\\"company\\\";i:1;s:4:\\\"plan\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:5:\\\"event\\\";s:8:\\\"assigned\\\";s:13:\\\"daysRemaining\\\";N;s:2:\\\"id\\\";s:36:\\\"dc7299f7-a922-4042-a055-33ea1b257ba2\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:8:\\\"database\\\";}}\",\"batchId\":null},\"createdAt\":1793486299,\"delay\":null}',0,NULL,1793486299,1793486299),(18,'default','{\"uuid\":\"7413554b-e7cf-4535-b402-7978c95a3bab\",\"displayName\":\"App\\\\Notifications\\\\SubscriptionNotification\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\",\"command\":\"O:48:\\\"Illuminate\\\\Notifications\\\\SendQueuedNotifications\\\":3:{s:11:\\\"notifiables\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:15:\\\"App\\\\Models\\\\User\\\";s:2:\\\"id\\\";a:1:{i:0;i:11;}s:9:\\\"relations\\\";a:0:{}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:12:\\\"notification\\\";O:42:\\\"App\\\\Notifications\\\\SubscriptionNotification\\\":4:{s:12:\\\"subscription\\\";O:45:\\\"Illuminate\\\\Contracts\\\\Database\\\\ModelIdentifier\\\":5:{s:5:\\\"class\\\";s:30:\\\"App\\\\Models\\\\CompanySubscription\\\";s:2:\\\"id\\\";i:4;s:9:\\\"relations\\\";a:2:{i:0;s:7:\\\"company\\\";i:1;s:4:\\\"plan\\\";}s:10:\\\"connection\\\";s:5:\\\"mysql\\\";s:15:\\\"collectionClass\\\";N;}s:5:\\\"event\\\";s:8:\\\"assigned\\\";s:13:\\\"daysRemaining\\\";N;s:2:\\\"id\\\";s:36:\\\"dc7299f7-a922-4042-a055-33ea1b257ba2\\\";}s:8:\\\"channels\\\";a:1:{i:0;s:4:\\\"mail\\\";}}\",\"batchId\":null},\"createdAt\":1793486299,\"delay\":null}',0,NULL,1793486299,1793486299);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_balance_transactions`
--

DROP TABLE IF EXISTS `leave_balance_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_balance_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `leave_balance_id` bigint(20) unsigned NOT NULL,
  `transaction_type` varchar(40) NOT NULL,
  `days` decimal(8,2) NOT NULL,
  `balance_before` decimal(8,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(8,2) NOT NULL DEFAULT 0.00,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_balance_transactions_employee_id_foreign` (`employee_id`),
  KEY `leave_balance_transactions_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_balance_transactions_created_by_foreign` (`created_by`),
  KEY `leave_bal_tx_company_employee_type_idx` (`company_id`,`employee_id`,`leave_type_id`),
  KEY `leave_bal_tx_balance_date_idx` (`leave_balance_id`,`created_at`),
  KEY `leave_bal_tx_source_idx` (`source_type`,`source_id`),
  KEY `leave_bal_tx_company_type_idx` (`company_id`,`transaction_type`),
  CONSTRAINT `leave_balance_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_balance_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_balance_transactions_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `leave_balance_transactions_leave_balance_id_foreign` FOREIGN KEY (`leave_balance_id`) REFERENCES `leave_balances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_balance_transactions_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balance_transactions`
--

LOCK TABLES `leave_balance_transactions` WRITE;
/*!40000 ALTER TABLE `leave_balance_transactions` DISABLE KEYS */;
INSERT INTO `leave_balance_transactions` VALUES (1,1,1,1,1,'adjustment',1.00,2.00,3.00,NULL,NULL,'Manual leave balance adjustment','testing',8,'2026-10-14 13:53:17','2026-10-14 13:53:17'),(2,1,3,1,2,'adjustment',1.00,9.00,10.00,NULL,NULL,'Manual leave balance adjustment','testing',8,'2026-10-14 13:55:23','2026-10-14 13:55:23'),(3,1,3,1,2,'adjustment',-1.00,10.00,9.00,NULL,NULL,'Manual leave balance adjustment','Reverse audit trail test',8,'2026-10-14 13:57:19','2026-10-14 13:57:19'),(4,1,1,1,1,'adjustment',-1.00,3.00,2.00,NULL,NULL,'Manual leave balance adjustment','Reverse audit trail test',8,'2026-10-14 13:58:58','2026-10-14 13:58:58'),(5,1,3,1,2,'leave_used',3.00,6.00,6.00,'LeaveRequest',2,'Leave request approved','Test Leave',NULL,'2026-10-14 14:16:42','2026-10-14 14:16:42'),(6,1,3,1,2,'leave_used',2.00,4.00,4.00,'LeaveRequest',3,'Leave request approved','Testthjkl',8,'2026-10-14 14:32:04','2026-10-14 14:32:04'),(7,1,3,1,2,'pending_reversed',2.00,2.00,4.00,'LeaveRequest',4,'Leave request rejected','Rejection audit test',8,'2026-10-14 19:24:35','2026-10-14 19:24:35'),(8,1,3,1,2,'leave_used',2.00,2.00,2.00,'LeaveRequest',5,'Leave request approved','ghjkbnm,.ukl',8,'2026-10-14 20:31:03','2026-10-14 20:31:03'),(9,1,3,1,2,'leave_cancelled',2.00,2.00,4.00,'LeaveRequest',5,'Approved leave request cancelled','Leave request cancelled',8,'2026-10-14 20:32:26','2026-10-14 20:32:26'),(10,1,3,1,2,'leave_pending',2.00,4.00,2.00,'LeaveRequest',6,'Leave request submitted',',mnghfdhgfd',8,'2026-10-14 20:49:28','2026-10-14 20:49:28');
/*!40000 ALTER TABLE `leave_balance_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `leave_policy_id` bigint(20) unsigned DEFAULT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `entitled_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `accrued_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `carried_forward_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `used_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `adjusted_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `pending_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `adjustment_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_balances_employee_type_year_unique` (`company_id`,`employee_id`,`leave_type_id`,`year`),
  KEY `leave_balances_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_balances_leave_policy_id_foreign` (`leave_policy_id`),
  KEY `leave_balances_company_year_index` (`company_id`,`year`),
  KEY `leave_balances_employee_year_index` (`employee_id`,`year`),
  CONSTRAINT `leave_balances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_balances_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `leave_balances_leave_policy_id_foreign` FOREIGN KEY (`leave_policy_id`) REFERENCES `leave_policies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_balances_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balances`
--

LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
INSERT INTO `leave_balances` VALUES (1,1,1,1,2,2026,0.00,2.00,0.00,0.00,0.00,0.00,'Reverse audit trail test','2026-10-12 11:37:41','2026-10-14 13:58:58'),(2,1,3,1,2,2026,0.00,2.00,0.00,12.00,14.00,2.00,'Reverse audit trail test','2026-10-12 11:37:41','2026-10-14 20:49:28'),(3,1,1,1,2,2027,0.00,0.00,0.00,0.00,0.00,0.00,NULL,'2026-10-13 07:53:59','2026-10-13 07:53:59'),(4,1,3,1,2,2027,0.00,0.00,0.00,0.00,0.00,0.00,NULL,'2026-10-13 07:53:59','2026-10-13 07:53:59');
/*!40000 ALTER TABLE `leave_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_policies`
--

DROP TABLE IF EXISTS `leave_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_policies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `entitlement_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `accrual_method` varchar(30) NOT NULL DEFAULT 'annual',
  `accrual_days_per_period` decimal(8,2) DEFAULT NULL,
  `carry_forward_allowed` tinyint(1) NOT NULL DEFAULT 0,
  `max_carry_forward_days` decimal(8,2) DEFAULT NULL,
  `min_notice_days` int(10) unsigned NOT NULL DEFAULT 0,
  `max_consecutive_days` decimal(8,2) DEFAULT NULL,
  `allow_negative_balance` tinyint(1) NOT NULL DEFAULT 0,
  `requires_attachment` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_policies_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_policies_company_type_index` (`company_id`,`leave_type_id`),
  KEY `leave_policies_company_effective_index` (`company_id`,`effective_from`),
  CONSTRAINT `leave_policies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_policies_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_policies`
--

LOCK TABLES `leave_policies` WRITE;
/*!40000 ALTER TABLE `leave_policies` DISABLE KEYS */;
INSERT INTO `leave_policies` VALUES (1,1,1,'2026-10-12','2026-10-31',14.00,'quarterly',2.00,0,NULL,0,NULL,0,0,1,'2026-10-12 11:37:15','2026-10-12 11:37:15'),(2,1,1,'2026-10-12','2027-10-12',2.00,'monthly',2.00,0,NULL,0,NULL,0,0,1,'2026-10-12 19:35:02','2026-10-12 19:35:02');
/*!40000 ALTER TABLE `leave_policies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `leave_type_id` bigint(20) unsigned NOT NULL,
  `leave_policy_id` bigint(20) unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days` decimal(8,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_requests_leave_policy_id_foreign` (`leave_policy_id`),
  KEY `leave_requests_submitted_by_foreign` (`submitted_by`),
  KEY `leave_requests_approved_by_foreign` (`approved_by`),
  KEY `leave_requests_cancelled_by_foreign` (`cancelled_by`),
  KEY `leave_requests_company_status_index` (`company_id`,`status`),
  KEY `leave_requests_employee_status_index` (`employee_id`,`status`),
  KEY `leave_requests_dates_index` (`company_id`,`start_date`,`end_date`),
  CONSTRAINT `leave_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `leave_requests_leave_policy_id_foreign` FOREIGN KEY (`leave_policy_id`) REFERENCES `leave_policies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  CONSTRAINT `leave_requests_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
INSERT INTO `leave_requests` VALUES (1,1,3,1,2,'2026-10-17','2026-10-23',7.00,NULL,NULL,'approved',8,8,'2026-10-12 19:48:09',NULL,NULL,NULL,'2026-10-24',NULL,'2026-10-12 19:38:04','2026-10-12 19:48:09'),(2,1,3,1,2,'2026-10-14','2026-10-16',3.00,'Test Leave',NULL,'approved',8,8,'2026-10-14 14:16:42',NULL,NULL,NULL,'2026-10-17',NULL,'2026-10-14 14:11:25','2026-10-14 14:16:42'),(3,1,3,1,2,'2026-10-28','2026-10-29',2.00,'Testthjkl',NULL,'approved',8,8,'2026-10-14 14:32:04',NULL,NULL,NULL,'2026-10-30',NULL,'2026-10-14 14:28:39','2026-10-14 14:32:04'),(4,1,3,1,2,'2026-10-30','2026-10-31',2.00,'testing 1 2',NULL,'rejected',8,NULL,NULL,'Rejection audit test',NULL,NULL,'2026-11-01',NULL,'2026-10-14 19:19:43','2026-10-14 19:24:35'),(5,1,3,1,2,'2026-11-03','2026-11-04',2.00,'ghjkbnm,.ukl',NULL,'cancelled',8,8,'2026-10-14 20:31:03',NULL,8,'2026-10-14 20:32:26','2026-11-05',NULL,'2026-10-14 20:29:29','2026-10-14 20:32:26'),(6,1,3,1,2,'2026-11-10','2026-11-11',2.00,',mnghfdhgfd',NULL,'pending',8,NULL,NULL,NULL,NULL,NULL,'2026-11-12',NULL,'2026-10-14 20:49:28','2026-10-14 20:49:28');
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT 1,
  `requires_balance` tinyint(1) NOT NULL DEFAULT 1,
  `requires_attachment` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_types_company_code_unique` (`company_id`,`code`),
  KEY `leave_types_company_active_index` (`company_id`,`is_active`),
  CONSTRAINT `leave_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_types`
--

LOCK TABLES `leave_types` WRITE;
/*!40000 ALTER TABLE `leave_types` DISABLE KEYS */;
INSERT INTO `leave_types` VALUES (1,1,'Annual','EG','kdnkejifejoej  jfldvklv',1,1,0,1,1,'2026-10-12 11:36:03','2026-10-12 11:36:03');
/*!40000 ALTER TABLE `leave_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_06_085911_create_companies_table',2),(5,'2026_10_06_090245_create_company_user_table',3),(6,'2026_10_06_123259_create_permission_tables',4),(10,'2026_10_06_134953_make_spatie_team_id_nullable',5),(11,'2026_10_06_143155_add_is_super_admin_to_users_table',5),(12,'2026_10_06_144013_add_is_super_admin_to_users_table',6),(13,'2026_10_07_101018_create_user_settings_table',6),(14,'2026_10_07_140251_add_is_active_to_users_table',7),(15,'2026_10_07_221840_create_subscription_plans_table',8),(16,'2026_10_07_222530_create_company_subscriptions_table',8),(17,'2026_10_07_223548_add_pricing_to_subscription_plans_table',9),(18,'2026_10_07_224054_create_subscription_plan_prices_table',10),(19,'2026_10_08_075014_create_departments_table',11),(20,'2026_10_08_075043_create_positions_table',11),(21,'2026_10_08_080000_create_employees_table',11),(22,'2026_10_08_080329_add_manager_foreign_key_to_departments_table',12),(23,'2026_10_08_144824_create_subscription_payments_table',13),(24,'2026_10_08_160511_create_accounting_transactions_table',14),(25,'2026_10_08_162000_create_notifications_table',15),(26,'2026_10_09_102235_create_subscription_reminders_table',16),(27,'2026_10_09_145342_create_employee_separations_table',17),(28,'2026_10_09_213833_create_employee_separation_clearances_table',18),(29,'2026_10_10_123311_create_employee_final_settlements_table',19),(30,'2026_10_12_084229_create_leave_types_table',20),(31,'2026_10_12_084231_create_leave_policies_table',20),(32,'2026_10_12_084233_create_leave_balances_table',20),(33,'2026_10_12_084235_create_leave_requests_table',20),(34,'2026_10_13_100556_create_leave_balance_transactions_table',21),(35,'2026_10_15_092728_create_attendance_devices_table',22),(36,'2026_10_15_092801_create_attendance_punches_table',22),(37,'2026_10_15_093444_create_attendance_records_table',22),(38,'2026_10_15_095040_create_work_schedules_table',22),(39,'2026_10_15_095210_create_work_schedule_days_table',22),(40,'2026_10_15_095321_create_employee_work_schedules_table',22),(41,'2026_10_18_091937_create_attendance_device_employees_table',23),(42,'2026_10_31_123617_create_payroll_periods_table',24),(43,'2026_10_31_150510_create_payroll_runs_table',25),(44,'2026_10_31_211701_create_payroll_run_items_table',26),(45,'2026_10_31_224642_create_salary_structures_table',27),(46,'2026_11_01_090801_create_payroll_components_table',28),(47,'2026_11_01_091319_create_employee_payroll_components_table',29),(48,'2026_11_02_064623_create_payroll_run_item_components_table',30),(49,'2026_11_02_152008_add_attendance_leave_summary_to_payroll_run_items_table',31),(50,'2026_11_03_135231_add_attendance_options_to_payroll_runs_table',32),(51,'2026_11_06_065042_add_statutory_fields_to_payroll_run_items_table',33),(52,'2026_11_06_070204_create_statutory_rule_sets_table',34),(53,'2026_11_06_070531_create_statutory_rules_table',35),(54,'2026_11_06_070821_create_statutory_tax_bands_table',36),(55,'2026_11_06_071137_create_statutory_rule_audits_table',37),(56,'2026_11_06_072617_add_statutory_classification_to_payroll_components_tables',38),(57,'2026_11_06_083140_add_employer_statutory_totals_to_payroll_runs_table',39),(58,'2026_11_06_085508_add_statutory_tax_information_to_companies_table',40);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  `team_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`team_id`,`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  KEY `model_has_permissions_permission_id_foreign` (`permission_id`),
  KEY `model_has_permissions_team_foreign_key_index` (`team_id`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  `team_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`team_id`,`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  KEY `model_has_roles_role_id_foreign` (`role_id`),
  KEY `model_has_roles_team_foreign_key_index` (`team_id`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (12,'App\\Models\\User',8,1),(18,'App\\Models\\User',8,2),(18,'App\\Models\\User',11,2),(28,'App\\Models\\User',8,3),(32,'App\\Models\\User',10,3);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES ('23716c91-38e9-4c23-b31e-142b27aebafb','App\\Notifications\\SubscriptionNotification','App\\Models\\User',1,'{\"subscription_id\":1,\"company_id\":1,\"company_name\":\"JM CON\",\"plan_name\":\"Basic\",\"event\":\"renewal_reminder\",\"days_remaining\":7}',NULL,'2026-10-08 18:16:40','2026-10-08 18:16:40'),('277016d0-1099-45b2-a584-08d583a4e927','App\\Notifications\\SubscriptionNotification','App\\Models\\User',9,'{\"subscription_id\":2,\"company_id\":3,\"company_name\":\"BGR\",\"plan_name\":\"Basic\",\"event\":\"renewed\",\"days_remaining\":null}',NULL,'2026-10-08 18:37:28','2026-10-08 18:37:28'),('69d89f51-a6c1-46d1-8496-ba6d9a868d8d','App\\Notifications\\SubscriptionNotification','App\\Models\\User',1,'{\"subscription_id\":2,\"company_id\":3,\"company_name\":\"BGR\",\"plan_name\":\"Basic\",\"event\":\"renewal_reminder\",\"days_remaining\":2}',NULL,'2026-10-08 18:17:03','2026-10-08 18:17:03'),('87b63ebc-0384-43e8-85ad-c687d5791d9e','App\\Notifications\\SubscriptionNotification','App\\Models\\User',9,'{\"subscription_id\":2,\"company_id\":3,\"company_name\":\"BGR\",\"plan_name\":\"Basic\",\"event\":\"suspended\",\"days_remaining\":null}',NULL,'2026-10-08 18:38:01','2026-10-08 18:38:01'),('9d6a6be6-bd3d-40ef-8b97-7436438e6dbb','App\\Notifications\\SubscriptionNotification','App\\Models\\User',1,'{\"subscription_id\":2,\"company_id\":3,\"company_name\":\"BGR\",\"plan_name\":\"Basic\",\"event\":\"renewal_reminder\",\"days_remaining\":7}',NULL,'2026-10-08 18:17:03','2026-10-08 18:17:03'),('a09f7b81-38f6-464a-88bf-cad681e4b692','App\\Notifications\\SubscriptionNotification','App\\Models\\User',8,'{\"subscription_id\":3,\"company_id\":1,\"company_name\":\"JM CON\",\"plan_name\":\"Starter\",\"event\":\"assigned\",\"days_remaining\":null}',NULL,'2026-10-09 08:04:44','2026-10-09 08:04:44'),('e810af87-6ca8-4063-84cf-9f0bfbaeee5d','App\\Notifications\\SubscriptionNotification','App\\Models\\User',8,'{\"subscription_id\":3,\"company_id\":1,\"company_name\":\"JM CON\",\"plan_name\":\"Starter\",\"event\":\"renewal_reminder\",\"days_remaining\":30}',NULL,'2026-10-09 09:44:19','2026-10-09 09:44:19');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_components`
--

DROP TABLE IF EXISTS `payroll_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `type` enum('allowance','deduction') NOT NULL,
  `calculation_type` enum('fixed','percentage') NOT NULL DEFAULT 'fixed',
  `default_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 0,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_paye_taxable` tinyint(1) NOT NULL DEFAULT 0,
  `is_napsa_pensionable` tinyint(1) NOT NULL DEFAULT 0,
  `is_nhima_liable` tinyint(1) NOT NULL DEFAULT 0,
  `is_sdl_liable` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_components_company_id_code_unique` (`company_id`,`code`),
  KEY `payroll_components_company_id_type_index` (`company_id`,`type`),
  KEY `payroll_components_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `payroll_components_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_components`
--

LOCK TABLES `payroll_components` WRITE;
/*!40000 ALTER TABLE `payroll_components` DISABLE KEYS */;
INSERT INTO `payroll_components` VALUES (1,2,'Housing Allowance','HD','allowance','fixed',2000.00,1,1,1,'nil','2026-11-01 13:15:20','2026-11-01 13:15:20',1,0,0,0),(2,2,'Loan Deduction','LD','deduction','fixed',500.00,0,1,1,'nil','2026-11-01 13:16:17','2026-11-01 13:16:17',0,0,0,0);
/*!40000 ALTER TABLE `payroll_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_periods`
--

DROP TABLE IF EXISTS `payroll_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_periods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `pay_date` date DEFAULT NULL,
  `frequency` enum('weekly','biweekly','monthly') NOT NULL DEFAULT 'monthly',
  `status` enum('open','processing','processed','approved','paid','locked') NOT NULL DEFAULT 'open',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_periods_company_id_start_date_end_date_unique` (`company_id`,`start_date`,`end_date`),
  KEY `payroll_periods_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `payroll_periods_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_periods`
--

LOCK TABLES `payroll_periods` WRITE;
/*!40000 ALTER TABLE `payroll_periods` DISABLE KEYS */;
INSERT INTO `payroll_periods` VALUES (1,2,'November 2026','2026-11-01','2026-11-30','2026-11-30','monthly','open','nill','2026-10-31 12:30:17','2026-11-04 11:51:57'),(2,2,'October 2026 Payroll','2026-10-01','2026-10-31','2026-10-31','monthly','open',NULL,'2026-10-31 15:28:20','2026-10-31 15:28:20'),(4,2,'September','2026-09-01','2026-09-30','2026-09-30','monthly','open',NULL,'2026-11-03 12:24:47','2026-11-03 12:24:47'),(5,2,'December','2026-12-01','2026-12-31','2026-12-31','monthly','open',NULL,'2026-11-03 12:30:51','2026-11-03 12:30:51');
/*!40000 ALTER TABLE `payroll_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_run_item_components`
--

DROP TABLE IF EXISTS `payroll_run_item_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_run_item_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_run_item_id` bigint(20) unsigned NOT NULL,
  `payroll_component_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('allowance','deduction') NOT NULL,
  `calculation_type` enum('fixed','percentage') NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `percentage` decimal(8,2) DEFAULT NULL,
  `calculated_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_paye_taxable` tinyint(1) NOT NULL DEFAULT 0,
  `is_napsa_pensionable` tinyint(1) NOT NULL DEFAULT 0,
  `is_nhima_liable` tinyint(1) NOT NULL DEFAULT 0,
  `is_sdl_liable` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `payroll_run_item_components_payroll_component_id_foreign` (`payroll_component_id`),
  KEY `payroll_run_item_components_payroll_run_item_id_type_index` (`payroll_run_item_id`,`type`),
  CONSTRAINT `payroll_run_item_components_payroll_component_id_foreign` FOREIGN KEY (`payroll_component_id`) REFERENCES `payroll_components` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_run_item_components_payroll_run_item_id_foreign` FOREIGN KEY (`payroll_run_item_id`) REFERENCES `payroll_run_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_run_item_components`
--

LOCK TABLES `payroll_run_item_components` WRITE;
/*!40000 ALTER TABLE `payroll_run_item_components` DISABLE KEYS */;
INSERT INTO `payroll_run_item_components` VALUES (1,5,1,'Housing Allowance','allowance','fixed',1000.00,NULL,1000.00,1,'2026-11-02 08:21:21','2026-11-06 07:40:12',1,0,0,0),(2,5,2,'Loan Deduction','deduction','fixed',500.00,NULL,500.00,0,'2026-11-02 08:21:21','2026-11-06 07:40:12',0,0,0,0),(3,8,1,'Housing Allowance','allowance','fixed',1000.00,NULL,0.00,1,'2026-11-05 06:58:20','2026-11-06 08:21:40',1,0,0,0),(4,8,2,'Loan Deduction','deduction','fixed',500.00,NULL,0.00,0,'2026-11-05 06:58:20','2026-11-06 08:21:40',0,0,0,0);
/*!40000 ALTER TABLE `payroll_run_item_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_run_items`
--

DROP TABLE IF EXISTS `payroll_run_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_run_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_run_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `scheduled_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `worked_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `paid_leave_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `unpaid_leave_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `absent_days` decimal(8,2) NOT NULL DEFAULT 0.00,
  `late_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `early_departure_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `overtime_hours` decimal(8,2) NOT NULL DEFAULT 0.00,
  `total_allowances` decimal(15,2) NOT NULL DEFAULT 0.00,
  `gross_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','calculated','held') NOT NULL DEFAULT 'pending',
  `calculation_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `paye` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employee_napsa` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employee_nhima` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employer_napsa` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employer_nhima` decimal(15,2) NOT NULL DEFAULT 0.00,
  `skills_development_levy` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employee_statutory_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employer_statutory_contributions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employer_total_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `statutory_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`statutory_breakdown`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_run_items_payroll_run_id_employee_id_unique` (`payroll_run_id`,`employee_id`),
  KEY `payroll_run_items_employee_id_foreign` (`employee_id`),
  CONSTRAINT `payroll_run_items_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `payroll_run_items_payroll_run_id_foreign` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_run_items`
--

LOCK TABLES `payroll_run_items` WRITE;
/*!40000 ALTER TABLE `payroll_run_items` DISABLE KEYS */;
INSERT INTO `payroll_run_items` VALUES (5,2,4,1000.00,21.00,3.00,0.00,0.00,1.00,20,30,0.33,1002.95,2002.95,612.76,1390.19,'calculated','{\"basic_salary\":1000,\"rates\":{\"daily_rate\":47.62,\"hourly_rate\":5.95},\"options\":{\"apply_attendance_rules\":true,\"deduct_unpaid_absence\":true,\"deduct_unpaid_leave\":true,\"deduct_late_time\":true,\"deduct_early_departure\":true,\"pay_overtime\":true},\"attendance\":{\"scheduled_days\":21,\"scheduled_hours\":168,\"worked_days\":3,\"absent_days\":1,\"unpaid_leave_days\":0,\"late_minutes\":20,\"early_departure_minutes\":30,\"overtime_hours\":0.33},\"attendance_deductions\":{\"absence\":47.62,\"unpaid_leave\":0,\"late\":1.98,\"early_departure\":2.98,\"total\":52.58},\"overtime_pay\":2.95,\"component_deductions\":500,\"gross_pay\":2002.95,\"statutory\":{\"country_code\":\"ZM\",\"rule_set_id\":1,\"rule_set_name\":\"Zambia Statutory Rules 2026\",\"tax_year\":2026,\"version\":1,\"effective_from\":\"2026-01-01\",\"effective_to\":null,\"earnings_bases\":{\"basic_salary\":1000,\"taxable_allowances\":1000,\"pensionable_allowances\":0,\"nhima_allowances\":0,\"overtime_pay\":2.95,\"gross_pay\":2002.95,\"taxable_emoluments\":2002.95,\"pensionable_earnings\":1002.95,\"nhima_employee_income\":1002.95,\"nhima_employer_income\":1000,\"sdl_emoluments\":2002.95},\"employee_contributions\":{\"paye\":0,\"napsa\":50.15,\"nhima\":10.03,\"total\":60.18},\"employer_contributions\":{\"napsa\":50.15,\"nhima\":10,\"sdl\":10.01,\"total\":70.16},\"sdl_exemption\":{\"is_exempt\":false,\"reason\":null,\"organisation_type\":null,\"annual_turnover\":null,\"exemption_reference\":null},\"applied_rules\":{\"paye\":{\"code\":\"PAYE\",\"name\":\"Pay As You Earn\",\"calculation_method\":\"progressive\",\"earnings_basis\":\"taxable_emoluments\",\"rate\":null,\"maximum_earnings\":null,\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"calculation_method\":\"marginal\"}},\"employee_napsa\":{\"code\":\"NAPSA_EMPLOYEE\",\"name\":\"Employee NAPSA Contribution\",\"calculation_method\":\"percentage\",\"earnings_basis\":\"pensionable_earnings\",\"rate\":5,\"maximum_earnings\":37236,\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8}},\"employer_napsa\":{\"code\":\"NAPSA_EMPLOYER\",\"name\":\"Employer NAPSA Contribution\",\"calculation_method\":\"percentage\",\"earnings_basis\":\"pensionable_earnings\",\"rate\":5,\"maximum_earnings\":37236,\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8}},\"employee_nhima\":{\"code\":\"NHIMA_EMPLOYEE\",\"name\":\"Employee NHIMA Contribution\",\"calculation_method\":\"percentage\",\"earnings_basis\":\"nhima_employee_income\",\"rate\":1,\"maximum_earnings\":null,\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"}},\"employer_nhima\":{\"code\":\"NHIMA_EMPLOYER\",\"name\":\"Employer NHIMA Contribution\",\"calculation_method\":\"percentage\",\"earnings_basis\":\"basic_salary\",\"rate\":1,\"maximum_earnings\":null,\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"}},\"sdl\":{\"code\":\"SDL\",\"name\":\"Skills Development Levy\",\"calculation_method\":\"percentage\",\"earnings_basis\":\"sdl_chargeable_emoluments\",\"rate\":0.5,\"maximum_earnings\":null,\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"annual_turnover_exemption_threshold\":800000,\"exempt_employer_categories\":[\"public_service\",\"local_authority\",\"approved_public_benefit_organisation\",\"income_tax_exempt_organisation\",\"approved_donor\"]}}},\"employer_total_cost\":2073.11},\"total_deductions\":612.76,\"net_pay\":1390.19}','2026-11-02 08:21:21','2026-11-06 07:40:12',0.00,50.15,10.03,50.15,10.00,10.01,60.18,70.16,2073.11,'{\"country_code\":\"ZM\",\"rule_set_id\":1,\"rule_set_name\":\"Zambia Statutory Rules 2026\",\"tax_year\":2026,\"version\":1,\"effective_from\":\"2026-01-01\",\"effective_to\":null,\"earnings_bases\":{\"basic_salary\":1000,\"taxable_allowances\":1000,\"pensionable_allowances\":0,\"nhima_allowances\":0,\"overtime_pay\":2.95,\"gross_pay\":2002.95,\"taxable_emoluments\":2002.95,\"pensionable_earnings\":1002.95,\"nhima_employee_income\":1002.95,\"nhima_employer_income\":1000,\"sdl_emoluments\":2002.95},\"employee_contributions\":{\"paye\":0,\"napsa\":50.15,\"nhima\":10.03,\"total\":60.18},\"employer_contributions\":{\"napsa\":50.15,\"nhima\":10,\"sdl\":10.01,\"total\":70.16},\"sdl_exemption\":{\"is_exempt\":false,\"reason\":null,\"organisation_type\":null,\"annual_turnover\":null,\"exemption_reference\":null},\"applied_rules\":{\"paye\":{\"code\":\"PAYE\",\"name\":\"Pay As You Earn\",\"calculation_method\":\"progressive\",\"earnings_basis\":\"taxable_emoluments\",\"rate\":null,\"maximum_earnings\":null,\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"calculation_method\":\"marginal\"}},\"employee_napsa\":{\"code\":\"NAPSA_EMPLOYEE\",\"name\":\"Employee NAPSA Contribution\",\"calculation_method\":\"percentage\",\"earnings_basis\":\"pensionable_earnings\",\"rate\":5,\"maximum_earnings\":37236,\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8}},\"employer_napsa\":{\"code\":\"NAPSA_EMPLOYER\",\"name\":\"Employer NAPSA Contribution\",\"calculation_method\":\"percentage\",\"earnings_basis\":\"pensionable_earnings\",\"rate\":5,\"maximum_earnings\":37236,\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8}},\"employee_nhima\":{\"code\":\"NHIMA_EMPLOYEE\",\"name\":\"Employee NHIMA Contribution\",\"calculation_method\":\"percentage\",\"earnings_basis\":\"nhima_employee_income\",\"rate\":1,\"maximum_earnings\":null,\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"}},\"employer_nhima\":{\"code\":\"NHIMA_EMPLOYER\",\"name\":\"Employer NHIMA Contribution\",\"calculation_method\":\"percentage\",\"earnings_basis\":\"basic_salary\",\"rate\":1,\"maximum_earnings\":null,\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"}},\"sdl\":{\"code\":\"SDL\",\"name\":\"Skills Development Levy\",\"calculation_method\":\"percentage\",\"earnings_basis\":\"sdl_chargeable_emoluments\",\"rate\":0.5,\"maximum_earnings\":null,\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"annual_turnover_exemption_threshold\":800000,\"exempt_employer_categories\":[\"public_service\",\"local_authority\",\"approved_public_benefit_organisation\",\"income_tax_exempt_organisation\",\"approved_donor\"]}}},\"employer_total_cost\":2073.11}'),(6,1,4,1000.00,0.00,0.00,0.00,0.00,0.00,0,0,0.00,0.00,1000.00,0.00,1000.00,'calculated',NULL,'2026-11-02 08:36:31','2026-11-02 10:40:41',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL),(7,3,4,1000.00,0.00,0.00,0.00,0.00,0.00,0,0,0.00,0.00,1000.00,0.00,1000.00,'calculated',NULL,'2026-11-03 13:34:56','2026-11-03 13:35:30',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL),(8,4,4,5000.00,0.00,0.00,0.00,0.00,0.00,0,0,0.00,0.00,0.00,0.00,0.00,'pending',NULL,'2026-11-05 06:58:20','2026-11-06 08:21:40',0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,NULL);
/*!40000 ALTER TABLE `payroll_run_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_runs`
--

DROP TABLE IF EXISTS `payroll_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `payroll_period_id` bigint(20) unsigned NOT NULL,
  `run_number` varchar(255) DEFAULT NULL,
  `status` enum('draft','processing','calculated','approved','paid','cancelled') NOT NULL DEFAULT 'draft',
  `apply_attendance_rules` tinyint(1) NOT NULL DEFAULT 0,
  `deduct_unpaid_absence` tinyint(1) NOT NULL DEFAULT 0,
  `deduct_unpaid_leave` tinyint(1) NOT NULL DEFAULT 0,
  `deduct_late_time` tinyint(1) NOT NULL DEFAULT 0,
  `deduct_early_departure` tinyint(1) NOT NULL DEFAULT 0,
  `pay_overtime` tinyint(1) NOT NULL DEFAULT 0,
  `employee_count` int(10) unsigned NOT NULL DEFAULT 0,
  `gross_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employer_napsa` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employer_nhima` decimal(15,2) NOT NULL DEFAULT 0.00,
  `skills_development_levy` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employer_statutory_contributions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `employer_total_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_runs_company_id_run_number_unique` (`company_id`,`run_number`),
  KEY `payroll_runs_payroll_period_id_foreign` (`payroll_period_id`),
  KEY `payroll_runs_created_by_foreign` (`created_by`),
  KEY `payroll_runs_approved_by_foreign` (`approved_by`),
  KEY `payroll_runs_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `payroll_runs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_runs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_runs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_runs_payroll_period_id_foreign` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_runs`
--

LOCK TABLES `payroll_runs` WRITE;
/*!40000 ALTER TABLE `payroll_runs` DISABLE KEYS */;
INSERT INTO `payroll_runs` VALUES (1,2,2,'PR-202610-000001','calculated',0,0,0,0,0,0,1,1000.00,0.00,1000.00,0.00,0.00,0.00,0.00,0.00,1,NULL,NULL,NULL,NULL,'2026-10-31 15:38:36','2026-11-02 10:40:41'),(2,2,1,'PR-202611-000002','calculated',1,1,1,1,1,1,1,2002.95,612.76,1390.19,50.15,10.00,10.01,70.16,2073.11,8,NULL,NULL,NULL,NULL,'2026-10-31 18:57:00','2026-11-06 07:40:12'),(3,2,4,'PR-202609-000003','calculated',1,1,1,1,1,1,1,1000.00,0.00,1000.00,0.00,0.00,0.00,0.00,0.00,8,NULL,NULL,NULL,NULL,'2026-11-03 12:25:12','2026-11-03 13:35:30'),(4,2,5,'PR-202612-000004','draft',1,1,1,1,1,1,1,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,8,NULL,NULL,NULL,NULL,'2026-11-03 12:30:57','2026-11-06 08:21:40');
/*!40000 ALTER TABLE `payroll_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'companies.view','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(2,'companies.create','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(3,'companies.update','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(4,'users.view','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(5,'users.create','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(6,'users.update','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(7,'users.delete','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(8,'users.assign_role','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(9,'employees.view','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(10,'employees.create','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(11,'employees.update','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(12,'employees.delete','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(13,'leave.view','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(14,'leave.manage','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(15,'attendance.view','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(16,'attendance.manage','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(17,'payroll.view','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(18,'payroll.manage','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(19,'payroll.approve','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(20,'documents.view','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(21,'documents.manage','web','2026-10-07 10:13:21','2026-10-07 10:13:21'),(22,'reports.view','web','2026-10-07 10:13:21','2026-10-07 10:13:21');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `positions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `positions_company_id_name_unique` (`company_id`,`name`),
  UNIQUE KEY `positions_company_id_code_unique` (`company_id`,`code`),
  KEY `positions_department_id_foreign` (`department_id`),
  KEY `positions_company_id_department_id_index` (`company_id`,`department_id`),
  KEY `positions_company_id_is_active_index` (`company_id`,`is_active`),
  CONSTRAINT `positions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `positions_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
INSERT INTO `positions` VALUES (1,3,2,'Human Resource Manger','HRM','Testing',1,'2026-10-08 06:24:34','2026-10-08 06:33:04');
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (4,10),(4,11),(4,18),(4,19),(4,26),(4,27),(5,10),(5,18),(5,26),(6,10),(6,18),(6,26),(7,10),(7,18),(7,26),(8,10),(8,18),(8,26),(9,10),(9,11),(9,12),(9,13),(9,14),(9,15),(9,16),(9,17),(9,18),(9,19),(9,20),(9,21),(9,22),(9,23),(9,24),(9,25),(9,26),(9,27),(9,28),(9,29),(9,30),(9,31),(9,32),(9,33),(10,10),(10,11),(10,12),(10,18),(10,19),(10,20),(10,26),(10,27),(10,28),(11,10),(11,11),(11,12),(11,18),(11,19),(11,20),(11,26),(11,27),(11,28),(12,10),(12,11),(12,18),(12,19),(12,26),(12,27),(13,10),(13,11),(13,12),(13,15),(13,16),(13,17),(13,18),(13,19),(13,20),(13,23),(13,24),(13,25),(13,26),(13,27),(13,28),(13,31),(13,32),(13,33),(14,10),(14,11),(14,12),(14,18),(14,19),(14,20),(14,26),(14,27),(14,28),(15,10),(15,11),(15,12),(15,15),(15,16),(15,17),(15,18),(15,19),(15,20),(15,23),(15,24),(15,25),(15,26),(15,27),(15,28),(15,31),(15,32),(15,33),(16,10),(16,11),(16,12),(16,15),(16,18),(16,19),(16,20),(16,23),(16,26),(16,27),(16,28),(16,31),(17,10),(17,13),(17,14),(17,17),(17,18),(17,21),(17,22),(17,25),(17,26),(17,29),(17,30),(17,33),(18,10),(18,13),(18,14),(18,18),(18,21),(18,22),(18,26),(18,29),(18,30),(19,10),(19,13),(19,18),(19,21),(19,26),(19,29),(20,10),(20,11),(20,12),(20,16),(20,17),(20,18),(20,19),(20,20),(20,24),(20,25),(20,26),(20,27),(20,28),(20,32),(20,33),(21,10),(21,11),(21,12),(21,18),(21,19),(21,20),(21,26),(21,27),(21,28),(22,10),(22,11),(22,13),(22,14),(22,17),(22,18),(22,19),(22,21),(22,22),(22,25),(22,26),(22,27),(22,29),(22,30),(22,33);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_team_id_name_guard_name_unique` (`team_id`,`name`,`guard_name`),
  KEY `roles_team_foreign_key_index` (`team_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (9,NULL,'super_admin','web','2026-10-06 11:48:33','2026-10-06 11:48:33'),(10,1,'company_admin','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(11,1,'hr_manager','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(12,1,'hr_officer','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(13,1,'payroll_manager','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(14,1,'accountant','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(15,1,'supervisor','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(16,1,'employee','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(17,1,'auditor','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(18,2,'company_admin','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(19,2,'hr_manager','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(20,2,'hr_officer','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(21,2,'payroll_manager','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(22,2,'accountant','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(23,2,'supervisor','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(24,2,'employee','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(25,2,'auditor','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(26,3,'company_admin','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(27,3,'hr_manager','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(28,3,'hr_officer','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(29,3,'payroll_manager','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(30,3,'accountant','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(31,3,'supervisor','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(32,3,'employee','web','2026-10-11 09:17:17','2026-10-11 09:17:17'),(33,3,'auditor','web','2026-10-11 09:17:17','2026-10-11 09:17:17');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_structures`
--

DROP TABLE IF EXISTS `salary_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_structures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'ZMW',
  `pay_frequency` enum('weekly','biweekly','monthly') NOT NULL DEFAULT 'monthly',
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `salary_structures_employee_id_effective_from_unique` (`employee_id`,`effective_from`),
  KEY `salary_structures_company_id_employee_id_index` (`company_id`,`employee_id`),
  KEY `salary_structures_company_id_status_index` (`company_id`,`status`),
  CONSTRAINT `salary_structures_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `salary_structures_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_structures`
--

LOCK TABLES `salary_structures` WRITE;
/*!40000 ALTER TABLE `salary_structures` DISABLE KEYS */;
INSERT INTO `salary_structures` VALUES (1,2,4,5000.00,'ZMW','monthly','2026-09-01','2026-12-31','active','edited','2026-10-31 21:08:53','2026-11-05 06:54:55');
/*!40000 ALTER TABLE `salary_structures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('cnZvTuiaNaGKKM0K3Wb6dQx7Y5rMHC3KvJPOIJe0',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTo3OntzOjY6Il90b2tlbiI7czo0MDoiSVpJeEZsYlk1ZXpOWVRJbkFvNFZWOHZkeWpEclpiQjNvNVNXSEsweiI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjQzOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvc3RhdHV0b3J5LXJ1bGUtc2V0cy8yIjtzOjU6InJvdXRlIjtzOjI0OiJzdGF0dXRvcnktcnVsZS1zZXRzLnNob3ciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo1O3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjY0OiJiNDdhNzZkOTRhOTRmODFhOWYxYWY4ODA2NzFkM2VlNjFlNDFiNDE0Y2YwYjE1ZTI5MTk5NjkzZjQ0ODdjZjhjIjtzOjE3OiJhY3RpdmVfY29tcGFueV9pZCI7aTozO30=',1794055642),('mqtVRYr4FCqcnk98rA2CbIMXJtweVxXdrW4Ptl2F',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTo3OntzOjY6Il90b2tlbiI7czo0MDoiU3dOQ291SEZ0MzlnOTNodFB6TW9JOUFMREFXZ0lZSFF4ZU5ueEk4byI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjQzOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvc3RhdHV0b3J5LXJ1bGUtc2V0cy8yIjtzOjU6InJvdXRlIjtzOjI0OiJzdGF0dXRvcnktcnVsZS1zZXRzLnNob3ciO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo1O3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjY0OiJiNDdhNzZkOTRhOTRmODFhOWYxYWY4ODA2NzFkM2VlNjFlNDFiNDE0Y2YwYjE1ZTI5MTk5NjkzZjQ0ODdjZjhjIjtzOjE3OiJhY3RpdmVfY29tcGFueV9pZCI7aTozO30=',1794002906);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statutory_rule_audits`
--

DROP TABLE IF EXISTS `statutory_rule_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statutory_rule_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `statutory_rule_set_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `auditable_type` varchar(255) NOT NULL,
  `auditable_id` bigint(20) unsigned NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `statutory_rule_audits_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `statutory_audits_rule_set_date_index` (`statutory_rule_set_id`,`created_at`),
  KEY `statutory_rule_audits_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `statutory_rule_audits_statutory_rule_set_id_foreign` FOREIGN KEY (`statutory_rule_set_id`) REFERENCES `statutory_rule_sets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `statutory_rule_audits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statutory_rule_audits`
--

LOCK TABLES `statutory_rule_audits` WRITE;
/*!40000 ALTER TABLE `statutory_rule_audits` DISABLE KEYS */;
INSERT INTO `statutory_rule_audits` VALUES (2,1,5,'App\\Models\\Company',2,'company_sdl_exemption_updated','{\"is_sdl_exempt\":false,\"sdl_exemption_reason\":null,\"sdl_exemption_reference\":null}','{\"is_sdl_exempt\":true,\"sdl_exemption_reason\":\"Audit trail test\",\"sdl_exemption_reference\":\"TEST-001\"}','Company SDL exemption and eligibility details updated.','2026-11-06 11:59:04','2026-11-06 11:59:04'),(3,1,5,'App\\Models\\Company',2,'company_sdl_exemption_updated','{\"is_sdl_exempt\":true}','{\"is_sdl_exempt\":false}','Company SDL exemption and eligibility details updated.','2026-11-06 12:06:48','2026-11-06 12:06:48'),(4,2,5,'App\\Models\\StatutoryRuleSet',2,'statutory_rule_set_created',NULL,'{\"country_code\":\"ZM\",\"country_name\":\"Zambia\",\"name\":\"Zambia Statutory Rule 2027\",\"tax_year\":2027,\"version\":1,\"effective_from\":\"2027-01-01T00:00:00.000000Z\",\"effective_to\":null,\"status\":\"draft\"}','New statutory rule set created.','2026-11-06 14:50:04','2026-11-06 14:50:04'),(5,2,5,'App\\Models\\StatutoryRule',7,'paye_rule_created',NULL,'{\"name\":\"Pay As You Earn (PAYE)\",\"description\":null,\"earnings_basis\":\"taxable_income\",\"notes\":null,\"is_active\":true}','PAYE rule configuration saved.','2026-11-06 15:51:05','2026-11-06 15:51:05'),(6,2,5,'App\\Models\\StatutoryTaxBand',5,'paye_tax_band_created',NULL,'{\"name\":\"Test Band 1\",\"lower_limit\":\"0.00\",\"upper_limit\":\"5000.00\",\"tax_rate\":\"0.000000\",\"base_tax\":\"0.00\",\"sort_order\":1}','PAYE tax band created.','2026-11-06 17:53:47','2026-11-06 17:53:47'),(7,2,5,'App\\Models\\StatutoryTaxBand',5,'paye_tax_band_updated','{\"name\":\"Test Band 1\",\"lower_limit\":\"0.00\",\"upper_limit\":\"5000.00\",\"tax_rate\":\"0.000000\",\"base_tax\":\"0.00\",\"sort_order\":1}','{\"name\":\"Test Band 2\",\"lower_limit\":\"0.00\",\"upper_limit\":\"6000.00\",\"tax_rate\":\"0.000000\",\"base_tax\":\"0.00\",\"sort_order\":1}','PAYE tax band updated.','2026-11-06 17:55:35','2026-11-06 17:55:35'),(8,2,5,'App\\Models\\StatutoryTaxBand',5,'paye_tax_band_deleted','{\"name\":\"Test Band 2\",\"lower_limit\":\"0.00\",\"upper_limit\":\"6000.00\",\"tax_rate\":\"0.000000\",\"base_tax\":\"0.00\",\"sort_order\":1}',NULL,'PAYE tax band deleted.','2026-11-06 17:55:43','2026-11-06 17:55:43'),(9,2,5,'App\\Models\\StatutoryRule',8,'created',NULL,'{\"name\":\"Employee NAPSA Contribution\",\"rate\":\"5.000000\",\"maximum_earnings\":\"37236.00\",\"earnings_basis\":\"pensionable_earnings\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8},\"is_active\":true,\"notes\":null}','NAPSA rule created.','2026-11-06 18:21:07','2026-11-06 18:21:07'),(10,2,5,'App\\Models\\StatutoryRule',9,'created',NULL,'{\"name\":\"Employer NAPSA Contribution\",\"rate\":\"5.000000\",\"maximum_earnings\":\"37236.00\",\"earnings_basis\":\"pensionable_earnings\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8},\"is_active\":true,\"notes\":null}','NAPSA rule created.','2026-11-06 18:21:07','2026-11-06 18:21:07'),(11,2,5,'App\\Models\\StatutoryRule',8,'updated','{\"name\":\"Employee NAPSA Contribution\",\"rate\":\"5.000000\",\"maximum_earnings\":\"37236.00\",\"earnings_basis\":\"pensionable_earnings\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8},\"is_active\":true,\"notes\":null}','{\"name\":\"Employee NAPSA Contribution\",\"rate\":\"5.000000\",\"maximum_earnings\":\"37236.00\",\"earnings_basis\":\"pensionable_earnings\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8},\"is_active\":true,\"notes\":null}','NAPSA rule updated.','2026-11-06 18:21:36','2026-11-06 18:21:36'),(12,2,5,'App\\Models\\StatutoryRule',9,'updated','{\"name\":\"Employer NAPSA Contribution\",\"rate\":\"5.000000\",\"maximum_earnings\":\"37236.00\",\"earnings_basis\":\"pensionable_earnings\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8},\"is_active\":true,\"notes\":null}','{\"name\":\"Employer NAPSA Contribution\",\"rate\":\"4.000000\",\"maximum_earnings\":\"37236.00\",\"earnings_basis\":\"pensionable_earnings\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1489.44},\"is_active\":true,\"notes\":null}','NAPSA rule updated.','2026-11-06 18:21:36','2026-11-06 18:21:36'),(13,2,5,'App\\Models\\StatutoryRule',8,'updated','{\"name\":\"Employee NAPSA Contribution\",\"rate\":\"5.000000\",\"maximum_earnings\":\"37236.00\",\"earnings_basis\":\"pensionable_earnings\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8},\"is_active\":true,\"notes\":null}','{\"name\":\"Employee NAPSA Contribution\",\"rate\":\"5.000000\",\"maximum_earnings\":\"37236.00\",\"earnings_basis\":\"pensionable_earnings\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8},\"is_active\":true,\"notes\":null}','NAPSA rule updated.','2026-11-06 18:22:01','2026-11-06 18:22:01'),(14,2,5,'App\\Models\\StatutoryRule',9,'updated','{\"name\":\"Employer NAPSA Contribution\",\"rate\":\"4.000000\",\"maximum_earnings\":\"37236.00\",\"earnings_basis\":\"pensionable_earnings\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1489.44},\"is_active\":true,\"notes\":null}','{\"name\":\"Employer NAPSA Contribution\",\"rate\":\"5.000000\",\"maximum_earnings\":\"37236.00\",\"earnings_basis\":\"pensionable_earnings\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8},\"is_active\":true,\"notes\":null}','NAPSA rule updated.','2026-11-06 18:22:01','2026-11-06 18:22:01'),(15,2,5,'App\\Models\\StatutoryRule',10,'created',NULL,'{\"name\":\"Employee NHIMA Contribution\",\"rate\":\"1.000000\",\"earnings_basis\":\"nhima_employee_income\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"},\"is_active\":true,\"notes\":null}','NHIMA rule created.','2026-11-06 18:32:17','2026-11-06 18:32:17'),(16,2,5,'App\\Models\\StatutoryRule',11,'created',NULL,'{\"name\":\"Employer NHIMA Contribution\",\"rate\":\"1.000000\",\"earnings_basis\":\"basic_salary\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"},\"is_active\":true,\"notes\":null}','NHIMA rule created.','2026-11-06 18:32:17','2026-11-06 18:32:17'),(17,2,5,'App\\Models\\StatutoryRule',10,'updated','{\"name\":\"Employee NHIMA Contribution\",\"rate\":\"1.000000\",\"earnings_basis\":\"nhima_employee_income\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"},\"is_active\":true,\"notes\":null}','{\"name\":\"Employee NHIMA Contribution\",\"rate\":\"2.000000\",\"earnings_basis\":\"nhima_employee_income\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"},\"is_active\":true,\"notes\":null}','NHIMA rule updated.','2026-11-06 18:32:42','2026-11-06 18:32:42'),(18,2,5,'App\\Models\\StatutoryRule',11,'updated','{\"name\":\"Employer NHIMA Contribution\",\"rate\":\"1.000000\",\"earnings_basis\":\"basic_salary\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"},\"is_active\":true,\"notes\":null}','{\"name\":\"Employer NHIMA Contribution\",\"rate\":\"2.000000\",\"earnings_basis\":\"basic_salary\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"},\"is_active\":true,\"notes\":null}','NHIMA rule updated.','2026-11-06 18:32:42','2026-11-06 18:32:42'),(19,2,5,'App\\Models\\StatutoryRule',10,'updated','{\"name\":\"Employee NHIMA Contribution\",\"rate\":\"2.000000\",\"earnings_basis\":\"nhima_employee_income\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"},\"is_active\":true,\"notes\":null}','{\"name\":\"Employee NHIMA Contribution\",\"rate\":\"1.000000\",\"earnings_basis\":\"nhima_employee_income\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"},\"is_active\":true,\"notes\":null}','NHIMA rule updated.','2026-11-06 18:32:56','2026-11-06 18:32:56'),(20,2,5,'App\\Models\\StatutoryRule',11,'updated','{\"name\":\"Employer NHIMA Contribution\",\"rate\":\"2.000000\",\"earnings_basis\":\"basic_salary\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"},\"is_active\":true,\"notes\":null}','{\"name\":\"Employer NHIMA Contribution\",\"rate\":\"1.000000\",\"earnings_basis\":\"basic_salary\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\"},\"is_active\":true,\"notes\":null}','NHIMA rule updated.','2026-11-06 18:32:56','2026-11-06 18:32:56'),(21,2,5,'App\\Models\\StatutoryRule',12,'updated',NULL,'{\"rate\":\"0.600000\",\"earnings_basis\":\"sdl_chargeable_emoluments\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"annual_turnover_exemption_threshold\":900000,\"exempt_employer_categories\":[\"public_service\",\"local_authority\",\"approved_public_benefit_organisation\",\"income_tax_exempt_organisation\",\"approved_donor\"]},\"is_active\":true,\"notes\":null}','Updated Skills Development Levy settings.','2026-11-06 18:43:44','2026-11-06 18:43:44'),(22,2,5,'App\\Models\\StatutoryRule',12,'updated','{\"rate\":\"0.600000\",\"earnings_basis\":\"sdl_chargeable_emoluments\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"annual_turnover_exemption_threshold\":900000,\"exempt_employer_categories\":[\"public_service\",\"local_authority\",\"approved_public_benefit_organisation\",\"income_tax_exempt_organisation\",\"approved_donor\"]},\"is_active\":true,\"notes\":null}','{\"rate\":\"0.500000\",\"earnings_basis\":\"sdl_chargeable_emoluments\",\"settings\":{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"annual_turnover_exemption_threshold\":800000,\"exempt_employer_categories\":[\"public_service\",\"local_authority\",\"approved_public_benefit_organisation\",\"income_tax_exempt_organisation\",\"approved_donor\"]},\"is_active\":true,\"notes\":null}','Updated Skills Development Levy settings.','2026-11-06 18:44:26','2026-11-06 18:44:26'),(23,2,5,'App\\Models\\StatutoryTaxBand',6,'paye_tax_band_created',NULL,'{\"name\":\"Tax-Free Band\",\"lower_limit\":\"0.00\",\"upper_limit\":\"5100.00\",\"tax_rate\":\"0.000000\",\"base_tax\":\"0.00\",\"sort_order\":1}','PAYE tax band created.','2026-11-07 10:39:31','2026-11-07 10:39:31'),(24,2,5,'App\\Models\\StatutoryTaxBand',7,'paye_tax_band_created',NULL,'{\"name\":\"Second Band\",\"lower_limit\":\"5100.00\",\"upper_limit\":\"7100.00\",\"tax_rate\":\"20.000000\",\"base_tax\":\"0.00\",\"sort_order\":2}','PAYE tax band created.','2026-11-07 10:40:34','2026-11-07 10:40:34'),(25,2,5,'App\\Models\\StatutoryTaxBand',8,'paye_tax_band_created',NULL,'{\"name\":\"Third Band\",\"lower_limit\":\"7100.00\",\"upper_limit\":\"9200.00\",\"tax_rate\":\"30.000000\",\"base_tax\":\"400.00\",\"sort_order\":3}','PAYE tax band created.','2026-11-07 10:42:03','2026-11-07 10:42:03'),(26,2,5,'App\\Models\\StatutoryTaxBand',9,'paye_tax_band_created',NULL,'{\"name\":\"Fourth Band\",\"lower_limit\":\"9200.00\",\"upper_limit\":\"37000.00\",\"tax_rate\":\"37.000000\",\"base_tax\":\"1030.00\",\"sort_order\":4}','PAYE tax band created.','2026-11-07 10:43:36','2026-11-07 10:43:36'),(27,2,5,'App\\Models\\StatutoryTaxBand',10,'paye_tax_band_created',NULL,'{\"name\":\"Top Band\",\"lower_limit\":\"37000.00\",\"upper_limit\":null,\"tax_rate\":\"39.500000\",\"base_tax\":\"11316.00\",\"sort_order\":5}','PAYE tax band created.','2026-11-07 10:45:36','2026-11-07 10:45:36');
/*!40000 ALTER TABLE `statutory_rule_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statutory_rule_sets`
--

DROP TABLE IF EXISTS `statutory_rule_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statutory_rule_sets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country_code` char(2) NOT NULL DEFAULT 'ZM',
  `country_name` varchar(100) NOT NULL DEFAULT 'Zambia',
  `name` varchar(255) NOT NULL,
  `tax_year` smallint(5) unsigned NOT NULL,
  `version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `status` enum('draft','active','archived') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `activated_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `statutory_rule_sets_country_year_version_unique` (`country_code`,`tax_year`,`version`),
  KEY `statutory_rule_sets_created_by_foreign` (`created_by`),
  KEY `statutory_rule_sets_country_code_status_index` (`country_code`,`status`),
  KEY `statutory_rule_sets_effective_from_effective_to_index` (`effective_from`,`effective_to`),
  CONSTRAINT `statutory_rule_sets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statutory_rule_sets`
--

LOCK TABLES `statutory_rule_sets` WRITE;
/*!40000 ALTER TABLE `statutory_rule_sets` DISABLE KEYS */;
INSERT INTO `statutory_rule_sets` VALUES (1,'ZM','Zambia','Zambia Statutory Rules 2026',2026,1,'2026-01-01',NULL,'active',NULL,'2026-11-06 06:14:56','Initial Zambia statutory configuration for the 2026 tax year.','2026-11-06 06:14:22','2026-11-06 06:14:56'),(2,'ZM','Zambia','Zambia Statutory Rule 2027',2027,1,'2027-01-01',NULL,'draft',5,NULL,NULL,'2026-11-06 14:50:04','2026-11-06 14:50:04');
/*!40000 ALTER TABLE `statutory_rule_sets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statutory_rules`
--

DROP TABLE IF EXISTS `statutory_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statutory_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `statutory_rule_set_id` bigint(20) unsigned NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `rule_type` varchar(50) NOT NULL,
  `applies_to` varchar(30) NOT NULL DEFAULT 'employee',
  `calculation_method` varchar(50) NOT NULL,
  `earnings_basis` varchar(100) DEFAULT NULL,
  `rate` decimal(10,6) DEFAULT NULL,
  `fixed_amount` decimal(15,2) DEFAULT NULL,
  `minimum_earnings` decimal(15,2) DEFAULT NULL,
  `maximum_earnings` decimal(15,2) DEFAULT NULL,
  `contribution_ceiling` decimal(15,2) DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `statutory_rules_set_code_unique` (`statutory_rule_set_id`,`code`),
  KEY `statutory_rules_statutory_rule_set_id_is_active_index` (`statutory_rule_set_id`,`is_active`),
  KEY `statutory_rules_rule_type_index` (`rule_type`),
  CONSTRAINT `statutory_rules_statutory_rule_set_id_foreign` FOREIGN KEY (`statutory_rule_set_id`) REFERENCES `statutory_rule_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statutory_rules`
--

LOCK TABLES `statutory_rules` WRITE;
/*!40000 ALTER TABLE `statutory_rules` DISABLE KEYS */;
INSERT INTO `statutory_rules` VALUES (1,1,'PAYE','Pay As You Earn','Progressive employee income tax.','tax','employee','progressive','taxable_emoluments',NULL,NULL,NULL,NULL,NULL,'{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"calculation_method\":\"marginal\"}',1,1,NULL,'2026-11-06 06:14:23','2026-11-06 06:14:23'),(2,1,'NAPSA_EMPLOYEE','Employee NAPSA Contribution','Employee pension contribution.','pension','employee','percentage','pensionable_earnings',5.000000,NULL,NULL,37236.00,NULL,'{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8}',1,2,NULL,'2026-11-06 06:14:23','2026-11-06 06:14:23'),(3,1,'NAPSA_EMPLOYER','Employer NAPSA Contribution','Employer pension contribution.','pension','employer','percentage','pensionable_earnings',5.000000,NULL,NULL,37236.00,NULL,'{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8}',1,3,NULL,'2026-11-06 06:14:23','2026-11-06 06:14:23'),(4,1,'NHIMA_EMPLOYEE','Employee NHIMA Contribution','Employee National Health Insurance contribution.','health','employee','percentage','nhima_employee_income',1.000000,NULL,NULL,NULL,NULL,'{\"period\":\"monthly\",\"rate_unit\":\"percentage\"}',1,4,NULL,'2026-11-06 06:14:23','2026-11-06 06:14:23'),(5,1,'NHIMA_EMPLOYER','Employer NHIMA Contribution','Employer National Health Insurance contribution.','health','employer','percentage','basic_salary',1.000000,NULL,NULL,NULL,NULL,'{\"period\":\"monthly\",\"rate_unit\":\"percentage\"}',1,5,NULL,'2026-11-06 06:14:23','2026-11-06 06:14:23'),(6,1,'SDL','Skills Development Levy','Employer Skills Development Levy.','levy','employer','percentage','sdl_chargeable_emoluments',0.500000,NULL,NULL,NULL,NULL,'{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"annual_turnover_exemption_threshold\":800000,\"exempt_employer_categories\":[\"public_service\",\"local_authority\",\"approved_public_benefit_organisation\",\"income_tax_exempt_organisation\",\"approved_donor\"]}',1,6,NULL,'2026-11-06 06:14:23','2026-11-06 06:14:23'),(7,2,'PAYE','Pay As You Earn (PAYE)',NULL,'tax','employee','progressive','taxable_income',NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-11-06 15:51:05','2026-11-06 15:51:05'),(8,2,'NAPSA_EMPLOYEE','Employee NAPSA Contribution',NULL,'pension','employee','percentage','pensionable_earnings',5.000000,NULL,NULL,37236.00,NULL,'{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8}',1,2,NULL,'2026-11-06 18:21:07','2026-11-06 18:21:07'),(9,2,'NAPSA_EMPLOYER','Employer NAPSA Contribution',NULL,'pension','employer','percentage','pensionable_earnings',5.000000,NULL,NULL,37236.00,NULL,'{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"earnings_ceiling\":37236,\"maximum_contribution\":1861.8}',1,3,NULL,'2026-11-06 18:21:07','2026-11-06 18:22:01'),(10,2,'NHIMA_EMPLOYEE','Employee NHIMA Contribution',NULL,'health','employee','percentage','nhima_employee_income',1.000000,NULL,NULL,NULL,NULL,'{\"period\":\"monthly\",\"rate_unit\":\"percentage\"}',1,4,NULL,'2026-11-06 18:32:17','2026-11-06 18:32:56'),(11,2,'NHIMA_EMPLOYER','Employer NHIMA Contribution',NULL,'health','employer','percentage','basic_salary',1.000000,NULL,NULL,NULL,NULL,'{\"period\":\"monthly\",\"rate_unit\":\"percentage\"}',1,5,NULL,'2026-11-06 18:32:17','2026-11-06 18:32:56'),(12,2,'SDL','Skills Development Levy',NULL,'levy','employer','percentage','sdl_chargeable_emoluments',0.500000,NULL,NULL,NULL,NULL,'{\"period\":\"monthly\",\"rate_unit\":\"percentage\",\"annual_turnover_exemption_threshold\":800000,\"exempt_employer_categories\":[\"public_service\",\"local_authority\",\"approved_public_benefit_organisation\",\"income_tax_exempt_organisation\",\"approved_donor\"]}',1,0,NULL,'2026-11-06 18:43:44','2026-11-06 18:44:26');
/*!40000 ALTER TABLE `statutory_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statutory_tax_bands`
--

DROP TABLE IF EXISTS `statutory_tax_bands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statutory_tax_bands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `statutory_rule_id` bigint(20) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `lower_limit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `upper_limit` decimal(15,2) DEFAULT NULL,
  `tax_rate` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `base_tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `statutory_tax_bands_rule_order_unique` (`statutory_rule_id`,`sort_order`),
  KEY `statutory_tax_bands_statutory_rule_id_lower_limit_index` (`statutory_rule_id`,`lower_limit`),
  CONSTRAINT `statutory_tax_bands_statutory_rule_id_foreign` FOREIGN KEY (`statutory_rule_id`) REFERENCES `statutory_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statutory_tax_bands`
--

LOCK TABLES `statutory_tax_bands` WRITE;
/*!40000 ALTER TABLE `statutory_tax_bands` DISABLE KEYS */;
INSERT INTO `statutory_tax_bands` VALUES (1,1,'Tax-free band',0.00,5100.00,0.000000,0.00,1,'2026-11-06 06:14:23','2026-11-06 06:14:23'),(2,1,'Second band',5100.00,7100.00,20.000000,0.00,2,'2026-11-06 06:14:23','2026-11-06 06:14:23'),(3,1,'Third band',7100.00,9200.00,30.000000,400.00,3,'2026-11-06 06:14:23','2026-11-06 06:14:23'),(4,1,'Top band',9200.00,NULL,37.000000,1030.00,4,'2026-11-06 06:14:23','2026-11-06 06:14:23'),(6,7,'Tax-Free Band',0.00,5100.00,0.000000,0.00,1,'2026-11-07 10:39:31','2026-11-07 10:39:31'),(7,7,'Second Band',5100.00,7100.00,20.000000,0.00,2,'2026-11-07 10:40:34','2026-11-07 10:40:34'),(8,7,'Third Band',7100.00,9200.00,30.000000,400.00,3,'2026-11-07 10:42:03','2026-11-07 10:42:03'),(9,7,'Fourth Band',9200.00,37000.00,37.000000,1030.00,4,'2026-11-07 10:43:36','2026-11-07 10:43:36'),(10,7,'Top Band',37000.00,NULL,39.500000,11316.00,5,'2026-11-07 10:45:36','2026-11-07 10:45:36');
/*!40000 ALTER TABLE `statutory_tax_bands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_payments`
--

DROP TABLE IF EXISTS `subscription_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_subscription_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'ZMW',
  `payment_date` date NOT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_payments_recorded_by_foreign` (`recorded_by`),
  KEY `subscription_payments_company_subscription_id_payment_date_index` (`company_subscription_id`,`payment_date`),
  CONSTRAINT `subscription_payments_company_subscription_id_foreign` FOREIGN KEY (`company_subscription_id`) REFERENCES `company_subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscription_payments_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_payments`
--

LOCK TABLES `subscription_payments` WRITE;
/*!40000 ALTER TABLE `subscription_payments` DISABLE KEYS */;
INSERT INTO `subscription_payments` VALUES (1,1,200.00,'ZMW','2026-10-08','677788','bank_transfer','testing',5,'2026-10-08 13:29:01','2026-10-08 13:29:01'),(2,1,200.00,'ZMW','2026-10-08','tttyj','bank_transfer','nil',5,'2026-10-08 13:32:10','2026-10-08 13:32:10'),(3,1,200.00,'ZMW','2026-10-08','tttyj','bank_transfer','nil',5,'2026-10-08 13:32:10','2026-10-08 13:32:10'),(4,1,200.00,'ZMW','2026-10-08',NULL,'bank_transfer','fdgg',5,'2026-10-08 13:42:20','2026-10-08 13:42:20'),(5,1,200.00,'ZMW','2026-10-08','556ty','bank_transfer',NULL,5,'2026-10-08 13:47:32','2026-10-08 13:47:32'),(6,1,2000.00,'ZMW','2026-10-08',NULL,NULL,NULL,5,'2026-10-08 13:52:57','2026-10-08 13:52:57'),(7,1,200.00,'ZMW','2026-10-08',NULL,'cash',NULL,5,'2026-10-08 13:53:48','2026-10-08 13:53:48'),(8,1,200.00,'ZMW','2026-10-08','hhjhj',NULL,NULL,5,'2026-10-08 13:54:06','2026-10-08 13:54:06'),(9,1,200.00,'ZMW','2026-10-08','878788','cash',NULL,5,'2026-10-08 14:11:25','2026-10-08 14:11:25');
/*!40000 ALTER TABLE `subscription_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_plan_prices`
--

DROP TABLE IF EXISTS `subscription_plan_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_plan_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_plan_id` bigint(20) unsigned NOT NULL,
  `billing_cycle` enum('monthly','quarterly','semi_annual','annual') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_plan_prices_subscription_plan_id_foreign` (`subscription_plan_id`),
  CONSTRAINT `subscription_plan_prices_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_plan_prices`
--

LOCK TABLES `subscription_plan_prices` WRITE;
/*!40000 ALTER TABLE `subscription_plan_prices` DISABLE KEYS */;
INSERT INTO `subscription_plan_prices` VALUES (1,1,'monthly',100.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(2,1,'quarterly',270.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(3,1,'semi_annual',500.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(4,1,'annual',1000.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(5,2,'monthly',200.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(6,2,'quarterly',540.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(7,2,'semi_annual',1000.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(8,2,'annual',2000.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(9,3,'monthly',350.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(10,3,'quarterly',945.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(11,3,'semi_annual',1750.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(12,3,'annual',3500.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(13,4,'monthly',600.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(14,4,'quarterly',1620.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(15,4,'semi_annual',3000.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(16,4,'annual',6000.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(17,5,'monthly',1200.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(18,5,'quarterly',3240.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(19,5,'semi_annual',6000.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(20,5,'annual',12000.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(21,6,'monthly',0.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(22,6,'quarterly',0.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(23,6,'semi_annual',0.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43'),(24,6,'annual',0.00,1,'2026-10-07 20:45:43','2026-10-07 20:45:43');
/*!40000 ALTER TABLE `subscription_plan_prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_plans`
--

DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `employee_limit` int(10) unsigned DEFAULT NULL,
  `billing_cycle` enum('monthly','quarterly','semi_annual','annual') NOT NULL DEFAULT 'monthly',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_plans_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_plans`
--

LOCK TABLES `subscription_plans` WRITE;
/*!40000 ALTER TABLE `subscription_plans` DISABLE KEYS */;
INSERT INTO `subscription_plans` VALUES (1,'Starter','starter','For small businesses getting started with PAYROW.',10,'monthly',1,'2026-10-07 20:37:01','2026-10-07 20:37:01'),(2,'Basic','basic','For growing businesses with larger teams.',25,'monthly',1,'2026-10-07 20:37:01','2026-10-07 20:45:43'),(3,'Standard','standard','For established businesses managing a larger workforce.',50,'monthly',1,'2026-10-07 20:37:01','2026-10-07 20:45:43'),(4,'Professional','professional','For businesses with larger HR and payroll requirements.',100,'monthly',1,'2026-10-07 20:37:01','2026-10-07 20:45:43'),(5,'Business','business','For larger organizations with extensive workforce requirements.',250,'monthly',1,'2026-10-07 20:37:01','2026-10-07 20:45:43'),(6,'Enterprise','enterprise','Unlimited employees with custom enterprise pricing.',NULL,'monthly',1,'2026-10-07 20:37:01','2026-10-07 20:45:43');
/*!40000 ALTER TABLE `subscription_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_reminders`
--

DROP TABLE IF EXISTS `subscription_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_reminders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_subscription_id` bigint(20) unsigned NOT NULL,
  `days_before` int(10) unsigned NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_reminder_unique` (`company_subscription_id`,`days_before`),
  CONSTRAINT `subscription_reminders_company_subscription_id_foreign` FOREIGN KEY (`company_subscription_id`) REFERENCES `company_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_reminders`
--

LOCK TABLES `subscription_reminders` WRITE;
/*!40000 ALTER TABLE `subscription_reminders` DISABLE KEYS */;
INSERT INTO `subscription_reminders` VALUES (1,3,30,'2026-10-09 09:44:16','2026-10-09 09:44:16','2026-10-09 09:44:16');
/*!40000 ALTER TABLE `subscription_reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `timezone` varchar(255) NOT NULL DEFAULT 'Africa/Lusaka',
  `date_format` varchar(255) NOT NULL DEFAULT 'd/m/Y',
  `language` varchar(255) NOT NULL DEFAULT 'en',
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `system_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_settings_user_id_unique` (`user_id`),
  CONSTRAINT `user_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_settings`
--

LOCK TABLES `user_settings` WRITE;
/*!40000 ALTER TABLE `user_settings` DISABLE KEYS */;
INSERT INTO `user_settings` VALUES (1,5,'+260977063306','Africa/Lusaka','d/m/Y','en',1,1,'2026-10-07 08:17:30','2026-10-07 08:31:03'),(2,1,NULL,'Africa/Lusaka','d/m/Y','en',1,1,'2026-10-07 10:16:27','2026-10-07 10:16:27'),(7,8,'098765400','Africa/Lusaka','d/m/Y','en',1,1,'2026-10-07 17:45:12','2026-10-07 19:39:26'),(8,9,'09998876','Africa/Lusaka','d/m/Y','en',1,1,'2026-10-07 19:55:28','2026-10-07 19:55:28'),(9,10,NULL,'Africa/Lusaka','d/m/Y','en',1,1,'2026-10-09 12:01:53','2026-10-09 12:01:53'),(10,11,'1234678','Africa/Lusaka','d/m/Y','en',1,1,'2026-10-11 21:35:35','2026-10-11 21:35:35');
/*!40000 ALTER TABLE `user_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Daniel Kuch','danielhashkuch@gmail.com',NULL,'$2y$12$oHQT2vlg65jUz7vk3sPRUus2mCqn3jwpnfnVOuyid5LyP/cyZfu1K',0,1,NULL,'2026-10-06 06:53:48','2026-10-06 20:48:29'),(2,'Test User','test@example.com','2026-10-06 10:55:18','$2y$12$i/UKKJQpuxxenkB7POofkOKsMMC1/gWbbmtZ9kE9tBLBE.LKYKH2a',0,1,'Z7f4aSzmWx','2026-10-06 10:55:18','2026-10-07 18:11:44'),(5,'Super Admin','admin@payrow.test',NULL,'$2y$12$VpFhLxQQs2IwodTXNFL.mecJJBrpLQFgXll0HYGacv3vyb0w7C0L.',1,1,NULL,'2026-10-06 12:43:25','2026-10-06 12:43:25'),(8,'Kappa Do','Kappa@gmail.com',NULL,'$2y$12$1vqSBD7Lpg3KXodeMyj3BusFNuZXWuL27Y0q3ok1JhXvZhkYK2Ywy',0,1,NULL,'2026-10-07 17:45:12','2026-10-07 19:39:26'),(9,'John Doee','Doe@gmail.com',NULL,'$2y$12$Bm4vzq6RqfIUf4wdLMwW7OtNNvhhp2emId7ndPPyF8BHpw7MoZ43m',0,1,NULL,'2026-10-07 19:55:28','2026-10-09 11:54:06'),(10,'hash','hash@gmail.com',NULL,'$2y$12$qE5R3AEo2j1peshbr00H.Ox3zBOCtYiXDiH922Wez85B9xjQwR7mO',0,1,NULL,'2026-10-09 12:01:53','2026-10-09 12:01:53'),(11,'Daniel Hash','danielhash@gmail.com',NULL,'$2y$12$ZqGYWCc.h7v0Y0sZQ9fXieLCswVHhBv1xSNkpSlTGZZBF8t9FD9vG',0,1,NULL,'2026-10-11 21:35:35','2026-10-11 21:35:35');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_schedule_days`
--

DROP TABLE IF EXISTS `work_schedule_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_schedule_days` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `work_schedule_id` bigint(20) unsigned NOT NULL,
  `day_of_week` tinyint(3) unsigned NOT NULL,
  `is_working_day` tinyint(1) NOT NULL DEFAULT 1,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `break_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `grace_period_minutes` int(10) unsigned DEFAULT NULL,
  `early_departure_grace_minutes` int(10) unsigned DEFAULT NULL,
  `allow_overtime` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `work_schedule_days_schedule_day_unique` (`work_schedule_id`,`day_of_week`),
  KEY `work_schedule_days_schedule_working_idx` (`work_schedule_id`,`is_working_day`),
  CONSTRAINT `work_schedule_days_work_schedule_id_foreign` FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_schedule_days`
--

LOCK TABLES `work_schedule_days` WRITE;
/*!40000 ALTER TABLE `work_schedule_days` DISABLE KEYS */;
INSERT INTO `work_schedule_days` VALUES (1,1,1,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 08:29:25','2026-10-15 08:29:25'),(2,1,2,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 08:29:25','2026-10-15 08:29:25'),(3,1,3,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 08:29:25','2026-10-15 08:29:25'),(4,1,4,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 08:29:25','2026-10-15 08:29:25'),(5,1,5,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 08:29:25','2026-10-15 08:29:25'),(6,1,6,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 08:29:25','2026-10-15 08:29:25'),(7,1,7,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 08:29:25','2026-10-15 08:29:25'),(8,2,1,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:18:20','2026-10-15 09:18:20'),(9,2,2,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:18:20','2026-10-15 09:18:20'),(10,2,3,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:18:20','2026-10-15 09:18:20'),(11,2,4,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:18:20','2026-10-15 09:18:20'),(12,2,5,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:18:20','2026-10-15 09:18:20'),(13,2,6,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 09:18:20','2026-10-15 09:18:20'),(14,2,7,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 09:18:20','2026-10-15 09:18:20'),(15,3,1,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:36:06','2026-10-15 09:36:06'),(16,3,2,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:36:06','2026-10-15 09:36:06'),(17,3,3,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:36:06','2026-10-15 09:36:06'),(18,3,4,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:36:06','2026-10-15 09:36:06'),(19,3,5,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:36:06','2026-10-15 09:36:06'),(20,3,6,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 09:36:06','2026-10-15 09:36:06'),(21,3,7,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 09:36:06','2026-10-15 09:36:06'),(22,4,1,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:39:29','2026-10-15 09:39:29'),(23,4,2,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:39:29','2026-10-15 09:39:29'),(24,4,3,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:39:29','2026-10-15 09:39:29'),(25,4,4,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:39:29','2026-10-15 09:39:29'),(26,4,5,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:39:29','2026-10-15 09:39:29'),(27,4,6,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 09:39:29','2026-10-15 09:39:29'),(28,4,7,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 09:39:29','2026-10-15 09:39:29'),(29,5,1,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:40:17','2026-10-15 09:40:17'),(30,5,2,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:40:17','2026-10-15 09:40:17'),(31,5,3,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:40:17','2026-10-15 09:40:17'),(32,5,4,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:40:17','2026-10-15 09:40:17'),(33,5,5,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:40:17','2026-10-15 09:40:17'),(34,5,6,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 09:40:17','2026-10-15 09:40:17'),(35,5,7,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 09:40:17','2026-10-15 09:40:17'),(36,6,1,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:45:43','2026-10-15 10:25:16'),(37,6,2,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:45:43','2026-10-15 10:25:16'),(38,6,3,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:45:43','2026-10-15 10:25:16'),(39,6,4,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:45:43','2026-10-15 10:25:16'),(40,6,5,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 09:45:43','2026-10-15 10:25:16'),(41,6,6,0,NULL,NULL,0,NULL,NULL,NULL,'2026-10-15 09:45:43','2026-10-15 10:25:16'),(42,6,7,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 09:45:43','2026-10-15 10:25:16'),(43,7,1,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 10:00:39','2026-10-15 10:00:39'),(44,7,2,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 10:00:39','2026-10-15 10:00:39'),(45,7,3,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 10:00:39','2026-10-15 10:00:39'),(46,7,4,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 10:00:39','2026-10-15 10:00:39'),(47,7,5,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-10-15 10:00:39','2026-10-15 10:00:39'),(48,7,6,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 10:00:39','2026-10-15 10:00:39'),(49,7,7,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-10-15 10:00:39','2026-10-15 10:00:39'),(50,8,1,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-11-02 18:41:03','2026-11-03 10:31:15'),(51,8,2,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-11-02 18:41:03','2026-11-03 10:31:15'),(52,8,3,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-11-02 18:41:03','2026-11-03 10:31:15'),(53,8,4,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-11-02 18:41:03','2026-11-03 10:31:15'),(54,8,5,1,'08:00:00','17:00:00',60,NULL,NULL,NULL,'2026-11-02 18:41:03','2026-11-03 10:31:15'),(55,8,6,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-11-02 18:41:03','2026-11-03 10:31:15'),(56,8,7,0,'08:00:00','13:00:00',0,NULL,NULL,NULL,'2026-11-02 18:41:03','2026-11-03 10:31:15');
/*!40000 ALTER TABLE `work_schedule_days` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_schedules`
--

DROP TABLE IF EXISTS `work_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `default_start_time` time NOT NULL,
  `default_end_time` time NOT NULL,
  `default_break_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `grace_period_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `early_departure_grace_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `overtime_after_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `allow_overtime` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `work_schedules_company_code_unique` (`company_id`,`code`),
  KEY `work_schedules_company_active_idx` (`company_id`,`is_active`),
  CONSTRAINT `work_schedules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_schedules`
--

LOCK TABLES `work_schedules` WRITE;
/*!40000 ALTER TABLE `work_schedules` DISABLE KEYS */;
INSERT INTO `work_schedules` VALUES (1,1,'Standard Office Hours','Code: STD-0800',NULL,'08:00:00','17:00:00',60,15,0,0,1,1,'2026-10-15 08:29:25','2026-10-15 08:29:25'),(2,1,'Staff','STD-070',NULL,'08:00:00','17:00:00',60,15,0,30,1,1,'2026-10-15 09:18:20','2026-10-17 12:28:01'),(3,1,'Standard Office Hours','STD-001',NULL,'08:00:00','17:00:00',60,15,0,0,1,1,'2026-10-15 09:36:06','2026-10-15 09:36:06'),(4,1,'Standard Office Hours','STD-002',NULL,'08:00:00','17:00:00',60,15,0,0,1,1,'2026-10-15 09:39:29','2026-10-15 09:39:29'),(5,1,'Standard Office Hours','STD-003',NULL,'08:00:00','17:00:00',60,15,0,0,1,1,'2026-10-15 09:40:17','2026-10-15 09:40:17'),(6,1,'Test Schedule','jhhhhhh',NULL,'09:00:00','17:00:00',60,20,0,0,0,1,'2026-10-15 09:45:43','2026-10-15 10:25:16'),(7,1,'ghhhhhhh','dfghjkl',NULL,'08:00:00','17:00:00',60,15,0,0,1,1,'2026-10-15 10:00:39','2026-10-15 10:00:39'),(8,2,'Standard Office Working Hours','STD-10001',NULL,'08:00:00','17:00:00',60,15,0,10,1,1,'2026-11-02 18:41:03','2026-11-03 10:31:15');
/*!40000 ALTER TABLE `work_schedules` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-07 14:57:20
