-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 25, 2026 at 05:07 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `project`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `aid` int(11) NOT NULL,
  `afname` varchar(100) NOT NULL,
  `alname` varchar(100) NOT NULL,
  `phone` char(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cnic` char(13) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `password` varchar(100) NOT NULL,
  `profile_img` varchar(255) DEFAULT 'default.png',
  `status` enum('Active','Blocked') DEFAULT 'Active',
  `user_role` varchar(20) NOT NULL DEFAULT 'user',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verify_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`aid`, `afname`, `alname`, `phone`, `email`, `cnic`, `dob`, `username`, `gender`, `password`, `profile_img`, `status`, `user_role`, `is_verified`, `verify_token`, `token_expiry`, `reset_token`, `reset_expiry`) VALUES
(1, 'Super', 'Admin', '012345678', 'super@admin.com', '', '2023-05-03', 'admin', 'M', 'admin123', '', 'Active', 'superadmin', 0, NULL, NULL, NULL, NULL),
(21, 'Md.', 'Rahman', '01700000011', 'mdmashiurpolash31@gmail.com', '1678945367128', '2026-02-07', 'M32425', 'M', 'M32425', 'default.png', 'Active', 'user', 0, NULL, NULL, NULL, NULL),
(29, 'mashiur', 'rahman', '01715618520', 'mashiur@gmail.com', NULL, NULL, 'mashiur', NULL, 'mashiur123', 'default.png', 'Active', 'operator', 0, NULL, NULL, NULL, NULL),
(30, 'new', 'user', '01867985298', 'rahman12@gmail.com', NULL, NULL, 'user1', NULL, 'user123', 'default.png', 'Active', 'admin', 0, NULL, NULL, NULL, NULL),
(31, 'Sowrov', 'Hossen', '14754681245', 'sowrov@gmail.com', '3654712589654', '1998-06-09', 'sowrov', 'M', 'sowrov123', 'default.png', 'Active', 'user', 0, NULL, NULL, NULL, NULL),
(32, 'polash', 'ahmed', '01745186421', 'polash@gmail.com', '3214758964125', '1996-06-12', 'polash', 'M', 'polash123', 'default.png', 'Active', 'user', 0, NULL, NULL, NULL, NULL),
(33, 'mosiur', 'rahman', '34532987654', 'mosiur@gmail.com', '0987654567894', '2026-01-01', 'mosiur', 'M', 'mosiur123', 'default.png', 'Active', 'user', 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `aid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `cqty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `cat_id` int(11) NOT NULL,
  `cat_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`cat_id`, `cat_name`) VALUES
(1, 'CPU'),
(5, 'GPU'),
(2, 'Keyboard'),
(470, 'Keyboard & Mouse Combo'),
(4, 'Motherboard'),
(3, 'Mouse'),
(6, 'Ram'),
(469, 'Webcam');

-- --------------------------------------------------------

--
-- Table structure for table `features`
--

CREATE TABLE `features` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `image` varchar(255) NOT NULL,
  `bg_color` varchar(20) DEFAULT '#fddde4'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `features`
--

INSERT INTO `features` (`id`, `title`, `image`, `bg_color`) VALUES
(1, 'Online Order', 'img/features/1770401200_f2.png', '#fddde4'),
(2, 'Free Shipping', 'img/features/1770542000_free shipping.png', '#ffebeb');

-- --------------------------------------------------------

--
-- Table structure for table `hero`
--

