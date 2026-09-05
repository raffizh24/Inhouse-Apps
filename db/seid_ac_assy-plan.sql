-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 05, 2026 at 05:22 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `seid_ac_assy-plan`
--

-- --------------------------------------------------------

--
-- Table structure for table `planning`
--

CREATE TABLE `planning` (
  `id` int(11) NOT NULL,
  `model` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `shift` int(11) NOT NULL,
  `seq` int(11) DEFAULT NULL,
  `qty_plan` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `planning`
--

INSERT INTO `planning` (`id`, `model`, `tanggal`, `shift`, `seq`, `qty_plan`, `created_at`) VALUES
(1, 'A3AHA5BEY2', '2026-09-01', 1, 1, 1400, '2026-09-05 15:15:26'),
(2, 'A3AUA5BEY2', '2026-09-01', 1, 1, 1400, '2026-09-05 15:15:26'),
(3, 'A3AHA5BEY2', '2026-09-01', 2, 1, 1200, '2026-09-05 15:15:26'),
(4, 'A3AUA5BEY2', '2026-09-01', 2, 1, 1200, '2026-09-05 15:15:26'),
(5, 'A3AHA5BEY2', '2026-09-01', 3, 1, 1200, '2026-09-05 15:15:26'),
(6, 'A3AUA5BEY2', '2026-09-01', 3, 1, 1200, '2026-09-05 15:15:26'),
(7, 'A3AHA5BMY2', '2026-09-02', 1, 1, 1400, '2026-09-05 15:15:26'),
(8, 'A3AUA5BMY2', '2026-09-02', 1, 1, 1400, '2026-09-05 15:15:26'),
(9, 'A3AHA5BMY2', '2026-09-02', 2, 1, 1200, '2026-09-05 15:15:26'),
(10, 'A3AUA5BMY2', '2026-09-02', 2, 1, 1200, '2026-09-05 15:15:26'),
(11, 'A3AHA5BMY2', '2026-09-02', 3, 1, 1200, '2026-09-05 15:15:26'),
(12, 'A3AUA5BMY2', '2026-09-02', 3, 1, 1200, '2026-09-05 15:15:26'),
(13, 'A3AHA5BEY2', '2026-09-01', 1, 1, 1000, '2026-09-05 15:19:54'),
(14, 'A3AHA5BBY2', '2026-09-01', 1, 2, 400, '2026-09-05 15:19:54'),
(15, 'A3AUA5BEY2', '2026-09-01', 1, 1, 1400, '2026-09-05 15:19:54'),
(16, 'A3AHA5BEY2', '2026-09-01', 2, 1, 800, '2026-09-05 15:19:54'),
(17, 'A3AHA5DEY', '2026-09-01', 2, 2, 400, '2026-09-05 15:19:54'),
(18, 'A3AUA5BEY2', '2026-09-01', 2, 1, 1200, '2026-09-05 15:19:54'),
(19, 'A3AHA5BEY2', '2026-09-01', 3, 1, 1200, '2026-09-05 15:19:54'),
(20, 'A3AUA5BEY2', '2026-09-01', 3, 1, 1200, '2026-09-05 15:19:54'),
(21, 'A3AHA5BMY2', '2026-09-02', 1, 1, 1400, '2026-09-05 15:19:54'),
(22, 'A3AUA5BMY2', '2026-09-02', 1, 1, 1400, '2026-09-05 15:19:54'),
(23, 'A3AHA5BMY2', '2026-09-02', 2, 1, 1200, '2026-09-05 15:19:54'),
(24, 'A3AUA5BMY2', '2026-09-02', 2, 1, 1200, '2026-09-05 15:19:54'),
(25, 'A3AHA5BMY2', '2026-09-02', 3, 1, 1200, '2026-09-05 15:19:54'),
(26, 'A3AUA5BMY2', '2026-09-02', 3, 1, 1200, '2026-09-05 15:19:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('production','warehouse') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'assy01', 'SeidMail01', 'production'),
(2, 'cmc01', 'SeidMail01', 'warehouse');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `planning`
--
ALTER TABLE `planning`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `planning`
--
ALTER TABLE `planning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
