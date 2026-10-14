<?php
/**
 * SSK Store - CMS Page Renderer & Contact Form
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$slug = $_GET['slug'] ?? 'about-us';
$page = db_fetch_one("SELECT * FROM pages WHERE slug = ? AND is_active = 1", [$slug]);

if (!$page) {
    header('Location: index.php');
    exit;
}

$contactSuccess = false;
$contactError = '';

// Handle Contact Message POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $slug === 'contact-us') {
    require_csrf();

    // Honeypot check (Bots fill out hidden fields)
    if (!empty($_POST['website_url_hp'])) {
        $contactSuccess = true;
    } elseif (!check_rate_limit('contact_form', 3, 300)) {
        $contactError = 'Too many messages sent. Please wait 5 minutes before submitting another inquiry.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        // Clean phone
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) === 12 && substr($cleanPhone, 0, 2) === '91') {
            $cleanPhone = substr($cleanPhone, 2);
        }

        if (empty($name) || empty($email) || empty($message)) {
            $contactError = 'Please provide all required fields: name, email, and your message.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $contactError = 'Please enter a valid email address.';
        } else {
            db_query("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)", [
                $name, $email, $cleanPhone, $subject, $message
            ]);
            $contactSuccess = true;
        }
    }
}

$pageTitle = htmlspecialchars($page['title']) . ' — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="max-width: 920px; margin-top: 1.5rem; margin-bottom: 4.5rem;">
  
  <?= render_breadcrumb([
    ['label' => 'Home', 'url' => 'index.php'],
    ['label' => $page['title']]
  ]) ?>

  <article class="ui-card" style="padding: 2.5rem 2rem; line-height: 1.8;">
    
    <div style="margin-bottom: 2rem; border-bottom: 1.5px solid var(--card-border); padding-bottom: 1.25rem;">
      <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--heading); margin: 0 0 0.4rem;"><?= htmlspecialchars($page['title']) ?></h1>
      <span style="font-size: 0.8125rem; color: var(--gold); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Shree Swaminarayan Mandir, Kalakunj • Sahitya Kendra</span>
    </div>

    <!-- CMS HTML Content -->
    <div style="color: var(--text); font-size: 1rem; line-height: 1.8;">
      <?= strip_tags((string)$page['content'], '<h2><h3><h4><p><strong><b><em><i><u><ul><ol><li><a><br><hr>') ?>
    </div>

    <!-- Contact Form (if Contact Us page) -->
    <?php if ($slug === 'contact-us'): ?>
      <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1.5px solid var(--card-border);">
        <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--heading); margin: 0 0 0.5rem;">Send Us a Message</h2>
        <p style="color: var(--text-muted); font-size: 0.9375rem; margin: 0 0 1.5rem;">Have questions regarding publications, bulk temple orders, or tracking? Reach out below.</p>

        <?php if ($contactSuccess): ?>
          <div style="background: rgba(16, 185, 129, 0.12); color: var(--success); padding: 1.25rem; border-radius: 12px; font-weight: 600; margin-bottom: 1.5rem; border: 1.5px solid var(--success); display: flex; align-items: center; gap: 10px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <div>Jai Swaminarayan! Your inquiry has been delivered to our administrative seva team. We will respond shortly.</div>
          </div>
        <?php elseif ($contactError): ?>
          <div style="background: rgba(239, 68, 68, 0.12); color: var(--danger); padding: 1.25rem; border-radius: 12px; font-weight: 600; margin-bottom: 1.5rem; border: 1.5px solid var(--danger);">
            <?= htmlspecialchars($contactError) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="pages.php?slug=contact-us">
          <?= csrf_field() ?>
          <div style="display: none;" aria-hidden="true">
            <input type="text" name="website_url_hp" tabindex="-1" autocomplete="off">
          </div>

          <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
            Fields marked with <span style="color: var(--red-text); font-weight: bold;">*</span> are required.
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label form-label-required" for="cnt-name">Your Full Name</label>
              <input type="text" id="cnt-name" name="name" class="form-input" placeholder="e.g. Ramesh Patel" autocomplete="name" required>
            </div>
            <div class="form-group">
              <label class="form-label form-label-required" for="cnt-email">Email Address</label>
              <input type="email" id="cnt-email" name="email" class="form-input" placeholder="name@example.com" autocomplete="email" required>
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label" for="cnt-phone">Mobile Phone <span class="form-label-optional">(Optional)</span></label>
              <div class="input-phone-wrap">
                <div class="phone-prefix-chip">+91</div>
                <input type="tel" id="cnt-phone" name="phone" class="form-input" placeholder="9876543210" pattern="[0-9]{10}" inputmode="numeric" maxlength="10">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label" for="cnt-subj">Subject <span class="form-label-optional">(Optional)</span></label>
              <input type="text" id="cnt-subj" name="subject" class="form-input" placeholder="Order inquiry / Scripture guidance">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label form-label-required" for="cnt-msg">Message</label>
            <textarea id="cnt-msg" name="message" class="form-textarea" rows="5" placeholder="Write your devotional inquiry or question here..." required></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="min-width: 220px;">
            Send Inquiry &rarr;
          </button>
        </form>
      </div>
    <?php endif; ?>

  </article>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