CREATE TABLE `hero` (
  `id` int(11) NOT NULL,
  `bg_image` varchar(255) NOT NULL,
  `sub_title` varchar(255) DEFAULT '',
  `main_title` varchar(255) DEFAULT '',
  `big_title` varchar(255) DEFAULT '',
  `description` varchar(255) DEFAULT '',
  `btn_text` varchar(100) DEFAULT 'Shop Now',
  `btn_link` varchar(255) DEFAULT 'shop.php',
  `is_active` tinyint(1) DEFAULT 0,
  `page_name` varchar(50) DEFAULT 'index.php'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hero`
--

INSERT INTO `hero` (`id`, `bg_image`, `sub_title`, `main_title`, `big_title`, `description`, `btn_text`, `btn_link`, `is_active`, `page_name`) VALUES
(7, 'hero_images/1770539143_miles-burke-idhx-MOCDSk-unsplash.jpg', 'Contac Us', 'Contact Us', 'We love to hear from you', '', 'Shop Now', 'shop.php', 1, 'contact.php'),
(8, 'hero_images/1770537576_kaleb-tapp-J59wWPn09BE-unsplash.jpg', 'One Stop Solution', 'Super Value Deals', 'Get what you Want', '', 'Shop Now', 'shop.php', 1, 'index.php'),
(10, 'hero_images/1770538588_lucrezia-carnelos-wQ9VuP_Njr4-unsplash.jpg', 'We Offer', 'Super Value Deals', 'On all products', 'Take Yours', 'Shop Now', 'shop.php', 1, 'shop.php'),
(11, 'hero_images/1770539126_ian-schneider-TamMbr4okv4-unsplash.jpg', 'Shop With Us', 'Passion Led Us here', 'Shop with us', 'Take Yours', 'Shop Now', 'shop.php', 1, 'about.php');

-- --------------------------------------------------------

--
-- Table structure for table `mail_settings`
--

CREATE TABLE `mail_settings` (
  `id` int(11) NOT NULL,
  `mail_driver` varchar(20) DEFAULT 'smtp',
  `mail_host` varchar(255) DEFAULT 'smtp.gmail.com',
  `mail_port` int(5) DEFAULT 587,
  `mail_encryption` varchar(10) DEFAULT 'tls',
  `mail_username` varchar(255) DEFAULT '',
  `mail_password` varchar(500) DEFAULT '',
  `mail_from_address` varchar(255) DEFAULT '',
  `mail_from_name` varchar(255) DEFAULT '',
  `mail_enabled` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mail_settings`
--

INSERT INTO `mail_settings` (`id`, `mail_driver`, `mail_host`, `mail_port`, `mail_encryption`, `mail_username`, `mail_password`, `mail_from_address`, `mail_from_name`, `mail_enabled`) VALUES
(1, 'smtp', 'smtp.gmail.com', 587, 'tls', 'mashiurecse31@gmail.com', 'bggs iyat yimw wqch', 'mashiurecse31@gmail.com', 'Northan E solution', 1);

-- --------------------------------------------------------

--
-- Table structure for table `order-details`
--

CREATE TABLE `order-details` (
  `oid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order-details`
--

INSERT INTO `order-details` (`oid`, `pid`, `price`, `qty`) VALUES
(20, 42, 0.00, 1),
(25, 33, 350.00, 1),
(25, 40, 140.00, 1),
(26, 39, 470.00, 1),
(27, 42, 140.00, 1),
(28, 40, 140.00, 1),
(29, 27, 250.00, 1),
(30, 41, 160.00, 1),
(31, 41, 160.00, 1),
(31, 43, 370.00, 1),
(32, 31, 130.00, 1),
(32, 32, 230.00, 3),
(32, 39, 470.00, 4),
(33, 36, 550.00, 1),
(35, 33, 350.00, 1),
(35, 39, 470.00, 1),
(35, 40, 140.00, 1),
(36, 35, 75.00, 1),
(36, 37, 380.00, 1),
(37, 40, 140.00, 1),
(50, 30, 30000.00, 1),
(51, 36, 176500.00, 1),
(52, 40, 1800.00, 1),
(53, 39, 48200.00, 1),
(54, 41, 5200.00, 1),
(55, 40, 1800.00, 1),
(56, 34, 23500.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `oid` int(11) NOT NULL,
  `dateod` date NOT NULL,
  `datedel` date DEFAULT NULL,
  `status` enum('Pending','Processing','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
  `aid` int(11) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(50) NOT NULL,
  `country` varchar(100) NOT NULL,
  `account` char(16) DEFAULT NULL,
  `total` int(11) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'cod' COMMENT 'Payment method used: cod, stripe, or bkash',
  `payment_status` varchar(50) DEFAULT 'pending' COMMENT 'Payment status: pending, paid, failed, refunded',
  `transaction_id` varchar(255) DEFAULT NULL COMMENT 'Transaction ID from payment gateway (Stripe charge ID or bKash trxID)',
  `stock_reduced` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`oid`, `dateod`, `datedel`, `status`, `aid`, `address`, `city`, `country`, `account`, `total`, `payment_method`, `payment_status`, `transaction_id`, `stock_reduced`) VALUES
(20, '2026-02-06', '2026-02-07', 'Delivered', 1, 'SARKAR BARI, NASNAPARA, KALAPARA', 'KHEPUPARA', 'Bangladesh', NULL, 140, 'cod', 'pending', NULL, 0),
(25, '2026-02-07', '2026-02-07', 'Delivered', 21, 'xvjscvh', 'xvs', 'Bangladesh', NULL, 490, 'cod', 'pending', NULL, 1),
(26, '2026-02-07', '2026-02-07', 'Delivered', 21, 'hgjhg', 'lkjhj', 'kjgkjf', NULL, 470, 'cod', 'pending', NULL, 1),
(27, '2026-02-07', '2026-02-07', 'Delivered', 21, 'hgjhg', 'lkjhj', 'kjgkjf', NULL, 140, 'cod', 'pending', NULL, 1),
(28, '2026-02-08', '2026-02-08', 'Delivered', 31, 'hgjhg', 'lkjhj', 'kjgkjf', NULL, 140, 'cod', 'pending', NULL, 1),
(29, '2026-02-08', '2026-02-08', 'Delivered', 31, 'hgjhg', 'lkjhj', 'kjgkjf', NULL, 250, 'cod', 'pending', NULL, 1),
(30, '2026-02-08', '2026-02-11', 'Delivered', 31, 'hgjhg', 'lkjhj', 'kjgkjf', NULL, 160, 'cod', 'pending', NULL, 1),
(31, '2026-02-11', '2026-02-23', 'Delivered', 31, 'mohakjhali', 'dhaka', 'kancha', NULL, 530, 'stripe', 'paid', 'ch_3SzY4fCXjsUGYc1O1t8lrP8B', 1),
(32, '2026-02-11', '2026-02-23', 'Delivered', 31, 'Mohakhali', 'Dhaka', 'Dhaka', NULL, 2700, 'stripe', 'paid', 'ch_3SzY7cCXjsUGYc1O0juwHWsg', 1),
(33, '2026-02-11', '2026-02-23', 'Delivered', 31, 'dhaka', 'dhaka', 'Dhaka', NULL, 550, 'cod', 'pending', NULL, 1),
(35, '2026-02-11', '2026-02-11', 'Delivered', 31, 'mirpur', 'taltola', 'Dhaka', NULL, 960, 'stripe', 'paid', 'ch_3SzaEyCXjsUGYc1O0BRPKKMs', 1),
(36, '2026-02-11', '2026-02-23', 'Delivered', 31, 'mirpur14', 'dhaka', 'Dhaka', NULL, 455, 'cod', 'pending', NULL, 1),
(37, '2026-02-11', '2026-02-11', 'Delivered', 31, 'jiakoloni', 'dhaka', 'Dhaka', NULL, 140, 'stripe', 'paid', 'ch_3SzetVCXjsUGYc1O0mhZsmc6', 1),
(50, '2026-02-16', '2026-02-16', 'Delivered', 31, 'dhaka', 'lkjhj', 'Bangladesh', NULL, 30000, 'stripe', 'paid', 'ch_3T1L5hCXjsUGYc1O0YLApVvU', 1),
(51, '2026-02-23', '2026-02-23', 'Delivered', 32, 'dhaka', 'taltola', 'kancha', NULL, 176500, 'stripe', 'paid', 'ch_3T3rQbCXjsUGYc1O1JKRKFvy', 1),
(52, '2026-02-24', '2026-02-24', 'Delivered', 31, 'dhaka', 'gazipur', 'Bangladesh', NULL, 1800, 'stripe', 'paid', 'ch_3T4Cc2CXjsUGYc1O1PCQr0Sf', 1),
(53, '2026-02-24', NULL, 'Pending', 33, 'dhaka', 'kawla', 'bangladesh', NULL, 48200, 'stripe', 'pending', NULL, 0),
(54, '2026-02-24', NULL, 'Pending', 31, 'dhaka', 'taltola', 'Bangladesh', NULL, 5200, 'stripe', 'pending', NULL, 0),
(55, '2026-02-24', NULL, 'Pending', 31, 'dhaka', 'taltola', 'Bangladesh', NULL, 1800, 'stripe', 'pending', NULL, 0),
(56, '2026-02-24', NULL, 'Pending', 31, 'dhaka', 'taltola', 'Bangladesh', NULL, 23500, 'stripe', 'pending', NULL, 0),
(57, '2026-02-24', NULL, 'Pending', 31, 'dhaka', 'taltola', 'Bangladesh', NULL, 0, 'bkash', 'pending', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `stripe_enabled` tinyint(1) DEFAULT 0,
  `stripe_publishable_key` varchar(255) DEFAULT '',
  `stripe_secret_key` varchar(255) DEFAULT '',
  `bkash_enabled` tinyint(1) DEFAULT 0,
  `bkash_merchant_number` varchar(20) DEFAULT '',
  `bkash_api_key` varchar(255) DEFAULT '',
  `bkash_api_secret` varchar(255) DEFAULT '',
  `bkash_username` varchar(100) DEFAULT '',
  `bkash_password` varchar(100) DEFAULT '',
  `bkash_app_key` varchar(255) DEFAULT '',
  `cod_enabled` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `stripe_enabled`, `stripe_publishable_key`, `stripe_secret_key`, `bkash_enabled`, `bkash_merchant_number`, `bkash_api_key`, `bkash_api_secret`, `bkash_username`, `bkash_password`, `bkash_app_key`, `cod_enabled`) VALUES
(1, 1, 'pk_test_51SzY0kCXjsUGYc1OUxseUicpTQGUYIpnqOwXzVUUO5OijV3bNSg8oan7OzV5WXehdnOXnJBILu2xJ1cDRzSEKg7T00cKUFUkbI', 'sk_test_51SzY0kCXjsUGYc1ODXhc0S0UJfVVKlDE5H2DTE4fc0mratEofWtqYV9adVgk6iqoI3BeMoENuM1xQDdCgVFlFSlX001az5cLa7', 1, '01724569715', NULL, '123456', 'Northern E-Solution', '123456', '123456', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `oid` int(11) NOT NULL COMMENT 'Order ID reference',
  `payment_method` varchar(50) NOT NULL COMMENT 'Payment method: stripe, bkash, cod',
  `transaction_id` varchar(255) DEFAULT NULL COMMENT 'Gateway transaction ID',
  `amount` decimal(10,2) NOT NULL COMMENT 'Transaction amount',
  `currency` varchar(10) DEFAULT 'BDT' COMMENT 'Currency code',
  `status` varchar(50) NOT NULL COMMENT 'Transaction status: pending, completed, failed',
  `gateway_response` text DEFAULT NULL COMMENT 'Full response from payment gateway (JSON)',
  `customer_email` varchar(100) DEFAULT NULL COMMENT 'Customer email',
  `customer_phone` varchar(20) DEFAULT NULL COMMENT 'Customer phone',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'Customer IP address',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Transaction creation timestamp',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last update timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Payment transaction log for audit trail';

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `pid` int(11) NOT NULL,
  `pname` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` varchar(200) NOT NULL,
  `price` int(11) NOT NULL,
  `qtyavail` int(11) NOT NULL,
  `img` varchar(255) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`pid`, `pname`, `category`, `description`, `price`, `qtyavail`, `img`, `brand`, `is_active`) VALUES
(27, 'Core i5 3570', 'CPU', 'Attention all tech enthusiasts! Upgrade your computer performance with the powerful i5 3570 processor! Built on Intel Ivy Bridge architecture, this quad-core processor boasts a base clock speed of 3.4', 2190, 3, 'x14.jpeg', 'Intel', 1),
(30, 'Razer BlackWidow V4 Pro', 'Keyboard', ' Take your gaming experience to the next level with the Razer BlackWidow V4 Pro! This mechanical gaming keyboard features Razer signature green switches, providing tactile feedback and optimized actua', 30000, 38, 'x3.jpeg', 'Razor', 1),
(31, 'Hp Gaming Mouse M160', 'Mouse', ' Dominate your opponents with the HP Gaming Mouse M160! This high-performance mouse features six programmable buttons and a high-precision optical sensor, allowing for quick and accurate movements in ', 400, 18, 'x5.jpeg', 'Hp', 1),
(32, 'Asus  Motherboard B450', 'Motherboard', 'Upgrade your rig with the ASUS B450 motherboard! This motherboard features an AM4 socket that supports AMD Ryzen processors, providing you with the power you need for intense gaming and multitasking. ', 8000, 9, 'x15.jpeg', 'Asus', 1),
(33, 'Ryzen 7 3700x ', 'CPU', 'Experience lightning-fast performance with the AMD Ryzen 7 3700X processor! With 8 cores and 16 threads, this processor delivers unrivaled speed and processing power for demanding tasks, including gam', 15500, 5, 'x6.jpeg', 'Ryzen', 1),
(34, 'Nvidia GTX 1660Ti GPU', 'GPU', 'Take your PC experience to the next level with the NVIDIA GeForce GTX 1660 Ti graphics card! This high-performance graphics card features NVIDIA Turing architecture and 6GB of GDDR6 memory, providing ', 23500, 5, 'x9.jpeg', 'Nvidia', 1),
(35, 'HyperX Fury Ram 16GB', 'Ram', 'Upgrade your PC performance with HyperX Fury RAM! With speeds of up to 3200MHz and capacities ranging from 8GB to 64GB, HyperX Fury RAM is the perfect choice for anyone looking to improve their PC mul', 8500, 22, '71GJY5+c14L._SY450_.jpg', 'HyperX', 1),
(36, 'Geforce RTX 4080 16GB', 'GPU', 'The NVIDIA GeForce RTX 4080 delivers the ultra performance and features that enthusiast gamers and creators demand. Bring your games and creative projects to life with ray tracing and AI-powered graph', 176500, 10, 'lol.jpeg', 'Nvidia', 1),
(37, 'Asus Rog Strix B550-E', 'Motherboard', 'Gamers and PC enthusiasts, elevate your build with the ASUS ROG Strix B550-E Gaming motherboard! Designed with performance in mind, this high-end motherboard features the latest PCIe 4.0 technology, a', 27500, 1, 'rog.jpeg', 'Asus', 1),
(38, 'MageGee Mechanical Gaming Keyboard', 'Keyboard', 'Upgrade your gaming setup with the MageGee Mechanical Gaming Keyboard. Built with high-quality and durable materials, this keyboard features mechanical switches that provide a tactile and satisfying t', 3400, 6, 'no.jpeg', 'MageGee', 1),
(39, 'Intel Core i9-10900K 3.7 GHz ', 'CPU', 'Experience the ultimate performance with the Intel Core i9-10900K 3.7 GHz processor. With 10 cores and 20 threads, this high-end processor delivers blazing-fast speeds and unparalleled multitasking ca', 48200, 9, 'i.jpeg', 'Intel', 1),
(40, 'Redragon Gaming Mouse', 'Mouse', ' Take your gaming to the next level with the RedDragon gaming mouse. This high-performance gaming mouse features an ergonomic design with customizable RGB lighting, making it not only comfortable to u', 1800, 5, 'red.jpeg', 'Redragon', 1),
(41, 'Razer Cynosa V2 RGB Gaming Keyboard ', 'Keyboard', ' The Razer Cynosa V2 RGB Gaming Keyboard is a must-have accessory for any avid gamer looking to take their gaming experience to the next level. With its fully customizable RGB lighting, you can create', 5200, 7, 'r.jpeg', 'Razor', 1),
(42, 'Glorious Model O Gaming Mouse', 'Mouse', 'The Glorious Model O is a gaming mouse that is built to deliver superior performance, accuracy, and speed to gamers of all levels. With its sleek and ergonomic design, this mouse is designed to fit co', 4500, 10, 'g.jpeg', 'Glorious', 1),
(43, 'Geforce RTX 3080 12GB Zotac', 'GPU', 'The GeForce RTX 3080 12GB Zotac is a high-performance graphics card designed for gamers and professionals who require the best in graphical processing power. This graphics card is powered by the NVIDI', 123000, 5, 'Rtx.jpeg', 'Nvidia', 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `pid` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`pid`, `cat_id`) VALUES
(27, 1),
(30, 2),
(31, 3),
(32, 4),
(33, 1),
(34, 5),
(35, 6),
(36, 5),
(37, 4),
(38, 2),
(39, 1),
(40, 3),
(41, 2),
(42, 3),
(43, 5),
(44, 6),
(45, 6),
(46, 5),
(47, 469),
(48, 2),
(49, 2),
(50, 5),
(51, 2),
(52, 470),
(53, 3),
(54, 3),
(55, 3);

-- --------------------------------------------------------

--
-- Table structure for table `product_tags`
--

CREATE TABLE `product_tags` (
  `pid` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_tags`
--

INSERT INTO `product_tags` (`pid`, `tag_id`) VALUES
(42, 5),
(43, 5);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `oid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `rtext` varchar(1000) DEFAULT NULL,
  `rating` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`oid`, `pid`, `rtext`, `rating`) VALUES
(25, 33, 'Best', 5),
(25, 40, 'Good ', 4),
(28, 40, 'nice', 2),
(50, 30, 'Great', 3),
(52, 40, 'good', 3);

-- --------------------------------------------------------

--
-- Table structure for table `slider`
--

CREATE TABLE `slider` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT '',
  `subtitle` varchar(255) DEFAULT '',
  `btn_text` varchar(100) DEFAULT 'Explore More',
  `btn_link` varchar(255) DEFAULT 'shop.php',
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `slider`
--

INSERT INTO `slider` (`id`, `image`, `title`, `subtitle`, `btn_text`, `btn_link`, `sort_order`) VALUES
(5, 'slider_images/1770539811_andrey-matveev-fPGLq62CSXg-unsplash.jpg', '', 'Watch Our Products', 'Explore More', 'shop.php', 0),
(6, 'slider_images/1770539822_andrey-matveev-UbpPW0Xsqlw-unsplash.jpg', '', 'Watch Our Products', 'Explore More', 'shop.php', 0),
(7, 'slider_images/1770539829_clastr-cloud-gaming-aO1OBDrRQg8-unsplash.jpg', '', 'Watch Our Products', 'Explore More', 'shop.php', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `tag_id` int(11) NOT NULL,
  `tag_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`tag_id`, `tag_name`) VALUES
(5, 'Feature');

-- --------------------------------------------------------

--
-- Table structure for table `website_settings`
--

CREATE TABLE `website_settings` (
  `id` int(11) NOT NULL,
  `site_title` varchar(255) DEFAULT 'ByteBazaar',
  `site_tagline` varchar(255) DEFAULT 'Premium Tech Store',
  `logo` varchar(255) DEFAULT 'img/logo.png',
  `favicon` varchar(255) DEFAULT 'img/favicon.ico',
  `address` varchar(255) DEFAULT '',
  `phone` varchar(50) DEFAULT '',
  `email` varchar(100) DEFAULT '',
  `hours` varchar(100) DEFAULT '',
  `footer_about` text DEFAULT NULL,
  `copyright` varchar(255) DEFAULT '2021. byteBazaar. HTML CSS',
  `currency` varchar(10) DEFAULT '$',
  `map_embed_url` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_settings`
--

INSERT INTO `website_settings` (`id`, `site_title`, `site_tagline`, `logo`, `favicon`, `address`, `phone`, `email`, `hours`, `footer_about`, `copyright`, `currency`, `map_embed_url`) VALUES
(1, 'Northern E-Solution', 'Northern Premium Store', 'img/1770807731_logo_ChatGPT Image1 Feb 11, 2026, 04_59_43 PM.png', 'img/1770807731_favicon_ChatGPT Image1 Feb 11, 2026, 04_59_43 PM.png', 'Northern University Bangladesh, Dhaka', '+8801715618520', 'info@nsn.com', '10am-8pm', 'Secured Payment Gateways', '', 'BDT ', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d10321.247588964174!2d90.42386476066021!3d23.85015349257642!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755c671012602eb%3A0x27761cc8da96b3b7!2sNorthern%20University%20Bangladesh%2C%20Permanent%20Campus!5e0!3m2!1sen!2sbd!4v1770469547746!5m2!1sen!2sbd\" width=\"600\" height=\"450\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `aid` int(11) NOT NULL,
  `pid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`aid`, `pid`) VALUES
(1, 27),
(1, 42);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`aid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `cnic` (`cnic`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`aid`,`pid`),
  ADD KEY `cartfk2` (`pid`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`cat_id`),
  ADD UNIQUE KEY `cat_name` (`cat_name`);

--
-- Indexes for table `features`
--
ALTER TABLE `features`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hero`
--
ALTER TABLE `hero`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mail_settings`
--
ALTER TABLE `mail_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order-details`
--
ALTER TABLE `order-details`
  ADD PRIMARY KEY (`oid`,`pid`),
  ADD KEY `orderdtfk2` (`pid`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`oid`),
  ADD KEY `ordersfk` (`aid`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_transaction_id` (`transaction_id`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`oid`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`pid`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`pid`,`cat_id`);

--
-- Indexes for table `product_tags`
--
ALTER TABLE `product_tags`
  ADD PRIMARY KEY (`pid`,`tag_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`oid`,`pid`),
  ADD KEY `reviewsfk2` (`pid`);

--
-- Indexes for table `slider`
--
ALTER TABLE `slider`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`tag_id`),
  ADD UNIQUE KEY `tag_name` (`tag_name`);

--
-- Indexes for table `website_settings`
--
ALTER TABLE `website_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`aid`,`pid`),
  ADD KEY `wishlistfk2` (`pid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `aid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `cat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=477;

--
-- AUTO_INCREMENT for table `features`
--
ALTER TABLE `features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `hero`
--
ALTER TABLE `hero`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `mail_settings`
--
ALTER TABLE `mail_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `oid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `pid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `slider`
--
ALTER TABLE `slider`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `website_settings`
--
ALTER TABLE `website_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cartfk1` FOREIGN KEY (`aid`) REFERENCES `accounts` (`aid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cartfk2` FOREIGN KEY (`pid`) REFERENCES `products` (`pid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order-details`
--
ALTER TABLE `order-details`
  ADD CONSTRAINT `orderdtfk1` FOREIGN KEY (`oid`) REFERENCES `orders` (`oid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orderdtfk2` FOREIGN KEY (`pid`) REFERENCES `products` (`pid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `ordersfk` FOREIGN KEY (`aid`) REFERENCES `accounts` (`aid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`oid`) REFERENCES `orders` (`oid`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviewsfk1` FOREIGN KEY (`oid`) REFERENCES `orders` (`oid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reviewsfk2` FOREIGN KEY (`pid`) REFERENCES `products` (`pid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlistfk1` FOREIGN KEY (`aid`) REFERENCES `accounts` (`aid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `wishlistfk2` FOREIGN KEY (`pid`) REFERENCES `products` (`pid`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
