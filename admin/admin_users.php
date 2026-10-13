<?php
/**
 * Admin - Role-Based Access Control (RBAC) & Admin User Management
 * Allows superadmin to create staff accounts and assign page-by-page access permissions.
 */

$adminPageTitle = 'Admin Roles & Access Management';
require_once __DIR__ . '/includes/header.php';

// Superadmin or explicit admin_users permission check
if (!admin_has_permission('admin_users', $admin)) {
    set_flash('error', 'Access Denied: Only Superadmin can manage admin roles and permissions.');
    header('Location: index.php');
    exit;
}

$allPermissions = get_all_admin_permissions();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. CREATE NEW ADMIN USER
    if ($action === 'create_admin') {
        $fullName = trim($_POST['full_name'] ?? '');
        $username = strtolower(trim($_POST['username'] ?? ''));
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $selectedPerms = $_POST['permissions'] ?? [];

        if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
            set_flash('error', 'Please fill in all required fields (Name, Username, Email, Password).');
        } elseif (strlen($password) < 6) {
            set_flash('error', 'Password must be at least 6 characters.');
        } else {
            // Check username or email uniqueness
            $existing = db_fetch_one("SELECT id FROM admin_users WHERE username = ? OR email = ?", [$username, $email]);
            if ($existing) {
                set_flash('error', 'An admin account with this username or email already exists.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $permsJson = ($role === 'super_admin') ? json_encode(['*']) : json_encode(array_values($selectedPerms));

                db_query("INSERT INTO admin_users (username, email, password, full_name, role, permissions, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)", [
                    $username, $email, $hash, $fullName, $role, $permsJson, $isActive
                ]);

                set_flash('success', "Admin user '{$username}' created successfully with specified page permissions.");
                header('Location: admin_users.php');
                exit;
            }
        }
    }

    // 2. UPDATE EXISTING ADMIN USER
    if ($action === 'update_admin') {
        $targetId = (int)($_POST['admin_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $selectedPerms = $_POST['permissions'] ?? [];

        $targetUser = db_fetch_one("SELECT * FROM admin_users WHERE id = ?", [$targetId]);
        if (!$targetUser) {
            set_flash('error', 'Target admin user not found.');
        } else {
            // Prevent removing super_admin from user #1 or yourself if only superadmin
            if ($targetId === 1 && $role !== 'super_admin') {
                set_flash('error', 'Primary Superadmin (ID #1) cannot be demoted.');
                $role = 'super_admin';
            }

            // Prevent self-deactivation
            if ($targetId === (int)$admin['id'] && !$isActive) {
                set_flash('error', 'You cannot deactivate your own admin account.');
                $isActive = 1;
            }

            $permsJson = ($role === 'super_admin') ? json_encode(['*']) : json_encode(array_values($selectedPerms));

            if (!empty($password)) {
                if (strlen($password) < 6) {
                    set_flash('error', 'New password must be at least 6 characters.');
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    db_query("UPDATE admin_users SET full_name = ?, email = ?, role = ?, permissions = ?, is_active = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [
                        $fullName, $email, $role, $permsJson, $isActive, $hash, $targetId
                    ]);
                    set_flash('success', "Admin '{$targetUser['username']}' updated successfully with new password and permissions.");
                }
            } else {
                db_query("UPDATE admin_users SET full_name = ?, email = ?, role = ?, permissions = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [
                    $fullName, $email, $role, $permsJson, $isActive, $targetId
                ]);
                set_flash('success', "Admin '{$targetUser['username']}' updated successfully with new permissions.");
            }

            header('Location: admin_users.php');
            exit;
        }
    }

    // 3. TOGGLE ACTIVE STATUS
    if ($action === 'toggle_active') {
        $targetId = (int)($_POST['admin_id'] ?? 0);
        if ($targetId === (int)$admin['id']) {
            set_flash('error', 'You cannot toggle status of your own account.');
        } elseif ($targetId === 1) {
            set_flash('error', 'Primary superadmin cannot be disabled.');
        } else {
            $user = db_fetch_one("SELECT is_active, username FROM admin_users WHERE id = ?", [$targetId]);
            if ($user) {
                $newStatus = $user['is_active'] ? 0 : 1;
                db_query("UPDATE admin_users SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$newStatus, $targetId]);
                set_flash('success', "Account status for '{$user['username']}' updated.");
            }
        }
        header('Location: admin_users.php');
        exit;
    }

    // 4. DELETE ADMIN USER
    if ($action === 'delete_admin') {
        $targetId = (int)($_POST['admin_id'] ?? 0);
        if ($targetId === 1) {
            set_flash('error', 'Primary Superadmin cannot be deleted.');
        } elseif ($targetId === (int)$admin['id']) {
            set_flash('error', 'You cannot delete your own account while logged in.');
        } else {
            $user = db_fetch_one("SELECT username FROM admin_users WHERE id = ?", [$targetId]);
            db_query("DELETE FROM admin_users WHERE id = ?", [$targetId]);
            set_flash('success', "Admin account '{$user['username']}' deleted permanently.");
        }
        header('Location: admin_users.php');
        exit;
    }
}

