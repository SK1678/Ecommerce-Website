<?php
session_start();
include("include/connect.php");
include("include/mail_helper.php");

// ── Ensure verification columns exist ────────────────────────────────────────
$check_cols = $con->query("SHOW COLUMNS FROM `accounts` LIKE 'is_verified'");
if ($check_cols->num_rows == 0) {
    $con->query("ALTER TABLE `accounts` ADD `is_verified` TINYINT(1) NOT NULL DEFAULT 0");
    $con->query("ALTER TABLE `accounts` ADD `verify_token` VARCHAR(64) DEFAULT NULL");
    $con->query("ALTER TABLE `accounts` ADD `token_expiry` DATETIME DEFAULT NULL");
}

// ── Helper: send verification email using central helper ─────────────────────
function sendVerificationEmail($con, $web_settings, $to_email, $to_name, $token)
{
    $site_name = !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar';
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST']
        . rtrim(dirname($_SERVER['PHP_SELF']), '/');
    $verify_url = $base_url . '/verify_email.php?token=' . urlencode($token);

    $subject = '✉️ Verify Your Email — ' . $site_name;
    $html_body = "
    <!DOCTYPE html><html><head><meta charset='UTF-8'>
    <style>
        body{margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;}
        .wrap{max-width:600px;margin:40px auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.12);}
        .hdr {background:linear-gradient(135deg,#088178,#05635d);padding:48px 40px 40px;text-align:center;color:#fff;}
        .hdr .emoji{font-size:52px;display:block;margin-bottom:16px;}
        .hdr h1{margin:0;font-size:26px;font-weight:800;letter-spacing:-.5px;}
        .hdr p {margin:10px 0 0;opacity:.85;font-size:15px;}
        .body{padding:40px;}
        .greet{font-size:16px;color:#1e293b;margin-bottom:16px;font-weight:600;}
        .msg{font-size:14px;color:#64748b;line-height:1.8;margin-bottom:32px;}
        .btn-wrap{text-align:center;margin-bottom:32px;}
        .btn{display:inline-block;padding:16px 40px;background:linear-gradient(135deg,#088178,#05635d);color:#fff;text-decoration:none;border-radius:12px;font-size:16px;font-weight:700;box-shadow:0 4px 16px rgba(8,129,120,.4);}
        .url-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 18px;font-size:12px;color:#64748b;word-break:break-all;margin-bottom:28px;}
        .warn{background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:14px 18px;font-size:13px;color:#92400e;}
        .footer{background:#f8fafc;padding:22px 40px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;}
    </style></head><body>
    <div class='wrap'>
        <div class='hdr'><span class='emoji'>✉️</span><h1>Confirm Your Email</h1><p>One click away from joining " . htmlspecialchars($site_name) . "!</p></div>
        <div class='body'>
            <p class='greet'>Hello, " . htmlspecialchars($to_name) . "! 👋</p>
            <p class='msg'>Thanks for registering at <strong>" . htmlspecialchars($site_name) . "</strong>. To activate your account and start shopping, please verify your email address by clicking the button below. This link will expire in 1 hour.</p>
            <div class='btn-wrap'><a href='$verify_url' class='btn'>✅ Verify My Email</a></div>
            <div class='url-box'><strong>Link:</strong> $verify_url</div>
            <div class='warn'>⚠️ If you didn't create an account, please ignore this email.</div>
        </div>
        <div class='footer'>&copy; " . date('Y') . " " . htmlspecialchars($site_name) . "</div>
    </div></body></html>";

    $alt_body = "Hello $to_name,\n\nVerify your email here: $verify_url\n\n— $site_name";

    $res = sendCustomEmail($to_email, $to_name, $subject, $html_body, $alt_body);
    return $res['success'];
}

// ── Form processing ───────────────────────────────────────────────────────────
if (isset($_POST['submit'])) {
    $firstname = $con->real_escape_string($_POST['firstName']);
    $lastname = $con->real_escape_string($_POST['lastName']);
    $username = $con->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $confirmpassowrd = $_POST['confirmPassword'];
    $cnic = $con->real_escape_string($_POST['cnic']);
    $dob = $con->real_escape_string($_POST['dob']);
    $contact = $con->real_escape_string($_POST['phone']);
    $gen = $con->real_escape_string($_POST['gender']);
    $email = $con->real_escape_string($_POST['email']);

    // ── Validation ────────────────────────────────────────────────────────────
    $query = "SELECT * FROM accounts WHERE username='$username' OR cnic='$cnic' OR phone='$contact' OR email='$email'";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);
    if (!empty($row['aid'])) {
        echo "<script> alert('Credentials already exists'); setTimeout(function(){ window.location.href = 'signup.php'; }, 100); </script>";
        exit();
    }
    if ($password != $confirmpassowrd) {
        echo "<script> alert('Passwords do not match'); setTimeout(function(){ window.location.href = 'signup.php'; }, 100); </script>";
        exit();
    }
    if (strlen($password) < 8) {
        echo "<script> alert('Passwords too short'); setTimeout(function(){ window.location.href = 'signup.php'; }, 100); </script>";
        exit();
    }
    if (strtotime($dob) > time()) {
        echo "<script> alert('Invalid date of birth'); setTimeout(function(){ window.location.href = 'signup.php'; }, 100); </script>";
        exit();
    }
    if ($gen == "S") {
        echo "<script> alert('Please select gender'); setTimeout(function(){ window.location.href = 'signup.php'; }, 100); </script>";
        exit();
    }
    if (preg_match('/\D/', $cnic) || strlen($cnic) != 13) {
        echo "<script> alert('Invalid NID — must be 13 digits'); setTimeout(function(){ window.location.href = 'signup.php'; }, 100); </script>";
        exit();
    }
    if (preg_match('/\D/', $contact) || strlen($contact) != 11) {
        echo "<script> alert('Invalid contact — must be 11 digits'); setTimeout(function(){ window.location.href = 'signup.php'; }, 100); </script>";
        exit();
    }

    // ── Check if mail system is enabled ───────────────────────────────────────
    $ms_res = $con->query("SELECT * FROM mail_settings LIMIT 1");
    $mail_row = ($ms_res && $ms_res->num_rows > 0) ? $ms_res->fetch_assoc() : null;
    $mail_active = $mail_row && !empty($mail_row['mail_enabled']) && !empty($mail_row['mail_username']) && !empty($mail_row['mail_password']);

    // ── Generate token + expiry (1 hour for consistency) ──────────────────────
    $token = $mail_active ? bin2hex(random_bytes(32)) : null;
    $expiry = $mail_active ? date('Y-m-d H:i:s', time() + 3600) : null;
    $is_verified = $mail_active ? 0 : 1;

    $token_sql = $token ? "'$token'" : "NULL";
    $expiry_sql = $expiry ? "'$expiry'" : "NULL";

    // ── Insert account ────────────────────────────────────────────────────────
    $query = "INSERT INTO `accounts`
               (afname, alname, phone, email, cnic, dob, username, gender, password, user_role, is_verified, verify_token, token_expiry)
               VALUES ('$firstname','$lastname','$contact','$email','$cnic','$dob','$username','$gen','$password','user',$is_verified,$token_sql,$expiry_sql)";
    $result = mysqli_query($con, $query);

    if ($result) {
        if ($mail_active) {
            $sent = sendVerificationEmail($con, $web_settings, $_POST['email'], $firstname . ' ' . $lastname, $token);
            if ($sent) {
                $_SESSION['signup_email'] = htmlspecialchars($_POST['email']);
                echo "<script>
                    sessionStorage.setItem('signupEmail', '" . addslashes($_POST['email']) . "');
                    window.location.href = 'signup_pending.php';
                </script>";
            }
            else {
                echo "<script> alert('Mail server error. Your account has been auto-activated.'); window.location.href = 'login.php'; </script>";
                $new_aid = mysqli_insert_id($con);
                $con->query("UPDATE accounts SET is_verified=1 WHERE aid=$new_aid");
            }
        }
        else {
            echo "<script> alert('Registration Success! No verification needed.'); window.location.href = 'login.php'; </script>";
        }
    }
    else {
        echo "<script> alert('Database Error. Try again.'); </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SignUp | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <?php include('header.php'); ?>
    <form method="post" id="form">
        <input class="input1" name="firstName" type="text" placeholder="First Name" required>
        <input class="input1" name="lastName" type="text" placeholder="Last Name" required>
        <input class="input1" name="username" type="text" placeholder="User Name" required>
        <input class="input1" name="email" type="email" placeholder="Email" required>
        <input class="input1" name="cnic" type="text" placeholder="NID (13 Digits)" required>
        <input class="input1" name="phone" type="text" placeholder="Phone (11 Digits)" required>
        <input class="input1" name="dob" type="date" placeholder="Date of Birth" required>
        <select class="select1" name="gender">
            <option value="S">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Others">Others</option>
        </select>
        <input class="input1" name="password" type="password" placeholder="Password (Min 8 characters)" required>
        <input class="input1" name="confirmPassword" type="password" placeholder="Confirm Password" required>
        <button class="btn" name="submit">SignUp</button>
    </form>
    <?php include('footer.php'); ?>
</body>
</html>