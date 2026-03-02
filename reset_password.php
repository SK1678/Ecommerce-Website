<?php
session_start();
include("include/connect.php");

// ── Ensure columns exist ──────────────────────────────────────────────────────
$con->query("ALTER TABLE `accounts` ADD COLUMN IF NOT EXISTS `reset_token` VARCHAR(64) DEFAULT NULL");
$con->query("ALTER TABLE `accounts` ADD COLUMN IF NOT EXISTS `reset_expiry` DATETIME DEFAULT NULL");

$token = trim($_GET['token'] ?? '');
$status = 'invalid'; // invalid | expired | valid | done
$user = null;

if ($token) {
    $safe = $con->real_escape_string($token);
    $res = $con->query("SELECT * FROM accounts WHERE reset_token='$safe' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if (strtotime($user['reset_expiry']) < time()) {
            $status = 'expired';
        }
        else {
            $status = 'valid';
        }
    }
}

$error = '';
$success = false;

if ($status === 'valid' && isset($_POST['reset_submit'])) {
    $new_pass = $_POST['new_password'];
    $conf_pass = $_POST['confirm_password'];

    if (strlen($new_pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    }
    elseif ($new_pass !== $conf_pass) {
        $error = 'Passwords do not match.';
    }
    else {
        $safe_pass = $con->real_escape_string($new_pass);
        $aid = (int)$user['aid'];
        $con->query("UPDATE accounts SET password='$safe_pass', reset_token=NULL, reset_expiry=NULL, is_verified=1 WHERE aid=$aid");
        $success = true;
        $status = 'done';
    }
}

$site_name = !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | <?php echo htmlspecialchars($site_name); ?></title>
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            padding: 20px;
        }
        body::before, body::after {
            content: ''; position: fixed; border-radius: 50%;
            filter: blur(90px); opacity: .2; pointer-events: none;
        }
        body::before { width:500px;height:500px;background:#088178;top:-150px;left:-150px; animation: float 8s ease-in-out infinite; }
        body::after  { width:400px;height:400px;background:#6366f1;bottom:-120px;right:-120px; animation: float 8s 4s ease-in-out infinite; }
        @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(25px)} }

        .card {
            position:relative; z-index:1;
            background: rgba(255,255,255,.06);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 22px;
            padding: 44px 42px;
            max-width: 440px; width: 100%;
            box-shadow: 0 25px 60px rgba(0,0,0,.4);
            animation: cardIn .5s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes cardIn {
            from { opacity:0; transform: translateY(30px) scale(.96); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }
        .icon-wrap {
            width:80px; height:80px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:34px; margin:0 auto 24px;
        }
        .icon-ok      { background:rgba(8,129,120,.2);   color:#10b981; border:2px solid rgba(8,129,120,.3); }
        .icon-expired { background:rgba(245,158,11,.2);  color:#f59e0b; border:2px solid rgba(245,158,11,.3); }
        .icon-invalid { background:rgba(239,68,68,.2);   color:#ef4444; border:2px solid rgba(239,68,68,.3); }
        .icon-done    { background:rgba(16,185,129,.2);  color:#10b981; border:2px solid rgba(16,185,129,.3); }

        h2 { text-align:center; font-size:22px; font-weight:800; color:#f1f5f9; margin-bottom:8px; }
        .sub { text-align:center; font-size:13px; color:#94a3b8; margin-bottom:28px; line-height:1.6; }

        .field { margin-bottom:18px; }
        .field label { display:block; font-size:12.5px; font-weight:600; color:#cbd5e1; margin-bottom:7px; }
        .field .input-wrap { position:relative; }
        .field input {
            width:100%; padding:12px 42px 12px 16px;
            border:1.5px solid rgba(255,255,255,.12);
            border-radius:10px; font-size:14px;
            font-family:'Inter',sans-serif; color:#f1f5f9;
            background:rgba(255,255,255,.07);
            outline:none; transition:border-color .25s, box-shadow .25s;
        }
        .field input:focus {
            border-color:#088178;
            box-shadow:0 0 0 3px rgba(8,129,120,.2);
        }
        .field input::placeholder { color:#475569; }
        .eye-btn {
            position:absolute; right:12px; top:50%;
            transform:translateY(-50%);
            background:none; border:none; cursor:pointer;
            color:#64748b; font-size:15px; transition:color .2s;
        }
        .eye-btn:hover { color:#94a3b8; }

        .strength { margin-top:6px; }
        .strength-bar {
            height:4px; border-radius:4px; background:#1e293b;
            overflow:hidden; margin-bottom:4px;
        }
        .strength-fill { height:100%; border-radius:4px; transition:width .3s, background .3s; }
        .strength-text { font-size:11px; color:#64748b; }

        .error-box {
            background:rgba(239,68,68,.15); border:1px solid rgba(239,68,68,.3);
            border-radius:10px; padding:12px 16px;
            font-size:13px; color:#fca5a5;
            display:flex; align-items:center; gap:10px;
            margin-bottom:18px;
        }

        .btn-reset {
            width:100%; padding:13px;
            border-radius:10px; border:none; cursor:pointer;
            font-size:15px; font-weight:700; font-family:'Inter',sans-serif;
            background:linear-gradient(135deg,#088178,#05635d);
            color:#fff; transition:all .3s;
            box-shadow:0 4px 14px rgba(8,129,120,.4);
            margin-top:6px;
        }
        .btn-reset:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(8,129,120,.5); }

        .btn-login {
            display:inline-flex; align-items:center; gap:8px;
            padding:13px 28px; border-radius:10px; border:none; cursor:pointer;
            font-size:14px; font-weight:700; font-family:'Inter',sans-serif;
            background:linear-gradient(135deg,#088178,#05635d);
            color:#fff; text-decoration:none;
            box-shadow:0 4px 14px rgba(8,129,120,.4);
            transition:all .3s;
        }
        .btn-login:hover { transform:translateY(-2px); }
        .btn-center { text-align:center; margin-top:20px; }

        .confetti { display:flex; justify-content:center; gap:8px; margin-bottom:20px; }
        .confetti span { width:10px;height:10px;border-radius:50%; animation:bounce .6s ease infinite alternate; }
        .confetti span:nth-child(1){background:#10b981;animation-delay:0s}
        .confetti span:nth-child(2){background:#6366f1;animation-delay:.1s}
        .confetti span:nth-child(3){background:#f59e0b;animation-delay:.2s}
        .confetti span:nth-child(4){background:#ec4899;animation-delay:.3s}
        @keyframes bounce { from{transform:translateY(0)} to{transform:translateY(-12px)} }
    </style>
</head>
<body>
<div class="card">

    <?php if ($status === 'done'): ?>
        <!-- ✅ Success -->
        <div class="confetti">
            <span></span><span></span><span></span><span></span>
        </div>
        <div class="icon-wrap icon-done"><i class="fas fa-check-circle"></i></div>
        <h2>Password Reset! 🎉</h2>
        <p class="sub">Your password has been changed successfully. You can now log in with your new password.</p>
        <div class="btn-center">
            <a href="login.php" class="btn-login"><i class="fas fa-right-to-bracket"></i> Go to Login</a>
        </div>

    <?php
elseif ($status === 'expired'): ?>
        <!-- ⏰ Expired -->
        <div class="icon-wrap icon-expired"><i class="fas fa-clock"></i></div>
        <h2>Link Expired</h2>
        <p class="sub">This reset link has expired (valid for 1 hour only). Please request a new one.</p>
        <div class="btn-center">
            <a href="login.php" class="btn-login"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>

    <?php
elseif ($status === 'invalid'): ?>
        <!-- ❌ Invalid -->
        <div class="icon-wrap icon-invalid"><i class="fas fa-triangle-exclamation"></i></div>
        <h2>Invalid Reset Link</h2>
        <p class="sub">This link is invalid or has already been used. Please request a new one from the login page.</p>
        <div class="btn-center">
            <a href="login.php" class="btn-login"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>

    <?php
else: ?>
        <!-- 🔑 Reset Form -->
        <div class="icon-wrap icon-ok"><i class="fas fa-lock-open"></i></div>
        <h2>Set New Password</h2>
        <p class="sub">Hello, <strong style="color:#e2e8f0;"><?php echo htmlspecialchars($user['afname']); ?></strong>! Choose a strong new password below.</p>

        <?php if ($error): ?>
        <div class="error-box">
            <i class="fas fa-circle-xmark"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php
    endif; ?>

        <form method="POST" action="reset_password.php?token=<?php echo urlencode($token); ?>">
            <div class="field">
                <label>New Password</label>
                <div class="input-wrap">
                    <input type="password" name="new_password" id="new_pass"
                           placeholder="Min. 8 characters" minlength="8" required
                           oninput="checkStrength(this.value)">
                    <button type="button" class="eye-btn" onclick="toggleEye('new_pass', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="strength">
                    <div class="strength-bar"><div class="strength-fill" id="strengthBar" style="width:0%"></div></div>
                    <div class="strength-text" id="strengthText"></div>
                </div>
            </div>

            <div class="field">
                <label>Confirm New Password</label>
                <div class="input-wrap">
                    <input type="password" name="confirm_password" id="conf_pass"
                           placeholder="Repeat new password" required>
                    <button type="button" class="eye-btn" onclick="toggleEye('conf_pass', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="reset_submit" class="btn-reset">
                <i class="fas fa-floppy-disk"></i> Save New Password
            </button>
        </form>
    <?php
endif; ?>

</div>

<script>
function toggleEye(id, btn) {
    const inp = document.getElementById(id);
    const ico = btn.querySelector('i');
    if (inp.type === 'password') { inp.type = 'text';     ico.className = 'fas fa-eye-slash'; }
    else                         { inp.type = 'password'; ico.className = 'fas fa-eye'; }
}

function checkStrength(val) {
    const bar  = document.getElementById('strengthBar');
    const txt  = document.getElementById('strengthText');
    let score  = 0;
    if (val.length >= 8)               score++;
    if (/[A-Z]/.test(val))             score++;
    if (/[0-9]/.test(val))             score++;
    if (/[^A-Za-z0-9]/.test(val))     score++;

    const levels = [
        { pct:'0%',   color:'transparent', label:'' },
        { pct:'25%',  color:'#ef4444',     label:'Weak' },
        { pct:'50%',  color:'#f59e0b',     label:'Fair' },
        { pct:'75%',  color:'#3b82f6',     label:'Good' },
        { pct:'100%', color:'#10b981',     label:'Strong ✓' },
    ];
    const l = levels[score];
    bar.style.width     = l.pct;
    bar.style.background = l.color;
    txt.textContent     = l.label;
    txt.style.color     = l.color;
}
</script>
</body>
</html>
