-- ============================================================
-- SSK STORE - Complete Database Schema & Mock Data
-- Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
-- ============================================================

CREATE DATABASE IF NOT EXISTS ssk_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ssk_store;

-- ============================================================
-- SETTINGS TABLE
-- ============================================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- ADMIN USERS TABLE
-- ============================================================
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin','admin','editor') DEFAULT 'admin',
    permissions TEXT DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- CUSTOMERS TABLE
-- ============================================================
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    email_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- CUSTOMER ADDRESSES TABLE
-- ============================================================
CREATE TABLE customer_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    address_type ENUM('shipping','billing') DEFAULT 'shipping',
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    country VARCHAR(50) DEFAULT 'India',
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DEPARTMENTS TABLE (Top-level)
-- ============================================================
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- CATEGORIES TABLE (Under departments)
-- ============================================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SUBCATEGORIES TABLE (Under categories)
-- ============================================================
CREATE TABLE subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- PRODUCTS TABLE
-- ============================================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    category_id INT NOT NULL,
    subcategory_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(280) UNIQUE NOT NULL,
    short_description VARCHAR(500) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    sku VARCHAR(50) UNIQUE DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    cost_price DECIMAL(10,2) DEFAULT NULL,
    stock_qty INT DEFAULT 0,
    min_order_qty INT DEFAULT 1,
    weight DECIMAL(6,2) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    gallery TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    on_sale TINYINT(1) DEFAULT 0,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- PRODUCT REVIEWS TABLE
-- ============================================================
CREATE TABLE product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK(rating BETWEEN 1 AND 5),
    review_text TEXT DEFAULT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SHOPPING CART TABLE
-- ============================================================
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT DEFAULT NULL,
    session_id VARCHAR(100) DEFAULT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- WISHLIST TABLE
-- ============================================================
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (customer_id, product_id)
) ENGINE=InnoDB;

-- ============================================================
-- ORDERS TABLE
-- ============================================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_number VARCHAR(30) UNIQUE NOT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
    payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping_name VARCHAR(100),
    shipping_phone VARCHAR(20),
    shipping_address TEXT,
    shipping_city VARCHAR(100),
    shipping_state VARCHAR(100),
    shipping_pincode VARCHAR(10),
    shipping_country VARCHAR(50) DEFAULT 'India',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
) ENGINE=InnoDB;

-- ============================================================
-- ORDER ITEMS TABLE
-- ============================================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(50) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- ============================================================
-- COUPONS TABLE
-- ============================================================
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) UNIQUE NOT NULL,
    type ENUM('percentage','fixed') DEFAULT 'percentage',
    value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0.00,
    max_uses INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- BANNERS / SLIDERS TABLE
-- ============================================================
CREATE TABLE banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) DEFAULT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    image VARCHAR(255) NOT NULL,
    link VARCHAR(500) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    position ENUM('hero','sidebar','footer') DEFAULT 'hero',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- SHIPPING ZONES TABLE
-- ============================================================
CREATE TABLE shipping_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_name VARCHAR(100) NOT NULL,
    min_order_free_shipping DECIMAL(10,2) DEFAULT 0.00,
    flat_rate DECIMAL(10,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- ============================================================
-- CONTACT MESSAGES TABLE
-- ============================================================
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    subject VARCHAR(255) DEFAULT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- PAGES TABLE (CMS)
-- ============================================================
CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(280) UNIQUE NOT NULL,
    content LONGTEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- INSERT MOCK DATA
-- ============================================================

-- Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'SSK.STORE'),
('site_tagline', 'Shri Sahajanand Sahitya Kendra — Kalakunj Mandir, Surat'),
('site_email', 'contact@sskstore.in'),
('site_phone', '+91 9426-55-1098'),
('whatsapp_number', '+91 9426-55-1098'),
('whatsapp_url', 'https://wa.me/919426551098?text=Jai%20Swaminarayan'),
('store_hours', 'Daily (Mon – Sun): 8:00 AM – 12:00 PM & 4:00 PM – 8:00 PM IST'),
('currency_symbol', '₹'),
('currency_code', 'INR'),
('default_country', 'India'),
('default_country_code', '91'),
('free_shipping_gujarat', '500'),
('free_shipping_metro', '750'),
('free_shipping_india', '750'),
('store_address', 'Shree Swaminarayan Mandir, Kalakunj, Varachha Main Rd, opp. Water Tank, Kalakunj Society, Swaminarayan Nagar-2, Kapodra Patiya, Surat, Gujarat 395006, India'),
('facebook_url', 'https://www.facebook.com'),
('instagram_url', 'https://www.instagram.com/s_s_sahitya_kendra'),
('youtube_url', 'https://www.youtube.com');

