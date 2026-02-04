-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 29 Ara 2025, 01:14:52
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `foodapp`
--

DELIMITER $$
--
-- Yordamlar
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetCartTotalCalculation` (IN `p_user_id` INT)   BEGIN
    SELECT u.fullname, SUM(m.price * c.quantity) as CartTotal
    FROM cart c
    JOIN menus m ON c.menu_id = m.id
    JOIN users u ON c.user_id = u.id
    WHERE u.id = p_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetFullReviewDetails` (IN `p_rest_id` INT)   BEGIN
    SELECT u.fullname, r.rating, r.comment, o.OrderDate, res.restaurant_name
    FROM order_ratings r
    JOIN users u ON r.user_id = u.id
    JOIN orders o ON r.order_id = o.OrderID
    JOIN restaurants res ON r.restaurant_id = res.id
    WHERE r.restaurant_id = p_rest_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetLiveDeliveryTracking` (IN `p_order_id` INT)   BEGIN
    SELECT o.OrderID, r.restaurant_name, u.fullname as CustomerName, o.DeliveryProgress
    FROM orders o
    JOIN restaurants r ON o.RestaurantID = r.id
    JOIN users u ON o.CustomerID = u.id
    WHERE o.OrderID = p_order_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetOrderInvoice` (IN `p_order_id` INT)   BEGIN
    SELECT o.OrderID, m.item_name, oi.Quantity, oi.Price, (oi.Quantity * oi.Price) as SubTotal
    FROM orders o
    JOIN order_items oi ON o.OrderID = oi.OrderID
    JOIN menus m ON oi.MenuID = m.id
    WHERE o.OrderID = p_order_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetPaymentLocationReport` (IN `p_user_id` INT)   BEGIN
    SELECT p.PaymentDate, p.Amount, r.restaurant_name, o.Address
    FROM payments p
    JOIN orders o ON p.OrderID = o.OrderID
    JOIN restaurants r ON o.RestaurantID = r.id
    JOIN users u ON o.CustomerID = u.id
    WHERE u.id = p_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetPendingFinancials` ()   BEGIN
    SELECT o.OrderID, u.fullname, o.Total
    FROM orders o
    LEFT JOIN payments p ON o.OrderID = p.OrderID
    JOIN users u ON o.CustomerID = u.id
    WHERE p.PaymentID IS NULL;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetRestaurantRatingDeep` (IN `p_rest_id` INT)   BEGIN
    SELECT r.restaurant_name, u.fullname, rr.rating
    FROM restaurant_ratings rr
    JOIN restaurants r ON rr.restaurant_id = r.id
    JOIN users u ON rr.user_id = u.id
    WHERE r.id = p_rest_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetRestaurantStats` (IN `p_seller_id` INT)   BEGIN
    SELECT r.restaurant_name, u.fullname as Owner, COUNT(m.id) as ItemCount
    FROM restaurants r
    JOIN users u ON r.seller_id = u.id
    JOIN menus m ON r.id = m.restaurant_id
    WHERE u.id = p_seller_id
    GROUP BY r.id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetSellerFinancialReport` (IN `p_seller_id` INT)   BEGIN
    SELECT r.restaurant_name, SUM(pay.Amount) as TotalCollected, COUNT(o.OrderID) as OrderCount
    FROM users u
    JOIN restaurants r ON u.id = r.seller_id
    JOIN orders o ON r.id = o.RestaurantID
    JOIN payments pay ON o.OrderID = pay.OrderID
    WHERE u.id = p_seller_id AND o.Status = 'delivered'
    GROUP BY r.id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetSellerMenuDetailed` (IN `p_rest_id` INT)   BEGIN
    SELECT m.item_name, m.price, r.restaurant_name, u.fullname as SellerName
    FROM menus m
    JOIN restaurants r ON m.restaurant_id = r.id
    JOIN users u ON r.seller_id = u.id
    WHERE r.id = p_rest_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetSystemActionLogs` ()   BEGIN
    SELECT l.msg, u.fullname, l.created_at
    FROM logs l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetTrendingProducts` ()   BEGIN
    SELECT m.item_name, r.restaurant_name, SUM(oi.Quantity) as TotalSold
    FROM order_items oi
    JOIN menus m ON oi.MenuID = m.id
    JOIN restaurants r ON m.restaurant_id = r.id
    GROUP BY m.id ORDER BY TotalSold DESC LIMIT 10;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserCartDetailed` (IN `p_user_id` INT)   BEGIN
    SELECT c.id, m.item_name, m.price, r.restaurant_name, c.quantity
    FROM cart c
    JOIN menus m ON c.menu_id = m.id
    JOIN restaurants r ON m.restaurant_id = r.id
    WHERE c.user_id = p_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserFavoritesWithBonus` (IN `p_user_id` INT)   BEGIN
    SELECT r.restaurant_name, r.rating, COUNT(m.id) as TotalItems, up.points
    FROM favorites f
    JOIN restaurants r ON f.restaurant_id = r.id
    JOIN menus m ON r.id = m.restaurant_id
    JOIN user_points up ON f.user_id = up.user_id
    WHERE f.user_id = p_user_id
    GROUP BY r.id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserPointEarnings` (IN `p_user_id` INT)   BEGIN
    SELECT u.fullname, up.points, o.Total as LastOrderAmount
    FROM users u
    JOIN user_points up ON u.id = up.user_id
    JOIN orders o ON u.id = o.CustomerID
    WHERE u.id = p_user_id ORDER BY o.OrderDate DESC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `favorites`
--

CREATE TABLE `favorites` (
  `user_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `favorites`
