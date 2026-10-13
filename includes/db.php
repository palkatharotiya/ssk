<?php
/**
 * BAPS Store - Database Connection Handler
 * Supports both MySQL and SQLite fallback
 */

require_once __DIR__ . '/config.php';

class DB {
    private static ?PDO $pdo = null;
    private static string $driver = 'mysql';

    public static function connect(): PDO {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // Try MySQL first
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            self::$driver = 'mysql';
            return self::$pdo;
        } catch (PDOException $e) {
            // Fallback to SQLite for zero-config portable execution
            $sqlitePath = ROOT_PATH . 'baps_store.sqlite';
            $needsInit = !file_exists($sqlitePath) || filesize($sqlitePath) === 0;

            try {
                self::$pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$driver = 'sqlite';

                if ($needsInit) {
                    self::initializeSqliteSchema(self::$pdo);
                }

                return self::$pdo;
            } catch (Exception $ex) {
                die("Database connection failed: " . $ex->getMessage());
            }
        }
    }

    public static function getDriver(): string {
        return self::$driver;
    }

    private static function initializeSqliteSchema(PDO $db): void {
        $db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT UNIQUE NOT NULL,
                setting_value TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS admin_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                full_name TEXT NOT NULL,
                role TEXT DEFAULT 'admin',
                permissions TEXT DEFAULT NULL,
                avatar TEXT DEFAULT NULL,
                is_active INTEGER DEFAULT 1,
                last_login DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                phone TEXT DEFAULT NULL,
                avatar TEXT DEFAULT NULL,
                is_active INTEGER DEFAULT 1,
                email_verified INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS customer_addresses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL,
                address_type TEXT DEFAULT 'shipping',
                full_name TEXT NOT NULL,
                phone TEXT,
                address_line1 TEXT NOT NULL,
                address_line2 TEXT DEFAULT NULL,
                city TEXT NOT NULL,
                state TEXT NOT NULL,
                pincode TEXT NOT NULL,
                country TEXT DEFAULT 'India',
                is_default INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS departments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                description TEXT DEFAULT NULL,
                image TEXT DEFAULT NULL,
                sort_order INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                department_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                slug TEXT NOT NULL,
                description TEXT DEFAULT NULL,
                image TEXT DEFAULT NULL,
                sort_order INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS subcategories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                slug TEXT NOT NULL,
                description TEXT DEFAULT NULL,
                sort_order INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                department_id INTEGER NOT NULL,
                category_id INTEGER NOT NULL,
                subcategory_id INTEGER DEFAULT NULL,
                name TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                short_description TEXT DEFAULT NULL,
                description TEXT DEFAULT NULL,
                sku TEXT UNIQUE DEFAULT NULL,
                price REAL NOT NULL DEFAULT 0.00,
                sale_price REAL DEFAULT NULL,
                cost_price REAL DEFAULT NULL,
                stock_qty INTEGER DEFAULT 0,
                min_order_qty INTEGER DEFAULT 1,
                weight REAL DEFAULT NULL,
                image TEXT DEFAULT NULL,
                gallery TEXT DEFAULT NULL,
                is_active INTEGER DEFAULT 1,
                is_featured INTEGER DEFAULT 0,
                on_sale INTEGER DEFAULT 0,
                views INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (department_id) REFERENCES departments(id),
                FOREIGN KEY (category_id) REFERENCES categories(id),
                FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS product_reviews (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                customer_id INTEGER NOT NULL,
                rating INTEGER NOT NULL,
                review_text TEXT DEFAULT NULL,
                is_approved INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS cart (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER DEFAULT NULL,
                session_id TEXT DEFAULT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS wishlist (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE(customer_id, product_id)
            );

            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL,
                order_number TEXT UNIQUE NOT NULL,
                status TEXT DEFAULT 'pending',
                payment_status TEXT DEFAULT 'pending',
                payment_method TEXT DEFAULT NULL,
                subtotal REAL NOT NULL DEFAULT 0.00,
                shipping_charge REAL NOT NULL DEFAULT 0.00,
                discount REAL NOT NULL DEFAULT 0.00,
                total REAL NOT NULL DEFAULT 0.00,
                shipping_name TEXT,
                shipping_phone TEXT,
                shipping_address TEXT,
                shipping_city TEXT,
                shipping_state TEXT,
                shipping_pincode TEXT,
                shipping_country TEXT DEFAULT 'India',
                notes TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES customers(id)
            );

            CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                product_name TEXT NOT NULL,
                product_sku TEXT DEFAULT NULL,
                quantity INTEGER NOT NULL DEFAULT 1,
                price REAL NOT NULL,
                total REAL NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id)
            );

            CREATE TABLE IF NOT EXISTS coupons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT UNIQUE NOT NULL,
                type TEXT DEFAULT 'percentage',
                value REAL NOT NULL,
                min_order_amount REAL DEFAULT 0.00,
                max_uses INTEGER DEFAULT NULL,
                used_count INTEGER DEFAULT 0,
                start_date TEXT DEFAULT NULL,
                end_date TEXT DEFAULT NULL,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS banners (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT DEFAULT NULL,
                subtitle TEXT DEFAULT NULL,
                image TEXT NOT NULL,
                link TEXT DEFAULT NULL,
                sort_order INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                position TEXT DEFAULT 'hero',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS shipping_zones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                zone_name TEXT NOT NULL,
                min_order_free_shipping REAL DEFAULT 0.00,
                flat_rate REAL DEFAULT 0.00,
                is_active INTEGER DEFAULT 1
            );

            CREATE TABLE IF NOT EXISTS contact_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT DEFAULT NULL,
                subject TEXT DEFAULT NULL,
                message TEXT NOT NULL,
                is_read INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                content TEXT,
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Seed initial data
        self::seedSqliteData($db);
    }

    private static function seedSqliteData(PDO $db): void {
        // Settings
        $settings = [
            ['site_name', 'SSK.STORE'],
            ['site_tagline', 'Shri Sahajanand Sahitya Kendra — Kalakunj Mandir, Surat'],
            ['site_email', 'contact@sskstore.in'],
            ['site_phone', '+91 9426-55-1098'],
            ['whatsapp_number', '+91 9426-55-1098'],
            ['whatsapp_url', 'https://wa.me/919426551098?text=Jai%20Swaminarayan'],
            ['store_hours', 'Daily (Mon – Sun): 8:00 AM – 12:00 PM & 4:00 PM – 8:00 PM IST'],
            ['currency_symbol', '₹'],
            ['currency_code', 'INR'],
            ['default_country', 'India'],
            ['default_country_code', '91'],
            ['free_shipping_gujarat', '500'],
            ['free_shipping_metro', '750'],
            ['free_shipping_india', '750'],
            ['store_address', 'Shree Swaminarayan Mandir, Kalakunj, Varachha Main Rd, opp. Water Tank, Kalakunj Society, Swaminarayan Nagar-2, Kapodra Patiya, Surat, Gujarat 395006, India'],
            ['facebook_url', 'https://www.facebook.com'],
            ['instagram_url', 'https://www.instagram.com/s_s_sahitya_kendra'],
            ['youtube_url', 'https://www.youtube.com']
        ];
        $stmt = $db->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($settings as $s) {
            $stmt->execute($s);
        }

        // Admin User (admin / admin123)
        $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->prepare("INSERT OR IGNORE INTO admin_users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)")
           ->execute(['admin', 'admin@baps.store', $adminHash, 'BAPS Administrator', 'super_admin']);

        // Sample customer (customer / customer123)
        $custHash = password_hash('customer123', PASSWORD_DEFAULT);
        $db->prepare("INSERT OR IGNORE INTO customers (first_name, last_name, email, password, phone, is_active, email_verified) VALUES (?, ?, ?, ?, ?, 1, 1)")
           ->execute(['Rajesh', 'Patel', 'rajesh@example.com', $custHash, '+919876543210']);

        // Coupons
        $db->prepare("INSERT OR IGNORE INTO coupons (code, type, value, min_order_amount, is_active) VALUES (?, ?, ?, ?, 1)")
           ->execute(['BAPS10', 'percentage', 10.0, 500.0]);
        $db->prepare("INSERT OR IGNORE INTO coupons (code, type, value, min_order_amount, is_active) VALUES (?, ?, ?, ?, 1)")
           ->execute(['DIWALI50', 'fixed', 50.0, 750.0]);

        // Departments
        $depts = [
            [1, 'Books', 'books', 'Spiritual and religious books in multiple languages', 1],
            [2, 'Kanthis', 'kanthis', 'Sacred thread necklaces', 2],
            [3, 'Japamalas', 'japamalas', 'Prayer beads for meditation and chanting', 3],
            [4, 'Ghar Mandirs', 'ghar-mandirs', 'Home temple shrines for daily worship', 4],
            [5, 'Puja Items', 'puja-items', 'Essential items for daily prayers and rituals', 5],
            [6, 'Murtis', 'murtis', 'Sacred images and statues for worship', 6],
            [7, 'Agarbatti & Dhoop', 'agarbatti-dhoop', 'Incense sticks and dhoop for worship', 7],
            [8, 'Clothing', 'clothing', 'T-shirts, caps and spiritual apparel', 8],
            [9, 'Calendars & Diaries', 'calendars-diaries', 'Annual calendars and daily planners', 9],
            [10, 'Audio & Video', 'audio-video', 'Spiritual music, kirtans and video content', 10]
        ];
        $deptStmt = $db->prepare("INSERT OR IGNORE INTO departments (id, name, slug, description, sort_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($depts as $d) {
            $deptStmt->execute($d);
        }

        // Categories
        $cats = [
            [1, 1, 'English Books', 'english', 1],
            [2, 1, 'Gujarati Books', 'gujarati', 2],
            [3, 1, 'Hindi Books', 'hindi', 3],
            [4, 1, 'Other Languages', 'other-languages', 4],
            [5, 1, 'Sanskrit', 'sanskrit', 5],
            [6, 2, 'Tulsi Kanthi', 'kanthi', 1],
            [7, 2, 'Plated Kanthi', 'kanthi-plating', 2],
            [8, 3, 'Devotional Japamalas', 'japamalas-cat', 1],
            [9, 4, 'Oxidized Mandirs', 'oxidized-ghar-mandir', 1],
            [10, 4, 'Wooden Mandirs', 'wooden-ghar-mandir', 2],
            [11, 5, 'Brass Puja Items', 'brass-puja-items', 1],
            [12, 5, 'Puja Samagri', 'general-puja-items', 2],
            [13, 6, 'Brass Murtis', 'metal-murtis', 1],
            [14, 6, 'Marble Murtis', 'marble-murtis', 2],
            [15, 7, 'Natural Agarbatti', 'agarbatti', 1],
            [16, 7, 'Sacred Dhoop', 'dhoop', 2],
            [17, 8, 'Mandir T-Shirts', 't-shirts', 1],
            [18, 8, 'Spiritual Caps', 'caps', 2],
            [19, 9, 'Wall Calendars', 'wall-calendars', 1],
            [20, 9, 'Diaries & Planners', 'diaries', 2],
            [21, 10, 'BAPS Kirtans', 'kirtans', 1],
            [22, 10, 'Pravachans & Discourses', 'pravachans', 2]
        ];
        $catStmt = $db->prepare("INSERT OR IGNORE INTO categories (id, department_id, name, slug, sort_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($cats as $c) {
            $catStmt->execute($c);
        }

        // Subcategories
        $subcats = [
            [1, 1, 'Vachanamrut (English)', 'vachanamrut-english', 1],
            [2, 1, 'Satsang Exams (English)', 'satsang-exams-english', 2],
            [3, 1, 'Children (English)', 'children-english', 3],
            [4, 1, 'Pramukh Swami Maharaj (English)', 'pramukh-swami-maharaj-english', 4],
            [5, 1, 'Hinduism Philosophy', 'hinduism', 5],
            [6, 1, 'Swaminarayan Faith', 'swaminarayan-faith', 6],
            [7, 1, 'Motivational', 'motivational-english', 7],
            [8, 1, 'Mahant Swami Maharaj', 'mahant-swami-maharaj-english', 8],
            [9, 2, 'Vachanamrut (Gujarati)', 'vachanamrut-gujarati', 1],
            [10, 2, 'Spiritual Living', 'spiritual-living-gujarati', 2],
            [11, 2, 'Satsang Exams (Gujarati)', 'satsang-exams-gujarati', 3],
            [12, 2, 'Children (Gujarati)', 'children-gujarati', 4],
            [13, 2, 'Motivational (Gujarati)', 'motivational-gujarati', 5],
            [14, 2, 'Pramukh Swami Maharaj (Gujarati)', 'pramukh-swami-maharaj-gujarati', 6],
            [15, 3, 'Satsang Exams (Hindi)', 'satsang-exams-hindi', 1],
            [16, 3, 'Children (Hindi)', 'children-hindi', 2],
            [17, 3, 'Bhagwan Swaminarayan', 'bhagwan-swaminarayan-hindi', 3],
            [18, 3, 'Pramukh Swami Maharaj (Hindi)', 'pramukh-swami-maharaj-hindi', 4]
        ];
        $subcatStmt = $db->prepare("INSERT OR IGNORE INTO subcategories (id, category_id, name, slug, sort_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($subcats as $sc) {
            $subcatStmt->execute($sc);
        }

        // Products
        $products = [
            // Books
            [1, 1, 1, 'Vachanamrut (English Translation)', 'vachanamrut-english-translation', 'The definitive English translation of the Vachanamrut scripture.', 'The Vachanamrut is a compilation of 273 spiritual discourses delivered by Bhagwan Swaminarayan between 1819 and 1829 CE. This comprehensive English translation brings the profound wisdom of these teachings to English-speaking devotees worldwide. Hardcover edition with commentary and explanatory notes.', 'BK-EN-001', 450.00, 399.00, 50, 'vachanamrut-english.jpg', 1, 1, 1],
            [1, 1, 1, 'Vachanamrut Study Guide', 'vachanamrut-study-guide', 'A comprehensive guide for studying the Vachanamrut scripture.', 'This study guide provides chapter-wise analysis, key themes, and discussion questions for in-depth study of the Vachanamrut. Ideal for satsang study circles and personal reflection.', 'BK-EN-002', 250.00, null, 75, 'vachanamrut-guide.jpg', 1, 0, 0],
            [1, 1, 2, 'Satsang Pravin Exam Guide', 'satsang-pravin-exam-guide', 'Complete preparation guide for annual Satsang Pravin exam.', 'Official study material for the annual Satsang Pravin examination. Contains syllabus, study notes, model questions and answers covering scriptures, history and philosophy.', 'BK-EN-003', 180.00, null, 100, 'satsang-pravin.jpg', 1, 1, 0],
            [1, 1, 3, 'My Swaminarayan Activity Book', 'my-swaminarayan-activity-book', 'Fun activity book for children with coloring and puzzles.', 'Colorful activity book designed for children aged 5-10. Contains puzzles, coloring pages, word searches and stories about Bhagwan Swaminarayan and the guru parampara.', 'BK-EN-004', 120.00, 99.00, 200, 'activity-book.jpg', 1, 1, 1],
            [1, 1, 4, 'Pramukh Swami Maharaj - His Life & Legacy', 'pramukh-swami-maharaj-life-legacy', 'A comprehensive biography of His Holiness Pramukh Swami Maharaj.', 'This definitive biography chronicles the extraordinary life of His Holiness Pramukh Swami Maharaj, the fifth spiritual successor of Bhagwan Swaminarayan. A 500-page hardcover with rare photographs and inspiring anecdotes.', 'BK-EN-005', 1200.00, 999.00, 30, 'psm-biography.jpg', 1, 1, 1],
            [1, 1, 5, 'Understanding Hinduism', 'understanding-hinduism', 'A clear introduction to Hindu philosophy, traditions and practice.', 'An accessible guide exploring the core concepts, practices, and traditions of Hinduism. Written for both beginners and those seeking deeper understanding. Illustrated edition.', 'BK-EN-006', 350.00, null, 60, 'understanding-hinduism.jpg', 1, 0, 0],
            [1, 1, 8, 'Mahant Swami Maharaj - In the Joy of Others', 'mahant-swami-maharaj-joy-others', 'The life and compassionate teachings of Mahant Swami Maharaj.', 'A beautifully illustrated book capturing the spiritual leadership and compassionate service of His Holiness Mahant Swami Maharaj, the current guru of BAPS.', 'BK-EN-008', 800.00, 699.00, 45, 'msm-book.jpg', 1, 1, 1],
            [1, 2, 9, 'Vachanamrut (Gujarati - Original Edition)', 'vachanamrut-gujarati-original', 'The original Vachanamrut text in Gujarati with gold embossing.', 'The original Vachanamrut scripture in Gujarati, the divine nectar of Bhagwan Swaminarayan discourses. Premium hardcover binding with gold-embossed lettering.', 'BK-GJ-001', 400.00, 350.00, 100, 'vachanamrut-gujarati.jpg', 1, 1, 1],
            [1, 2, 10, 'Jeevan No Aachar', 'jeevan-no-aachar', 'Practical guide to spiritual and ethical living in Gujarati.', 'A practical guide to applying spiritual principles in daily life. Covers topics such as devotion, discipline, family harmony, and developing divine virtues.', 'BK-GJ-002', 175.00, null, 80, 'jeevan-aachar.jpg', 1, 0, 0],
            [1, 3, 17, 'Bhagwan Swaminarayan - Jeevan Charitra (Hindi)', 'bhagwan-swaminarayan-jeevan-charitra-hindi', 'Life story of Bhagwan Swaminarayan in Hindi language.', 'An illustrated biography of Bhagwan Swaminarayan from His childhood as Ghanshyam in Chhapaiya to the establishment of the Swaminarayan Sampraday in Gujarat.', 'BK-HI-003', 350.00, 299.00, 40, 'swaminarayan-hindi.jpg', 1, 1, 1],

            // Kanthis
            [2, 6, null, 'Tulsi Kanthi - Double Thread', 'tulsi-kanthi-double-thread', 'Sacred tulsi bead kanthi handcrafted with double thread.', 'Handcrafted kanthi made from genuine sacred tulsi wood beads strung on durable double thread. The kanthi is a sacred necklace symbolizing devotion to God. Length: 18 inches.', 'KN-001', 80.00, null, 500, 'tulsi-kanthi-double.jpg', 1, 1, 0],
            [2, 6, null, 'Tulsi Kanthi - Single Thread', 'tulsi-kanthi-single-thread', 'Classic tulsi bead kanthi with single thread.', 'Traditional kanthi with finely polished tulsi beads on a single cotton thread. A symbol of initiation and devotion. Length: 16 inches.', 'KN-002', 50.00, null, 800, 'tulsi-kanthi-single.jpg', 1, 0, 0],
            [2, 7, null, 'Gold Plated Kanthi', 'gold-plated-kanthi', 'Premium gold-plated tulsi kanthi for special occasions.', 'Elegant kanthi with tulsi beads and gold-plated spacer beads. Premium quality gold plating that retains shine. Ideal for festivals and sabhas. Length: 18 inches.', 'KN-003', 250.00, 199.00, 150, 'gold-kanthi.jpg', 1, 1, 1],
            [2, 7, null, 'Silver Plated Kanthi', 'silver-plated-kanthi', 'Premium silver-plated tulsi kanthi with polished beads.', 'Kanthi with genuine tulsi beads and silver-plated spacer beads. Hypoallergenic and durable silver finish. Length: 18 inches.', 'KN-004', 200.00, null, 200, 'silver-kanthi.jpg', 1, 0, 0],

            // Japamalas
            [3, 8, null, 'Tulsi Japamala - 108 Beads', 'tulsi-japamala-108-beads', 'Traditional 108-bead tulsi japamala for daily meditation.', 'A traditional prayer mala with 108 genuine tulsi wood beads, hand-knotted on durable cotton thread with a guru bead and tassel. Essential for daily Swaminarayan mantra chanting.', 'JP-001', 120.00, null, 300, 'tulsi-japamala.jpg', 1, 1, 0],
            [3, 8, null, 'Sandalwood Japamala - 108 Beads', 'sandalwood-japamala-108-beads', 'Fragrant pure sandalwood 108-bead japamala.', 'Premium sandalwood japamala with 108 beads. The soothing natural aroma of sandalwood calms the mind and enhances meditative focus. Hand-knotted with guru bead.', 'JP-002', 350.00, 299.00, 100, 'sandalwood-japamala.jpg', 1, 1, 1],

            // Ghar Mandirs
            [4, 9, null, 'Oxidized Ghar Mandir - Small', 'oxidized-ghar-mandir-small', 'Compact oxidized metal ghar mandir with ornate domes.', 'A beautifully crafted small ghar mandir in oxidized silver finish. Features intricate dome and pillar designs inspired by Akshardham architecture. Perfect for apartments. Dimensions: 12" x 8" x 16".', 'GM-001', 2500.00, 2199.00, 20, 'ghar-mandir-small.jpg', 1, 1, 1],
            [4, 9, null, 'Oxidized Ghar Mandir - Medium', 'oxidized-ghar-mandir-medium', 'Medium-sized oxidized metal ghar mandir with light.', 'An elegant medium ghar mandir in oxidized silver finish with ornate detailing. Includes LED light provision and pull-out aarti tray. Dimensions: 18" x 12" x 24".', 'GM-002', 4500.00, null, 15, 'ghar-mandir-medium.jpg', 1, 1, 0],
            [4, 10, null, 'Wooden Ghar Mandir - Premium Teak', 'wooden-ghar-mandir-premium-teak', 'Hand-carved premium teak wood ghar mandir.', 'Hand-carved solid teak wood ghar mandir inspired by traditional Swaminarayan temple shikhar architecture. Features detailed floral and deity carvings with ample shelf space. Dimensions: 24" x 14" x 30".', 'GM-003', 8500.00, 7499.00, 8, 'ghar-mandir-teak.jpg', 1, 1, 1],

            // Puja Items
            [5, 11, null, 'Brass Diya Set (4 pcs)', 'brass-diya-set-4pcs', 'Set of 4 traditional solid brass diyas.', 'Handcrafted solid brass diya set of 4 pieces. Traditional design with lotus petal edges. Perfect for daily evening aarti and festival celebrations. Mirror polished.', 'PJ-001', 350.00, null, 100, 'brass-diya-set.jpg', 1, 0, 0],
            [5, 11, null, 'Brass Thali Set for Aarti', 'brass-thali-set-aarti', 'Complete brass aarti thali with bell and diya.', 'A complete 5-piece aarti set including thali, diya, bell, incense holder, and kumkum container. All pieces in polished brass with traditional temple engravings.', 'PJ-002', 750.00, 649.00, 50, 'aarti-thali.jpg', 1, 1, 1],
            [5, 12, null, 'Puja Samagri Kit', 'puja-samagri-kit', 'Complete daily puja essentials kit with wicks and chandan.', 'All-in-one daily puja kit containing cotton wicks, pure camphor, chandan tilak stone, kumkum, rice (akshat), and brass containers. Everything needed for daily morning puja.', 'PJ-004', 250.00, 199.00, 150, 'puja-kit.jpg', 1, 1, 1],

            // Murtis
            [6, 13, null, 'Akshar Purushottam Murti - Brass (6 inch)', 'akshar-purushottam-murti-brass-6inch', 'Fine brass murti of Akshar Purushottam Maharaj.', 'Finely detailed brass murti of Akshar Purushottam Maharaj. Standing posture with abhay mudra. Hand-finished with intricate crown and shawl detailing. Height: 6 inches.', 'MR-001', 1800.00, 1599.00, 25, 'ap-murti-brass.jpg', 1, 1, 1],
            [6, 14, null, 'Harikrishna Maharaj Murti - Marble (8 inch)', 'harikrishna-maharaj-murti-marble-8inch', 'White marble murti of Shri Harikrishna Maharaj.', 'Hand-carved white marble murti of Shri Harikrishna Maharaj in peaceful sitting posture. Detailed golden ornaments and crown. Height: 8 inches.', 'MR-002', 3500.00, null, 10, 'hkm-murti-marble.jpg', 1, 1, 0],

            // Agarbatti
            [7, 15, null, 'BAPS Premium Agarbatti - Rose (Pack of 12)', 'baps-premium-agarbatti-rose-12', 'Pure rose fragrance incense sticks with low smoke.', 'Hand-rolled premium quality incense sticks with natural rose essential oil. Pack of 12 boxes (20 sticks each = 240 sticks). Low smoke formula made with temple-grade natural ingredients.', 'AG-001', 180.00, 149.00, 300, 'agarbatti-rose.jpg', 1, 1, 1],
            [7, 15, null, 'BAPS Premium Agarbatti - Sandalwood (Pack of 12)', 'baps-premium-agarbatti-sandalwood-12', 'Authentic sandalwood aroma incense sticks.', 'Rich sandalwood aroma incense sticks creating a divine temple ambience. Pack of 12 boxes (20 sticks each = 240 sticks). Pure and natural ingredients.', 'AG-002', 200.00, null, 250, 'agarbatti-sandalwood.jpg', 1, 0, 0],

            // Apparel
            [8, 17, null, 'Mandir T-Shirt - White (100% Cotton)', 'mandir-tshirt-white-cotton', 'White cotton T-shirt featuring Akshardham motif.', 'Premium 100% combed cotton round-neck T-shirt featuring the Akshardham Mandir illustration. Breathable pre-shrunk fabric available in all sizes.', 'CL-001', 499.00, 399.00, 200, 'tshirt-mandir-white.jpg', 1, 1, 1],

            // Calendars
            [9, 19, null, 'BAPS Wall Calendar 2027', 'baps-wall-calendar-2027', 'Annual wall calendar with international mandirs.', 'Beautifully designed wall calendar featuring stunning photography of BAPS mandirs worldwide. Includes Hindu festival dates, ekadashi tithis, and auspicious muhurats. Size: 14" x 22".', 'CD-001', 150.00, 120.00, 500, 'calendar-2027.jpg', 1, 1, 1],
            [9, 20, null, 'BAPS Daily Planner Diary 2027', 'baps-daily-planner-diary-2027', 'Hardbound daily planner with inspirational satsang quotes.', 'A premium daily planner diary featuring inspiring spiritual quotes from Pramukh Swami Maharaj and Mahant Swami Maharaj, important dates, and bookmark ribbon. 365 pages.', 'CD-002', 350.00, 299.00, 200, 'diary-2027.jpg', 1, 1, 1]
        ];

        $prodStmt = $db->prepare("INSERT OR IGNORE INTO products (department_id, category_id, subcategory_id, name, slug, short_description, description, sku, price, sale_price, stock_qty, image, is_active, is_featured, on_sale) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($products as $p) {
            $prodStmt->execute($p);
        }

        // Banners
        $banners = [
            ['Welcome to BAPS Store', 'Official Online Store of BAPS Swaminarayan Sanstha', 'banner-welcome.jpg', 'catalog.php', 1],
            ['Sacred Publications & Scriptures', 'Discover Vachanamrut, Biographies & Audio-Visual Satsang', 'banner-books.jpg', 'catalog.php?dept=1', 2],
            ['Free Shipping Across India', 'Orders above ₹500 in Gujarat & ₹750 India-wide', 'banner-shipping.jpg', 'catalog.php', 3],
            ['Festival Specials & On Sale', 'Limited period discounts on holy murtis & ghar mandirs', 'banner-sale.jpg', 'catalog.php?on_sale=1', 4]
        ];
        $banStmt = $db->prepare("INSERT OR IGNORE INTO banners (title, subtitle, image, link, sort_order, is_active, position) VALUES (?, ?, ?, ?, ?, 1, 'hero')");
        foreach ($banners as $b) {
            $banStmt->execute($b);
        }

        // CMS Pages
        $pages = [
            ['About Us', 'about-us', '<h2>About Shri Sahajanand Sahitya Kendra</h2><p>Shri Sahajanand Sahitya Kendra is the divine publication and devotional center of <strong>Shree Swaminarayan Mandir, Kalakunj, Surat</strong>. We offer authentic spiritual scriptures, Vachanamrut, Shikshapatri, sanctified tulsi kanthis, handcrafted teakwood ghar mandirs, brass puja items, and sacred worship essentials to devotees across India and worldwide.</p><p>Rooted in the sacred teachings of Bhagwan Swaminarayan, our mission is to spread pure spiritual knowledge, divine devotion, and virtuous living through authenticated publications and sacred artifacts.</p>'],
            ['Contact Us', 'contact-us', '<h2>Contact Shree Sahajanand Sahitya Kendra</h2><p><strong>Address:</strong> Shree Swaminarayan Mandir, Kalakunj, Varachha Main Rd, opp. Water Tank, Kalakunj Society, Swaminarayan Nagar-2, Kapodra Patiya, Surat, Gujarat 395006, India</p><p><strong>Helpline / Phone:</strong> +91 9426-55-1098</p><p><strong>WhatsApp Support:</strong> +91 9426-55-1098</p><p><strong>Email:</strong> contact@sskstore.in</p><p><strong>Operating Hours:</strong> Daily (Mon – Sun): 8:00 AM – 12:00 PM & 4:00 PM – 8:00 PM IST</p>'],
            ['Privacy Policy', 'privacy-policy', '<h2>Privacy Policy</h2><p>Shri Sahajanand Sahitya Kendra is dedicated to protecting your privacy. We safeguard all personal details, transaction records, and shipping addresses using 256-bit SSL encryption. We never sell or share customer data with third parties.</p>'],
            ['Return & Refund Policy', 'return-policy', '<h2>Return & Refund Policy</h2><p>Your satisfaction and devotional peace of mind are our priority. If any item is damaged in transit or defective, please contact our support desk within 7 days of delivery for a prompt replacement or refund.</p>'],
            ['Terms & Conditions', 'terms-conditions', '<h2>Terms & Conditions</h2><p>By browsing and purchasing on SSK Store, you agree to our standard terms of use, privacy policies, and shipping agreements. All publications and sacred literature are published by Shri Sahajanand Sahitya Kendra (Kalakunj Mandir).</p>']
        ];
        $pageStmt = $db->prepare("INSERT OR IGNORE INTO pages (title, slug, content, is_active) VALUES (?, ?, ?, 1)");
        foreach ($pages as $pg) {
            $pageStmt->execute($pg);
        }
    }
}

// Global DB Helper functions
function get_db(): PDO {
    return DB::connect();
}

function db_query(string $sql, array $params = []): PDOStatement {
    $db = get_db();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_fetch_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

function db_fetch_one(string $sql, array $params = []): ?array {
    $res = db_query($sql, $params)->fetch();
    return $res ?: null;
}

function db_last_insert_id(): string {
    return get_db()->lastInsertId();
}
