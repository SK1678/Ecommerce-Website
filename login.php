<?php
session_start();
include("include/connect.php");
include("include/mail_helper.php");

$login_error = ''; // 'not_found' | 'wrong_password' | 'not_verified'
$login_email = ''; // masked email for verification warning
$prefill_user = ''; // re-fill username field on error

if (isset($_POST['submit'])) {

    $username = $con->real_escape_string(trim($_POST['username']));
    $password = trim($_POST['password']);
    $prefill_user = htmlspecialchars($username);

    // ── Step 1: Does the username even exist? ─────────────────────────────────
    $row_res = $con->query("SELECT * FROM accounts WHERE username='$username' LIMIT 1");

    if (!$row_res || $row_res->num_rows === 0) {
        $login_error = 'not_found';
    }
    else {
        $row = $row_res->fetch_assoc();

        // ── Step 2: Is the account verified? ─────────────────────────────────
        $is_verified = isset($row['is_verified']) ? (int)$row['is_verified'] : 1;
        if ($is_verified === 0) {
            $login_error = 'not_verified';
            $login_email = preg_replace('/(?<=.{2}).(?=.*@)/', '*', $row['email']);

        // ── Step 3: Password check ────────────────────────────────────────────
        }
        elseif ($row['password'] !== $password) {
            $login_error = 'wrong_password';
        }
        else {
            // ✅ SUCCESS
            $_SESSION['aid'] = $row['aid'];
            header("Location: profile.php");
            exit();
        }
    }
}

// ── Forgot Password / Username handler ─────────────────────────────────────────
$fp_msg = '';
$fp_error = '';

