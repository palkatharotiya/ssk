-- ============================================================
-- Migration Rollback: 001_initial_schema_fixes_rollback.sql
-- ============================================================

DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS login_attempts;
-- To revert payment_status on MySQL:
-- ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending';