--

INSERT INTO `favorites` (`user_id`, `restaurant_id`) VALUES
(6, 7),
(6, 8);

--
-- Tetikleyiciler `favorites`
--
DELIMITER $$
CREATE TRIGGER `LogNewFavorite` AFTER INSERT ON `favorites` FOR EACH ROW BEGIN
    -- Kullanıcıya favori ödülü olarak 5 puan ekle
    INSERT INTO user_points (user_id, points) VALUES (NEW.user_id, 5)
    ON DUPLICATE KEY UPDATE points = points + 5;
    
    -- Logs tablosuna işlem kaydını yaz (Yorum satırı kaldırıldı)
    INSERT INTO logs (user_id, msg) VALUES (NEW.user_id, 'Yeni bir restoran favorilere eklendi.');
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `msg` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `logs`
--

INSERT INTO `logs` (`id`, `user_id`, `msg`, `created_at`) VALUES
(1, 6, 'Sistem testi yapıldı.', '2025-12-28 15:13:15');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `menus`
--

INSERT INTO `menus` (`id`, `restaurant_id`, `item_name`, `price`, `image`, `created_at`) VALUES
(4, 2, 'Karadeniz Pidesi', 125.00, NULL, '2025-12-10 22:23:23'),
(5, 2, 'Kaşarlı Pide', 120.00, NULL, '2025-12-10 22:23:31'),
(7, 3, 'Mercü Çorba', 40.00, '1765739046_4210.png', '2025-12-14 19:04:06'),
(8, 3, 'Kuru Fasulye', 110.00, '1765739078_8206.png', '2025-12-14 19:04:38'),
(9, 3, 'Tavuk Sote', 130.00, '1765739099_5965.png', '2025-12-14 19:04:59'),
(10, 3, 'Sütlaç', 69.90, '1765739116_9977.png', '2025-12-14 19:05:16'),
(11, 4, 'Nohut', 79.90, '1765739193_5255.png', '2025-12-14 19:06:33'),
(12, 4, 'İzmir Köfte', 129.90, '1765739213_6833.png', '2025-12-14 19:06:53'),
(13, 4, 'Revani', 59.90, '1765739233_3093.png', '2025-12-14 19:07:13'),
(14, 4, 'Karnıyarık', 99.90, '1765739255_2508.png', '2025-12-14 19:07:35'),
(15, 4, 'Pilav', 49.90, '1765739274_5763.png', '2025-12-14 19:07:54'),
(16, 5, 'Yayla Çorbası', 39.90, '1765739333_7624.png', '2025-12-14 19:08:53'),
(17, 5, 'Tas Kebabı', 179.90, '1765739357_9830.png', '2025-12-14 19:09:17'),
(18, 5, 'Tavuk Haşlama', 129.90, '1765739375_7869.png', '2025-12-14 19:09:35'),
(19, 5, 'Sebzeli Bulgur Pilavı', 69.90, '1765739402_7140.png', '2025-12-14 19:10:02'),
(20, 5, 'Kazandibi', 69.90, '1765739421_2424.png', '2025-12-14 19:10:21'),
(21, 6, 'Domates Çorbası', 39.90, '1765739460_7981.png', '2025-12-14 19:11:00'),
(22, 6, 'Etli Türlü', 150.00, '1765739472_3783.png', '2025-12-14 19:11:12'),
(23, 6, 'Zeytinyağlı Taze Fasulye', 70.00, '1765739489_9288.png', '2025-12-14 19:11:29'),
(24, 6, 'Pilav', 39.00, '1765739508_3562.png', '2025-12-14 19:11:48'),
(25, 6, 'Aşure', 59.90, '1765739524_7526.png', '2025-12-14 19:12:04'),
(26, 7, 'Tarhana Çorbası', 40.00, '1765739570_9677.png', '2025-12-14 19:12:50'),
(27, 7, 'Et Sote', 140.00, '1765739581_8648.png', '2025-12-14 19:13:01'),
(28, 7, 'Mercimek Köftesi', 40.00, '1765739595_4013.png', '2025-12-14 19:13:15'),
(29, 7, 'Yoğurt', 25.00, '1765739614_9869.png', '2025-12-14 19:13:34'),
(30, 7, 'İrmik Helvası', 90.00, '1765739635_2195.png', '2025-12-14 19:13:55'),
(31, 8, 'Adana Kebap', 220.00, '1765739705_5100.png', '2025-12-14 19:15:05'),
(32, 8, 'Urfa Kebap', 220.00, '1765739717_1614.png', '2025-12-14 19:15:17'),
(33, 8, 'Kuzu Şiş', 240.00, '1765739731_9283.png', '2025-12-14 19:15:31'),
(34, 8, 'Lahmacun', 60.00, '1765739748_3335.png', '2025-12-14 19:15:48'),
(35, 8, 'İçli Köfte', 70.00, '1765739773_5691.png', '2025-12-14 19:16:13'),
(36, 8, 'Soğan Salatası (Sumaklı)', 20.00, '1765739801_6682.png', '2025-12-14 19:16:41'),
(37, 8, 'Künefe', 70.00, '1765739820_5824.png', '2025-12-14 19:17:00'),
(38, 3, 'Lahmacun', 89.90, '1765971136_4713.png', '2025-12-17 11:32:16');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `OrderID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `RestaurantID` int(11) NOT NULL,
  `Total` decimal(10,2) NOT NULL,
  `Address` varchar(255) NOT NULL,
  `Status` enum('pending','preparing','on_the_way','delivered') DEFAULT 'pending',
  `OrderDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_points` int(11) DEFAULT 0,
  `DeliveryProgress` int(11) DEFAULT 0,
  `on_the_way_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `orders`
