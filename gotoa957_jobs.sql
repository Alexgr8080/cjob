-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 23, 2025 at 07:21 PM
-- Server version: 8.4.6-cll-lve
-- PHP Version: 8.3.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gotoa957_jobs`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `active_jobs_view`
-- (See below for the actual view)
--
CREATE TABLE `active_jobs_view` (
`id` int
,`title` varchar(255)
,`company` varchar(255)
,`job_type` enum('full-time','part-time','contract','temporary','internship')
,`salary_display` varchar(100)
,`application_deadline` date
,`created_at` timestamp
,`views` int
,`applications_count` int
,`category_name` varchar(100)
,`category_slug` varchar(100)
,`location_city` varchar(100)
,`location_region` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int NOT NULL,
  `job_id` int NOT NULL,
  `user_id` int NOT NULL,
  `cover_letter` text COLLATE utf8mb4_unicode_ci,
  `resume_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','reviewed','shortlisted','interviewed','offered','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `applied_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `applications`
--
DELIMITER $$
CREATE TRIGGER `update_job_applications_after_delete` AFTER DELETE ON `applications` FOR EACH ROW BEGIN
    UPDATE jobs SET applications_count = applications_count - 1 WHERE id = OLD.job_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_job_applications_after_insert` AFTER INSERT ON `applications` FOR EACH ROW BEGIN
    UPDATE jobs SET applications_count = applications_count + 1 WHERE id = NEW.job_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_count` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `job_count`, `is_active`, `created_at`) VALUES
(1, 'Information Technology', 'information-technology', 'Software development, IT support, cybersecurity, and more', '💻', 1, 1, '2025-10-23 05:13:20'),
(2, 'Healthcare', 'healthcare', 'Medical professionals, nursing, healthcare administration', '🏥', 0, 1, '2025-10-23 05:13:20'),
(3, 'Engineering', 'engineering', 'Civil, mechanical, electrical, and software engineering', '⚙️', 0, 1, '2025-10-23 05:13:20'),
(4, 'Education', 'education', 'Teaching, training, and educational administration', '📚', 0, 1, '2025-10-23 05:13:20'),
(5, 'Finance & Accounting', 'finance-accounting', 'Accounting, financial analysis, banking', '💰', 0, 1, '2025-10-23 05:13:20'),
(6, 'Sales & Marketing', 'sales-marketing', 'Sales representatives, marketing specialists, business development', '📈', 1, 1, '2025-10-23 05:13:20'),
(7, 'Hospitality & Tourism', 'hospitality-tourism', 'Hotels, restaurants, travel, and tourism services', '🏨', 0, 1, '2025-10-23 05:13:20'),
(8, 'Construction & Trades', 'construction-trades', 'Builders, electricians, plumbers, carpenters', '🔨', 0, 1, '2025-10-23 05:13:20'),
(9, 'Agriculture & Horticulture', 'agriculture-horticulture', 'Farming, vineyard work, horticulture', '🌾', 0, 1, '2025-10-23 05:13:20'),
(10, 'Retail & Customer Service', 'retail-customer-service', 'Retail sales, customer support, service roles', '🛍️', 0, 1, '2025-10-23 05:13:20'),
(11, 'Transportation & Logistics', 'transportation-logistics', 'Drivers, warehouse, supply chain management', '🚛', 0, 1, '2025-10-23 05:13:20'),
(12, 'Creative & Design', 'creative-design', 'Graphic design, content creation, creative roles', '🎨', 0, 1, '2025-10-23 05:13:20'),
(13, 'Legal & Compliance', 'legal-compliance', 'Lawyers, legal assistants, compliance officers', '⚖️', 0, 1, '2025-10-23 05:13:20'),
(14, 'Human Resources', 'human-resources', 'HR management, recruitment, employee relations', '👥', 0, 1, '2025-10-23 05:13:20'),
(15, 'Manufacturing & Production', 'manufacturing-production', 'Factory work, production management, quality control', '🏭', 0, 1, '2025-10-23 05:13:20');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `job_type` enum('full-time','part-time','contract','temporary','internship') COLLATE utf8mb4_unicode_ci DEFAULT 'full-time',
  `salary_min` decimal(10,2) DEFAULT NULL,
  `salary_max` decimal(10,2) DEFAULT NULL,
  `salary_display` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `requirements` text COLLATE utf8mb4_unicode_ci,
  `benefits` text COLLATE utf8mb4_unicode_ci,
  `application_deadline` date DEFAULT NULL,
  `status` enum('active','closed','draft') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `views` int DEFAULT '0',
  `applications_count` int DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `title`, `company`, `category_id`, `location_id`, `job_type`, `salary_min`, `salary_max`, `salary_display`, `description`, `requirements`, `benefits`, `application_deadline`, `status`, `views`, `applications_count`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Senior Software Engineer', 'Tech Solutions NZ', 1, 1, 'full-time', NULL, NULL, '$90,000 - $120,000 per year', 'We are seeking an experienced Senior Software Engineer to join our growing team. You will be responsible for designing, developing, and maintaining high-quality software applications.', '- 5+ years of software development experience\n- Strong proficiency in JavaScript, Python, or Java\n- Experience with cloud platforms (AWS, Azure, or GCP)\n- Excellent problem-solving skills', '- Competitive salary\n- Health insurance\n- Flexible working hours\n- Professional development opportunities', '2025-11-22', 'active', 0, 0, NULL, '2025-10-23 05:13:20', '2025-10-23 05:13:20'),
(2, 'Registered Nurse', 'Auckland Hospital', 2, 1, 'full-time', NULL, NULL, '$65,000 - $80,000 per year', 'Join our dedicated nursing team at Auckland Hospital. We are looking for a compassionate Registered Nurse to provide high-quality patient care.', '- Current NZ Nursing Council registration\n- Minimum 2 years clinical experience\n- Strong communication skills\n- Ability to work shifts', '- Competitive salary with shift allowances\n- Continuing education support\n- Career progression opportunities\n- Staff discounts', '2025-12-07', 'active', 0, 0, NULL, '2025-10-23 05:13:20', '2025-10-23 05:13:20'),
(3, 'Marketing Manager', 'Creative Agency Ltd', 6, 2, 'full-time', NULL, NULL, '$75,000 - $95,000 per year', 'Lead our marketing efforts and develop strategies to increase brand awareness and drive business growth.', '- 5+ years marketing experience\n- Proven track record in digital marketing\n- Strong leadership skills\n- Experience with marketing analytics', '- Performance bonuses\n- Work from home options\n- Team building events\n- Modern office environment', '2025-12-22', 'active', 0, 0, NULL, '2025-10-23 05:13:20', '2025-10-23 05:13:20'),
(4, 'Researcher', 'NZQRI', 1, 1, 'full-time', NULL, NULL, '89000', 'Test for the first job and well know Test for the first job and well know', 'No really', '', '2025-10-30', 'active', 0, 0, 2, '2025-10-23 06:39:14', '2025-10-23 06:39:14'),
(5, 'Researcher 4', 'NZADA', 6, 10, 'part-time', NULL, NULL, '89000', 'Agency That Thrives on Your Success\r\nDriving Your Success Through Innovation, Research, and Coordinated Health Events.\r\nWe are committed to driving success across diverse industries and empowering future generations through advanced technology and innovative solutions. Our mission is to revolutionise products, business services, health events, and educational experiences by integrating cutting-edge technolo', 'Agency That Thrives on Your Success\r\nDriving Your Success Through Innovation, Research, and Coordinated Health Events.\r\nWe are committed to driving success across diverse industries and empowering future generations through advanced technology and innovative solutions. Our mission is to revolutionise products, business services, health events, and educational experiences by integrating cutting-edge technolo', 'Agency That Thrives on Your Success\r\nDriving Your Success Through Innovation, Research, and Coordinated Health Events.\r\nWe are committed to driving success across diverse industries and empowering future generations through advanced technology and innovative solutions. Our mission is to revolutionise products, business services, health events, and educational experiences by integrating cutting-edge technolo', '2025-11-09', 'active', 0, 0, 2, '2025-10-23 08:13:47', '2025-10-23 08:13:47');

--
-- Triggers `jobs`
--
DELIMITER $$
CREATE TRIGGER `update_category_count_after_delete` AFTER DELETE ON `jobs` FOR EACH ROW BEGIN
    IF OLD.category_id IS NOT NULL THEN
        UPDATE categories SET job_count = job_count - 1 WHERE id = OLD.category_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_category_count_after_insert` AFTER INSERT ON `jobs` FOR EACH ROW BEGIN
    IF NEW.category_id IS NOT NULL THEN
        UPDATE categories SET job_count = job_count + 1 WHERE id = NEW.category_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_category_count_after_update` AFTER UPDATE ON `jobs` FOR EACH ROW BEGIN
    IF OLD.category_id IS NOT NULL AND OLD.category_id != NEW.category_id THEN
        UPDATE categories SET job_count = job_count - 1 WHERE id = OLD.category_id;
    END IF;
    IF NEW.category_id IS NOT NULL AND OLD.category_id != NEW.category_id THEN
        UPDATE categories SET job_count = job_count + 1 WHERE id = NEW.category_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_location_count_after_delete` AFTER DELETE ON `jobs` FOR EACH ROW BEGIN
    IF OLD.location_id IS NOT NULL THEN
        UPDATE locations SET job_count = job_count - 1 WHERE id = OLD.location_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_location_count_after_insert` AFTER INSERT ON `jobs` FOR EACH ROW BEGIN
    IF NEW.location_id IS NOT NULL THEN
        UPDATE locations SET job_count = job_count + 1 WHERE id = NEW.location_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_location_count_after_update` AFTER UPDATE ON `jobs` FOR EACH ROW BEGIN
    IF OLD.location_id IS NOT NULL AND OLD.location_id != NEW.location_id THEN
        UPDATE locations SET job_count = job_count - 1 WHERE id = OLD.location_id;
    END IF;
    IF NEW.location_id IS NOT NULL AND OLD.location_id != NEW.location_id THEN
        UPDATE locations SET job_count = job_count + 1 WHERE id = NEW.location_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'New Zealand',
  `job_count` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `city`, `region`, `country`, `job_count`, `is_active`, `created_at`) VALUES
