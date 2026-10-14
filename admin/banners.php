<?php
/**
 * Admin - Hero Slideshow / Main Banners Manager
 * Allows admin to edit, add, reorder, and toggle hero slideshow slides for the index page.
 */

require_once __DIR__ . '/includes/admin_bootstrap.php';
admin_require_auth('banners');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD NEW SLIDE
    if ($action === 'add_slide') {
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $link = trim($_POST['link'] ?? 'catalog.php');
        $sortOrder = (int)($_POST['sort_order'] ?? 1);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $imagePath = trim($_POST['image_path'] ?? '');

        // Handle File Upload if provided securely
        if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image_file'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] <= $maxSize) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                $allowedMimes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                ];
                if (isset($allowedMimes[$mime])) {
                    $ext = $allowedMimes[$mime];
                    $filename = 'slide_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $targetDir = ROOT_PATH . 'assets/images/';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    $dest = $targetDir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        chmod($dest, 0644);
                        $imagePath = 'assets/images/' . $filename;
                    }
                }
            }
        }

        if (empty($imagePath)) {
            $imagePath = 'assets/images/slide1.jpg'; // default fallback
        }

        db_query("INSERT INTO banners (title, subtitle, image, link, sort_order, is_active, position) VALUES (?, ?, ?, ?, ?, ?, 'hero')", [
            $title, $subtitle, $imagePath, $link, $sortOrder, $isActive
        ]);

        set_flash('success', 'New hero slide added successfully!');
        header('Location: banners.php');
        exit;
    }

    // 2. EDIT SLIDE
    if ($action === 'edit_slide') {
        $slideId = (int)($_POST['slide_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $link = trim($_POST['link'] ?? 'catalog.php');
        $sortOrder = (int)($_POST['sort_order'] ?? 1);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $imagePath = trim($_POST['image_path'] ?? '');

        // Handle File Upload if provided securely
        if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image_file'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] <= $maxSize) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                $allowedMimes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                ];
                if (isset($allowedMimes[$mime])) {
                    $ext = $allowedMimes[$mime];
                    $filename = 'slide_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $targetDir = ROOT_PATH . 'assets/images/';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    $dest = $targetDir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        chmod($dest, 0644);
                        $imagePath = 'assets/images/' . $filename;
                    }
                }
            }
        }

        if (!empty($imagePath)) {
            db_query("UPDATE banners SET title = ?, subtitle = ?, image = ?, link = ?, sort_order = ?, is_active = ? WHERE id = ?", [
                $title, $subtitle, $imagePath, $link, $sortOrder, $isActive, $slideId
            ]);
        } else {
            db_query("UPDATE banners SET title = ?, subtitle = ?, link = ?, sort_order = ?, is_active = ? WHERE id = ?", [
                $title, $subtitle, $link, $sortOrder, $isActive, $slideId
            ]);
        }

        set_flash('success', 'Hero slide updated successfully!');
        header('Location: banners.php');
        exit;
    }

    // 3. TOGGLE ACTIVE STATUS
    if ($action === 'toggle_active') {
        $slideId = (int)($_POST['slide_id'] ?? 0);
        $slide = db_fetch_one("SELECT is_active FROM banners WHERE id = ?", [$slideId]);
        if ($slide) {
            $newVal = $slide['is_active'] ? 0 : 1;
            db_query("UPDATE banners SET is_active = ? WHERE id = ?", [$newVal, $slideId]);
            set_flash('success', 'Slide display status updated!');
        }
        header('Location: banners.php');
        exit;
    }

    // 4. MOVE UP / MOVE DOWN
    if ($action === 'move_up' || $action === 'move_down') {
        $slideId = (int)($_POST['slide_id'] ?? 0);
        $current = db_fetch_one("SELECT * FROM banners WHERE id = ?", [$slideId]);
        if ($current) {
            $curOrder = (int)$current['sort_order'];
            $targetOrder = $action === 'move_up' ? $curOrder - 1 : $curOrder + 1;
            $neighbor = db_fetch_one("SELECT * FROM banners WHERE sort_order = ?", [$targetOrder]);
            if ($neighbor) {
                db_query("UPDATE banners SET sort_order = ? WHERE id = ?", [$curOrder, $neighbor['id']]);
                db_query("UPDATE banners SET sort_order = ? WHERE id = ?", [$targetOrder, $current['id']]);
                set_flash('success', 'Slide order changed successfully!');
            }
        }
        header('Location: banners.php');
        exit;
    }

    // 5. DELETE SLIDE
    if ($action === 'delete_slide') {
        $slideId = (int)($_POST['slide_id'] ?? 0);
        db_query("DELETE FROM banners WHERE id = ?", [$slideId]);
        set_flash('success', 'Slide deleted successfully!');
        header('Location: banners.php');
        exit;
    }

    // 6. REORDER ALL
    if ($action === 'reorder_all') {
        $orders = $_POST['order'] ?? [];
        foreach ($orders as $sId => $ord) {
            db_query("UPDATE banners SET sort_order = ? WHERE id = ?", [(int)$ord, (int)$sId]);
        }
        set_flash('success', 'Slide sequence saved successfully!');
        header('Location: banners.php');
        exit;
    }
}

