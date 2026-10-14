<?php
/**
 * SSK Store - Transactional Mail Delivery Engine
 * Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)
 *
 * Implements defensive transactional email with local dev logging fallback (Finding P1-5a).
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Sends an email or logs to logs/mail.log if in dev / unconfigured environment.
 *
 * @param string $toEmail
 * @param string $toName
 * @param string $subject
 * @param string $htmlBody
 * @param string $textBody
 * @return bool
 */
function send_app_mail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool {
    // 1. CRLF Injection Defense
    $toEmail = str_replace(["\r", "\n"], '', trim($toEmail));
    $toName  = str_replace(["\r", "\n"], '', trim($toName));
    $subject = str_replace(["\r", "\n"], '', trim($subject));

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("[SSK Mail Error] Invalid recipient email address: '$toEmail'");
        return false;
    }

    $fromEmail = getenv('MAIL_FROM_ADDRESS') ?: 'contact@sskstore.in';
    $fromName  = getenv('MAIL_FROM_NAME') ?: 'SSK Store (Kalakunj Mandir, Surat)';

    // 2. Logging to logs/mail.log (Dev / Audit trail)
    $logDir = ROOT_PATH . 'logs/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0750, true);
    }

    $logEntry = sprintf(
        "============================================================\n" .
        "TIME:    %s\n" .
        "TO:      %s <%s>\n" .
        "FROM:    %s <%s>\n" .
        "SUBJECT: %s\n" .
        "------------------------------------------------------------\n" .
        "%s\n" .
        "============================================================\n\n",
        date('Y-m-d H:i:s T'),
        $toName ?: 'Devotee',
        $toEmail,
        $fromName,
        $fromEmail,
        $subject,
        !empty($textBody) ? $textBody : strip_tags(str_replace(['<br>', '<p>'], ["\n", "\n\n"], $htmlBody))
    );

    file_put_contents($logDir . 'mail.log', $logEntry, FILE_APPEND | LOCK_EX);

    // 3. Attempt live transmission if SMTP or PHP mail is configured
    $smtpHost = getenv('SMTP_HOST');
    if (IS_PRODUCTION && !empty($smtpHost)) {
        // Shared-hosting standard PHP mail() with strict headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($fromName), $fromEmail),
            'Reply-To: ' . $fromEmail,
            'X-Mailer: SSK-Store-Mailer/1.0'
        ];

        $encodedSubject = sprintf('=?UTF-8?B?%s?=', base64_encode($subject));
        $toHeader = sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($toName ?: 'Devotee'), $toEmail);

        return @mail($toHeader, $encodedSubject, $htmlBody, implode("\r\n", $headers));
    }

    // In local / development mode, logging to mail.log counts as successful dispatch
    return true;
}

/**
 * Sends order placement confirmation to the customer.
 */
