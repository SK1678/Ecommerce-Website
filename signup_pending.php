<?php
session_start();
include("include/connect.php");

$email = $_SESSION['signup_email'] ?? '';
$site_name = !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Your Email | <?php echo htmlspecialchars($site_name); ?></title>
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
        body::before {
            content: '';
            position: fixed;
            width: 600px; height: 600px; border-radius: 50%;
            background: #088178; filter: blur(120px); opacity: .15;
            top: -200px; left: -200px; pointer-events: none;
            animation: blob 8s ease-in-out infinite;
        }
        @keyframes blob {
            0%,100% { transform: scale(1); }
            50%      { transform: scale(1.1) translateY(20px); }
        }

        .card {
            position: relative; z-index: 1;
            background: rgba(255,255,255,.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 24px;
            padding: 52px 48px;
            max-width: 500px; width: 100%;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,.4);
            animation: cardIn .6s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes cardIn {
            from { opacity:0; transform: translateY(40px) scale(.95); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }

        /* Envelope animation */
        .envelope {
            width: 100px; height: 100px;
            margin: 0 auto 32px;
            position: relative;
        }
        .env-icon {
            width: 100px; height: 100px; border-radius: 50%;
            background: rgba(8,129,120,.2);
            border: 2px solid rgba(8,129,120,.4);
            display: flex; align-items: center; justify-content: center;
            font-size: 42px; color: #10b981;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(8,129,120,.4); }
            50%      { box-shadow: 0 0 0 20px rgba(8,129,120,.0); }
        }
        .env-badge {
            position: absolute; top: 2px; right: 2px;
            width: 26px; height: 26px; border-radius: 50%;
            background: #10b981; color: white;
            font-size: 12px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid #1e293b;
            animation: pop .5s .5s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes pop {
            from { opacity:0; transform: scale(0); }
            to   { opacity:1; transform: scale(1); }
        }

        h1 { font-size: 26px; font-weight: 800; color: #f1f5f9; margin-bottom: 12px; }
        .subtitle { font-size: 14px; color: #94a3b8; line-height: 1.7; margin-bottom: 28px; }

        .email-box {
            background: rgba(8,129,120,.1);
            border: 1px solid rgba(8,129,120,.3);
            border-radius: 10px;
            padding: 12px 20px;
            font-size: 15px; font-weight: 600; color: #10b981;
            margin-bottom: 28px;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            word-break: break-all;
        }

        .steps {
            text-align: left;
            margin-bottom: 32px;
        }
        .step {
            display: flex; align-items: flex-start; gap: 14px;
            margin-bottom: 16px;
        }
        .step-num {
            width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
            background: rgba(8,129,120,.2); border: 1px solid rgba(8,129,120,.4);
            color: #10b981; font-size: 12px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }
        .step-text { font-size: 13px; color: #94a3b8; line-height: 1.6; padding-top: 4px; }
        .step-text strong { color: #e2e8f0; }

        .divider { border: none; border-top: 1px solid rgba(255,255,255,.08); margin: 24px 0; }

        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px;
            border-radius: 10px; border: none; cursor: pointer;
            font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif;
            text-decoration: none; transition: all .3s;
        }
        .btn-ghost {
            background: rgba(255,255,255,.07);
            color: #94a3b8; border: 1px solid rgba(255,255,255,.1);
            font-size: 13px;
        }
        .btn-ghost:hover { background: rgba(255,255,255,.12); color: #e2e8f0; }

        .spam-note {
            font-size: 12px; color: #475569; margin-top: 20px;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .spam-note i { color: #f59e0b; }
    </style>
</head>
<body>
    <div class="card">
        <div class="envelope">
            <div class="env-icon"><i class="fas fa-envelope-open-text"></i></div>
            <div class="env-badge"><i class="fas fa-check"></i></div>
        </div>

        <h1>Check Your Email!</h1>
        <p class="subtitle">We've sent a verification link to your inbox. Please confirm your email to activate your account.</p>

        <?php if ($email): ?>
        <div class="email-box">
            <i class="fas fa-at"></i>
            <?php echo htmlspecialchars($email); ?>
        </div>
        <?php
endif; ?>

        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-text">Open your email inbox for <strong><?php echo htmlspecialchars($email ?: 'your email address'); ?></strong></div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-text">Find the email from <strong><?php echo htmlspecialchars($site_name); ?></strong> with subject <strong>"Verify Your Email"</strong></div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-text">Click the <strong>"✅ Verify My Email"</strong> button inside the email</div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-text">You'll be redirected back here and can <strong>login immediately</strong></div>
            </div>
        </div>

        <hr class="divider">

        <a href="login.php" class="btn btn-ghost">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>

        <p class="spam-note">
            <i class="fas fa-triangle-exclamation"></i>
            Didn't receive it? Check your <strong>&nbsp;Spam / Junk</strong>&nbsp; folder. The link expires in 24 hours.
        </p>
    </div>
</body>
</html>
