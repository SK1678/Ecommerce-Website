<?php
include("include/auth.php");

// Create Payment Settings Table
$con->query("CREATE TABLE IF NOT EXISTS `payment_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `stripe_enabled` tinyint(1) DEFAULT 0,
    `stripe_publishable_key` varchar(255) DEFAULT '',
    `stripe_secret_key` varchar(255) DEFAULT '',
    `bkash_enabled` tinyint(1) DEFAULT 0,
    `bkash_merchant_number` varchar(20) DEFAULT '',
    `bkash_api_key` varchar(255) DEFAULT '',
    `bkash_api_secret` varchar(255) DEFAULT '',
    `bkash_username` varchar(100) DEFAULT '',
    `bkash_password` varchar(100) DEFAULT '',
    `bkash_app_key` varchar(255) DEFAULT '',
    `cod_enabled` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure one row exists
$check = $con->query("SELECT * FROM payment_settings LIMIT 1");
if ($check->num_rows == 0) {
    $con->query("INSERT INTO payment_settings (stripe_enabled, bkash_enabled, cod_enabled) VALUES (0, 0, 1)");
}

// Handler: Update Payment Settings
if (isset($_POST['update_payment_settings'])) {
    $stripe_enabled = isset($_POST['stripe_enabled']) ? 1 : 0;
    $stripe_publishable_key = $_POST['stripe_publishable_key'];
    $stripe_secret_key = $_POST['stripe_secret_key'];

    $bkash_enabled = isset($_POST['bkash_enabled']) ? 1 : 0;
    $bkash_merchant_number = $_POST['bkash_merchant_number'];
    $bkash_api_key = $_POST['bkash_api_key'];
    $bkash_api_secret = $_POST['bkash_api_secret'];
    $bkash_username = $_POST['bkash_username'];
    $bkash_password = $_POST['bkash_password'];
    $bkash_app_key = $_POST['bkash_app_key'];

    $cod_enabled = isset($_POST['cod_enabled']) ? 1 : 0;

    $sql = "UPDATE payment_settings SET 
            stripe_enabled=?, stripe_publishable_key=?, stripe_secret_key=?,
            bkash_enabled=?, bkash_merchant_number=?, bkash_api_key=?, bkash_api_secret=?, bkash_username=?, bkash_password=?, bkash_app_key=?,
            cod_enabled=?
            WHERE id=1";
    $stmt = $con->prepare($sql);
    $stmt->bind_param(
        "issississsi",
        $stripe_enabled,
        $stripe_publishable_key,
        $stripe_secret_key,
        $bkash_enabled,
        $bkash_merchant_number,
        $bkash_api_key,
        $bkash_api_secret,
        $bkash_username,
        $bkash_password,
        $bkash_app_key,
        $cod_enabled
    );

    if ($stmt->execute()) {
        $msg = "Payment settings updated successfully!";
    } else {
        $error = "Failed to update payment settings.";
    }
}

$payment_settings = $con->query("SELECT * FROM payment_settings LIMIT 1")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway Settings | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <!-- Favicon -->
    <link rel="icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/logo.png'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            max-width: 900px;
            margin: 20px auto;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .input-group input[type="text"],
        .input-group input[type="password"],
        .input-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-save {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        .btn-save:hover {
            opacity: 0.9;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
        }

        .section-divider {
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #088178;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-divider h3 {
            margin: 0;
            color: #088178;
        }

        .section-divider .badge {
            background: #088178;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            display: block;
        }

        .gateway-logo {
            height: 30px;
            margin-left: 10px;
            vertical-align: middle;
        }

        .disabled-section {
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>

<body>

    <?php include('admin_sidebar.php'); ?>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-credit-card"></i> Payment Gateway Settings</h1>
            <div class="user-info">
                <span><?php echo $display_admin_name; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </div>
        </div>

        <?php if (isset($msg)) {
            echo "<div class='alert alert-success'>$msg</div>";
        } ?>
        <?php if (isset($error)) {
            echo "<div class='alert alert-error'>$error</div>";
        } ?>

        <div class="form-card">
            <form action="" method="POST">

                <!-- Cash on Delivery -->
                <div class="section-divider">
                    <h3><i class="fas fa-money-bill-wave"></i> Cash on Delivery</h3>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="cod_enabled" id="cod_enabled" <?php echo $payment_settings['cod_enabled'] ? 'checked' : ''; ?>>
                    <label for="cod_enabled">Enable Cash on Delivery</label>
                </div>

                <!-- Stripe Payment Gateway -->
                <div class="section-divider">
                    <h3><i class="fas fa-cc-stripe"></i> Stripe Payment (Visa/Mastercard)</h3>
                    <span class="badge">Free Tier Available</span>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="stripe_enabled" id="stripe_enabled" <?php echo $payment_settings['stripe_enabled'] ? 'checked' : ''; ?> onchange="toggleSection('stripe')">
                    <label for="stripe_enabled">Enable Stripe Payment Gateway</label>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg" class="gateway-logo" alt="Stripe">
                </div>

                <div id="stripe-section" class="<?php echo !$payment_settings['stripe_enabled'] ? 'disabled-section' : ''; ?>">
                    <div class="input-group">
                        <label>Stripe Publishable Key</label>
                        <input type="text" name="stripe_publishable_key" value="<?php echo $payment_settings['stripe_publishable_key']; ?>" placeholder="pk_test_...">
                        <small class="help-text"><i class="fas fa-info-circle"></i> Get your keys from <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard</a></small>
                    </div>

                    <div class="input-group">
                        <label>Stripe Secret Key</label>
                        <input type="password" name="stripe_secret_key" value="<?php echo $payment_settings['stripe_secret_key']; ?>" placeholder="sk_test_...">
                        <small class="help-text"><i class="fas fa-lock"></i> Keep this key confidential</small>
                    </div>
                </div>

                <!-- bKash Payment Gateway -->
                <div class="section-divider">
                    <h3><i class="fas fa-mobile-alt"></i> bKash Payment Gateway</h3>
                    <span class="badge">Bangladesh</span>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="bkash_enabled" id="bkash_enabled" <?php echo $payment_settings['bkash_enabled'] ? 'checked' : ''; ?> onchange="toggleSection('bkash')">
                    <label for="bkash_enabled">Enable bKash Payment Gateway</label>
                    <img src="https://seeklogo.com/images/B/bkash-logo-250D94B6A4-seeklogo.com.png" class="gateway-logo" alt="bKash">
                </div>

                <div id="bkash-section" class="<?php echo !$payment_settings['bkash_enabled'] ? 'disabled-section' : ''; ?>">
                    <div class="input-group">
                        <label>bKash Merchant Number</label>
                        <input type="text" name="bkash_merchant_number" value="<?php echo $payment_settings['bkash_merchant_number']; ?>" placeholder="01XXXXXXXXX">
                        <small class="help-text"><i class="fas fa-info-circle"></i> Your bKash merchant wallet number</small>
                    </div>

                    <div class="input-group">
                        <label>bKash App Key</label>
                        <input type="text" name="bkash_app_key" value="<?php echo $payment_settings['bkash_app_key']; ?>" placeholder="App Key from bKash">
                        <small class="help-text"><i class="fas fa-info-circle"></i> Get credentials from <a href="https://developer.bka.sh/" target="_blank">bKash Developer Portal</a></small>
                    </div>

                    <div class="input-group">
                        <label>bKash App Secret</label>
                        <input type="password" name="bkash_api_secret" value="<?php echo $payment_settings['bkash_api_secret']; ?>" placeholder="App Secret from bKash">
                    </div>

                    <div class="input-group">
                        <label>bKash Username</label>
                        <input type="text" name="bkash_username" value="<?php echo $payment_settings['bkash_username']; ?>" placeholder="Sandbox/Production username">
                    </div>

                    <div class="input-group">
                        <label>bKash Password</label>
                        <input type="password" name="bkash_password" value="<?php echo $payment_settings['bkash_password']; ?>" placeholder="Sandbox/Production password">
                    </div>
                </div>

                <button type="submit" name="update_payment_settings" class="btn-save">
                    <i class="fas fa-save"></i> Save Payment Settings
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleSection(gateway) {
            const section = document.getElementById(gateway + '-section');
            const checkbox = document.getElementById(gateway + '_enabled');

            if (checkbox.checked) {
                section.classList.remove('disabled-section');
            } else {
                section.classList.add('disabled-section');
            }
        }
    </script>

</body>

</html>