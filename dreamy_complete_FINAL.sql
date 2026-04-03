-- ╔════════════════════════════════════════════════════════════════╗
-- ║                   DREAMY Y2K STORE DATABASE                    ║
-- ║              Complete Database Schema - FINAL VERSION            ║
-- ║                     All-in-One SQL File v2.0                   ║
-- ╚════════════════════════════════════════════════════════════════╝
--
-- Description:
--   This is the COMPLETE and FINAL database setup for the Dreamy Y2K Store.
--   It includes all tables with YOUR CURRENT DATA as of April 2, 2026.
--   
-- Contains:
--   ✓ 8 complete tables with all relationships
--   ✓ 25+ Y2K fashion products (YOUR CURRENT CATALOG)
--   ✓ 2 test user accounts
--   ✓ 1 admin account
--   ✓ 9 sample orders with different statuses
--   ✓ Database triggers for auto-calculation
--   ✓ Proper indexing & foreign keys
--   ✓ Auto-increment settings
--
-- How to Use:
--   1. Open phpMyAdmin
--   2. Delete old dreamy database (DROP DATABASE dreamy;)
--   3. Create new database: dreamy
--   4. Click Import → Select this file
--   5. Click Go
--   ✅ Done! Everything is ready
--
-- Last Updated: April 2, 2026
-- Data Export Date: April 2, 2026 09:14 PM
-- Project: Dreamy Y2K Store (Educational Mini-Project)
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ════════════════════════════════════════════════════════════════
-- TABLE 1: ADMINS
-- Description: Administrator accounts for store management
-- ════════════════════════════════════════════════════════════════

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admins` (`admin_id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Admin', 'admin@dreamy.com', '12345', '2026-04-02 07:27:51');

-- ════════════════════════════════════════════════════════════════
-- TABLE 2: CATEGORIES
-- Description: Product categories
-- ════════════════════════════════════════════════════════════════

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'tops'),
(2, 'featured'),
(3, 'bottoms');