-- Default admin account removed per security policy P0-4.
-- Provision your initial administrator account securely via CLI:
-- php tools/create-admin.php

-- Departments
INSERT INTO departments (id, name, slug, description, sort_order) VALUES
(1, 'Books', 'books', 'Spiritual and religious books in multiple languages', 1),
(2, 'Kanthis', 'kanthis', 'Sacred thread necklaces', 2),
(3, 'Japamalas', 'japamalas', 'Prayer beads for meditation and chanting', 3),
(4, 'Ghar Mandirs', 'ghar-mandirs', 'Home temple shrines for daily worship', 4),
(5, 'Puja Items', 'puja-items', 'Essential items for daily prayers and rituals', 5),
(6, 'Murtis', 'murtis', 'Sacred images and statues for worship', 6),
(7, 'Agarbatti & Dhoop', 'agarbatti-dhoop', 'Incense sticks and dhoop for worship', 7),
(8, 'Clothing', 'clothing', 'T-shirts, caps and spiritual apparel', 8),
(9, 'Calendars & Diaries', 'calendars-diaries', 'Annual calendars and daily planners', 9),
(10, 'Audio & Video', 'audio-video', 'Spiritual music, kirtans and video content', 10);

-- Categories
INSERT INTO categories (id, department_id, name, slug, sort_order) VALUES
-- Books
(1, 1, 'English', 'english', 1),
(2, 1, 'Gujarati', 'gujarati', 2),
(3, 1, 'Hindi', 'hindi', 3),
(4, 1, 'Other Languages', 'other-languages', 4),
(5, 1, 'Sanskrit', 'sanskrit', 5),
-- Kanthis
(6, 2, 'Kanthi', 'kanthi', 1),
(7, 2, 'Kanthi: Plating', 'kanthi-plating', 2),
-- Japamalas
(8, 3, 'Japamalas', 'japamalas-cat', 1),
-- Ghar Mandirs
(9, 4, 'Oxidized Ghar Mandir', 'oxidized-ghar-mandir', 1),
(10, 4, 'Wooden Ghar Mandir', 'wooden-ghar-mandir', 2),
-- Puja Items
(11, 5, 'Brass Puja Items', 'brass-puja-items', 1),
(12, 5, 'General Puja Items', 'general-puja-items', 2),
-- Murtis
(13, 6, 'Metal Murtis', 'metal-murtis', 1),
(14, 6, 'Marble Murtis', 'marble-murtis', 2),
-- Agarbatti
(15, 7, 'Agarbatti', 'agarbatti', 1),
(16, 7, 'Dhoop', 'dhoop', 2),
-- Clothing
(17, 8, 'T-Shirts', 't-shirts', 1),
(18, 8, 'Caps', 'caps', 2),
-- Calendars
(19, 9, 'Wall Calendars', 'wall-calendars', 1),
(20, 9, 'Diaries', 'diaries', 2),
-- Audio/Video
(21, 10, 'Kirtans', 'kirtans', 1),
(22, 10, 'Pravachans', 'pravachans', 2);