// Fetch all slides ordered by sort_order
$slides = db_fetch_all("SELECT * FROM banners ORDER BY sort_order ASC, id ASC");
$editSlideId = (int)($_GET['edit'] ?? 0);
$editSlide = null;
if ($editSlideId > 0) {
    $editSlide = db_fetch_one("SELECT * FROM banners WHERE id = ?", [$editSlideId]);
}
$adminPageTitle = 'Hero Slideshow Manager';
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h1 style="font-size: 1.75rem; font-weight: 800; margin: 0; color: var(--admin-text-main);">
      🖼️ Homepage Hero Slideshow Manager
    </h1>
    <p style="margin: 0.25rem 0 0 0; color: var(--admin-text-muted); font-size: 0.9rem;">
      Customize the hero slider images, headlines, promotional subtitles, and destination links shown on the index page.
    </p>
  </div>
  <div style="display: flex; gap: 0.75rem;">
    <a href="../index.php" target="_blank" class="admin-btn admin-btn-secondary" style="display: flex; align-items: center; gap: 0.5rem;">
      <span>👁️</span> Preview Live Slideshow ↗
    </a>
    <a href="#addSlideModal" onclick="document.getElementById('slideFormCard').scrollIntoView({behavior: 'smooth'})" class="admin-btn admin-btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
      <span>➕</span> Add New Slide
    </a>
  </div>
</div>

