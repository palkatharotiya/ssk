-- ============================================================
-- Migration: 001_initial_schema_fixes.sql
-- SSK Store: Add pending_verification payment status, is_guest flag, login_attempts, and password_resets tables.
-- Portable for MySQL/MariaDB and SQLite.
-- ============================================================

-- MySQL Driver
-- ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending','paid','failed','refunded','pending_verification') DEFAULT 'pending';
-- ALTER TABLE customers ADD COLUMN is_guest TINYINT(1) DEFAULT 0;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    identifier VARCHAR(150) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attempt_ip_ident (ip_address, identifier, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