-- Subcategories (for Books)
INSERT INTO subcategories (id, category_id, name, slug, sort_order) VALUES
-- English Books
(1, 1, 'Vachanamrut (English)', 'vachanamrut-english', 1),
(2, 1, 'Satsang Exams (English)', 'satsang-exams-english', 2),
(3, 1, 'Children (English)', 'children-english', 3),
(4, 1, 'Pramukh Swami Maharaj (English)', 'pramukh-swami-maharaj-english', 4),
(5, 1, 'Hinduism', 'hinduism', 5),
(6, 1, 'Swaminarayan Faith', 'swaminarayan-faith', 6),
(7, 1, 'Motivational (English)', 'motivational-english', 7),
(8, 1, 'Mahant Swami Maharaj (English)', 'mahant-swami-maharaj-english', 8),
-- Gujarati Books
(9, 2, 'Vachanamrut (Gujarati)', 'vachanamrut-gujarati', 1),
(10, 2, 'Spiritual Living (Gujarati)', 'spiritual-living-gujarati', 2),
(11, 2, 'Satsang Exams (Gujarati)', 'satsang-exams-gujarati', 3),
(12, 2, 'Children (Gujarati)', 'children-gujarati', 4),
(13, 2, 'Motivational (Gujarati)', 'motivational-gujarati', 5),
(14, 2, 'Pramukh Swami Maharaj (Gujarati)', 'pramukh-swami-maharaj-gujarati', 6),
-- Hindi Books
(15, 3, 'Satsang Exams (Hindi)', 'satsang-exams-hindi', 1),
(16, 3, 'Children (Hindi)', 'children-hindi', 2),
(17, 3, 'Bhagwan Swaminarayan (Hindi)', 'bhagwan-swaminarayan-hindi', 3),
(18, 3, 'Pramukh Swami Maharaj (Hindi)', 'pramukh-swami-maharaj-hindi', 4);

-- Products (realistic mock data based on REA inspection)
INSERT INTO products (department_id, category_id, subcategory_id, name, slug, short_description, description, sku, price, sale_price, stock_qty, image, is_active, is_featured, on_sale) VALUES
-- Books - English
(1, 1, 1, 'Vachanamrut (English Translation)', 'vachanamrut-english-translation', 'The definitive English translation of the Vachanamrut', 'The Vachanamrut is a compilation of 273 spiritual discourses delivered by Bhagwan Swaminarayan. This comprehensive English translation brings the profound wisdom of these teachings to English-speaking devotees worldwide. Hardcover edition with detailed commentary and explanatory notes.', 'BK-EN-001', 450.00, 399.00, 50, 'vachanamrut-english.jpg', 1, 1, 1),
(1, 1, 1, 'Vachanamrut Study Guide', 'vachanamrut-study-guide', 'A comprehensive guide for studying the Vachanamrut', 'This study guide provides chapter-wise analysis, key themes, and discussion questions for in-depth study of the Vachanamrut. Ideal for satsang study circles and personal reflection.', 'BK-EN-002', 250.00, NULL, 75, 'vachanamrut-guide.jpg', 1, 0, 0),
(1, 1, 2, 'Satsang Pravin Exam Guide', 'satsang-pravin-exam-guide', 'Complete preparation guide for Satsang Pravin exam', 'Official study material for the annual Satsang Pravin examination. Contains syllabus, study notes, model questions and answers covering scriptures, history and philosophy.', 'BK-EN-003', 180.00, NULL, 100, 'satsang-pravin.jpg', 1, 1, 0),
(1, 1, 3, 'My Swaminarayan Activity Book', 'my-swaminarayan-activity-book', 'Fun activity book for children', 'Colorful activity book designed for children aged 5-10. Contains puzzles, coloring pages, word searches and stories about Bhagwan Swaminarayan and the guru parampara.', 'BK-EN-004', 120.00, 99.00, 200, 'activity-book.jpg', 1, 1, 1),
(1, 1, 4, 'Pramukh Swami Maharaj - His Life and Legacy', 'pramukh-swami-maharaj-life-legacy', 'A comprehensive biography of Pramukh Swami Maharaj', 'This definitive biography chronicles the extraordinary life of His Holiness Pramukh Swami Maharaj, the fifth spiritual successor of Bhagwan Swaminarayan. A 500-page hardcover with rare photographs.', 'BK-EN-005', 1200.00, 999.00, 30, 'psm-biography.jpg', 1, 1, 1),
(1, 1, 5, 'Understanding Hinduism', 'understanding-hinduism', 'A clear introduction to Hindu philosophy and practice', 'An accessible guide exploring the core concepts, practices, and traditions of Hinduism. Written for both beginners and those seeking deeper understanding. With illustrations and references.', 'BK-EN-006', 350.00, NULL, 60, 'understanding-hinduism.jpg', 1, 0, 0),
(1, 1, 6, 'Satsang Reader Volume 1', 'satsang-reader-vol-1', 'Collection of spiritual discourses and teachings', 'A curated collection of satsang talks, stories, and spiritual guidance. Designed for daily reading and reflection. Paperback edition.', 'BK-EN-007', 200.00, NULL, 90, 'satsang-reader.jpg', 1, 0, 0),
(1, 1, 8, 'Mahant Swami Maharaj - In the Joy of Others', 'mahant-swami-maharaj-joy-others', 'The life and teachings of Mahant Swami Maharaj', 'A beautifully illustrated book capturing the spiritual leadership and compassionate service of His Holiness Mahant Swami Maharaj, the current guru of BAPS.', 'BK-EN-008', 800.00, 699.00, 45, 'msm-book.jpg', 1, 1, 1),