// Fetch all admin accounts
$adminUsers = db_fetch_all("SELECT * FROM admin_users ORDER BY id ASC");

// Check if editing a specific user
$editUserId = (int)($_GET['edit'] ?? 0);
$editUser = null;
$editUserPerms = [];
if ($editUserId > 0) {
    $editUser = db_fetch_one("SELECT * FROM admin_users WHERE id = ?", [$editUserId]);
    if ($editUser) {
        $rawPerms = $editUser['permissions'] ?? '[]';
        $decoded = json_decode($rawPerms, true);
        $editUserPerms = is_array($decoded) ? $decoded : [];
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
  <div>
    <h1 style="font-size: 1.75rem; font-weight: 800; margin: 0; color: var(--admin-text-main);">
      🛡️ Admin Roles & Page Access Control (RBAC)
    </h1>
    <p style="margin: 0.25rem 0 0 0; color: var(--admin-text-muted); font-size: 0.9rem;">
      Superadmin portal to grant or revoke page-by-page access permissions for staff and managers.
    </p>
  </div>
  <div>
    <a href="#adminUserFormCard" onclick="document.getElementById('adminUserFormCard').scrollIntoView({behavior: 'smooth'})" class="admin-btn admin-btn-primary" style="display: flex; align-items: center; gap: 0.5rem;">
      <span>➕</span> Create New Admin User
    </a>
  </div>
</div>

<!-- Admin Users Directory -->
<div class="admin-card" style="margin-bottom: 2rem;">
  <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700;">Admin & Staff Accounts</h3>
      <span style="font-size: 0.8125rem; color: var(--admin-text-muted);">
        Only permitted sections appear in each staff member's navigation bar.
      </span>
    </div>
    <span class="badge" style="background: #ede9fe; color: #6d28d9; font-weight: 700; padding: 0.35rem 0.75rem; border-radius: 999px;">
      Total Accounts: <?= count($adminUsers) ?>
    </span>
  </div>

  <div class="admin-card-body" style="padding: 0;">
    <div style="overflow-x: auto;">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 50px;">ID</th>
            <th style="width: 220px;">Staff User</th>
            <th style="width: 140px;">Role</th>
            <th>Assigned Page Permissions</th>
            <th style="width: 110px; text-align: center;">Status</th>
            <th style="width: 160px;">Last Login</th>
            <th style="width: 180px; text-align: center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($adminUsers as $u): 
            $uPerms = [];
            if (!empty($u['permissions'])) {
                $dec = json_decode($u['permissions'], true);
                $uPerms = is_array($dec) ? $dec : [];
            }
            $isSuper = ($u['role'] === 'super_admin');
          ?>
            <tr style="<?= !$u['is_active'] ? 'opacity: 0.6; background: #f8fafc;' : '' ?>">
              <td>#<?= $u['id'] ?></td>
              
              <!-- Staff User Info -->
              <td>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <div style="width: 38px; height: 38px; border-radius: 50%; background: <?= $isSuper ? '#fef3c7' : '#e0e7ff' ?>; color: <?= $isSuper ? '#b45309' : '#3730a3' ?>; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.95rem; border: 1px solid <?= $isSuper ? '#fde68a' : '#c7d2fe' ?>;">
                    <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                  </div>
                  <div>
                    <div style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($u['full_name']) ?></div>
                    <div style="font-size: 0.75rem; color: #64748b;">@<?= htmlspecialchars($u['username']) ?> • <?= htmlspecialchars($u['email']) ?></div>
                  </div>
                </div>
              </td>

              <!-- Role Badge -->
              <td>
                <?php if ($isSuper): ?>
                  <span style="display: inline-block; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                    👑 Super Admin
                  </span>
                <?php elseif ($u['role'] === 'admin'): ?>
                  <span style="display: inline-block; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                    👤 Staff Admin
                  </span>
                <?php else: ?>
                  <span style="display: inline-block; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                    ✏️ <?= htmlspecialchars(ucfirst($u['role'])) ?>
                  </span>
                <?php endif; ?>
              </td>

              <!-- Page Permissions -->
              <td>
                <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; max-width: 520px;">
                  <?php if ($isSuper || in_array('*', $uPerms, true)): ?>
                    <span style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 0.75rem; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 700;">
                      ★ Full Unrestricted Access (All Pages)
                    </span>
                  <?php elseif (empty($uPerms)): ?>
                    <span style="color: #94a3b8; font-size: 0.75rem; font-style: italic;">
                      No page permissions assigned
                    </span>
                  <?php else: ?>
                    <?php foreach ($uPerms as $p): ?>
                      <?php if (isset($allPermissions[$p])): ?>
                        <span style="background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; font-size: 0.7125rem; padding: 0.15rem 0.45rem; border-radius: 4px; font-weight: 600;">
                          <?= $allPermissions[$p]['icon'] ?> <?= $allPermissions[$p]['title'] ?>
                        </span>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Status -->
              <td style="text-align: center;">
                <?php if ($u['is_active']): ?>
                  <span style="display: inline-block; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: #dcfce7; color: #166534;">
                    Active
                  </span>
                <?php else: ?>
                  <span style="display: inline-block; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: #fee2e2; color: #991b1b;">
                    Inactive
                  </span>
                <?php endif; ?>
              </td>

              <!-- Last Login -->
              <td style="font-size: 0.8125rem; color: #64748b;">
                <?= $u['last_login'] ? date('M j, Y g:i A', strtotime($u['last_login'])) : 'Never' ?>
              </td>

              <!-- Actions -->
              <td style="text-align: center;">
                <div style="display: flex; gap: 0.35rem; justify-content: center; align-items: center;">
                  <!-- Edit Button -->
                  <a href="admin_users.php?edit=<?= $u['id'] ?>#adminUserFormCard" class="admin-btn admin-btn-secondary admin-btn-sm" style="font-size: 0.75rem;" title="Edit Account & Permissions">
                    ✏️ Edit
                  </a>

                  <!-- Toggle Active (except ID 1 and self) -->
                  <?php if ($u['id'] !== 1 && $u['id'] !== (int)$admin['id']): ?>
                    <button type="button" onclick="toggleUserStatus(<?= $u['id'] ?>)" class="admin-btn admin-btn-sm <?= $u['is_active'] ? 'admin-btn-secondary' : 'admin-btn-primary' ?>" style="font-size: 0.75rem;">
                      <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
                    </button>
                  <?php endif; ?>

                  <!-- Delete (except ID 1 and self) -->
                  <?php if ($u['id'] !== 1 && $u['id'] !== (int)$admin['id']): ?>
                    <button type="button" onclick="confirmDeleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')" class="admin-btn admin-btn-danger admin-btn-sm" style="font-size: 0.75rem;" title="Delete User">
                      🗑️
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add or Edit Admin Account Form Card -->
<div class="admin-card" id="adminUserFormCard" style="border: 2px solid <?= $editUser ? '#8b5cf6' : '#e2e8f0' ?>;">
  <div class="admin-card-header" style="background: <?= $editUser ? '#f5f3ff' : '#ffffff' ?>; display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: <?= $editUser ? '#6d28d9' : '#0f172a' ?>;">
        <?= $editUser ? '✏️ Edit Admin Account & Access: @' . htmlspecialchars($editUser['username']) : '➕ Create New Staff Admin Account' ?>
      </h3>
      <span style="font-size: 0.8125rem; color: var(--admin-text-muted);">
        <?= $editUser ? 'Modify profile info, reset password, or update page-level permissions.' : 'Add a new member to the admin team and choose which pages they can access.' ?>
      </span>
    </div>
    <?php if ($editUser): ?>
      <a href="admin_users.php" class="admin-btn admin-btn-secondary admin-btn-sm">Cancel Edit</a>
    <?php endif; ?>
  </div>

  <div class="admin-card-body">
    <form method="POST" action="admin_users.php">
      <input type="hidden" name="action" value="<?= $editUser ? 'update_admin' : 'create_admin' ?>">
      <?php if ($editUser): ?>
        <input type="hidden" name="admin_id" value="<?= $editUser['id'] ?>">
      <?php endif; ?>

      <!-- Account Credentials Section -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
        
        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" style="font-weight: 700;">Full Name *</label>
          <input type="text" name="full_name" class="form-input" required placeholder="e.g. Ramesh Patel" value="<?= htmlspecialchars($editUser['full_name'] ?? '') ?>">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" style="font-weight: 700;">Username *</label>
          <input type="text" name="username" class="form-input" required placeholder="e.g. ramesh_patel" <?= $editUser ? 'readonly style="background: #f1f5f9; cursor: not-allowed;"' : '' ?> value="<?= htmlspecialchars($editUser['username'] ?? '') ?>">
          <?php if ($editUser): ?><span style="font-size: 0.7rem; color: #64748b;">Username cannot be changed.</span><?php endif; ?>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" style="font-weight: 700;">Email Address *</label>
          <input type="email" name="email" class="form-input" required placeholder="e.g. ramesh@sskstore.in" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" style="font-weight: 700;">Password <?= $editUser ? '(Leave blank to keep current)' : '*' ?></label>
          <input type="password" name="password" class="form-input" <?= $editUser ? '' : 'required' ?> placeholder="<?= $editUser ? '••••••••' : 'Minimum 6 chars' ?>">
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label" style="font-weight: 700;">Admin Role *</label>
          <select name="role" id="roleSelect" class="form-input" onchange="handleRoleChange(this.value)">
            <option value="admin" <?= (($editUser['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Staff Admin (Custom Page Access)</option>
            <option value="editor" <?= (($editUser['role'] ?? '') === 'editor') ? 'selected' : '' ?>>Editor (Content & Catalog)</option>
            <option value="super_admin" <?= (($editUser['role'] ?? '') === 'super_admin') ? 'selected' : '' ?>>👑 Super Admin (Full Access to All Pages)</option>
          </select>
        </div>

        <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1.5rem;">
          <input type="checkbox" id="user_is_active" name="is_active" value="1" <?= (!$editUser || !empty($editUser['is_active'])) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
          <label for="user_is_active" style="font-weight: 700; font-size: 0.875rem; cursor: pointer;">
            Account Active
          </label>
        </div>

      </div>

      <!-- Page Permissions Checklist Box -->
      <div id="permissionsBox" style="background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
          <div>
            <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: var(--heading);">
              📋 Assign Page-Level Access Permissions
            </h4>
            <span style="font-size: 0.8125rem; color: var(--text-muted);">
              Check the pages this staff member is authorized to see and operate.
            </span>
          </div>
          <div style="display: flex; gap: 0.5rem;">
            <button type="button" onclick="toggleAllPermissions(true)" class="admin-btn admin-btn-secondary admin-btn-sm" style="font-size: 0.75rem;">
              Select All
            </button>
            <button type="button" onclick="toggleAllPermissions(false)" class="admin-btn admin-btn-secondary admin-btn-sm" style="font-size: 0.75rem;">
              Deselect All
            </button>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.85rem;">
          <?php foreach ($allPermissions as $permKey => $permInfo): 
            $isAssigned = false;
            if ($editUser) {
                $isAssigned = in_array('*', $editUserPerms, true) || in_array($permKey, $editUserPerms, true);
            }
          ?>
            <label style="display: flex; align-items: flex-start; gap: 0.65rem; background: var(--surface); border: 1px solid var(--border); border-radius: 6px; padding: 0.75rem 0.85rem; cursor: pointer; transition: all 0.2s ease;">
              <input type="checkbox" name="permissions[]" value="<?= $permKey ?>" class="perm-checkbox" <?= $isAssigned ? 'checked' : '' ?> style="margin-top: 0.2rem; width: 17px; height: 17px; accent-color: var(--red);">
              <div>
                <div style="font-weight: 700; font-size: 0.875rem; color: var(--heading); display: flex; align-items: center; gap: 0.35rem;">
                  <span><?= $permInfo['icon'] ?></span> <?= htmlspecialchars($permInfo['title']) ?>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.35; margin-top: 0.15rem;">
                  <?= htmlspecialchars($permInfo['desc']) ?>
                </div>
                <div style="font-size: 0.6875rem; color: var(--text-muted); font-family: monospace; margin-top: 0.2rem;">
                  <?= $permInfo['file'] ?>
                </div>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
        <?php if ($editUser): ?>
          <a href="admin_users.php" class="admin-btn admin-btn-secondary">Cancel</a>
          <button type="submit" class="admin-btn admin-btn-primary">
            💾 Save User & Permissions
          </button>
        <?php else: ?>
          <button type="submit" class="admin-btn admin-btn-primary">
            ➕ Create Admin Account
          </button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Action Form for Toggle / Delete -->
<form id="userActionForm" method="POST" action="admin_users.php" style="display: none;">
  <input type="hidden" name="action" id="userActionField">
  <input type="hidden" name="admin_id" id="userIdField">
</form>

<script>
function toggleAllPermissions(selectAll) {
  document.querySelectorAll('.perm-checkbox').forEach(cb => {
    cb.checked = selectAll;
  });
}

function handleRoleChange(role) {
  const permBox = document.getElementById('permissionsBox');
  if (role === 'super_admin') {
    toggleAllPermissions(true);
  }
}

function toggleUserStatus(id) {
  document.getElementById('userActionField').value = 'toggle_active';
  document.getElementById('userIdField').value = id;
  document.getElementById('userActionForm').submit();
}

function confirmDeleteUser(id, username) {
  if (confirm('Are you sure you want to permanently delete admin account "@' + username + '"?')) {
    document.getElementById('userActionField').value = 'delete_admin';
    document.getElementById('userIdField').value = id;
    document.getElementById('userActionForm').submit();
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
