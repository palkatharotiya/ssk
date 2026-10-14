<?php
/**
 * Migration: 002_add_performance_indexes
 * Adds compound indexes on cart, orders, products, and subcategories to eliminate full table scans.
 * Fully compatible with MySQL/MariaDB and SQLite.
 */

return [
    'up' => function(PDO $pdo, string $driver): void {
        if ($driver === 'mysql') {
            // Helper to check if index exists in MySQL
            $hasIndex = function(string $table, string $indexName) use ($pdo): bool {
                $stmt = $pdo->prepare("
                    SELECT COUNT(1) 
                    FROM information_schema.STATISTICS 
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
                ");
                $stmt->execute([$table, $indexName]);
                return (int)$stmt->fetchColumn() > 0;
            };

            if (!$hasIndex('cart', 'idx_cart_session')) {
                $pdo->exec("CREATE INDEX idx_cart_session ON cart (session_id)");
            }
            if (!$hasIndex('orders', 'idx_orders_customer_created')) {
                $pdo->exec("CREATE INDEX idx_orders_customer_created ON orders (customer_id, created_at)");
            }
            if (!$hasIndex('orders', 'idx_orders_status')) {
                $pdo->exec("CREATE INDEX idx_orders_status ON orders (status, created_at)");
            }
            if (!$hasIndex('products', 'idx_products_filter')) {
                $pdo->exec("CREATE INDEX idx_products_filter ON products (is_active, department_id, category_id)");
            }
            if (!$hasIndex('subcategories', 'idx_subcategories_cat_active')) {
                $pdo->exec("CREATE INDEX idx_subcategories_cat_active ON subcategories (category_id, is_active)");
            }
            if (!$hasIndex('order_items', 'idx_order_items_order_id')) {
                $pdo->exec("CREATE INDEX idx_order_items_order_id ON order_items (order_id)");
            }
        } else {
            // SQLite driver
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_cart_session ON cart (session_id)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_orders_customer_created ON orders (customer_id, created_at)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_orders_status ON orders (status, created_at)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_products_filter ON products (is_active, department_id, category_id)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_subcategories_cat_active ON subcategories (category_id, is_active)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items (order_id)");
        }
    },
    'down' => function(PDO $pdo, string $driver): void {
        if ($driver === 'mysql') {
            $dropIndex = function(string $table, string $indexName) use ($pdo): void {
                try {
                    $pdo->exec("ALTER TABLE {$table} DROP INDEX {$indexName}");
                } catch (\Throwable $e) {
                    // Ignore if not exists
                }
            };
            $dropIndex('cart', 'idx_cart_session');
            $dropIndex('orders', 'idx_orders_customer_created');
            $dropIndex('orders', 'idx_orders_status');
            $dropIndex('products', 'idx_products_filter');
            $dropIndex('subcategories', 'idx_subcategories_cat_active');
            $dropIndex('order_items', 'idx_order_items_order_id');
        } else {
            $pdo->exec("DROP INDEX IF EXISTS idx_cart_session");
            $pdo->exec("DROP INDEX IF EXISTS idx_orders_customer_created");
            $pdo->exec("DROP INDEX IF EXISTS idx_orders_status");
            $pdo->exec("DROP INDEX IF EXISTS idx_products_filter");
            $pdo->exec("DROP INDEX IF EXISTS idx_subcategories_cat_active");
            $pdo->exec("DROP INDEX IF EXISTS idx_order_items_order_id");
        }
    }
];
