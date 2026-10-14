<?php
/**
 * SSK Store - Database Migration Runner
 * Supports both MySQL/MariaDB and SQLite with tracking table.
 *
 * Usage via CLI: php tools/migrate.php [--rollback]
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = DB::connect();
$driver = DB::getDriver();

echo "Running migrations for driver: [{$driver}]...\n";

// Ensure migrations tracking table exists
if ($driver === 'sqlite') {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) UNIQUE NOT NULL,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");
} else {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) UNIQUE NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

$applied = $pdo->query("SELECT migration FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);

$migrationsDir = __DIR__ . '/../database/migrations';
if (!is_dir($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
}

$files = glob($migrationsDir . '/*.php');
sort($files);

$count = 0;
foreach ($files as $file) {
    $migrationName = basename($file);
    if (in_array($migrationName, $applied, true)) {
        continue;
    }

    echo "Applying migration: {$migrationName}... ";
    
    // Each migration file returns an anonymous class or array with 'up' and 'down'
    $migration = require $file;
    if (is_callable($migration)) {
        $migration($pdo, $driver);
    } elseif (is_array($migration) && isset($migration['up']) && is_callable($migration['up'])) {
        $migration['up']($pdo, $driver);
    }

    $stmt = $pdo->prepare("INSERT INTO schema_migrations (migration) VALUES (?)");
    $stmt->execute([$migrationName]);

    echo "DONE\n";
    $count++;
}

if ($count === 0) {
    echo "No pending migrations to apply. Database is up to date.\n";
} else {
    echo "Successfully applied {$count} migration(s).\n";
}
