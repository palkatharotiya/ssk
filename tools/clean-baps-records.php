<?php
/**
 * SSK Store - Data Migration: Purge remaining BAPS branding artifacts from DB
 */

require_once __DIR__ . '/../includes/functions.php';

$db = DB::connect();

$c1 = $db->exec("UPDATE categories SET name = 'Devotional Kirtans' WHERE name LIKE '%BAPS%'");
$c2 = $db->exec("UPDATE coupons SET code = 'SSK10' WHERE code = 'BAPS10'");

$products = $db->query("SELECT id, name, slug FROM products WHERE name LIKE '%BAPS%' OR slug LIKE '%baps%'")->fetchAll();
$updatedProds = 0;
foreach ($products as $p) {
    $newName = str_ireplace('BAPS ', 'SSK ', $p['name']);
    $baseSlug = str_ireplace('baps-', 'ssk-', $p['slug']);
    $newSlug = $baseSlug;

    // Check if new slug already exists for another product
    $chk = $db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
    $chk->execute([$newSlug, $p['id']]);
    if ($chk->fetch()) {
        $newSlug = $baseSlug . '-' . $p['id'];
    }

    $stmt = $db->prepare("UPDATE products SET name = ?, slug = ? WHERE id = ?");
    $stmt->execute([$newName, $newSlug, $p['id']]);
    $updatedProds++;
}

$c4 = $db->exec("UPDATE banners SET title = REPLACE(title, 'BAPS Store', 'SSK Store'), subtitle = REPLACE(subtitle, 'BAPS Swaminarayan Sanstha', 'Shri Sahajanand Sahitya Kendra (Kalakunj Mandir)') WHERE title LIKE '%BAPS%' OR subtitle LIKE '%BAPS%'");

echo "Cleaned DB Records:\n";
echo " - Categories updated: " . (int)$c1 . "\n";
echo " - Coupons updated: " . (int)$c2 . "\n";
echo " - Products updated: " . $updatedProds . "\n";
echo " - Banners updated: " . (int)$c4 . "\n";