(1, 'Auckland', 'Auckland', 'New Zealand', 1, 1, '2025-10-23 05:13:20'),
(2, 'Wellington', 'Wellington', 'New Zealand', 0, 1, '2025-10-23 05:13:20'),
(3, 'Christchurch', 'Canterbury', 'New Zealand', 0, 1, '2025-10-23 05:13:20'),
(4, 'Hamilton', 'Waikato', 'New Zealand', 0, 1, '2025-10-23 05:13:20'),
(5, 'Tauranga', 'Bay of Plenty', 'New Zealand', 0, 1, '2025-10-23 05:13:20'),
(6, 'Dunedin', 'Otago', 'New Zealand', 0, 1, '2025-10-23 05:13:20'),
(7, 'Palmerston North', 'Manawatū-Whanganui', 'New Zealand', 0, 1, '2025-10-23 05:13:20'),
(8, 'Napier', 'Hawke\'s Bay', 'New Zealand', 0, 1, '2025-10-23 05:13:20'),
(9, 'Nelson', 'Nelson', 'New Zealand', 0, 1, '2025-10-23 05:13:20'),
(10, 'Rotorua', 'Bay of Plenty', 'New Zealand', 1, 1, '2025-10-23 05:13:20'),
(11, 'New Plymouth', 'Taranaki', 'New Zealand', 0, 1, '2025-10-23 05:13:20'),
(12, 'Whangarei', 'Northland', 'New Zealand', 0, 1, '2025-10-23 05:13:20'),
(13, 'Invercargill', 'Southland', 'New Zealand', 0, 1, '2025-10-23 05:13:20'),
(14, 'Queenstown', 'Otago', 'New Zealand', 0, 1, '2025-10-23 05:13:20'),
(15, 'Remote', 'Remote', 'New Zealand', 0, 1, '2025-10-23 05:13:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `resume_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `role`, `resume_path`, `created_at`, `updated_at`, `last_login`, `is_active`) VALUES
(1, 'System Administrator', 'admin@gotoaus.com', '$2b$12$rf6QzlYif.hklLwyBHCHsuneyWTxMD0B8uVAfsrz/MXSzhkLe30Nm', '', 'admin', NULL, '2025-10-23 05:13:20', '2025-10-23 06:29:07', NULL, 1),
(2, 'System Administrator', 'ahmed@nzqri.co.nz', '$2b$12$WCG4tPdtOzmvlbPc4rVfjOiUP.rwuc9/kTIPNfHAC7g3/U/G9r0Qu', '', 'admin', NULL, '2025-10-23 05:13:20', '2025-10-23 08:13:15', '2025-10-23 08:13:15', 1),
(3, 'Esraa Ahmed', 'adesraa@gmail.com', '$2y$10$4c4WdGH3Pg6oQ9CFDMILyu6Qyi3q7qNrXVjDiruD69eqhr.0Tl.uK', '', 'user', NULL, '2025-10-23 06:51:08', '2025-10-23 06:51:08', NULL, 1),
(4, 'medo alsa', 'gougowith@yahoo.com', '$2y$10$bd.LKKEoEn6eyzoGgt6PXOg0/KVKPbuPOruEKQ/PNvkoRq0QPiZlW', '+64211332862', 'user', NULL, '2025-10-23 07:56:52', '2025-10-23 07:57:01', '2025-10-23 07:57:01', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_application` (`job_id`,`user_id`),
  ADD KEY `idx_job` (`job_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_applied_at` (`applied_at`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_location` (`location_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`job_type`),
  ADD KEY `idx_deadline` (`application_deadline`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_location` (`city`,`region`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- --------------------------------------------------------

--
-- Structure for view `active_jobs_view`
--
DROP TABLE IF EXISTS `active_jobs_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`cpses_gov1e4amgj`@`localhost` SQL SECURITY DEFINER VIEW `active_jobs_view`  AS SELECT `j`.`id` AS `id`, `j`.`title` AS `title`, `j`.`company` AS `company`, `j`.`job_type` AS `job_type`, `j`.`salary_display` AS `salary_display`, `j`.`application_deadline` AS `application_deadline`, `j`.`created_at` AS `created_at`, `j`.`views` AS `views`, `j`.`applications_count` AS `applications_count`, `c`.`name` AS `category_name`, `c`.`slug` AS `category_slug`, `l`.`city` AS `location_city`, `l`.`region` AS `location_region` FROM ((`jobs` `j` left join `categories` `c` on((`j`.`category_id` = `c`.`id`))) left join `locations` `l` on((`j`.`location_id` = `l`.`id`))) WHERE ((`j`.`status` = 'active') AND ((`j`.`application_deadline` is null) OR (`j`.`application_deadline` >= curdate()))) ORDER BY `j`.`created_at` DESC ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `jobs_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
