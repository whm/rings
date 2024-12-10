-- MariaDB dump 10.19  Distrib 10.11.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Server version	10.11.6-MariaDB-0+deb12u1

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
-- Table structure for table `groups`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `group_id` varchar(16) NOT NULL DEFAULT '',
  `group_name` varchar(64) NOT NULL DEFAULT '',
  `group_description` varchar(255) NOT NULL DEFAULT '',
  `date_last_maint` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `next_number`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `next_number` (
  `id` char(32) NOT NULL DEFAULT '',
  `next_number` int(11) NOT NULL DEFAULT 0,
  `date_last_maint` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `people_or_places`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_or_places` (
  `uid` varchar(32) NOT NULL DEFAULT '',
  `auth_uid` varchar(32) DEFAULT NULL,
  `cn` varchar(255) DEFAULT NULL,
  `display_name` varchar(255) NOT NULL DEFAULT '',
  `date_of_birth` date DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `visibility` varchar(16) NOT NULL DEFAULT 'SHOW',
  `date_last_maint` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`uid`),
  KEY `auth_uid` (`auth_uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `picture_action_queue`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `picture_action_queue` (
  `pid` int(11) NOT NULL,
  `action` varchar(16) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'PENDING',
  `error_text` varchar(255) DEFAULT NULL,
  `date_last_maint` datetime NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`pid`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `picture_comments_grades`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `picture_comments_grades` (
  `pid` int(11) NOT NULL DEFAULT 0,
  `uid` varchar(32) NOT NULL,
  `comment` text NOT NULL,
  `grade` char(1) DEFAULT NULL,
  `date_last_maint` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`pid`,`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `picture_details`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `picture_details` (
  `size_id` varchar(16) NOT NULL,
  `pid` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `mime_type` varchar(64) NOT NULL,
  `width` int(11) NOT NULL DEFAULT 0,
  `height` int(11) DEFAULT 0,
  `size` int(11) NOT NULL,
  `format` varchar(64) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `date_last_maint` datetime NOT NULL DEFAULT current_timestamp(),
  `date_added` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`size_id`,`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `picture_grades`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `picture_grades` (
  `pid` int(11) NOT NULL,
  `uid` varchar(32) NOT NULL,
  `grade` varchar(4) NOT NULL,
  `date_last_maint` datetime NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`pid`,`uid`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `picture_groups`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `picture_groups` (
  `group_id` varchar(16) NOT NULL DEFAULT '',
  `uid` varchar(32) NOT NULL DEFAULT '',
  `date_last_maint` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`group_id`,`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `picture_rings`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `picture_rings` (
  `uid` char(32) NOT NULL DEFAULT '',
  `pid` int(11) NOT NULL DEFAULT 0,
  `date_last_maint` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`uid`,`pid`),
  KEY `index_pid` (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `picture_upload_queue`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `picture_upload_queue` (
  `path` varchar(255) NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'PENDING',
  `error_text` varchar(255) DEFAULT NULL,
  `date_last_maint` datetime NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pictures_information`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pictures_information` (
  `pid` int(11) NOT NULL DEFAULT 0,
  `source_file` varchar(255) DEFAULT NULL,
  `picture_lot` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL DEFAULT 'UNKNOWN',
  `date_taken` varchar(32) NOT NULL DEFAULT 'UNKNOWN',
  `picture_date` datetime DEFAULT NULL,
  `picture_sequence` int(11) NOT NULL DEFAULT 1,
  `camera_date` datetime DEFAULT NULL,
  `taken_by` varchar(64) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `key_words` varchar(255) NOT NULL DEFAULT 'NEW',
  `grade` varchar(4) NOT NULL DEFAULT 'A',
  `public` varchar(1) NOT NULL DEFAULT 'Y',
  `raw_picture_size` int(11) DEFAULT NULL,
  `raw_signature` varchar(255) DEFAULT NULL,
  `camera` varchar(32) DEFAULT NULL,
  `shutter_speed` varchar(16) DEFAULT NULL,
  `fstop` varchar(16) DEFAULT NULL,
  `date_last_maint` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`pid`),
  KEY `key_words` (`key_words`),
  KEY `date_taken_index` (`date_taken`),
  KEY `date_added_index` (`date_added`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `uid` varchar(32) NOT NULL,
  `password` varchar(32) NOT NULL DEFAULT '',
  `email_address` varchar(128) DEFAULT NULL,
  `common_name` varchar(32) NOT NULL DEFAULT '',
  `privilege` enum('ADMINISTRATOR','MAINTAINER','USER') DEFAULT NULL,
  `date_last_maint` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_added` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-12-09 22:57:22
-- MariaDB dump 10.19  Distrib 10.11.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: shelter-db    Database: delaine
-- ------------------------------------------------------
-- Server version	10.11.6-MariaDB-0+deb12u1

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
-- Table structure for table `picture_types`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `picture_types` (
  `mime_type` varchar(32) NOT NULL DEFAULT 'image/jpeg',
  `file_type` varchar(8) NOT NULL DEFAULT 'jpg',
  `date_last_maint` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`mime_type`,`file_type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `picture_types`
--

LOCK TABLES `picture_types` WRITE;
/*!40000 ALTER TABLE `picture_types` DISABLE KEYS */;
INSERT INTO `picture_types` VALUES
('image/gif','gif','2016-09-17 08:16:35','2016-09-17 08:16:35'),
('image/jpeg','jpeg','2016-09-17 07:33:01','2016-09-17 07:33:01'),
('image/jpg','jpg','2016-09-17 07:33:01','2016-09-17 07:33:01'),
('image/png','png','2016-09-17 08:16:35','2016-09-17 08:16:35'),
('image/x-png','png','2016-12-03 18:35:38','2016-12-03 18:35:38');
/*!40000 ALTER TABLE `picture_types` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-12-09 23:03:08
-- MariaDB dump 10.19  Distrib 10.11.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: shelter-db    Database: delaine
-- ------------------------------------------------------
-- Server version	10.11.6-MariaDB-0+deb12u1

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
-- Table structure for table `picture_sizes`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `picture_sizes` (
  `size_id` varchar(16) NOT NULL,
  `description` varchar(32) NOT NULL,
  `picture_table` varchar(32) NOT NULL,
  `max_height` int(11) NOT NULL DEFAULT 0,
  `max_width` int(11) NOT NULL DEFAULT 0,
  `store_file` tinyint(1) DEFAULT NULL,
  `store_db` tinyint(1) DEFAULT NULL,
  `date_last_maint` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`size_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `picture_sizes`
--

LOCK TABLES `picture_sizes` WRITE;
/*!40000 ALTER TABLE `picture_sizes` DISABLE KEYS */;
INSERT INTO `picture_sizes` VALUES
('125x125','small','pictures_small',125,125,NULL,NULL,'2016-09-19 01:57:11','2016-09-19 01:57:11'),
('1280x1024','largest','pictures_1280_1024',1024,1280,NULL,NULL,'2016-09-19 02:00:40','2016-09-19 02:00:40'),
('640x480','large','pictures_large',480,640,NULL,NULL,'2016-09-19 01:59:46','2016-09-19 01:59:46'),
('800x600','larger','pictures_larger',600,800,NULL,NULL,'2016-09-19 01:59:46','2016-09-19 01:59:46'),
('raw','Raw Image','pictures_raw',0,0,NULL,NULL,'2016-09-19 01:57:11','2016-09-19 01:57:11');
/*!40000 ALTER TABLE `picture_sizes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-12-09 23:02:53
