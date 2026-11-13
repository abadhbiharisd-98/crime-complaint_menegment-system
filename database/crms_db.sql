-- CRMS Production Database Dump
-- Compatible with MySQL 8.x, MariaDB, and Cloud MySQL (Aiven, Render, AWS)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 1;
SET FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_REQUIRE_PRIMARY_KEY = @@SESSION.sql_require_primary_key;
SET SESSION sql_require_primary_key = 0;

-- --------------------------------------------------------
-- Table structure for table `complainants`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `complainants` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `address` text NOT NULL,
  `contact` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=unverified,1=verified',
  `email` varchar(200) NOT NULL,
  `password` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `complainants`
INSERT INTO `complainants` (`id`, `name`, `address`, `contact`, `status`, `email`, `password`) VALUES
(1, 'Claire Blake', 'Sample Address', '+18456-5455-55', 0, 'cblake@sample.com', '4744ddea876b11dcb1d169fadf494418')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- --------------------------------------------------------
-- Table structure for table `complaints`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `complainant_id` int(30) NOT NULL,
  `message` text NOT NULL,
  `address` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Pending,2=Received,3=Action Made',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `complaints`
INSERT INTO `complaints` (`id`, `complainant_id`, `message`, `address`, `date_created`, `status`) VALUES
(1, 1, 'Sample Report', 'Sample Address', '2020-10-30 13:48:19', 3),
(2, 1, 'Incident reported near sector 4', 'Central Avenue', '2020-10-30 14:11:24', 1)
ON DUPLICATE KEY UPDATE `message`=VALUES(`message`);

-- --------------------------------------------------------
-- Table structure for table `complaints_action`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `complaints_action` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `complaint_id` int(30) NOT NULL,
  `responder_id` int(30) NOT NULL,
  `dispatched_by` int(30) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=pending,1= confirmed',
  `remarks` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `complaints_action`
INSERT INTO `complaints_action` (`id`, `complaint_id`, `responder_id`, `dispatched_by`, `status`, `remarks`, `date_created`) VALUES
(1, 1, 1, 1, 1, 'Patrol team dispatched to site', '2020-10-30 16:59:00')
ON DUPLICATE KEY UPDATE `remarks`=VALUES(`remarks`);

-- --------------------------------------------------------
-- Table structure for table `responders_team`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `responders_team` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `station_id` int(30) NOT NULL,
  `name` varchar(200) NOT NULL,
  `availability` tinyint(4) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `responders_team`
INSERT INTO `responders_team` (`id`, `station_id`, `name`, `availability`) VALUES
(1, 1, 'R-101 Emergency Unit', 1),
(2, 1, 'R-102 Patrol Unit', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- --------------------------------------------------------
-- Table structure for table `stations`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `stations` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `address` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `stations`
INSERT INTO `stations` (`id`, `name`, `address`, `date_created`) VALUES
(1, 'Central Police Station', 'Downtown Precinct, Block A', '2020-10-30 10:56:25'),
(2, 'North Substation', 'Northern Highway, Unit 4', '2020-10-30 10:56:43')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- --------------------------------------------------------
-- Table structure for table `system_settings`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `email` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `cover_img` text NOT NULL,
  `about_content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `system_settings`
INSERT INTO `system_settings` (`id`, `name`, `email`, `contact`, `cover_img`, `about_content`) VALUES
(1, 'Crime/Complaints Reporting Management System', 'contact@crms-portal.org', '+1 800-555-CRMS', 'police_banner.jpg', '&lt;p style=&quot;text-align: center;&quot;&gt;&lt;strong&gt;Welcome to the Crime &amp; Complaints Reporting Management System&lt;/strong&gt;&lt;/p&gt;&lt;p style=&quot;text-align: center;&quot;&gt;Our 24/7 digital emergency assistance portal enables citizens to file incident reports, view case status in real time, and request immediate responder dispatch.&lt;/p&gt;')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `username` varchar(200) NOT NULL,
  `password` text NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 3 COMMENT '1=Admin,2=Staff',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `users` (admin / admin123)
INSERT INTO `users` (`id`, `name`, `username`, `password`, `type`) VALUES
(1, 'Administrator', 'admin', '0192023a7bbd73250516f069df18b500', 1)
ON DUPLICATE KEY UPDATE `password`=VALUES(`password`), `type`=1;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
