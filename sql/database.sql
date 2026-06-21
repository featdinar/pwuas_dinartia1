-- Database creation script for Unsent
CREATE DATABASE IF NOT EXISTS `unsent_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `unsent_db`;

-- Drop tables if they exist
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `songs`;
DROP TABLE IF EXISTS `premium_packages`;
DROP TABLE IF EXISTS `users`;

-- Users table
CREATE TABLE `users` (
  `id_user` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'user') DEFAULT 'user',
  `premium_status` TINYINT(1) DEFAULT 0,
  `premium_until` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Songs table
CREATE TABLE `songs` (
  `id_song` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `artist` VARCHAR(100) NOT NULL,
  `link` VARCHAR(255) NOT NULL,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `spotify_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages table
CREATE TABLE `messages` (
  `id_message` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `recipient_name` VARCHAR(100) NOT NULL,
  `message_content` TEXT NOT NULL,
  `id_song` INT NOT NULL,
  `anonymous` TINYINT(1) DEFAULT 0,
  `private_message` TINYINT(1) DEFAULT 0,
  `theme` VARCHAR(50) DEFAULT 'cream',
  `scheduled_date` DATE NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  FOREIGN KEY (`id_song`) REFERENCES `songs` (`id_song`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Premium Packages table
CREATE TABLE `premium_packages` (
  `id_package` INT AUTO_INCREMENT PRIMARY KEY,
  `package_name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `duration_days` INT NOT NULL,
  `features` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments table
CREATE TABLE `payments` (
  `id_payment` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `id_package` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
  `payment_status` ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
  `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
  FOREIGN KEY (`id_package`) REFERENCES `premium_packages` (`id_package`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEED DATA
-- Seed users (Admin password is 'admin123', User password is 'user123')
INSERT INTO `users` (`id_user`, `name`, `email`, `password`, `role`, `premium_status`, `premium_until`) VALUES
(1, 'Administrator', 'admin@unsent.com', '$2y$10$wK1WwzTfL4LqV/R2r7aPweB9e4GZ09aGqfBfWz9V6cR7aG.z1PzSq', 'admin', 0, NULL),
(2, 'Jane Doe', 'jane@example.com', '$2y$10$X8m17gN5oZsc15jV.K34eeU/Gk.iH32nK.K9vT.7GcrE1Lye0mO5S', 'user', 0, NULL),
(3, 'John Doe', 'john@example.com', '$2y$10$X8m17gN5oZsc15jV.K34eeU/Gk.iH32nK.K9vT.7GcrE1Lye0mO5S', 'user', 1, '2027-12-31 23:59:59');

-- Seed songs
INSERT INTO `songs` (`id_song`, `title`, `artist`, `link`, `cover_image`, `spotify_url`) VALUES
(1, 'Glimpse of Us', 'Joji', 'https://www.youtube.com/watch?v=FvOpPnUXRYY', 'https://i.scdn.co/image/ab67616d0000b2731c2cfbf948574ba85c4a7966', 'https://open.spotify.com/track/6zSp6ex6V4Kx1x1CC27g5f'),
(2, 'Heather', 'Conan Gray', 'https://www.youtube.com/watch?v=24u3NoPmgpg', 'https://i.scdn.co/image/ab67616d0000b27341e31d04bd6c29c80d876378', 'https://open.spotify.com/track/4mx270uEnuLgkygC62c35E'),
(3, 'Someone Like You', 'Adele', 'https://www.youtube.com/watch?v=hLQl3WQQoQ0', 'https://i.scdn.co/image/ab67616d0000b273212f45de4fa23126d4001d8a', 'https://open.spotify.com/track/1CkvOZp1OJ24mg6r36Ujb5'),
(4, 'Happier', 'Olivia Rodrigo', 'https://www.youtube.com/watch?v=Z5g_7-gN0B8', 'https://i.scdn.co/image/ab67616d0000b273a91b1fb4b9fb0e7740f9076f', 'https://open.spotify.com/track/2tGvwE8vJnw4XqwA5V267v'),
(5, 'Ghost', 'Justin Bieber', 'https://www.youtube.com/watch?v=F5tSoaJ93ac', 'https://i.scdn.co/image/ab67616d0000b273e23b1f5e612eb8b63e185856', 'https://open.spotify.com/track/6I9VvXRYFyjuZs325lhZ4B'),
(6, 'Hati-Hati di Jalan', 'Tulus', 'https://www.youtube.com/watch?v=d_M2HkXFGl0', 'https://i.scdn.co/image/ab67616d0000b2736465492d4f208170c01efc9b', 'https://open.spotify.com/track/2hkS21goT7944o4UB54hww'),
(7, 'Monokrom', 'Tulus', 'https://www.youtube.com/watch?v=0k5G6GfGkkw', 'https://i.scdn.co/image/ab67616d0000b273ea61405e3f43df658b4ecf74', 'https://open.spotify.com/track/0c6T3f2C2aV4pxtP312c32'),
(8, 'Sempurna', 'Andra and The Backbone', 'https://www.youtube.com/watch?v=4b78p2rWl0c', 'https://i.scdn.co/image/ab67616d0000b273d40bf5ff23734032d1844b2f', 'https://open.spotify.com/track/25Y23Q7l1B2Z06D2kF7B8J');

-- Seed premium packages
INSERT INTO `premium_packages` (`id_package`, `package_name`, `price`, `duration_days`, `features`) VALUES
(1, 'Basic Premium', 10000.00, 30, 'Anonymous Message, Private Message, Scheduled Message, Custom Theme'),
(2, 'Silver Premium', 25000.00, 90, 'Anonymous Message, Private Message, Scheduled Message, Custom Theme'),
(3, 'Gold Premium', 50000.00, 365, 'Anonymous Message, Private Message, Scheduled Message, Custom Theme');

-- Seed dummy messages
INSERT INTO `messages` (`id_message`, `id_user`, `recipient_name`, `message_content`, `id_song`, `anonymous`, `private_message`, `theme`, `scheduled_date`) VALUES
(1, 2, 'Adinda', 'Aku masih sering mendengarkan lagu ini dan teringat saat kita jalan-jalan di bawah hujan. Semoga kamu bahagia di sana.', 1, 0, 0, 'cream', NULL),
(2, 2, 'Budi', 'Maaf aku tidak pernah mengatakannya secara langsung, tapi keputusanmu untuk pergi adalah hal terbaik sekaligus tersulit yang pernah kuterima.', 3, 1, 0, 'cream', NULL),
(3, 3, 'Sarah', 'Pesan ini rahasia, hanya untukmu. Aku menulis ini di malam yang sangat dingin ketika merindukan suaramu.', 6, 0, 0, 'navy', NULL);