-- Books - Gujarati
(1, 2, 9, 'Vachanamrut (Gujarati - Original)', 'vachanamrut-gujarati-original', 'The original Vachanamrut text in Gujarati', 'The original Vachanamrut scripture in Gujarati, the language in which it was compiled. Premium hardcover binding with gold-embossed lettering.', 'BK-GJ-001', 400.00, 350.00, 100, 'vachanamrut-gujarati.jpg', 1, 1, 1),
(1, 2, 10, 'Jeevan No Aachar', 'jeevan-no-aachar', 'Guide to spiritual living in Gujarati', 'A practical guide to applying spiritual principles in daily life. Covers topics such as devotion, discipline, service, and developing divine qualities.', 'BK-GJ-002', 175.00, NULL, 80, 'jeevan-aachar.jpg', 1, 0, 0),
(1, 2, 11, 'Satsang Prashnotari 2026', 'satsang-prashnotari-2026', 'Satsang exam question bank 2026 in Gujarati', 'Official question bank for the annual Satsang examination in Gujarati. Includes multiple choice, short answer and essay-type questions with answers.', 'BK-GJ-003', 150.00, NULL, 150, 'prashnotari-2026.jpg', 1, 0, 0),
(1, 2, 12, 'Bal Sahitya Set (Gujarati)', 'bal-sahitya-set-gujarati', 'Set of 5 children books in Gujarati', 'A delightful set of 5 illustrated story books for children in Gujarati. Stories of saints, devotees, and moral values presented in engaging narrative form.', 'BK-GJ-004', 500.00, 425.00, 60, 'bal-sahitya.jpg', 1, 1, 1),

