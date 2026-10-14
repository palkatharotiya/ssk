<?php
/**
 * SSK Store - Bulk Product Import & Image Uploader
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir)
 */

require_once __DIR__ . '/includes/admin_bootstrap.php';

$admin = current_admin();
if (!admin_has_permission('products', $admin)) {
    set_flash('error', 'Access Denied: You do not have permission to manage products.');
    header('Location: index.php');
    exit;
}

$action = $_GET['action'] ?? '';

// ============================================================
// 1. ACTION: DOWNLOAD SAMPLE CSV TEMPLATE
// ============================================================
if ($action === 'sample_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ssk_products_sample_template.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // Write UTF-8 BOM for Microsoft Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Headers
    fputcsv($output, [
        'name',
        'department',
        'category',
        'subcategory',
        'price',
        'sale_price',
        'stock_qty',
        'sku',
        'image',
        'short_description',
        'description',
        'is_featured',
        'on_sale',
        'is_active'
    ]);

    // Sample Devotional Products
    $sampleRows = [
        [
            'Shri Vachanamrut (Gujarati Hardcover)',
            'Books',
            'Gujarati Books',
            'Vachanamrut (Gujarati)',
            '350.00',
            '299.00',
            '100',
            'SSK-BK-101',
            'vachanamrut-gujarati.jpg',
            'The divine discourses of Bhagwan Swaminarayan with authentic Rahasyartha notes.',
            'Complete and sanctified publication from Shree Swaminarayan Mandir, Kalakunj, Surat. Includes gold-embossed cover and glossary.',
            '1',
            '1',
            '1'
        ],
        [
            'Pure Tulsi Kanthi 2-Round (Gold Plated)',
            'Kanthis',
            'Plated Kanthi',
            '',
            '199.00',
            '',
            '75',
            'SSK-KN-201',
            'tulsi-kanthi-gold.jpg',
            'Handcrafted sacred natural tulsi beads with sanctified auspicious thread.',
            'Blessed in mandir puja. Premium quality durable gold-plated clasp and smooth polished natural beads.',
            '1',
            '0',
            '1'
        ],
        [
            'Carved Wooden Ghar Mandir (Teak Finish)',
            'Ghar Mandirs',
            'Wooden Ghar Mandirs',
            '4999.00',
            '4499.00',
            '15',
            'SSK-MN-301',
            'wooden-mandir.jpg',
            'Handcrafted home temple shrine with pull-out thali drawer, dome shikhar, and warm LED light.',
            'Carved by traditional temple artisans. Dimensions: 24 x 15 x 32 inches. Perfect for daily worship.',
            '1',
            '1',
            '1'
        ],
        [
            'Premium Agarbatti - Sandalwood (Pack of 12)',
            'Agarbatti & Dhoop',
            'Natural Agarbatti',
            '200.00',
            '175.00',
            '250',
            'SSK-AG-401',
            'sandalwood-agarbatti.jpg',
            'Aromatic chandan fragrance natural incense sticks for daily devotional rituals.',
            'Slow-burning, charcoal-free, 100% pure herbal formulation creating a sanctified atmosphere.',
            '0',
            '1',
            '1'
        ],
        [
            'Brass Puja Thali Set (Panchamrut Bowls & Diya)',
            'Puja Items',
            'Puja Samagri',
            '599.00',
            '549.00',
            '40',
            'SSK-PJ-501',
            'brass-thali-set.jpg',
            'Complete 7-piece heavy brass puja thali with embossed floral motifs.',
            'Includes Aarti Diya, Bell (Ghanti), Kalash, Agarbatti stand, and 2 prasad cups.',
            '0',
            '0',
            '1'
        ]
    ];

    foreach ($sampleRows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// ============================================================
// 2. ACTION: EXPORT CURRENT CATALOG TO CSV
// ============================================================
if ($action === 'export') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ssk_products_export_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, [
        'id',
        'name',
        'department',
        'category',
        'price',
        'sale_price',
        'stock_qty',
        'sku',
        'image',
        'short_description',
        'description',
        'is_featured',
        'on_sale',
        'is_active',
        'created_at'
    ]);

    $allProducts = db_fetch_all("
        SELECT p.*, d.name as dept_name, c.name as cat_name 
        FROM products p 
        LEFT JOIN departments d ON p.department_id = d.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.id ASC
    ");

    foreach ($allProducts as $p) {
        fputcsv($output, [
            $p['id'],
            $p['name'],
            $p['dept_name'] ?? 'General',
            $p['cat_name'] ?? 'Standard',
            $p['price'],
            $p['sale_price'],
            $p['stock_qty'],
            $p['sku'],
            $p['image'],
            $p['short_description'],
            $p['description'],
            $p['is_featured'],
            $p['on_sale'],
            $p['is_active'],
            $p['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// ============================================================
// 3. ACTION: PROCESS BULK IMPORT (CSV + IMAGES)
// ============================================================
$importStats = null;
$importErrors = [];
$unpackedImages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_import'])) {
    require_csrf();
    $upsertMode = isset($_POST['upsert_existing']);
    $autoCreateCats = isset($_POST['auto_create_cats']);
    $uploadDir = ROOT_PATH . 'uploads/products/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $finfo = class_exists('finfo') ? new finfo(FILEINFO_MIME_TYPE) : null;
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    // A. Handle Multiple Images Upload (if user attached files)
    if (!empty($_FILES['image_files']['name'][0])) {
        foreach ($_FILES['image_files']['name'] as $idx => $origName) {
            if ($_FILES['image_files']['error'][$idx] === UPLOAD_ERR_OK) {
                // Size limit: 5MB per image
                if ($_FILES['image_files']['size'][$idx] > 5 * 1024 * 1024) {
                    $importErrors[] = "Skipped image '$origName': Exceeds 5MB limit.";
                    continue;
                }
                $tmpPath = $_FILES['image_files']['tmp_name'][$idx];
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $mime = $finfo ? $finfo->file($tmpPath) : 'image/' . $ext;

                if (in_array($ext, $allowedExts, true) && in_array($mime, $allowedMimes, true)) {
                    $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($origName));
                    if (!empty($cleanName)) {
                        $destPath = $uploadDir . $cleanName;
                        if (move_uploaded_file($tmpPath, $destPath)) {
                            $unpackedImages[] = $cleanName;
                        }
                    }
                } else {
                    $importErrors[] = "Skipped image '$origName': Invalid image format or MIME type.";
                }
            }
        }
    }

    // B. Handle ZIP of Images (if user attached a .zip archive)
    if (!empty($_FILES['images_zip']['name']) && $_FILES['images_zip']['error'] === UPLOAD_ERR_OK) {
        $zipSize = $_FILES['images_zip']['size'];
        if ($zipSize > 25 * 1024 * 1024) {
            $importErrors[] = "ZIP upload rejected: Archive exceeds maximum size of 25MB.";
        } else {
            $zipExt = strtolower(pathinfo($_FILES['images_zip']['name'], PATHINFO_EXTENSION));
            if ($zipExt === 'zip') {
                $tmpZip = $_FILES['images_zip']['tmp_name'];
                if (class_exists('ZipArchive')) {
                    $zip = new ZipArchive();
                    if ($zip->open($tmpZip) === true) {
                        $maxZipFiles = 150;
                        $totalExtractedSize = 0;
                        $maxTotalSize = 50 * 1024 * 1024; // 50MB max extracted

                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            if ($i >= $maxZipFiles) {
                                $importErrors[] = "ZIP file contained more than $maxZipFiles images. Remainder skipped.";
                                break;
                            }
                            $entryName = $zip->getNameIndex($i);
                            // Defense against Zip Slip and system metadata
                            if (substr($entryName, -1) === '/' || strpos($entryName, '__MACOSX') !== false || strpos($entryName, '..') !== false) {
                                continue;
                            }
                            $ext = strtolower(pathinfo($entryName, PATHINFO_EXTENSION));
                            if (in_array($ext, $allowedExts, true)) {
                                $stat = $zip->statIndex($i);
                                $entrySize = $stat['size'] ?? 0;
                                if ($entrySize > 5 * 1024 * 1024) {
                                    $importErrors[] = "Skipped '$entryName' from ZIP: Uncompressed size exceeds 5MB.";
                                    continue;
                                }
                                $totalExtractedSize += $entrySize;
                                if ($totalExtractedSize > $maxTotalSize) {
                                    $importErrors[] = "ZIP decompression stopped: Exceeded safety size limit (50MB).";
                                    break;
                                }

                                $baseName = basename($entryName);
                                $cleanName = preg_replace('/[^a-zA-Z0-9._-]/', '', $baseName);
                                if (!empty($cleanName)) {
                                    $content = $zip->getFromIndex($i);
                                    if ($content !== false) {
                                        $destPath = $uploadDir . $cleanName;
                                        file_put_contents($destPath, $content);
                                        $extractedMime = $finfo ? $finfo->file($destPath) : 'image/' . $ext;
                                        if (!in_array($extractedMime, $allowedMimes, true)) {
                                            @unlink($destPath);
                                            $importErrors[] = "Skipped '$entryName': Invalid image data in ZIP.";
                                        } else {
                                            $unpackedImages[] = $cleanName;
                                        }
                                    }
                                }
                            }
                        }
                        $zip->close();
                    } else {
                        $importErrors[] = "Failed to open images ZIP archive.";
                    }
                }
            }
        }
    }

    // C. Get CSV Content from File or Textarea
    $csvData = '';
    if (!empty($_FILES['csv_file']['tmp_name']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $csvData = file_get_contents($_FILES['csv_file']['tmp_name']);
    } elseif (!empty($_POST['csv_text'])) {
        $csvData = trim($_POST['csv_text']);
    }

    if (empty($csvData)) {
        set_flash('error', 'Please upload a CSV file or paste CSV content to import products.');
    } else {
        // Strip UTF-8 BOM
        if (substr($csvData, 0, 3) === chr(0xEF).chr(0xBB).chr(0xBF)) {
            $csvData = substr($csvData, 3);
        }

        // Detect delimiter (comma or tab or semicolon)
        $firstLine = strtok($csvData, "\r\n");
        $commaCount = substr_count($firstLine, ',');
        $tabCount = substr_count($firstLine, "\t");
        $semiCount = substr_count($firstLine, ';');

        $delimiter = ',';
        if ($tabCount > $commaCount && $tabCount > $semiCount) {
            $delimiter = "\t";
        } elseif ($semiCount > $commaCount && $semiCount > $tabCount) {
            $delimiter = ';';
        }

        // Open memory stream to parse CSV
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csvData);
        rewind($stream);

        // Read header row
        $rawHeaders = fgetcsv($stream, 0, $delimiter);
        if (!$rawHeaders) {
            set_flash('error', 'Could not parse headers from CSV file.');
        } else {
            // Normalize header keys
            $headerMap = [];
            foreach ($rawHeaders as $colIdx => $h) {
                $norm = strtolower(trim($h));
                $norm = str_replace([' ', '_', '-'], '', $norm);
                $headerMap[$colIdx] = $norm;
            }

            // Cache departments, categories, and subcategories from DB
            $deptRows = db_fetch_all("SELECT id, name, slug FROM departments");
            $deptLookup = [];
            foreach ($deptRows as $dr) {
                $deptLookup[strtolower(trim($dr['name']))] = (int)$dr['id'];
                $deptLookup[strtolower(trim($dr['slug']))] = (int)$dr['id'];
                $deptLookup[(string)$dr['id']] = (int)$dr['id'];
            }

            $catRows = db_fetch_all("SELECT id, department_id, name, slug FROM categories");
            $catLookup = [];
            foreach ($catRows as $cr) {
                $key = $cr['department_id'] . '_' . strtolower(trim($cr['name']));
                $catLookup[$key] = (int)$cr['id'];
                $catLookup[(string)$cr['id']] = (int)$cr['id'];
            }

            $subcatRows = db_fetch_all("SELECT id, category_id, name, slug FROM subcategories");
            $subcatLookup = [];
            foreach ($subcatRows as $sr) {
                $subKey = $sr['category_id'] . '_' . strtolower(trim($sr['name']));
                $subcatLookup[$subKey] = (int)$sr['id'];
                $subcatLookup[(string)$sr['id']] = (int)$sr['id'];
            }

            $totalRows = 0;
            $inserted = 0;
            $updated = 0;
            $skipped = 0;

            // Execute entire CSV import within an ACID transaction
            $pdo = db();
            $pdo->beginTransaction();
            $importSuccess = true;

            try {
                while (($row = fgetcsv($stream, 0, $delimiter)) !== false) {
                    if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) {
                        continue;
                    }
                    $totalRows++;

                    // Map row by header names
                    $item = [];
                    foreach ($row as $idx => $val) {
                        $key = $headerMap[$idx] ?? ('col_' . $idx);
                        $item[$key] = trim($val);
                    }

                    // Extract fields with smart aliases
                    $name = $item['name'] ?? $item['title'] ?? $item['productname'] ?? $item['itemname'] ?? '';
                    if (empty($name)) {
                        $importErrors[] = "Row #$totalRows skipped: Missing product title / name.";
                        $skipped++;
                        continue;
                    }

                    $deptRaw = $item['department'] ?? $item['dept'] ?? $item['departmentname'] ?? $item['deptname'] ?? $item['departmentid'] ?? 'Books';
                    $catRaw = $item['category'] ?? $item['cat'] ?? $item['categoryname'] ?? $item['catname'] ?? $item['categoryid'] ?? 'General';
                    $subcatRaw = $item['subcategory'] ?? $item['subcat'] ?? $item['subcategoryname'] ?? '';
                    $price = (float)($item['price'] ?? $item['regularprice'] ?? $item['mrp'] ?? 0);
                    $salePrice = !empty($item['saleprice']) ? (float)$item['saleprice'] : null;
                    $stock = (int)($item['stockqty'] ?? $item['stock'] ?? $item['quantity'] ?? $item['qty'] ?? 100);
                    $sku = $item['sku'] ?? $item['code'] ?? $item['itemcode'] ?? '';
                    $image = $item['image'] ?? $item['imageurl'] ?? $item['photo'] ?? $item['picture'] ?? '';
                    $shortDesc = $item['shortdescription'] ?? $item['subtitle'] ?? $item['summary'] ?? '';
                    $desc = $item['description'] ?? $item['details'] ?? $item['longdescription'] ?? '';
                    $isFeatured = in_array(strtolower($item['isfeatured'] ?? $item['featured'] ?? '0'), ['1', 'true', 'yes'], true) ? 1 : 0;
                    $onSale = in_array(strtolower($item['onsale'] ?? $item['sale'] ?? '0'), ['1', 'true', 'yes'], true) ? 1 : 0;
                    $isActive = in_array(strtolower($item['isactive'] ?? $item['active'] ?? $item['status'] ?? '1'), ['1', 'true', 'yes', 'active'], true) ? 1 : 0;

                    // Department Resolution
                    $deptId = $deptLookup[strtolower(trim($deptRaw))] ?? null;
                    if (!$deptId) {
                        if ($autoCreateCats) {
                            $deptSlug = generate_unique_slug($deptRaw, 'departments');
                            $newDeptId = db_insert("INSERT INTO departments (name, slug, sort_order, is_active) VALUES (?, ?, 20, 1)", [
                                $deptRaw, $deptSlug
                            ]);
                            $deptId = (int)$newDeptId;
                            $deptLookup[strtolower(trim($deptRaw))] = $deptId;
                        } else {
                            $deptId = !empty($deptRows) ? (int)$deptRows[0]['id'] : 1;
                        }
                    }

                    // Category Resolution
                    $catKey = $deptId . '_' . strtolower(trim($catRaw));
                    $catId = $catLookup[$catKey] ?? null;
                    if (!$catId) {
                        if ($autoCreateCats) {
                            $catSlug = generate_unique_slug($catRaw, 'categories');
                            $newCatId = db_insert("INSERT INTO categories (department_id, name, slug, sort_order, is_active) VALUES (?, ?, ?, 20, 1)", [
                                $deptId, $catRaw, $catSlug
                            ]);
                            $catId = (int)$newCatId;
                            $catLookup[$catKey] = $catId;
                        } else {
                            $catId = 1;
                        }
                    }

                    // Subcategory Resolution (Optional)
                    $subcatId = null;
                    if (!empty($subcatRaw)) {
                        $subKey = $catId . '_' . strtolower(trim($subcatRaw));
                        $subcatId = $subcatLookup[$subKey] ?? null;
                        if (!$subcatId && $autoCreateCats) {
                            $subSlug = generate_unique_slug($subcatRaw, 'subcategories');
                            $newSubId = db_insert("INSERT INTO subcategories (category_id, name, slug, sort_order, is_active) VALUES (?, ?, ?, 20, 1)", [
                                $catId, $subcatRaw, $subSlug
                            ]);
                            $subcatId = (int)$newSubId;
                            $subcatLookup[$subKey] = $subcatId;
                        }
                    }

                    // Check for existing product by SKU
                    $existing = null;
                    if (!empty($sku)) {
                        $existing = db_fetch_one("SELECT id FROM products WHERE sku = ?", [$sku]);
                    }

                    if (empty($sku)) {
                        $sku = generate_unique_sku($name);
                    }

                    $slug = generate_unique_slug($name, 'products', $existing['id'] ?? 0);

                    // Default image fallback if left empty
                    if (empty($image)) {
                        $image = 'vachanamrut-english.jpg';
                    }

                    if ($existing && $upsertMode) {
                        // Update existing
                        db_query("UPDATE products SET department_id = ?, category_id = ?, subcategory_id = ?, name = ?, slug = ?, price = ?, sale_price = ?, stock_qty = ?, short_description = ?, description = ?, image = ?, is_featured = ?, on_sale = ?, is_active = ? WHERE id = ?", [
                            $deptId, $catId, $subcatId, $name, $slug, $price, $salePrice, $stock, $shortDesc, $desc, $image, $isFeatured, $onSale, $isActive, $existing['id']
                        ]);
                        $updated++;
                    } elseif ($existing && !$upsertMode) {
                        // If exists and upsert not allowed, generate alternative unique SKU and insert
                        $altSku = generate_unique_sku($name);
                        db_query("INSERT INTO products (department_id, category_id, subcategory_id, name, slug, sku, price, sale_price, stock_qty, short_description, description, image, is_featured, on_sale, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                            $deptId, $catId, $subcatId, $name, $slug, $altSku, $price, $salePrice, $stock, $shortDesc, $desc, $image, $isFeatured, $onSale, $isActive
                        ]);
                        $inserted++;
                    } else {
                        // Fresh insert
                        db_query("INSERT INTO products (department_id, category_id, subcategory_id, name, slug, sku, price, sale_price, stock_qty, short_description, description, image, is_featured, on_sale, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [
                            $deptId, $catId, $subcatId, $name, $slug, $sku, $price, $salePrice, $stock, $shortDesc, $desc, $image, $isFeatured, $onSale, $isActive
                        ]);
                        $inserted++;
                    }
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $importSuccess = false;
                set_flash('error', 'Import failed and all database updates were rolled back: ' . $e->getMessage());
            }

            fclose($stream);

            if ($importSuccess) {
                $importStats = [
                    'total' => $totalRows,
                    'inserted' => $inserted,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'images_unpacked' => count($unpackedImages),
                ];

                set_flash('success', "Bulk Import Complete: $inserted new products added, $updated updated, and " . count($unpackedImages) . " images linked successfully!");
            }
        }
    }
}

// Fetch Departments and Categories for reference guide
$allDepts = db_fetch_all("SELECT * FROM departments ORDER BY sort_order ASC, name ASC");
$allCats = db_fetch_all("SELECT c.*, d.name as dept_name FROM categories c JOIN departments d ON c.department_id = d.id ORDER BY d.name ASC, c.name ASC");

$adminPageTitle = 'Bulk Product Import (CSV & Images)';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Title Bar with Quick Actions -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--admin-text); margin: 0;">
      📥 Bulk Product Import & Image Uploader
    </h1>
    <p style="color: var(--admin-text-muted); font-size: 0.875rem; margin-top: 0.25rem;">
      Insert hundreds of sacred items at one time using CSV spreadsheet with automatic image extraction.
    </p>
  </div>
  <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
    <a href="bulk_import.php?action=sample_template" class="admin-btn admin-btn-secondary" title="Download sample CSV template ready for Excel">
      📥 Download Sample CSV
    </a>
    <a href="bulk_import.php?action=export" class="admin-btn admin-btn-secondary" title="Export current catalog">
      📤 Export Current Catalog
    </a>
    <a href="products.php" class="admin-btn admin-btn-secondary">
      &larr; View Products
    </a>
  </div>
