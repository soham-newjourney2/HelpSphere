-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 09, 2026 at 07:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `admin_master`
--

CREATE TABLE `admin_master` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `assistance_rejection`
--

CREATE TABLE `assistance_rejection` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `rejected_by_ngo_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `assistance_requests`
--

CREATE TABLE `assistance_requests` (
  `id` int(11) NOT NULL,
  `beneficiary_id` int(11) DEFAULT NULL,
  `category` enum('Food','Money','Resources') DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Fulfilled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngo_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `beneficiary_documents`
--

CREATE TABLE `beneficiary_documents` (
  `id` int(11) NOT NULL,
  `beneficiary_id` int(11) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Uploaded','Verified') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `beneficiary_master`
--

CREATE TABLE `beneficiary_master` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `dob` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `address` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL,
  `ngo_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `st_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Completed','Ongoing') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `campaign_volunteers`
--

CREATE TABLE `campaign_volunteers` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `skills` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `document_reports`
--

CREATE TABLE `document_reports` (
  `id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `ngo_name` varchar(255) NOT NULL,
  `beneficiary_name` varchar(255) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `report` text NOT NULL,
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `target_id` int(11) NOT NULL DEFAULT 0,
  `target_role` enum('NGO','Beneficiary') NOT NULL,
  `status` enum('Pending','Accepted','Donated','Picked Up') NOT NULL,
  `donation_type` enum('Food','Money','Resource','Blood') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `donor_master`
--

CREATE TABLE `donor_master` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `preferred_type` enum('Food','Money','Resources','Blood') NOT NULL,
  `preferred_ngo_cause` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `food_donations`
--

