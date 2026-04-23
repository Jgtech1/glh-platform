-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2026 at 02:20 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `glh_platform`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `parent_id`, `icon`, `status`, `created_at`) VALUES
(1, 'Vegetables', 'Fresh organic vegetables', NULL, 'fa-carrot', 'active', '2026-04-18 22:21:00'),
(2, 'Fruits', 'Seasonal fresh fruits', NULL, 'fa-apple-alt', 'active', '2026-04-18 22:21:00'),
(3, 'Dairy', 'Milk, cheese, eggs and more', NULL, 'fa-cheese', 'active', '2026-04-18 22:21:00'),
(4, 'Meat', 'Fresh meat and poultry', NULL, 'fa-drumstick-bite', 'active', '2026-04-18 22:21:00'),
(5, 'Grains', 'Rice, wheat and cereals', NULL, 'fa-wheat-alt', 'active', '2026-04-18 22:21:00'),
(6, 'Herbs', 'Fresh herbs and spices', NULL, 'fa-seedling', 'active', '2026-04-18 22:21:00');

-- --------------------------------------------------------

--
-- Table structure for table `currency_settings`
--

CREATE TABLE `currency_settings` (
  `id` int(11) NOT NULL,
  `currency_code` varchar(3) NOT NULL,
  `currency_symbol` varchar(5) NOT NULL,
  `currency_name` varchar(50) NOT NULL,
  `exchange_rate` decimal(10,4) DEFAULT 1.0000,
  `is_base_currency` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `decimal_places` int(11) DEFAULT 2,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `currency_settings`
--

INSERT INTO `currency_settings` (`id`, `currency_code`, `currency_symbol`, `currency_name`, `exchange_rate`, `is_base_currency`, `is_active`, `decimal_places`, `created_at`, `updated_at`) VALUES
(1, 'USD', '$', 'US Dollar', '1.0000', 1, 1, 2, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(2, 'EUR', '€', 'Euro', '0.9200', 0, 1, 2, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(3, 'GBP', '£', 'British Pound', '0.7900', 0, 1, 2, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(4, 'CAD', 'C$', 'Canadian Dollar', '1.3500', 0, 1, 2, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(5, 'AUD', 'A$', 'Australian Dollar', '1.5200', 0, 1, 2, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(6, 'INR', '₹', 'Indian Rupee', '83.5000', 0, 1, 2, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(7, 'JPY', '¥', 'Japanese Yen', '150.5000', 0, 1, 2, '2026-04-18 20:59:31', '2026-04-18 20:59:31');

-- --------------------------------------------------------

--
-- Table structure for table `dynamic_content`
--

CREATE TABLE `dynamic_content` (
  `id` int(11) NOT NULL,
  `content_key` varchar(100) NOT NULL,
  `content_value` text DEFAULT NULL,
  `content_type` enum('text','html','json','image') DEFAULT 'text',
  `category` varchar(50) DEFAULT 'general',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dynamic_content`
--

INSERT INTO `dynamic_content` (`id`, `content_key`, `content_value`, `content_type`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'site_title', 'Greenfield Local Hub', 'text', 'general', 1, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(2, 'site_tagline', 'Fresh from Local Farms to Your Table', 'text', 'general', 1, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(3, 'hero_title', 'Fresh from Local Farms', 'text', 'hero', 1, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(4, 'hero_subtitle', 'Support local farmers and get the freshest produce delivered to your doorstep', 'text', 'hero', 1, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(5, 'about_text', 'Greenfield Local Hub connects local farmers directly with customers, ensuring fresh produce and fair prices.', 'text', 'about', 1, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(6, 'footer_text', 'Connecting local farmers with communities', 'text', 'footer', 1, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(7, 'welcome_message', 'Welcome to Greenfield Local Hub!', 'text', 'general', 1, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(8, 'promo_banner', 'Free delivery on orders over $50!', 'text', 'promo', 1, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(9, 'contact_email', 'support@greenfieldhub.com', 'text', 'contact', 1, '2026-04-18 20:59:31', '2026-04-18 20:59:31'),
(10, 'contact_phone', '+1 (555) 123-4567', 'text', 'contact', 1, '2026-04-18 20:59:31', '2026-04-18 20:59:31');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_transactions`
--

CREATE TABLE `loyalty_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `transaction_type` enum('earned','used') NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_transactions`
--

INSERT INTO `loyalty_transactions` (`id`, `user_id`, `points`, `transaction_type`, `order_id`, `description`, `created_at`) VALUES
(1, 4, 15, 'earned', 35, 'Points earned for order #ORD-20260419-8137', '2026-04-19 13:55:13'),
(2, 4, 9, 'earned', 36, 'Points earned for order #ORD-20260419-6381', '2026-04-19 14:48:16'),
(3, 4, 9, 'used', 36, 'Points used for order discount', '2026-04-19 14:48:44'),
(4, 4, 5, 'used', 37, 'Points used for order discount', '2026-04-19 19:06:10'),
(5, 4, 11, 'earned', 37, 'Points earned for order #ORD-20260419-2280', '2026-04-19 19:06:10');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `total_amount_base` decimal(10,2) DEFAULT NULL,
  `total_amount_display` decimal(10,2) DEFAULT NULL,
  `currency_code` varchar(3) DEFAULT 'USD',
  `exchange_rate_used` decimal(10,4) DEFAULT 1.0000,
  `delivery_type` enum('pickup','delivery') DEFAULT 'delivery',
  `delivery_address` text DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid') DEFAULT 'pending',
  `order_notes` text DEFAULT NULL,
  `loyalty_points_used` int(11) DEFAULT 0,
  `loyalty_points_earned` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `total_amount`, `total_amount_base`, `total_amount_display`, `currency_code`, `exchange_rate_used`, `delivery_type`, `delivery_address`, `scheduled_date`, `scheduled_time`, `status`, `payment_status`, `order_notes`, `loyalty_points_used`, `loyalty_points_earned`, `created_at`, `updated_at`) VALUES
(35, 'ORD-20260419-8137', 4, '0.00', '15.19', '12.00', 'GBP', '0.7900', 'delivery', 'No.2 Amokpgbe Ehaamufu Isi Uzo Local Government, Isi Uzo Eha Amufu, 568890, USA', NULL, NULL, 'cancelled', 'pending', '', 0, 15, '2026-04-19 13:55:13', '2026-04-19 14:41:36'),
(36, 'ORD-20260419-6381', 4, '0.00', '9.73', '7.69', 'GBP', '0.7900', 'delivery', 'No.2 Amokpgbe Ehaamufu Isi Uzo Local Government, Isi Uzo Eha Amufu, 568890, USA', NULL, NULL, 'completed', 'pending', '', 0, 9, '2026-04-19 14:48:16', '2026-04-19 16:10:50'),
(37, 'ORD-20260419-2280', 4, '0.00', '11.95', '11.95', 'USD', '1.0000', 'delivery', 'No.2 Amokpgbe Ehaamufu Isi Uzo Local Government, San Francisco, 568890, USA', NULL, NULL, 'pending', 'pending', '', 5, 11, '2026-04-19 19:06:10', '2026-04-19 19:06:10');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_time` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price_at_time`) VALUES
(24, 35, 2, 1, '2.49'),
(25, 35, 1, 1, '3.99'),
(26, 36, 2, 1, '2.49'),
(27, 37, 1, 1, '3.99'),
(28, 37, 2, 1, '2.49');

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `notes`, `created_at`) VALUES
(1, 35, 'cancelled', 'Status updated to Cancelled', '2026-04-19 07:41:37'),
(2, 36, 'cancelled', 'Status updated to Cancelled', '2026-04-19 07:48:44'),
(3, 36, 'pending', 'Status updated to Pending', '2026-04-19 08:16:37'),
(4, 36, 'completed', 'Status updated to Completed', '2026-04-19 09:10:50');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `producer_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `producer_id`, `name`, `description`, `price`, `stock_quantity`, `image_url`, `category`, `unit`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'Organic Tomatoes', 'Fresh organic tomatoes from local farm', '3.99', 49, 'assets/uploads/1776624217_tomatoes-1296x728-feature.jpg', 'Vegetables', 'kg', 'active', '2026-04-18 20:37:25', '2026-04-19 19:06:10'),
(2, 2, 'Fresh Lettuce', 'Crisp green lettuce', '2.49', 74, 'assets/uploads/1776890981_tango-oakleaf-lettuce-c6f6417e-b835c4813e1d4cbf9d11ddf09fbd2ea6.jpg', 'Vegetables', 'piece', 'active', '2026-04-18 20:37:25', '2026-04-22 20:49:41'),
(3, 2, 'Farm Eggs', 'Free-range organic eggs', '5.99', 50, 'assets/uploads/1776891185_images (54).jpg', 'Dairy', 'dozen', 'active', '2026-04-18 20:37:25', '2026-04-22 20:53:05'),
(4, 2, 'Cassava:', 'A root crop processed into products like garri, fufu, and starch', '20.00', 150, 'assets/uploads/1776893246_images (55).jpg', 'Grains', 'piece', 'active', '2026-04-22 21:26:55', '2026-04-22 21:27:26'),
(5, 2, 'Cabbage', 'A leafy vegetable used in salads and cooking.', '25.00', 500, 'assets/uploads/1776893435_69e93dfbde1e8.jpg', 'Vegetables', 'piece', 'active', '2026-04-22 21:30:35', '2026-04-22 21:30:35'),
(6, 2, 'Millet', 'A small-seeded grain rich in nutrients, commonly grown in dry regions.', '30.00', 200, 'assets/uploads/1776893715_69e93f13afd49.jpg', 'Grains', 'bundle', 'active', '2026-04-22 21:35:15', '2026-04-22 21:35:15'),
(7, 2, 'Onion', 'A flavorful vegetable used as a base ingredient in cooking.', '5.00', 300, 'assets/uploads/1776893882_69e93fbae7370.jpg', 'Vegetables', 'piece', 'active', '2026-04-22 21:38:02', '2026-04-22 21:38:02');

-- --------------------------------------------------------

--
-- Table structure for table `system_backups`
--

CREATE TABLE `system_backups` (
  `id` int(11) NOT NULL,
  `backup_name` varchar(200) DEFAULT NULL,
  `backup_file` varchar(500) DEFAULT NULL,
  `backup_size` varchar(50) DEFAULT NULL,
  `backup_type` enum('database','files','full') DEFAULT 'database',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'base_currency', 'USD', 'text', 'Base currency for all prices', '2026-04-18 20:59:31'),
(2, 'default_currency', 'USD', 'text', 'Default display currency', '2026-04-18 20:59:31'),
(3, 'loyalty_points_ratio', '10', 'number', 'Points earned per currency unit spent', '2026-04-18 20:59:31'),
(4, 'max_delivery_distance', '50', 'number', 'Maximum delivery distance in km', '2026-04-18 20:59:31'),
(5, 'order_timeout_minutes', '30', 'number', 'Cart timeout in minutes', '2026-04-18 20:59:31'),
(6, 'enable_loyalty_program', 'true', 'boolean', 'Enable/disable loyalty program', '2026-04-18 20:59:31'),
(7, 'producer_notify_orders_2', '1', 'text', NULL, '2026-04-19 18:58:12'),
(8, 'producer_notify_low_stock_2', '1', 'text', NULL, '2026-04-19 18:58:13'),
(9, 'producer_low_stock_qty_2', '10', 'text', NULL, '2026-04-19 18:58:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','producer','admin') DEFAULT 'customer',
  `loyalty_points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `address`, `role`, `loyalty_points`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@glh.com', '$2y$10$f0Rhm1cX0NNKW8JcosrIm.pjnUG2JnpZVoDtY2alWpFNeEFqTjQo6', 'System Administrator', '07037483220', 'NO. 12 NEBEOLISA STREET NEW FRIENDS ESTATE AGBOVO NGENE AMAWBIA', 'admin', 0, '2026-04-18 20:37:25', '2026-04-20 21:43:14'),
(2, 'johnfarmer', 'john@greenfarm.com', '$2y$10$f0Rhm1cX0NNKW8JcosrIm.pjnUG2JnpZVoDtY2alWpFNeEFqTjQo6', 'John Farmer', '1234567890', NULL, 'producer', 0, '2026-04-18 20:37:25', '2026-04-19 15:00:57'),
(4, 'user', 'user1@email.com', '$2y$10$f0Rhm1cX0NNKW8JcosrIm.pjnUG2JnpZVoDtY2alWpFNeEFqTjQo6', 'user1', '08123456789', 'online', 'customer', 21, '2026-04-18 22:04:37', '2026-04-19 19:06:10');

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
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `currency_settings`
--
ALTER TABLE `currency_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `currency_code` (`currency_code`);

--
-- Indexes for table `dynamic_content`
--
ALTER TABLE `dynamic_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `content_key` (`content_key`);

--
-- Indexes for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producer_id` (`producer_id`);

--
-- Indexes for table `system_backups`
--
ALTER TABLE `system_backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `currency_settings`
--
ALTER TABLE `currency_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `dynamic_content`
--
ALTER TABLE `dynamic_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `system_backups`
--
ALTER TABLE `system_backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD CONSTRAINT `loyalty_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loyalty_transactions_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`producer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_backups`
--
ALTER TABLE `system_backups`
  ADD CONSTRAINT `system_backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