--

INSERT INTO `orders` (`OrderID`, `CustomerID`, `RestaurantID`, `Total`, `Address`, `Status`, `OrderDate`, `used_points`, `DeliveryProgress`, `on_the_way_at`) VALUES
(1, 6, 1, 409.98, 'asfsdgf', 'pending', '2025-12-07 21:00:35', 0, 0, NULL),
(2, 6, 1, 129.99, 'retyl', 'pending', '2025-12-07 21:03:47', 0, 0, NULL),
(3, 6, 1, 129.99, 'ıu*8', 'pending', '2025-12-07 21:06:17', 0, 0, NULL),
(4, 6, 1, 300.00, 'ujkljıl', 'pending', '2025-12-10 10:05:56', 0, 0, NULL),
(5, 6, 1, 150.00, 'asd', 'pending', '2025-12-10 10:11:03', 0, 0, NULL),
(6, 6, 1, 129.99, 't67', 'preparing', '2025-12-10 10:16:05', 0, 0, NULL),
(7, 6, 1, 150.00, 'fsdfs', 'pending', '2025-12-10 11:58:21', 0, 0, NULL),
(8, 6, 1, 1232.96, 'Oruçreis', 'delivered', '2025-12-10 12:09:30', 370, 0, NULL),
(9, 6, 1, 251.98, 'sdfg', 'preparing', '2025-12-10 17:19:46', 3080, 0, NULL),
(10, 6, 1, 3150.00, 'zxcvxcvb', 'delivered', '2025-12-10 17:21:28', 0, 0, NULL),
(11, 6, 1, 0.00, 'ERTYUIO,', 'delivered', '2025-12-10 19:35:52', 7100, 0, NULL),
(12, 6, 2, 1100.00, 'fglj', 'pending', '2025-12-10 23:17:41', 0, 0, NULL),
(13, 6, 2, 684.00, 'sagfh', 'on_the_way', '2025-12-11 18:43:57', 4160, 5, NULL),
(14, 6, 2, 245.00, 'fdsg', 'pending', '2025-12-11 18:56:28', 0, 0, NULL),
(15, 6, 2, 499.00, 'ertyuıop', 'delivered', '2025-12-11 19:09:02', 2320, 0, NULL),
(16, 6, 6, 97.60, 'sdfsdfdg', 'delivered', '2025-12-15 11:39:32', 1240, 0, NULL),
(17, 6, 8, 1158.00, 'asdfçsfgmndfngmdg', 'delivered', '2025-12-15 11:57:40', 240, 0, '2025-12-18 00:50:01'),
(18, 6, 7, 255.00, 'asdasdfas', 'delivered', '2025-12-16 20:41:58', 2900, 100, NULL),
(19, 6, 8, 1508.00, 'dsg', 'delivered', '2025-12-16 20:46:11', 640, 100, NULL),
(20, 6, 8, 1232.00, 'seadrftgyhujıkloşp', 'delivered', '2025-12-16 20:51:28', 3760, 100, NULL),
(21, 6, 5, 645.50, 'rgdhfghfgh', 'on_the_way', '2025-12-17 11:21:10', 3080, 0, NULL),
(22, 6, 2, 654.00, 'gdhfdgh', 'pending', '2025-12-17 21:40:18', 1620, 0, NULL),
(23, 6, 6, 517.70, 'asfsgdhfjghj', 'delivered', '2025-12-17 21:42:42', 1640, 0, '2025-12-18 00:54:04'),
(24, 6, 3, 306.00, 'sdfsdf', 'delivered', '2025-12-19 10:57:49', 1280, 0, '2025-12-19 13:58:07'),
(25, 6, 3, 161.00, 'rgrghr', 'delivered', '2025-12-19 11:04:04', 780, 0, '2025-12-19 14:04:18'),
(26, 6, 3, 279.60, 'reger', 'delivered', '2025-12-19 11:06:00', 0, 0, '2025-12-19 14:06:15'),
(27, 6, 3, 700.00, 'werw', 'delivered', '2025-12-19 11:09:30', 0, 0, '2025-12-19 14:09:44'),
(28, 6, 3, 227.50, 'asdadasd', 'preparing', '2025-12-28 13:48:21', 2840, 0, NULL),
(29, 6, 3, 709.50, 'adasfsdf', 'on_the_way', '2025-12-28 15:14:52', 0, 0, '2025-12-29 03:13:54'),
(30, 6, 4, 259.60, 'ghj', 'pending', '2025-12-29 00:13:40', 0, 0, NULL);