<!-- Slides List & Sequence Table -->
<div class="admin-card" style="margin-bottom: 2.5rem;">
  <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
      <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--heading);">Active Slides in Hero Slideshow</h3>
      <span style="font-size: 0.85rem; color: var(--admin-text-muted);">
        Slides rotate automatically every 5 seconds on the homepage with smooth transitions.
      </span>
    </div>
    <span class="badge" style="background: rgba(1, 23, 96, 0.08); color: var(--navy-2); font-weight: 700; padding: 0.4rem 0.85rem; border-radius: 999px; border: 1px solid var(--border);">
      Total: <?= count($slides) ?> Slides
    </span>
  </div>

  <div class="admin-card-body" style="padding: 0;">
    <form method="POST" action="banners.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="reorder_all">
      <div class="table-responsive" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
        <table class="admin-table" style="width: 100%; min-width: 860px;">
          <thead>
            <tr>
              <th style="width: 100px; text-align: center;">Order</th>
              <th style="width: 140px;">Preview</th>
              <th>Headline & Subtitle</th>
              <th style="width: 220px;">Target Link</th>
              <th style="width: 110px; text-align: center;">Status</th>
              <th style="width: 230px; text-align: center;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($slides)): ?>
              <tr>
                <td colspan="6" style="text-align: center; padding: 3rem; color: var(--admin-text-muted);">
                  No slides found. Click "Add New Hero Slide" below to create your first slide.
                </td>
              </tr>
            <?php endif; ?>

            <?php foreach ($slides as $idx => $s): ?>
              <tr style="<?= !$s['is_active'] ? 'opacity: 0.65; background: var(--surface-2);' : '' ?>">
                <!-- Compact Order Group -->
                <td style="text-align: center;">
                  <div style="display: inline-flex; align-items: center; gap: 4px; background: var(--surface-2); padding: 4px 6px; border-radius: 8px; border: 1px solid var(--border);">
                    <input type="number" name="order[<?= $s['id'] ?>]" value="<?= (int)$s['sort_order'] ?>" style="width: 44px; text-align: center; padding: 0.3rem 0.2rem; font-weight: 700; border: 1px solid var(--border); border-radius: 6px; background: var(--surface); color: var(--text);">
                    <div style="display: flex; flex-direction: column; gap: 2px;">
                      <?php if ($idx > 0): ?>
                        <button type="submit" formaction="banners.php" name="action" value="move_up" onclick="setSlideId(<?= $s['id'] ?>)" class="admin-btn admin-btn-secondary admin-btn-sm" style="padding: 1px 5px; font-size: 0.65rem; height: 16px; line-height: 1;" title="Move Up">▲</button>
                      <?php endif; ?>
                      <?php if ($idx < count($slides) - 1): ?>
                        <button type="submit" formaction="banners.php" name="action" value="move_down" onclick="setSlideId(<?= $s['id'] ?>)" class="admin-btn admin-btn-secondary admin-btn-sm" style="padding: 1px 5px; font-size: 0.65rem; height: 16px; line-height: 1;" title="Move Down">▼</button>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>

                <!-- 16:9 Image Thumbnail -->
                <td>
                  <div style="width: 120px; height: 68px; border-radius: 8px; overflow: hidden; background: #0b1437; position: relative; border: 1px solid var(--border); box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
                    <img src="../<?= htmlspecialchars($s['image']) ?>" alt="Slide" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.onerror=null; this.src='../assets/images/placeholder.php?title=SSK+Banner';">
                    <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0,0,0,0.7); color: #fff; font-size: 0.625rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 4px;">
                      #<?= $idx + 1 ?>
                    </span>
                  </div>
                </td>

                <!-- Title & Subtitle -->
                <td>
                  <div style="font-weight: 700; font-size: 0.95rem; color: var(--heading); margin-bottom: 0.25rem;">
                    <?= htmlspecialchars($s['title']) ?>
                  </div>
                  <div style="font-size: 0.8125rem; color: var(--text-muted); max-width: 420px; line-height: 1.4;">
                    <?= htmlspecialchars($s['subtitle']) ?>
                  </div>
                  <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                    File: <code style="font-size: 0.75rem;"><?= htmlspecialchars($s['image']) ?></code>
                  </div>
                </td>

                <!-- Target Link Pill Badge -->
                <td>
                  <a href="../<?= htmlspecialchars($s['link']) ?>" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 6px; padding: 0.35rem 0.75rem; border-radius: 999px; background: var(--surface-2); border: 1px solid var(--border); font-size: 0.8125rem; color: var(--navy-2); text-decoration: none; font-weight: 600; transition: all 0.2s ease;">
                    <span>🔗</span> <?= htmlspecialchars($s['link']) ?> <span style="font-size: 0.75rem; opacity: 0.7;">↗</span>
                  </a>
                </td>

                <!-- Status Badge -->
                <td style="text-align: center;">
                  <?php if ($s['is_active']): ?>
                    <span style="display: inline-block; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">
                      Active
                    </span>
                  <?php else: ?>
                    <span style="display: inline-block; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                      Hidden
                    </span>
                  <?php endif; ?>
                </td>

                <!-- Actions: Single Aligned Horizontal Row -->
                <td style="text-align: center; white-space: nowrap;">
                  <div style="display: inline-flex; gap: 8px; align-items: center; justify-content: center;">
                    <!-- Edit Button -->
                    <a href="banners.php?edit=<?= $s['id'] ?>#slideFormCard" class="admin-btn admin-btn-secondary admin-btn-sm" style="display: inline-flex; align-items: center; gap: 4px; height: 32px; padding: 0 0.65rem; font-size: 0.8125rem;">
                      ✏️ Edit
                    </a>

                    <!-- Toggle Visibility -->
                    <button type="button" onclick="toggleSlideStatus(<?= $s['id'] ?>)" class="admin-btn admin-btn-sm <?= $s['is_active'] ? 'admin-btn-secondary' : 'admin-btn-primary' ?>" style="display: inline-flex; align-items: center; gap: 4px; height: 32px; padding: 0 0.65rem; font-size: 0.8125rem;">
                      <?= $s['is_active'] ? '👁️ Hide' : '✨ Show' ?>
                    </button>

                    <!-- Delete Button -->
                    <button type="button" onclick="confirmDeleteSlide(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['title'])) ?>')" class="admin-btn admin-btn-danger admin-btn-sm" style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; padding: 0; font-size: 0.875rem;" title="Delete Slide">
                      🗑️
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($slides)): ?>
        <div style="padding: 1rem 1.5rem; background: var(--surface-2); border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
          <span style="font-size: 0.85rem; color: var(--text-muted);">
            Tip: You can edit order numbers in the table and click "Save Order Sequence" to reorder all slides at once.
          </span>
          <button type="submit" class="admin-btn admin-btn-primary">
            💾 Save Order Sequence
          </button>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Add or Edit Slide Form Card -->