-- Books - Hindi  
(1, 3, 15, 'Satsang Pariksha Guide (Hindi)', 'satsang-pariksha-guide-hindi', 'Satsang exam preparation in Hindi', 'Complete study material for the annual Satsang examination in Hindi. Covers all prescribed texts, history, and philosophy with detailed explanations.', 'BK-HI-001', 160.00, NULL, 120, 'pariksha-hindi.jpg', 1, 0, 0),
(1, 3, 16, 'Bal Katha Sangrah (Hindi)', 'bal-katha-sangrah-hindi', 'Children story collection in Hindi', 'A collection of inspiring stories for children in Hindi. Each story carries a moral lesson rooted in the Swaminarayan tradition.', 'BK-HI-002', 120.00, NULL, 90, 'bal-katha-hindi.jpg', 1, 0, 0),
(1, 3, 17, 'Bhagwan Swaminarayan - Jeevan Charitra (Hindi)', 'bhagwan-swaminarayan-jeevan-charitra-hindi', 'Life story of Bhagwan Swaminarayan in Hindi', 'An illustrated biography of Bhagwan Swaminarayan from His birth in Chhapaiya to the establishment of the Swaminarayan Sampraday. Rich with historical detail.', 'BK-HI-003', 350.00, 299.00, 40, 'swaminarayan-hindi.jpg', 1, 1, 1),

-- Kanthis
(2, 6, NULL, 'Tulsi Kanthi - Double Thread', 'tulsi-kanthi-double-thread', 'Sacred tulsi bead kanthi with double thread', 'Handcrafted kanthi made from genuine tulsi wood beads strung on durable double thread. The kanthi is a sacred necklace symbolizing devotion to God. Length: 18 inches.', 'KN-001', 80.00, NULL, 500, 'tulsi-kanthi-double.jpg', 1, 1, 0),
(2, 6, NULL, 'Tulsi Kanthi - Single Thread', 'tulsi-kanthi-single-thread', 'Classic tulsi bead kanthi with single thread', 'Traditional kanthi with finely polished tulsi beads on a single cotton thread. A symbol of initiation and devotion. Length: 16 inches.', 'KN-002', 50.00, NULL, 800, 'tulsi-kanthi-single.jpg', 1, 0, 0),
(2, 7, NULL, 'Gold Plated Kanthi', 'gold-plated-kanthi', 'Premium gold-plated tulsi kanthi', 'Elegant kanthi with tulsi beads and gold-plated spacer beads. Premium quality gold plating that retains shine. Ideal for special occasions. Length: 18 inches.', 'KN-003', 250.00, 199.00, 150, 'gold-kanthi.jpg', 1, 1, 1),
(2, 7, NULL, 'Silver Plated Kanthi', 'silver-plated-kanthi', 'Premium silver-plated tulsi kanthi', 'Kanthi with tulsi beads and silver-plated spacer beads. Hypoallergenic and durable silver plating. Length: 18 inches.', 'KN-004', 200.00, NULL, 200, 'silver-kanthi.jpg', 1, 0, 0),

-- Japamalas
(3, 8, NULL, 'Tulsi Japamala - 108 Beads', 'tulsi-japamala-108-beads', 'Traditional 108-bead tulsi japamala for chanting', 'A traditional prayer mala with 108 genuine tulsi wood beads, hand-knotted on durable cotton thread with a guru bead and tassel. Essential for daily mantra chanting.', 'JP-001', 120.00, NULL, 300, 'tulsi-japamala.jpg', 1, 1, 0),
(3, 8, NULL, 'Sandalwood Japamala - 108 Beads', 'sandalwood-japamala-108-beads', 'Fragrant sandalwood 108-bead japamala', 'Premium sandalwood japamala with 108 beads. The natural fragrance of sandalwood enhances meditation and spiritual focus. Hand-knotted with guru bead.', 'JP-002', 350.00, 299.00, 100, 'sandalwood-japamala.jpg', 1, 1, 1),
(3, 8, NULL, 'Crystal Japamala - 108 Beads', 'crystal-japamala-108-beads', 'Clear crystal 108-bead japamala', 'Beautiful crystal bead japamala ideal for meditation. Each bead is polished for smoothness. The clarity of crystal symbolizes purity of devotion.', 'JP-003', 250.00, NULL, 150, 'crystal-japamala.jpg', 1, 0, 0),