if (isset($_POST['action']) && $_POST['action'] === 'forgot_password') {
    $fp_email = $con->real_escape_string(trim($_POST['fp_email']));

    if (!filter_var($fp_email, FILTER_VALIDATE_EMAIL)) {
        $fp_error = 'Please enter a valid email address.';
    }
    else {
        $fp_res = $con->query("SELECT * FROM accounts WHERE email='$fp_email' LIMIT 1");

        if (!$fp_res || $fp_res->num_rows === 0) {
            $fp_error = 'No account found with that email address.';
        }
        else {
            $fp_user = $fp_res->fetch_assoc();

            // ── Generate reset token (valid 1 hour) ───────────────────────────
            $check_reset = $con->query("SHOW COLUMNS FROM `accounts` LIKE 'reset_token'");
            if ($check_reset->num_rows == 0) {
                $con->query("ALTER TABLE `accounts` ADD `reset_token` VARCHAR(64) DEFAULT NULL");
                $con->query("ALTER TABLE `accounts` ADD `reset_expiry` DATETIME DEFAULT NULL");
            }

            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 3600);
            $aid = (int)$fp_user['aid'];
            $con->query("UPDATE accounts SET reset_token='$token', reset_expiry='$expiry' WHERE aid=$aid");

            // ── Send reset email using helper ──────────────────────────────────
            $site_name = !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar';
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST']
                . rtrim(dirname($_SERVER['PHP_SELF']), '/');
            $reset_url = $base_url . '/reset_password.php?token=' . urlencode($token);

            $subject = '🔐 Account Recovery (Username & Password) — ' . $site_name;
            $html_body = "
            <!DOCTYPE html><html><head><meta charset='UTF-8'>
            <style>
                body{margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;}
                .wrap{max-width:600px;margin:40px auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.12);}
                .hdr{background:linear-gradient(135deg,#088178,#05635d);padding:40px;text-align:center;color:#fff;}
                .hdr .ico{font-size:48px;margin-bottom:12px;}
                .hdr h1{margin:0;font-size:24px;font-weight:800;}
                .body{padding:36px 40px;}
                .info-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:28px;}
                .info-item{margin-bottom:10px;font-size:15px;color:#475569;}
                .info-item strong{color:#1e293b;font-size:16px;}
                .greet{font-size:16px;color:#1e293b;font-weight:600;margin-bottom:12px;}
                .msg{font-size:14px;color:#64748b;line-height:1.8;margin-bottom:20px;}
                .btn-wrap{text-align:center;margin:30px 0;}
                .btn{display:inline-block;padding:15px 36px;background:linear-gradient(135deg,#088178,#05635d);color:#fff;text-decoration:none;border-radius:12px;font-size:16px;font-weight:700;}
                .url-box{background:#f1f5f9;border-radius:8px;padding:12px;font-size:12px;color:#94a3b8;word-break:break-all;text-align:center;}
                .warn{background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:12px 16px;font-size:13px;color:#92400e;margin-top:20px;}
                .footer{background:#f8fafc;padding:20px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}
            </style></head><body>
            <div class='wrap'>
                <div class='hdr'><div class='ico'>🔐</div><h1>Account Recovery</h1></div>
                <div class='body'>
                    <p class='greet'>Hello, " . htmlspecialchars($fp_user['afname']) . "!</p>
                    <p class='msg'>We received a request to recover your account details for <strong>" . htmlspecialchars($site_name) . "</strong>.</p>
                    
                    <div class='info-box'>
                        <div class='info-item'>👤 <strong>Your Username:</strong> " . htmlspecialchars($fp_user['username']) . "</div>
                        <div class='info-item'>🔑 <strong>Need to Reset Password?</strong> Click the button below:</div>
                    </div>

                    <div class='btn-wrap'><a href='$reset_url' class='btn'>🔑 Reset My Password</a></div>
                    
                    <div class='url-box'>Link: $reset_url</div>
                    
                    <div class='warn'>🕒 <strong>Note:</strong> The password reset link will expire in 1 hour. If you didn't request this, you can safely ignore this email.</div>
                </div>
                <div class='footer'>&copy; " . date('Y') . " " . htmlspecialchars($site_name) . ". All rights reserved.</div>
            </div></body></html>";

            $alt_body = "Account Recovery for $site_name\n\nYour Username: " . $fp_user['username'] . "\n\nReset your password here: $reset_url\n\nThis link expires in 1 hour.";

            $res = sendCustomEmail($fp_email, $fp_user['afname'] . ' ' . $fp_user['alname'], $subject, $html_body, $alt_body);

            if ($res['success']) {
                $fp_msg = 'sent';
            }
            else {
                $fp_error = "Mail delivery failed: " . $res['message'];
            }
        }
    }
}

$site_name = !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | <?php echo htmlspecialchars($site_name); ?></title>
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
    <style>
        /* ── Login Alert Banners ──────────────────────────────────────────── */
        .login-alert {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 10px;
            margin: 0 auto 18px;
            max-width: 400px;
            font-size: 13.5px;
            font-weight: 500;
            line-height: 1.5;
            animation: alertIn .35s ease both;
            width: 90%;
        }
        @keyframes alertIn {
            from { opacity:0; transform: translateY(-8px); }
            to   { opacity:1; transform: translateY(0); }
        }
        .alert-danger   { background:#fee2e2; color:#991b1b; border-left:4px solid #ef4444; }
        .alert-warning  { background:#fffbeb; color:#92400e; border-left:4px solid #f59e0b; }
        .alert-info     { background:#eff6ff; color:#1e40af; border-left:4px solid #3b82f6; }

        .login-alert .alert-icon { font-size:18px; flex-shrink:0; margin-top:1px; }
        .login-alert .alert-text strong { display:block; margin-bottom:3px; }
        .login-alert .alert-text a {
            color: inherit; text-decoration: underline; font-weight: 700; cursor: pointer;
        }

        /* ── Forgot Password link ────────────────────────────────────────── */
        .forgot-link {
            font-size: 12.5px;
            color: #088178;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            font-family: inherit;
            transition: opacity .2s;
        }
        .forgot-link:hover { opacity: .7; text-decoration: underline; }

        /* ── Bottom links row (same line centered) ───────────────────────── */
        .bottom-links {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            max-width: 500px;
            width: 100%;
            margin: 20px auto 30px;
            font-size: 14px;
        }
        .bottom-links .signn {
            color: #088178;
            font-weight: 600;
            text-decoration: underline;
            transition: 0.3s;
        }
        .bottom-links .forgot-link {
            color: #088178;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            background: none;
            border: none;
            padding: 0;
            font-family: inherit;
            font-size: 14px;
            cursor: pointer;
        }
        .bottom-links .signn:hover, .bottom-links .forgot-link:hover {
            color: #05635d;
        }
        .bottom-links .sep {
            width: 1px;
            height: 14px;
            background: #ccc;
        }

        /* ── Modal Overlay ───────────────────────────────────────────────── */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.55);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }
        .modal-overlay.open { display: flex; animation: fadeIn .3s ease; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }

        .modal-box {
            background: #fff;
            border-radius: 18px;
            padding: 36px 36px 30px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 25px 60px rgba(0,0,0,.25);
            position: relative;
            animation: slideUp .35s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes slideUp {
            from { opacity:0; transform: translateY(30px) scale(.96); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }
        .modal-close {
            position:absolute; top:16px; right:18px;
            background:none; border:none; font-size:20px;
            color:#94a3b8; cursor:pointer; padding:4px 8px;
            border-radius:6px; transition: .2s;
        }
        .modal-close:hover { background:#f1f5f9; color:#475569; }

        .modal-icon {
            width:60px; height:60px; border-radius:50%;
            background:linear-gradient(135deg,#088178,#05635d);
            display:flex; align-items:center; justify-content:center;
            font-size:24px; color:#fff; margin:0 auto 20px;
        }
        .modal-box h3 {
            text-align:center; font-size:20px; font-weight:700;
            color:#1e293b; margin-bottom:8px;
        }
        .modal-box p.modal-sub {
            text-align:center; font-size:13px; color:#64748b;
            margin: 0 0 22px; line-height: 1.6;
        }
        .modal-input {
            width:100%; padding:12px 16px;
            border:1.5px solid #e2e8f0; border-radius:10px;
            font-size:14px; font-family:inherit; color:#1e293b;
            outline:none; transition:border-color .25s, box-shadow .25s;
            margin-bottom:16px;
        }
        .modal-input:focus {
            border-color:#088178;
            box-shadow:0 0 0 3px rgba(8,129,120,.12);
        }
        .modal-btn {
            width:100%; padding:13px;
            border-radius:10px; border:none; cursor:pointer;
            font-size:15px; font-weight:700; font-family:inherit;
            background:linear-gradient(135deg,#088178,#05635d);
            color:#fff; transition: all .3s;
            box-shadow:0 4px 14px rgba(8,129,120,.35);
        }
        .modal-btn:hover { transform:translateY(-2px); box-shadow:0 8px 22px rgba(8,129,120,.4); }
        .modal-btn:disabled { opacity:.6; cursor:not-allowed; transform:none; }

        .fp-result {
            border-radius:10px; padding:14px 16px;
            font-size:13px; font-weight:500;
            display:flex; align-items:flex-start; gap:10px; margin-top:14px;
        }
        .fp-result.ok   { background:#d1fae5; border:1px solid #a7f3d0; color:#065f46; }
        .fp-result.fail { background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; }
        .fp-result i    { font-size:16px; flex-shrink:0; margin-top:1px; }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <!-- ── Login Alert ──────────────────────────────────────────────────── -->
    <?php if ($login_error === 'not_found'): ?>
    <div class="login-alert alert-danger">
        <i class="fas fa-circle-xmark alert-icon"></i>
        <div class="alert-text">
            <strong>Account Not Found</strong>
            No account exists with username <strong>"<?php echo htmlspecialchars($prefill_user); ?>"</strong>.
            <a href="signup.php">Create an account?</a>
        </div>
    </div>

    <?php
elseif ($login_error === 'wrong_password'): ?>
    <div class="login-alert alert-danger">
        <i class="fas fa-lock alert-icon"></i>
        <div class="alert-text">
            <strong>Incorrect Password</strong>
            The password you entered is wrong. Try again or
            <a onclick="openForgot()">recover your account</a>.
        </div>
    </div>

    <?php
elseif ($login_error === 'not_verified'): ?>
    <div class="login-alert alert-warning">
        <i class="fas fa-envelope-open-text alert-icon"></i>
        <div class="alert-text">
            <strong>Email Not Verified Yet</strong>
            A verification link was sent to <strong><?php echo htmlspecialchars($login_email); ?></strong>.
            Please check your inbox &amp; spam folder and click the confirmation link to activate your account.
        </div>
    </div>
    <?php
endif; ?>

    <!-- ── Login Form ───────────────────────────────────────────────────── -->
    <form method="post" id="form">
        <h3 style="color: darkred; margin: auto"></h3>
        <input class="input1" id="user" name="username" type="text"
               placeholder="Username *" value="<?php echo htmlspecialchars($prefill_user); ?>" autocomplete="username">
        <input class="input1" id="pass" name="password" type="password"
               placeholder="Password *" autocomplete="current-password">
        <button type="submit" class="btn" name="submit">Login</button>
    </form>

    <!-- ── Bottom links row (same line centered) ────────────────────────── -->
    <div class="bottom-links">
        <a href="signup.php" class="signn">Do not have an account?</a>
        <div class="sep"></div>
        <button type="button" class="forgot-link" onclick="openForgot()">
            <i class="fas fa-key"></i> Forgot Password/Username?
        </button>
    </div>

    <!-- ── Forgot Password Modal ────────────────────────────────────────── -->
    <div class="modal-overlay" id="forgotModal" onclick="closeOnBackdrop(event)">
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="fpTitle">
            <button class="modal-close" onclick="closeForgot()" title="Close">&times;</button>

            <div class="modal-icon"><i class="fas fa-user-shield"></i></div>
            <h3 id="fpTitle">Account Recovery</h3>
            <p class="modal-sub">Enter your email and we'll send you your <strong>Username</strong> and a <strong>Password Reset</strong> link.</p>

            <?php if ($fp_msg === 'sent'): ?>
                <div class="fp-result ok">
                    <i class="fas fa-circle-check"></i>
                    <div><strong>Email Sent!</strong><br>
                    Check your inbox (and spam folder) for your account details.</div>
                </div>
            <?php
elseif ($fp_error): ?>
                <div class="fp-result fail">
                    <i class="fas fa-circle-xmark"></i>
                    <div><strong>Error:</strong><br><?php echo htmlspecialchars($fp_error); ?></div>
                </div>
            <?php
endif; ?>

            <?php if ($fp_msg !== 'sent'): ?>
            <form method="POST" id="forgotForm">
                <input type="hidden" name="action" value="forgot_password">
                <input type="email" name="fp_email" id="fp_email" class="modal-input"
                       placeholder="Enter your registered email..."
                       value="<?php echo isset($_POST['fp_email']) ? htmlspecialchars($_POST['fp_email']) : ''; ?>"
                       required>
                <button type="submit" class="modal-btn" id="fpBtn">
                    <i class="fas fa-paper-plane"></i> Send Recovery Details
                </button>
            </form>
            <?php
endif; ?>
        </div>
    </div>

    <?php include('footer.php'); ?>
    <script src="script.js"></script>

    <script>
        // ── Open/close forgotten password modal ────────────────────────────
        function openForgot() {
            document.getElementById('forgotModal').classList.add('open');
            setTimeout(() => document.getElementById('fp_email')?.focus(), 100);
        }
        function closeForgot() {
            document.getElementById('forgotModal').classList.remove('open');
        }
        function closeOnBackdrop(e) {
            if (e.target === document.getElementById('forgotModal')) closeForgot();
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeForgot();
        });

        // ── Auto-open modal if there was a forgot-password form error/result
        <?php if ($fp_msg || $fp_error): ?>
        document.addEventListener('DOMContentLoaded', () => openForgot());
        <?php
endif; ?>

        // ── Button loading state ─────────────────────────────────────────
        document.getElementById('forgotForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('fpBtn');
            // Delay disabling slightly so the form data (including any button values) is captured
            setTimeout(() => {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending Recovery Email…';
            }, 50);
        });
    </script>
</body>
</html>