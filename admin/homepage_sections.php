<?php
/**
 * Admin - Homepage Product Showcase Sections Manager
 * Controls display order (which shows 1st, 2nd, 3rd, 4th), titles, and visibility
 */

require_once __DIR__ . '/includes/admin_bootstrap.php';
$adminPageTitle = 'Homepage Sections Layout';
require_once __DIR__ . '/includes/header.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'reorder_all') {
        $orders = $_POST['order'] ?? [];
        foreach ($orders as $secId => $sortOrder) {
            $secId = (int)$secId;
            $sortOrder = (int)$sortOrder;
            db_query("UPDATE homepage_sections SET sort_order = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$sortOrder, $secId]);
        }
        set_flash('Homepage sections order updated successfully!', 'success');
        header('Location: homepage_sections.php');
        exit;
    }

    if ($action === 'move_up' || $action === 'move_down') {
        $secId = (int)($_POST['section_id'] ?? 0);
        $current = db_fetch_one("SELECT * FROM homepage_sections WHERE id = ?", [$secId]);
        
        if ($current) {
            $curOrder = (int)$current['sort_order'];
            $targetOrder = $action === 'move_up' ? $curOrder - 1 : $curOrder + 1;
            
            // Find neighbor section
            $neighbor = db_fetch_one("SELECT * FROM homepage_sections WHERE sort_order = ?", [$targetOrder]);
            if ($neighbor) {
                // Swap sort orders
                db_query("UPDATE homepage_sections SET sort_order = ? WHERE id = ?", [$curOrder, $neighbor['id']]);
                db_query("UPDATE homepage_sections SET sort_order = ? WHERE id = ?", [$targetOrder, $current['id']]);
                set_flash('Display order adjusted successfully!', 'success');
            }
        }
        header('Location: homepage_sections.php');
        exit;
    }

    if ($action === 'toggle_active') {
        $secId = (int)($_POST['section_id'] ?? 0);
        $sec = db_fetch_one("SELECT is_active FROM homepage_sections WHERE id = ?", [$secId]);
        if ($sec) {
            $newVal = $sec['is_active'] ? 0 : 1;
            db_query("UPDATE homepage_sections SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$newVal, $secId]);
            set_flash('Section visibility updated!', 'success');
        }
        header('Location: homepage_sections.php');
        exit;
    }

    if ($action === 'update_details') {
        $secId = (int)($_POST['section_id'] ?? 0);
        $title = trim($_POST['section_title'] ?? '');
        $subtitle = trim($_POST['section_subtitle'] ?? '');
        $badge = trim($_POST['badge_text'] ?? '');
        $limit = max(2, min(12, (int)($_POST['items_limit'] ?? 4)));

        if (!empty($title)) {
            db_query("UPDATE homepage_sections SET section_title = ?, section_subtitle = ?, badge_text = ?, items_limit = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [
                $title, $subtitle, $badge, $limit, $secId
            ]);
            set_flash('Section details updated successfully!', 'success');
        }
        header('Location: homepage_sections.php');
        exit;
    }
}

// Fetch all sections ordered by sort_order
$sections = db_fetch_all("SELECT * FROM homepage_sections ORDER BY sort_order ASC, id ASC");
?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--admin-text); margin-bottom: 0.25rem;">
      Homepage Product Showcases (Order & Visibility)
    </h1>
    <p style="color: var(--admin-text-muted); font-size: 0.875rem;">
      Manage which showcase appears 1st, 2nd, 3rd, or 4th on the SSK Store homepage.
    </p>
  </div>
  <div>
    <a href="../index.php" target="_blank" class="admin-btn admin-btn-secondary">
      <span>🌐</span> Preview Homepage
    </a>
  </div>
</div>