-- Ghar Mandirs
(4, 9, NULL, 'Oxidized Ghar Mandir - Small', 'oxidized-ghar-mandir-small', 'Compact oxidized metal ghar mandir', 'A beautifully crafted small ghar mandir in oxidized silver finish. Features intricate dome and pillar designs inspired by temple architecture. Perfect for apartments. Dimensions: 12" x 8" x 16".', 'GM-001', 2500.00, 2199.00, 20, 'ghar-mandir-small.jpg', 1, 1, 1),
(4, 9, NULL, 'Oxidized Ghar Mandir - Medium', 'oxidized-ghar-mandir-medium', 'Medium-sized oxidized metal ghar mandir', 'An elegant medium ghar mandir in oxidized silver finish with ornate detailing. Includes LED light provision. Dimensions: 18" x 12" x 24".', 'GM-002', 4500.00, NULL, 15, 'ghar-mandir-medium.jpg', 1, 1, 0),
(4, 10, NULL, 'Wooden Ghar Mandir - Premium Teak', 'wooden-ghar-mandir-premium-teak', 'Premium teak wood ghar mandir with carving', 'Hand-carved teak wood ghar mandir with traditional Swaminarayan temple architecture. Features detailed floral and deity carvings. Includes shelf for murtis. Dimensions: 24" x 14" x 30".', 'GM-003', 8500.00, 7499.00, 8, 'ghar-mandir-teak.jpg', 1, 1, 1),

-- Puja Items
(5, 11, NULL, 'Brass Diya Set (4 pcs)', 'brass-diya-set-4pcs', 'Set of 4 traditional brass diyas', 'Handcrafted solid brass diya set of 4 pieces. Traditional design with lotus petal edges. Perfect for daily aarti and festive decorations. Polished to a mirror finish.', 'PJ-001', 350.00, NULL, 100, 'brass-diya-set.jpg', 1, 0, 0),
(5, 11, NULL, 'Brass Thali Set for Aarti', 'brass-thali-set-aarti', 'Complete brass aarti thali with accessories', 'A complete aarti set including thali, diya, bell, incense holder, and kumkum container. All pieces in polished brass with traditional engravings.', 'PJ-002', 750.00, 649.00, 50, 'aarti-thali.jpg', 1, 1, 1),
(5, 12, NULL, 'Chandan Tilak Set', 'chandan-tilak-set', 'Pure sandalwood tilak paste set', 'Premium quality sandalwood (chandan) paste for daily tilak application. Includes a small stone plate for preparation. Net weight: 50g.', 'PJ-003', 150.00, NULL, 200, 'chandan-set.jpg', 1, 0, 0),
(5, 12, NULL, 'Puja Samagri Kit', 'puja-samagri-kit', 'Complete daily puja essentials kit', 'All-in-one daily puja kit containing cotton wicks, camphor, kumkum, haldi, rice (akshat), incense sticks, and a small ghee container. Everything needed for daily worship.', 'PJ-004', 250.00, 199.00, 150, 'puja-kit.jpg', 1, 1, 1),

-- Murtis
(6, 13, NULL, 'Akshar Purushottam Murti - Brass (6 inch)', 'akshar-purushottam-murti-brass-6inch', 'Brass murti of Akshar Purushottam', 'Finely detailed brass murti of Akshar Purushottam Maharaj. Standing pose with abhay mudra. Hand-polished finish. Height: 6 inches.', 'MR-001', 1800.00, 1599.00, 25, 'ap-murti-brass.jpg', 1, 1, 1),
(6, 14, NULL, 'Harikrishna Maharaj Murti - Marble (8 inch)', 'harikrishna-maharaj-murti-marble-8inch', 'White marble murti of Shri Harikrishna Maharaj', 'Hand-carved white marble murti of Shri Harikrishna Maharaj in sitting posture. Detailed crown and ornament work. Height: 8 inches.', 'MR-002', 3500.00, NULL, 10, 'hkm-murti-marble.jpg', 1, 1, 0),