CREATE TABLE `food_donations` (
  `id` int(11) NOT NULL,
  `donor_id` int(11) DEFAULT NULL,
  `food_type` enum('Cooked','Packaged','Raw') DEFAULT NULL,
  `target_type` enum('NGO','Beneficiary') DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `quantity` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Accepted','Donated','Picked Up') DEFAULT 'Donated',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pickup_address` varchar(255) NOT NULL,
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time NOT NULL,
  `urgency` varchar(20) NOT NULL DEFAULT 'High',
  `donation_type` enum('Immediate','Scheduled') NOT NULL,
  `is_requested_by_ngo` enum('Yes','No') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `food_donation_batches`
--

CREATE TABLE `food_donation_batches` (
  `id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `food_type` enum('Cooked','Packaged','Raw') NOT NULL,
  `quantity` varchar(255) NOT NULL,
  `pickup_address` varchar(255) NOT NULL,
  `pickup_within` tinyint(4) NOT NULL,
  `urgency` varchar(20) DEFAULT 'High',
  `status` enum('Pending','Accepted','Expired') DEFAULT 'Pending',
  `ngo_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `food_donation_notifications`
--

CREATE TABLE `food_donation_notifications` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `round_no` int(11) DEFAULT 1,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `status` enum('Pending','Accepted','Expired') DEFAULT 'Pending',
  `notified_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `food_donation_requests`
--

CREATE TABLE `food_donation_requests` (
  `id` int(11) NOT NULL,
  `status` enum('Pending','Donated','Accepted','Picked Up') NOT NULL DEFAULT 'Pending',
  `request_type` enum('Scheduled') NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `description` varchar(200) NOT NULL,
  `urgency` enum('N/A') NOT NULL,
  `donor_id` int(11) NOT NULL DEFAULT 0,
  `quantity` varchar(200) NOT NULL DEFAULT '',
  `food_type` enum('Cooked','Packaged','Raw') DEFAULT NULL,
  `donated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `fulfillment_status`
--

CREATE TABLE `fulfillment_status` (
  `id` int(11) NOT NULL,
  `beneficiary_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `fulfilled_at` time NOT NULL,
  `support_type` varchar(255) NOT NULL,
  `provider` varchar(255) NOT NULL,
  `delivery_method` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `general_messages`
--

CREATE TABLE `general_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_role` enum('Beneficiary','NGO','Donor','Admin') NOT NULL,
  `receiver_id` int(11) NOT NULL DEFAULT 0,
  `receiver_role` enum('Beneficiary','NGO','Donor','Admin') NOT NULL,
  `message` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `ngo_documents`
--

CREATE TABLE `ngo_documents` (
  `id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Uploaded','Verified') NOT NULL DEFAULT 'Uploaded'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `ngo_document_admin_rejections`
--

CREATE TABLE `ngo_document_admin_rejections` (
  `id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `reason` varchar(500) NOT NULL DEFAULT '',
  `rejected_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ngo_document_rejections`
--

CREATE TABLE `ngo_document_rejections` (
  `id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `rejected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` varchar(500) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ngo_master`
--

CREATE TABLE `ngo_master` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `focus` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `registration_no` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `registration_date` datetime DEFAULT current_timestamp(),
  `status` enum('Verified','Unverified') NOT NULL DEFAULT 'Unverified',
  `phone` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `request_ngo_decisions`
--

CREATE TABLE `request_ngo_decisions` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `ngo_id` int(11) NOT NULL,
  `decision` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `decided_at` timestamp NULL DEFAULT NULL,
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resource_donations`
--

CREATE TABLE `resource_donations` (
  `id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `resource_type` enum('Clothes','Books','Medical Supplies','Stationery','Other') NOT NULL,
  `quantity` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `resource_condition` enum('New','Gently Used') NOT NULL,
  `pickup_address` varchar(255) NOT NULL,
  `pickup_date` date NOT NULL,
  `pickup_time` time NOT NULL,
  `status` enum('Pending','Approved') NOT NULL,
  `ngo_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` enum('Beneficiary','Donor','NGO','Admin') NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_master`
--
ALTER TABLE `admin_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assistance_rejection`
--
ALTER TABLE `assistance_rejection`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assistance_requests`
--
ALTER TABLE `assistance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `beneficiary_id` (`beneficiary_id`);

--
-- Indexes for table `beneficiary_documents`
--
ALTER TABLE `beneficiary_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `beneficiary_id` (`beneficiary_id`);

--
-- Indexes for table `beneficiary_master`
--
ALTER TABLE `beneficiary_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ngo_id` (`ngo_id`);

--
-- Indexes for table `campaign_volunteers`
--
ALTER TABLE `campaign_volunteers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_application` (`campaign_id`,`donor_id`);

--
-- Indexes for table `document_reports`
--
ALTER TABLE `document_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donor_master`
--
ALTER TABLE `donor_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `food_donations`
--
ALTER TABLE `food_donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donor_id` (`donor_id`);

--
-- Indexes for table `food_donation_batches`
--
ALTER TABLE `food_donation_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `food_donation_notifications`
--
ALTER TABLE `food_donation_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `food_donation_requests`
--
ALTER TABLE `food_donation_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ngo__id` (`ngo_id`);

--
-- Indexes for table `fulfillment_status`
--
ALTER TABLE `fulfillment_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `general_messages`
--
ALTER TABLE `general_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ngo_documents`
--
ALTER TABLE `ngo_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ngo_document_admin_rejections`
--
ALTER TABLE `ngo_document_admin_rejections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `ngo_document_rejections`
--
ALTER TABLE `ngo_document_rejections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rejection` (`ngo_id`,`document_id`);

--
-- Indexes for table `ngo_master`
--
ALTER TABLE `ngo_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `request_ngo_decisions`
--
ALTER TABLE `request_ngo_decisions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_req_ngo` (`request_id`,`ngo_id`),
  ADD KEY `ngo_id` (`ngo_id`);

--
-- Indexes for table `resource_donations`
--
ALTER TABLE `resource_donations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_master`
--
ALTER TABLE `admin_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assistance_rejection`
--
ALTER TABLE `assistance_rejection`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assistance_requests`
--
ALTER TABLE `assistance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `beneficiary_documents`
--
ALTER TABLE `beneficiary_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `beneficiary_master`
--
ALTER TABLE `beneficiary_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_volunteers`
--
ALTER TABLE `campaign_volunteers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_reports`
--
ALTER TABLE `document_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donor_master`
--
ALTER TABLE `donor_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `food_donations`
--
ALTER TABLE `food_donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `food_donation_batches`
--
ALTER TABLE `food_donation_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `food_donation_notifications`
--
ALTER TABLE `food_donation_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `food_donation_requests`
--
ALTER TABLE `food_donation_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fulfillment_status`
--
ALTER TABLE `fulfillment_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `general_messages`
--
ALTER TABLE `general_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ngo_documents`
--
ALTER TABLE `ngo_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ngo_document_admin_rejections`
--
ALTER TABLE `ngo_document_admin_rejections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ngo_document_rejections`
--
ALTER TABLE `ngo_document_rejections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ngo_master`
--
ALTER TABLE `ngo_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_ngo_decisions`
--
ALTER TABLE `request_ngo_decisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resource_donations`
--
ALTER TABLE `resource_donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assistance_requests`
--
ALTER TABLE `assistance_requests`
  ADD CONSTRAINT `assistance_requests_ibfk_1` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiary_master` (`id`);

--
-- Constraints for table `beneficiary_documents`
--
ALTER TABLE `beneficiary_documents`
  ADD CONSTRAINT `beneficiary_documents_ibfk_1` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiary_master` (`id`);

--
-- Constraints for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD CONSTRAINT `campaigns_ibfk_1` FOREIGN KEY (`ngo_id`) REFERENCES `ngo_master` (`id`);

--
-- Constraints for table `food_donations`
--
ALTER TABLE `food_donations`
  ADD CONSTRAINT `food_donations_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `donor_master` (`id`);

--
-- Constraints for table `food_donation_requests`
--
ALTER TABLE `food_donation_requests`
  ADD CONSTRAINT `food_donation_requests_ibfk_1` FOREIGN KEY (`ngo_id`) REFERENCES `ngo_master` (`id`);

--
-- Constraints for table `ngo_document_admin_rejections`
--
ALTER TABLE `ngo_document_admin_rejections`
  ADD CONSTRAINT `ngo_document_admin_rejections_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `ngo_documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_ngo_decisions`
--
ALTER TABLE `request_ngo_decisions`
  ADD CONSTRAINT `request_ngo_decisions_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `assistance_requests` (`id`),
  ADD CONSTRAINT `request_ngo_decisions_ibfk_2` FOREIGN KEY (`ngo_id`) REFERENCES `ngo_master` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