<!-- Reorder Form -->
<form method="POST" action="homepage_sections.php">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="reorder_all">

  <div style="display: grid; gap: 1.25rem; margin-bottom: 2rem;">
    <?php foreach ($sections as $index => $sec): ?>
      <?php 
        $rank = $index + 1;
        $rankColors = [
            1 => ['bg' => '#ecfdf5', 'color' => '#059669', 'label' => '1st — Shows at Top'],
            2 => ['bg' => '#eff6ff', 'color' => '#2563eb', 'label' => '2nd Section'],
            3 => ['bg' => '#fef3c7', 'color' => '#d97706', 'label' => '3rd Section'],
            4 => ['bg' => '#f3e8ff', 'color' => '#7c3aed', 'label' => '4th Section'],
        ];
        $badgeMeta = $rankColors[$rank] ?? ['bg' => '#f4f4f5', 'color' => '#71717a', 'label' => "Rank #$rank"];
      ?>
      <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-card); padding: 1.5rem; box-shadow: var(--shadow-sm); display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;">
        
        <!-- Left: Order & Rank Info -->
        <div style="display: flex; align-items: center; gap: 1.25rem; min-width: 240px;">
          <div style="width: 48px; height: 48px; border-radius: 12px; background: <?= $badgeMeta['bg'] ?>; color: <?= $badgeMeta['color'] ?>; display: flex; flex-direction: column; align-items: center; justify-content: center; font-weight: 800; font-size: 1.25rem;">
            <span>#<?= $rank ?></span>
          </div>

          <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
              <span style="font-size: 0.75rem; font-weight: 700; background: <?= $badgeMeta['bg'] ?>; color: <?= $badgeMeta['color'] ?>; padding: 0.15rem 0.5rem; border-radius: 9999px;">
                <?= $badgeMeta['label'] ?>
              </span>
              <?php if (!$sec['is_active']): ?>
                <span style="font-size: 0.75rem; font-weight: 700; background: #fee2e2; color: #dc2626; padding: 0.15rem 0.5rem; border-radius: 9999px;">
                  HIDDEN
                </span>
              <?php endif; ?>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--admin-text); margin: 0;">
              <?= htmlspecialchars($sec['section_title']) ?>
            </h3>
            <span style="font-size: 0.75rem; color: var(--admin-text-muted); font-family: monospace;">
              key: <?= htmlspecialchars($sec['section_key']) ?>
            </span>
          </div>
        </div>

        <!-- Middle: Description & Subtitle -->
        <div style="flex: 1; min-width: 250px;">
          <p style="font-size: 0.875rem; color: var(--admin-text-muted); margin: 0 0 0.35rem 0; line-height: 1.4;">
            <?= htmlspecialchars($sec['section_subtitle']) ?>
          </p>
          <div style="display: flex; gap: 0.5rem; font-size: 0.75rem;">
            <span style="background: #f4f4f5; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600;">
              Badge: "<?= htmlspecialchars($sec['badge_text']) ?>"
            </span>
            <span style="background: #f4f4f5; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600;">
              Limit: <?= (int)$sec['items_limit'] ?> items
            </span>
          </div>
        </div>

        <!-- Right: Sort Order Selector & Quick Move Controls -->
        <div style="display: flex; align-items: center; gap: 0.75rem;">
          
          <!-- Numeric Position Input -->
          <div style="display: flex; align-items: center; gap: 0.35rem;">
            <label style="font-size: 0.75rem; font-weight: 700; color: var(--admin-text-muted);">Order:</label>
            <input type="number" name="order[<?= $sec['id'] ?>]" value="<?= $sec['sort_order'] ?>" min="1" max="10" class="admin-form-input" style="width: 60px; text-align: center; font-weight: 700; padding: 0.4rem;">
          </div>

          <!-- 1-Click Move Up / Down Buttons -->
          <div style="display: flex; flex-direction: column; gap: 2px;">
            <?php if ($index > 0): ?>
              <button type="submit" formaction="homepage_sections.php" name="action" value="move_up" onclick="document.getElementById('moveSecId').value='<?= $sec['id'] ?>';" class="admin-btn admin-btn-secondary admin-btn-sm" style="padding: 0.15rem 0.5rem; font-size: 0.75rem; line-height: 1;" title="Move Up">
                ▲
              </button>
            <?php else: ?>
              <span style="height: 18px;"></span>
            <?php endif; ?>

            <?php if ($index < count($sections) - 1): ?>
              <button type="submit" formaction="homepage_sections.php" name="action" value="move_down" onclick="document.getElementById('moveSecId').value='<?= $sec['id'] ?>';" class="admin-btn admin-btn-secondary admin-btn-sm" style="padding: 0.15rem 0.5rem; font-size: 0.75rem; line-height: 1;" title="Move Down">
                ▼
              </button>
            <?php endif; ?>
          </div>

          <!-- Edit Modal Trigger -->
          <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($sec)) ?>)">
            ✏️ Edit
          </button>

          <!-- Active / Inactive Toggle -->
          <button type="submit" formaction="homepage_sections.php" name="action" value="toggle_active" onclick="document.getElementById('moveSecId').value='<?= $sec['id'] ?>';" class="admin-btn admin-btn-sm <?= $sec['is_active'] ? 'admin-btn-secondary' : 'admin-btn-primary' ?>" style="font-size: 0.75rem;">
            <?= $sec['is_active'] ? 'Hide' : 'Show' ?>
          </button>

        </div>

      </div>
    <?php endforeach; ?>
  </div>

  <input type="hidden" name="section_id" id="moveSecId" value="">

  <div style="display: flex; justify-content: flex-end; gap: 1rem; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-card); padding: 1rem 1.5rem;">
    <button type="submit" class="admin-btn admin-btn-primary">
      💾 Save All Display Orders
    </button>
  </div>