-- Agarbatti
(7, 15, NULL, 'SSK Premium Agarbatti - Rose (Pack of 12)', 'ssk-premium-agarbatti-rose-12', 'Rose fragrance premium incense sticks', 'Hand-rolled premium quality incense sticks with natural rose fragrance. Pack of 12 boxes (20 sticks each = 240 sticks). Low smoke formula. Prepared with sacred natural ingredients at Kalakunj Mandir.', 'AG-001', 180.00, 149.00, 300, 'agarbatti-rose.jpg', 1, 1, 1),
(7, 15, NULL, 'SSK Premium Agarbatti - Sandalwood (Pack of 12)', 'ssk-premium-agarbatti-sandalwood-12', 'Sandalwood fragrance premium incense sticks', 'Rich sandalwood aroma incense sticks. Pack of 12 boxes (20 sticks each). Created with natural sandalwood oil for an authentic temple atmosphere.', 'AG-002', 200.00, NULL, 250, 'agarbatti-sandalwood.jpg', 1, 0, 0),
(7, 16, NULL, 'Guggul Dhoop (Pack of 6)', 'guggul-dhoop-pack-6', 'Natural guggul dhoop cones', 'Premium guggul dhoop made from natural resin. Creates a purifying and aromatic environment for puja. Pack of 6 boxes (12 cones each = 72 cones).', 'AG-003', 120.00, NULL, 200, 'guggul-dhoop.jpg', 1, 0, 0),

-- Clothing
(8, 17, NULL, 'Mandir T-Shirt - White (Cotton)', 'mandir-tshirt-white-cotton', 'White cotton T-shirt with mandir print', 'Premium 100% cotton round-neck T-shirt featuring holy Mandir illustration. Available in sizes S, M, L, XL, XXL. Pre-shrunk fabric.', 'CL-001', 499.00, 399.00, 200, 'tshirt-mandir-white.jpg', 1, 1, 1),
(8, 18, NULL, 'SSK Devotional Cap - Navy Blue', 'ssk-devotional-cap-navy-blue', 'Adjustable cap with embroidered SSK logo', 'High-quality cotton cap in navy blue with embroidered SSK logo. Adjustable strap for perfect fit. One size fits all.', 'CL-002', 250.00, NULL, 150, 'cap-navy.jpg', 1, 0, 0),

-- Calendars
(9, 19, NULL, 'Kalakunj Mandir Wall Calendar 2027', 'kalakunj-mandir-wall-calendar-2027', 'Annual wall calendar with beautiful mandir images', 'Beautifully designed wall calendar featuring stunning photographs of Shree Swaminarayan Mandir, Kalakunj. Includes all Hindu festivals and tithi dates. Size: 14" x 22".', 'CD-001', 150.00, 120.00, 500, 'calendar-2027.jpg', 1, 1, 1),
(9, 20, NULL, 'SSK Daily Satsang Diary 2027', 'ssk-daily-satsang-diary-2027', 'Daily planner with spiritual quotes', 'A premium daily planner diary featuring daily spiritual quotes from Bhagwan Swaminarayan, important dates, and ample space for planning. Hardcover with ribbon bookmark. 365 pages.', 'CD-002', 350.00, 299.00, 200, 'diary-2027.jpg', 1, 1, 1);

-- Banners
INSERT INTO banners (title, subtitle, image, link, sort_order, is_active, position) VALUES
('Festive Specials & Sacred Literature', 'Authentic scriptures, sanctified tulsi kanthis, and ghar mandirs from Shree Swaminarayan Mandir, Kalakunj, Surat.', 'banner-welcome.jpg', '/catalog.php', 1, 1, 'hero'),
('New Arrivals - Books', 'Discover our latest spiritual books collection from Shri Sahajanand Sahitya Kendra', 'banner-books.jpg', '/catalog.php?dept=1', 2, 1, 'hero'),
('Free Shipping Across India', 'Free shipping on orders above ₹500 in Gujarat, ₹750 India-wide', 'banner-shipping.jpg', '#', 3, 1, 'hero'),
('On Sale', 'Special discounts on selected items', 'banner-sale.jpg', '/catalog.php?on_sale=1', 4, 1, 'hero');

