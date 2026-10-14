<?php
/**
 * Migration: 001_initial_schema_fixes
 * Adds pending_verification payment status, is_guest customer flag, login_attempts, and password_resets tables.
 */

return [
    'up' => function(PDO $pdo, string $driver): void {
        if ($driver === 'mysql') {
            // 1. Expand payment_status enum to include 'pending_verification'
            $pdo->exec("
                ALTER TABLE orders 
                MODIFY COLUMN payment_status ENUM('pending','paid','failed','refunded','pending_verification') DEFAULT 'pending'
            ");

            // 2. Add is_guest column to customers if not exists
            $colExists = $pdo->query("
                SELECT COUNT(*) FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'is_guest'
            ")->fetchColumn();

            if (!$colExists) {
                $pdo->exec("ALTER TABLE customers ADD COLUMN is_guest TINYINT(1) DEFAULT 0 AFTER email_verified");
            }

            // 3. Login attempts table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS login_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    identifier VARCHAR(150) NOT NULL,
                    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_attempt_ip_ident (ip_address, identifier, attempted_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // 4. Password resets table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS password_resets (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    token_hash VARCHAR(64) NOT NULL,
                    expires_at TIMESTAMP NOT NULL,
                    used_at TIMESTAMP NULL DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_pwd_resets_token (token_hash),
                    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        } else {
            // SQLite driver
            // Check if is_guest column exists in customers table
            $cols = $pdo->query("PRAGMA table_info(customers)")->fetchAll(PDO::FETCH_ASSOC);
            $hasGuest = false;
            foreach ($cols as $col) {
                if ($col['name'] === 'is_guest') {
                    $hasGuest = true;
                    break;
                }
            }
            if (!$hasGuest) {
                $pdo->exec("ALTER TABLE customers ADD COLUMN is_guest INTEGER DEFAULT 0");
            }

            // Login attempts table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS login_attempts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    ip_address TEXT NOT NULL,
                    identifier TEXT NOT NULL,
                    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX IF NOT EXISTS idx_attempt_ip_ident ON login_attempts (ip_address, identifier, attempted_at);
            ");

            // Password resets table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS password_resets (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    customer_id INTEGER NOT NULL,
                    token_hash TEXT NOT NULL,
                    expires_at DATETIME NOT NULL,
                    used_at DATETIME DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
                );
                CREATE INDEX IF NOT EXISTS idx_pwd_resets_token ON password_resets (token_hash);
            ");
        }
    },
    'down' => function(PDO $pdo, string $driver): void {
        if ($driver === 'mysql') {
            $pdo->exec("DROP TABLE IF EXISTS password_resets;");
            $pdo->exec("DROP TABLE IF EXISTS login_attempts;");
        } else {
            $pdo->exec("DROP TABLE IF EXISTS password_resets;");
            $pdo->exec("DROP TABLE IF EXISTS login_attempts;");
        }
    }
];