--
-- Tetikleyiciler `orders`
--
DELIMITER $$
CREATE TRIGGER `AfterOrderDelivered_Points` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF NEW.Status = 'delivered' AND OLD.Status <> 'delivered' THEN
        INSERT INTO user_points (user_id, points) 
        VALUES (NEW.CustomerID, NEW.Total * 0.1)
        ON DUPLICATE KEY UPDATE points = points + (NEW.Total * 0.1);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_items`
--

CREATE TABLE `order_items` (
  `OrderItemID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `CustomerName` varchar(100) DEFAULT NULL,
  `MenuID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `order_items`
--

INSERT INTO `order_items` (`OrderItemID`, `OrderID`, `CustomerName`, `MenuID`, `Quantity`, `Price`) VALUES
(4, 2, NULL, 3, 1, 129.99),
(5, 3, 'Sercan', 2, 1, 129.99),
(6, 7, 'Sercan', 1, 1, 150.00),
(7, 8, 'Sercan', 1, 5, 150.00),
(8, 8, 'Sercan', 2, 2, 129.99),
(9, 8, 'Sercan', 3, 2, 129.99),
(10, 9, 'Sercan', 1, 2, 150.00),
(11, 9, 'Sercan', 2, 1, 129.99),
(12, 9, 'Sercan', 3, 1, 129.99),
(13, 10, 'Sercan', 1, 21, 150.00),
(14, 11, 'Sercan', 1, 3, 150.00),
(15, 11, 'Sercan', 2, 2, 129.99),
(16, 12, 'Sercan', 4, 4, 125.00),
(17, 12, 'Sercan', 5, 5, 120.00),
(18, 13, 'Sercan', 4, 4, 125.00),
(19, 13, 'Sercan', 5, 5, 120.00),
(20, 14, 'Sercan', 4, 1, 125.00),
(21, 14, 'Sercan', 5, 1, 120.00),
(22, 15, 'Sercan', 4, 3, 125.00),
(23, 15, 'Sercan', 5, 2, 120.00),
(24, 16, 'Sercan', 21, 4, 39.90),
(25, 17, 'Sercan', 31, 3, 220.00),
(26, 17, 'Sercan', 32, 2, 220.00),
(27, 17, 'Sercan', 35, 1, 70.00),
(28, 18, 'Sercan', 26, 3, 40.00),
(29, 18, 'Sercan', 27, 2, 140.00),
(30, 19, 'Sercan', 31, 4, 220.00),
(31, 19, 'Sercan', 32, 3, 220.00),
(32, 20, 'Sercan', 31, 5, 220.00),
(33, 20, 'Sercan', 34, 3, 60.00),
(34, 20, 'Sercan', 37, 2, 70.00),
(35, 21, 'Sercan', 17, 3, 179.90),
(36, 21, 'Sercan', 18, 2, 129.90),
(37, 22, 'Sercan', 4, 3, 125.00),
(38, 22, 'Sercan', 5, 3, 120.00),
(39, 23, 'Sercan', 23, 6, 70.00),
(40, 23, 'Sercan', 25, 3, 59.90),
(41, 24, 'Sercan', 8, 1, 110.00),
(42, 24, 'Sercan', 9, 2, 130.00),
(43, 25, 'Sercan', 7, 5, 40.00),
(44, 26, 'Sercan', 10, 4, 69.90),
(45, 27, 'Sercan', 8, 4, 110.00),
(46, 27, 'Sercan', 9, 2, 130.00),
(47, 28, 'Sercan', 10, 4, 69.90),
(48, 28, 'Sercan', 38, 1, 89.90),
(49, 29, 'Sercan', 7, 2, 40.00),
(50, 29, 'Sercan', 8, 1, 110.00),
(51, 29, 'Sercan', 9, 1, 130.00),
(52, 29, 'Sercan', 10, 3, 69.90),
(53, 29, 'Sercan', 38, 2, 89.90),
(54, 30, 'Sercan', 11, 2, 79.90),
(55, 30, 'Sercan', 15, 2, 49.90);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_ratings`
--