</div>

<!-- Import Results Alert Box (if just imported) -->
<?php if ($importStats): ?>
  <div class="admin-card" style="border-left: 4px solid #10b981; background: #ecfdf5; margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
      <div>
        <h3 style="font-size: 1.15rem; font-weight: 700; color: #065f46; margin-bottom: 0.25rem;">
          🎉 Bulk Import Finished Successfully!
        </h3>
        <p style="font-size: 0.875rem; color: #047857;">
          Processed <strong><?= $importStats['total'] ?></strong> rows:
          <strong><?= $importStats['inserted'] ?></strong> new items created, 
          <strong><?= $importStats['updated'] ?></strong> items updated, and 
          <strong><?= $importStats['images_unpacked'] ?></strong> images stored in <code>uploads/products/</code>.
        </p>
      </div>
      <div>
        <a href="products.php" class="admin-btn admin-btn-primary">
          Browse Imported Products in Catalog &rarr;
        </a>
      </div>
    </div>

    <?php if (!empty($importErrors)): ?>
      <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #a7f3d0; font-size: 0.8125rem; color: #991b1b;">
        <strong>Warnings / Skipped Rows:</strong>
        <ul style="margin-top: 0.35rem; padding-left: 1.25rem;">
          <?php foreach ($importErrors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- 3 Steps Simple Workflow Guide -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 2rem;">
  
  <div class="admin-card" style="margin: 0; padding: 1.25rem; border-top: 3px solid var(--admin-primary);">
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
      <span style="width: 28px; height: 28px; border-radius: 999px; background: var(--admin-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8125rem;">1</span>
      <strong style="color: var(--admin-text); font-size: 0.95rem;">Download Template</strong>
    </div>
    <p style="font-size: 0.8125rem; color: var(--admin-text-muted); line-height: 1.5; margin-bottom: 0.75rem;">
      Download the pre-formatted sample CSV file. It has all the column headers configured for Microsoft Excel and Google Sheets.
    </p>
    <a href="bulk_import.php?action=sample_template" class="admin-btn admin-btn-secondary admin-btn-sm" style="width: 100%; justify-content: center;">
      📥 Get Template CSV
    </a>
  </div>

  <div class="admin-card" style="margin: 0; padding: 1.25rem; border-top: 3px solid #d97706;">
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
      <span style="width: 28px; height: 28px; border-radius: 999px; background: #d97706; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8125rem;">2</span>
      <strong style="color: var(--admin-text); font-size: 0.95rem;">Fill In Products</strong>
    </div>
    <p style="font-size: 0.8125rem; color: var(--admin-text-muted); line-height: 1.5;">
      Add your item names, prices, categories, and image filenames (e.g. <code>vachanamrut.jpg</code>). You can copy department names from the reference guide below.
    </p>
  </div>

  <div class="admin-card" style="margin: 0; padding: 1.25rem; border-top: 3px solid #10b981;">
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
      <span style="width: 28px; height: 28px; border-radius: 999px; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8125rem;">3</span>
      <strong style="color: var(--admin-text); font-size: 0.95rem;">Upload & Done!</strong>
    </div>
    <p style="font-size: 0.8125rem; color: var(--admin-text-muted); line-height: 1.5;">
      Upload your CSV file and optionally attach a ZIP file of images (or select multiple photos). The system imports everything in seconds!
    </p>
  </div>

</div>

<!-- Main Bulk Import Form -->
<div class="admin-card">
  <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
    <span>⚡</span> Upload CSV & Product Images
  </h3>

  <form method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="do_import" value="1">

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 1.75rem;">
      
      <!-- Left Column: CSV Upload / Paste -->
      <div>
        <label class="admin-label" style="font-size: 0.95rem; margin-bottom: 0.5rem; display: block;">
          📊 1. Product Data Input Mode *
        </label>
        
        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem;">
          <button type="button" id="tabUploadBtn" onclick="switchDataTab('upload')" class="admin-btn admin-btn-sm" style="background: var(--admin-primary); color: #fff; border: 1px solid var(--admin-primary); border-radius: 6px; font-weight: 600; cursor: pointer;">
            📁 Upload CSV File
          </button>
          <button type="button" id="tabPasteBtn" onclick="switchDataTab('paste')" class="admin-btn admin-btn-sm" style="background: #f1f5f9; color: var(--admin-text); border: 1px solid var(--admin-border); border-radius: 6px; font-weight: 600; cursor: pointer;">
            📝 Paste Spreadsheet / Excel Rows
          </button>
        </div>

        <div id="uploadPane">
          <div style="border: 2px dashed var(--admin-border); border-radius: var(--admin-radius-sm); padding: 1.5rem; text-align: center; background: #f8fafc; transition: border-color 0.2s;" ondragover="this.style.borderColor='var(--admin-primary)'" ondragleave="this.style.borderColor='var(--admin-border)'">
            <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" style="display: block; width: 100%; margin-bottom: 0.5rem;">
            <small style="color: var(--admin-text-muted); font-size: 0.75rem;">
              Supports .csv files from Excel, Google Sheets, or custom exports.
            </small>
          </div>
        </div>

        <div id="pastePane" style="display: none;">
          <div style="margin-bottom: 0.35rem; font-size: 0.75rem; color: var(--admin-text-muted);">
            💡 Copy rows directly from Excel or Google Sheets (Ctrl+C) and paste them here (Ctrl+V):
          </div>
          <textarea name="csv_text" id="csv_text" rows="7" class="admin-textarea" style="font-family: monospace; font-size: 0.8125rem;" placeholder="name,department,category,price,sale_price,stock_qty,sku,image&#10;Shri Vachanamrut,Books,Gujarati Books,350,299,100,SSK-BK-101,vachanamrut.jpg&#10;Pure Tulsi Kanthi,Kanthis,Plated Kanthi,199,,75,SSK-KN-201,tulsi-kanthi.jpg"></textarea>
        </div>
      </div>

      <!-- Right Column: Product Images (ZIP or Multi-Files) -->
      <div>
        <div class="admin-form-group">
          <label class="admin-label" for="images_zip" style="font-size: 0.95rem;">
            🖼️ 2. Bulk Product Images (Optional)
          </label>
          <div style="border: 2px dashed #cbd5e1; border-radius: var(--admin-radius-sm); padding: 1.25rem; background: #f8fafc; margin-bottom: 1rem;">
            <div style="font-weight: 700; font-size: 0.8125rem; margin-bottom: 0.35rem; color: var(--admin-text);">
              Option A: ZIP Archive of Images (.zip)
            </div>
            <input type="file" name="images_zip" id="images_zip" accept=".zip" style="display: block; width: 100%; margin-bottom: 0.35rem;">
            <small style="color: var(--admin-text-muted); font-size: 0.75rem;">
              Upload a .zip containing all photos. They are automatically extracted to <code>uploads/products/</code> and mapped to your CSV.
            </small>
          </div>

          <div style="border: 2px dashed #cbd5e1; border-radius: var(--admin-radius-sm); padding: 1.25rem; background: #f8fafc;">
            <div style="font-weight: 700; font-size: 0.8125rem; margin-bottom: 0.35rem; color: var(--admin-text);">
              Option B: Select Multiple Images Directly
            </div>
            <input type="file" name="image_files[]" id="image_files" multiple accept="image/*" style="display: block; width: 100%; margin-bottom: 0.35rem;">
            <small style="color: var(--admin-text-muted); font-size: 0.75rem;">
              Hold Ctrl or Shift to select multiple images (JPG, PNG, WEBP).
            </small>
          </div>
        </div>
      </div>

    </div>

    <!-- Import Settings -->
    <div style="background: #f8fafc; border: 1px solid var(--admin-border); border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.75rem; display: flex; gap: 2rem; flex-wrap: wrap;">
      <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; cursor: pointer;">
        <input type="checkbox" name="upsert_existing" value="1" checked>
        <strong>Update existing products if SKU already exists (Upsert)</strong>
      </label>

      <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; cursor: pointer;">
        <input type="checkbox" name="auto_create_cats" value="1" checked>
        <strong>Automatically create missing Departments & Categories</strong>
      </label>
    </div>

    <!-- Submit Button -->
    <div style="display: flex; justify-content: flex-end; gap: 1rem; align-items: center;">
      <a href="products.php" class="admin-btn admin-btn-secondary">Cancel</a>
      <button type="submit" class="admin-btn admin-btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem;">
        🚀 Import Products Now
      </button>
    </div>

  </form>
</div>

<!-- Reference Guide: Column Specs & Active Departments -->
<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
    <h3 style="font-size: 1.15rem; font-weight: 700;">
      📋 CSV Column Reference Guide
    </h3>
    <span style="font-size: 0.8125rem; color: var(--admin-text-muted);">
      Use these headers in your spreadsheet
    </span>
  </div>

  <div class="table-responsive" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch; margin-bottom: 2rem;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Column Header</th>
          <th>Required?</th>
          <th>Example Value</th>
          <th>Notes & Behavior</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><code>name</code></td>
          <td><span style="color: #dc2626; font-weight: 700;">Yes *</span></td>
          <td>Shri Vachanamrut</td>
          <td>Product title / book name.</td>
        </tr>
        <tr>
          <td><code>price</code></td>
          <td><span style="color: #dc2626; font-weight: 700;">Yes *</span></td>
          <td>350.00</td>
          <td>Regular selling price in ₹.</td>
        </tr>
        <tr>
          <td><code>department</code></td>
          <td>Optional</td>
          <td>Books, Kanthis, Ghar Mandirs</td>
          <td>Auto-created if does not exist.</td>
        </tr>
        <tr>
          <td><code>category</code></td>
          <td>Optional</td>
          <td>Gujarati Books, Plated Kanthi</td>
          <td>Auto-created under parent department.</td>
        </tr>
        <tr>
          <td><code>sale_price</code></td>
          <td>Optional</td>
          <td>299.00</td>
          <td>Discounted offer price (leave blank if no discount).</td>
        </tr>
        <tr>
          <td><code>stock_qty</code></td>
          <td>Optional</td>
          <td>100</td>
          <td>Available inventory (defaults to 100).</td>
        </tr>
        <tr>
          <td><code>sku</code></td>
          <td>Optional</td>
          <td>SSK-BK-101</td>
          <td>Unique item code (auto-generated if left empty).</td>
        </tr>
        <tr>
          <td><code>image</code></td>
          <td>Optional</td>
          <td>vachanamrut.jpg</td>
          <td>Image filename inside ZIP / uploads, or full web URL (<code>https://...</code>).</td>
        </tr>
        <tr>
          <td><code>short_description</code></td>
          <td>Optional</td>
          <td>Sacred discourses...</td>
          <td>One-line summary for product card.</td>
        </tr>
        <tr>
          <td><code>description</code></td>
          <td>Optional</td>
          <td>Complete edition...</td>
          <td>Full detailed product description.</td>
        </tr>
        <tr>
          <td><code>is_featured</code></td>
          <td>Optional</td>
          <td>1 or 0</td>
          <td>1 = Highlight on homepage showcase.</td>
        </tr>
        <tr>
          <td><code>on_sale</code></td>
          <td>Optional</td>
          <td>1 or 0</td>
          <td>1 = Display in Best Offers section.</td>
        </tr>
        <tr>
          <td><code>is_active</code></td>
          <td>Optional</td>
          <td>1 or 0</td>
          <td>1 = Published to live store immediately (defaults to 1).</td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Active Departments & Categories Quick Reference -->
  <div style="background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-card); padding: 1.25rem;">
    <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--heading);">
      Active Store Departments & Categories (Copy directly into CSV)
    </h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.75rem;">
      <?php foreach ($allDepts as $dept): ?>
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.65rem 0.85rem; box-shadow: var(--shadow-sm);">
          <div style="font-weight: 700; color: var(--heading); font-size: 0.875rem;">
            <?= htmlspecialchars($dept['name']) ?>
          </div>
          <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
            <?php 
              $cList = [];
              foreach ($allCats as $cat) {
                if ($cat['department_id'] == $dept['id']) {
                  $cList[] = $cat['name'];
                }
              }
              echo !empty($cList) ? htmlspecialchars(implode(', ', array_slice($cList, 0, 4))) : 'General';
            ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<script>
function switchDataTab(mode) {
  const uploadPane = document.getElementById('uploadPane');
  const pastePane = document.getElementById('pastePane');
  const tabUploadBtn = document.getElementById('tabUploadBtn');
  const tabPasteBtn = document.getElementById('tabPasteBtn');
  
  if (mode === 'upload') {
    if (uploadPane) uploadPane.style.display = 'block';
    if (pastePane) pastePane.style.display = 'none';
    if (tabUploadBtn) {
      tabUploadBtn.style.background = 'var(--admin-primary)';
      tabUploadBtn.style.color = '#fff';
      tabUploadBtn.style.borderColor = 'var(--admin-primary)';
    }
    if (tabPasteBtn) {
      tabPasteBtn.style.background = '#f1f5f9';
      tabPasteBtn.style.color = 'var(--admin-text)';
      tabPasteBtn.style.borderColor = 'var(--admin-border)';
    }
  } else {
    if (uploadPane) uploadPane.style.display = 'none';
    if (pastePane) pastePane.style.display = 'block';
    if (tabPasteBtn) {
      tabPasteBtn.style.background = 'var(--admin-primary)';
      tabPasteBtn.style.color = '#fff';
      tabPasteBtn.style.borderColor = 'var(--admin-primary)';
    }
    if (tabUploadBtn) {
      tabUploadBtn.style.background = '#f1f5f9';
      tabUploadBtn.style.color = 'var(--admin-text)';
      tabUploadBtn.style.borderColor = 'var(--admin-border)';
    }
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
