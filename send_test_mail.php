<?php
// ── Auth & DB ────────────────────────────────────────────────────────────────
include("include/auth.php");
include("include/mail_helper.php");

header('Content-Type: application/json');

// ── Only accept POST + AJAX ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'send_test') {
    echo json_encode(['success' => false, 'title' => 'Invalid Request', 'message' => 'Bad request method.']);
    exit;
}

$recipient = filter_var(trim($_POST['recipient'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$recipient) {
    echo json_encode(['success' => false, 'title' => 'Invalid Email', 'message' => 'Please enter a valid recipient email address.']);
    exit;
}

// compose test email
$site_name = !empty($web_settings['site_title']) ? htmlspecialchars($web_settings['site_title']) : 'ByteBazaar';
$sent_time = date('D, d M Y H:i:s T');
$subject = '✅ Test Email — Mail Server Working!';

$html_body = "
<!DOCTYPE html><html><head><meta charset='UTF-8'><style>
    body { margin:0; padding:0; font-family:'Segoe UI',Arial,sans-serif; background:#f1f5f9; }
    .wrap { max-width:600px; margin:40px auto; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,.1); }
    .header { background:linear-gradient(135deg,#088178,#05635d); padding:40px 30px; text-align:center; color:#fff; }
    .header .icon { font-size:48px; margin-bottom:16px; }
    .header h1 { margin:0; font-size:24px; font-weight:700; }
    .body { padding:36px 32px; }
    .status-box { display:flex;align-items:center;gap:14px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:16px 20px; margin-bottom:24px; }
    .status-box .dot { width:14px;height:14px;border-radius:50%;background:#10b981;flex-shrink:0; }
    .status-box p { margin:0; color:#065f46; font-weight:600; font-size:15px; }
    .footer { background:#f8fafc; padding:20px; text-align:center; font-size:12px; color:#94a3b8; border-top:1px solid #e2e8f0; }
    .badge { display:inline-block;background:#088178;color:#fff;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700; }
</style></head><body>
<div class='wrap'><div class='header'><div class='icon'>📧</div><h1>Mail Server Check!</h1></div><div class='body'>
<div class='status-box'><div class='dot'></div><p>✅ Connection to SMTP server is confirmed.</p></div>
<p>If you see this email, your configuration at <strong>$site_name</strong> is correct. Sent at: <strong>$sent_time</strong></p>
</div><div class='footer'><span class='badge'>$site_name</span></div></div></body></html>";

$alt_body = "Your SMTP configuration is working correctly. Time: $sent_time";

$res = sendCustomEmail($recipient, 'Admin Staff', $subject, $html_body, $alt_body);

if ($res['success']) {
    echo json_encode([
        'success' => true,
        'title' => '✅ Test Email Sent!',
        'message' => "Email successfully delivered to {$recipient}. Please check your inbox."
    ]);
}
else {
    echo json_encode([
        'success' => false,
        'title' => '❌ Send Failed',
        'message' => $res['message']
    ]);
}
