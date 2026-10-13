<?php
/**
 * SSK Store - Custom 404 Not Found Page
 * Shree Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

http_response_code(404);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Page Not Found (404) — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="max-width: 680px; margin-top: 3rem; margin-bottom: 5rem;">
  
  <?= render_breadcrumb([
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => '404 Page Not Found']
  ]) ?>

  <div class="ui-card" style="padding: 4.5rem 2rem; text-align: center;">
    <div style="font-size: 4rem; line-height: 1; margin-bottom: 1rem;">📿</div>
    
    <span style="display: inline-block; background: var(--stat-amber-bg); color: var(--stat-amber); padding: 0.3rem 0.85rem; border-radius: var(--radius-full); font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; margin-bottom: 1rem; border: 1px solid var(--stat-amber);">
      404 Not Found
    </span>

    <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--heading); margin: 0 0 0.75rem;">Page or Item Not Found</h1>
    <p style="color: var(--text-muted); font-size: 1rem; line-height: 1.6; max-width: 480px; margin: 0 auto 2rem;">
      The sacred publication or page you are looking for may have been moved, renamed, or is temporarily unavailable.
    </p>

    <!-- Search box -->
    <form action="catalog.php" method="GET" style="max-width: 420px; margin: 0 auto 2rem; display: flex; gap: 8px;">
      <input type="text" name="q" class="form-input" placeholder="Search scriptures, kanthis, murtis..." style="flex: 1;" required>
      <button type="submit" class="btn btn-primary" style="min-width: 90px;">Search</button>
    </form>

    <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
      <a href="index.php" class="btn btn-secondary btn-lg">&larr; Return to Homepage</a>
      <a href="catalog.php" class="btn btn-primary btn-lg">Browse Catalog &rarr;</a>
    </div>
  </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
