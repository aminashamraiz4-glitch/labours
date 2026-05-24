-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2025 at 04:32 AM
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
-- Database: `labour_booking`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `labour_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('booked','completed','cancelled') DEFAULT 'booked',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `name`, `email`, `password`, `location`, `created_at`) VALUES
(1, 'Hamza', 'hamza123@gmail.com', '$2y$10$7Ns20BL9xD3H/ORH.EyrlecJzAvK8GENKhrXjewGX6epWxBbQqSiu', 'Cicil lines, jhelum', '2025-10-28 15:33:26');

-- --------------------------------------------------------

--
-- Table structure for table `labours`
--

CREATE TABLE `labours` (
  `labour_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `skill` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `availability_start` time DEFAULT NULL,
  `availability_end` time DEFAULT NULL,
  `verified_status` enum('verified','unverified') DEFAULT 'unverified',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `labours`
--

INSERT INTO `labours` (`labour_id`, `name`, `email`, `phone`, `password`, `skill`, `image`, `location`, `availability_start`, `availability_end`, `verified_status`, `created_at`) VALUES
(2, 'Reman Ali', 'rehman123@gmail.com', '0333 3333333', '$2y$10$H9kO6ENpcMTZCUgtB5zrsueEr8lcz8OAkfIdCDQWBBQbaNPohXtTC', 'Mechanic', 'labour_1761593014_4072.jpg', 'Cicil lines, jhelum', '10:00:00', '17:00:00', 'unverified', '2025-10-27 19:23:34'),
(3, 'Farhan Saleem', 'farhan123@gmail.com', '0313 1111111', '$2y$10$RL6XSQo3JrxoAzxuQmgZreybPK6a2iALN8Usm9sLOXfZJLnLmiJ6S', 'Plumber', 'labour_1761593256_8175.jpg', 'House No. 90, Near Govt. Girls High School, GT Road, Dina', '12:00:00', '18:00:00', 'unverified', '2025-10-27 19:27:36'),
(4, 'Saleem Mirza', 'saleem786@gmail.com', '0319 5555555', '$2y$10$Y4VamSW69R8EaFEyvqrQKOsfHf8o4Lg0G4Vy83dcTkpjhoUnte6qy', 'Labour', 'labour_1761593518_7606.jpg', 'House No. 25, Street 2, Al-Noor Town, Jhelum', '09:00:00', '17:00:00', 'unverified', '2025-10-27 19:31:58'),
(5, 'Aqib Butt', 'aqibbutt@gmail.com', '0333 xxxxxxx', '$2y$10$zetdvD3sSEOhq.78sfBYEOr3JiGsMWd5q5IkfdV5rL7Gu.asOpXWG', 'Electrician', 'labour_1761629380_5127.jpg', 'House No. 47, Street 10, Muhammadi Colony, Jhelum', '00:00:00', '20:00:00', 'unverified', '2025-10-28 05:29:40'),
(6, 'Hamza Butt', 'hamza123@gmail.com', '0319 9875646', '$2y$10$/hgYGEOaXPWwxu26usDjqOhFO4Wklhj82.Tqo/2gvpXdPEHzFsKZ6', 'Painter', 'labour_1761629581_4176.jpg', 'House No. 40, Street 6, Gulshan-e-Rehman, Jhelum', '09:00:00', '18:00:00', 'unverified', '2025-10-28 05:33:01'),
(7, 'Bashir Ahmed', 'bashir786@gmail.com', '0331 8987666', '$2y$10$yQ/nY1i0544dvPY31jm.3Og.jLY.vN8MBKNEfwQP9GHqgMRlozrK6', 'Labour', 'labour_1761631877_2238.jpg', 'House No. 71, Street 11, Gulshan Colony, Dina', '10:00:00', '18:00:00', 'unverified', '2025-10-28 06:11:17'),
(8, 'Anwar Ahmed', 'Anwarali@gmail.com', '0319 6574468', '$2y$10$4WBSvqYucKh2SsIrQluY9eF4ert8rqpgTlyKSeILcnAxJ1jOhfeDq', 'Plumber', 'labour_1761631975_5372.jpg', 'Plot No. 208, Street 9, Rehman Town, Dina', '10:00:00', '18:00:00', 'unverified', '2025-10-28 06:12:55'),
(9, 'Muhammad Aslam', 'maslam@gmail.com', '0319 8756666', '$2y$10$AO7cfhTni77tzwSEsRsWGOxvnR9j/QICRqYJ9xWRapDBa3wUyOHfe', 'Labour', 'labour_1761632087_8209.jpg', 'dina, jhelum', '10:00:00', '18:00:00', 'unverified', '2025-10-28 06:14:47'),
(10, 'Shaukat Ali', 'alishaukat@gmail.com', '0319 8887779', '$2y$10$pb1yKkJ5XpJ3imw.4A99iehU0AQOUTLuxp3aSyVyBroDfwF6lszyq', 'Painter', 'labour_1761632170_2449.jpg', 'House No. 47, Street 10, Muhammadi Colony, Jhelum', '11:00:00', '18:00:00', 'unverified', '2025-10-28 06:16:10'),
(11, 'Babar Ali', 'babar123@gmail.com', '0331 9999999', '$2y$10$D48nABzTZT2sFYjcwZWMrubeKex.YB73COwcCWKVFxG5encvPlX8y', 'Plumber', 'labour_1761632267_2380.jpg', 'Mohalla Saeedia, Near Railway Road, Dina', '10:00:00', '16:00:00', 'unverified', '2025-10-28 06:17:47'),
(12, 'Mushtaq Hussain', 'hussainm@gmail.com', '0331 7654567', '$2y$10$e8quPYoD6DzCPbX/JxSAaeGOuk4fVom.J9E6gAP6/BQ0.Kd50lUGG', 'Electrician', 'labour_1761632514_6207.jpg', 'Street 1, Al-Hafeez Town, Dina', '10:00:00', '16:00:00', 'unverified', '2025-10-28 06:21:54'),
(13, 'Ghulam Rasool', 'rasoolg@gmail.com', '0319 6756676', '$2y$10$2nueZ94lb2QF3151yjSkEOqbW55tjMvq2gmpGT5aQeVy7Hzc.3J16', 'Painter', 'labour_1761632610_1661.jpg', 'Near Sabzi Mandi, Main Bazaar, Dina', '10:00:00', '20:00:00', 'unverified', '2025-10-28 06:23:30');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_labour_booking` (`labour_id`),
  ADD KEY `fk_customer_booking` (`customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `labours`
--
ALTER TABLE `labours`
  ADD PRIMARY KEY (`labour_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `fk_booking_review` (`booking_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `labours`
--
ALTER TABLE `labours`
  MODIFY `labour_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_customer_booking` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_labour_booking` FOREIGN KEY (`labour_id`) REFERENCES `labours` (`labour_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_booking_review` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
