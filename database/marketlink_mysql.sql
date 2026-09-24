-- MarketLink database for phpMyAdmin / MySQL / MariaDB
-- Import this complete file in phpMyAdmin.

CREATE DATABASE IF NOT EXISTS marketlink
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE marketlink;

SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+05:00';

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS order_events;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS pickup_slots;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS farmer_market_days;
DROP TABLE IF EXISTS farmer_markets;
DROP TABLE IF EXISTS market_days;
DROP TABLE IF EXISTS markets;
DROP TABLE IF EXISTS farmer_profiles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS app_meta;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('customer','farmer','admin') NOT NULL,
    status ENUM('active','pending','approved','rejected','suspended','deactivated') NOT NULL DEFAULT 'active',
    name VARCHAR(80) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(25) NOT NULL,
    address VARCHAR(500) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL,
    INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE farmer_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    stall_name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    latitude DECIMAL(10,8) NULL,
    longitude DECIMAL(11,8) NULL,
    map_provider VARCHAR(30) NOT NULL DEFAULT 'OpenStreetMap',
    map_url VARCHAR(500) NULL,
    pickup_start TIME NOT NULL DEFAULT '08:00:00',
    pickup_end TIME NOT NULL DEFAULT '13:00:00',
    cutoff_time TIME NOT NULL DEFAULT '20:00:00',
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_farmer_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE markets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    address VARCHAR(500) NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    map_provider VARCHAR(30) NOT NULL DEFAULT 'OpenStreetMap',
    map_url VARCHAR(500) NULL,
    open_time TIME NOT NULL DEFAULT '08:00:00',
    close_time TIME NOT NULL DEFAULT '14:00:00',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_markets_status_name (status, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE market_days (
    market_id INT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (market_id, day_of_week),
    CONSTRAINT chk_market_day CHECK (day_of_week BETWEEN 0 AND 6),
    CONSTRAINT fk_market_day_market FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE farmer_markets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT UNSIGNED NOT NULL,
    market_id INT UNSIGNED NOT NULL,
    stall_label VARCHAR(100) NOT NULL DEFAULT '',
    pickup_instructions VARCHAR(500) NOT NULL DEFAULT '',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_farmer_market (farmer_id, market_id),
    INDEX idx_farmer_markets_market (market_id, farmer_id),
    CONSTRAINT fk_farmer_market_farmer FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_farmer_market_market FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE farmer_market_days (
    user_id INT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, day_of_week),
    CONSTRAINT chk_farmer_day CHECK (day_of_week BETWEEN 0 AND 6),
    CONSTRAINT fk_farmer_day_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(300) NOT NULL DEFAULT '',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    price_cents INT UNSIGNED NOT NULL,
    unit VARCHAR(20) NOT NULL DEFAULT 'piece',
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    image VARCHAR(255) NULL,
    status ENUM('active','unavailable','sold_out','hidden') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_products_public (status, category_id, price_cents, created_at),
    INDEX idx_products_farmer (farmer_id, status, updated_at),
    CONSTRAINT fk_product_farmer FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pickup_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT UNSIGNED NOT NULL,
    market_id INT UNSIGNED NOT NULL,
    pickup_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    cutoff_at DATETIME NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 30,
    booked_count INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('open','closed','completed') NOT NULL DEFAULT 'open',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pickup_slot (farmer_id, market_id, pickup_date, start_time, end_time),
    INDEX idx_slots_lookup (farmer_id, market_id, pickup_date, status),
    CONSTRAINT chk_slot_capacity CHECK (booked_count <= capacity),
    CONSTRAINT fk_slot_farmer FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_slot_market FOREIGN KEY (market_id) REFERENCES markets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_user_product (user_id, product_id),
    CONSTRAINT chk_cart_quantity CHECK (quantity > 0),
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(30) NOT NULL UNIQUE,
    customer_id INT UNSIGNED NOT NULL,
    farmer_id INT UNSIGNED NOT NULL,
    pickup_market_id INT UNSIGNED NOT NULL,
    pickup_slot_id INT UNSIGNED NOT NULL,
    status ENUM('placed','accepted','ready','completed','declined','cancelled') NOT NULL DEFAULT 'placed',
    subtotal_cents INT UNSIGNED NOT NULL,
    note VARCHAR(500) NOT NULL DEFAULT '',
    pickup_address VARCHAR(500) NOT NULL,
    cutoff_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    accepted_at DATETIME NULL,
    ready_at DATETIME NULL,
    completed_at DATETIME NULL,
    declined_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    INDEX idx_orders_customer (customer_id, created_at),
    INDEX idx_orders_farmer (farmer_id, status, created_at),
    CONSTRAINT fk_order_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_order_farmer FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_order_market FOREIGN KEY (pickup_market_id) REFERENCES markets(id) ON DELETE RESTRICT,
    CONSTRAINT fk_order_slot FOREIGN KEY (pickup_slot_id) REFERENCES pickup_slots(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    price_cents INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total_cents INT UNSIGNED NOT NULL,
    reviewed_at DATETIME NULL,
    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_product (product_id),
    CONSTRAINT chk_order_item_quantity CHECK (quantity > 0),
    CONSTRAINT fk_order_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    actor_id INT UNSIGNED NOT NULL,
    event_type VARCHAR(30) NOT NULL,
    from_status VARCHAR(20) NULL,
    to_status VARCHAR(20) NULL,
    note VARCHAR(500) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_events_order (order_id, created_at),
    CONSTRAINT fk_order_event_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_event_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favorites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    item_type ENUM('farmer','product','market') NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favorite (user_id, item_type, item_id),
    CONSTRAINT fk_favorite_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    order_item_id INT UNSIGNED NOT NULL UNIQUE,
    product_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    farmer_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    status ENUM('published','hidden','removed') NOT NULL DEFAULT 'published',
    response VARCHAR(1000) NOT NULL DEFAULT '',
    response_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reviews_product (product_id, status, created_at),
    CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_review_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    CONSTRAINT fk_review_order_item FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE RESTRICT,
    CONSTRAINT fk_review_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    CONSTRAINT fk_review_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_review_farmer FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'general',
    title VARCHAR(150) NOT NULL,
    message VARCHAR(700) NOT NULL,
    link VARCHAR(500) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user (user_id, is_read, created_at),
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL,
    message VARCHAR(800) NOT NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'published',
    created_by INT UNSIGNED NULL,
    published_at DATETIME NULL,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_announcements_status (status, published_at),
    CONSTRAINT fk_announcement_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    email VARCHAR(120) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new','read','closed') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT UNSIGNED NULL,
    details VARCHAR(700) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_actor (actor_id, created_at),
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE app_meta (
    meta_key VARCHAR(80) PRIMARY KEY,
    meta_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo accounts. Plaintext passwords are documented only in DEMO_CREDENTIALS.txt.
INSERT INTO users (id, role, status, name, email, phone, address, password_hash) VALUES
(1, 'admin', 'active', 'MarketLink Admin', 'admin@marketlink.local', '+92 300 9000001', 'MarketLink Office, Lahore', '$2y$10$mnflaSJo8Tx/uyvtsJpKduWP6FvOUFI9w09gbqjoBGdPggq7EiMk2'),
(2, 'farmer', 'approved', 'Ayesha Khan', 'farmer@marketlink.local', '+92 300 1234567', 'Green Valley Farm, Kahlector Road, Lahore', '$2y$10$P16h3KdWu/JojZSjGMtr.OpdnJVy1tU8ijC6BZtqgPXkRurXya3LS'),
(3, 'farmer', 'approved', 'Bilal Ahmed', 'farmer2@marketlink.local', '+92 301 2345678', 'Sunrise Family Farm, Model Town, Lahore', '$2y$10$P16h3KdWu/JojZSjGMtr.OpdnJVy1tU8ijC6BZtqgPXkRurXya3LS'),
(4, 'farmer', 'approved', 'Hina Aslam', 'farmer3@marketlink.local', '+92 302 3456789', 'Honey Bee Acres, Gulshan-e-Iqbal, Lahore', '$2y$10$P16h3KdWu/JojZSjGMtr.OpdnJVy1tU8ijC6BZtqgPXkRurXya3LS'),
(5, 'customer', 'active', 'Sara Ali', 'customer@marketlink.local', '+92 303 4567890', 'House 24, Street 7, Gulberg III, Lahore', '$2y$10$IId1eSfum6VGm2pGnJr9y.yzHULy4Pd3pBwck63ERe.yY2AEUyXza'),
(6, 'customer', 'active', 'Omar Hassan', 'customer2@marketlink.local', '+92 304 5678901', 'House 8, Street 12, Model Town, Lahore', '$2y$10$IId1eSfum6VGm2pGnJr9y.yzHULy4Pd3pBwck63ERe.yY2AEUyXza'),
(7, 'farmer', 'pending', 'Usman Raza', 'pendingfarmer@marketlink.local', '+92 305 6789012', 'New Growth Farm, Lahore', '$2y$10$1uGb9WfAo7naKebW2ccKk.wLuZSzJ03rddZuectSKCYxc4o2AoMKi'),
(8, 'customer', 'active', 'Noor Fatima', 'customer3@marketlink.local', '+92 306 7890123', 'House 17, Askari X, Lahore', '$2y$10$IId1eSfum6VGm2pGnJr9y.yzHULy4Pd3pBwck63ERe.yY2AEUyXza');

INSERT INTO farmer_profiles (user_id, stall_name, description, latitude, longitude, pickup_start, pickup_end, cutoff_time, approved_at) VALUES
(2, 'Green Valley Organics', 'Regenerative vegetables, farm-fresh eggs, and honest produce grown with care for our neighborhood.', 31.51070000, 74.34410000, '08:00:00', '13:00:00', '20:00:00', CURRENT_TIMESTAMP),
(3, 'Sunrise Family Farm', 'Sun-ripened fruit and pantry staples from our family plots in Model Town.', 31.48090000, 74.13580000, '09:00:00', '14:00:00', '21:00:00', CURRENT_TIMESTAMP),
(4, 'Honey Bee Acres', 'Small-batch bread, honey, cheese, and orchard fruit made close to home.', 31.51000000, 74.14530000, '10:00:00', '15:00:00', '20:30:00', CURRENT_TIMESTAMP),
(7, 'New Growth Farm', 'A new local grower preparing seasonal leafy vegetables.', 31.52020000, 74.35880000, '08:30:00', '13:30:00', '20:00:00', NULL);

INSERT INTO markets (id, name, description, address, latitude, longitude, open_time, close_time, status) VALUES
(1, 'Green Valley Farmers Market', 'A friendly weekly market for seasonal produce and neighborhood pantry staples.', 'Gulberg III, Main Boulevard, Lahore', 31.51070000, 74.34410000, '08:00:00', '14:00:00', 'active'),
(2, 'Fresh Roots Community Market', 'Small local growers, family recipes, and produce harvested close to the market.', 'Model Town, Bank Road, Lahore', 31.48090000, 74.13580000, '09:00:00', '15:00:00', 'active'),
(3, 'Riverside Artisan Market', 'A weekend destination for bakers, apiarists, orchard fruit, and craft food producers.', 'Gulshan-e-Iqbal, University Road, Lahore', 31.51000000, 74.14530000, '10:00:00', '16:00:00', 'active');

INSERT INTO market_days (market_id, day_of_week) VALUES (1,6),(1,0),(2,0),(3,5),(3,6);

INSERT INTO farmer_markets (farmer_id, market_id, stall_label, pickup_instructions, status) VALUES
(2, 1, 'Stall 04', 'Enter through Gate 2 and look for the green MarketLink sign.', 'active'),
(3, 2, 'Stall 11', 'Collect from the shaded row beside the community information desk.', 'active'),
(4, 3, 'Stall 02', 'Use the riverside entrance; pickup counter is beside the bakery tent.', 'active');

INSERT INTO farmer_market_days (user_id, day_of_week) VALUES
(2,6),(2,0),(3,0),(4,5),(4,6),(7,6);

INSERT INTO categories (id, name, description, status, sort_order) VALUES
(1, 'Vegetables', 'Fresh seasonal greens, roots, and garden vegetables.', 'active', 1),
(2, 'Fruits', 'Orchard and market fruit selected for flavor and ripeness.', 'active', 2),
(3, 'Dairy', 'Milk, yogurt, cheese, and other fresh dairy choices.', 'active', 3),
(4, 'Bakery', 'Small-batch bread and baked goods.', 'active', 4),
(5, 'Eggs', 'Farm and free-range eggs collected locally.', 'active', 5),
(6, 'Pantry', 'Honey, preserves, sauces, and useful staples.', 'active', 6);

INSERT INTO products (id, farmer_id, category_id, name, description, price_cents, unit, stock_quantity, status) VALUES
(1, 2, 1, 'Organic Tomatoes', 'Vine-ripened tomatoes with a bright color and balanced sweetness.', 220, 'kg', 18, 'active'),
(2, 2, 1, 'Baby Spinach', 'Tender spinach leaves washed and packed for a quick salad.', 180, 'bag', 14, 'active'),
(3, 2, 1, 'Rainbow Carrots', 'Crunchy bunches with a mix of purple, yellow, and orange roots.', 160, 'kg', 22, 'active'),
(4, 2, 5, 'Free Range Eggs', 'Fresh eggs from free-ranging hens, collected twice weekly.', 480, 'dozen', 8, 'active'),
(5, 3, 2, 'Sweet Strawberries', 'Small, fragrant berries picked at peak ripeness for the weekend.', 650, 'box', 7, 'active'),
(6, 3, 2, 'Desi Mangoes', 'Tree-ripened mangoes with a rich aroma and no long-distance shipping.', 480, 'kg', 14, 'active'),
(7, 3, 5, 'Pasture Raised Eggs', 'Golden-yolk eggs from hens raised on a small local pasture.', 520, 'dozen', 10, 'active'),
(8, 3, 6, 'Wildflower Honey', 'Unfiltered seasonal honey from hives at the edge of the farm.', 850, 'jar', 9, 'active'),
(9, 4, 4, 'Country Sourdough', 'Long-fermented country loaf with a crisp crust and soft center.', 350, 'loaf', 9, 'active'),
(10, 4, 3, 'Creamy Goat Cheese', 'Fresh goat cheese rolled with herbs and sea salt.', 950, 'piece', 5, 'active'),
(11, 4, 2, 'Orchard Apples', 'Crisp local apples sorted by size and packed in reusable bags.', 390, 'kg', 15, 'active'),
(12, 4, 1, 'Fresh Mint Bundle', 'A generous bundle of aromatic mint for tea, chutney, and salads.', 80, 'bundle', 20, 'active'),
(13, 2, 1, 'Market Salad Bowl', 'A ready-to-enjoy seasonal mix of greens and vegetables.', 450, 'bowl', 0, 'sold_out');

INSERT INTO announcements (title, message, status, created_by, published_at) VALUES
('Saturday market is open', 'Green Valley Farmers Market opens at 8:00 AM this Saturday. Popular leafy greens may sell out early.', 'published', 1, CURRENT_TIMESTAMP);

INSERT INTO favorites (user_id, item_type, item_id) VALUES
(5, 'farmer', 2),
(5, 'product', 1),
(5, 'market', 1),
(6, 'farmer', 3);

INSERT INTO app_meta (meta_key, meta_value) VALUES
('schema_version', '1'),
('demo_operational_seed', '0');

SET FOREIGN_KEY_CHECKS = 1;
