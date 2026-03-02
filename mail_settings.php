<?php
include("include/auth.php");

// ─── Create mail_settings table if not exists ───────────────────────────────
$con->query("CREATE TABLE IF NOT EXISTS `mail_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `mail_driver` varchar(20) DEFAULT 'smtp',
    `mail_host` varchar(255) DEFAULT 'smtp.gmail.com',
    `mail_port` int(5) DEFAULT 587,
    `mail_encryption` varchar(10) DEFAULT 'tls',
    `mail_username` varchar(255) DEFAULT '',
    `mail_password` varchar(500) DEFAULT '',
    `mail_from_address` varchar(255) DEFAULT '',
    `mail_from_name` varchar(255) DEFAULT '',
    `mail_enabled` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure one row exists
$chk = $con->query("SELECT * FROM mail_settings LIMIT 1");
if ($chk->num_rows == 0) {
    $con->query("INSERT INTO mail_settings (mail_driver, mail_host, mail_port, mail_encryption, mail_username, mail_password, mail_from_address, mail_from_name, mail_enabled)
                 VALUES ('smtp','smtp.gmail.com',587,'tls','','','','',0)");
}

$msg = $error = '';

// ─── Handle Save ────────────────────────────────────────────────────────────
if (isset($_POST['save_mail_settings'])) {
    $driver = $con->real_escape_string($_POST['mail_driver']);
    $host = $con->real_escape_string($_POST['mail_host']);
    $port = (int)$_POST['mail_port'];
    $encryption = $con->real_escape_string($_POST['mail_encryption']);
    $username = $con->real_escape_string($_POST['mail_username']);
    $password = $con->real_escape_string($_POST['mail_password']);
    $from_addr = $con->real_escape_string($_POST['mail_from_address']);
    $from_name = $con->real_escape_string($_POST['mail_from_name']);
    $enabled = isset($_POST['mail_enabled']) ? 1 : 0;

    $sql = "UPDATE mail_settings SET mail_driver=?, mail_host=?, mail_port=?, mail_encryption=?,
             mail_username=?, mail_password=?, mail_from_address=?, mail_from_name=?, mail_enabled=? WHERE id=1";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ssisssssi", $driver, $host, $port, $encryption, $username, $password, $from_addr, $from_name, $enabled);

    if ($stmt->execute()) {
        $msg = "✅ Mail server settings saved successfully!";
    }
    else {
        $error = "❌ Failed to save settings: " . $con->error;
    }
}

$mail = $con->query("SELECT * FROM mail_settings LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Server Settings | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <link rel="icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/logo.png'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; }

        .page-wrapper {
            max-width: 960px;
            margin: 0 auto;
            padding: 10px 20px 40px;
        }

        /* ── Alert Banners ── */
        .mail-alert {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 500;
            animation: slideDown .4s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .mail-alert.success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .mail-alert.danger  { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .mail-alert i { font-size: 18px; }

        /* ── Cards ── */
        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,.07);
            margin-bottom: 24px;
            overflow: hidden;
        }
        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 28px;
            background: linear-gradient(135deg, #088178 0%, #05635d 100%);
            color: #fff;
        }
        .card-header .icon-wrap {
            width: 42px; height: 42px;
            border-radius: 10px;
            background: rgba(255,255,255,.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }
        .card-header h2 { margin: 0; font-size: 17px; font-weight: 600; }
        .card-header p  { margin: 2px 0 0; font-size: 12px; opacity: .8; }
        .card-body { padding: 28px; }

        /* ── Status Badge ── */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all .3s;
        }
        .status-pill.enabled  { background: #d1fae5; color: #065f46; }
        .status-pill.disabled { background: #f1f5f9; color: #64748b; }
        .status-pill .dot { width: 8px; height: 8px; border-radius: 50%; }
        .status-pill.enabled  .dot { background: #10b981; animation: pulse 1.5s infinite; }
        .status-pill.disabled .dot { background: #94a3b8; }
        @keyframes pulse {
            0%,100% { opacity:1; }
            50%      { opacity:.4; }
        }

        /* ── Toggle Switch ── */
        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px;
            background: #f8fafc;
            border-radius: 10px;
            margin-bottom: 28px;
            border: 1px solid #e2e8f0;
        }
        .toggle-row .toggle-info h4 { margin: 0; font-size: 15px; color: #1e293b; }
        .toggle-row .toggle-info p  { margin: 4px 0 0; font-size: 12px; color: #64748b; }
        .toggle-switch { position: relative; width: 52px; height: 28px; flex-shrink: 0; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; inset: 0;
            background: #cbd5e1;
            border-radius: 28px;
            cursor: pointer;
            transition: .4s;
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 20px; height: 20px;
            left: 4px; top: 4px;
            border-radius: 50%;
            background: white;
            transition: .4s;
            box-shadow: 0 2px 4px rgba(0,0,0,.2);
        }
        .toggle-switch input:checked + .toggle-slider { background: #088178; }
        .toggle-switch input:checked + .toggle-slider::before { transform: translateX(24px); }

        /* ── Form Fields ── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1/-1; }
        .form-group label {
            font-size: 13px; font-weight: 600;
            color: #374151; display: flex; align-items: center; gap: 6px;
        }
        .form-group label i { color: #088178; width: 14px; }
        .form-group input,
        .form-group select {
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            background: #fff;
            transition: border-color .25s, box-shadow .25s;
            outline: none;
            width: 100%;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #088178;
            box-shadow: 0 0 0 3px rgba(8,129,120,.12);
        }
        .form-group .hint {
            font-size: 11.5px; color: #94a3b8;
            display: flex; align-items: center; gap: 5px;
        }
        .password-wrap { position: relative; }
        .password-wrap input { padding-right: 42px; }
        .password-toggle {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #94a3b8; font-size: 15px;
            padding: 0; transition: color .2s;
        }
        .password-toggle:hover { color: #088178; }

        /* ── Encryption Chips ── */
        .encryption-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .enc-chip input[type="radio"] { display: none; }
        .enc-chip label {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 8px; cursor: pointer;
            border: 1.5px solid #e2e8f0;
            font-size: 13px; font-weight: 500; color: #64748b;
            transition: all .25s;
        }
        .enc-chip input:checked + label {
            border-color: #088178;
            background: #f0fdf4;
            color: #088178;
        }
        .enc-chip label:hover { border-color: #088178; }

        /* ── Preset Buttons ── */
        .preset-row {
            display: flex; gap: 10px; flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .preset-btn {
            padding: 8px 18px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            cursor: pointer;
            font-size: 13px; font-weight: 500; color: #475569;
            display: flex; align-items: center; gap: 8px;
            transition: all .25s;
        }
        .preset-btn:hover { border-color: #088178; color: #088178; background: #f0fdf4; }
        .preset-btn img { height: 18px; }

        /* ── Section Divider ── */
        .section-title {
            display: flex; align-items: center; gap: 10px;
            font-size: 13px; font-weight: 700; color: #088178;
            text-transform: uppercase; letter-spacing: .6px;
            margin: 24px 0 18px;
        }
        .section-title::after {
            content: '';
            flex: 1; height: 1px; background: #e2e8f0;
        }

        /* ── Action Buttons ── */
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px;
            border-radius: 10px; border: none; cursor: pointer;
            font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #088178, #05635d);
            color: #fff;
            transition: all .3s;
            box-shadow: 0 4px 12px rgba(8,129,120,.35);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(8,129,120,.4);
        }
        .btn-primary:active { transform: translateY(0); }
        .form-actions { display: flex; justify-content: flex-end; margin-top: 28px; }

        /* ── Test Mail Panel ── */
        .test-panel {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 14px;
            padding: 28px;
            color: #fff;
            margin-bottom: 24px;
        }
        .test-panel h3 { margin: 0 0 6px; font-size: 16px; display: flex; align-items: center; gap: 10px; }
        .test-panel p  { margin: 0 0 22px; font-size: 13px; color: #94a3b8; }
        .test-panel .test-form-grid { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: end; }
        .test-panel .test-input {
            display: flex; flex-direction: column; gap: 6px;
        }
        .test-panel .test-input label { font-size: 13px; color: #cbd5e1; font-weight: 500; }
        .test-panel .test-input input {
            padding: 11px 16px;
            border-radius: 8px; border: 1.5px solid #334155;
            background: rgba(255,255,255,.08);
            color: #fff; font-size: 14px; font-family: 'Inter', sans-serif;
            outline: none; transition: border-color .25s;
        }
        .test-panel .test-input input::placeholder { color: #64748b; }
        .test-panel .test-input input:focus { border-color: #10b981; }
        .btn-test {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px;
            border-radius: 10px; border: none; cursor: pointer;
            font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff; white-space: nowrap;
            transition: all .3s;
            box-shadow: 0 4px 12px rgba(16,185,129,.35);
        }
        .btn-test:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(16,185,129,.4); }
        .btn-test:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        /* ── Test Result Box ── */
        .test-result {
            margin-top: 18px;
            border-radius: 10px;
            padding: 14px 18px;
            font-size: 13px;
            font-weight: 500;
            display: none;
            align-items: flex-start;
            gap: 12px;
        }
        .test-result.show  { display: flex; animation: slideDown .4s ease; }
        .test-result.ok    { background: rgba(16,185,129,.15); border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; }
        .test-result.fail  { background: rgba(239,68,68,.15);  border: 1px solid rgba(239,68,68,.3);  color: #fca5a5; }
        .test-result .ri   { font-size: 20px; flex-shrink: 0; }
        .test-result .rd   { line-height: 1.5; }
        .test-result .rd strong { display: block; margin-bottom: 4px; font-size: 14px; }

        /* ── Info Box ── */
        .info-box {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 10px;
            padding: 16px 20px;
            font-size: 13px; color: #92400e;
            display: flex; gap: 12px; align-items: flex-start;
            margin-bottom: 20px;
        }
        .info-box i { font-size: 18px; color: #f59e0b; flex-shrink: 0; margin-top: 1px; }
        .info-box a { color: #b45309; font-weight: 600; }

        @media (max-width: 640px) {
            .form-grid { grid-template-columns: 1fr; }
            .test-panel .test-form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include('admin_sidebar.php'); ?>

<div class="main-content">
    <div class="header">
        <h1><i class="fas fa-envelope-open-text"></i> Mail Server Configuration</h1>
        <div class="user-info">
            <span><?php echo (string)($display_admin_name ?? ''); ?></span>
            <img src="<?php echo $admin_img; ?>" alt="Admin" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
        </div>
    </div>

    <div class="page-wrapper">

        <?php if ($msg): ?>
            <div class="mail-alert success"><i class="fas fa-circle-check"></i> <?php echo $msg; ?></div>
        <?php
endif; ?>
        <?php if ($error): ?>
            <div class="mail-alert danger"><i class="fas fa-circle-xmark"></i> <?php echo $error; ?></div>
        <?php
endif; ?>

        <!-- ── Gmail App Password Notice ─────────────────────────────────── -->
        <div class="info-box">
            <i class="fas fa-triangle-exclamation"></i>
            <div>
                <strong style="display:block;margin-bottom:4px;">Gmail users: You must use an App Password</strong>
                Your Gmail account requires a dedicated <strong>App Password</strong> instead of your regular account password.
                Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">Google App Passwords</a>,
                create a new password for "Mail", and paste it in the <em>SMTP Password</em> field below.
                Also ensure <strong>2-Step Verification</strong> is enabled on your Google account.
            </div>
        </div>

        <!-- ── Mail Settings Card ─────────────────────────────────────────── -->
        <div class="card">
            <div class="card-header">
                <div class="icon-wrap"><i class="fas fa-server"></i></div>
                <div>
                    <h2>SMTP Mail Server Settings</h2>
                    <p>Configure outgoing email via Gmail or any SMTP provider</p>
                </div>
            </div>
            <div class="card-body">
                <form action="" method="POST" id="mailForm">

                    <!-- Enable Toggle -->
                    <div class="toggle-row">
                        <div class="toggle-info">
                            <h4><i class="fas fa-envelope" style="color:#088178;margin-right:6px;"></i>Mail System</h4>
                            <p>Enable to send order confirmations, password resets, and notifications.</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="mail_enabled" id="mail_enabled"
                                   <?php echo $mail['mail_enabled'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <!-- Quick Presets -->
                    <div class="section-title"><i class="fas fa-bolt"></i> Quick Presets</div>
                    <div class="preset-row">
                        <button type="button" class="preset-btn" onclick="applyPreset('gmail')">
                            <img src="https://www.gstatic.com/images/branding/product/1x/gmail_2020q4_32dp.png" alt="Gmail"> Gmail (SMTP)
                        </button>
                        <button type="button" class="preset-btn" onclick="applyPreset('outlook')">
                            <img src="https://is1-ssl.mzstatic.com/image/thumb/Purple112/v4/32/4b/bf/324bbfad-fe87-7e7e-26cf-5f9af7ac7c43/AppIcon-0-0-1x_U007emarketing-0-0-0-10-0-0-sRGB-0-0-0-GLES2_U002c0-512MB-85-220-0-0.png/32x32bb.png" alt="Outlook" style="border-radius:4px;"> Outlook
                        </button>
                        <button type="button" class="preset-btn" onclick="applyPreset('yahoo')">
                            <i class="fab fa-yahoo" style="color:#6001d2;font-size:16px;"></i> Yahoo Mail
                        </button>
                        <button type="button" class="preset-btn" onclick="applyPreset('custom')">
                            <i class="fas fa-server" style="color:#088178;"></i> Custom SMTP
                        </button>
                    </div>

                    <!-- Connection Settings -->
                    <div class="section-title"><i class="fas fa-plug"></i> Connection Settings</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-network-wired"></i> SMTP Host</label>
                            <input type="text" name="mail_host" id="mail_host"
                                   value="<?php echo htmlspecialchars($mail['mail_host']); ?>"
                                   placeholder="smtp.gmail.com" required>
                            <span class="hint"><i class="fas fa-info-circle"></i> e.g. smtp.gmail.com</span>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-hashtag"></i> SMTP Port</label>
                            <input type="number" name="mail_port" id="mail_port"
                                   value="<?php echo (int)$mail['mail_port']; ?>"
                                   placeholder="587" required min="1" max="65535">
                            <span class="hint"><i class="fas fa-info-circle"></i> TLS=587, SSL=465</span>
                        </div>
                        <div class="form-group full">
                            <label><i class="fas fa-shield-alt"></i> Encryption</label>
                            <div class="encryption-row">
                                <div class="enc-chip">
                                    <input type="radio" name="mail_encryption" id="enc_tls" value="tls"
                                           <?php echo($mail['mail_encryption'] === 'tls') ? 'checked' : ''; ?>>
                                    <label for="enc_tls"><i class="fas fa-lock"></i> TLS (Recommended)</label>
                                </div>
                                <div class="enc-chip">
                                    <input type="radio" name="mail_encryption" id="enc_ssl" value="ssl"
                                           <?php echo($mail['mail_encryption'] === 'ssl') ? 'checked' : ''; ?>>
                                    <label for="enc_ssl"><i class="fas fa-lock"></i> SSL</label>
                                </div>
                                <div class="enc-chip">
                                    <input type="radio" name="mail_encryption" id="enc_none" value="none"
                                           <?php echo($mail['mail_encryption'] === 'none') ? 'checked' : ''; ?>>
                                    <label for="enc_none"><i class="fas fa-unlock"></i> None</label>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="mail_driver" value="smtp">
                    </div>

                    <!-- Auth Settings -->
                    <div class="section-title"><i class="fas fa-key"></i> Authentication</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-at"></i> SMTP Username (Email)</label>
                            <input type="email" name="mail_username" id="mail_username"
                                   value="<?php echo htmlspecialchars($mail['mail_username']); ?>"
                                   placeholder="you@gmail.com" required>
                            <span class="hint"><i class="fas fa-info-circle"></i> Your full Gmail address</span>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> SMTP Password</label>
                            <div class="password-wrap">
                                <input type="password" name="mail_password" id="mail_password"
                                       value="<?php echo htmlspecialchars($mail['mail_password']); ?>"
                                       placeholder="App Password (not your Gmail password)">
                                <button type="button" class="password-toggle" onclick="togglePassword('mail_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <span class="hint"><i class="fas fa-triangle-exclamation" style="color:#f59e0b;"></i> Use a Gmail App Password</span>
                        </div>
                    </div>

                    <!-- Sender Info -->
                    <div class="section-title"><i class="fas fa-id-card"></i> Sender Identity</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> From Email Address</label>
                            <input type="email" name="mail_from_address" id="mail_from_address"
                                   value="<?php echo htmlspecialchars($mail['mail_from_address']); ?>"
                                   placeholder="noreply@yourstore.com" required>
                            <span class="hint"><i class="fas fa-info-circle"></i> Visible to mail recipients</span>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-signature"></i> From Name</label>
                            <input type="text" name="mail_from_name" id="mail_from_name"
                                   value="<?php echo htmlspecialchars($mail['mail_from_name']); ?>"
                                   placeholder="<?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'My Store'; ?>">
                            <span class="hint"><i class="fas fa-info-circle"></i> Sender display name</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="save_mail_settings" class="btn-primary">
                            <i class="fas fa-floppy-disk"></i> Save Mail Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Test Mail Panel ─────────────────────────────────────────────── -->
        <div class="test-panel">
            <h3><i class="fas fa-paper-plane" style="color:#10b981;"></i> Send a Test Email</h3>
            <p>Verify your mail configuration is working correctly by sending a test email right now.</p>

            <div class="test-form-grid">
                <div class="test-input">
                    <label for="test_recipient"><i class="fas fa-inbox"></i> Recipient Email Address</label>
                    <input type="email" id="test_recipient"
                           placeholder="Enter email to send test to..."
                           value="<?php echo htmlspecialchars($mail['mail_username']); ?>">
                </div>
                <button type="button" class="btn-test" id="btnSendTest" onclick="sendTestMail()">
                    <i class="fas fa-paper-plane"></i> Send Test Mail
                </button>
            </div>

            <div class="test-result" id="testResult">
                <span class="ri"><i class="fas fa-circle-check"></i></span>
                <div class="rd">
                    <strong id="testTitle">Success!</strong>
                    <span id="testMsg"></span>
                </div>
            </div>
        </div>

        <!-- ── Configuration Tips ────────────────────────────────────────── -->
        <div class="card">
            <div class="card-header">
                <div class="icon-wrap"><i class="fas fa-lightbulb"></i></div>
                <div>
                    <h2>Setup Guide — Gmail SMTP</h2>
                    <p>Step-by-step instructions to configure Gmail correctly</p>
                </div>
            </div>
            <div class="card-body">
                <ol style="margin:0;padding-left:22px;color:#374151;font-size:14px;line-height:2.2;">
                    <li>Log into your Google Account → <strong>Manage your Google Account</strong></li>
                    <li>Go to <strong>Security</strong> tab → enable <strong>2-Step Verification</strong></li>
                    <li>Search for <strong>"App passwords"</strong> in the search bar</li>
                    <li>Create a new app password — select app: <em>Mail</em>, device: <em>Other</em> → name it <em>ByteBazaar</em></li>
                    <li>Copy the 16-character password and paste it in the <strong>SMTP Password</strong> field above</li>
                    <li>Use <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">smtp.gmail.com</code> as host, port <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">587</code>, encryption <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">TLS</code></li>
                    <li>Save settings, then use the <strong>Send Test Email</strong> button to verify</li>
                </ol>
            </div>
        </div>

    </div><!-- /.page-wrapper -->
</div><!-- /.main-content -->

<script>
/* ── Preset Loader ─────────────────────────────────────── */
const PRESETS = {
    gmail:   { host: 'smtp.gmail.com',       port: 587, enc: 'tls' },
    outlook: { host: 'smtp.office365.com',   port: 587, enc: 'tls' },
    yahoo:   { host: 'smtp.mail.yahoo.com',  port: 465, enc: 'ssl' },
    custom:  { host: '',                      port: 587, enc: 'tls' }
};

function applyPreset(provider) {
    const p = PRESETS[provider];
    document.getElementById('mail_host').value = p.host;
    document.getElementById('mail_port').value = p.port;
    const encInput = document.querySelector(`input[name="mail_encryption"][value="${p.enc}"]`);
    if (encInput) encInput.checked = true;
    if (provider === 'custom') document.getElementById('mail_host').focus();
}

/* ── Password Toggle ───────────────────────────────────── */
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.querySelector('i').className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        btn.querySelector('i').className = 'fas fa-eye';
    }
}

/* ── Send Test Mail ────────────────────────────────────── */
function sendTestMail() {
    const recipient = document.getElementById('test_recipient').value.trim();
    if (!recipient) {
        showTestResult(false, 'Missing Recipient', 'Please enter a recipient email address.');
        return;
    }

    const btn = document.getElementById('btnSendTest');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';

    const fd = new FormData();
    fd.append('recipient', recipient);
    fd.append('action', 'send_test');

    fetch('send_test_mail.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            showTestResult(data.success, data.title, data.message);
        })
        .catch(() => {
            showTestResult(false, 'Request Failed', 'Could not reach the server. Check your network or PHP error logs.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Test Mail';
        });
}

function showTestResult(ok, title, msg) {
    const box   = document.getElementById('testResult');
    const icon  = box.querySelector('.ri i');
    const tTitle = document.getElementById('testTitle');
    const tMsg   = document.getElementById('testMsg');

    box.className = 'test-result show ' + (ok ? 'ok' : 'fail');
    icon.className = ok ? 'fas fa-circle-check' : 'fas fa-circle-xmark';
    tTitle.textContent = title;
    tMsg.textContent   = msg;
}

/* ── Auto show/hide toggle label ──────────────────────── */
document.getElementById('mail_enabled').addEventListener('change', function() {
    // Could add visual feedback here if needed
});
</script>

</body>
</html>