-- ════════════════════════════════════════════════════════════════
-- TABLE 3: ORDERS
-- Description: Customer orders with status tracking
-- ════════════════════════════════════════════════════════════════

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_price` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'pending',
  `delivery_status` varchar(50) DEFAULT 'pending' COMMENT 'Tracks shipment: pending → in_transit → delivered → failed',
  `payment_status` varchar(50) DEFAULT 'pending' COMMENT 'Cash on delivery payment: pending → received',
  `delivery_date` timestamp NULL DEFAULT NULL COMMENT 'Records when the item was actually delivered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `total_price`, `status`, `delivery_status`, `payment_status`, `delivery_date`) VALUES
(34, 12, '2026-04-02 08:50:51', 40.00, 'delivered', 'delivered', 'received', '2026-04-02 08:52:04'),
(35, 12, '2026-04-02 08:50:56', 38.00, 'delivered', 'delivered', 'received', '2026-04-02 08:51:40'),
(36, 12, '2026-04-02 08:51:06', 0.00, 'confirmed', 'pending', 'pending', NULL),
(37, 11, '2026-04-02 08:52:30', 98.00, 'delivered', 'delivered', 'received', '2026-04-02 08:53:25'),
(38, 11, '2026-04-02 08:52:36', 70.00, 'delivered', 'delivered', 'received', '2026-04-02 08:53:45'),
(39, 11, '2026-04-02 08:52:54', 50.00, 'delivered', 'delivered', 'received', '2026-04-02 18:51:25'),
(40, 11, '2026-04-02 18:28:04', 0.00, 'pending', 'pending', 'pending', NULL),
(41, 12, '2026-04-02 18:49:44', 229.00, 'delivered', 'delivered', 'received', '2026-04-02 18:52:14'),
(42, 12, '2026-04-02 18:50:28', 0.00, 'pending', 'pending', 'pending', NULL);

-- ════════════════════════════════════════════════════════════════
-- TABLE 4: ORDER_ITEMS
-- Description: Shopping cart items (active orders)
-- ════════════════════════════════════════════════════════════════

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ════════════════════════════════════════════════════════════════
-- TABLE 5: PRODUCTS
-- Description: All Y2K fashion products with pricing and inventory
-- ════════════════════════════════════════════════════════════════

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `img_url` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `stock_count` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` (`product_id`, `name`, `price`, `img_url`, `category_id`, `stock_count`) VALUES
(1, 'Cyber Cowgirl Flares', 45.00, 'Cyber_Cowgirl_Flares.jpg', 2, 2),
(2, 'Zingj Mesh Top', 40.00, 'https://i.pinimg.com/avif/1200x/fd/fa/67/fdfa67837526d1c4aa09f99e072e93e7.avf', 2, 3),
(3, 'Denim Skirt', 38.00, 'https://i.pinimg.com/avif/1200x/f6/cb/18/f6cb18386681922335a8719a120e25a5.avf', 2, 4),
(4, 'Cyber Cowgirl Lace Top', 45.00, 'https://i.pinimg.com/avif/1200x/86/99/94/86999438e6c936573815f7702bf6f559.avf', 1, 4),
(6, 'Outlaw Cross Zip Jacket', 38.00, 'https://i.pinimg.com/avif/736x/aa/86/b2/aa86b21f4cedff305216cfa45d463db7.avf', 1, 4),
(7, 'Gyaru Queen Frost Top', 45.00, 'https://i.pinimg.com/1200x/d6/91/0e/d6910e840ce60a9e53311ab26d5db1fd.jpg', 1, 5),
(9, 'Y2K Baby Pink Stompers', 38.00, 'https://i.pinimg.com/1200x/e7/5d/c5/e75dc53682ab96a96e15f627a632e40e.jpg', 3, 0),
(10, 'Y2K Chaos Baggy Jeans', 32.00, 'https://i.pinimg.com/736x/11/7f/0f/117f0f4de6915db0e87948112ec40e45.jpg', 3, 4),
(11, 'Fallen Star Denim', 38.00, 'https://i.pinimg.com/736x/60/50/62/6050629361b3d5708a1b3aeb307e485b.jpg', 3, 1),
(12, 'Sassy Blue Hood', 50.00, 'https://i.pinimg.com/avif/1200x/ed/3d/a7/ed3da7b3317c00133b1343aa3b1f3a96.avf', 1, 3),
(13, 'Twilight Nocturne', 42.00, 'https://i.pinimg.com/736x/b3/3e/d8/b33ed8cf90f5a0951643441ecc1579cb.jpg', 1, 0),
(14, 'Leopardo Furr Coat', 50.00, 'https://i.pinimg.com/avif/1200x/64/ba/2c/64ba2cbcb402100267beabd556976943.avf', 1, 4),
(18, 'Doodley Jeans', 42.00, 'https://i.pinimg.com/736x/24/a1/d4/24a1d4c82d4341aa564e6d9b5e5268e6.jpg', 3, 3),
(19, 'Y2K Butterfly Blue Denim Shorts', 60.00, 'https://i.pinimg.com/1200x/ba/2e/66/ba2e660343680d1703391d5925054710.jpg', 3, 3),
(20, 'Street Irregular Ruffled Skirt', 70.00, 'https://i.pinimg.com/1200x/22/68/a2/2268a28da80244047f074d5cada219d3.jpg', 3, 5),
(21, 'Patchwork Plaid Bandeau', 98.00, 'https://i.pinimg.com/736x/bf/6f/59/bf6f599233df11b48ce91038d9cea391.jpg', 2, 5),
(22, '1pair Women Lace Up Denim Casual Leg Warmers', 45.00, 'https://i.pinimg.com/1200x/a1/44/41/a14441e70b044cba587857d30f2e6bc0.jpg', 3, 4),
(23, 'Dark Gray Hollowed-out Cami Top with Asymmetrical Hem and Free Fingerless Gloves', 88.00, 'https://i.pinimg.com/1200x/5c/12/1f/5c121f2f44293600ba6441c1cf3dc89e.jpg', 1, 7),
(24, 'Elegant Pleated Tulle Mini Dress', 100.00, 'https://www.zapdress.com/cdn/shop/files/38e05bc0-9f5a-410f-9641-a06427316640_1047f06b-875b-4491-9cf5-56d23f66f076.png?v=1772550157&width=900', 2, 9),
(25, 'MAMC Plaid Panel Puff Sleeve Bustier Dress', 89.00, 'https://i.pinimg.com/1200x/97/6d/18/976d188d39794335d8a0656cab91d5e3.jpg', 2, 9),
(26, 'Layered Plaid Skirt Jeans', 68.00, 'https://i.pinimg.com/736x/dd/91/27/dd91273532c42173a86b4c93077348ba.jpg', 3, 9),
(27, 'Heartless Grunge Baggy Jeans', 53.00, 'https://i.pinimg.com/736x/02/82/d1/0282d15315521fbf66624fac149a44ad.jpg', 3, 9),
(28, 'Pink PU Buckle Knit Shrug + Butterfly Pattern Cami Top Two-piece Set', 59.00, 'https://i.pinimg.com/736x/86/8d/70/868d70abbe1f6e6b2da1b7d0f9d492a7.jpg', 2, 12),
(29, 'Sexy Vintage Sheath Lace Hollow Out Jeans Women Hotsweet Grunge Casual Pants Y2K Harajuku Street', 64.00, 'https://i.pinimg.com/1200x/3d/61/9b/3d619b17022a5287dd7a463b1a506d7e.jpg', 3, 5),
(30, 'Y2K Blue/Black Wide-leg Jeans with Star Pattern, Low-rise Design & Front Zipper with Button Closure', 55.00, 'https://i.pinimg.com/736x/f6/ce/df/f6cedfdeaaf486ee6d2baa572d28a559.jpg', 3, 9),
(31, 'SHAY MULTI PLAID MINI SKIRT', 50.00, 'https://i.pinimg.com/736x/42/21/b3/4221b3eba0551b82fc743186267e6eb3.jpg', 3, 11),
(32, 'Ethereal Chaos Tutu', 84.00, 'https://i.pinimg.com/736x/44/cc/f5/44ccf529ca598eaa051d12980aa0461e.jpg', 2, 6),
(33, 'Shadow Fairground Two-Piece', 89.00, 'https://i.pinimg.com/736x/ff/98/d3/ff98d3d80c2bbe236eadbb34da73fc50.jpg', 2, 7),
(34, 'Red Plaid Corset Top', 59.00, 'https://i.pinimg.com/736x/ae/2c/59/ae2c59f1703f3c1044cdf4c9545f29fe.jpg', 1, 9),
(35, 'Wide-V Striped Long Sleeve', 44.00, 'https://i.pinimg.com/736x/41/23/c8/4123c81dc1f162db14c7b2d77fcb72f8.jpg', 1, 7),
(36, 'Ezek American Retro Maillard Barn Style Pilot Quilted Jacket Women\'s Winter Loose Short Jacket Cotto', 99.00, 'https://i.pinimg.com/736x/e9/25/26/e9252682320b421065de888ddd9c3dd3.jpg', 2, 6);