<div class="admin-card" id="slideFormCard" style="border: 2px solid <?= $editSlide ? 'var(--navy-2)' : 'var(--border)' ?>;">
  <div class="admin-card-header" style="background: <?= $editSlide ? 'var(--stat-blue-bg)' : 'var(--surface)' ?>; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
      <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: <?= $editSlide ? 'var(--navy-2)' : 'var(--heading)' ?>;">
        <?= $editSlide ? '✏️ Edit Hero Slide #' . $editSlide['id'] : '➕ Add New Hero Slide' ?>
      </h3>
      <span style="font-size: 0.8125rem; color: var(--text-muted);">
        <?= $editSlide ? 'Update the details or replace the slide image below.' : 'Create a new high-impact banner slide for the homepage carousel.' ?>
      </span>
    </div>
    <?php if ($editSlide): ?>
      <a href="banners.php" class="admin-btn admin-btn-secondary admin-btn-sm">Cancel Edit</a>
    <?php endif; ?>
  </div>

  <div class="admin-card-body" style="padding: 2rem;">
    <form method="POST" action="banners.php" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $editSlide ? 'edit_slide' : 'add_slide' ?>">
      <?php if ($editSlide): ?>
        <input type="hidden" name="slide_id" value="<?= $editSlide['id'] ?>">
      <?php endif; ?>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 280px), 1fr)); gap: 1.5rem;">
        
        <!-- Left Column: Copy & Link -->
        <div>
          <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label" style="font-weight: 700; color: var(--heading); margin-bottom: 0.4rem; display: block;">Slide Title / Main Headline *</label>
            <input type="text" name="title" class="form-input" required placeholder="e.g. Festive Specials & Sacred Literature" value="<?= htmlspecialchars($editSlide['title'] ?? '') ?>" style="width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1.5px solid var(--border); background: var(--surface); color: var(--text);">
            <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.35rem;">Displayed in bold hero typography on top of the slide.</span>
          </div>

          <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label" style="font-weight: 700; color: var(--heading); margin-bottom: 0.4rem; display: block;">Slide Subtitle / Description</label>
            <textarea name="subtitle" class="form-input" rows="3" placeholder="e.g. Authentic scriptures, sanctified tulsi kanthis, and ghar mandirs from Shree Swaminarayan Mandir, Kalakunj, Surat." style="width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1.5px solid var(--border); background: var(--surface); color: var(--text); line-height: 1.5;"><?= htmlspecialchars($editSlide['subtitle'] ?? '') ?></textarea>
            <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.35rem;">Brief summary explaining the devotional collection or festival special.</span>
          </div>

          <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label" style="font-weight: 700; color: var(--heading); margin-bottom: 0.4rem; display: block;">Button Target Link URL</label>
            <input type="text" name="link" class="form-input" placeholder="e.g. catalog.php?dept=1" value="<?= htmlspecialchars($editSlide['link'] ?? 'catalog.php') ?>" style="width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1.5px solid var(--border); background: var(--surface); color: var(--text);">
            <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.35rem;">URL devotees are redirected to upon clicking the slide CTA button.</span>
          </div>
        </div>

        <!-- Right Column: Image & Status -->
        <div>
          <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label" style="font-weight: 700; color: var(--heading); margin-bottom: 0.4rem; display: block;">Upload New Slide Image</label>
            <div style="border: 2px dashed var(--border); padding: 1.5rem; border-radius: 12px; background: var(--surface-2); text-align: center; transition: border-color 0.2s ease;">
              <input type="file" id="slideFileInput" name="image_file" class="form-input" accept="image/png, image/jpeg, image/webp" style="display: block; width: 100%; padding: 0.5rem; background: var(--surface); border-radius: 6px; border: 1px solid var(--border);">
              <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.5rem;">
                Supported: WebP, JPEG, PNG (Max 5MB). Optimal resolution: <strong>1920 × 600 px (16:5 ratio)</strong>.
              </span>
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label" style="font-weight: 700; color: var(--heading); margin-bottom: 0.4rem; display: block;">Or Existing Image Path</label>
            <input type="text" name="image_path" class="form-input" placeholder="assets/images/slide1.webp" value="<?= htmlspecialchars($editSlide['image'] ?? '') ?>" style="width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1.5px solid var(--border); background: var(--surface); color: var(--text);">
            <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 0.35rem;">Default available: <code>assets/images/slide1.webp</code>, <code>slide2.webp</code>, <code>slide3.webp</code></span>
          </div>

          <?php if ($editSlide && !empty($editSlide['image'])): ?>
            <div style="margin-bottom: 1.5rem;">
              <span style="font-size: 0.8125rem; font-weight: 700; color: var(--heading); display: block; margin-bottom: 0.4rem;">Current Active Preview:</span>
              <div style="height: 90px; width: 180px; border-radius: 8px; overflow: hidden; border: 1.5px solid var(--border); box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
                <img src="../<?= htmlspecialchars($editSlide['image']) ?>" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
              </div>
            </div>
          <?php endif; ?>

          <div style="display: flex; gap: 1.5rem; align-items: center; margin-top: 1rem; flex-wrap: wrap;">
            <div class="form-group" style="margin-bottom: 0;">
              <label class="form-label" style="font-weight: 700; color: var(--heading); margin-bottom: 0.3rem; display: block;">Display Sequence</label>
              <input type="number" name="sort_order" class="form-input" style="width: 110px; padding: 0.6rem 0.85rem; border-radius: 8px; border: 1.5px solid var(--border); background: var(--surface); color: var(--text); font-weight: 700;" value="<?= (int)($editSlide['sort_order'] ?? (count($slides) + 1)) ?>">
            </div>

            <div style="display: flex; align-items: center; gap: 0.65rem; margin-top: 1.25rem;">
              <input type="checkbox" id="is_active" name="is_active" value="1" <?= (!$editSlide || !empty($editSlide['is_active'])) ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: var(--navy-2); cursor: pointer;">
              <label for="is_active" style="font-weight: 700; font-size: 0.9rem; cursor: pointer; color: var(--heading);">
                Active (Display on Homepage)
              </label>
            </div>
          </div>

        </div>

      </div>

      <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 1rem;">
        <?php if ($editSlide): ?>
          <a href="banners.php" class="admin-btn admin-btn-secondary">Cancel</a>
          <button type="submit" class="admin-btn admin-btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1rem;">
            💾 Save Changes
          </button>
        <?php else: ?>
          <button type="submit" class="admin-btn admin-btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1rem;">
            ➕ Add Hero Slide
          </button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Single action form for AJAX/JS operations -->
<form id="actionForm" method="POST" action="banners.php" style="display: none;">
  <?= csrf_field() ?>
  <input type="hidden" name="action" id="actionField">
  <input type="hidden" name="slide_id" id="slideIdField">
</form>

<script>
function setSlideId(id) {
  document.getElementById('slideIdField').value = id;
}

function toggleSlideStatus(id) {
  document.getElementById('actionField').value = 'toggle_active';
  document.getElementById('slideIdField').value = id;
  document.getElementById('actionForm').submit();
}

function confirmDeleteSlide(id, title) {
  if (typeof showConfirmModal === 'function') {
    showConfirmModal(
      'Delete Hero Slide',
      'Are you sure you want to delete slide "' + title + '"? This will remove it from the homepage slideshow.',
      function () {
        document.getElementById('actionField').value = 'delete_slide';
        document.getElementById('slideIdField').value = id;
        document.getElementById('actionForm').submit();
      },
      'Yes, Delete Slide'
    );
  } else if (confirm('Are you sure you want to delete slide: "' + title + '"?')) {
    document.getElementById('actionField').value = 'delete_slide';
    document.getElementById('slideIdField').value = id;
    document.getElementById('actionForm').submit();
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
