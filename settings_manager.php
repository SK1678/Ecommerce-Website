<?php
include("include/auth.php");

// Create Settings Table
$con->query("CREATE TABLE IF NOT EXISTS `website_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `site_title` varchar(255) DEFAULT 'ByteBazaar',
    `site_tagline` varchar(255) DEFAULT 'Premium Tech Store',
    `logo` varchar(255) DEFAULT 'img/logo.png',
    `favicon` varchar(255) DEFAULT 'img/favicon.ico',
    `address` varchar(255) DEFAULT '',
    `phone` varchar(50) DEFAULT '',
    `email` varchar(100) DEFAULT '',
    `hours` varchar(100) DEFAULT '',
    `footer_about` text,
    `copyright` varchar(255) DEFAULT '2021. byteBazaar. HTML CSS',
    `currency` varchar(10) DEFAULT '$',
    `map_embed_url` text,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure site_title, site_tagline, and currency columns exist (for existing tables)
$con->query("ALTER TABLE `website_settings` ADD COLUMN IF NOT EXISTS `site_title` varchar(255) DEFAULT 'ByteBazaar' AFTER `id` ");
$con->query("ALTER TABLE `website_settings` ADD COLUMN IF NOT EXISTS `site_tagline` varchar(255) DEFAULT 'Premium Tech Store' AFTER `site_title` ");
$con->query("ALTER TABLE `website_settings` ADD COLUMN IF NOT EXISTS `currency` varchar(10) DEFAULT '$' AFTER `copyright` ");
$con->query("ALTER TABLE `website_settings` ADD COLUMN IF NOT EXISTS `map_embed_url` text AFTER `currency` ");

// Ensure one row exists
$check = $con->query("SELECT * FROM website_settings LIMIT 1");
if ($check->num_rows == 0) {
    $con->query("INSERT INTO website_settings (address, phone, hours, footer_about) VALUES ('Street 2, Johar Town Block A, Lahore', '+92324953752', '9am-5pm', 'Secured Payment Gateways')");
}

// Handler: Update Settings
if (isset($_POST['update_settings'])) {
    $site_title = $_POST['site_title'];
    $site_tagline = $_POST['site_tagline'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $hours = $_POST['hours'];
    $footer_about = $_POST['footer_about'];
    $copyright = $_POST['copyright'];
    $currency = $_POST['currency'];
    $map_embed_url = $_POST['map_embed_url'];

    // Logo Upload
    $logo_sql = "";
    if (!empty($_FILES['logo']['name'])) {
        $logo = "img/" . time() . "_logo_" . $_FILES['logo']['name'];
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo)) {
            $logo_sql = ", logo='$logo'";
        }
    }

    // Favicon Upload
    $fav_sql = "";
    if (!empty($_FILES['favicon']['name'])) {
        $fav = "img/" . time() . "_favicon_" . $_FILES['favicon']['name'];
        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $fav)) {
            $fav_sql = ", favicon='$fav'";
        }
    }

    $sql = "UPDATE website_settings SET site_title=?, site_tagline=?, address=?, phone=?, email=?, hours=?, footer_about=?, copyright=?, currency=?, map_embed_url=? $logo_sql $fav_sql WHERE id=1";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ssssssssss", $site_title, $site_tagline, $address, $phone, $email, $hours, $footer_about, $copyright, $currency, $map_embed_url);

    if ($stmt->execute()) {
        $msg = "Settings updated successfully!";
    } else {
        $error = "Failed to update settings.";
    }
}

$settings = $con->query("SELECT * FROM website_settings LIMIT 1")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Settings | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
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
            max-width: 800px;
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

        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .preview-img {
            max-height: 50px;
            margin-top: 10px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 4px;
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

        .row {
            display: flex;
            gap: 20px;
        }

        .col {
            flex: 1;
        }
    </style>
</head>

<body>

    <?php include('admin_sidebar.php'); ?>

    <div class="main-content">
        <div class="header">
            <h1>Website Settings</h1>
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
            <form action="" method="POST" enctype="multipart/form-data">

                <h3 style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;">General Branding</h3>

                <div class="row">
                    <div class="col input-group">
                        <label>Site Title</label>
                        <input type="text" name="site_title" value="<?php echo !empty($settings['site_title']) ? $settings['site_title'] : 'ByteBazaar'; ?>">
                    </div>
                    <div class="col input-group">
                        <label>Site Tagline</label>
                        <input type="text" name="site_tagline" value="<?php echo !empty($settings['site_tagline']) ? $settings['site_tagline'] : 'Premium Tech Store'; ?>">
                    </div>
                </div>

                <div class="input-group">
                    <label>Global Currency Symbol</label>
                    <input type="text" name="currency" value="<?php echo !empty($settings['currency']) ? $settings['currency'] : '$'; ?>" placeholder="$, €, £, rs, etc.">
                </div>

                <div class="row">
                    <div class="col input-group">
                        <label>Website Logo</label>
                        <input type="file" name="logo" accept="image/*">
                        <?php if (!empty($settings['logo'])) echo "<img src='{$settings['logo']}' class='preview-img'>"; ?>
                    </div>
                    <div class="col input-group">
                        <label>Favicon</label>
                        <input type="file" name="favicon" accept="image/*">
                        <?php if (!empty($settings['favicon'])) echo "<img src='{$settings['favicon']}' class='preview-img'>"; ?>
                    </div>
                </div>

                <h3 style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 20px;">Contact Information (Footer)</h3>

                <div class="input-group">
                    <label>Address</label>
                    <input type="text" name="address" value="<?php echo $settings['address']; ?>" placeholder="Street Address, City">
                </div>

                <div class="row">
                    <div class="col input-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" value="<?php echo $settings['phone']; ?>" placeholder="+123456789">
                    </div>
                    <div class="col input-group">
                        <label>Email Address</label>
                        <input type="text" name="email" value="<?php echo $settings['email']; ?>" placeholder="info@example.com">
                    </div>
                </div>

                <div class="input-group">
                    <label>Working Hours</label>
                    <input type="text" name="hours" value="<?php echo $settings['hours']; ?>" placeholder="Mon-Sat: 9am - 5pm">
                </div>

                <div class="input-group">
                    <label>Footer Short Text / About</label>
                    <textarea name="footer_about" rows="3"><?php echo $settings['footer_about']; ?></textarea>
                </div>

                <h3 style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 20px;">Contact Page Settings</h3>

                <div class="input-group">
                    <label>Google Maps Embed URL</label>
                    <textarea name="map_embed_url" rows="2" placeholder="Paste full iframe src URL from Google Maps (e.g., https://www.google.com/maps/embed?pb=...)"><?php echo $settings['map_embed_url']; ?></textarea>
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                        <i class="fas fa-info-circle"></i> Go to <a href="https://www.google.com/maps" target="_blank">Google Maps</a> → Search location → Share → Embed a map → Copy the <strong>src</strong> URL only
                    </small>
                </div>

                <div class="input-group">
                    <label>Copyright Text</label>
                    <input type="text" name="copyright" value="<?php echo $settings['copyright']; ?>">
                </div>

                <button type="submit" name="update_settings" class="btn-save">Save Settings</button>
            </form>
        </div>
    </div>

</body>

</html>