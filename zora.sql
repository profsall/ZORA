-- =============================================================
-- ZORA Electronics Store Database
-- Target: MySQL 5.7+ / MariaDB 10.4+ (XAMPP)
-- Import file ini melalui phpMyAdmin atau command line.
-- =============================================================

CREATE DATABASE IF NOT EXISTS `zora`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `zora`;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `wishlist_items`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `carts`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `user_addresses`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS: akun customer dan administrator
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(25) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL COMMENT 'Simpan hasil password_hash(), jangan plain text',
  `role` ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  `status` ENUM('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `email_verified_at` DATETIME DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role_status` (`role`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CATEGORIES: kategori produk
CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image_url` VARCHAR(500) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. PRODUCTS: katalog produk ZORA
CREATE TABLE `products` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` BIGINT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(180) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `sku` VARCHAR(80) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `compare_at_price` DECIMAL(14,2) DEFAULT NULL COMMENT 'Harga sebelum diskon; NULL jika tidak ada',
  `stock` INT UNSIGNED NOT NULL DEFAULT 0,
  `color` VARCHAR(80) DEFAULT NULL,
  `tag` VARCHAR(30) DEFAULT NULL,
  `image_url` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('draft','active','archived') NOT NULL DEFAULT 'active',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  UNIQUE KEY `uq_products_sku` (`sku`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_status_featured` (`status`,`is_featured`),
  KEY `idx_products_price` (`price`),
  CONSTRAINT `fk_products_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PRODUCT_IMAGES: foto tambahan untuk galeri detail produk
CREATE TABLE `product_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `image_url` VARCHAR(500) NOT NULL,
  `alt_text` VARCHAR(180) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product` (`product_id`,`sort_order`),
  CONSTRAINT `fk_product_images_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. USER_ADDRESSES: alamat pengiriman milik pengguna
CREATE TABLE `user_addresses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `recipient_name` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(25) NOT NULL,
  `label` VARCHAR(50) DEFAULT 'Rumah',
  `province` VARCHAR(100) DEFAULT NULL,
  `city` VARCHAR(100) NOT NULL,
  `district` VARCHAR(100) DEFAULT NULL,
  `village` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(10) DEFAULT NULL,
  `address_detail` TEXT NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_addresses_user` (`user_id`),
  CONSTRAINT `fk_addresses_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. CARTS: keranjang pengguna atau sesi tamu
CREATE TABLE `carts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `session_id` VARCHAR(128) DEFAULT NULL,
  `status` ENUM('active','converted','abandoned') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_carts_user_status` (`user_id`,`status`),
  KEY `idx_carts_session_status` (`session_id`,`status`),
  CONSTRAINT `fk_carts_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. CART_ITEMS: item dalam keranjang
CREATE TABLE `cart_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cart_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(14,2) NOT NULL COMMENT 'Snapshot harga saat item dimasukkan ke keranjang',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_product` (`cart_id`,`product_id`),
  KEY `idx_cart_items_product` (`product_id`),
  CONSTRAINT `fk_cart_items_cart`
    FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_cart_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. ORDERS: transaksi utama dan snapshot alamat pembeli
CREATE TABLE `orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(40) NOT NULL,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `recipient_name` VARCHAR(120) NOT NULL,
  `recipient_phone` VARCHAR(25) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `shipping_city` VARCHAR(100) DEFAULT NULL,
  `shipping_province` VARCHAR(100) DEFAULT NULL,
  `shipping_postal_code` VARCHAR(10) DEFAULT NULL,
  `subtotal` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `order_status` ENUM('pending','confirmed','processing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  `customer_note` TEXT DEFAULT NULL,
  `ordered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_number` (`order_number`),
  KEY `idx_orders_user_date` (`user_id`,`ordered_at`),
  KEY `idx_orders_status_date` (`order_status`,`ordered_at`),
  CONSTRAINT `fk_orders_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. ORDER_ITEMS: snapshot nama, SKU, gambar, dan harga saat transaksi
CREATE TABLE `order_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(180) NOT NULL,
  `product_sku` VARCHAR(80) DEFAULT NULL,
  `product_image` VARCHAR(500) DEFAULT NULL,
  `unit_price` DECIMAL(14,2) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `line_total` DECIMAL(14,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. PAYMENTS: metode dan status pembayaran
CREATE TABLE `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `payment_method` ENUM('bank_transfer','qris','ewallet','cod') NOT NULL DEFAULT 'bank_transfer',
  `payment_status` ENUM('pending','waiting_verification','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `amount` DECIMAL(14,2) NOT NULL,
  `proof_image` VARCHAR(500) DEFAULT NULL,
  `provider_reference` VARCHAR(150) DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_order` (`order_id`),
  KEY `idx_payments_status` (`payment_status`),
  CONSTRAINT `fk_payments_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. WISHLIST_ITEMS: produk favorit customer
CREATE TABLE `wishlist_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wishlist_user_product` (`user_id`,`product_id`),
  CONSTRAINT `fk_wishlist_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- DATA AWAL: kategori dan produk contoh (sesuai prototype UI ZORA)
-- =============================================================

INSERT INTO `categories` (`id`,`name`,`slug`,`description`,`sort_order`) VALUES
(1,'Smartphones','smartphones','Smartphone untuk kebutuhan sehari-hari.',1),
(2,'Computers','computers','Laptop dan tablet untuk kerja serta hiburan.',2),
(3,'Audio','audio','Headphone dan speaker untuk pengalaman audio.',3),
(4,'Wearables','wearables','Smartwatch dan perangkat wearable.',4),
(5,'Cameras','cameras','Kamera untuk mengabadikan momen.',5),
(6,'Accessories','accessories','Aksesori elektronik dan produktivitas.',6);

INSERT INTO `products`
(`id`,`category_id`,`name`,`slug`,`sku`,`description`,`price`,`compare_at_price`,`stock`,`color`,`tag`,`image_url`,`status`,`is_featured`) VALUES
(1,1,'Nova Phone X','nova-phone-x','ZOR-PHN-001','Smartphone dengan desain ringkas, layar jernih, dan performa untuk aktivitas sehari-hari.',4299000,4799000,25,'Midnight Blue','NEW','https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=85','active',1),
(2,2,'AirBook 14','airbook-14','ZOR-LPT-001','Laptop ringan untuk bekerja, belajar, dan menikmati hiburan dengan nyaman.',8999000,9499000,12,'Silver','POPULAR','https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=900&q=85','active',1),
(3,3,'Studio Headphones','studio-headphones','ZOR-AUD-001','Headphone wireless dengan suara seimbang dan bantalan yang nyaman digunakan.',1299000,NULL,30,'Navy',NULL,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=85','active',1),
(4,4,'Motion Watch','motion-watch','ZOR-WAT-001','Jam pintar dengan tampilan minimal dan fitur praktis untuk rutinitas harian.',1799000,1999000,18,'Black','NEW','https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=900&q=85','active',1),
(5,3,'Room Speaker Mini','room-speaker-mini','ZOR-AUD-002','Speaker compact untuk menghadirkan musik di ruang kerja atau rumah.',899000,NULL,22,'Cloud White',NULL,'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?auto=format&fit=crop&w=900&q=85','active',0),
(6,5,'Pocket Camera','pocket-camera','ZOR-CAM-001','Kamera praktis untuk mengabadikan momen dan membuat konten sehari-hari.',3599000,3999000,9,'Graphite','LIMITED','https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=900&q=85','active',0),
(7,2,'Tab S Lite','tab-s-lite','ZOR-TAB-001','Tablet serbaguna untuk membaca, mencatat, dan menikmati konten favorit.',2899000,NULL,16,'Silver',NULL,'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&w=900&q=85','active',0),
(8,6,'Precision Mouse','precision-mouse','ZOR-ACC-001','Mouse ergonomis dengan bentuk sederhana untuk menunjang produktivitas.',349000,399000,40,'White',NULL,'https://images.unsplash.com/photo-1527814050087-3793815479db?auto=format&fit=crop&w=900&q=85','active',0);

-- Akun admin awal untuk pengembangan lokal.
-- Email: admin@zora.test | Password awal: AdminZora123!
-- Ganti password ini setelah autentikasi backend diimplementasikan.
INSERT INTO `users` (`name`,`email`,`password`,`role`,`status`) VALUES
('ZORA Administrator','admin@zora.test','$2y$12$3wr5lz5vgeJhGrflfNTKb.pNXj/jFV1VpZjUdV5cU/jL6ehP7dOGy','admin','active');

-- Reset AUTO_INCREMENT setelah seed menggunakan ID eksplisit.
ALTER TABLE `categories` AUTO_INCREMENT = 7;
ALTER TABLE `products` AUTO_INCREMENT = 9;

-- =============================================================
-- SELESAI. Ringkasan tabel:
-- users, categories, products, product_images, user_addresses,
-- carts, cart_items, orders, order_items, payments, wishlist_items.
-- =============================================================
