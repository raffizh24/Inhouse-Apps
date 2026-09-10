-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 10, 2026 at 04:51 PM
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
-- Database: `seid_ac_inhouseapps`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `description` text DEFAULT NULL,
  `target_table` varchar(50) NOT NULL,
  `part_code` varchar(50) NOT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `target_table`, `part_code`, `old_data`, `new_data`, `created_at`) VALUES
(1, 3, 'INSERT', 'Input FG Press [Shift 2 | Tgl: 2026-09-10]: GCAB-A646JBPZ (Top Table) Qty: 1000', 'stock_transactions', 'GCAB-A646JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:33:48'),
(2, 3, 'INSERT', 'Input FG Press [Shift 2 | Tgl: 2026-09-10]: GCAB-A767JBPZ (Front Panel) Qty: 1000', 'stock_transactions', 'GCAB-A767JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:33:48'),
(3, 3, 'INSERT', 'Input FG Press [Shift 2 | Tgl: 2026-09-10]: LCHS-A800JBPZ (Base Pan) Qty: 1000', 'stock_transactions', 'LCHS-A800JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:33:48'),
(4, 3, 'INSERT', 'Input FG Press [Shift 2 | Tgl: 2026-09-10]: PPLT-B282JBPZ (Side Cover R) Qty: 1000', 'stock_transactions', 'PPLT-B282JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:33:48'),
(5, 3, 'INSERT', 'Input FG Press [Shift 2 | Tgl: 2026-09-10]: GCAB-A646JBPZ (Top Table) Qty: 1000', 'stock_transactions', 'GCAB-A646JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:33:55'),
(6, 3, 'INSERT', 'Input FG Press [Shift 2 | Tgl: 2026-09-10]: GCAB-A767JBPZ (Front Panel) Qty: 1000', 'stock_transactions', 'GCAB-A767JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:33:55'),
(7, 3, 'INSERT', 'Input FG Press [Shift 2 | Tgl: 2026-09-10]: LCHS-A800JBPZ (Base Pan) Qty: 1000', 'stock_transactions', 'LCHS-A800JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:33:55'),
(8, 3, 'INSERT', 'Input FG Press [Shift 2 | Tgl: 2026-09-10]: PPLT-B282JBPZ (Side Cover R) Qty: 1000', 'stock_transactions', 'PPLT-B282JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:33:55'),
(9, 4, 'INSERT', 'Input FG Painting [Shift 2 | Tgl: 2026-09-10]: PEVA-A055VDKZ (EVAP REF 162) Qty: 1000', 'stock_transactions', 'PEVA-A055VDKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:34:20'),
(10, 4, 'INSERT', 'Input FG Painting [Shift 2 | Tgl: 2026-09-10]: GCAB-A646JBPZ (Top Table) Qty: 1000', 'stock_transactions', 'GCAB-A646JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:34:20'),
(11, 4, 'INSERT', 'Input FG Painting [Shift 2 | Tgl: 2026-09-10]: GCAB-A767JBPZ (Front Panel) Qty: 1000', 'stock_transactions', 'GCAB-A767JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:34:20'),
(12, 4, 'INSERT', 'Input FG Painting [Shift 2 | Tgl: 2026-09-10]: LCHS-A800JBPZ (Base Pan) Qty: 1000', 'stock_transactions', 'LCHS-A800JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:34:20'),
(13, 4, 'INSERT', 'Input FG Painting [Shift 2 | Tgl: 2026-09-10]: PPLT-B282JBPZ (Side Cover R) Qty: 1000', 'stock_transactions', 'PPLT-B282JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\"}', '2026-09-10 14:34:20'),
(14, 6, 'INSERT', 'Input FG Injection [Area AC | Shift 2 | Tgl: 2026-09-10]: GGADPA056JBFA (Fan Guard) Qty: 1000', 'stock_transactions', 'GGADPA056JBFA', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"AC\"}', '2026-09-10 14:34:54'),
(15, 6, 'INSERT', 'Input FG Injection [Area AC | Shift 2 | Tgl: 2026-09-10]: GWAK-A517JBFA (Front Panel) Qty: 1000', 'stock_transactions', 'GWAK-A517JBFA', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"AC\"}', '2026-09-10 14:34:54'),
(16, 6, 'INSERT', 'Input FG Injection [Area AC | Shift 2 | Tgl: 2026-09-10]: GWAK-A517JBFB (Front Panel Black) Qty: 1000', 'stock_transactions', 'GWAK-A517JBFB', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"AC\"}', '2026-09-10 14:34:54'),
(17, 6, 'INSERT', 'Input FG Injection [Area AC | Shift 2 | Tgl: 2026-09-10]: GWAK-A520JBFA (Front Panel PCI) Qty: 1000', 'stock_transactions', 'GWAK-A520JBFA', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"AC\"}', '2026-09-10 14:34:54'),
(18, 6, 'INSERT', 'Input FG Injection [Area AC | Shift 2 | Tgl: 2026-09-10]: GWAK-A544JBFC (Front Panel DEY) Qty: 1000', 'stock_transactions', 'GWAK-A544JBFC', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"AC\"}', '2026-09-10 14:34:54'),
(19, 6, 'INSERT', 'Input FG Injection [Area AC | Shift 2 | Tgl: 2026-09-10]: LCHS-A801JBFA (Cabinet) Qty: 1000', 'stock_transactions', 'LCHS-A801JBFA', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"AC\"}', '2026-09-10 14:34:54'),
(20, 6, 'INSERT', 'Input FG Injection [Area AC | Shift 2 | Tgl: 2026-09-10]: LCHS-A801JBFC (Cabinet DEY) Qty: 1000', 'stock_transactions', 'LCHS-A801JBFC', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"AC\"}', '2026-09-10 14:34:54'),
(21, 5, 'INSERT', 'Input FG HEPI [Area HE | Shift 2 | Tgl: 2026-09-10]: PEVA-B161JBPZ (Evaporator) Qty: 1000', 'stock_transactions', 'PEVA-B161JBPZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"HE\",\"table\":\"stok_he\"}', '2026-09-10 14:35:11'),
(22, 5, 'INSERT', 'Input FG HEPI [Area HE | Shift 2 | Tgl: 2026-09-10]: DCON-B070JBEZ (Condensor SR Normal) Qty: 1000', 'stock_transactions', 'DCON-B070JBEZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"HE\",\"table\":\"stok_he\"}', '2026-09-10 14:35:11'),
(23, 5, 'INSERT', 'Input FG HEPI [Area HE | Shift 2 | Tgl: 2026-09-10]: DCON-B105JBEZ (Condensor DR Normal) Qty: 1000', 'stock_transactions', 'DCON-B105JBEZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"HE\",\"table\":\"stok_he\"}', '2026-09-10 14:35:11'),
(24, 5, 'INSERT', 'Input FG HEPI [Area HE | Shift 2 | Tgl: 2026-09-10]: DCON-B074JBEZ (Condensor SR Inverter) Qty: 1000', 'stock_transactions', 'DCON-B074JBEZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"HE\",\"table\":\"stok_he\"}', '2026-09-10 14:35:11'),
(25, 5, 'INSERT', 'Input FG HEPI [Area HE | Shift 2 | Tgl: 2026-09-10]: DCON-B075JBEZ (Condensor DR Inverter) Qty: 1000', 'stock_transactions', 'DCON-B075JBEZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"HE\",\"table\":\"stok_he\"}', '2026-09-10 14:35:11'),
(26, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC533JBKZ (TUBE ASSY) Qty: 1000', 'stock_transactions', 'CPIPCC533JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(27, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC616JBKZ (SUCTION 7K/5K-2) Qty: 1000', 'stock_transactions', 'CPIPCC616JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(28, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC625JBKZ (SUCTION 9K2) Qty: 1000', 'stock_transactions', 'CPIPCC625JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(29, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC571JBKZ (SUCTION 6K/8K/10K) Qty: 1000', 'stock_transactions', 'CPIPCC571JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(30, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC624JBKZ (SUCTION MUFFLER 13K) Qty: 1000', 'stock_transactions', 'CPIPCC624JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(31, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCK426JBKZ (SUCTION 9CAY) Qty: 1000', 'stock_transactions', 'CPIPCK426JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(32, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCYC-F289JBKZ (DISCHARGE 5K2) Qty: 1000', 'stock_transactions', 'CCYC-F289JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(33, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCYC-F091JBKZ (DISCHARGE 7K) Qty: 1000', 'stock_transactions', 'CCYC-F091JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(34, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCYC-F255JBKZ (DISCHARGE 9K2) Qty: 1000', 'stock_transactions', 'CCYC-F255JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(35, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC572JBKZ (DISCHARGE 6K & 8K) Qty: 1000', 'stock_transactions', 'CPIPCC572JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(36, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC561JBKZ (DISCHARGE 10K) Qty: 1000', 'stock_transactions', 'CPIPCC561JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(37, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC623JBKZ (DISCHARGE 13K) Qty: 1000', 'stock_transactions', 'CPIPCC623JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(38, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCYC-F311JBKZ (DISCHARGE 9CAY) Qty: 1000', 'stock_transactions', 'CCYC-F311JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(39, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCPY-A455JBKZ (CAPILARY 5K) Qty: 1000', 'stock_transactions', 'CCPY-A455JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(40, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCPY-A456JBKZ (CAPILARY 7K) Qty: 1000', 'stock_transactions', 'CCPY-A456JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(41, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCPY-A457JBKZ (CAPILARY 9K) Qty: 1000', 'stock_transactions', 'CCPY-A457JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(42, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCPY-A458JBKZ (CAPILARY X6/X8) Qty: 1000', 'stock_transactions', 'CCPY-A458JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(43, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCPY-A459JBKZ (CAPILARY X10) Qty: 1000', 'stock_transactions', 'CCPY-A459JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(44, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCPY-A516JBKZ (CAPILARY X13) Qty: 1000', 'stock_transactions', 'CCPY-A516JBKZ', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(45, 5, 'INSERT', 'Input FG HEPI [Area PIPING | Shift 2 | Tgl: 2026-09-10]: PCPY-C007JB1Z (CAPILARY 9CAY) Qty: 1000', 'stock_transactions', 'PCPY-C007JB1Z', NULL, '{\"qty\":1000,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:35:32'),
(46, 1, 'INSERT', 'Pemakaian ASSY [Area PAINTING | Shift 2 | Tgl: 2026-09-10]: GCAB-A646JBPZ (Top Table) Qty: 100', 'stock_transactions', 'GCAB-A646JBPZ', NULL, '{\"qty\":100,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PAINTING\",\"table\":\"stok_pp\"}', '2026-09-10 14:35:55'),
(47, 1, 'INSERT', 'Pemakaian ASSY [Area PAINTING | Shift 2 | Tgl: 2026-09-10]: GCAB-A767JBPZ (Front Panel) Qty: 100', 'stock_transactions', 'GCAB-A767JBPZ', NULL, '{\"qty\":100,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PAINTING\",\"table\":\"stok_pp\"}', '2026-09-10 14:35:55'),
(48, 1, 'INSERT', 'Pemakaian ASSY [Area PAINTING | Shift 2 | Tgl: 2026-09-10]: LCHS-A800JBPZ (Base Pan) Qty: 100', 'stock_transactions', 'LCHS-A800JBPZ', NULL, '{\"qty\":100,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PAINTING\",\"table\":\"stok_pp\"}', '2026-09-10 14:35:55'),
(49, 1, 'INSERT', 'Pemakaian ASSY [Area PAINTING | Shift 2 | Tgl: 2026-09-10]: PPLT-B282JBPZ (Side Cover R) Qty: 100', 'stock_transactions', 'PPLT-B282JBPZ', NULL, '{\"qty\":100,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PAINTING\",\"table\":\"stok_pp\"}', '2026-09-10 14:35:55'),
(50, 1, 'INSERT', 'Pemakaian ASSY [Area HE | Shift 2 | Tgl: 2026-09-10]: PEVA-B161JBPZ (Evaporator) Qty: 200', 'stock_transactions', 'PEVA-B161JBPZ', NULL, '{\"qty\":200,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"HE\",\"table\":\"stok_he\"}', '2026-09-10 14:36:05'),
(51, 1, 'INSERT', 'Pemakaian ASSY [Area HE | Shift 2 | Tgl: 2026-09-10]: DCON-B070JBEZ (Condensor SR Normal) Qty: 200', 'stock_transactions', 'DCON-B070JBEZ', NULL, '{\"qty\":200,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"HE\",\"table\":\"stok_he\"}', '2026-09-10 14:36:05'),
(52, 1, 'INSERT', 'Pemakaian ASSY [Area HE | Shift 2 | Tgl: 2026-09-10]: DCON-B105JBEZ (Condensor DR Normal) Qty: 200', 'stock_transactions', 'DCON-B105JBEZ', NULL, '{\"qty\":200,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"HE\",\"table\":\"stok_he\"}', '2026-09-10 14:36:05'),
(53, 1, 'INSERT', 'Pemakaian ASSY [Area HE | Shift 2 | Tgl: 2026-09-10]: DCON-B074JBEZ (Condensor SR Inverter) Qty: 200', 'stock_transactions', 'DCON-B074JBEZ', NULL, '{\"qty\":200,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"HE\",\"table\":\"stok_he\"}', '2026-09-10 14:36:05'),
(54, 1, 'INSERT', 'Pemakaian ASSY [Area HE | Shift 2 | Tgl: 2026-09-10]: DCON-B075JBEZ (Condensor DR Inverter) Qty: 200', 'stock_transactions', 'DCON-B075JBEZ', NULL, '{\"qty\":200,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"HE\",\"table\":\"stok_he\"}', '2026-09-10 14:36:05'),
(55, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC533JBKZ (TUBE ASSY) Qty: 300', 'stock_transactions', 'CPIPCC533JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(56, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC616JBKZ (SUCTION 7K/5K-2) Qty: 300', 'stock_transactions', 'CPIPCC616JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(57, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC625JBKZ (SUCTION 9K2) Qty: 300', 'stock_transactions', 'CPIPCC625JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(58, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC571JBKZ (SUCTION 6K/8K/10K) Qty: 300', 'stock_transactions', 'CPIPCC571JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(59, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC624JBKZ (SUCTION MUFFLER 13K) Qty: 300', 'stock_transactions', 'CPIPCC624JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(60, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCK426JBKZ (SUCTION 9CAY) Qty: 300', 'stock_transactions', 'CPIPCK426JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(61, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCYC-F289JBKZ (DISCHARGE 5K2) Qty: 300', 'stock_transactions', 'CCYC-F289JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(62, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCYC-F091JBKZ (DISCHARGE 7K) Qty: 300', 'stock_transactions', 'CCYC-F091JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(63, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCYC-F255JBKZ (DISCHARGE 9K2) Qty: 300', 'stock_transactions', 'CCYC-F255JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(64, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC572JBKZ (DISCHARGE 6K & 8K) Qty: 300', 'stock_transactions', 'CPIPCC572JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(65, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC561JBKZ (DISCHARGE 10K) Qty: 300', 'stock_transactions', 'CPIPCC561JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(66, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CPIPCC623JBKZ (DISCHARGE 13K) Qty: 300', 'stock_transactions', 'CPIPCC623JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(67, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCYC-F311JBKZ (DISCHARGE 9CAY) Qty: 300', 'stock_transactions', 'CCYC-F311JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(68, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCPY-A455JBKZ (CAPILARY 5K) Qty: 300', 'stock_transactions', 'CCPY-A455JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(69, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCPY-A456JBKZ (CAPILARY 7K) Qty: 300', 'stock_transactions', 'CCPY-A456JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(70, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCPY-A457JBKZ (CAPILARY 9K) Qty: 300', 'stock_transactions', 'CCPY-A457JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(71, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCPY-A458JBKZ (CAPILARY X6/X8) Qty: 300', 'stock_transactions', 'CCPY-A458JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(72, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCPY-A459JBKZ (CAPILARY X10) Qty: 300', 'stock_transactions', 'CCPY-A459JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(73, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: CCPY-A516JBKZ (CAPILARY X13) Qty: 300', 'stock_transactions', 'CCPY-A516JBKZ', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(74, 1, 'INSERT', 'Pemakaian ASSY [Area PIPING | Shift 2 | Tgl: 2026-09-10]: PCPY-C007JB1Z (CAPILARY 9CAY) Qty: 300', 'stock_transactions', 'PCPY-C007JB1Z', NULL, '{\"qty\":300,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"PIPING\",\"table\":\"stok_piping\"}', '2026-09-10 14:36:25'),
(75, 1, 'INSERT', 'Pemakaian ASSY [Area INJECTION | Shift 2 | Tgl: 2026-09-10]: GGADPA056JBFA (Fan Guard) Qty: 400', 'stock_transactions', 'GGADPA056JBFA', NULL, '{\"qty\":400,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"INJECTION\",\"table\":\"stok_injection\"}', '2026-09-10 14:36:33'),
(76, 1, 'INSERT', 'Pemakaian ASSY [Area INJECTION | Shift 2 | Tgl: 2026-09-10]: GWAK-A517JBFA (Front Panel) Qty: 400', 'stock_transactions', 'GWAK-A517JBFA', NULL, '{\"qty\":400,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"INJECTION\",\"table\":\"stok_injection\"}', '2026-09-10 14:36:33'),
(77, 1, 'INSERT', 'Pemakaian ASSY [Area INJECTION | Shift 2 | Tgl: 2026-09-10]: GWAK-A517JBFB (Front Panel Black) Qty: 400', 'stock_transactions', 'GWAK-A517JBFB', NULL, '{\"qty\":400,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"INJECTION\",\"table\":\"stok_injection\"}', '2026-09-10 14:36:33'),
(78, 1, 'INSERT', 'Pemakaian ASSY [Area INJECTION | Shift 2 | Tgl: 2026-09-10]: GWAK-A520JBFA (Front Panel PCI) Qty: 400', 'stock_transactions', 'GWAK-A520JBFA', NULL, '{\"qty\":400,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"INJECTION\",\"table\":\"stok_injection\"}', '2026-09-10 14:36:33'),
(79, 1, 'INSERT', 'Pemakaian ASSY [Area INJECTION | Shift 2 | Tgl: 2026-09-10]: GWAK-A544JBFC (Front Panel DEY) Qty: 400', 'stock_transactions', 'GWAK-A544JBFC', NULL, '{\"qty\":400,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"INJECTION\",\"table\":\"stok_injection\"}', '2026-09-10 14:36:33'),
(80, 1, 'INSERT', 'Pemakaian ASSY [Area INJECTION | Shift 2 | Tgl: 2026-09-10]: LCHS-A801JBFA (Cabinet) Qty: 400', 'stock_transactions', 'LCHS-A801JBFA', NULL, '{\"qty\":400,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"INJECTION\",\"table\":\"stok_injection\"}', '2026-09-10 14:36:33'),
(81, 1, 'INSERT', 'Pemakaian ASSY [Area INJECTION | Shift 2 | Tgl: 2026-09-10]: LCHS-A801JBFC (Cabinet DEY) Qty: 400', 'stock_transactions', 'LCHS-A801JBFC', NULL, '{\"qty\":400,\"shift\":2,\"production_date\":\"2026-09-10\",\"area\":\"INJECTION\",\"table\":\"stok_injection\"}', '2026-09-10 14:36:33');

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
(1, 'A3AHA5BEY2', '2026-08-03', 1, 1, 1300, '2026-09-10 13:28:24'),
(2, 'A3AUA5BEY2', '2026-08-03', 1, 1, 1300, '2026-09-10 13:28:24');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

CREATE TABLE `stock_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('ASSY','CMC','HEPI','PAINTING','PRESS','INJECTION') NOT NULL,
  `part_code` varchar(50) NOT NULL,
  `source_table` enum('stok_injection','stok_pp','stok_he','stok_piping') NOT NULL,
  `transaction_type` enum('IN','OUT') NOT NULL,
  `qty` int(11) NOT NULL,
  `shift` int(11) DEFAULT NULL,
  `production_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_transactions`
--

INSERT INTO `stock_transactions` (`id`, `user_id`, `role`, `part_code`, `source_table`, `transaction_type`, `qty`, `shift`, `production_date`, `created_at`) VALUES
(1, 3, 'PRESS', 'GCAB-A646JBPZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:33:48'),
(2, 3, 'PRESS', 'GCAB-A767JBPZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:33:48'),
(3, 3, 'PRESS', 'LCHS-A800JBPZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:33:48'),
(4, 3, 'PRESS', 'PPLT-B282JBPZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:33:48'),
(5, 3, 'PRESS', 'GCAB-A646JBPZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:33:55'),
(6, 3, 'PRESS', 'GCAB-A767JBPZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:33:55'),
(7, 3, 'PRESS', 'LCHS-A800JBPZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:33:55'),
(8, 3, 'PRESS', 'PPLT-B282JBPZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:33:55'),
(9, 4, 'PAINTING', 'PEVA-A055VDKZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:34:20'),
(10, 4, 'PAINTING', 'GCAB-A646JBPZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:34:20'),
(11, 4, 'PAINTING', 'GCAB-A767JBPZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:34:20'),
(12, 4, 'PAINTING', 'LCHS-A800JBPZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:34:20'),
(13, 4, 'PAINTING', 'PPLT-B282JBPZ', 'stok_pp', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:34:20'),
(14, 6, 'INJECTION', 'GGADPA056JBFA', 'stok_injection', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:34:54'),
(15, 6, 'INJECTION', 'GWAK-A517JBFA', 'stok_injection', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:34:54'),
(16, 6, 'INJECTION', 'GWAK-A517JBFB', 'stok_injection', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:34:54'),
(17, 6, 'INJECTION', 'GWAK-A520JBFA', 'stok_injection', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:34:54'),
(18, 6, 'INJECTION', 'GWAK-A544JBFC', 'stok_injection', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:34:54'),
(19, 6, 'INJECTION', 'LCHS-A801JBFA', 'stok_injection', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:34:54'),
(20, 6, 'INJECTION', 'LCHS-A801JBFC', 'stok_injection', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:34:54'),
(21, 5, 'HEPI', 'PEVA-B161JBPZ', 'stok_he', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:11'),
(22, 5, 'HEPI', 'DCON-B070JBEZ', 'stok_he', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:11'),
(23, 5, 'HEPI', 'DCON-B105JBEZ', 'stok_he', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:11'),
(24, 5, 'HEPI', 'DCON-B074JBEZ', 'stok_he', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:11'),
(25, 5, 'HEPI', 'DCON-B075JBEZ', 'stok_he', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:11'),
(26, 5, 'HEPI', 'CPIPCC533JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(27, 5, 'HEPI', 'CPIPCC616JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(28, 5, 'HEPI', 'CPIPCC625JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(29, 5, 'HEPI', 'CPIPCC571JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(30, 5, 'HEPI', 'CPIPCC624JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(31, 5, 'HEPI', 'CPIPCK426JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(32, 5, 'HEPI', 'CCYC-F289JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(33, 5, 'HEPI', 'CCYC-F091JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(34, 5, 'HEPI', 'CCYC-F255JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(35, 5, 'HEPI', 'CPIPCC572JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(36, 5, 'HEPI', 'CPIPCC561JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(37, 5, 'HEPI', 'CPIPCC623JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(38, 5, 'HEPI', 'CCYC-F311JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(39, 5, 'HEPI', 'CCPY-A455JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(40, 5, 'HEPI', 'CCPY-A456JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(41, 5, 'HEPI', 'CCPY-A457JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(42, 5, 'HEPI', 'CCPY-A458JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(43, 5, 'HEPI', 'CCPY-A459JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(44, 5, 'HEPI', 'CCPY-A516JBKZ', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(45, 5, 'HEPI', 'PCPY-C007JB1Z', 'stok_piping', 'IN', 1000, 2, '2026-09-10', '2026-09-10 14:35:32'),
(46, 1, 'ASSY', 'GCAB-A646JBPZ', 'stok_pp', 'OUT', 100, 2, '2026-09-10', '2026-09-10 14:35:55'),
(47, 1, 'ASSY', 'GCAB-A767JBPZ', 'stok_pp', 'OUT', 100, 2, '2026-09-10', '2026-09-10 14:35:55'),
(48, 1, 'ASSY', 'LCHS-A800JBPZ', 'stok_pp', 'OUT', 100, 2, '2026-09-10', '2026-09-10 14:35:55'),
(49, 1, 'ASSY', 'PPLT-B282JBPZ', 'stok_pp', 'OUT', 100, 2, '2026-09-10', '2026-09-10 14:35:55'),
(50, 1, 'ASSY', 'PEVA-B161JBPZ', 'stok_he', 'OUT', 200, 2, '2026-09-10', '2026-09-10 14:36:05'),
(51, 1, 'ASSY', 'DCON-B070JBEZ', 'stok_he', 'OUT', 200, 2, '2026-09-10', '2026-09-10 14:36:05'),
(52, 1, 'ASSY', 'DCON-B105JBEZ', 'stok_he', 'OUT', 200, 2, '2026-09-10', '2026-09-10 14:36:05'),
(53, 1, 'ASSY', 'DCON-B074JBEZ', 'stok_he', 'OUT', 200, 2, '2026-09-10', '2026-09-10 14:36:05'),
(54, 1, 'ASSY', 'DCON-B075JBEZ', 'stok_he', 'OUT', 200, 2, '2026-09-10', '2026-09-10 14:36:05'),
(55, 1, 'ASSY', 'CPIPCC533JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(56, 1, 'ASSY', 'CPIPCC616JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(57, 1, 'ASSY', 'CPIPCC625JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(58, 1, 'ASSY', 'CPIPCC571JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(59, 1, 'ASSY', 'CPIPCC624JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(60, 1, 'ASSY', 'CPIPCK426JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(61, 1, 'ASSY', 'CCYC-F289JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(62, 1, 'ASSY', 'CCYC-F091JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(63, 1, 'ASSY', 'CCYC-F255JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(64, 1, 'ASSY', 'CPIPCC572JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(65, 1, 'ASSY', 'CPIPCC561JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(66, 1, 'ASSY', 'CPIPCC623JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(67, 1, 'ASSY', 'CCYC-F311JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(68, 1, 'ASSY', 'CCPY-A455JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(69, 1, 'ASSY', 'CCPY-A456JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(70, 1, 'ASSY', 'CCPY-A457JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(71, 1, 'ASSY', 'CCPY-A458JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(72, 1, 'ASSY', 'CCPY-A459JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(73, 1, 'ASSY', 'CCPY-A516JBKZ', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(74, 1, 'ASSY', 'PCPY-C007JB1Z', 'stok_piping', 'OUT', 300, 2, '2026-09-10', '2026-09-10 14:36:25'),
(75, 1, 'ASSY', 'GGADPA056JBFA', 'stok_injection', 'OUT', 400, 2, '2026-09-10', '2026-09-10 14:36:33'),
(76, 1, 'ASSY', 'GWAK-A517JBFA', 'stok_injection', 'OUT', 400, 2, '2026-09-10', '2026-09-10 14:36:33'),
(77, 1, 'ASSY', 'GWAK-A517JBFB', 'stok_injection', 'OUT', 400, 2, '2026-09-10', '2026-09-10 14:36:33'),
(78, 1, 'ASSY', 'GWAK-A520JBFA', 'stok_injection', 'OUT', 400, 2, '2026-09-10', '2026-09-10 14:36:33'),
(79, 1, 'ASSY', 'GWAK-A544JBFC', 'stok_injection', 'OUT', 400, 2, '2026-09-10', '2026-09-10 14:36:33'),
(80, 1, 'ASSY', 'LCHS-A801JBFA', 'stok_injection', 'OUT', 400, 2, '2026-09-10', '2026-09-10 14:36:33'),
(81, 1, 'ASSY', 'LCHS-A801JBFC', 'stok_injection', 'OUT', 400, 2, '2026-09-10', '2026-09-10 14:36:33');

-- --------------------------------------------------------

--
-- Table structure for table `stok_he`
--

CREATE TABLE `stok_he` (
  `part_code` varchar(50) NOT NULL,
  `part_name` varchar(100) NOT NULL,
  `qty_he` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stok_he`
--

INSERT INTO `stok_he` (`part_code`, `part_name`, `qty_he`, `updated_at`) VALUES
('DCON-B070JBEZ', 'Condensor SR Normal', 800, '2026-09-10 14:36:05'),
('DCON-B074JBEZ', 'Condensor SR Inverter', 800, '2026-09-10 14:36:05'),
('DCON-B075JBEZ', 'Condensor DR Inverter', 800, '2026-09-10 14:36:05'),
('DCON-B105JBEZ', 'Condensor DR Normal', 800, '2026-09-10 14:36:05'),
('PEVA-B161JBPZ', 'Evaporator', 800, '2026-09-10 14:36:05');

-- --------------------------------------------------------

--
-- Table structure for table `stok_injection`
--

CREATE TABLE `stok_injection` (
  `part_code` varchar(50) NOT NULL,
  `part_name` varchar(100) NOT NULL,
  `qty_inj` int(11) DEFAULT 0,
  `area` enum('AC','WM') NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stok_injection`
--

INSERT INTO `stok_injection` (`part_code`, `part_name`, `qty_inj`, `area`, `updated_at`) VALUES
('GGADPA056JBFA', 'Fan Guard', 600, 'AC', '2026-09-10 14:36:33'),
('GWAK-A517JBFA', 'Front Panel', 600, 'AC', '2026-09-10 14:36:33'),
('GWAK-A517JBFB', 'Front Panel Black', 600, 'AC', '2026-09-10 14:36:33'),
('GWAK-A520JBFA', 'Front Panel PCI', 600, 'AC', '2026-09-10 14:36:33'),
('GWAK-A544JBFC', 'Front Panel DEY', 600, 'AC', '2026-09-10 14:36:33'),
('LCHS-A801JBFA', 'Cabinet', 600, 'AC', '2026-09-10 14:36:33'),
('LCHS-A801JBFC', 'Cabinet DEY', 600, 'AC', '2026-09-10 14:36:33');

-- --------------------------------------------------------

--
-- Table structure for table `stok_piping`
--

CREATE TABLE `stok_piping` (
  `part_code` varchar(50) NOT NULL,
  `part_name` varchar(100) NOT NULL,
  `qty_piping` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stok_piping`
--

INSERT INTO `stok_piping` (`part_code`, `part_name`, `qty_piping`, `updated_at`) VALUES
('CCPY-A455JBKZ', 'CAPILARY 5K', 700, '2026-09-10 14:36:25'),
('CCPY-A456JBKZ', 'CAPILARY 7K', 700, '2026-09-10 14:36:25'),
('CCPY-A457JBKZ', 'CAPILARY 9K', 700, '2026-09-10 14:36:25'),
('CCPY-A458JBKZ', 'CAPILARY X6/X8', 700, '2026-09-10 14:36:25'),
('CCPY-A459JBKZ', 'CAPILARY X10', 700, '2026-09-10 14:36:25'),
('CCPY-A516JBKZ', 'CAPILARY X13', 700, '2026-09-10 14:36:25'),
('CCYC-F091JBKZ', 'DISCHARGE 7K', 700, '2026-09-10 14:36:25'),
('CCYC-F255JBKZ', 'DISCHARGE 9K2', 700, '2026-09-10 14:36:25'),
('CCYC-F289JBKZ', 'DISCHARGE 5K2', 700, '2026-09-10 14:36:25'),
('CCYC-F311JBKZ', 'DISCHARGE 9CAY', 700, '2026-09-10 14:36:25'),
('CPIPCC533JBKZ', 'TUBE ASSY', 700, '2026-09-10 14:36:25'),
('CPIPCC561JBKZ', 'DISCHARGE 10K', 700, '2026-09-10 14:36:25'),
('CPIPCC571JBKZ', 'SUCTION 6K/8K/10K', 700, '2026-09-10 14:36:25'),
('CPIPCC572JBKZ', 'DISCHARGE 6K & 8K', 700, '2026-09-10 14:36:25'),
('CPIPCC616JBKZ', 'SUCTION 7K/5K-2', 700, '2026-09-10 14:36:25'),
('CPIPCC623JBKZ', 'DISCHARGE 13K', 700, '2026-09-10 14:36:25'),
('CPIPCC624JBKZ', 'SUCTION MUFFLER 13K', 700, '2026-09-10 14:36:25'),
('CPIPCC625JBKZ', 'SUCTION 9K2', 700, '2026-09-10 14:36:25'),
('CPIPCK426JBKZ', 'SUCTION 9CAY', 700, '2026-09-10 14:36:25'),
('PCPY-C007JB1Z', 'CAPILARY 9CAY', 700, '2026-09-10 14:36:25');

-- --------------------------------------------------------

--
-- Table structure for table `stok_pp`
--

CREATE TABLE `stok_pp` (
  `part_code` varchar(50) NOT NULL,
  `part_name` varchar(100) NOT NULL,
  `qty_press` int(11) DEFAULT 0,
  `qty_paint` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stok_pp`
--

INSERT INTO `stok_pp` (`part_code`, `part_name`, `qty_press`, `qty_paint`, `updated_at`) VALUES
('GCAB-A646JBPZ', 'Top Table', 1000, 900, '2026-09-10 14:35:55'),
('GCAB-A767JBPZ', 'Front Panel', 1000, 900, '2026-09-10 14:35:55'),
('LCHS-A800JBPZ', 'Base Pan', 1000, 900, '2026-09-10 14:35:55'),
('PEVA-A055VDKZ', 'EVAP REF 162', -1000, 1000, '2026-09-10 14:34:20'),
('PPLT-B282JBPZ', 'Side Cover R', 1000, 900, '2026-09-10 14:35:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('ASSY','CMC','HEPI','PAINTING','PRESS','INJECTION') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'assy01', 'SeidMail01', 'ASSY', '2026-09-09 12:01:17'),
(2, 'cmc01', 'SeidMail01', 'CMC', '2026-09-09 12:01:31'),
(3, 'press01', 'SeidMail01', 'PRESS', '2026-09-09 12:01:44'),
(4, 'paint01', 'SeidMail01', 'PAINTING', '2026-09-09 12:01:53'),
(5, 'hepi01', 'SeidMail01', 'HEPI', '2026-09-09 12:02:03'),
(6, 'injection01', 'SeidMail01', 'INJECTION', '2026-09-09 12:02:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `planning`
--
ALTER TABLE `planning`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stok_he`
--
ALTER TABLE `stok_he`
  ADD PRIMARY KEY (`part_code`);

--
-- Indexes for table `stok_injection`
--
ALTER TABLE `stok_injection`
  ADD PRIMARY KEY (`part_code`);

--
-- Indexes for table `stok_piping`
--
ALTER TABLE `stok_piping`
  ADD PRIMARY KEY (`part_code`);

--
-- Indexes for table `stok_pp`
--
ALTER TABLE `stok_pp`
  ADD PRIMARY KEY (`part_code`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `planning`
--
ALTER TABLE `planning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