-- Shipping Zones
INSERT INTO shipping_zones (zone_name, min_order_free_shipping, flat_rate) VALUES
('Gujarat', 500.00, 50.00),
('Metro Cities', 750.00, 75.00),
('Rest of India', 750.00, 100.00);

-- Default sample customer removed per security policy P0-4.

-- CMS Pages
INSERT INTO pages (title, slug, content) VALUES
('About Us', 'about-us', '<h2>About SSK Store</h2><p>SSK Store is the official online store of <strong>Shri Sahajanand Sahitya Kendra (Shree Swaminarayan Mandir, Kalakunj, Surat)</strong>. We offer authentic spiritual books, puja items, sanctified tulsi kanthis, carved ghar mandirs, and devotional accessories.</p>'),
('Contact Us', 'contact-us', '<h2>Contact SSK Store</h2><p><strong>Address:</strong> Shree Swaminarayan Mandir, Kalakunj, Varachha Main Rd, opp. Water Tank, Kalakunj Society, Swaminarayan Nagar-2, Kapodra Patiya, Surat, Gujarat 395006, India</p><p><strong>Phone:</strong> +91-79-2830 0100</p><p><strong>Email:</strong> contact@sskstore.in</p><p><strong>Working Hours:</strong> Monday to Saturday, 9:00 AM - 6:00 PM IST</p>'),
('Privacy Policy', 'privacy-policy', '<h2>Privacy Policy</h2><p>At SSK Store, we are committed to protecting the privacy and security of our customers personal information. This policy outlines how we collect, use, store, and protect your data when you visit our website and make purchases.</p>'),
('Return Policy', 'return-policy', '<h2>Return & Refund Policy</h2><p>We want you to be completely satisfied with your purchase. If you are not satisfied, you may return most items within 7 days of delivery for a full refund or exchange. Items must be in their original condition and packaging.</p>'),
('Terms & Conditions', 'terms-conditions', '<h2>Terms & Conditions</h2><p>By using SSK Store, you agree to these terms and conditions. Please read them carefully before making any purchase.</p>');

-- Homepage Sections
CREATE TABLE IF NOT EXISTS homepage_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL UNIQUE,
    section_title VARCHAR(100) NOT NULL,
    section_subtitle VARCHAR(255) DEFAULT '',
    badge_text VARCHAR(50) DEFAULT '',
    sort_order INT NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    items_limit INT NOT NULL DEFAULT 4,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO homepage_sections (section_key, section_title, section_subtitle, badge_text, sort_order, is_active, items_limit) VALUES
('festive_specials', 'Festive Specials', 'Handcrafted Ghar Mandirs, Murtis & Sacred Puja Thalis for Auspicious Celebrations', 'FESTIVE SPECIAL', 1, 1, 4),
('new_arrivals', 'New Arrivals', 'Freshly Printed Sacred Literature & Newly Blessed Devotional Items', 'NEWLY RELEASED', 2, 1, 4),
('top_selling', 'Top Selling', 'Most Cherished Scriptures, Holy Tulsi Kanthis & Daily Satsang Essentials', 'MOST POPULAR', 3, 1, 4),
('best_offer', 'Best Offer', 'Exclusive Limited-Period Discounts on Sacred Publications & Mandir Essentials', 'BEST VALUE', 4, 1, 4);

-- Add full-text index for search
ALTER TABLE products ADD FULLTEXT INDEX ft_product_search (name, short_description, description);
