-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 15, 2026 at 09:17 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aquaintelx`
--

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `email`, `ip_address`, `user_agent`, `status`, `created_at`) VALUES
(11, NULL, 'admin@aquaintelx.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'failed', '2026-06-13 11:51:21'),
(12, NULL, 'admin@aquaintelx.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'failed', '2026-06-13 11:51:29'),
(13, 1, 'admin@aquaintelx.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'success', '2026-06-13 12:05:37'),
(14, 1, 'admin@aquaintelx.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'success', '2026-06-13 12:17:56'),
(15, 1, 'admin@aquaintelx.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'success', '2026-06-13 13:23:53'),
(16, 1, 'admin@aquaintelx.com', '192.168.100.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-06-13 17:10:13'),
(17, 1, 'admin@aquaintelx.com', '192.168.100.53', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'success', '2026-06-13 17:38:53'),
(18, 1, 'admin@aquaintelx.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-06-14 03:51:49'),
(19, 1, 'admin@aquaintelx.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-06-15 15:29:48'),
(20, 1, 'admin@aquaintelx.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-06-15 18:11:49'),
(21, 1, 'admin@aquaintelx.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'success', '2026-06-15 19:02:01');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `caption` varchar(200) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sensor_readings`
--

CREATE TABLE `sensor_readings` (
  `id` int(11) NOT NULL,
  `sensor_node` varchar(50) NOT NULL DEFAULT 'NODE-01',
  `temperature` decimal(6,2) DEFAULT NULL,
  `turbidity` decimal(6,2) DEFAULT NULL,
  `tds` decimal(8,2) DEFAULT NULL,
  `ph` decimal(5,2) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'normal',
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sensor_readings`
--

INSERT INTO `sensor_readings` (`id`, `sensor_node`, `temperature`, `turbidity`, `tds`, `ph`, `status`, `recorded_at`) VALUES
(1, 'NODE-01', '27.50', '1.20', '320.00', '7.10', 'normal', '2026-06-13 12:18:13'),
(2, 'NODE-01', '26.00', '1.00', '150.00', '7.10', 'normal', '2026-06-13 13:36:16'),
(3, 'NODE-01', '29.00', '3.50', '350.00', '6.50', 'warning', '2026-06-13 15:28:48'),
(4, 'NODE-01', '26.00', '1.00', '150.00', '7.10', 'normal', '2026-06-13 16:17:08'),
(5, 'NODE-01', '35.00', '10.00', '850.00', '5.20', 'critical', '2026-06-13 16:18:02'),
(6, 'NODE-01', NULL, '8.00', '650.00', '7.00', 'critical', '2026-06-13 16:22:43'),
(7, 'NODE-01', '34.00', '8.00', '650.00', '7.00', 'critical', '2026-06-13 16:23:08'),
(8, 'NODE-01', '24.00', '5.00', '250.00', '7.16', 'warning', '2026-06-13 16:46:27'),
(9, 'NODE-01', NULL, NULL, '0.00', '8.39', 'normal', '2026-06-13 17:24:48'),
(10, 'NODE-01', NULL, NULL, '0.00', '8.42', 'normal', '2026-06-13 17:24:53'),
(11, 'NODE-01', NULL, NULL, '0.00', '8.41', 'normal', '2026-06-13 17:24:58'),
(12, 'NODE-01', NULL, NULL, '0.00', '8.43', 'normal', '2026-06-13 17:25:03'),
(13, 'NODE-01', NULL, NULL, '0.00', '8.42', 'normal', '2026-06-13 17:25:08'),
(14, 'NODE-01', NULL, NULL, '0.00', '8.41', 'normal', '2026-06-13 17:25:13'),
(15, 'NODE-01', NULL, NULL, '0.00', '8.39', 'normal', '2026-06-13 17:25:18'),
(16, 'NODE-01', NULL, NULL, '0.00', '8.43', 'normal', '2026-06-13 17:25:23'),
(17, 'NODE-01', NULL, NULL, '0.00', '8.40', 'normal', '2026-06-13 17:25:28'),
(18, 'NODE-01', NULL, NULL, '0.00', '8.40', 'normal', '2026-06-13 17:25:33'),
(19, 'NODE-01', NULL, NULL, '0.00', '8.40', 'normal', '2026-06-13 17:25:38'),
(20, 'NODE-01', NULL, NULL, '0.00', '8.24', 'normal', '2026-06-13 17:25:43'),
(21, 'NODE-01', NULL, NULL, '0.00', '8.40', 'normal', '2026-06-13 17:25:48'),
(22, 'NODE-01', NULL, NULL, '0.00', '8.44', 'normal', '2026-06-13 17:25:53'),
(23, 'NODE-01', NULL, NULL, '0.00', '8.38', 'normal', '2026-06-13 17:25:58'),
(24, 'NODE-01', NULL, NULL, '0.00', '8.40', 'normal', '2026-06-13 17:26:03'),
(25, 'NODE-01', NULL, NULL, '0.00', '8.41', 'normal', '2026-06-13 17:26:08'),
(26, 'NODE-01', NULL, NULL, '0.00', '8.41', 'normal', '2026-06-13 17:26:13'),
(27, 'NODE-01', NULL, NULL, '0.00', '8.36', 'normal', '2026-06-13 17:26:18'),
(28, 'NODE-01', NULL, NULL, '0.00', '8.43', 'normal', '2026-06-13 17:26:23'),
(29, 'NODE-01', NULL, NULL, '0.00', '8.40', 'normal', '2026-06-13 17:26:28'),
(30, 'NODE-01', NULL, NULL, '0.00', '8.04', 'normal', '2026-06-13 17:26:33'),
(31, 'NODE-01', NULL, NULL, '0.00', '8.40', 'normal', '2026-06-13 17:26:38'),
(32, 'NODE-01', NULL, NULL, '0.00', '8.40', 'normal', '2026-06-13 17:26:43'),
(33, 'NODE-01', NULL, NULL, '0.00', '8.41', 'normal', '2026-06-13 17:26:48'),
(34, 'NODE-01', NULL, NULL, '0.00', '8.43', 'normal', '2026-06-13 17:26:53'),
(35, 'NODE-01', NULL, NULL, '0.00', '8.42', 'normal', '2026-06-13 17:26:58'),
(36, 'NODE-01', NULL, NULL, '0.00', '8.42', 'normal', '2026-06-13 17:27:03'),
(37, 'NODE-01', NULL, NULL, '0.00', '8.38', 'normal', '2026-06-13 17:27:08'),
(38, 'NODE-01', NULL, NULL, '0.00', '8.38', 'normal', '2026-06-13 17:27:13'),
(39, 'NODE-01', NULL, NULL, '0.00', '8.43', 'normal', '2026-06-13 17:27:18'),
(40, 'NODE-01', NULL, NULL, '0.00', '8.42', 'normal', '2026-06-13 17:27:23'),
(41, 'NODE-01', NULL, NULL, '0.00', '8.41', 'normal', '2026-06-13 17:27:28'),
(42, 'NODE-01', NULL, NULL, '0.00', '8.40', 'normal', '2026-06-13 17:27:33'),
(43, 'NODE-01', NULL, NULL, '0.00', '8.40', 'normal', '2026-06-13 17:27:38'),
(44, 'NODE-01', NULL, NULL, '0.00', '8.39', 'normal', '2026-06-13 17:27:43'),
(45, 'NODE-01', NULL, NULL, '0.00', '8.43', 'normal', '2026-06-13 17:27:48'),
(46, 'NODE-01', NULL, NULL, '0.00', '8.42', 'normal', '2026-06-13 17:27:53'),
(47, 'NODE-01', NULL, NULL, '0.00', '8.36', 'normal', '2026-06-13 17:27:58'),
(48, 'NODE-01', NULL, NULL, '0.00', '8.40', 'normal', '2026-06-13 17:28:03'),
(49, 'NODE-01', NULL, NULL, '0.00', '8.39', 'normal', '2026-06-13 17:28:08'),
(50, 'NODE-01', NULL, NULL, '0.00', '8.36', 'normal', '2026-06-13 17:28:13'),
(51, 'NODE-01', NULL, NULL, '0.00', '8.42', 'normal', '2026-06-13 17:28:18'),
(52, 'NODE-01', NULL, NULL, '0.00', '8.38', 'normal', '2026-06-13 17:28:23'),
(53, 'NODE-01', NULL, NULL, '0.00', '8.38', 'normal', '2026-06-13 17:28:28'),
(54, 'NODE-01', NULL, NULL, '0.00', '8.40', 'normal', '2026-06-13 17:28:33'),
(55, 'NODE-01', NULL, NULL, '0.00', '8.39', 'normal', '2026-06-13 17:28:38'),
(56, 'NODE-01', NULL, NULL, '0.00', '8.39', 'normal', '2026-06-13 17:28:43'),
(57, 'NODE-01', NULL, NULL, '0.00', '8.43', 'normal', '2026-06-13 17:28:48'),
(58, 'NODE-01', NULL, NULL, '0.00', '8.36', 'normal', '2026-06-13 17:28:53'),
(59, 'NODE-01', NULL, NULL, '0.00', '8.37', 'normal', '2026-06-13 17:28:58'),
(60, 'NODE-01', NULL, NULL, '0.00', '8.37', 'normal', '2026-06-13 17:29:03'),
(61, 'NODE-01', '28.30', '171.30', '0.00', '8.39', 'critical', '2026-06-13 17:30:09'),
(62, 'NODE-01', '28.20', '166.00', '0.00', '8.19', 'critical', '2026-06-13 17:30:14'),
(63, 'NODE-01', '28.20', '172.80', '0.00', '8.37', 'critical', '2026-06-13 17:30:15'),
(64, 'NODE-01', '28.20', '171.30', '0.00', '8.42', 'critical', '2026-06-13 17:30:20'),
(65, 'NODE-01', '28.20', '161.50', '0.00', '8.41', 'critical', '2026-06-13 17:30:25'),
(66, 'NODE-01', '28.20', '170.50', '0.00', '8.35', 'critical', '2026-06-13 17:30:30'),
(67, 'NODE-01', '28.20', '169.80', '0.00', '8.38', 'critical', '2026-06-13 17:30:35'),
(68, 'NODE-01', '28.30', '177.30', '0.00', '8.37', 'critical', '2026-06-13 17:30:40'),
(69, 'NODE-01', '28.20', '170.50', '0.00', '8.35', 'critical', '2026-06-13 17:30:44'),
(70, 'NODE-01', '28.30', '173.50', '0.00', '8.41', 'critical', '2026-06-13 17:30:50'),
(71, 'NODE-01', '28.20', '178.80', '0.00', '8.36', 'critical', '2026-06-13 17:30:55'),
(72, 'NODE-01', '28.20', '175.80', '0.00', '8.37', 'critical', '2026-06-13 17:31:00'),
(73, 'NODE-01', '28.30', '167.50', '0.00', '8.37', 'critical', '2026-06-13 17:31:05'),
(74, 'NODE-01', '28.20', '202.00', '0.00', '8.36', 'critical', '2026-06-13 17:31:10'),
(75, 'NODE-01', '28.20', '168.30', '0.00', '8.33', 'critical', '2026-06-13 17:31:15'),
(76, 'NODE-01', '28.20', '176.50', '0.00', '8.41', 'critical', '2026-06-13 17:31:20'),
(77, 'NODE-01', '28.20', '174.30', '0.00', '8.42', 'critical', '2026-06-13 17:31:25'),
(78, 'NODE-01', '28.20', '165.20', '0.00', '8.39', 'critical', '2026-06-13 17:31:30'),
(79, 'NODE-01', '28.20', '172.00', '0.00', '8.37', 'critical', '2026-06-13 17:31:35'),
(80, 'NODE-01', '28.20', '164.50', '0.00', '8.36', 'critical', '2026-06-13 17:31:40'),
(81, 'NODE-01', '28.20', '170.50', '0.00', '8.37', 'critical', '2026-06-13 17:31:45'),
(82, 'NODE-01', '28.20', '173.50', '0.00', '8.39', 'critical', '2026-06-13 17:31:50'),
(83, 'NODE-01', '28.20', '163.00', '0.00', '8.34', 'critical', '2026-06-13 17:31:55'),
(84, 'NODE-01', '28.20', '166.00', '0.00', '8.39', 'critical', '2026-06-13 17:32:00'),
(85, 'NODE-01', '28.20', '169.00', '0.00', '8.40', 'critical', '2026-06-13 17:32:05'),
(86, 'NODE-01', '28.20', '167.50', '0.00', '8.32', 'critical', '2026-06-13 17:32:10'),
(87, 'NODE-01', '28.20', '171.30', '0.00', '8.39', 'critical', '2026-06-13 17:32:15'),
(88, 'NODE-01', '28.20', '175.80', '0.00', '8.41', 'critical', '2026-06-13 17:32:20'),
(89, 'NODE-01', '28.30', '176.50', '0.00', '8.36', 'critical', '2026-06-13 17:32:25'),
(90, 'NODE-01', '26.50', '1.20', '180.00', '7.10', 'normal', '2026-06-15 19:08:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `is_active`) VALUES
(1, 'AquaIntelX Admin', 'admin@aquaintelx.com', '$2y$10$RYHQbSmpTxkKWP/uOr9.m.4J5jEkUI3aaKxC8YiBT3VYm/a2Uj3Se', 'admin', '2026-06-13 10:53:47', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sensor_readings`
--
ALTER TABLE `sensor_readings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