function send_order_confirmation_mail(array $order, array $items): bool {
    $toEmail = $order['shipping_email'] ?? $order['customer_email'] ?? '';
    $toName  = $order['shipping_name'] ?? 'Devotee';
    $orderNumber = $order['order_number'];

    if (empty($toEmail)) {
        return false;
    }

    $subject = "Order Confirmed: #{$orderNumber} — SSK Store";

    $itemsHtml = '';
    foreach ($items as $item) {
        $itemsHtml .= sprintf(
            "<tr>
                <td style='padding: 8px; border-bottom: 1px solid #e2e8f0;'>%s</td>
                <td style='padding: 8px; border-bottom: 1px solid #e2e8f0; text-align: center;'>%d</td>
                <td style='padding: 8px; border-bottom: 1px solid #e2e8f0; text-align: right;'>%s</td>
            </tr>",
            htmlspecialchars($item['product_name']),
            (int)$item['quantity'],
            format_price((float)$item['price'] * (int)$item['quantity'])
        );
    }

    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b;'>
        <div style='background: #011760; color: #ffffff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
            <h1 style='margin: 0; font-size: 22px;'>Jay Swaminarayan 🙏</h1>
            <p style='margin: 5px 0 0 0; font-size: 14px;'>Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)</p>
        </div>
        <div style='padding: 24px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 8px 8px;'>
            <h2 style='color: #011760; font-size: 18px; margin-top: 0;'>Order Confirmation: #{$orderNumber}</h2>
            <p>Dear " . htmlspecialchars($toName) . ",</p>
            <p>Thank you for ordering with SSK Store. Your order has been recorded and will be prepared by the temple publication seva team.</p>
            
            <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                <thead>
                    <tr style='background: #f8fafc;'>
                        <th style='padding: 8px; text-align: left; border-bottom: 2px solid #cbd5e1;'>Item</th>
                        <th style='padding: 8px; text-align: center; border-bottom: 2px solid #cbd5e1;'>Qty</th>
                        <th style='padding: 8px; text-align: right; border-bottom: 2px solid #cbd5e1;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$itemsHtml}
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan='2' style='padding: 8px; text-align: right; font-weight: bold;'>Total:</td>
                        <td style='padding: 8px; text-align: right; font-weight: bold; color: #E70207;'>" . format_price((float)$order['total']) . "</td>
                    </tr>
                </tfoot>
            </table>

            <p style='font-size: 13px; color: #64748b;'>
                Delivery Address: " . htmlspecialchars($order['shipping_address'] ?? '') . ", " . htmlspecialchars($order['shipping_city'] ?? '') . " " . htmlspecialchars($order['shipping_pincode'] ?? '') . "<br>
                Payment Method: " . strtoupper(htmlspecialchars($order['payment_method'] ?? 'COD')) . "
            </p>
            <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
            <p style='font-size: 12px; color: #94a3b8; text-align: center; margin: 0;'>
                Shree Swaminarayan Mandir, Kalakunj, Varachha Main Road, Surat, Gujarat 395006<br>
                Helpline / WhatsApp: +91 9426-55-1098
            </p>
        </div>
    </div>";

    return send_app_mail($toEmail, $toName, $subject, $body);
}

/**
 * Sends order fulfillment / status change alert to customer.
 */
function send_order_status_update_mail(array $order, string $newStatus): bool {
    $toEmail = $order['shipping_email'] ?? $order['customer_email'] ?? '';
    $toName  = $order['shipping_name'] ?? 'Devotee';
    $orderNumber = $order['order_number'];

    if (empty($toEmail)) {
        return false;
    }

    $subject = "Order #{$orderNumber} Status: " . ucfirst($newStatus) . " — SSK Store";

    $statusDescriptions = [
        'processing' => 'Your order is currently being sanctified, verified, and packaged at Kalakunj Mandir.',
        'shipped'    => 'Your order has been handed over to our dispatch courier partner for express delivery.',
        'delivered'  => 'Your package has been successfully delivered. May these sacred items bring spiritual peace.',
        'cancelled'  => 'Your order has been cancelled per request. If paid via UPI, verification/refund will proceed.',
    ];
    $desc = $statusDescriptions[$newStatus] ?? "Your order status is now: " . ucfirst($newStatus);

    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b;'>
        <div style='background: #011760; color: #ffffff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
            <h1 style='margin: 0; font-size: 22px;'>Jay Swaminarayan 🙏</h1>
        </div>
        <div style='padding: 24px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 8px 8px;'>
            <h2 style='color: #011760; font-size: 18px; margin-top: 0;'>Order #{$orderNumber} is now " . ucfirst($newStatus) . "</h2>
            <p>Dear " . htmlspecialchars($toName) . ",</p>
            <p>{$desc}</p>
            <p>You can review order details anytime in your account at <a href='" . SITE_URL . "/account.php'>SSK Store Portal</a>.</p>
        </div>
    </div>";

    return send_app_mail($toEmail, $toName, $subject, $body);
}
