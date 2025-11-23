-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 13, 2025 at 10:16 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `amc`
--

-- --------------------------------------------------------

--
-- Table structure for table `aircraft_details`
--

CREATE TABLE `aircraft_details` (
  `registration` varchar(10) NOT NULL,
  `aircraft_type` varchar(30) DEFAULT NULL,
  `operator_airline` varchar(100) DEFAULT NULL,
  `category` varchar(20) NOT NULL COMMENT 'Commercial, Cargo, Charter',
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `aircraft_details`
--

INSERT INTO `aircraft_details` (`registration`, `aircraft_type`, `operator_airline`, `category`, `notes`, `updated_at`) VALUES
('13-031', 'DO 328', '', 'charter', NULL, '2025-07-21 14:00:56'),
('13-075', 'DO 328', '', 'charter', NULL, '2025-07-21 14:00:56'),
('13031', 'DO 328', '', 'charter', NULL, '2025-07-21 14:00:56'),
('166-375', 'G V', '', 'charter', NULL, '2025-07-21 14:00:56'),
('18-0093', 'A 400M', '', 'charter', NULL, '2025-07-21 14:00:56'),
('2-TSPP', 'B 738', '', 'charter', NULL, '2025-07-21 14:00:56'),
('20241', 'XIAN Y20 B', '', 'charter', NULL, '2025-07-21 14:00:56'),
('20248', 'XIAN Y20 B', '', 'charter', NULL, '2025-07-21 14:00:56'),
('330-002', 'A 330', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-NATHO', 'G VI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-NYC', 'L 1000', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-VCB', 'CL 350', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-VCC', 'CL 350', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-VCE', 'CL 350', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-VCR', 'CL 350', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-VCZ', 'CL 350', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-VFF', 'CL 60', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-VIE', 'G 7500', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-VIH', 'G 7500', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-VIL', 'G 7000', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-VIT', 'G 7500', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-VJG', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9H-VJI', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('9M-VEV', 'AVANTI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('A-001', 'BBJ 2', 'TNI AU', 'charter', NULL, '2025-07-21 14:00:56'),
('A-7304', 'B 734', 'TNI AU', 'charter', NULL, '2025-07-21 14:00:56'),
('A-7305', 'B 734', 'TNI AU', 'charter', NULL, '2025-07-21 14:00:56'),
('A-7306', 'B 735', 'TNI AU', 'charter', NULL, '2025-07-21 14:00:56'),
('A-7307', 'B 735', 'TNI AU', 'charter', NULL, '2025-07-21 14:00:56'),
('A-7308', 'B 735', 'TNI AU', 'charter', NULL, '2025-07-21 14:00:56'),
('A-7309', 'B 738', 'TNI AU', 'charter', NULL, '2025-07-21 14:00:56'),
('A2-MBA', 'C 208', '', 'charter', NULL, '2025-07-21 14:00:56'),
('A39-007', 'A 330', '', 'charter', NULL, '2025-07-21 14:00:56'),
('A56-003', 'FAL 7X', '', 'charter', NULL, '2025-07-21 14:00:56'),
('A6-RRJ', 'A 319', '', 'charter', NULL, '2025-07-21 14:00:56'),
('A7-CGG', 'G VI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('A7-CHG', 'G VII', '', 'charter', NULL, '2025-07-21 14:00:56'),
('A7-MHH', 'A 319', '', 'charter', NULL, '2025-07-21 14:00:56'),
('ade', 'setiawan', 'membership', 'Charter', '', '2025-08-31 14:11:15'),
('B-16888', 'CL 60', '', 'charter', NULL, '2025-07-21 14:00:56'),
('B-6738', 'A 320', '', 'charter', NULL, '2025-07-21 14:00:56'),
('B-8125', 'G V', '', 'charter', NULL, '2025-07-21 14:00:56'),
('B-8160', 'G V', '', 'charter', NULL, '2025-07-21 14:00:56'),
('B-8255', 'G V', '', 'charter', NULL, '2025-07-21 14:00:56'),
('B-8309', 'G V', '', 'charter', NULL, '2025-07-21 14:00:56'),
('C-9BAX', 'B 733', '', 'charter', NULL, '2025-07-21 14:00:56'),
('C-FVDH', 'TWIN OTTER', '', 'charter', NULL, '2025-07-21 14:00:56'),
('C-GSGP', 'TWIN OTTER', '', 'charter', NULL, '2025-07-21 14:00:56'),
('CS-GLC', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('EP-IGA', 'A 340', '', 'charter', NULL, '2025-07-21 14:00:56'),
('ER-BYK', 'B 744', '', 'charter', NULL, '2025-07-21 14:00:56'),
('F-HBQT', 'FAL 8X', '', 'charter', NULL, '2025-07-21 14:00:56'),
('F-HGNU', 'ATR 72', '', 'charter', NULL, '2025-07-21 14:00:56'),
('F-RAFA', 'FAL 7X', '', 'charter', NULL, '2025-07-21 14:00:56'),
('F-RAFP', 'FAL 900', '', 'charter', NULL, '2025-07-21 14:00:56'),
('F-XCUH', 'ATLANTIQUE 2', '', 'charter', NULL, '2025-07-21 14:00:56'),
('FNY-5663A', 'CARACAL', '', 'charter', NULL, '2025-07-21 14:00:56'),
('FNY-5663D', 'CARACAL', '', 'charter', NULL, '2025-07-21 14:00:56'),
('HA-5180', 'BELL 412EP', '', 'charter', NULL, '2025-07-21 14:00:56'),
('HB-JKQ', 'G VI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('HL-8299', 'G VI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('HS-KCS', 'CT 750', '', 'charter', NULL, '2025-07-21 14:00:56'),
('M-ARCO', 'G V', '', 'charter', NULL, '2025-07-21 14:00:56'),
('M-ARDI', 'G VI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('M-JGVJ', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('M-PTGG', 'FAL 8X', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-110H', 'C 208', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-118CY', 'DA 62', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-11UB', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-171GA', 'G 700', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-198RL', 'G V', 'TRAVIRA', 'charter', NULL, '2025-07-21 14:00:56'),
('N-204DD', 'G 200', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-227GV', 'L 1000', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('N-2409K', 'HK 800', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-308KB', 'G 280', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-308KG', 'G V', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-38SV', 'LJ 60', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-440QS', 'G IV', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-45AY', 'CT 680', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-484BA', 'LJ 31', 'IAP', 'charter', NULL, '2025-07-21 14:00:56'),
('N-61WC', 'AVANTI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-61WX', 'G VI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-61WZ', 'G V', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-69HY', 'CT 525', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-705HM', 'G 500', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-777ZH', 'G VI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-779LG', 'CL 60', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-810CK', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-887WM', 'G VI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-889ST', 'G7500', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-902CL', 'CT 700', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-91GT', 'CT 525', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-939AP', 'GLEX', 'BIOMANTARA', 'charter', NULL, '2025-07-21 14:00:56'),
('N-939GS', 'GLEX', 'BIOMANTARA', 'charter', NULL, '2025-07-21 14:00:56'),
('N-939SG', 'G VI', 'BIOMANTARA', 'charter', NULL, '2025-07-21 14:00:56'),
('N-954JJ', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-9688R', 'PH 300', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-970NX', 'GLEX', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('N-977HS', 'G V', '', 'charter', NULL, '2025-07-21 14:00:56'),
('N-977JH', 'CT 680', 'JIP', 'charter', NULL, '2025-07-21 14:00:56'),
('P-3102', 'AS 365', 'POLICE', 'charter', NULL, '2025-07-21 14:00:56'),
('P-3306', 'AW 169', '', 'charter', NULL, '2025-07-21 14:00:56'),
('P-3309', 'AW 169', 'POLICE', 'charter', NULL, '2025-07-21 14:00:56'),
('P-4301', 'BE 1900', 'POLICE', 'charter', NULL, '2025-07-21 14:00:56'),
('P-4501', 'CN 295', 'POLICE', 'charter', NULL, '2025-07-21 14:00:56'),
('P-7001', 'AW 189', '', 'charter', NULL, '2025-07-21 14:00:56'),
('P-7301', 'B 738', 'POLICE', 'charter', NULL, '2025-07-21 14:00:56'),
('P-8304', 'CN 235', '', 'charter', NULL, '2025-07-21 14:00:56'),
('P4-AL9', 'CL 850', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-BBJ', 'B 734', 'B. B. N.', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-BBN', 'B 738', 'B. B. N', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-BGF', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKA', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKB', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKC', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKD', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKE', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKF', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKG', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKH', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKI', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKJ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKK', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKL', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKM', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKN', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKO', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKP', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKQ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKR', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKS', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKT', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKU', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKV', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKW', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKX', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKY', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BKZ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLA', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLB', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLC', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLD', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLE', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLF', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLG', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLH', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLI', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLJ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLK', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLL', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLM', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLN', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLO', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLP', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLQ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLR', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLS', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLT', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLU', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLV', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLW', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLX', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLY', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BLZ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BMB', 'EMB 135', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-BON', 'G IV', 'JETSET', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-BVB', 'C 208', 'SUSI AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BVN', 'C 208', 'SUSI AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BVO', 'AVANTI', 'SUSI AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BVV', 'AVANTI', 'SUSI AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-BVX', 'AVANTI', 'SUSI AIR', 'Komersial', NULL, '2025-10-26 06:25:20'),
('PK-CAA', 'CT 700', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-CAN', 'KING AIR', 'DEPHUB', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-CAQ', 'KING AIR', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-CAR', 'HK 900', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-CEO', 'HK 800', 'JETSET', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-DAM', 'AS 350', 'DERAZONA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-DAP', 'AS 350', 'DERAZONA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-DAR', 'BELL 412', 'DERAZONA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-DAS', 'BELL 412', 'DERAZONA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-DGC', 'BAE ATP', 'DERAYA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-DJH', 'AW 169', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-DJM', 'AW 169', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-DJR', 'AW 169', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-DJV', 'HK 900', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-DPI', 'C 208', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-DPR', 'EC 145', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-FGG', 'EC 45', 'SURYA AIR', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-FGS', 'BK 117', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-FGT', 'EC 155', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-GE ER D', 'G5', 'SUSI', 'Commercial', '', '2025-09-07 07:08:28'),
('PK-GFA', 'B 738', 'GARUDA', 'Komersial', NULL, '2025-10-26 06:31:41'),
('PK-GFC', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFD', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFE', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFF', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFG', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFH', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFI', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFJ', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFK', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFL', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFM', 'B 738', 'GARUDA', 'Komersial', NULL, '2025-10-26 06:29:49'),
('PK-GFN', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFO', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFP', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFQ', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFR', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFS', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFT', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFU', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFV', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFW', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFX', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GFY', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GJQ', 'ATR 72', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GJT', 'ATR 72', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GJV', 'ATR 72', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLA', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLC', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLD', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLE', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLF', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLG', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLH', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLI', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLJ', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLK', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLL', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLM', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLN', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLO', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLP', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLQ', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLR', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLS', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLT', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLU', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLV', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLW', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLX', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLY', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GLZ', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMA', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMC', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMD', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GME', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMF', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMG', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMH', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMI', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMJ', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMK', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GML', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMM', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMN', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMO', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMP', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMQ', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMR', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMS', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMT', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMU', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMV', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMW', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMX', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMY', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GMZ', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNA', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNC', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GND', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNE', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNF', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNG', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNH', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNI', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNJ', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNK', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNL', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNM', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNN', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNO', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNP', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNQ', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNR', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNS', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNT', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNU', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNV', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNW', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNX', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNY', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GNZ', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQA', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQC', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQD', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQE', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQF', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQG', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQH', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQI', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQJ', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQK', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQL', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQM', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQN', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQO', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQP', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQQ', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQR', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQS', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQT', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQU', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQV', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQW', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQX', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQY', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GQZ', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GRD', 'BBJ 2', 'PTN', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-GSA', 'C 208', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-GTA', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTC', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTD', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTE', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTF', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTG', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTH', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTI', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTJ', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTK', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTL', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTM', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTN', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTO', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTP', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTQ', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTR', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTS', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTT', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTU', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTV', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTW', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTX', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTY', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GTZ', 'A 320', 'CITILINK', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUA', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUC', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUD', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUE', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUF', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUG', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUH', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUI', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUJ', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUK', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUL', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUM', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUN', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUO', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUP', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUQ', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUR', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUS', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUT', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUU', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUV', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUW', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUX', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUY', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-GUZ', 'B 738', 'GARUDA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-IDR', 'CL 850', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-JBK', 'KING AIR', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-JBP', 'KING AIR', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-JCO', 'PH 300', 'PURAWISATA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-JRB', 'B 733', 'JAYAWIJAYA', 'Cargo', NULL, '2025-10-26 06:22:59'),
('PK-JTO', 'EC 130', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-JTX', 'BELL 407', 'AIR PASIFIC', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-KAS', 'CL 850', 'JETSET', 'Charter', NULL, '2025-10-26 06:17:35'),
('PK-LAA', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAB', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAC', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAD', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAE', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAF', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAG', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAH', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAI', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAJ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAK', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAL', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAM', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAN', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAO', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAP', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAQ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAR', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAS', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAT', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAU', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAV', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAW', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAX', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAY', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LAZ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LBS', 'CL 850', 'SUIII', 'Commercial', '', '2025-09-07 07:09:53'),
('PK-LBW', 'B 738', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDA', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDB', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDC', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDD', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDE', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDF', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDG', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDH', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDI', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDJ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDK', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDL', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDM', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDN', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDO', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDP', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDQ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDR', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDS', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDT', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDU', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDV', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDW', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDX', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDY', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LDZ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUA', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUB', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUC', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUD', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUE', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUF', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUG', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUH', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUI', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUJ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUK', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUL', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUM', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUN', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUO', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUP', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUQ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUR', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUS', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUT', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUU', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUV', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUW', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUX', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUY', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LUZ', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-LZH', 'A 320', 'BATIK AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-MBA', 'B 733', 'TRI MG', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-MBM', 'B 733', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-MGZ', 'B 733', 'TRI MG', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-NKV', 'B 733', 'AIRNESIA', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-NMI', 'B 733', 'AIRNESIA', 'Cargo', NULL, '2025-10-26 06:09:44'),
('PK-OAM', 'DHC 6', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-OCF', 'DHC 6', 'AIRFAST', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-OCH', 'TWIN OTTER', 'AIRFAST', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-OCI', 'DHC 6', 'AIRFAST', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-OCJ', 'DHC 6', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-OCK', 'DHC-6', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-ODC', 'AS350', 'AIRFAST', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-OTD', 'B 738', 'RIMBUN AIR', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-OTR', 'ATR 72', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-PAH', 'ATR 72', 'PELITA', 'Komersial', NULL, '2025-10-26 10:05:37'),
('PK-PAM', 'ATR 72', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-PAW', 'ATR 72', 'PELITA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-PAX', 'ATR 42', 'PELITA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-PDC', 'S 76', 'PELITA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-PJJ', 'RJ 85', 'SETNEG', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-PUJ', 'BELL 412', 'PELITA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-PUK', 'BELL 412', 'PELITA', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-RAW', 'DO 328', 'TRIGANA', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-RDA', 'HK 900', 'TRI MG', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-RJA', 'EMB 135', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RJH', 'EC 135', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RJQ', 'C 172', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RJS', 'AW 139', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RJT', 'CT 560', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RJV', 'C 208', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RJX', 'EMB 135', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RSO', 'CT 650', 'ENGGANG', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RSS', 'EMB 135', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RTF', 'C 172', 'GENESA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RTK', 'C 172', 'GENESA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RTN', 'PIPER PA 34', 'GENESA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RTP', 'R 22', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RTW', 'C 172', 'GENESA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RTX', 'AS 365', 'GENESA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-RTY', 'AS 365', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-SNF', 'C 208', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-SNG', 'C 208', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-SNM', 'C 208', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-SNN', 'C 208', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-SNP', 'C 208', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-SNU', 'P-750', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TFS', 'EMB 135', 'IAT', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-THT', 'ATR 42', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TMI', 'G IV', 'TRAVIRA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TUM', 'KING AIR', 'TRAVIRA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TVB', 'AW 139', 'TRAVIRA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TVC', 'AW 139', 'TRAVIRA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TVE', 'ATR 72', 'TRAVIRA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TVI', 'ATR 42', 'TRAVIRA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TVJ', 'AW 139', 'TRAVIRA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TVM', 'ATR 72', 'TRAVIRA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TVX', 'C 208', 'TRAVIRA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TWC', 'AGUSTA', 'TRANSWISATA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TWP', 'BELL 412', 'TRANSWISATA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TWV', 'BELL 412', 'TRANSWISATA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TWW', 'C 212', 'TRANSWISATA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TWX', 'KING AIR', 'TRANSWISATA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TWY', 'G IV', 'TRANSWISATA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-TWZ', 'AW 109', 'TRANSWISATA', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-VCD', 'PA 34', 'PENAS', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-VCS', 'EMB 135', 'JETSET', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-VVF', 'C 208', 'SUSI AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-VVH', 'C 208', 'SUSI AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-VVM', 'C 208', 'SUSI AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-VVO', 'C 208', 'SUSI AIR', 'commercial', NULL, '2025-07-21 14:00:56'),
('PK-WMI', 'C 208', '', 'charter', NULL, '2025-07-21 14:00:56'),
('pk-wowow', 'xpander', '', 'Charter', '', '2025-07-22 00:42:55'),
('PK-WSJ', 'BELL 429', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-YGK', 'HK 400', 'TRI MG', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-YGR', 'HK 800', 'TRI MG', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-YGV', 'B733', 'TRI MG', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-YRD', 'B 733', 'TRIGANA', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-YRE', 'ATR 42', '', 'charter', NULL, '2025-07-21 14:00:56'),
('PK-YSC', 'B 733', 'TRIGANA', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-YSG', 'B 733', 'TRIGANA', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-YSH', 'B 733', 'SUSI AIR', 'Cargo', NULL, '2025-10-26 06:13:39'),
('PK-YSN', 'B 733', 'TRIGANA', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-YST', 'B 733', 'TRIGANA', 'Cargo', NULL, '2025-10-30 07:52:25'),
('PK-YSV', 'B 733', 'TRIGANA', 'cargo', NULL, '2025-07-21 14:00:56'),
('PK-YSZ', 'B 733', 'TRIGANA', 'cargo', NULL, '2025-07-21 14:00:56'),
('PT-MBC', 'EMB 550', '', 'charter', NULL, '2025-07-21 14:00:56'),
('RP-C7256', 'ATR 72', '', 'charter', NULL, '2025-07-21 14:00:56'),
('RP-C7257', 'ATR 72', '', 'charter', NULL, '2025-07-21 14:00:56'),
('RP-C8575', 'G IV', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-187', 'EMB 135', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-3338', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-727', 'HK 800', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-777', 'BBJ', 'JIP', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-7HS', 'G VII', 'JIP', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-808', 'G VI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-889', 'EMB 135', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-977', 'G IV', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-A187', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-AAA', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-ARN', 'G VI', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-BAT', 'EMB 135', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-BOSS', 'A 319', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-EQR', 'G IV', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-FCN', 'HK 800', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-GBS', 'EMB 135', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-HKG', 'PH 300', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-HNA', 'G IV', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-J3T', 'G V', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-JCI', 'EMB 135', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-MEL', 'GLEX', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-MGM', 'G IV', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-MPI', 'EMB 135', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-RSG', 'G V', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-TUN', 'HK 850', '', 'charter', NULL, '2025-07-21 14:00:56'),
('T7-X14', 'G VI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('TC-TRK', 'B 747-800', '', 'charter', NULL, '2025-07-21 14:00:56'),
('TC-TUR', 'A 330-200', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VH-8PO', 'KING AIR', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VH-BVS', 'BARON 58', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VH-CAD', 'FAL 900', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VH-OFJ', 'HK 800', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VH-PFS', 'LJ 45', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VH-RDI', 'LJ 60', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VH-TCN', 'AS 350', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VH-YYQ', 'AS 350', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VN-A268', 'EMB 135', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VN-A878', 'B 787', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VP-CAH', 'G 7500', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VP-CAK', 'G V', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VP-CDZ', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VP-CGO', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VP-CLL', 'EMB 135', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VP-CPT', 'GLEX', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VP-CTC', 'G 7500', 'PREMI', 'charter', NULL, '2025-07-21 14:00:56'),
('VP-CTW', 'G VI', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VP-CWS', 'G IV', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VP-CWZ', 'G V', '', 'charter', NULL, '2025-07-21 14:00:56'),
('VT-PRM', 'LINAGE 1000', '', 'charter', NULL, '2025-07-21 14:00:56'),
('ZM-408', 'A 400M', '', 'charter', NULL, '2025-07-21 14:00:56'),
('ZS-ZBB', 'B 733', '', 'charter', NULL, '2025-07-21 14:00:56');

-- --------------------------------------------------------

--
-- Table structure for table `aircraft_movements`
--

CREATE TABLE `aircraft_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `registration` varchar(10) NOT NULL,
  `aircraft_type` varchar(30) DEFAULT NULL,
  `on_block_time` varchar(50) DEFAULT NULL COMMENT 'Stores user input like "1430" or "EX RON". Parsed by backend.',
  `off_block_time` varchar(50) DEFAULT NULL COMMENT 'Stores user input like "1500". Parsed by backend.',
  `parking_stand` varchar(20) NOT NULL,
  `from_location` varchar(50) DEFAULT NULL,
  `to_location` varchar(50) DEFAULT NULL,
  `flight_no_arr` varchar(20) DEFAULT NULL,
  `flight_no_dep` varchar(20) DEFAULT NULL,
  `operator_airline` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `is_ron` tinyint(1) NOT NULL DEFAULT 0,
  `ron_complete` tinyint(1) NOT NULL DEFAULT 0,
  `movement_date` date NOT NULL,
  `user_id_created` bigint(20) UNSIGNED NOT NULL,
  `user_id_updated` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `on_block_date` date DEFAULT NULL,
  `off_block_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `aircraft_movements`
--

INSERT INTO `aircraft_movements` (`id`, `registration`, `aircraft_type`, `on_block_time`, `off_block_time`, `parking_stand`, `from_location`, `to_location`, `flight_no_arr`, `flight_no_dep`, `operator_airline`, `remarks`, `is_ron`, `ron_complete`, `movement_date`, `user_id_created`, `user_id_updated`, `created_at`, `updated_at`, `on_block_date`, `off_block_date`) VALUES
(26, 'PK-VVM', 'C 208', '12:33 (14/08/2025)', '11:55 (16/08/2025)', 'A0', '', '', '', '', 'SUSI AIR', '', 1, 1, '2025-08-14', 1, 1, '2025-08-14 15:29:18', '2025-08-16 04:32:29', '2025-08-14', '2025-08-16'),
(27, 'PK-YST', 'B 733', '12:33 (14/08/2025)', '22:35 (16/08/2025)', 'B11', '', '', '', '', 'TRIGANA', '', 1, 1, '2025-08-14', 1, 1, '2025-08-14 15:29:40', '2025-08-16 04:47:42', '2025-08-14', '2025-08-16'),
(28, 'PK-NMI', 'B 733', '13:56 (14/08/2025)', '12:55 (16/08/2025)', 'B12', '', '', '', '', 'AIRNESIA', '', 1, 1, '2025-08-14', 1, 1, '2025-08-14 15:29:51', '2025-08-16 04:57:26', '2025-08-14', '2025-08-16'),
(29, 'PK-YSH', 'B 733', '12:23 (14/08/2025)', '14:00 (03/10/2025)', 'B13', '', '', '', '', 'TRIGANA', '', 1, 1, '2025-08-14', 1, 2, '2025-08-14 15:30:18', '2025-10-03 04:06:33', '2025-08-14', '2025-10-03'),
(30, 'PK-PAH', 'ATR 72', '12:35 (14/08/2025) (15/08/2025)', '13:00 (26/10/2025)', 'A2', '', '', 'IP 7301', '', 'PELITA', '', 1, 1, '2025-08-14', 1, 2, '2025-08-14 15:30:27', '2025-10-26 10:05:52', '2025-08-14', '2025-10-26'),
(31, 'PK-GJT', 'ATR 72', '12:33 (14/08/2025)', '15:00 (26/10/2025)', 'A3', '', '', '', '', 'CITILINK', '', 1, 1, '2025-08-14', 1, 2, '2025-08-14 15:30:48', '2025-10-26 06:12:17', '2025-08-14', '2025-10-26'),
(32, 'N-977JH', 'CT 680', '12:35 (1408/2025) (14/08/2025)', '15:00 (14/08/2025)', 'B4', '', '', '', '', 'JIP', '', 1, 1, '2025-08-14', 1, 1, '2025-08-14 15:31:18', '2025-08-14 15:32:33', '2025-08-14', '2025-08-14'),
(33, 'PK-BKR', 'A 320', '12:23 (14/08/2025)', '16:00 (14/08/2025)', 'A3', '', '', '', '', 'BATIK AIR', '', 1, 1, '2025-08-14', 1, 1, '2025-08-14 15:31:27', '2025-08-14 15:33:54', '2025-08-14', '2025-08-14'),
(34, 'PK-BLM', 'A 320', '12:35 (15/08/2025)', '15:22 (15/08/2025)', 'B3', '', '', '', '', 'BATIK AIR', '', 1, 1, '2025-08-15', 1, 1, '2025-08-15 03:16:27', '2025-08-15 03:16:53', '2025-08-15', '2025-08-15'),
(35, 'T7-777', 'B 733', '12:33 (15/08/2025)', '15:00 (16/08/2025)', 'B1', '', '', '', '', 'AIRNESIA', '', 1, 1, '2025-08-15', 1, 1, '2025-08-15 03:58:23', '2025-08-16 05:27:03', '2025-08-15', '2025-08-16'),
(38, 'T7-X14', 'G VI', '12:35 (15/08/2025)', '12:00 (16/08/2025)', 'SA27', '', '', '', '', '', '', 1, 1, '2025-08-15', 1, 1, '2025-08-15 04:03:59', '2025-08-16 05:26:58', '2025-08-15', '2025-08-16'),
(39, 'T7-187', 'EMB 135', '12:35 (15/08/2025)', NULL, 'SA26', '', '', '', '', '', '', 1, 0, '2025-08-15', 1, 1, '2025-08-15 07:19:22', '2025-08-15 07:20:30', '2025-08-15', NULL),
(40, 'VP-CLL', 'EMB 135', '12:23 (15/08/2025)', '15:00 (16/08/2025)', 'SA30', '', '', '', '', '', '', 1, 1, '2025-08-15', 1, 1, '2025-08-15 07:19:38', '2025-08-16 05:26:54', '2025-08-15', '2025-08-16'),
(41, 'PK-GMV', 'B 738', '12:35 (15/08/2025)', NULL, 'SA25', '', '', '', '', 'GARUDA', '', 1, 0, '2025-08-15', 1, 1, '2025-08-15 07:20:08', '2025-08-15 07:21:44', '2025-08-15', NULL),
(42, 'N-484BA', 'LJ 31', '12:35 (15/08/2025)', '13:00 (03/10/2025)', 'B4', '', '', '', '', 'IAP', '', 1, 1, '2025-08-15', 1, 2, '2025-08-15 07:21:03', '2025-10-03 04:06:23', '2025-08-15', '2025-10-03'),
(43, 'N-977JH', 'CT 680', '12:24 (15/08/2025)', '15:00 (16/08/2025)', 'RW08', '', '', '', '', 'JIP', '', 1, 1, '2025-08-15', 1, 1, '2025-08-15 07:21:11', '2025-08-16 03:45:33', '2025-08-15', '2025-08-16'),
(44, 'PK-BON', 'G IV', '12:23 (15/08/2025)', NULL, 'RW02', '', '', '', '', 'JETSET', '', 1, 0, '2025-08-15', 1, 1, '2025-08-15 07:21:32', '2025-08-15 07:21:44', '2025-08-15', NULL),
(45, 'PK-BVX', 'AVANTI', '12:33 (15/08/2025)', NULL, 'NSA15', '', '', '', '', 'SUSI AIR', '', 1, 0, '2025-08-15', 1, 1, '2025-08-15 07:37:46', '2025-08-16 03:44:56', '2025-08-15', NULL),
(46, 'PK-GFM', 'B 738', '12:24 (15/08/2025)', NULL, 'SA05', '', '', '', '', 'GARUDA', '', 1, 0, '2025-08-15', 1, 1, '2025-08-15 07:38:40', '2025-08-16 03:44:56', '2025-08-15', NULL),
(47, 'PK-KAS', 'CL 850', '12:35 (15/08/2025)', NULL, 'SA20', '', '', '', '', 'JETSET', '', 1, 0, '2025-08-15', 1, 1, '2025-08-15 07:38:51', '2025-08-16 03:44:56', '2025-08-15', NULL),
(48, 'pk-grd', 'BBJ 2', '12:33', '15:00 (16/08/2025)', 'B8', '', '', '', '', 'PTN', '', 0, 0, '2025-08-16', 1, 1, '2025-08-16 04:34:09', '2025-08-16 04:34:27', '2025-08-16', '2025-08-16'),
(49, 'PK-AAA', 'A 320', '12:33', '15:56', 'RE01', '', '', '', '', 'SUSI AIR', '', 0, 0, '2025-08-16', 1, 1, '2025-08-16 04:48:47', '2025-08-16 04:48:58', '2025-08-16', '2025-08-16'),
(50, 'PK-NMI', 'B 733', '12:35 (16/08/2025)', NULL, 'B7', '', '', '', '', 'AIRNESIA', '', 1, 0, '2025-08-16', 1, 1, '2025-08-16 05:30:12', '2025-08-28 13:04:37', '2025-08-16', NULL),
(51, 'PK-LUR', 'A 320', '12:35', '18:22', 'B1', '', '', '', '', 'BATIK AIR', '', 0, 0, '2025-08-16', 1, 1, '2025-08-16 05:32:34', '2025-08-16 09:35:12', '2025-08-16', '2025-08-16'),
(52, 'peka peka', '', '12:33 (29/08/2025)', NULL, 'RW06', '', '', '', '', '', '', 1, 0, '2025-08-29', 1, 2, '2025-08-29 15:19:43', '2025-08-31 16:02:35', '2025-08-29', NULL),
(53, 'VP-CLL', 'EMB 135', '12:22 (05/09/2025)', '13:00 (03/10/2025)', 'B1', '', '', '', '', '', '', 1, 1, '2025-09-05', 2, 2, '2025-09-05 02:28:47', '2025-10-03 04:06:46', NULL, '2025-10-03'),
(54, 't7-x14', 'G VI', '13:5 (05/09/2025)', '15:00 (26/10/2025)', 'A1', '', '', '', '', '', '', 1, 1, '2025-09-05', 2, 2, '2025-09-05 06:12:33', '2025-10-26 06:12:26', NULL, '2025-10-26'),
(55, 'PK-LUO', 'A 320', '12:33 (05/09/2025)', NULL, 'SA15', 'WARR', 'WARR', 'ID 7510', 'ID 7515', 'BATIK AIR', '', 1, 0, '2025-09-05', 2, 2, '2025-09-05 06:17:04', '2025-09-07 06:33:12', '2025-09-05', NULL),
(56, 'PK-BKU', 'A 320', '13:45 (05/09/2025)', NULL, 'SA16', 'WARR', 'WARR', 'ID 7514', 'ID 7515', 'BATIK AIR', '', 1, 0, '2025-09-05', 2, 2, '2025-09-05 06:22:01', '2025-09-07 06:33:12', '2025-09-05', NULL),
(57, 'PK-GAB', 'A320', '08:00', '09:00', 'A1', 'SIN', 'CGK', 'GA837', 'GA838', 'Garuda Indonesia', NULL, 0, 0, '2025-09-05', 1, 2, '2025-09-05 08:01:29', '2025-09-05 09:33:54', '2025-09-05', '2025-09-05'),
(58, 'PK-LOH', 'B738', '08:30', '0930', 'B2', 'KUL', 'CGK', 'ID7283', 'ID7284', 'Batik Air', NULL, 0, 0, '2025-09-05', 1, 2, '2025-09-05 08:01:29', '2025-09-05 09:33:54', '2025-09-05', '2025-09-05'),
(59, '9M-LCA', 'A330', '10:00 (05/09/2025)', NULL, 'C3', 'JED', 'CGK', 'OD901', '', 'Malindo Air', NULL, 1, 0, '2025-09-05', 1, 2, '2025-09-05 08:01:29', '2025-09-07 06:33:12', '2025-09-05', NULL),
(60, 'PK-GJV', 'ATR 72', '02:04 (07/09/2025)', NULL, 'WR01', 'WAHH', '', 'QG 1101', '', 'CITILINK', '', 1, 0, '2025-09-07', 2, 2, '2025-09-07 07:05:45', '2025-09-07 08:14:53', '2025-09-07', NULL),
(61, 'PK-YST', 'B 733', '12:33 (17/09/2025)', NULL, 'RE05', '', '', '', '', 'TRIGANA', '', 1, 0, '2025-09-17', 2, 2, '2025-09-17 08:03:45', '2025-09-17 08:14:03', '2025-09-17', NULL),
(62, 'PK-LUBIS', 'XPANDER', ' (19/09/2025)', NULL, 'WR03', '', '', '', '', '', '', 1, 0, '2025-09-17', 2, 2, '2025-09-17 08:14:46', '2025-09-19 15:30:04', NULL, NULL),
(63, 'WADASD', '', '(19/09/2025)', '15:00 (02/10/2025)', 'RW10', '', '', '', '', '', '', 1, 1, '2025-09-17', 2, 2, '2025-09-17 12:25:03', '2025-10-02 11:34:09', NULL, '2025-10-02'),
(64, 'PK-LUO', '', '12:33 (19/09/2025)', NULL, 'SA27', '', '', '', '', '', '', 1, 0, '2025-09-19', 2, 2, '2025-09-19 15:29:17', '2025-09-19 15:30:04', '2025-09-19', NULL),
(65, 'PK-GJV', '', '12:33 (19/09/2025)', NULL, 'SA23', '', '', '', '', '', '', 1, 0, '2025-09-19', 2, 2, '2025-09-19 15:33:36', '2025-09-20 00:15:02', '2025-09-19', NULL),
(66, 'PK-VVH', 'C 208', '19:22 (20/09/2025)', '15:44 (20/09/2025)', 'RW08', '', '', '', '', 'SUSI AIR', '', 1, 1, '2025-09-20', 2, 2, '2025-09-20 00:17:36', '2025-09-20 04:51:51', '2025-09-20', '2025-09-20'),
(67, 'T7-777', 'BBJ', '(03/10/2025)', '14:00 (03/10/2025)', 'B8', '', '', '', '', 'JIP', '', 1, 1, '2025-09-24', 2, 2, '2025-09-24 04:21:39', '2025-10-03 04:25:08', NULL, '2025-10-03'),
(68, 'PK-BVX', 'AVANTI', '12:00', '13:00 (03/10/2025)', 'RW11', '', '', '', '', 'SUSI AIR', '', 0, 0, '2025-10-03', 2, 2, '2025-10-03 04:04:41', '2025-10-03 04:05:14', NULL, '2025-10-03'),
(69, 'PK-VVM', 'C 208', '12:00 (03/10/2025)', NULL, 'RW11', '', '', '', '', 'SUSI AIR', '', 1, 0, '2025-10-03', 2, 2, '2025-10-03 04:05:48', '2025-10-03 04:18:08', '2025-10-03', NULL),
(70, 'PK-TEST', '', '15:55 (03/10/2025)', NULL, 'B5', '', '', '', '', '', '', 1, 0, '2025-10-03', 2, 2, '2025-10-03 04:15:03', '2025-10-03 04:18:08', '2025-10-03', NULL),
(71, 'PK-DBM', '', '12:33 (03/10/2025)', NULL, 'B4', '', '', '', '', '', '', 1, 0, '2025-10-03', 2, 2, '2025-10-03 04:16:36', '2025-10-03 04:18:08', '2025-10-03', NULL),
(72, 'PK-VVH', 'C 208', '12:44 (03/10/2025)', NULL, 'B9', '', '', '', '', 'SUSI AIR', '', 1, 0, '2025-10-03', 2, 2, '2025-10-03 04:24:59', '2025-10-20 10:36:20', NULL, NULL),
(73, 'PK-PAH', 'ATR 72', '12:33 (03/10/2025)', '19:40 (20/10/2025)', 'B1', '', '', '', '', 'PELITA', '', 1, 1, '2025-10-03', 2, 2, '2025-10-03 04:32:05', '2025-10-20 10:40:09', '2025-10-03', '2025-10-20'),
(74, 'PK-NMI', 'B 733', '(26/10/2025)', '13:00 (30/10/2025)', 'B1', '', '', '', '', 'AIRNESIA', '', 1, 1, '2025-10-26', 2, 2, '2025-10-26 06:03:23', '2025-10-30 07:28:27', NULL, '2025-10-30'),
(75, 'PK-NMI', 'B 733', ' (26/10/2025)', NULL, 'B11', '', '', '', '', 'AIRNESIA', '', 1, 0, '2025-10-26', 2, 2, '2025-10-26 06:09:44', '2025-10-26 11:02:57', NULL, NULL),
(76, 'PK-YSH', 'B 733', ' (26/10/2025)', NULL, 'B10', '', '', '', '', 'SUSI AIR', '', 1, 0, '2025-10-26', 2, 2, '2025-10-26 06:13:39', '2025-10-26 11:02:57', NULL, NULL),
(77, 'PK-KAS', 'CL 850', '(26/10/2025)', '15:00 (30/10/2025)', 'B2', '', '', '', '', 'JETSET', '', 1, 1, '2025-10-26', 2, 2, '2025-10-26 06:17:35', '2025-10-30 07:28:16', NULL, '2025-10-30'),
(78, 'PK-JRB', 'B 733', '12:33', '12:44 (26/10/2025)', 'A3', '', '', '', '', 'JAYAWIJAYA', '', 0, 0, '2025-10-26', 2, 2, '2025-10-26 06:22:59', '2025-10-26 06:34:11', NULL, '2025-10-26'),
(79, 'PK-BVX', 'AVANTI', ' (26/10/2025)', NULL, 'B8', '', '', '', '', 'SUSI AIR', '', 1, 0, '2025-10-26', 2, 2, '2025-10-26 06:25:20', '2025-10-26 11:02:57', NULL, NULL),
(80, 'PK-GFM', 'B 738', ' (26/10/2025)', NULL, 'B3', '', '', '', '', 'GARUDA', '', 1, 0, '2025-10-26', 2, 2, '2025-10-26 06:29:49', '2025-10-26 11:02:57', NULL, NULL),
(81, 'PK-GFA', 'B 738', '(26/10/2025)', '15:00 (30/10/2025)', 'A1', '', '', '', '', 'GARUDA', '', 1, 1, '2025-10-26', 2, 2, '2025-10-26 06:31:41', '2025-10-30 07:29:03', NULL, '2025-10-30'),
(82, 'PK-PAH', 'ATR 72', ' (26/10/2025)', NULL, 'A3', '', '', '', '', 'PELITA', '', 1, 0, '2025-10-26', 2, 2, '2025-10-26 10:05:37', '2025-10-26 11:02:57', NULL, NULL),
(83, 'PK-GFM', 'B 738', '12:22 (30/10/2025)', NULL, 'B2', '', '', '', '', 'GARUDA', '', 1, 0, '2025-10-30', 2, 2, '2025-10-30 07:29:32', '2025-11-04 07:39:07', '2025-10-30', NULL),
(84, 'PK-JRB', 'B 733', '12:00 (30/10/2025)', NULL, 'B13', '', '', '', '', 'JAYAWIJAYA', '', 1, 0, '2025-10-30', 2, 2, '2025-10-30 07:34:26', '2025-11-04 07:39:07', '2025-10-30', NULL),
(85, 'PK-YST', 'B 733', '12:23 (30/10/2025)', NULL, 'B12', '', '', '', '', 'TRIGANA', '', 1, 0, '2025-10-30', 2, 2, '2025-10-30 07:52:25', '2025-11-04 07:39:07', '2025-10-30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `airline_preferences`
--

CREATE TABLE `airline_preferences` (
  `id` int(11) NOT NULL,
  `airline_name` varchar(100) NOT NULL,
  `airline_category` enum('COMMERCIAL','CARGO','CHARTER') NOT NULL,
  `aircraft_type` varchar(50) DEFAULT NULL COMMENT 'NULL means applies to all aircraft types for this airline',
  `stand_name` varchar(10) NOT NULL,
  `priority_score` int(11) NOT NULL DEFAULT 1 COMMENT 'Higher score = higher priority (0–100)',
  `notes` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `airline_preferences`
--

INSERT INTO `airline_preferences` (`id`, `airline_name`, `airline_category`, `aircraft_type`, `stand_name`, `priority_score`, `notes`, `active`, `created_at`, `updated_at`) VALUES
(1, 'GARUDA', 'COMMERCIAL', NULL, 'B2', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(2, 'GARUDA', 'COMMERCIAL', NULL, 'B1', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(3, 'GARUDA', 'COMMERCIAL', NULL, 'B3', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(4, 'GARUDA', 'COMMERCIAL', NULL, 'A3', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(5, 'CITILINK', 'COMMERCIAL', NULL, 'B1', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(6, 'CITILINK', 'COMMERCIAL', NULL, 'B2', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(7, 'CITILINK', 'COMMERCIAL', NULL, 'A3', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(8, 'FLY JAYA', 'COMMERCIAL', NULL, 'B1', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(9, 'FLY JAYA', 'COMMERCIAL', NULL, 'B2', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(10, 'FLY JAYA', 'COMMERCIAL', NULL, 'A3', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(11, 'BATIK AIR', 'COMMERCIAL', NULL, 'A1', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(12, 'BATIK AIR', 'COMMERCIAL', NULL, 'A2', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(13, 'BATIK AIR', 'COMMERCIAL', NULL, 'A3', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(14, 'BATIK AIR', 'COMMERCIAL', NULL, 'B1', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(15, 'BATIK AIR', 'COMMERCIAL', NULL, 'B6', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(16, 'BATIK AIR', 'COMMERCIAL', NULL, 'B7', 75, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(17, 'BATIK AIR', 'COMMERCIAL', NULL, 'B8', 70, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(18, 'PELITA AIR', 'COMMERCIAL', NULL, 'A1', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(19, 'PELITA AIR', 'COMMERCIAL', NULL, 'A2', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(20, 'PELITA AIR', 'COMMERCIAL', NULL, 'A3', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(21, 'PELITA AIR', 'COMMERCIAL', NULL, 'B1', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(22, 'SUSI AIR', 'COMMERCIAL', NULL, 'A0', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(23, 'SUSI AIR', 'COMMERCIAL', NULL, 'B7', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(24, 'TRIGANA AIR', 'CARGO', NULL, 'B13', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(25, 'TRIGANA AIR', 'CARGO', NULL, 'B12', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(26, 'TRIGANA AIR', 'CARGO', NULL, 'B11', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(27, 'TRIGANA AIR', 'CARGO', NULL, 'B10', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(28, 'TRIGANA AIR', 'CARGO', NULL, 'B9', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(29, 'CITILINK CARGO', 'CARGO', NULL, 'B13', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(30, 'CITILINK CARGO', 'CARGO', NULL, 'B12', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(31, 'CITILINK CARGO', 'CARGO', NULL, 'B11', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(32, 'CITILINK CARGO', 'CARGO', NULL, 'B10', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(33, 'CITILINK CARGO', 'CARGO', NULL, 'B9', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(34, 'AIRNESIA', 'CARGO', NULL, 'B13', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(35, 'AIRNESIA', 'CARGO', NULL, 'B12', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(36, 'AIRNESIA', 'CARGO', NULL, 'B11', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(37, 'AIRNESIA', 'CARGO', NULL, 'B10', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(38, 'AIRNESIA', 'CARGO', NULL, 'B9', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(39, 'JAYA WIJAYA', 'CARGO', NULL, 'B13', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(40, 'JAYA WIJAYA', 'CARGO', NULL, 'B12', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(41, 'JAYA WIJAYA', 'CARGO', NULL, 'B11', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(42, 'JAYA WIJAYA', 'CARGO', NULL, 'B10', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(43, 'JAYA WIJAYA', 'CARGO', NULL, 'B9', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(44, 'B.B.N AIRLINES', 'CARGO', NULL, 'B13', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(45, 'B.B.N AIRLINES', 'CARGO', NULL, 'B12', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(46, 'B.B.N AIRLINES', 'CARGO', NULL, 'B11', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(47, 'B.B.N AIRLINES', 'CARGO', NULL, 'B10', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(48, 'B.B.N AIRLINES', 'CARGO', NULL, 'B9', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(49, 'TRI MG', 'CARGO', NULL, 'B13', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(50, 'TRI MG', 'CARGO', NULL, 'B12', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(51, 'TRI MG', 'CARGO', NULL, 'B11', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(52, 'TRI MG', 'CARGO', NULL, 'B10', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(53, 'TRI MG', 'CARGO', NULL, 'B9', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(54, 'JAS', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(55, 'JAS', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(56, 'JAS', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(57, 'JAS', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(58, 'JAS', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(59, 'TRAVIRA', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(60, 'TRAVIRA', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(61, 'TRAVIRA', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(62, 'TRAVIRA', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(63, 'TRAVIRA', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(64, 'AIRNESIA', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(65, 'AIRNESIA', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(66, 'AIRNESIA', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(67, 'AIRNESIA', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(68, 'AIRNESIA', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(69, 'JETSET', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(70, 'JETSET', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(71, 'JETSET', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(72, 'JETSET', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(73, 'JETSET', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(74, 'KARISMA', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(75, 'KARISMA', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(76, 'KARISMA', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(77, 'KARISMA', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(78, 'KARISMA', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(79, 'IAT', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(80, 'IAT', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(81, 'IAT', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(82, 'IAT', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(83, 'IAT', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(84, 'JIP', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(85, 'JIP', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(86, 'JIP', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(87, 'JIP', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(88, 'JIP', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(89, 'AFM', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(90, 'AFM', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(91, 'AFM', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(92, 'AFM', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(93, 'AFM', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(94, 'PELITA', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(95, 'PELITA', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(96, 'PELITA', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(97, 'PELITA', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(98, 'PELITA', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(99, 'GENESA', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(100, 'GENESA', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(101, 'GENESA', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(102, 'GENESA', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(103, 'GENESA', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(104, 'PREMI', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(105, 'PREMI', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(106, 'PREMI', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(107, 'PREMI', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(108, 'PREMI', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(109, 'PURAWISATA', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(110, 'PURAWISATA', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(111, 'PURAWISATA', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(112, 'PURAWISATA', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(113, 'PURAWISATA', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(114, 'PTN', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(115, 'PTN', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(116, 'PTN', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:32', '2025-10-24 09:15:32'),
(117, 'PTN', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(118, 'PTN', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(119, 'BIOMANTARA', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(120, 'BIOMANTARA', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(121, 'BIOMANTARA', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(122, 'BIOMANTARA', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(123, 'BIOMANTARA', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(124, 'SUBA AIR', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(125, 'SUBA AIR', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(126, 'SUBA AIR', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(127, 'SUBA AIR', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(128, 'SUBA AIR', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(129, 'AIR PASIFIC', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(130, 'AIR PASIFIC', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(131, 'AIR PASIFIC', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(132, 'AIR PASIFIC', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(133, 'AIR PASIFIC', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(134, 'DEPHUB', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(135, 'DEPHUB', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(136, 'DEPHUB', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(137, 'DEPHUB', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(138, 'DEPHUB', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(139, 'BGS', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(140, 'BGS', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(141, 'BGS', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(142, 'BGS', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(143, 'BGS', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(144, 'TRANSWISATA', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(145, 'TRANSWISATA', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(146, 'TRANSWISATA', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(147, 'TRANSWISATA', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(148, 'TRANSWISATA', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(149, 'GAPURA', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(150, 'GAPURA', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(151, 'GAPURA', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(152, 'GAPURA', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(153, 'GAPURA', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(154, 'AMM', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(155, 'AMM', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(156, 'AMM', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(157, 'AMM', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(158, 'AMM', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(159, 'B. G. S.', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(160, 'B. G. S.', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(161, 'B. G. S.', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(162, 'B. G. S.', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(163, 'B. G. S.', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(164, 'AIRFAST', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(165, 'AIRFAST', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(166, 'AIRFAST', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(167, 'AIRFAST', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(168, 'AIRFAST', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(169, 'TAS', 'CHARTER', NULL, 'B3', 100, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(170, 'TAS', 'CHARTER', NULL, 'B4', 95, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(171, 'TAS', 'CHARTER', NULL, 'B5', 90, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(172, 'TAS', 'CHARTER', NULL, 'B6', 85, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33'),
(173, 'TAS', 'CHARTER', NULL, 'B7', 80, '', 1, '2025-10-24 09:15:33', '2025-10-24 09:15:33');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action_type` varchar(50) NOT NULL COMMENT 'e.g., CREATE_MOVEMENT, UPDATE_USER',
  `target_table` varchar(50) NOT NULL,
  `target_id` bigint(20) UNSIGNED NOT NULL,
  `old_values` text DEFAULT NULL COMMENT 'JSON-encoded old data',
  `new_values` text NOT NULL COMMENT 'JSON-encoded new data',
  `action_timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action_type`, `target_table`, `target_id`, `old_values`, `new_values`, `action_timestamp`) VALUES
(1, 4, 'LOGIN_FAIL', 'users', 4, NULL, '{\"ip\":\"::1\"}', '2025-08-29 16:03:03'),
(2, 4, 'LOGIN_SUCCESS', 'users', 4, NULL, '', '2025-08-29 16:03:26'),
(3, 4, 'LOGOUT', 'users', 4, NULL, '', '2025-08-29 16:04:08'),
(4, 3, 'LOGIN_SUCCESS', 'users', 3, NULL, '', '2025-08-29 16:04:22'),
(5, 3, 'LOGOUT', 'users', 3, NULL, '', '2025-08-29 16:06:27'),
(6, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-29 16:06:35'),
(7, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-08-29 16:10:49'),
(8, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-29 16:10:59'),
(9, 2, 'LOGIN_FAIL', 'users', 2, NULL, '{\"ip\":\"::1\"}', '2025-08-29 16:11:39'),
(10, 2, 'LOGIN_FAIL', 'users', 2, NULL, '{\"ip\":\"::1\"}', '2025-08-29 16:11:46'),
(11, 2, 'LOGIN_FAIL', 'users', 2, NULL, '{\"ip\":\"::1\"}', '2025-08-29 16:11:53'),
(12, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-29 16:12:07'),
(13, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-08-29 16:16:46'),
(14, 4, 'LOGIN_SUCCESS', 'users', 4, NULL, '', '2025-08-29 16:16:57'),
(15, 4, 'LOGOUT', 'users', 4, NULL, '', '2025-08-29 16:17:43'),
(16, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-29 16:17:54'),
(17, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-08-29 16:18:38'),
(18, 3, 'LOGIN_SUCCESS', 'users', 3, NULL, '', '2025-08-29 16:18:53'),
(19, 3, 'LOGOUT', 'users', 3, NULL, '', '2025-08-29 16:19:06'),
(20, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-29 16:19:13'),
(21, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-08-29 16:20:03'),
(22, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-29 16:23:43'),
(23, 2, 'LOGIN_FAIL', 'users', 2, NULL, '{\"ip\":\"::1\"}', '2025-08-31 12:52:59'),
(24, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-31 12:53:16'),
(25, 2, 'CREATE_USER', 'users', 5, NULL, '{\"username\":\"syarifadriann\",\"email\":\"syarifadrian9@gmail.com\",\"role\":\"operator\",\"status\":\"active\"}', '2025-08-31 14:13:04'),
(26, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-31 14:33:59'),
(27, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-31 15:18:40'),
(28, 2, 'UPDATE_USER', 'users', 2, '{\"id\":2,\"username\":\"admingpt\",\"password_hash\":\"$2y$10$.AQ5AXg5eXZSET9DSC8BVOKuXA5o.xBNLDk4qOREy85P2FOofxvEC\",\"role\":\"admin\",\"status\":\"active\",\"last_login_at\":null,\"must_change_password\":0,\"full_name\":null,\"email\":\"admin@amc.local\",\"is_active\":1,\"created_at\":\"2025-08-29 22:39:44\",\"updated_at\":\"2025-08-29 22:39:44\"}', '{\"id\":\"2\",\"csrf_token\":\"a4743313cbc9001ca0922a67be6bd0c1a094a8a938026b839ca47d8c10dbcd4e\",\"full_name\":\"admin\",\"username\":\"admingpt\",\"email\":\"admin@amc.local\",\"role\":\"admin\",\"status\":\"active\",\"password\":\"uhuy\",\"action\":\"update\"}', '2025-08-31 15:19:27'),
(29, 2, 'UPDATE_USER', 'users', 3, '{\"id\":3,\"username\":\"operatorgpt\",\"password_hash\":\"$2y$10$DY49yWh0aN5.eR23wk\\/PwezkLf8w3tAiimG2.bvu4wdPKn9nH4VhW\",\"role\":\"operator\",\"status\":\"active\",\"last_login_at\":null,\"must_change_password\":0,\"full_name\":null,\"email\":\"operator@amc.local\",\"is_active\":1,\"created_at\":\"2025-08-29 22:39:44\",\"updated_at\":\"2025-08-29 22:39:44\"}', '{\"id\":\"3\",\"csrf_token\":\"a4743313cbc9001ca0922a67be6bd0c1a094a8a938026b839ca47d8c10dbcd4e\",\"full_name\":\"operator\",\"username\":\"operatorgpt\",\"email\":\"operator@amc.local\",\"role\":\"operator\",\"status\":\"active\",\"password\":\"\",\"action\":\"update\"}', '2025-08-31 15:19:38'),
(30, 2, 'CREATE_USER', 'users', 6, NULL, '{\"username\":\"amc\",\"email\":\"amchlp@gmail.com\",\"role\":\"operator\",\"status\":\"active\"}', '2025-08-31 15:20:21'),
(31, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-08-31 15:20:26'),
(32, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-08-31 15:20:32'),
(33, 6, 'LOGOUT', 'users', 6, NULL, '', '2025-08-31 15:21:03'),
(34, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-31 15:21:10'),
(35, 2, 'SET_STATUS', 'users', 5, NULL, '{\"status\":\"suspended\"}', '2025-08-31 15:22:15'),
(36, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-08-31 15:22:19'),
(37, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-08-31 15:22:25'),
(38, 5, 'LOGOUT', 'users', 5, NULL, '', '2025-08-31 15:22:41'),
(39, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-31 15:22:48'),
(40, 2, 'RESET_PASSWORD', 'users', 6, NULL, '{\"reset_by\":2}', '2025-08-31 15:23:21'),
(41, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-08-31 15:23:37'),
(42, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-08-31 15:23:44'),
(43, 6, 'LOGOUT', 'users', 6, NULL, '', '2025-08-31 15:29:44'),
(44, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-31 15:29:52'),
(45, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-31 15:30:27'),
(46, 2, 'RESET_PASSWORD', 'users', 6, NULL, '{\"reset_by\":2}', '2025-08-31 15:31:23'),
(47, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-08-31 15:31:30'),
(48, 5, 'LOGIN_FAIL', 'users', 5, NULL, '{\"ip\":\"::1\"}', '2025-08-31 15:31:36'),
(49, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-08-31 15:31:44'),
(50, 6, 'LOGOUT', 'users', 6, NULL, '', '2025-08-31 15:32:25'),
(51, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-31 15:32:32'),
(52, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-31 15:34:22'),
(53, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-31 15:38:33'),
(54, 2, 'SET_STATUS', 'users', 5, NULL, '{\"status\":\"active\"}', '2025-08-31 15:39:05'),
(55, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-08-31 15:39:09'),
(56, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-08-31 15:39:16'),
(57, 5, 'LOGOUT', 'users', 5, NULL, '', '2025-08-31 15:39:21'),
(58, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-31 15:39:31'),
(59, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-08-31 16:00:12'),
(60, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-03 04:51:08'),
(61, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-04 13:41:11'),
(62, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-05 02:04:12'),
(63, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-05 06:56:51'),
(64, 2, 'CREATE_SNAPSHOT', 'daily_snapshots', 1, NULL, '{\"date\":\"2025-09-05\"}', '2025-09-05 08:04:12'),
(65, 2, 'DELETE_SNAPSHOT', 'daily_snapshots', 1, '{\"snapshot_date\":\"2025-09-05\"}', '', '2025-09-05 08:12:44'),
(66, 2, 'CREATE_SNAPSHOT', 'daily_snapshots', 2, NULL, '{\"date\":\"2025-09-05\"}', '2025-09-05 08:14:20'),
(67, 2, 'DELETE_SNAPSHOT', 'daily_snapshots', 2, '{\"snapshot_date\":\"2025-09-05\"}', '', '2025-09-05 08:45:04'),
(68, 2, 'CREATE_SNAPSHOT', 'daily_snapshots', 3, NULL, '{\"date\":\"2025-09-05\"}', '2025-09-05 08:45:11'),
(69, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-07 06:33:12'),
(70, 2, 'CREATE_SNAPSHOT', 'daily_snapshots', 4, NULL, '{\"date\":\"2025-09-07\"}', '2025-09-07 07:06:29'),
(71, 2, 'UPDATE_USER', 'users', 5, '{\"id\":5,\"username\":\"syarifadriann\",\"password_hash\":\"$argon2id$v=19$m=65536,t=4,p=1$ZXNxTG13SHB3amxRRmVWVw$Pcl0y73J9ZQ+o3oYUNI7Ev55WZGyds9BL8KjX56yIZQ\",\"role\":\"operator\",\"status\":\"active\",\"last_login_at\":null,\"must_change_password\":1,\"full_name\":\"syarif adrian mangaraja lubis\",\"email\":\"syarifadrian9@gmail.com\",\"is_active\":1,\"created_at\":\"2025-08-31 21:13:04\",\"updated_at\":\"2025-08-31 22:39:05\"}', '{\"id\":\"5\",\"csrf_token\":\"115ac200c47fe3a13a2f39123035719d0af08bbbaf16b14f322432667b7a6990\",\"full_name\":\"syarif adrian mangaraja lubis\",\"username\":\"syarifadriann\",\"email\":\"syarifadrian9@gmail.com\",\"role\":\"viewer\",\"status\":\"active\",\"password\":\"Admin@123\",\"action\":\"update\"}', '2025-09-07 07:39:30'),
(72, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-09-07 07:39:34'),
(73, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-07 07:39:41'),
(74, 5, 'LOGOUT', 'users', 5, NULL, '', '2025-09-07 07:40:20'),
(75, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-07 07:40:25'),
(76, 2, 'DELETE_SNAPSHOT', 'daily_snapshots', 4, '{\"snapshot_date\":\"2025-09-07\"}', '', '2025-09-07 07:41:04'),
(77, 2, 'DELETE_SNAPSHOT', 'daily_snapshots', 3, '{\"snapshot_date\":\"2025-09-05\"}', '', '2025-09-07 07:41:08'),
(78, 2, 'CREATE_SNAPSHOT', 'daily_snapshots', 5, NULL, '{\"date\":\"2025-09-07\"}', '2025-09-07 07:41:15'),
(79, 2, 'CREATE_SNAPSHOT', 'daily_snapshots', 6, NULL, '{\"date\":\"2025-09-05\"}', '2025-09-07 07:43:49'),
(80, 2, 'DELETE_SNAPSHOT', 'daily_snapshots', 5, '{\"snapshot_date\":\"2025-09-07\"}', '', '2025-09-07 07:44:47'),
(81, 2, 'DELETE_SNAPSHOT', 'daily_snapshots', 6, '{\"snapshot_date\":\"2025-09-05\"}', '', '2025-09-07 07:44:51'),
(82, 2, 'CREATE_SNAPSHOT', 'daily_snapshots', 7, NULL, '{\"date\":\"2025-09-07\"}', '2025-09-07 07:44:52'),
(83, 2, 'CREATE_SNAPSHOT', 'daily_snapshots', 8, NULL, '{\"date\":\"2025-09-05\"}', '2025-09-07 07:45:12'),
(84, 2, 'UPSERT_SNAPSHOT', 'daily_snapshots', 0, NULL, '{\"date\":\"2025-09-07\"}', '2025-09-07 08:00:36'),
(85, 2, 'UPSERT_SNAPSHOT', 'daily_snapshots', 0, NULL, '{\"date\":\"2025-09-05\"}', '2025-09-07 08:00:42'),
(86, 2, 'UPSERT_SNAPSHOT', 'daily_snapshots', 0, NULL, '{\"date\":\"2025-09-05\"}', '2025-09-07 08:13:37'),
(87, 2, 'UPSERT_SNAPSHOT', 'daily_snapshots', 0, NULL, '{\"date\":\"2025-09-05\"}', '2025-09-07 08:15:10'),
(88, 2, 'UPSERT_SNAPSHOT', 'daily_snapshots', 0, NULL, '{\"date\":\"2025-09-07\"}', '2025-09-07 08:15:37'),
(89, 2, 'UPSERT_SNAPSHOT', 'daily_snapshots', 0, NULL, '{\"date\":\"2025-09-07\"}', '2025-09-07 08:23:38'),
(90, 2, 'UPSERT_SNAPSHOT', 'daily_snapshots', 0, NULL, '{\"date\":\"2025-09-05\"}', '2025-09-07 08:23:44'),
(91, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-07 09:21:15'),
(92, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-15 05:25:37'),
(93, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-15 07:28:28'),
(94, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-15 08:35:33'),
(95, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-15 08:38:57'),
(96, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-17 07:02:19'),
(97, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-17 07:45:09'),
(98, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-17 08:21:36'),
(99, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-17 12:24:57'),
(100, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 11:38:00'),
(101, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 11:39:11'),
(102, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 11:42:50'),
(103, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 11:47:43'),
(104, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 11:51:28'),
(105, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 11:52:06'),
(106, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 11:54:26'),
(107, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 11:55:21'),
(108, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 11:55:56'),
(109, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 11:57:21'),
(110, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 11:58:51'),
(111, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:00:30'),
(112, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:01:36'),
(113, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:02:41'),
(114, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:03:28'),
(115, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:05:23'),
(116, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:07:25'),
(117, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:08:02'),
(118, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:08:53'),
(119, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:09:39'),
(120, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:16:32'),
(121, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:20:54'),
(122, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:23:01'),
(123, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:23:55'),
(124, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:25:05'),
(125, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:26:03'),
(126, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:26:46'),
(127, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:27:45'),
(128, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:28:27'),
(129, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:29:11'),
(130, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:30:02'),
(131, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:30:47'),
(132, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:31:35'),
(133, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:32:24'),
(134, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:33:32'),
(135, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:34:08'),
(136, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:35:08'),
(137, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:36:30'),
(138, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:41:48'),
(139, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:42:31'),
(140, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:43:18'),
(141, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:44:16'),
(142, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:44:55'),
(143, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:45:34'),
(144, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:46:14'),
(145, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:46:55'),
(146, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:47:35'),
(147, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:48:29'),
(148, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:49:16'),
(149, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:49:59'),
(150, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:51:07'),
(151, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:51:39'),
(152, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:52:41'),
(153, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:55:28'),
(154, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:56:16'),
(155, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:58:35'),
(156, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 12:59:50'),
(157, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 13:00:32'),
(158, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 13:01:13'),
(159, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 13:01:54'),
(160, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 13:02:24'),
(161, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 13:03:06'),
(162, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-18 13:03:45'),
(163, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 13:03:48'),
(164, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 13:04:34'),
(165, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 13:05:12'),
(166, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-18 13:12:23'),
(167, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-19 03:52:31'),
(168, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-19 15:04:38'),
(169, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-09-19 15:04:56'),
(170, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-19 15:04:58'),
(171, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-19 15:10:09'),
(172, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-20 00:15:02'),
(173, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-20 01:46:53'),
(174, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-20 04:50:14'),
(175, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-20 06:19:03'),
(176, 2, 'UPSERT_SNAPSHOT', 'daily_snapshots', 0, NULL, '{\"date\":\"2025-09-20\"}', '2025-09-20 06:52:58'),
(177, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-09-20 08:26:39'),
(178, 3, 'LOGIN_FAIL', 'users', 3, NULL, '{\"ip\":\"::1\"}', '2025-09-20 08:27:12'),
(179, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-20 08:28:17'),
(180, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-20 12:39:04'),
(181, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-20 13:07:44'),
(182, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-09-20 15:04:18'),
(183, 7, 'LOGIN_SUCCESS', 'users', 7, NULL, '', '2025-09-20 15:04:27'),
(184, 7, 'LOGOUT', 'users', 7, NULL, '', '2025-09-20 15:05:55'),
(185, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-20 15:06:01'),
(186, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-09-20 15:13:49'),
(187, 9, 'LOGIN_FAIL', 'users', 9, NULL, '{\"ip\":\"::1\"}', '2025-09-20 15:13:58'),
(188, 9, 'LOGIN_FAIL', 'users', 9, NULL, '{\"ip\":\"::1\"}', '2025-09-20 15:14:09'),
(189, 9, 'LOGIN_SUCCESS', 'users', 9, NULL, '', '2025-09-20 15:14:15'),
(190, 9, 'LOGOUT', 'users', 9, NULL, '', '2025-09-20 15:16:39'),
(191, 7, 'LOGIN_FAIL', 'users', 7, NULL, '{\"ip\":\"::1\"}', '2025-09-20 15:16:44'),
(192, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-20 15:16:53'),
(193, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-09-20 15:18:02'),
(194, 7, 'LOGIN_SUCCESS', 'users', 7, NULL, '', '2025-09-20 15:18:10'),
(195, 7, 'LOGOUT', 'users', 7, NULL, '', '2025-09-20 15:18:31'),
(196, 9, 'LOGIN_SUCCESS', 'users', 9, NULL, '', '2025-09-20 15:18:37'),
(197, 5, 'LOGIN_SUCCESS', 'users', 5, NULL, '', '2025-09-24 04:19:12'),
(198, 5, 'LOGOUT', 'users', 5, NULL, '', '2025-09-24 04:20:45'),
(199, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-24 04:21:06'),
(200, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-09-24 04:21:07'),
(201, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-02 10:50:11'),
(202, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-10-02 10:59:20'),
(203, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-02 11:08:33'),
(204, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-03 03:58:28'),
(205, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-10-03 04:09:22'),
(206, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-03 04:09:32'),
(207, 2, 'CREATE_USER', 'users', 10, NULL, '{\"username\":\"ATC\",\"email\":\"atc@gmail.com\",\"role\":\"viewer\",\"status\":\"active\"}', '2025-10-03 04:10:57'),
(208, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-10-03 04:11:21'),
(209, 10, 'LOGIN_SUCCESS', 'users', 10, NULL, '', '2025-10-03 04:11:29'),
(210, 10, 'LOGOUT', 'users', 10, NULL, '', '2025-10-03 04:12:29'),
(211, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-03 04:12:33'),
(212, 2, 'UPSERT_SNAPSHOT', 'daily_snapshots', 0, NULL, '{\"date\":\"2025-10-03\"}', '2025-10-03 04:12:52'),
(213, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-20 10:36:20'),
(214, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-20 10:39:11'),
(215, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-24 18:27:53'),
(216, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-10-24 18:28:29'),
(217, 10, 'LOGIN_SUCCESS', 'users', 10, NULL, '', '2025-10-24 18:30:27'),
(218, 10, 'LOGIN_SUCCESS', 'users', 10, NULL, '', '2025-10-24 18:31:22'),
(219, 10, 'LOGIN_SUCCESS', 'users', 10, NULL, '', '2025-10-24 18:39:16'),
(220, 10, 'LOGIN_SUCCESS', 'users', 10, NULL, '', '2025-10-24 18:40:34'),
(221, 10, 'LOGIN_SUCCESS', 'users', 10, NULL, '', '2025-10-24 18:40:44'),
(222, 10, 'LOGIN_SUCCESS', 'users', 10, NULL, '', '2025-10-24 18:43:42'),
(223, 10, 'LOGIN_SUCCESS', 'users', 10, NULL, '', '2025-10-24 18:47:10'),
(224, 10, 'LOGIN_SUCCESS', 'users', 10, NULL, '', '2025-10-24 18:47:27'),
(225, 10, 'LOGIN_SUCCESS', 'users', 10, NULL, '', '2025-10-24 18:49:39'),
(226, 1, 'LOGIN_SUCCESS', 'users', 1, NULL, '', '2025-10-25 12:57:25'),
(227, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 12:57:29'),
(228, 1, 'LOGIN_SUCCESS', 'users', 1, NULL, '', '2025-10-25 12:57:38'),
(229, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 12:58:11'),
(230, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 12:58:19'),
(231, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 13:13:06'),
(232, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 13:13:54'),
(233, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 13:14:30'),
(234, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 13:14:48'),
(235, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 13:15:13'),
(236, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 13:15:40'),
(237, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 13:46:46'),
(238, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 13:47:27'),
(239, 6, 'LOGOUT', 'users', 6, NULL, '', '2025-10-25 13:53:46'),
(240, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-25 13:53:48'),
(241, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 14:15:02'),
(242, 6, 'LOGIN_SUCCESS', 'users', 6, NULL, '', '2025-10-25 14:32:17'),
(243, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-26 05:59:48'),
(244, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-26 06:35:43'),
(245, 11, 'LOGIN_SUCCESS', 'users', 11, NULL, '', '2025-10-26 06:50:30'),
(246, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-27 07:14:16'),
(247, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-28 07:32:23'),
(248, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-30 07:22:54'),
(249, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-30 12:02:50'),
(250, 2, 'LOGOUT', 'users', 2, NULL, '', '2025-10-30 12:26:51'),
(251, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-10-30 12:31:10'),
(252, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-11-04 07:39:07'),
(253, 2, 'LOGIN_SUCCESS', 'users', 2, NULL, '', '2025-11-05 06:42:31');

-- --------------------------------------------------------

--
-- Table structure for table `daily_snapshots`
--

CREATE TABLE `daily_snapshots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `snapshot_date` date NOT NULL,
  `snapshot_data` longtext NOT NULL COMMENT 'JSON-encoded snapshot data',
  `created_by_user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `daily_snapshots`
--

INSERT INTO `daily_snapshots` (`id`, `snapshot_date`, `snapshot_data`, `created_by_user_id`, `created_at`) VALUES
(7, '2025-09-07', '{\"staff_roster\":[],\"movements\":[{\"id\":60,\"registration\":\"PK-GJV\",\"aircraft_type\":\"ATR 72\",\"on_block_time\":\"02:04 (07\\/09\\/2025)\",\"off_block_time\":null,\"parking_stand\":\"WR01\",\"from_location\":\"WAHH\",\"to_location\":\"\",\"flight_no_arr\":\"QG 1101\",\"flight_no_dep\":\"\",\"operator_airline\":\"CITILINK\",\"remarks\":\"\",\"is_ron\":1,\"ron_complete\":0,\"movement_date\":\"2025-09-07\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-09-07 14:05:45\",\"updated_at\":\"2025-09-07 15:14:53\",\"on_block_date\":\"2025-09-07\",\"off_block_date\":null,\"category\":\"commercial\",\"aircraft_operator\":\"CITILINK\"}],\"ron_data\":[{\"id\":60,\"registration\":\"PK-GJV\",\"aircraft_type\":\"ATR 72\",\"on_block_time\":\"02:04 (07\\/09\\/2025)\",\"off_block_time\":null,\"parking_stand\":\"WR01\",\"from_location\":\"WAHH\",\"to_location\":\"\",\"flight_no_arr\":\"QG 1101\",\"flight_no_dep\":\"\",\"operator_airline\":\"CITILINK\",\"remarks\":\"\",\"is_ron\":1,\"ron_complete\":0,\"movement_date\":\"2025-09-07\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-09-07 14:05:45\",\"updated_at\":\"2025-09-07 15:14:53\",\"on_block_date\":\"2025-09-07\",\"off_block_date\":null,\"category\":\"commercial\"}],\"daily_metrics\":{\"total_arrivals\":1,\"total_departures\":0,\"new_ron\":1,\"active_ron\":18,\"hourly_movements\":[{\"time_range\":\"02:00-03:59\",\"Arrivals\":\"1\",\"Departures\":\"0\"}],\"snapshot_generated_at\":\"2025-09-07 10:23:38\"}}', 2, '2025-09-07 07:44:52'),
(8, '2025-09-05', '{\"staff_roster\":[{\"id\":13,\"roster_date\":\"2025-09-05\",\"shift\":\"Day\",\"updated_by_user_id\":1,\"updated_at\":\"2025-09-05 15:01:29\",\"aerodrome_code\":\"WIHH\",\"day_shift_staff_1\":\"John Doe\",\"day_shift_staff_2\":\"Jane Smith\",\"day_shift_staff_3\":\"Operator One\",\"night_shift_staff_1\":\"Peter Jones\",\"night_shift_staff_2\":\"Mary Williams\",\"night_shift_staff_3\":\"Operator Two\"}],\"movements\":[{\"id\":57,\"registration\":\"PK-GAB\",\"aircraft_type\":\"A320\",\"on_block_time\":\"08:00\",\"off_block_time\":\"09:00\",\"parking_stand\":\"A1\",\"from_location\":\"SIN\",\"to_location\":\"CGK\",\"flight_no_arr\":\"GA837\",\"flight_no_dep\":\"GA838\",\"operator_airline\":\"Garuda Indonesia\",\"remarks\":null,\"is_ron\":0,\"ron_complete\":0,\"movement_date\":\"2025-09-05\",\"user_id_created\":1,\"user_id_updated\":2,\"created_at\":\"2025-09-05 15:01:29\",\"updated_at\":\"2025-09-05 16:33:54\",\"on_block_date\":\"2025-09-05\",\"off_block_date\":\"2025-09-05\",\"category\":null,\"aircraft_operator\":null},{\"id\":58,\"registration\":\"PK-LOH\",\"aircraft_type\":\"B738\",\"on_block_time\":\"08:30\",\"off_block_time\":\"0930\",\"parking_stand\":\"B2\",\"from_location\":\"KUL\",\"to_location\":\"CGK\",\"flight_no_arr\":\"ID7283\",\"flight_no_dep\":\"ID7284\",\"operator_airline\":\"Batik Air\",\"remarks\":null,\"is_ron\":0,\"ron_complete\":0,\"movement_date\":\"2025-09-05\",\"user_id_created\":1,\"user_id_updated\":2,\"created_at\":\"2025-09-05 15:01:29\",\"updated_at\":\"2025-09-05 16:33:54\",\"on_block_date\":\"2025-09-05\",\"off_block_date\":\"2025-09-05\",\"category\":null,\"aircraft_operator\":null},{\"id\":59,\"registration\":\"9M-LCA\",\"aircraft_type\":\"A330\",\"on_block_time\":\"10:00 (05\\/09\\/2025)\",\"off_block_time\":null,\"parking_stand\":\"C3\",\"from_location\":\"JED\",\"to_location\":\"CGK\",\"flight_no_arr\":\"OD901\",\"flight_no_dep\":\"\",\"operator_airline\":\"Malindo Air\",\"remarks\":null,\"is_ron\":1,\"ron_complete\":0,\"movement_date\":\"2025-09-05\",\"user_id_created\":1,\"user_id_updated\":2,\"created_at\":\"2025-09-05 15:01:29\",\"updated_at\":\"2025-09-07 13:33:12\",\"on_block_date\":\"2025-09-05\",\"off_block_date\":null,\"category\":null,\"aircraft_operator\":null},{\"id\":53,\"registration\":\"VP-CLL\",\"aircraft_type\":\"EMB 135\",\"on_block_time\":\"12:22 (05\\/09\\/2025)\",\"off_block_time\":\"\",\"parking_stand\":\"B1\",\"from_location\":\"\",\"to_location\":\"\",\"flight_no_arr\":\"\",\"flight_no_dep\":\"\",\"operator_airline\":\"\",\"remarks\":\"\",\"is_ron\":1,\"ron_complete\":0,\"movement_date\":\"2025-09-05\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-09-05 09:28:47\",\"updated_at\":\"2025-09-05 15:14:10\",\"on_block_date\":null,\"off_block_date\":null,\"category\":\"charter\",\"aircraft_operator\":\"\"},{\"id\":55,\"registration\":\"PK-LUO\",\"aircraft_type\":\"A 320\",\"on_block_time\":\"12:33 (05\\/09\\/2025)\",\"off_block_time\":null,\"parking_stand\":\"SA15\",\"from_location\":\"WARR\",\"to_location\":\"WARR\",\"flight_no_arr\":\"ID 7510\",\"flight_no_dep\":\"ID 7515\",\"operator_airline\":\"BATIK AIR\",\"remarks\":\"\",\"is_ron\":1,\"ron_complete\":0,\"movement_date\":\"2025-09-05\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-09-05 13:17:04\",\"updated_at\":\"2025-09-07 13:33:12\",\"on_block_date\":\"2025-09-05\",\"off_block_date\":null,\"category\":\"commercial\",\"aircraft_operator\":\"BATIK AIR\"},{\"id\":56,\"registration\":\"PK-BKU\",\"aircraft_type\":\"A 320\",\"on_block_time\":\"13:45 (05\\/09\\/2025)\",\"off_block_time\":null,\"parking_stand\":\"SA16\",\"from_location\":\"WARR\",\"to_location\":\"WARR\",\"flight_no_arr\":\"ID 7514\",\"flight_no_dep\":\"ID 7515\",\"operator_airline\":\"BATIK AIR\",\"remarks\":\"\",\"is_ron\":1,\"ron_complete\":0,\"movement_date\":\"2025-09-05\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-09-05 13:22:01\",\"updated_at\":\"2025-09-07 13:33:12\",\"on_block_date\":\"2025-09-05\",\"off_block_date\":null,\"category\":\"commercial\",\"aircraft_operator\":\"BATIK AIR\"},{\"id\":54,\"registration\":\"t7-x14\",\"aircraft_type\":\"G VI\",\"on_block_time\":\"13:5 (05\\/09\\/2025)\",\"off_block_time\":null,\"parking_stand\":\"A1\",\"from_location\":\"\",\"to_location\":\"\",\"flight_no_arr\":\"\",\"flight_no_dep\":\"\",\"operator_airline\":\"\",\"remarks\":\"\",\"is_ron\":1,\"ron_complete\":0,\"movement_date\":\"2025-09-05\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-09-05 13:12:33\",\"updated_at\":\"2025-09-07 13:33:12\",\"on_block_date\":null,\"off_block_date\":null,\"category\":\"charter\",\"aircraft_operator\":\"\"}],\"ron_data\":[{\"id\":54,\"registration\":\"t7-x14\",\"aircraft_type\":\"G VI\",\"on_block_time\":\"13:5 (05\\/09\\/2025)\",\"off_block_time\":null,\"parking_stand\":\"A1\",\"from_location\":\"\",\"to_location\":\"\",\"flight_no_arr\":\"\",\"flight_no_dep\":\"\",\"operator_airline\":\"\",\"remarks\":\"\",\"is_ron\":1,\"ron_complete\":0,\"movement_date\":\"2025-09-05\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-09-05 13:12:33\",\"updated_at\":\"2025-09-07 13:33:12\",\"on_block_date\":null,\"off_block_date\":null,\"category\":\"charter\"},{\"id\":53,\"registration\":\"VP-CLL\",\"aircraft_type\":\"EMB 135\",\"on_block_time\":\"12:22 (05\\/09\\/2025)\",\"off_block_time\":\"\",\"parking_stand\":\"B1\",\"from_location\":\"\",\"to_location\":\"\",\"flight_no_arr\":\"\",\"flight_no_dep\":\"\",\"operator_airline\":\"\",\"remarks\":\"\",\"is_ron\":1,\"ron_complete\":0,\"movement_date\":\"2025-09-05\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-09-05 09:28:47\",\"updated_at\":\"2025-09-05 15:14:10\",\"on_block_date\":null,\"off_block_date\":null,\"category\":\"charter\"},{\"id\":59,\"registration\":\"9M-LCA\",\"aircraft_type\":\"A330\",\"on_block_time\":\"10:00 (05\\/09\\/2025)\",\"off_block_time\":null,\"parking_stand\":\"C3\",\"from_location\":\"JED\",\"to_location\":\"CGK\",\"flight_no_arr\":\"OD901\",\"flight_no_dep\":\"\",\"operator_airline\":\"Malindo Air\",\"remarks\":null,\"is_ron\":1,\"ron_complete\":0,\"movement_date\":\"2025-09-05\",\"user_id_created\":1,\"user_id_updated\":2,\"created_at\":\"2025-09-05 15:01:29\",\"updated_at\":\"2025-09-07 13:33:12\",\"on_block_date\":\"2025-09-05\",\"off_block_date\":null,\"category\":null},{\"id\":55,\"registration\":\"PK-LUO\",\"aircraft_type\":\"A 320\",\"on_block_time\":\"12:33 (05\\/09\\/2025)\",\"off_block_time\":null,\"parking_stand\":\"SA15\",\"from_location\":\"WARR\",\"to_location\":\"WARR\",\"flight_no_arr\":\"ID 7510\",\"flight_no_dep\":\"ID 7515\",\"operator_airline\":\"BATIK AIR\",\"remarks\":\"\",\"is_ron\":1,\"ron_complete\":0,\"movement_date\":\"2025-09-05\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-09-05 13:17:04\",\"updated_at\":\"2025-09-07 13:33:12\",\"on_block_date\":\"2025-09-05\",\"off_block_date\":null,\"category\":\"commercial\"},{\"id\":56,\"registration\":\"PK-BKU\",\"aircraft_type\":\"A 320\",\"on_block_time\":\"13:45 (05\\/09\\/2025)\",\"off_block_time\":null,\"parking_stand\":\"SA16\",\"from_location\":\"WARR\",\"to_location\":\"WARR\",\"flight_no_arr\":\"ID 7514\",\"flight_no_dep\":\"ID 7515\",\"operator_airline\":\"BATIK AIR\",\"remarks\":\"\",\"is_ron\":1,\"ron_complete\":0,\"movement_date\":\"2025-09-05\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-09-05 13:22:01\",\"updated_at\":\"2025-09-07 13:33:12\",\"on_block_date\":\"2025-09-05\",\"off_block_date\":null,\"category\":\"commercial\"}],\"daily_metrics\":{\"total_arrivals\":6,\"total_departures\":2,\"new_ron\":5,\"active_ron\":18,\"hourly_movements\":[{\"time_range\":\"08:00-09:59\",\"Arrivals\":\"2\",\"Departures\":\"2\"},{\"time_range\":\"10:00-11:59\",\"Arrivals\":\"0\",\"Departures\":\"0\"},{\"time_range\":\"12:00-13:59\",\"Arrivals\":\"4\",\"Departures\":\"1\"}],\"snapshot_generated_at\":\"2025-09-07 10:23:44\"}}', 2, '2025-09-07 07:45:12'),
(16, '2025-09-20', '{\"staff_roster\":[{\"id\":15,\"roster_date\":\"2025-09-20\",\"shift\":\"\",\"updated_by_user_id\":2,\"updated_at\":\"2025-09-20 13:52:13\",\"aerodrome_code\":\"WIHH\",\"day_shift_staff_1\":\"ashiap1\",\"day_shift_staff_2\":\"ashiap2\",\"day_shift_staff_3\":\"ashiap3\",\"night_shift_staff_1\":\"aha1\",\"night_shift_staff_2\":\"aha2\",\"night_shift_staff_3\":\"aha3\"}],\"movements\":[{\"id\":66,\"registration\":\"PK-VVH\",\"aircraft_type\":\"C 208\",\"on_block_time\":\"19:22 (20\\/09\\/2025)\",\"off_block_time\":\"15:44 (20\\/09\\/2025)\",\"parking_stand\":\"RW08\",\"from_location\":\"\",\"to_location\":\"\",\"flight_no_arr\":\"\",\"flight_no_dep\":\"\",\"operator_airline\":\"SUSI AIR\",\"remarks\":\"\",\"is_ron\":1,\"ron_complete\":1,\"movement_date\":\"2025-09-20\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-09-20 07:17:36\",\"updated_at\":\"2025-09-20 11:51:51\",\"on_block_date\":\"2025-09-20\",\"off_block_date\":\"2025-09-20\",\"category\":\"commercial\",\"aircraft_operator\":\"SUSI AIR\"}],\"ron_data\":[{\"id\":66,\"registration\":\"PK-VVH\",\"aircraft_type\":\"C 208\",\"on_block_time\":\"19:22 (20\\/09\\/2025)\",\"off_block_time\":\"15:44 (20\\/09\\/2025)\",\"parking_stand\":\"RW08\",\"from_location\":\"\",\"to_location\":\"\",\"flight_no_arr\":\"\",\"flight_no_dep\":\"\",\"operator_airline\":\"SUSI AIR\",\"remarks\":\"\",\"is_ron\":1,\"ron_complete\":1,\"movement_date\":\"2025-09-20\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-09-20 07:17:36\",\"updated_at\":\"2025-09-20 11:51:51\",\"on_block_date\":\"2025-09-20\",\"off_block_date\":\"2025-09-20\",\"category\":\"commercial\"}],\"daily_metrics\":{\"total_arrivals\":1,\"total_departures\":1,\"new_ron\":1,\"active_ron\":23,\"hourly_movements\":[{\"time_range\":\"18:00-19:59\",\"Arrivals\":\"1\",\"Departures\":\"1\"}],\"movements_by_category\":[{\"category\":\"commercial\",\"arrivals\":\"1\",\"departures\":\"1\"}],\"ron_count\":1,\"apron_status\":{\"total\":83,\"available\":61,\"occupied\":22,\"ron\":22},\"snapshot_generated_at\":\"2025-09-20 08:52:58\"}}', 2, '2025-09-20 06:52:58'),
(17, '2025-10-03', '{\"staff_roster\":[],\"movements\":[{\"id\":69,\"registration\":\"PK-VVM\",\"aircraft_type\":\"C 208\",\"on_block_time\":\"12:00\",\"off_block_time\":null,\"parking_stand\":\"RW11\",\"from_location\":\"\",\"to_location\":\"\",\"flight_no_arr\":\"\",\"flight_no_dep\":\"\",\"operator_airline\":\"SUSI AIR\",\"remarks\":\"\",\"is_ron\":0,\"ron_complete\":0,\"movement_date\":\"2025-10-03\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-10-03 11:05:48\",\"updated_at\":\"2025-10-03 11:05:48\",\"on_block_date\":\"2025-10-03\",\"off_block_date\":null,\"category\":\"commercial\",\"aircraft_operator\":\"SUSI AIR\"},{\"id\":68,\"registration\":\"PK-BVX\",\"aircraft_type\":\"AVANTI\",\"on_block_time\":\"12:00\",\"off_block_time\":\"13:00 (03\\/10\\/2025)\",\"parking_stand\":\"RW11\",\"from_location\":\"\",\"to_location\":\"\",\"flight_no_arr\":\"\",\"flight_no_dep\":\"\",\"operator_airline\":\"SUSI AIR\",\"remarks\":\"\",\"is_ron\":0,\"ron_complete\":0,\"movement_date\":\"2025-10-03\",\"user_id_created\":2,\"user_id_updated\":2,\"created_at\":\"2025-10-03 11:04:41\",\"updated_at\":\"2025-10-03 11:05:14\",\"on_block_date\":null,\"off_block_date\":\"2025-10-03\",\"category\":\"commercial\",\"aircraft_operator\":\"SUSI AIR\"}],\"ron_data\":[],\"daily_metrics\":{\"total_arrivals\":2,\"total_departures\":1,\"new_ron\":0,\"active_ron\":19,\"hourly_movements\":[{\"time_range\":\"12:00-13:59\",\"Arrivals\":\"2\",\"Departures\":\"1\"}],\"snapshot_generated_at\":\"2025-10-03 06:12:52\"}}', 2, '2025-10-03 04:12:52');

-- --------------------------------------------------------

--
-- Table structure for table `daily_staff_roster`
--

CREATE TABLE `daily_staff_roster` (
  `id` int(11) NOT NULL,
  `roster_date` date NOT NULL,
  `shift` varchar(50) NOT NULL,
  `updated_by_user_id` int(11) DEFAULT 1,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `aerodrome_code` varchar(10) DEFAULT NULL,
  `day_shift_staff_1` varchar(100) DEFAULT NULL,
  `day_shift_staff_2` varchar(100) DEFAULT NULL,
  `day_shift_staff_3` varchar(100) DEFAULT NULL,
  `night_shift_staff_1` varchar(100) DEFAULT NULL,
  `night_shift_staff_2` varchar(100) DEFAULT NULL,
  `night_shift_staff_3` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_staff_roster`
--

INSERT INTO `daily_staff_roster` (`id`, `roster_date`, `shift`, `updated_by_user_id`, `updated_at`, `aerodrome_code`, `day_shift_staff_1`, `day_shift_staff_2`, `day_shift_staff_3`, `night_shift_staff_1`, `night_shift_staff_2`, `night_shift_staff_3`) VALUES
(1, '2025-07-11', 'Day', 1, '2025-07-11 10:18:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, '2025-07-11', 'Night', 1, '2025-07-11 10:18:34', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, '2025-07-11', 'Day', 1, '2025-07-11 11:12:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, '2025-07-11', 'Night', 1, '2025-07-11 11:12:36', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, '2025-07-11', 'Day', 1, '2025-07-11 11:20:02', 'WIHH', 'NIKON', 'SERDA JIM JIM', '', 'RIDWAN', 'PELTU UUM', ''),
(6, '2025-07-11', 'Night', 1, '2025-07-11 11:20:02', 'WIHH', 'NIKON', 'SERDA JIM JIM', '', 'RIDWAN', 'PELTU UUM', ''),
(7, '2025-07-11', 'Day', 1, '2025-07-11 11:21:30', 'WIHH', 'SIANG2', 'SIANG3', '', 'MALAM2', 'MALAM3', ''),
(8, '2025-07-11', 'Night', 1, '2025-07-11 11:21:30', 'WIHH', 'SIANG2', 'SIANG3', '', 'MALAM2', 'MALAM3', ''),
(9, '2025-07-17', 'Day', 1, '2025-07-11 11:43:16', 'WIHH', 'pagiii2', 'pagiii3', '', 'mlem2', 'mlem3', ''),
(10, '2025-07-17', 'Night', 1, '2025-07-11 11:43:16', 'WIHH', 'pagiii2', 'pagiii3', '', 'mlem2', 'mlem3', ''),
(11, '2025-07-22', 'Day', 1, '2025-07-11 12:49:08', 'WIHH', 'day1', 'day2', 'day3', 'night1', 'night2', 'night3'),
(12, '2025-07-22', 'Night', 1, '2025-07-11 12:49:08', 'WIHH', 'day1', 'day2', 'day3', 'night1', 'night2', 'night3'),
(13, '2025-09-05', 'Day', 1, '2025-09-05 15:01:29', 'WIHH', 'John Doe', 'Jane Smith', 'Operator One', 'Peter Jones', 'Mary Williams', 'Operator Two'),
(14, '2025-09-19', '', 2, '2025-09-19 22:26:58', 'WIHH', '19day', '19dayy', '19dayyy', '19night', '19nightt', '19nighttt'),
(15, '2025-09-20', '', 2, '2025-09-20 13:52:13', 'WIHH', 'ashiap1', 'ashiap2', 'ashiap3', 'aha1', 'aha2', 'aha3');

-- --------------------------------------------------------

--
-- Table structure for table `flight_references`
--

CREATE TABLE `flight_references` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `flight_no` varchar(20) NOT NULL,
  `default_route` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `flight_references`
--

INSERT INTO `flight_references` (`id`, `flight_no`, `default_route`) VALUES
(1, '0B 1001', 'WIII'),
(2, '0B 1051', 'WAJJ'),
(3, '0B 5021', 'WAAA'),
(4, '0B 5022', 'WAMM'),
(5, '0B 5024', 'WAAA'),
(6, '0B 5273', 'WAOO'),
(7, '0B 5274', 'WAOO'),
(8, '0B 5275', 'WAOO'),
(9, '0B 5276', 'WAOO'),
(10, '0B 5611', 'WIOO'),
(11, '0B 5612', 'WIOO'),
(12, '0B 5613', 'WIOO'),
(13, '0B 5614', 'WIOO'),
(14, '0B 5631', 'WAGG'),
(15, '0B 5632', 'WAGG'),
(16, '0B 5633', 'WAGG'),
(17, '0B 5634', 'WAGG'),
(18, 'GA 0121', 'WIMM'),
(19, 'GA 0163', 'WIEE'),
(20, 'GA 0166', 'WIEE'),
(21, 'GA 0180', 'WIMM'),
(22, 'GA 0181', 'WIMM'),
(23, 'GA 0330', 'WARR'),
(24, 'GA 0331', 'WARR'),
(25, 'GA 1301', 'WIII'),
(26, 'GM 0019', 'WMKK'),
(27, 'GM 0141', 'WIOO'),
(28, 'GM 019', 'WSSS'),
(29, 'GM 030', 'WALL'),
(30, 'GM 061', 'WALL'),
(31, 'GM 063', 'WALL'),
(32, 'GM 066', 'WALL'),
(33, 'GM 141', 'WIOO'),
(34, 'GM 142', 'WAOO'),
(35, 'GM 143', 'WAOO'),
(36, 'GM 144', 'WIOO'),
(37, 'GM 145', 'WAOO'),
(38, 'GM 150', 'WAGG'),
(39, 'GM 151', 'WAGG'),
(40, 'GM 171', 'WIMM'),
(41, 'GM 172', 'WIMM'),
(42, 'GM 174', 'WIMM'),
(43, 'GM 175', 'WIMM'),
(44, 'GM 180', 'WAGG'),
(45, 'GM 182', 'WIOO'),
(46, 'GM 193', 'WIMM'),
(47, 'GM 203D', 'WIII'),
(48, 'GM 306', 'WSSS'),
(49, 'GM 362', 'WAMM'),
(50, 'GM 449', 'WIDD'),
(51, 'GM 741', 'WICC'),
(52, 'GM 861', 'WSSS'),
(53, 'ID 7010', 'WIMM'),
(54, 'ID 7011', 'WIMM'),
(55, 'ID 7014', 'WIMM'),
(56, 'ID 7021', 'WIMM'),
(57, 'ID 7040', 'WAHQ'),
(58, 'ID 7041', 'WAHQ'),
(59, 'ID 7051', 'WIPP'),
(60, 'ID 7052', 'WIPP'),
(61, 'ID 7053', 'WIPP'),
(62, 'ID 7054', 'WIPP'),
(63, 'ID 7055', 'WIPP'),
(64, 'ID 7056', 'WIPP'),
(65, 'ID 7058', 'WIPP'),
(66, 'ID 7059', 'WIPP'),
(67, 'ID 7060', 'WIBB'),
(68, 'ID 7061', 'WIBB'),
(69, 'ID 7062', 'WIBB'),
(70, 'ID 7063', 'WIBB'),
(71, 'ID 7065', 'WIBB'),
(72, 'ID 7066', 'WIBB'),
(73, 'ID 7108', 'WIEE'),
(74, 'ID 7109', 'WIEE'),
(75, 'ID 7270', 'WALL'),
(76, 'ID 7271', 'WALL'),
(77, 'ID 7308', 'WADD'),
(78, 'ID 7309', 'WADD'),
(79, 'ID 7310', 'WADD'),
(80, 'ID 7311', 'WADD'),
(81, 'ID 7501', 'WARR'),
(82, 'ID 7502', 'WARR'),
(83, 'ID 7503', 'WARR'),
(84, 'ID 7508', 'WARR'),
(85, 'ID 7510', 'WARR'),
(86, 'ID 7511', 'WARR'),
(87, 'ID 7512', 'WARR'),
(88, 'ID 7513', 'WARR'),
(89, 'ID 7514', 'WARR'),
(90, 'ID 7515', 'WARR'),
(91, 'ID 7516', 'WARR'),
(92, 'ID 7517', 'WARR'),
(93, 'ID 7518', 'WARR'),
(94, 'ID 7519', 'WARR'),
(95, 'ID 7520', 'WARR'),
(96, 'ID 7521', 'WARR'),
(97, 'ID 7530', 'WAHI'),
(98, 'ID 7531', 'WAHQ'),
(99, 'ID 7532', 'WAHQ'),
(100, 'ID 7533', 'WAHI'),
(101, 'ID 7536', 'WAHI'),
(102, 'ID 7537', 'WAHI'),
(103, 'ID 7538', 'WAHI'),
(104, 'ID 7539', 'WAHI'),
(105, 'ID 7540', 'WAHI'),
(106, 'ID 7541', 'WAHI'),
(107, 'ID 7552', 'WAHS'),
(108, 'ID 7553', 'WAHS'),
(109, 'ID 7556', 'WAHS'),
(110, 'ID 7557', 'WAHS'),
(111, 'ID 7558', 'WAHS'),
(112, 'ID 7559', 'WAHS'),
(113, 'ID 7580', 'WARA'),
(114, 'ID 7581', 'WARA'),
(115, 'ID 7582', 'WARA'),
(116, 'ID 7583', 'WARA'),
(117, 'ID 8010', 'WIMM'),
(118, 'ID 8021', 'WIMM'),
(119, 'ID 8065', 'WIBB'),
(120, 'ID 8519', 'WARR'),
(121, 'ID 8580', 'WARA'),
(122, 'ID 8581', 'WARA'),
(123, 'IL 146', 'WAAA'),
(124, 'IL 322', 'WIRR'),
(125, 'IL 701', 'WIMM'),
(126, 'IL 702', 'WIMM'),
(127, 'IL 702D', 'WIMM'),
(128, 'IL 703', 'WIMM'),
(129, 'IL 704', 'WIMM'),
(130, 'IL 713', 'WAGG'),
(131, 'IL 7131', 'WAGG'),
(132, 'IL 714', 'WAGG'),
(133, 'IL 7141', 'WAGG'),
(134, 'IL 715', 'WIKK'),
(135, 'IL 716', 'WIKK'),
(136, 'IL 717', 'WIDN'),
(137, 'IL 718', 'WIDN'),
(138, 'IL 7181', 'WIDN'),
(139, 'IL 719', 'WIDD'),
(140, 'IL 720', 'WIDD'),
(141, 'IL 721', 'WAAA'),
(142, 'IL 722', 'WAAA'),
(143, 'IL 7221', 'WAAA'),
(144, 'IL 722D', 'WAAA'),
(145, 'IL 723', 'WAAA'),
(146, 'IL 724', 'WAAA'),
(147, 'IL 725', 'WAOO'),
(148, 'IL 726', 'WAOO'),
(149, 'IL 7261', 'WAOO'),
(150, 'IL 726D', 'WAOO'),
(151, 'IL 727', 'WALL'),
(152, 'IL 7271', 'WALL'),
(153, 'IL 728', 'WALL'),
(154, 'IL 7281', 'WALL'),
(155, 'IL 729', 'WAMM'),
(156, 'IL 7291', 'WAMM'),
(157, 'IL 730', 'WAMM'),
(158, 'IL 7301', 'WAMM'),
(159, 'IL 7301D', 'WAMM'),
(160, 'IL 730D', 'WAMM'),
(161, 'IL 731', 'WITT'),
(162, 'IL 732', 'WITT'),
(163, 'IL 733', 'WAQQ'),
(164, 'IL 737', 'WATT'),
(165, 'IL 738', 'WATT'),
(166, 'IL 741', 'WIOO'),
(167, 'IL 742', 'WIOO'),
(168, 'IL 7421', 'WIOO'),
(169, 'IL 743', 'WIOO'),
(170, 'IL 744', 'WIOO'),
(171, 'IL 745', 'WIOO'),
(172, 'IL 746', 'WIOO'),
(173, 'IL 751', 'WAFF'),
(174, 'IL 752', 'WAFF'),
(175, 'IL 754', 'WAEE'),
(176, 'IL 755', 'WAMG'),
(177, 'IL 756', 'WAMG'),
(178, 'IL 757', 'WAWW'),
(179, 'IL 759', 'WAAA'),
(180, 'IL 760', 'WAAA'),
(181, 'IL 761', 'WAPP'),
(182, 'IL 900', 'WIRR'),
(183, 'IP 7300', 'WIDM'),
(184, 'IP 7301', 'WIDM'),
(185, 'IP 7302', 'WIDM'),
(186, 'IP 7303', 'WIDM'),
(187, 'QG 0010', 'WIMM'),
(188, 'QG 0011', 'WIMM'),
(189, 'QG 0020', 'WIMN'),
(190, 'QG 0021', 'WIMN'),
(191, 'QG 0022', 'WIMM'),
(192, 'QG 0025', 'WIMM'),
(193, 'QG 0030', 'WIBB'),
(194, 'QG 0031', 'WIBB'),
(195, 'QG 0032', 'WIBB'),
(196, 'QG 0033', 'WIBB'),
(197, 'QG 0040', 'WALL'),
(198, 'QG 0041', 'WALL'),
(199, 'QG 0046', 'WIEE'),
(200, 'QG 0047', 'WIEE'),
(201, 'QG 0084', 'WIPP'),
(202, 'QG 0085', 'WIPP'),
(203, 'QG 0088', 'WIPP'),
(204, 'QG 0089', 'WIPP'),
(205, 'QG 0102', 'WAHI'),
(206, 'QG 0103', 'WAHI'),
(207, 'QG 0126', 'WAHQ'),
(208, 'QG 0127', 'WAHQ'),
(209, 'QG 0144', 'WAHS'),
(210, 'QG 0145', 'WAHS'),
(211, 'QG 0146', 'WAHS'),
(212, 'QG 0147', 'WAHS'),
(213, 'QG 0164', 'WARA'),
(214, 'QG 0165', 'WARA'),
(215, 'QG 0170', 'WARR'),
(216, 'QG 0171', 'WARR'),
(217, 'QG 0172', 'WARR'),
(218, 'QG 0173', 'WARR'),
(219, 'QG 0174', 'WARR'),
(220, 'QG 0175', 'WARR'),
(221, 'QG 0176', 'WARR'),
(222, 'QG 0177', 'WARR'),
(223, 'QG 0178', 'WARR'),
(224, 'QG 0179', 'WARR'),
(225, 'QG 0180', 'WARR'),
(226, 'QG 0181', 'WARR'),
(227, 'QG 0194', 'WADD'),
(228, 'QG 0195', 'WADD'),
(229, 'QG 0196', 'WADD'),
(230, 'QG 0197', 'WADD'),
(231, 'QG 1100', 'WAHH'),
(232, 'QG 1101', 'WAHH'),
(233, 'QG 1102', 'WAHH'),
(234, 'QG 1103', 'WAHH'),
(235, 'QG 1104', 'WAHH'),
(236, 'QG 1105', 'WAHH'),
(237, 'QG 1106', 'WAHH'),
(238, 'QG 1107', 'WAHH'),
(239, 'QG 1122', 'WIII'),
(240, 'QG 1234', 'WIII'),
(241, 'QG 1998', 'WIPO'),
(242, 'QG 1999', 'WIPO'),
(243, 'QG 2023', 'WIMM'),
(244, 'QG 2104', 'WIMM'),
(245, 'QG 4141', 'WAOO'),
(246, 'QG 8250', 'WAYY'),
(247, 'QG 8251', 'WAYY'),
(248, 'QG 8252', 'WAYY'),
(249, 'QG 8253', 'WAYY'),
(250, 'QG 8267', 'WAYY'),
(251, 'QG 8269', 'WAYY'),
(252, 'QG 9048', 'WIEE'),
(253, 'QG 9049', 'WIEE'),
(254, 'QG 9102', 'WAHI'),
(255, 'QG 9103', 'WAHI'),
(256, 'QG 9170', 'WIII'),
(257, 'QG 9176', 'WIII'),
(258, 'QG 9194', 'WADD'),
(259, 'QG 9195', 'WADD'),
(260, 'QG 9197', 'WADD'),
(261, 'SI 204', 'WICN'),
(262, 'SI 205', 'WICN'),
(263, 'SI 230', 'WICC'),
(264, 'SI 231', 'WICC'),
(265, 'SI 234', 'WICN'),
(266, 'SI 235', 'WICN'),
(267, 'GM 002', 'WIII'),
(268, 'GM 110', 'WAJJ'),
(269, 'ID 5000', 'WIII'),
(270, 'IL 741R', 'RTB'),
(271, 'IL 7151', 'WIKK'),
(272, 'IL 7161', 'WIKK'),
(273, 'IL 727D', 'WALL'),
(274, 'QG 9107', 'WAHH'),
(275, 'QG 9113', 'WAHI'),
(276, 'IL 712', 'WAGG'),
(277, 'IL 7251', 'WAOO'),
(278, 'GA 6361', 'WIII'),
(279, 'GA 1631', 'WIII'),
(280, 'test101', 'WOWOW'),
(281, 'testflight12', 'TESTROUTE01');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username_attempted` varchar(100) DEFAULT NULL,
  `attempt_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ml_model_versions`
--

CREATE TABLE `ml_model_versions` (
  `id` int(11) NOT NULL,
  `version_number` varchar(20) NOT NULL,
  `training_date` date NOT NULL,
  `training_samples` int(11) NOT NULL,
  `test_accuracy` decimal(5,4) DEFAULT NULL,
  `top3_accuracy` decimal(5,4) NOT NULL,
  `model_file_path` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ml_model_versions`
--

INSERT INTO `ml_model_versions` (`id`, `version_number`, `training_date`, `training_samples`, `test_accuracy`, `top3_accuracy`, `model_file_path`, `notes`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'v1.0', '2025-10-24', 1145, 0.4310, 0.6157, 'ml/parking_stand_model.pkl', 'Baseline model trained from parking_history.csv snapshot on 2025-10-24.', 0, 1, '2025-10-25 13:32:59'),
(2, 'v2.0', '2025-10-30', 4152, 0.3613, 0.8015, 'ml/parking_stand_model_rf_redo.pkl', 'Random Forest (100 trees) with 6 engineered features: aircraft_type, aircraft_size, operator_airline, airline_tier, category, stand_zone. Achieved 80.15% Top-3 accuracy (target: 80%). Upgraded from Decision Tree v1.0 (61.57% Top-3). Key improvement: Stand Zone feature (37.58% importance).', 1, 1, '2025-10-30 07:12:01');

-- --------------------------------------------------------

--
-- Table structure for table `ml_prediction_log`
--

CREATE TABLE `ml_prediction_log` (
  `id` int(11) NOT NULL,
  `prediction_token` char(36) NOT NULL,
  `prediction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `aircraft_type` varchar(50) NOT NULL,
  `operator_airline` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `predicted_stands` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`predicted_stands`)),
  `recommendation_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recommendation_payload`)),
  `model_version` varchar(20) DEFAULT NULL,
  `requested_by_user` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_by_user` bigint(20) UNSIGNED DEFAULT NULL,
  `actual_stand_assigned` varchar(10) DEFAULT NULL,
  `was_prediction_correct` tinyint(1) DEFAULT NULL,
  `actual_recorded_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ml_prediction_log`
--

INSERT INTO `ml_prediction_log` (`id`, `prediction_token`, `prediction_date`, `aircraft_type`, `operator_airline`, `category`, `predicted_stands`, `recommendation_payload`, `model_version`, `requested_by_user`, `assigned_by_user`, `actual_stand_assigned`, `was_prediction_correct`, `actual_recorded_at`, `notes`) VALUES
(1, '0676e94c958bb7e8dc9f932f26314b0e', '2025-10-25 20:47:03', 'B 738', 'GARUDA', 'KOMERSIAL', '[{\"stand\":\"B2\",\"probability\":0.7954545454545454,\"rank\":1},{\"stand\":\"B4\",\"probability\":0.06818181818181818,\"rank\":2},{\"stand\":\"B5\",\"probability\":0.06818181818181818,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"B2\",\"rank\":1,\"probability\":0.7954545454545454,\"preference_score\":100,\"composite_score\":0.8772727272727272}],\"availability\":{\"available\":[\"A0\",\"B1\",\"B2\",\"B3\",\"B6\",\"B8\",\"B10\",\"B11\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"A1\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\",\"A2\",\"A3\"],\"timestamp\":\"2025-10-25T15:47:03+02:00\"},\"preferences\":{\"B2\":100,\"B1\":95,\"B3\":90,\"A3\":85},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_operator_airline.pkl\",\"enc_category.pkl\",\"enc_airline_category.pkl\",\"enc_aircraft_airline.pkl\",\"enc_aircraft_category.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.5807860262008734,\"top3_accuracy_percent\":\"58.1%\",\"model_timestamp\":\"2025-10-24T16:18:19.204351Z\",\"model_version\":\"v1.0\",\"model_training_date\":\"2025-10-24\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 58.1% (target 70%).\",\"input\":{\"aircraft_type\":\"B 738\",\"operator_airline\":\"GARUDA\",\"category\":\"Komersial\"}}', 'v1.0', 6, NULL, NULL, NULL, NULL, NULL),
(2, 'f429ac001e817a22d1ff038de50c5849', '2025-10-25 20:47:31', 'B 738', 'GARUDA', 'KOMERSIAL', '[{\"stand\":\"B2\",\"probability\":0.7954545454545454,\"rank\":1},{\"stand\":\"B4\",\"probability\":0.06818181818181818,\"rank\":2},{\"stand\":\"B5\",\"probability\":0.06818181818181818,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"B2\",\"rank\":1,\"probability\":0.7954545454545454,\"preference_score\":100,\"composite_score\":0.8772727272727272}],\"availability\":{\"available\":[\"A0\",\"B1\",\"B2\",\"B3\",\"B6\",\"B8\",\"B10\",\"B11\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"A1\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\",\"A2\",\"A3\"],\"timestamp\":\"2025-10-25T15:47:31+02:00\"},\"preferences\":{\"B2\":100,\"B1\":95,\"B3\":90,\"A3\":85},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_operator_airline.pkl\",\"enc_category.pkl\",\"enc_airline_category.pkl\",\"enc_aircraft_airline.pkl\",\"enc_aircraft_category.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.5807860262008734,\"top3_accuracy_percent\":\"58.1%\",\"model_timestamp\":\"2025-10-24T16:18:19.204351Z\",\"model_version\":\"v1.0\",\"model_training_date\":\"2025-10-24\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 58.1% (target 70%).\",\"input\":{\"aircraft_type\":\"B 738\",\"operator_airline\":\"GARUDA\",\"category\":\"Komersial\"}}', 'v1.0', 6, NULL, NULL, NULL, NULL, NULL),
(3, '5d226f587737c54a6c85982246b82875', '2025-10-26 13:06:40', 'B 733', 'AIRNESIA', 'CARGO', '[{\"stand\":\"B10\",\"probability\":0.2962962962962963,\"rank\":1},{\"stand\":\"B11\",\"probability\":0.2962962962962963,\"rank\":2},{\"stand\":\"B13\",\"probability\":0.14814814814814814,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"B11\",\"rank\":1,\"probability\":0.2962962962962963,\"preference_score\":90,\"composite_score\":0.5377777777777778},{\"stand\":\"B10\",\"rank\":2,\"probability\":0.2962962962962963,\"preference_score\":85,\"composite_score\":0.5177777777777778},{\"stand\":\"B13\",\"rank\":3,\"probability\":0.14814814814814814,\"preference_score\":100,\"composite_score\":0.48888888888888893}],\"availability\":{\"available\":[\"A0\",\"B2\",\"B3\",\"B6\",\"B8\",\"B10\",\"B11\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B1\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"A1\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\",\"A2\",\"A3\"],\"timestamp\":\"2025-10-26T07:06:40+01:00\"},\"preferences\":{\"B13\":100,\"B12\":95,\"B11\":90,\"B10\":85,\"B9\":80},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_operator_airline.pkl\",\"enc_category.pkl\",\"enc_airline_category.pkl\",\"enc_aircraft_airline.pkl\",\"enc_aircraft_category.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.5807860262008734,\"top3_accuracy_percent\":\"58.1%\",\"model_timestamp\":\"2025-10-24T16:18:19.204351Z\",\"model_version\":\"v1.0\",\"model_training_date\":\"2025-10-24\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 58.1% (target 70%).\",\"input\":{\"aircraft_type\":\"B 733\",\"operator_airline\":\"AIRNESIA\",\"category\":\"Cargo\"}}', 'v1.0', 2, 2, 'B11', 1, '2025-10-26 13:09:44', NULL),
(4, '286cdb820cb5e8c867cda0584a3be2fc', '2025-10-26 13:12:49', 'C 208', 'SUSI AIR', 'KOMERSIAL', '[{\"stand\":\"B7\",\"probability\":0.5,\"rank\":1},{\"stand\":\"B9\",\"probability\":0.25,\"rank\":2},{\"stand\":\"B13\",\"probability\":0.25,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"B13\",\"rank\":1,\"probability\":0.25,\"preference_score\":0,\"composite_score\":0.15}],\"availability\":{\"available\":[\"A0\",\"A1\",\"A3\",\"B2\",\"B3\",\"B6\",\"B8\",\"B10\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B1\",\"B11\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\",\"A2\"],\"timestamp\":\"2025-10-26T07:12:49+01:00\"},\"preferences\":{\"A0\":100,\"B7\":95},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_operator_airline.pkl\",\"enc_category.pkl\",\"enc_airline_category.pkl\",\"enc_aircraft_airline.pkl\",\"enc_aircraft_category.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.5807860262008734,\"top3_accuracy_percent\":\"58.1%\",\"model_timestamp\":\"2025-10-24T16:18:19.204351Z\",\"model_version\":\"v1.0\",\"model_training_date\":\"2025-10-24\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 58.1% (target 70%).\",\"input\":{\"aircraft_type\":\"C 208\",\"operator_airline\":\"SUSI AIR\",\"category\":\"Komersial\"}}', 'v1.0', 2, NULL, NULL, NULL, NULL, NULL),
(5, '2d7f0696b4ac238f96ce1c1c77ea81fe', '2025-10-26 13:13:31', 'B 733', 'SUSI AIR', 'CARGO', '[{\"stand\":\"B10\",\"probability\":0.2962962962962963,\"rank\":1},{\"stand\":\"B11\",\"probability\":0.2962962962962963,\"rank\":2},{\"stand\":\"B13\",\"probability\":0.14814814814814814,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"B13\",\"rank\":1,\"probability\":0.14814814814814814,\"preference_score\":50,\"composite_score\":0.28888888888888886},{\"stand\":\"B10\",\"rank\":2,\"probability\":0.2962962962962963,\"preference_score\":0,\"composite_score\":0.17777777777777776}],\"availability\":{\"available\":[\"A0\",\"A1\",\"A3\",\"B2\",\"B3\",\"B6\",\"B8\",\"B10\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B1\",\"B11\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\",\"A2\"],\"timestamp\":\"2025-10-26T07:13:31+01:00\"},\"preferences\":{\"B11\":100,\"RE05\":50,\"B13\":50,\"B1\":50,\"B7\":50,\"B12\":50},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_operator_airline.pkl\",\"enc_category.pkl\",\"enc_airline_category.pkl\",\"enc_aircraft_airline.pkl\",\"enc_aircraft_category.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.5807860262008734,\"top3_accuracy_percent\":\"58.1%\",\"model_timestamp\":\"2025-10-24T16:18:19.204351Z\",\"model_version\":\"v1.0\",\"model_training_date\":\"2025-10-24\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 58.1% (target 70%).\",\"input\":{\"aircraft_type\":\"B 733\",\"operator_airline\":\"SUSI AIR\",\"category\":\"Cargo\"}}', 'v1.0', 2, 2, 'B10', 1, '2025-10-26 13:13:39', NULL),
(6, '6e5afdbeb58753cf096341b0b8abf83f', '2025-10-26 13:17:31', 'CL 850', 'JETSET', 'CHARTER', '[{\"stand\":\"B2\",\"probability\":0.7954545454545454,\"rank\":1},{\"stand\":\"B4\",\"probability\":0.06818181818181818,\"rank\":2},{\"stand\":\"B5\",\"probability\":0.06818181818181818,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"B2\",\"rank\":1,\"probability\":0.7954545454545454,\"preference_score\":0,\"composite_score\":0.47727272727272724}],\"availability\":{\"available\":[\"A0\",\"A1\",\"A3\",\"B2\",\"B3\",\"B6\",\"B8\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B1\",\"B11\",\"B10\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\",\"A2\"],\"timestamp\":\"2025-10-26T07:17:31+01:00\"},\"preferences\":{\"B3\":100,\"B4\":95,\"B5\":90,\"B6\":85,\"B7\":80},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_operator_airline.pkl\",\"enc_category.pkl\",\"enc_airline_category.pkl\",\"enc_aircraft_airline.pkl\",\"enc_aircraft_category.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.5807860262008734,\"top3_accuracy_percent\":\"58.1%\",\"model_timestamp\":\"2025-10-24T16:18:19.204351Z\",\"model_version\":\"v1.0\",\"model_training_date\":\"2025-10-24\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 58.1% (target 70%).\",\"input\":{\"aircraft_type\":\"CL 850\",\"operator_airline\":\"JETSET\",\"category\":\"Charter\"}}', 'v1.0', 2, 2, 'B2', 1, '2025-10-26 13:17:35', NULL),
(7, 'c2116355576577419adafd26563dd92e', '2025-10-26 13:21:48', 'B 733', 'JAYAWIJAYA', 'CARGO', '[{\"stand\":\"B10\",\"probability\":0.2962962962962963,\"rank\":1},{\"stand\":\"B11\",\"probability\":0.2962962962962963,\"rank\":2},{\"stand\":\"B13\",\"probability\":0.14814814814814814,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"B13\",\"rank\":1,\"probability\":0.14814814814814814,\"preference_score\":50,\"composite_score\":0.28888888888888886}],\"availability\":{\"available\":[\"A0\",\"A1\",\"A3\",\"B3\",\"B6\",\"B8\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B1\",\"B11\",\"B10\",\"B2\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\",\"A2\"],\"timestamp\":\"2025-10-26T07:21:48+01:00\"},\"preferences\":{\"B11\":100,\"B1\":50,\"B7\":50,\"B10\":50,\"B12\":50,\"RE05\":50,\"B13\":50},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_operator_airline.pkl\",\"enc_category.pkl\",\"enc_airline_category.pkl\",\"enc_aircraft_airline.pkl\",\"enc_aircraft_category.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.5807860262008734,\"top3_accuracy_percent\":\"58.1%\",\"model_timestamp\":\"2025-10-24T16:18:19.204351Z\",\"model_version\":\"v1.0\",\"model_training_date\":\"2025-10-24\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 58.1% (target 70%).\",\"input\":{\"aircraft_type\":\"B 733\",\"operator_airline\":\"JAYAWIJAYA\",\"category\":\"Cargo\"}}', 'v1.0', 2, 2, 'A3', 0, '2025-10-26 13:22:59', NULL),
(8, 'af764080e82b4b4c6e16c7d5225aa348', '2025-10-26 13:25:07', 'AVANTI', 'SUSI AIR', 'KOMERSIAL', '[{\"stand\":\"A3\",\"probability\":1,\"rank\":1},{\"stand\":\"B9\",\"probability\":0,\"rank\":2},{\"stand\":\"B8\",\"probability\":0,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"B8\",\"rank\":1,\"probability\":0,\"preference_score\":0,\"composite_score\":0}],\"availability\":{\"available\":[\"A0\",\"A1\",\"B3\",\"B6\",\"B8\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B1\",\"B11\",\"B10\",\"B2\",\"A3\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\",\"A2\"],\"timestamp\":\"2025-10-26T07:25:07+01:00\"},\"preferences\":{\"A0\":100,\"B7\":95},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_operator_airline.pkl\",\"enc_category.pkl\",\"enc_airline_category.pkl\",\"enc_aircraft_airline.pkl\",\"enc_aircraft_category.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.5807860262008734,\"top3_accuracy_percent\":\"58.1%\",\"model_timestamp\":\"2025-10-24T16:18:19.204351Z\",\"model_version\":\"v1.0\",\"model_training_date\":\"2025-10-24\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 58.1% (target 70%).\",\"input\":{\"aircraft_type\":\"AVANTI\",\"operator_airline\":\"SUSI AIR\",\"category\":\"Komersial\"}}', 'v1.0', 2, 2, 'B8', 1, '2025-10-26 13:25:20', NULL),
(9, '541c247f03f83d74e50cd9303648240a', '2025-10-26 13:29:35', 'B 738', 'GARUDA', 'KOMERSIAL', '[{\"stand\":\"B2\",\"probability\":0.7954545454545454,\"rank\":1},{\"stand\":\"B4\",\"probability\":0.06818181818181818,\"rank\":2},{\"stand\":\"B5\",\"probability\":0.06818181818181818,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"A0\",\"rank\":1,\"probability\":null,\"preference_score\":0,\"composite_score\":0},{\"stand\":\"A1\",\"rank\":2,\"probability\":null,\"preference_score\":0,\"composite_score\":0},{\"stand\":\"B3\",\"rank\":3,\"probability\":null,\"preference_score\":0,\"composite_score\":0}],\"availability\":{\"available\":[\"A0\",\"A1\",\"B3\",\"B6\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B1\",\"B11\",\"B10\",\"B2\",\"A3\",\"B8\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\",\"A2\"],\"timestamp\":\"2025-10-26T07:29:35+01:00\"},\"preferences\":{\"B2\":100,\"B1\":95,\"B3\":90,\"A3\":85},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_operator_airline.pkl\",\"enc_category.pkl\",\"enc_airline_category.pkl\",\"enc_aircraft_airline.pkl\",\"enc_aircraft_category.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.5807860262008734,\"top3_accuracy_percent\":\"58.1%\",\"model_timestamp\":\"2025-10-24T16:18:19.204351Z\",\"model_version\":\"v1.0\",\"model_training_date\":\"2025-10-24\"},\"source\":\"fallback\",\"notes\":\"Model predictions were filtered out by availability; provided fallback stands. Latest evaluated top-3 accuracy: 58.1% (target 70%).\",\"input\":{\"aircraft_type\":\"B 738\",\"operator_airline\":\"GARUDA\",\"category\":\"Komersial\"}}', 'v1.0', 2, 2, 'B3', 0, '2025-10-26 13:29:49', NULL),
(10, '8684865a2bf06d1051bcea1f217ce9e3', '2025-10-26 13:31:28', 'B 738', 'GARUDA', 'KOMERSIAL', '[{\"stand\":\"B2\",\"probability\":0.7954545454545454,\"rank\":1},{\"stand\":\"B4\",\"probability\":0.06818181818181818,\"rank\":2},{\"stand\":\"B5\",\"probability\":0.06818181818181818,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"A0\",\"rank\":1,\"probability\":null,\"preference_score\":0,\"composite_score\":0},{\"stand\":\"A1\",\"rank\":2,\"probability\":null,\"preference_score\":0,\"composite_score\":0},{\"stand\":\"B6\",\"rank\":3,\"probability\":null,\"preference_score\":0,\"composite_score\":0}],\"availability\":{\"available\":[\"A0\",\"A1\",\"B6\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B1\",\"B11\",\"B10\",\"B2\",\"A3\",\"B8\",\"B3\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\",\"A2\"],\"timestamp\":\"2025-10-26T07:31:28+01:00\"},\"preferences\":{\"B2\":100,\"B1\":95,\"B3\":90,\"A3\":85},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_operator_airline.pkl\",\"enc_category.pkl\",\"enc_airline_category.pkl\",\"enc_aircraft_airline.pkl\",\"enc_aircraft_category.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.5807860262008734,\"top3_accuracy_percent\":\"58.1%\",\"model_timestamp\":\"2025-10-24T16:18:19.204351Z\",\"model_version\":\"v1.0\",\"model_training_date\":\"2025-10-24\"},\"source\":\"fallback\",\"notes\":\"Model predictions were filtered out by availability; provided fallback stands. Latest evaluated top-3 accuracy: 58.1% (target 70%).\",\"input\":{\"aircraft_type\":\"B 738\",\"operator_airline\":\"GARUDA\",\"category\":\"Komersial\"}}', 'v1.0', 2, 2, 'A1', 0, '2025-10-26 13:31:41', NULL),
(11, '50fc3e83c1635604565ba2095a62f6d9', '2025-10-26 17:05:34', 'ATR 72', 'PELITA', 'KOMERSIAL', '[{\"stand\":\"A2\",\"probability\":0.17197452229299362,\"rank\":1},{\"stand\":\"A3\",\"probability\":0.14012738853503184,\"rank\":2},{\"stand\":\"A1\",\"probability\":0.12738853503184713,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"A3\",\"rank\":1,\"probability\":0.14012738853503184,\"preference_score\":90,\"composite_score\":0.44407643312101913}],\"availability\":{\"available\":[\"A0\",\"A3\",\"B6\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B1\",\"B11\",\"B10\",\"B2\",\"B8\",\"B3\",\"A1\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\",\"A2\"],\"timestamp\":\"2025-10-26T11:05:34+01:00\"},\"preferences\":{\"A1\":100,\"A2\":95,\"A3\":90,\"B1\":85},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_operator_airline.pkl\",\"enc_category.pkl\",\"enc_airline_category.pkl\",\"enc_aircraft_airline.pkl\",\"enc_aircraft_category.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.5807860262008734,\"top3_accuracy_percent\":\"58.1%\",\"model_timestamp\":\"2025-10-24T16:18:19.204351Z\",\"model_version\":\"v1.0\",\"model_training_date\":\"2025-10-24\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 58.1% (target 70%).\",\"input\":{\"aircraft_type\":\"ATR 72\",\"operator_airline\":\"PELITA\",\"category\":\"Komersial\"}}', 'v1.0', 2, 2, 'A3', 1, '2025-10-26 17:05:37', NULL),
(12, '718a4b55700aaf72796871ff11d23756', '2025-10-26 17:08:44', 'A320', 'BATIK AIR', 'KOMERSIAL', '[{\"stand\":\"A2\",\"probability\":0.17197452229299362,\"rank\":1},{\"stand\":\"A3\",\"probability\":0.14012738853503184,\"rank\":2},{\"stand\":\"A1\",\"probability\":0.12738853503184713,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"A2\",\"rank\":1,\"probability\":0.17197452229299362,\"preference_score\":95,\"composite_score\":0.48318471337579616}],\"availability\":{\"available\":[\"A0\",\"A2\",\"B6\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B1\",\"B11\",\"B10\",\"B2\",\"B8\",\"B3\",\"A1\",\"A3\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\"],\"timestamp\":\"2025-10-26T11:08:44+01:00\"},\"preferences\":{\"A1\":100,\"A2\":95,\"A3\":90,\"B1\":85,\"B6\":80,\"B7\":75,\"B8\":70},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_operator_airline.pkl\",\"enc_category.pkl\",\"enc_airline_category.pkl\",\"enc_aircraft_airline.pkl\",\"enc_aircraft_category.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.5807860262008734,\"top3_accuracy_percent\":\"58.1%\",\"model_timestamp\":\"2025-10-24T16:18:19.204351Z\",\"model_version\":\"v1.0\",\"model_training_date\":\"2025-10-24\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 58.1% (target 70%).\",\"input\":{\"aircraft_type\":\"A320\",\"operator_airline\":\"BATIK AIR\",\"category\":\"Komersial\"}}', 'v1.0', NULL, NULL, NULL, NULL, NULL, NULL),
(13, '241c3d5ffa01f96ea5ce4987d9be0b03', '2025-10-30 14:27:53', 'B 738', 'GARUDA', 'KOMERSIAL', '[{\"stand\":\"B2\",\"probability\":0.8641496669607743,\"rank\":1},{\"stand\":\"B1\",\"probability\":0.09570621504326975,\"rank\":2},{\"stand\":\"A3\",\"probability\":0.016917430285906972,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"A0\",\"rank\":1,\"probability\":null,\"preference_score\":0,\"composite_score\":0},{\"stand\":\"A2\",\"rank\":2,\"probability\":null,\"preference_score\":0,\"composite_score\":0},{\"stand\":\"B6\",\"rank\":3,\"probability\":null,\"preference_score\":0,\"composite_score\":0}],\"availability\":{\"available\":[\"A0\",\"A2\",\"B6\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B1\",\"B11\",\"B10\",\"B2\",\"B8\",\"B3\",\"A1\",\"A3\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\"],\"timestamp\":\"2025-10-30T08:27:53+01:00\"},\"preferences\":{\"B2\":100,\"B1\":95,\"B3\":90,\"A3\":85},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model_rf_redo.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_aircraft_size.pkl\",\"enc_operator_airline.pkl\",\"enc_airline_tier.pkl\",\"enc_category.pkl\",\"enc_stand_zone.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.6252408477842004,\"top3_accuracy_percent\":\"62.5%\",\"model_timestamp\":\"2025-10-27T05:15:20.489073Z\",\"model_version\":\"v2.0\",\"model_training_date\":\"2025-10-30\"},\"source\":\"fallback\",\"notes\":\"Model predictions were filtered out by availability; provided fallback stands. Latest evaluated top-3 accuracy: 62.5% (target 70%).\",\"input\":{\"aircraft_type\":\"B 738\",\"operator_airline\":\"GARUDA\",\"category\":\"Komersial\"}}', 'v2.0', 2, NULL, NULL, NULL, NULL, NULL),
(14, '31313cc0c481ab960af5dc0f23ff8a06', '2025-10-30 14:29:17', 'B 738', 'GARUDA', 'KOMERSIAL', '[{\"stand\":\"B2\",\"probability\":0.8641496669607743,\"rank\":1},{\"stand\":\"B1\",\"probability\":0.09570621504326975,\"rank\":2},{\"stand\":\"A3\",\"probability\":0.016917430285906976,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"B2\",\"rank\":1,\"probability\":0.8641496669607743,\"preference_score\":100,\"composite_score\":0.9184898001764646},{\"stand\":\"B1\",\"rank\":2,\"probability\":0.09570621504326975,\"preference_score\":95,\"composite_score\":0.4374237290259618}],\"availability\":{\"available\":[\"A0\",\"A1\",\"A2\",\"B1\",\"B2\",\"B6\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B11\",\"B10\",\"B8\",\"B3\",\"A3\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\"],\"timestamp\":\"2025-10-30T08:29:17+01:00\"},\"preferences\":{\"B2\":100,\"B1\":95,\"B3\":90,\"A3\":85},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model_rf_redo.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_aircraft_size.pkl\",\"enc_operator_airline.pkl\",\"enc_airline_tier.pkl\",\"enc_category.pkl\",\"enc_stand_zone.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.6252408477842004,\"top3_accuracy_percent\":\"62.5%\",\"model_timestamp\":\"2025-10-27T05:15:20.489073Z\",\"model_version\":\"v2.0\",\"model_training_date\":\"2025-10-30\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 62.5% (target 70%).\",\"input\":{\"aircraft_type\":\"B 738\",\"operator_airline\":\"GARUDA\",\"category\":\"Komersial\"}}', 'v2.0', 2, 2, 'B2', 1, '2025-10-30 14:29:32', NULL),
(15, '78737d3f3e6dbc2afb585a5180d4ac6b', '2025-10-30 14:34:17', 'B 733', 'JAYAWIJAYA', 'CARGO', '[{\"stand\":\"B11\",\"probability\":0.27519334345903773,\"rank\":1},{\"stand\":\"B10\",\"probability\":0.26645118461152323,\"rank\":2},{\"stand\":\"B13\",\"probability\":0.20804034736553048,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"B13\",\"rank\":1,\"probability\":0.20804034736553048,\"preference_score\":50,\"composite_score\":0.3248242084193183}],\"availability\":{\"available\":[\"A0\",\"A1\",\"A2\",\"B1\",\"B6\",\"B12\",\"B13\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B2\",\"B11\",\"B10\",\"B8\",\"B3\",\"A3\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\"],\"timestamp\":\"2025-10-30T08:34:17+01:00\"},\"preferences\":{\"B11\":100,\"B12\":50,\"RE05\":50,\"A3\":50,\"B13\":50,\"B1\":50,\"B7\":50,\"B10\":50},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model_rf_redo.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_aircraft_size.pkl\",\"enc_operator_airline.pkl\",\"enc_airline_tier.pkl\",\"enc_category.pkl\",\"enc_stand_zone.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.6252408477842004,\"top3_accuracy_percent\":\"62.5%\",\"model_timestamp\":\"2025-10-27T05:15:20.489073Z\",\"model_version\":\"v2.0\",\"model_training_date\":\"2025-10-30\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 62.5% (target 70%).\",\"input\":{\"aircraft_type\":\"B 733\",\"operator_airline\":\"JAYAWIJAYA\",\"category\":\"Cargo\"}}', 'v2.0', 2, 2, 'B13', 1, '2025-10-30 14:34:26', NULL),
(16, '84c2ab1215dba00b212c17e129751b80', '2025-10-30 14:43:54', 'B 733', 'SUSI AIR', 'CARGO', '[{\"stand\":\"B10\",\"probability\":0.2271198168617201,\"rank\":1},{\"stand\":\"B11\",\"probability\":0.21962042988396033,\"rank\":2},{\"stand\":\"B13\",\"probability\":0.1924012121559615,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"A0\",\"rank\":1,\"probability\":null,\"preference_score\":0,\"composite_score\":0},{\"stand\":\"A1\",\"rank\":2,\"probability\":null,\"preference_score\":0,\"composite_score\":0},{\"stand\":\"A2\",\"rank\":3,\"probability\":null,\"preference_score\":0,\"composite_score\":0}],\"availability\":{\"available\":[\"A0\",\"A1\",\"A2\",\"B1\",\"B6\",\"B12\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B2\",\"B13\",\"B11\",\"B10\",\"B8\",\"B3\",\"A3\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\"],\"timestamp\":\"2025-10-30T08:43:54+01:00\"},\"preferences\":{\"B13\":100,\"B11\":100,\"B12\":50,\"RE05\":50,\"A3\":50,\"B1\":50,\"B7\":50,\"B10\":50},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model_rf_redo.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_aircraft_size.pkl\",\"enc_operator_airline.pkl\",\"enc_airline_tier.pkl\",\"enc_category.pkl\",\"enc_stand_zone.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.6252408477842004,\"top3_accuracy_percent\":\"62.5%\",\"model_timestamp\":\"2025-10-27T05:15:20.489073Z\",\"model_version\":\"v2.0\",\"model_training_date\":\"2025-10-30\"},\"source\":\"fallback\",\"notes\":\"Model predictions were filtered out by availability; provided fallback stands. Latest evaluated top-3 accuracy: 62.5% (target 70%).\",\"input\":{\"aircraft_type\":\"B 733\",\"operator_airline\":\"SUSI AIR\",\"category\":\"Cargo\"}}', 'v2.0', 2, NULL, NULL, NULL, NULL, NULL),
(17, 'e8eed2376b1ba49c33a861da7fb38b3a', '2025-10-30 14:44:12', 'B 733', 'TRIGANA', 'CARGO', '[{\"stand\":\"B12\",\"probability\":0.2981438842696607,\"rank\":1},{\"stand\":\"B11\",\"probability\":0.23481582369707582,\"rank\":2},{\"stand\":\"B13\",\"probability\":0.2286397279139676,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"B12\",\"rank\":1,\"probability\":0.2981438842696607,\"preference_score\":95,\"composite_score\":0.5588863305617964},{\"stand\":\"A0\",\"probability\":null,\"preference_score\":0,\"composite_score\":0},{\"stand\":\"A1\",\"probability\":null,\"preference_score\":0,\"composite_score\":0}],\"availability\":{\"available\":[\"A0\",\"A1\",\"A2\",\"B1\",\"B6\",\"B12\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B2\",\"B13\",\"B11\",\"B10\",\"B8\",\"B3\",\"A3\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\"],\"timestamp\":\"2025-10-30T08:44:12+01:00\"},\"preferences\":{\"B13\":100,\"B12\":95,\"B11\":90,\"B10\":85,\"B9\":80},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model_rf_redo.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_aircraft_size.pkl\",\"enc_operator_airline.pkl\",\"enc_airline_tier.pkl\",\"enc_category.pkl\",\"enc_stand_zone.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.6252408477842004,\"top3_accuracy_percent\":\"62.5%\",\"model_timestamp\":\"2025-10-27T05:15:20.489073Z\",\"model_version\":\"v2.0\",\"model_training_date\":\"2025-10-30\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 62.5% (target 70%).\",\"input\":{\"aircraft_type\":\"B 733\",\"operator_airline\":\"TRIGANA\",\"category\":\"Cargo\"}}', 'v2.0', 2, NULL, NULL, NULL, NULL, NULL),
(18, '948f0957d72a1134b54eff17b0336d0c', '2025-10-30 14:52:16', 'B 733', 'TRIGANA', 'CARGO', '[{\"stand\":\"B12\",\"probability\":0.2981438842696607,\"rank\":1},{\"stand\":\"B11\",\"probability\":0.23481582369707588,\"rank\":2},{\"stand\":\"B13\",\"probability\":0.2286397279139676,\"rank\":3}]', '{\"candidates\":[{\"stand\":\"B12\",\"rank\":1,\"probability\":0.2981438842696607,\"preference_score\":95,\"composite_score\":0.5588863305617964},{\"stand\":\"A1\",\"probability\":null,\"preference_score\":0,\"composite_score\":0},{\"stand\":\"A2\",\"probability\":null,\"preference_score\":0,\"composite_score\":0}],\"availability\":{\"available\":[\"A0\",\"A1\",\"A2\",\"B1\",\"B6\",\"B12\",\"SA01\",\"SA02\",\"SA03\",\"SA04\",\"SA06\",\"SA07\",\"SA08\",\"SA09\",\"SA10\",\"SA11\",\"SA12\",\"SA13\",\"SA14\",\"SA17\",\"SA18\",\"SA19\",\"SA21\",\"SA22\",\"SA24\",\"SA28\",\"SA29\",\"SA30\",\"NSA01\",\"NSA02\",\"NSA03\",\"NSA04\",\"NSA05\",\"NSA06\",\"NSA07\",\"NSA08\",\"NSA09\",\"NSA10\",\"NSA11\",\"NSA12\",\"NSA13\",\"NSA14\",\"WR02\",\"RE01\",\"RE02\",\"RE03\",\"RE04\",\"RE06\",\"RE07\",\"RW01\",\"RW03\",\"RW04\",\"RW05\",\"RW07\",\"RW08\",\"RW09\",\"RW10\",\"C1\",\"C2\",\"HGR\"],\"occupied\":[\"B2\",\"B13\",\"B11\",\"B10\",\"B8\",\"B3\",\"A3\",\"RW11\",\"B5\",\"B4\",\"B9\",\"SA27\",\"SA23\",\"RE05\",\"WR03\",\"WR01\",\"SA15\",\"SA16\",\"C3\",\"RW06\",\"B7\",\"SA26\",\"SA25\",\"RW02\",\"NSA15\",\"SA05\",\"SA20\"],\"timestamp\":\"2025-10-30T08:52:16+01:00\"},\"preferences\":{\"B13\":100,\"B12\":95,\"B11\":90,\"B10\":85,\"B9\":80},\"metadata\":{\"model_path\":\"C:\\\\xampp\\\\htdocs\\\\amc\\\\ml\\\\parking_stand_model_rf_redo.pkl\",\"encoder_versions\":[\"enc_aircraft_type.pkl\",\"enc_aircraft_size.pkl\",\"enc_operator_airline.pkl\",\"enc_airline_tier.pkl\",\"enc_category.pkl\",\"enc_stand_zone.pkl\",\"enc_parking_stand.pkl\"],\"top_k_requested\":3,\"top3_accuracy\":0.6252408477842004,\"top3_accuracy_percent\":\"62.5%\",\"model_timestamp\":\"2025-10-27T05:15:20.489073Z\",\"model_version\":\"v2.0\",\"model_training_date\":\"2025-10-30\"},\"source\":\"model\",\"notes\":\"Recommendations filtered by availability and airline preferences. Latest evaluated top-3 accuracy: 62.5% (target 70%).\",\"input\":{\"aircraft_type\":\"B 733\",\"operator_airline\":\"TRIGANA\",\"category\":\"Cargo\"}}', 'v2.0', 2, 2, 'B12', 1, '2025-10-30 14:52:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `narrative_logbook_amc`
--

CREATE TABLE `narrative_logbook_amc` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `log_date` date NOT NULL,
  `shift` varchar(20) NOT NULL,
  `log_time` time NOT NULL,
  `activity_description` text NOT NULL,
  `entered_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stands`
--

CREATE TABLE `stands` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stand_name` varchar(20) NOT NULL,
  `section` varchar(50) NOT NULL COMMENT 'e.g., Main Apron, South Apron, HGR',
  `x_coord` int(11) DEFAULT NULL COMMENT 'X coordinate for rendering. NULL for logical containers like Hangar.',
  `y_coord` int(11) DEFAULT NULL COMMENT 'Y coordinate for rendering. NULL for logical containers like Hangar.',
  `capacity` int(11) NOT NULL DEFAULT 1 COMMENT 'Number of aircraft the stand can hold. >1 for Hangar.',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stands`
--

INSERT INTO `stands` (`id`, `stand_name`, `section`, `x_coord`, `y_coord`, `capacity`, `is_active`) VALUES
(1, 'A0', 'MAIN APRON', 1785, 923, 1, 0),
(2, 'A1', 'MAIN APRON', 1712, 923, 1, 0),
(3, 'A2', 'MAIN APRON', 1621, 923, 1, 0),
(4, 'A3', 'MAIN APRON', 1518, 923, 1, 0),
(5, 'B1', 'MAIN APRON', 1414, 923, 1, 0),
(6, 'B2', 'MAIN APRON', 1321, 923, 1, 0),
(7, 'B3', 'MAIN APRON', 1229, 923, 1, 0),
(8, 'B4', 'MAIN APRON', 1136, 923, 1, 0),
(9, 'B5', 'MAIN APRON', 1043, 923, 1, 0),
(10, 'B6', 'MAIN APRON', 950, 923, 1, 0),
(11, 'B7', 'MAIN APRON', 859, 923, 1, 0),
(12, 'B8', 'MAIN APRON', 768, 923, 1, 0),
(13, 'B9', 'MAIN APRON', 673, 923, 1, 0),
(14, 'B10', 'MAIN APRON', 577, 923, 1, 0),
(15, 'B11', 'MAIN APRON', 483, 923, 1, 0),
(16, 'B12', 'MAIN APRON', 394, 923, 1, 0),
(17, 'B13', 'MAIN APRON', 306, 923, 1, 0),
(18, 'SA01', 'SOUTH APRON', 152, 125, 1, 0),
(19, 'SA02', 'SOUTH APRON', 365, 125, 1, 0),
(20, 'SA03', 'SOUTH APRON', 578, 125, 1, 0),
(21, 'SA04', 'SOUTH APRON', 791, 125, 1, 0),
(22, 'SA05', 'SOUTH APRON', 1004, 125, 1, 0),
(23, 'SA06', 'SOUTH APRON', 1218, 125, 1, 0),
(24, 'SA07', 'SOUTH APRON', 87, 250, 1, 0),
(25, 'SA08', 'SOUTH APRON', 210, 250, 1, 0),
(26, 'SA09', 'SOUTH APRON', 300, 250, 1, 0),
(27, 'SA10', 'SOUTH APRON', 423, 250, 1, 0),
(28, 'SA11', 'SOUTH APRON', 514, 250, 1, 0),
(29, 'SA12', 'SOUTH APRON', 635, 250, 1, 0),
(30, 'SA13', 'SOUTH APRON', 726, 250, 1, 0),
(31, 'SA14', 'SOUTH APRON', 849, 250, 1, 0),
(32, 'SA15', 'SOUTH APRON', 940, 250, 1, 0),
(33, 'SA16', 'SOUTH APRON', 1062, 250, 1, 0),
(34, 'SA17', 'SOUTH APRON', 1153, 250, 1, 0),
(35, 'SA18', 'SOUTH APRON', 1275, 250, 1, 0),
(36, 'SA19', 'SOUTH APRON', 87, 399, 1, 0),
(37, 'SA20', 'SOUTH APRON', 208, 399, 1, 0),
(38, 'SA21', 'SOUTH APRON', 300, 399, 1, 0),
(39, 'SA22', 'SOUTH APRON', 421, 399, 1, 0),
(40, 'SA23', 'SOUTH APRON', 513, 399, 1, 0),
(41, 'SA24', 'SOUTH APRON', 635, 399, 1, 0),
(42, 'SA25', 'SOUTH APRON', 726, 399, 1, 0),
(43, 'SA26', 'SOUTH APRON', 848, 399, 1, 0),
(44, 'SA27', 'SOUTH APRON', 939, 399, 1, 0),
(45, 'SA28', 'SOUTH APRON', 1061, 399, 1, 0),
(46, 'SA29', 'SOUTH APRON', 1153, 399, 1, 0),
(47, 'SA30', 'SOUTH APRON', 1275, 399, 1, 0),
(48, 'NSA01', 'NEW SOUTH APRON', 1460, 146, 1, 0),
(49, 'NSA02', 'NEW SOUTH APRON', 1520, 146, 1, 0),
(50, 'NSA03', 'NEW SOUTH APRON', 1584, 146, 1, 0),
(51, 'NSA04', 'NEW SOUTH APRON', 1643, 146, 1, 0),
(52, 'NSA05', 'NEW SOUTH APRON', 1702, 146, 1, 0),
(53, 'NSA06', 'NEW SOUTH APRON', 1761, 146, 1, 0),
(54, 'NSA07', 'NEW SOUTH APRON', 1819, 146, 1, 0),
(55, 'NSA08', 'NEW SOUTH APRON', 1883, 180, 1, 0),
(56, 'NSA09', 'NEW SOUTH APRON', 1883, 293, 1, 0),
(57, 'NSA10', 'NEW SOUTH APRON', 1520, 328, 1, 0),
(58, 'NSA11', 'NEW SOUTH APRON', 1584, 328, 1, 0),
(59, 'NSA12', 'NEW SOUTH APRON', 1643, 328, 1, 0),
(60, 'NSA13', 'NEW SOUTH APRON', 1702, 328, 1, 0),
(61, 'NSA14', 'NEW SOUTH APRON', 1761, 328, 1, 0),
(62, 'NSA15', 'NEW SOUTH APRON', 1819, 328, 1, 0),
(63, 'WR01', 'MAIN APRON', 115, 627, 1, 0),
(64, 'WR02', 'MAIN APRON', 115, 784, 1, 0),
(65, 'WR03', 'MAIN APRON', 115, 941, 1, 0),
(66, 'RE01', 'MAIN APRON', 703, 700, 1, 0),
(67, 'RE02', 'MAIN APRON', 637, 700, 1, 0),
(68, 'RE03', 'MAIN APRON', 568, 700, 1, 0),
(69, 'RE04', 'MAIN APRON', 499, 700, 1, 0),
(70, 'RE05', 'MAIN APRON', 431, 700, 1, 0),
(71, 'RE06', 'MAIN APRON', 363, 700, 1, 0),
(72, 'RE07', 'MAIN APRON', 296, 700, 1, 0),
(73, 'RW01', 'MAIN APRON', 1647, 700, 1, 0),
(74, 'RW02', 'MAIN APRON', 1580, 700, 1, 0),
(75, 'RW03', 'MAIN APRON', 1513, 700, 1, 0),
(76, 'RW04', 'MAIN APRON', 1446, 700, 1, 0),
(77, 'RW05', 'MAIN APRON', 1379, 700, 1, 0),
(78, 'RW06', 'MAIN APRON', 1307, 700, 1, 0),
(79, 'RW07', 'MAIN APRON', 1241, 700, 1, 0),
(80, 'RW08', 'MAIN APRON', 1173, 700, 1, 0),
(81, 'RW09', 'MAIN APRON', 1107, 700, 1, 0),
(82, 'RW10', 'MAIN APRON', 1039, 700, 1, 0),
(83, 'RW11', 'MAIN APRON', 970, 700, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL COMMENT 'e.g., admin, operator, viewer',
  `status` enum('active','suspended') DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `must_change_password` tinyint(1) DEFAULT 0,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `status`, `last_login_at`, `must_change_password`, `full_name`, `email`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'system', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NULL, 0, 'System User', 'system@localhost', 1, '2025-07-09 07:35:16', '2025-07-09 07:35:16'),
(2, 'admingpt', '$2y$10$.AQ5AXg5eXZSET9DSC8BVOKuXA5o.xBNLDk4qOREy85P2FOofxvEC', 'admin', 'active', NULL, 0, 'admin', 'admin@amc.local', 1, '2025-08-29 15:39:44', '2025-08-31 15:19:27'),
(3, 'operatorgpt', '$argon2id$v=19$m=65536,t=4,p=1$eFdORkpGRmdZNWoyS3E0cg$xeuHlQxx8MxQH0gRWi1BzhFeeiB/+Ihl8Xjk1SPbMVY', 'operator', 'active', NULL, 1, 'operator', 'operator@amc.local', 1, '2025-08-29 15:39:44', '2025-09-20 15:03:09'),
(4, 'viewergpt', '$2y$10$Xdb7CcfozdzeDVgplX0Hz.Em/9W5J/cr.BGkKhBGIQc1r2ik1CJae', 'viewer', 'active', NULL, 0, NULL, 'viewer@amc.local', 1, '2025-08-29 15:39:44', '2025-08-29 15:39:44'),
(5, 'syarifadriann', '$argon2id$v=19$m=65536,t=4,p=1$ZXNxTG13SHB3amxRRmVWVw$Pcl0y73J9ZQ+o3oYUNI7Ev55WZGyds9BL8KjX56yIZQ', 'viewer', 'active', NULL, 1, 'syarif adrian mangaraja lubis', 'syarifadrian9@gmail.com', 1, '2025-08-31 14:13:04', '2025-09-07 07:39:30'),
(6, 'amc', '$argon2id$v=19$m=65536,t=4,p=1$MzNnQUxFSnBxUi9YbjNyaQ$07+lwC2N7kH/nAIJUimOSEG029hIpfsEYS85wCg6c+Y', 'operator', 'active', NULL, 1, 'mike charlie', 'amchlp@gmail.com', 1, '2025-08-31 15:20:21', '2025-09-20 15:19:47'),
(7, 'asepjengki', '$argon2id$v=19$m=65536,t=4,p=1$NWIzNndwQWxnSUhkU1NRdg$NTtieUlwvhHrQK/c+CBNoPMLI+JocR5nlO5qg76icwM', 'viewer', 'active', NULL, 0, 'asep', 'geeg@gmail.com', 1, '2025-09-20 15:02:44', '2025-09-20 15:02:44'),
(9, 'keyranti', '$argon2id$v=19$m=65536,t=4,p=1$a2dXdzlPWmtZc0ZSNjZxYg$tBchae6slqtDer1ONSTabPnHktYNyOvvAYLVwgEwLp8', 'admin', 'active', NULL, 1, 'key', 'key@hotmail.com', 1, '2025-09-20 15:10:23', '2025-09-20 15:17:40'),
(10, 'ATC', '$argon2id$v=19$m=65536,t=4,p=1$UjkuTC4wUTJTMndTVTIubQ$8KkKNtrHNgnIoPT3NntTnT5lCJBqkKuMpYd0+CRHWM4', 'viewer', 'active', NULL, 1, 'Airnav', 'atc@gmail.com', 1, '2025-10-03 04:10:57', '2025-10-03 04:10:57'),
(11, 'admin1', '$argon2id$v=19$m=65536,t=4,p=1$Y2hVTXJiZXQ3T0dVeW1yTg$/ADSr5ph4pnZf5BaVjY92CG8dE8hcGJl4OeJSTpuuYg', 'admin', 'active', NULL, 0, 'admidn', 'admin@amc.localawe', 1, '2025-10-25 13:54:30', '2025-10-25 13:54:30'),
(12, 'testuser', '$argon2id$v=19$m=65536,t=4,p=1$SjJMSWlzTlpQRlBrQkdqQw$jfrAlDXcPiFhr7M9Csh7ynYz+IU7IkWB699eHNA35Rg', 'admin', 'active', NULL, 0, 'Test User', 'testuser@amc.local', 1, '2025-10-26 05:56:25', '2025-10-26 05:56:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `aircraft_details`
--
ALTER TABLE `aircraft_details`
  ADD PRIMARY KEY (`registration`);

--
-- Indexes for table `aircraft_movements`
--
ALTER TABLE `aircraft_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aircraft_movements_registration_index` (`registration`),
  ADD KEY `aircraft_movements_movement_date_index` (`movement_date`),
  ADD KEY `aircraft_movements_parking_stand_index` (`parking_stand`),
  ADD KEY `aircraft_movements_user_id_created_foreign` (`user_id_created`),
  ADD KEY `aircraft_movements_user_id_updated_foreign` (`user_id_updated`),
  ADD KEY `idx_on_block_date` (`on_block_date`),
  ADD KEY `idx_off_block_date` (`off_block_date`),
  ADD KEY `idx_ron_complete` (`ron_complete`),
  ADD KEY `idx_movement_date_ron` (`movement_date`,`is_ron`,`ron_complete`);

--
-- Indexes for table `airline_preferences`
--
ALTER TABLE `airline_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_preference` (`airline_name`,`aircraft_type`,`stand_name`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_log_user_id_foreign` (`user_id`);

--
-- Indexes for table `daily_snapshots`
--
ALTER TABLE `daily_snapshots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_snapshot_date` (`snapshot_date`),
  ADD KEY `daily_snapshots_created_by_user_id_foreign` (`created_by_user_id`);

--
-- Indexes for table `daily_staff_roster`
--
ALTER TABLE `daily_staff_roster`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `flight_references`
--
ALTER TABLE `flight_references`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flight_references_flight_no_index` (`flight_no`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`);

--
-- Indexes for table `ml_model_versions`
--
ALTER TABLE `ml_model_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ml_model_versions_version_number_unique` (`version_number`),
  ADD KEY `ml_model_versions_created_by_foreign` (`created_by`);

--
-- Indexes for table `ml_prediction_log`
--
ALTER TABLE `ml_prediction_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ml_prediction_log_token_unique` (`prediction_token`),
  ADD KEY `ml_prediction_log_model_version_index` (`model_version`),
  ADD KEY `ml_prediction_log_requested_by_foreign` (`requested_by_user`),
  ADD KEY `ml_prediction_log_assigned_by_foreign` (`assigned_by_user`);

--
-- Indexes for table `narrative_logbook_amc`
--
ALTER TABLE `narrative_logbook_amc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `narrative_logbook_amc_entered_by_user_id_foreign` (`entered_by_user_id`);

--
-- Indexes for table `stands`
--
ALTER TABLE `stands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stands_stand_name_unique` (`stand_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `aircraft_movements`
--
ALTER TABLE `aircraft_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `airline_preferences`
--
ALTER TABLE `airline_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=174;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=254;

--
-- AUTO_INCREMENT for table `daily_snapshots`
--
ALTER TABLE `daily_snapshots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `daily_staff_roster`
--
ALTER TABLE `daily_staff_roster`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `flight_references`
--
ALTER TABLE `flight_references`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=282;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `ml_model_versions`
--
ALTER TABLE `ml_model_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ml_prediction_log`
--
ALTER TABLE `ml_prediction_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `narrative_logbook_amc`
--
ALTER TABLE `narrative_logbook_amc`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stands`
--
ALTER TABLE `stands`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `aircraft_movements`
--
ALTER TABLE `aircraft_movements`
  ADD CONSTRAINT `aircraft_movements_user_id_created_foreign` FOREIGN KEY (`user_id_created`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `aircraft_movements_user_id_updated_foreign` FOREIGN KEY (`user_id_updated`) REFERENCES `users` (`id`);

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `daily_snapshots`
--
ALTER TABLE `daily_snapshots`
  ADD CONSTRAINT `daily_snapshots_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `ml_model_versions`
--
ALTER TABLE `ml_model_versions`
  ADD CONSTRAINT `ml_model_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `ml_prediction_log`
--
ALTER TABLE `ml_prediction_log`
  ADD CONSTRAINT `ml_prediction_log_assigned_by_foreign` FOREIGN KEY (`assigned_by_user`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `ml_prediction_log_requested_by_foreign` FOREIGN KEY (`requested_by_user`) REFERENCES `users` (`id`);

--
-- Constraints for table `narrative_logbook_amc`
--
ALTER TABLE `narrative_logbook_amc`
  ADD CONSTRAINT `narrative_logbook_amc_entered_by_user_id_foreign` FOREIGN KEY (`entered_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