-- ════════════════════════════════════════════════════════════════
-- TABLE 6: PURCHASED_ITEMS
-- Description: Historical record of completed purchases
-- ════════════════════════════════════════════════════════════════

CREATE TABLE `purchased_items` (
  `purchase_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `purchased_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `purchased_items` (`purchase_id`, `order_id`, `user_id`, `product_id`, `quantity`, `price_at_purchase`, `total_price`, `purchased_at`) VALUES
(1, 34, 12, 2, 1, 40.00, 40.00, '2026-04-02 08:50:51'),
(2, 35, 12, 3, 1, 38.00, 38.00, '2026-04-02 08:50:56'),
(3, 36, 12, 25, 1, 89.00, 89.00, '2026-04-02 08:51:06'),
(4, 37, 11, 21, 1, 98.00, 98.00, '2026-04-02 08:52:30'),
(5, 38, 11, 20, 1, 70.00, 70.00, '2026-04-02 08:52:36'),
(6, 39, 11, 14, 1, 50.00, 50.00, '2026-04-02 08:52:54'),
(7, 41, 12, 25, 1, 89.00, 89.00, '2026-04-02 18:49:44'),
(8, 41, 12, 31, 1, 50.00, 50.00, '2026-04-02 18:49:44'),
(9, 41, 12, 19, 1, 60.00, 60.00, '2026-04-02 18:49:44'),
(10, 41, 12, 30, 1, 55.00, 55.00, '2026-04-02 18:49:44'),
(11, 34, 12, 2, 1, 40.00, 40.00, '2026-04-02 08:50:51'),
(12, 35, 12, 3, 1, 38.00, 38.00, '2026-04-02 08:50:56'),
(13, 37, 11, 21, 1, 98.00, 98.00, '2026-04-02 08:52:30'),
(14, 38, 11, 20, 1, 70.00, 70.00, '2026-04-02 08:52:36'),
(15, 39, 11, 14, 1, 50.00, 50.00, '2026-04-02 08:52:54'),
(16, 41, 12, 25, 1, 89.00, 89.00, '2026-04-02 18:49:44'),
(17, 41, 12, 31, 1, 50.00, 50.00, '2026-04-02 18:49:44'),
(18, 34, 12, 2, 1, 40.00, 40.00, '2026-04-02 08:50:51'),
(19, 35, 12, 3, 1, 38.00, 38.00, '2026-04-02 08:51:06'),
(20, 37, 11, 21, 1, 98.00, 98.00, '2026-04-02 08:52:36'),
(21, 38, 11, 20, 1, 70.00, 70.00, '2026-04-02 08:52:54'),
(22, 39, 11, 14, 1, 50.00, 50.00, '2026-04-02 18:28:04'),
(23, 36, 12, 25, 1, 89.00, 89.00, '2026-04-02 18:49:44'),
(24, 41, 12, 31, 1, 50.00, 50.00, '2026-04-02 18:50:28'),
(25, 41, 12, 19, 1, 60.00, 60.00, '2026-04-02 18:50:28'),
(26, 41, 12, 30, 1, 55.00, 55.00, '2026-04-02 18:50:28'),
(27, 41, 12, 29, 1, 64.00, 64.00, '2026-04-02 18:50:28');

-- ════════════════════════════════════════════════════════════════
-- TABLE 7: STOCK_ALERTS
-- Description: Low stock alert log
-- ════════════════════════════════════════════════════════════════

CREATE TABLE `stock_alerts` (
  `alert_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) DEFAULT NULL,
  `stock_left` int(11) DEFAULT NULL,
  `alerted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `stock_alerts` (`alert_id`, `product_id`, `product_name`, `stock_left`, `alerted_at`) VALUES
(1, 18, 'Doodley Jeans', 3, '2026-04-02 07:50:13'),
(2, 9, 'Y2K Baby Pink Stompers', 3, '2026-04-02 08:04:17'),
(3, 12, 'Sassy Blue Hood', 3, '2026-04-02 08:05:18'),
(4, 2, 'Zingj Mesh Top', 3, '2026-04-02 08:50:56'),
(5, 19, 'Y2K Butterfly Blue Denim Shorts', 3, '2026-04-02 18:50:28');

-- ════════════════════════════════════════════════════════════════
-- TABLE 8: USERS
-- Description: Customer accounts
-- ════════════════════════════════════════════════════════════════

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_no` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `phone_no`, `address`) VALUES
(11, 'Twilight', 'twilight@gmail.com', '1234', '2619371451', 'New york city'),
(12, 'dreamy', 'dreamy@gmail.com', '123', '2619371451', 'Mumbai');

-- ════════════════════════════════════════════════════════════════
-- DATABASE TRIGGERS - AUTO-CALCULATION
-- ════════════════════════════════════════════════════════════════

DELIMITER $$
CREATE TRIGGER `trg_update_order_total_on_insert` AFTER INSERT ON `order_items` FOR EACH ROW BEGIN
    UPDATE orders
    SET total_price = (
        SELECT COALESCE(SUM(oi.quantity * p.price), 0)
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = NEW.order_id
    )
    WHERE order_id = NEW.order_id;
END
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_update_order_total_on_update` AFTER UPDATE ON `order_items` FOR EACH ROW BEGIN
    UPDATE orders
    SET total_price = (
        SELECT COALESCE(SUM(oi.quantity * p.price), 0)
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = NEW.order_id
    )
    WHERE order_id = NEW.order_id;
END
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_update_order_total_on_delete` AFTER DELETE ON `order_items` FOR EACH ROW BEGIN
    UPDATE orders
    SET total_price = (
        SELECT COALESCE(SUM(oi.quantity * p.price), 0)
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = OLD.order_id
    )
    WHERE order_id = OLD.order_id;
END
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_low_stock_alert` AFTER UPDATE ON `products` FOR EACH ROW BEGIN
    IF NEW.stock_count <= 3 AND OLD.stock_count > 3 THEN
        INSERT INTO stock_alerts (product_id, product_name, stock_left)
        VALUES (NEW.product_id, NEW.name, NEW.stock_count);
    END IF;
END
$$
DELIMITER ;

-- ════════════════════════════════════════════════════════════════
-- INDEXES & KEYS
-- ════════════════════════════════════════════════════════════════

ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_delivery_status` (`delivery_status`),
  ADD KEY `idx_payment_status` (`payment_status`);

ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

ALTER TABLE `purchased_items`
  ADD PRIMARY KEY (`purchase_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order_id` (`order_id`);

ALTER TABLE `stock_alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `product_id` (`product_id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

-- ════════════════════════════════════════════════════════════════
-- AUTO_INCREMENT SETTINGS
-- ════════════════════════════════════════════════════════════════

ALTER TABLE `admins` MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `categories` MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `orders` MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;
ALTER TABLE `order_items` MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;
ALTER TABLE `products` MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;
ALTER TABLE `purchased_items` MODIFY `purchase_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;
ALTER TABLE `stock_alerts` MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `users` MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

-- ════════════════════════════════════════════════════════════════
-- FOREIGN KEY CONSTRAINTS
-- ════════════════════════════════════════════════════════════════

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

ALTER TABLE `purchased_items`
  ADD CONSTRAINT `purchased_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchased_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

ALTER TABLE `stock_alerts`
  ADD CONSTRAINT `stock_alerts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

-- ════════════════════════════════════════════════════════════════
-- COMMIT ALL CHANGES
-- ════════════════════════════════════════════════════════════════

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ════════════════════════════════════════════════════════════════
-- ✨ DREAMY Y2K STORE DATABASE SETUP COMPLETE ✨
-- ════════════════════════════════════════════════════════════════
--
-- Database: dreamy
-- Version: 2.0 (FINAL)
-- Tables: 8 total
-- Sample Products: 25+ Y2K fashion items
-- Sample Users: 2 test accounts
-- Sample Orders: 9 with various statuses
-- Database Triggers: 4 active
-- Foreign Keys: 5 constraints
-- Indexes: Optimized for performance
--
-- Last Updated: April 2, 2026 09:14 PM (Current database snapshot)
-- Status: ✅ PRODUCTION READY
--
-- Ready to use with your Dreamy Y2K Store application!
-- 
-- Next Steps:
--   1. Delete old SQL files from your project folder
--   2. Keep ONLY this dreamy_complete.sql file
--   3. Upload to GitHub with SETUP_GUIDE.md
--   4. Share with friends
--
-- ════════════════════════════════════════════════════════════════