</form>

<!-- Edit Section Details Modal -->
<div id="editSectionModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 9999; align-items: center; justify-content: center; padding: 1.5rem;">
  <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-card); max-width: 520px; width: 100%; padding: 2rem; box-shadow: var(--shadow); color: var(--text);">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
      <h3 id="modalSecTitle" style="font-size: 1.25rem; font-weight: 800; color: var(--heading); margin: 0;">Edit Showcase Section</h3>
      <button type="button" onclick="closeEditModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--admin-text-muted);">&times;</button>
    </div>

    <form method="POST" action="homepage_sections.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_details">
      <input type="hidden" name="section_id" id="modalSecId" value="">

      <div class="admin-form-group">
        <label class="admin-label">Section Heading</label>
        <input type="text" name="section_title" id="modalInputTitle" required class="admin-form-input">
      </div>

      <div class="admin-form-group">
        <label class="admin-label">Section Subtitle / Description</label>
        <textarea name="section_subtitle" id="modalInputSubtitle" rows="2" class="admin-form-input"></textarea>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
        <div class="admin-form-group">
          <label class="admin-label">Badge Label</label>
          <input type="text" name="badge_text" id="modalInputBadge" class="admin-form-input">
        </div>
        <div class="admin-form-group">
          <label class="admin-label">Max Items Displayed</label>
          <input type="number" name="items_limit" id="modalInputLimit" min="2" max="12" class="admin-form-input">
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
        <button type="button" onclick="closeEditModal()" class="admin-btn admin-btn-secondary">Cancel</button>
        <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
      </div>
    </form>

  </div>
</div>

<script>
function openEditModal(sec) {
  document.getElementById('modalSecId').value = sec.id;
  document.getElementById('modalSecTitle').textContent = 'Edit: ' + sec.section_title;
  document.getElementById('modalInputTitle').value = sec.section_title;
  document.getElementById('modalInputSubtitle').value = sec.section_subtitle;
  document.getElementById('modalInputBadge').value = sec.badge_text;
  document.getElementById('modalInputLimit').value = sec.items_limit;
  document.getElementById('editSectionModal').style.display = 'flex';
}
function closeEditModal() {
  document.getElementById('editSectionModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
