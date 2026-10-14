<?php
/**
 * BAPS Store - Admin Customers Directory
 */

$adminPageTitle = 'Customer Accounts';
require_once __DIR__ . '/includes/header.php';

$customers = db_fetch_all("SELECT c.*, COUNT(o.id) as total_orders, COALESCE(SUM(o.total), 0) as total_spent 
                           FROM customers c 
                           LEFT JOIN orders o ON c.id = o.customer_id 
                           GROUP BY c.id 
                           ORDER BY c.id DESC");
?>

<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h3 style="font-size: 1.25rem; font-weight: 700;">Registered Customers</h3>
    <span style="font-size: 0.875rem; color: var(--admin-text-muted);"><?= count($customers) ?> total users</span>
  </div>

  <div class="table-responsive" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Customer Name</th>
          <th>Email Address</th>
          <th>Phone</th>
          <th>Total Orders</th>
          <th>Total Spend</th>
          <th>Joined Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($customers as $cust): ?>
          <tr>
            <td>
              <strong style="color: var(--admin-text);"><?= htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']) ?></strong>
            </td>
            <td><?= htmlspecialchars($cust['email']) ?></td>
            <td><?= htmlspecialchars($cust['phone'] ?? 'N/A') ?></td>
            <td><?= $cust['total_orders'] ?> orders</td>
            <td style="font-weight: 700; color: var(--admin-primary);"><?= format_price($cust['total_spent']) ?></td>
            <td style="font-size: 0.8125rem; color: var(--admin-text-muted);"><?= date('d M Y', strtotime($cust['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
