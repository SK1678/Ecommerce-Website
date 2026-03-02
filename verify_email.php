<?php
session_start();
include("include/connect.php");

// ── Ensure the accounts table has the needed columns ────────────────────────
$con->query("ALTER TABLE `accounts` ADD COLUMN IF NOT EXISTS `is_verified` TINYINT(1) NOT NULL DEFAULT 0");
$con->query("ALTER TABLE `accounts` ADD COLUMN IF NOT EXISTS `verify_token` VARCHAR(64) DEFAULT NULL");
$con->query("ALTER TABLE `accounts` ADD COLUMN IF NOT EXISTS `token_expiry` DATETIME DEFAULT NULL");

$status = 'invalid'; // invalid | expired | already | success
$message = '';

if (!empty($_GET['token'])) {
    $token = $con->real_escape_string(trim($_GET['token']));

    $res = $con->query("SELECT * FROM accounts WHERE verify_token = '$token' LIMIT 1");

    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();

        if ($user['is_verified'] == 1) {
            $status = 'already';
            $message = 'Your email is already verified. You can log in.';
        }
        elseif (strtotime($user['token_expiry']) < time()) {
            $status = 'expired';
            $message = 'This verification link has expired (valid for 24 hours). Please sign up again or request a new link.';
        }
        else {
            // ── Activate the account ──────────────────────────────────────
            $aid = (int)$user['aid'];
            $con->query("UPDATE accounts SET is_verified=1, verify_token=NULL, token_expiry=NULL WHERE aid=$aid");
            $status = 'success';
            $message = 'Your email has been verified successfully! You can now log in.';
        }
    }
    else {
        $status = 'invalid';
        $message = 'Invalid or already used verification link.';
    }
}
else {
    header("Location: index.php");
    exit;
}

$site_name = !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | <?php echo htmlspecialchars($site_name); ?></title>
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            padding: 20px;
        }

        /* Animated background blobs */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .25;
            pointer-events: none;
            animation: float 8s ease-in-out infinite;
        }
        body::before {
            width: 500px; height: 500px;
            background: #088178;
            top: -100px; left: -100px;
        }
        body::after {
            width: 400px; height: 400px;
            background: #6366f1;
            bottom: -100px; right: -100px;
            animation-delay: -4s;
        }
        @keyframes float {
            0%,100% { transform: translateY(0) scale(1); }
            50%      { transform: translateY(30px) scale(1.05); }
        }

        .card {
            position: relative; z-index: 1;
            background: rgba(255,255,255,.04);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 24px;
            padding: 52px 48px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,.4);
            animation: cardIn .6s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(40px) scale(.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .icon-wrap {
            width: 90px; height: 90px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 38px;
            margin: 0 auto 28px;
            animation: iconPop .5s .3s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes iconPop {
            from { opacity:0; transform: scale(0); }
            to   { opacity:1; transform: scale(1); }
        }

        .icon-success { background: rgba(16,185,129,.2); color: #10b981; border: 2px solid rgba(16,185,129,.3); }
        .icon-expired { background: rgba(245,158,11,.2); color: #f59e0b; border: 2px solid rgba(245,158,11,.3); }
        .icon-already { background: rgba(99,102,241,.2); color: #818cf8; border: 2px solid rgba(99,102,241,.3); }
        .icon-invalid { background: rgba(239,68,68,.2);  color: #ef4444; border: 2px solid rgba(239,68,68,.3); }

        h1 {
            font-size: 26px; font-weight: 800;
            color: #f1f5f9;
            margin-bottom: 14px;
            line-height: 1.3;
        }

        .subtitle {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 13px 32px;
            border-radius: 12px; border: none; cursor: pointer;
            font-size: 15px; font-weight: 600; font-family: 'Inter', sans-serif;
            text-decoration: none;
            transition: all .3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #088178, #05635d);
            color: #fff;
            box-shadow: 0 4px 16px rgba(8,129,120,.4);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(8,129,120,.5); }

        .btn-secondary {
            background: rgba(255,255,255,.08);
            color: #cbd5e1;
            border: 1px solid rgba(255,255,255,.12);
        }
        .btn-secondary:hover { background: rgba(255,255,255,.14); transform: translateY(-2px); }

        .btn-row { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

        /* Success confetti dots */
        .confetti-line {
            display: flex; justify-content: center; gap: 8px;
            margin-bottom: 24px;
        }
        .dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            animation: bounce .6s ease infinite alternate;
        }
        .dot:nth-child(1) { background:#10b981; animation-delay: 0s; }
        .dot:nth-child(2) { background:#6366f1; animation-delay: .1s; }
        .dot:nth-child(3) { background:#f59e0b; animation-delay: .2s; }
        .dot:nth-child(4) { background:#ec4899; animation-delay: .3s; }
        .dot:nth-child(5) { background:#088178; animation-delay: .4s; }
        @keyframes bounce {
            from { transform: translateY(0); }
            to   { transform: translateY(-12px); }
        }

        .brand { font-size: 13px; color: #475569; margin-top: 32px; }
        .brand strong { color: #088178; }
    </style>
</head>
<body>
    <div class="card">

        <?php if ($status === 'success'): ?>
            <div class="icon-wrap icon-success"><i class="fas fa-check-circle"></i></div>
            <div class="confetti-line">
                <div class="dot"></div><div class="dot"></div><div class="dot"></div>
                <div class="dot"></div><div class="dot"></div>
            </div>
            <h1>Email Verified! 🎉</h1>
            <p class="subtitle">Your account is now active and ready to go.<br>Welcome to <?php echo htmlspecialchars($site_name); ?>!</p>
            <div class="btn-row">
                <a href="login.php" class="btn btn-primary"><i class="fas fa-right-to-bracket"></i> Login Now</a>
            </div>

        <?php
elseif ($status === 'expired'): ?>
            <div class="icon-wrap icon-expired"><i class="fas fa-clock"></i></div>
            <h1>Link Expired</h1>
            <p class="subtitle"><?php echo $message; ?></p>
            <div class="btn-row">
                <a href="signup.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Sign Up Again</a>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-house"></i> Home</a>
            </div>

        <?php
elseif ($status === 'already'): ?>
            <div class="icon-wrap icon-already"><i class="fas fa-circle-check"></i></div>
            <h1>Already Verified</h1>
            <p class="subtitle"><?php echo $message; ?></p>
            <div class="btn-row">
                <a href="login.php" class="btn btn-primary"><i class="fas fa-right-to-bracket"></i> Go to Login</a>
            </div>

        <?php
else: ?>
            <div class="icon-wrap icon-invalid"><i class="fas fa-triangle-exclamation"></i></div>
            <h1>Invalid Link</h1>
            <p class="subtitle"><?php echo $message; ?></p>
            <div class="btn-row">
                <a href="signup.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Sign Up</a>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-house"></i> Home</a>
            </div>
        <?php
endif; ?>

        <p class="brand">Powered by <strong><?php echo htmlspecialchars($site_name); ?></strong></p>
    </div>
</body>
</html>