CREATE TABLE `order_ratings` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `order_ratings`
--

INSERT INTO `order_ratings` (`id`, `order_id`, `restaurant_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 11, 1, 6, 5, 'Harika', '2025-12-10 23:23:46'),
(2, 10, 1, 6, 5, NULL, '2025-12-10 23:31:45'),
(3, 16, 6, 6, 5, NULL, '2025-12-15 11:59:34'),
(4, 20, 8, 6, 5, NULL, '2025-12-16 21:05:30'),
(5, 15, 2, 6, 5, 'Yemek sıcaktı ve hızlı geldi çok teşekkürler elinize sağlık', '2025-12-16 21:09:14'),
(6, 27, 3, 6, 4, 'harika lezzet', '2025-12-19 11:12:01');

--
-- Tetikleyiciler `order_ratings`
--
DELIMITER $$
CREATE TRIGGER `UpdateRestaurantRating` AFTER INSERT ON `order_ratings` FOR EACH ROW BEGIN
    UPDATE restaurants 
    SET rating = (SELECT AVG(rating) FROM order_ratings WHERE restaurant_id = NEW.restaurant_id),
        rating_count = (SELECT COUNT(*) FROM order_ratings WHERE restaurant_id = NEW.restaurant_id)
    WHERE id = NEW.restaurant_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

CREATE TABLE `payments` (
  `PaymentID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `PaymentDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `Amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `payments`
--

INSERT INTO `payments` (`PaymentID`, `OrderID`, `PaymentDate`, `Amount`) VALUES
(1, 1, '2025-12-07 21:00:35', 409.98),
(2, 2, '2025-12-07 21:03:47', 129.99),
(3, 3, '2025-12-07 21:06:17', 129.99),
(4, 7, '2025-12-10 11:58:21', 150.00),
(5, 8, '2025-12-10 12:09:30', 1232.96),
(6, 9, '2025-12-10 17:19:46', 251.98),
(7, 10, '2025-12-10 17:21:28', 3150.00),
(8, 11, '2025-12-10 19:35:52', 0.00),
(9, 12, '2025-12-10 23:17:41', 1100.00),
(10, 13, '2025-12-11 18:43:57', 684.00),
(11, 14, '2025-12-11 18:56:28', 245.00),
(12, 15, '2025-12-11 19:09:02', 499.00),
(13, 16, '2025-12-15 11:39:32', 97.60),
(14, 17, '2025-12-15 11:57:40', 1158.00),
(15, 18, '2025-12-16 20:41:58', 255.00),
(16, 19, '2025-12-16 20:46:11', 1508.00),
(17, 20, '2025-12-16 20:51:29', 1232.00),
(18, 21, '2025-12-17 11:21:10', 645.50),
(19, 22, '2025-12-17 21:40:18', 654.00),
(20, 23, '2025-12-17 21:42:42', 517.70),
(21, 24, '2025-12-19 10:57:49', 306.00),
(22, 25, '2025-12-19 11:04:04', 161.00),
(23, 26, '2025-12-19 11:06:00', 279.60),
(24, 27, '2025-12-19 11:09:30', 700.00),
(25, 28, '2025-12-28 13:48:21', 227.50),
(26, 29, '2025-12-28 15:14:52', 709.50),
(27, 30, '2025-12-29 00:13:40', 259.60);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `restaurants`
--

CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `restaurant_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `logo` varchar(255) NOT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `restaurants`
--

INSERT INTO `restaurants` (`id`, `seller_id`, `restaurant_name`, `description`, `created_at`, `logo`, `rating`, `rating_count`) VALUES
(2, 8, 'Kardeşler Pide Salonu', 'Nefis ve lezizo pideler', '2025-12-10 22:23:12', '1765405392_9449.jpeg', 5.00, 1),
(3, 9, 'Bereket Esnaf Lokantası', 'Esnaf Lokantası', '2025-12-14 19:03:26', '1765739006_1349.png', 4.00, 1),
(4, 10, 'Anafartalar Sofrası', 'Gel Abi Gel', '2025-12-14 19:06:19', '1765739179_7278.png', 0.00, 0),
(5, 11, 'Usta’nın Yeri', 'Haluk Baba\'nın Baba Yemekleri', '2025-12-14 19:08:30', '1765739310_2822.png', 0.00, 0),
(6, 12, 'Çınaraltı Lokantası', 'Çınaraltı Lokantası', '2025-12-14 19:10:44', '1765739444_5473.png', 5.00, 1),
(7, 13, 'Güven Lokantası', 'Yemekte Bize Güvencen Baba', '2025-12-14 19:12:31', '1765739551_8969.png', 0.00, 0),
(8, 14, 'Urfa Ocağı Kebap Salonu', 'Buyur Abi Gel Adana Kebab Ye', '2025-12-14 19:14:35', '1765739691_6741.png', 5.00, 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `restaurant_ratings`
--

CREATE TABLE `restaurant_ratings` (
  `id` int(11) NOT NULL,
  `restaurant_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','seller','customer') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `role`, `created_at`, `profile_image`) VALUES
(1, 'Admin', 'admin@foodapp.com', '$2y$10$MtwTvTyKmW2E98.2UhqMv.8HlmXik1Uj2nNayNeIz3Im7.tyQLiyK', 'admin', '2025-12-07 13:14:20', NULL),
(3, 'ali seşen', 'sdasdasq@gmail.com', '$2y$10$Lze8sasuqyz17ZC.08NywO9Q4AadwND7K6ZRcEBlkydFo4NIS9vZq', 'customer', '2025-12-07 13:28:29', NULL),
(6, 'Sercan', 'aa1@gmail.com', '$2y$10$MtwTvTyKmW2E98.2UhqMv.8HlmXik1Uj2nNayNeIz3Im7.tyQLiyK', 'customer', '2025-12-07 20:37:57', 'user_6_1766929629.png'),
(7, 'sercan', 'asdfsdfgsdgdsfg@gmail.com', '$2y$10$q3RtK/b2PLbXAPA7nFuVM.rBc9.JiWFLX6pfvbbZsRShXLdsX3dcm', 'customer', '2025-12-09 21:56:55', NULL),
(8, 'sercanreis', 'aa11@gmail.com', '$2y$10$slx9htvrzvEjJm0tA8IHUe9XXmITIykPj2CJYIu2JZHz.oSLl7T8y', 'seller', '2025-12-10 22:22:34', NULL),
(9, 'Ali Selen', 'esnaf1@gmail.com', '$2y$10$CY7K1ln4bIu6DMqLwb8A5OpWH7lT12s4l8GbAVOH/UVI5S1.NR8nK', 'seller', '2025-12-14 19:01:10', NULL),
(10, 'Berat Kahraman', 'esnaf2@gmail.com', '$2y$10$LR4q7x9vYrFAtjfv5SNdR./8gNjem24T97vL.0j1CIo8ul254FyHe', 'seller', '2025-12-14 19:01:33', NULL),
(11, 'Efe Dağ', 'esnaf3@gmail.com', '$2y$10$hQPEPOO3BFVCmQ8ysIyKb.CaMHAvrINWMCPJsSFB.qrlY/9fGiVgK', 'seller', '2025-12-14 19:01:51', NULL),
(12, 'Faruk Öztürk', 'esnaf4@gmail.com', '$2y$10$YiGo3t/sU9DfH/Q5naKQE.qnISubdIpFhPbkvcL9o66vL9ImZSHuq', 'seller', '2025-12-14 19:02:13', NULL),
(13, 'Mert Özmet', 'esnaf5@gmail.com', '$2y$10$BYfA4lGrKKUgDj.LGSSp9eAtEJB7OWwwZfi40uPz0y10N/.5NZiE.', 'seller', '2025-12-14 19:02:36', NULL),
(14, 'Yusuf Bakır', 'esnaf6@gmail.com', '$2y$10$RpSVqnzxffVWrSEGYX5Psei8paDe.IhuNAzK4T0YeAxpZplwZha8G', 'seller', '2025-12-14 19:02:51', NULL),
(15, 'CrayzBoy', 'a1a1@gmail.com', '$2y$10$xjDt6xmDcgDUkF4WBFtjFeKm5rM.nyzLjyxmIsaJH/ViD0hKlrF6q', 'customer', '2025-12-19 11:20:08', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_points`
--

CREATE TABLE `user_points` (
  `user_id` int(11) NOT NULL,
  `points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `user_points`
--

INSERT INTO `user_points` (`user_id`, `points`) VALUES
(6, 3004);

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`user_id`,`restaurant_id`);

--
-- Tablo için indeksler `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Tablo için indeksler `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`OrderID`);

--
-- Tablo için indeksler `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`OrderItemID`),
  ADD KEY `OrderID` (`OrderID`);

--
-- Tablo için indeksler `order_ratings`
--
ALTER TABLE `order_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`);

--
-- Tablo için indeksler `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `OrderID` (`OrderID`);

--
-- Tablo için indeksler `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Tablo için indeksler `restaurant_ratings`
--
ALTER TABLE `restaurant_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Tablo için indeksler `user_points`
--
ALTER TABLE `user_points`
  ADD PRIMARY KEY (`user_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- Tablo için AUTO_INCREMENT değeri `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Tablo için AUTO_INCREMENT değeri `order_items`
--
ALTER TABLE `order_items`
  MODIFY `OrderItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- Tablo için AUTO_INCREMENT değeri `order_ratings`
--
ALTER TABLE `order_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Tablo için AUTO_INCREMENT değeri `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `restaurant_ratings`
--
ALTER TABLE `restaurant_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `menus_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`OrderID`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`OrderID`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `restaurants`
--
ALTER TABLE `restaurants`
  ADD CONSTRAINT `restaurants_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
