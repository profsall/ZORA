CREATE DATABASE IF NOT EXISTS `zora`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `zora`;

SET NAMES utf8mb4;

-- 1. USERS
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_login_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CATEGORIES
CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. PRODUCTS
CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id BIGINT UNSIGNED DEFAULT NULL,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    sku VARCHAR(80) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    compare_at_price DECIMAL(14,2) DEFAULT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    color VARCHAR(80) DEFAULT NULL,
    tag VARCHAR(30) DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    status ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'active',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    UNIQUE KEY uq_products_sku (sku),
    KEY idx_products_category (category_id),
    KEY idx_products_status_featured (status, is_featured),
    KEY idx_products_price (price),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CARTS
CREATE TABLE IF NOT EXISTS carts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    session_id VARCHAR(128) DEFAULT NULL,
    status ENUM('active', 'converted', 'abandoned') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_carts_user_status (user_id, status),
    KEY idx_carts_session_status (session_id, status),
    CONSTRAINT fk_carts_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. CART ITEMS
CREATE TABLE IF NOT EXISTS cart_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cart_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cart_items_cart_product (cart_id, product_id),
    KEY idx_cart_items_product (product_id),
    CONSTRAINT fk_cart_items_cart
        FOREIGN KEY (cart_id) REFERENCES carts(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ORDERS
CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_number VARCHAR(40) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    recipient_name VARCHAR(150) NOT NULL,
    recipient_phone VARCHAR(30) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_province VARCHAR(100) DEFAULT NULL,
    shipping_postal_code VARCHAR(15) DEFAULT NULL,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    shipping_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    order_status ENUM(
        'pending', 'confirmed', 'processing',
        'shipped', 'completed', 'cancelled'
    ) NOT NULL DEFAULT 'pending',
    customer_note TEXT DEFAULT NULL,
    ordered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_order_number (order_number),
    KEY idx_orders_user_ordered (user_id, ordered_at),
    KEY idx_orders_status (order_status),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. ORDER ITEMS
CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED DEFAULT NULL,
    product_name VARCHAR(180) NOT NULL,
    product_sku VARCHAR(80) DEFAULT NULL,
    product_image VARCHAR(500) DEFAULT NULL,
    unit_price DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    line_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. PAYMENTS
CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    payment_method VARCHAR(30) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded')
        NOT NULL DEFAULT 'pending',
    amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    payment_reference VARCHAR(150) DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payments_order (order_id, id),
    KEY idx_payments_status (payment_status),
    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. SEED ADMIN USER
-- Email: admin@zora.test
-- Password: AdminZora123!
INSERT IGNORE INTO users (
    name, email, phone, password, role, status
) VALUES (
    'ZORA Administrator',
    'admin@zora.test',
    NULL,
    '$2y$12$3c3od6KM2pkeDuVb9egLkex8dN62wPQc/wHMv837./WczT8JVpi7e',
    'admin',
    'active'
);

-- 10. SEED SIX PRODUCT CATEGORIES
INSERT INTO categories (name, slug, description, sort_order)
VALUES
('Smartphones', 'smartphones', 'Smartphone untuk kebutuhan sehari-hari.', 1),
('Computers', 'computers', 'Laptop dan tablet untuk kerja serta hiburan.', 2),
('Audio', 'audio', 'Headphone dan speaker untuk pengalaman audio.', 3),
('Wearables', 'wearables', 'Smartwatch dan perangkat wearable.', 4),
('Cameras', 'cameras', 'Kamera untuk mengabadikan momen.', 5),
('Accessories', 'accessories', 'Aksesori elektronik dan produktivitas.', 6)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    sort_order = VALUES(sort_order);

-- 11. SEED 16 PRODUCTS
INSERT INTO products (
    category_id, name, slug, sku, description,
    price, compare_at_price, stock, color, tag,
    image_url, status, is_featured
)
SELECT
    c.id,
    p.name,
    p.slug,
    p.sku,
    p.description,
    p.price,
    p.compare_at_price,
    p.stock,
    p.color,
    p.tag,
    p.image_url,
    'active',
    p.is_featured
FROM (
    SELECT
        'smartphones' AS category_slug,
        'Nova Phone Pro' AS name,
        'nova-phone-pro' AS slug,
        'ZOR-PHN-002' AS sku,
        'Smartphone premium dengan kamera berkualitas tinggi dan performa responsif.' AS description,
        7499000 AS price,
        7999000 AS compare_at_price,
        20 AS stock,
        'Silver' AS color,
        'POPULAR' AS tag,
        'https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?auto=format&fit=crop&w=900&q=85' AS image_url,
        0 AS is_featured

    UNION ALL SELECT
        'smartphones',
        'Nova Phone SE',
        'nova-phone-se',
        'ZOR-PHN-003',
        'Smartphone praktis dengan desain minimalis untuk kebutuhan sehari-hari.',
        3299000,
        3599000,
        30,
        'White',
        'NEW',
        'https://images.unsplash.com/photo-1512499617640-c74ae3a79d37?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'smartphones',
        'Nova Fold',
        'nova-fold',
        'ZOR-PHN-004',
        'Smartphone layar lipat dengan desain modern dan pengalaman multitasking.',
        9999000,
        10999000,
        10,
        'Black',
        'NEW',
        'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'computers',
        'AirBook Pro 16',
        'airbook-pro-16',
        'ZOR-LPT-002',
        'Laptop performa tinggi untuk pekerjaan profesional dan kebutuhan kreatif.',
        14999000,
        15999000,
        10,
        'Space Gray',
        'POPULAR',
        'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'computers',
        'Creator Laptop 15',
        'creator-laptop-15',
        'ZOR-LPT-003',
        'Laptop untuk produktivitas, desain grafis, dan aktivitas multimedia.',
        6499000,
        6999000,
        15,
        'Silver',
        'NEW',
        'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'audio',
        'Wireless Earbuds Pro',
        'wireless-earbuds-pro',
        'ZOR-AUD-003',
        'Earbuds wireless dengan desain compact dan kualitas suara jernih.',
        1499000,
        1699000,
        35,
        'White',
        'NEW',
        'https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'audio',
        'Bluetooth Speaker Go',
        'bluetooth-speaker-go',
        'ZOR-AUD-004',
        'Speaker portable dengan desain minimalis untuk menemani aktivitas.',
        799000,
        899000,
        25,
        'Black',
        NULL,
        'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'wearables',
        'Fit Band 2',
        'fit-band-2',
        'ZOR-WAT-002',
        'Smart band ringan untuk mendukung aktivitas dan rutinitas harian.',
        699000,
        799000,
        30,
        'Black',
        'NEW',
        'https://images.unsplash.com/photo-1544117519-31a4b719223d?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'wearables',
        'Motion Watch Pro',
        'motion-watch-pro',
        'ZOR-WAT-003',
        'Smartwatch premium dengan tampilan modern dan fitur pintar.',
        2599000,
        2899000,
        18,
        'Silver',
        'POPULAR',
        'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'wearables',
        'Smart Ring',
        'smart-ring',
        'ZOR-WAT-004',
        'Perangkat wearable berbentuk cincin dengan desain sederhana.',
        1899000,
        NULL,
        12,
        'Titanium',
        'NEW',
        'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'cameras',
        'Mirrorless Camera M1',
        'mirrorless-camera-m1',
        'ZOR-CAM-002',
        'Kamera mirrorless untuk fotografi dan kebutuhan konten profesional.',
        8999000,
        9499000,
        8,
        'Black',
        'POPULAR',
        'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'cameras',
        'Action Camera 4K',
        'action-camera-4k',
        'ZOR-CAM-003',
        'Kamera aksi compact untuk dokumentasi aktivitas dan perjalanan.',
        1899000,
        2199000,
        14,
        'Black',
        'NEW',
        'https://images.unsplash.com/photo-1510127034890-2eea7e4c6a69?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'cameras',
        'Instant Camera',
        'instant-camera',
        'ZOR-CAM-004',
        'Kamera instan dengan desain klasik untuk mengabadikan momen.',
        2499000,
        NULL,
        11,
        'White',
        NULL,
        'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'accessories',
        'USB-C Hub 7-in-1',
        'usb-c-hub-7in1',
        'ZOR-ACC-002',
        'Hub USB-C multifungsi untuk menghubungkan berbagai perangkat.',
        899000,
        999000,
        40,
        'Silver',
        'NEW',
        'https://images.unsplash.com/photo-1625842268584-8f3296236761?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'accessories',
        'Mechanical Keyboard Mini',
        'mechanical-keyboard-mini',
        'ZOR-ACC-003',
        'Keyboard mechanical compact untuk produktivitas dan kebutuhan gaming.',
        599000,
        699000,
        25,
        'White',
        'POPULAR',
        'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=900&q=85',
        0

    UNION ALL SELECT
        'accessories',
        'Power Bank 20K',
        'power-bank-20k',
        'ZOR-ACC-004',
        'Power bank berkapasitas besar untuk menunjang mobilitas perangkat.',
        349000,
        399000,
        45,
        'Black',
        NULL,
        'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5?auto=format&fit=crop&w=900&q=85',
        0
) AS p
INNER JOIN categories c
    ON c.slug = p.category_slug
WHERE NOT EXISTS (
    SELECT 1
    FROM products existing
    WHERE existing.slug = p.slug
       OR existing.sku = p.sku
);

SELECT
    c.name AS category,
    COUNT(p.id) AS active_products
FROM categories c
LEFT JOIN products p
    ON p.category_id = c.id
   AND p.status = 'active'
WHERE c.is_active = 1
GROUP BY c.id, c.name, c.sort_order
ORDER BY c.sort_order;
