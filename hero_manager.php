<?php
include("include/auth.php");

// Create hero table
$con->query("CREATE TABLE IF NOT EXISTS `hero` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bg_image` varchar(255) NOT NULL,
    `sub_title` varchar(255) DEFAULT '',
    `main_title` varchar(255) DEFAULT '',
    `big_title` varchar(255) DEFAULT '',
    `description` varchar(255) DEFAULT '',
    `btn_text` varchar(100) DEFAULT 'Shop Now',
    `btn_link` varchar(255) DEFAULT 'shop.php',
    `is_active` tinyint(1) DEFAULT 0,
    `page_name` varchar(50) DEFAULT 'index.php',
    PRIMARY KEY (`id`)
)");

// Add page_name column if missing (Migration)
$check_col = $con->query("SHOW COLUMNS FROM hero LIKE 'page_name'");
if ($check_col->num_rows == 0) {
    $con->query("ALTER TABLE hero ADD COLUMN page_name varchar(50) DEFAULT 'index.php'");
}


// Handler: Update Hero
if (isset($_POST['update_hero'])) {
    $id = intval($_POST['edit_id']);
    $sub_title = $_POST['sub_title'];
    $main_title = $_POST['main_title'];
    $big_title = $_POST['big_title'];
    $description = $_POST['description'];
    $btn_text = $_POST['btn_text'];
    $btn_link = $_POST['btn_link'];
    $page_name = $_POST['page_name'];

    // Optional Image Update
    if (!empty($_FILES['hero_image']['name'])) {
        $image = $_FILES['hero_image']['name'];
        $temp_image = $_FILES['hero_image']['tmp_name'];
        $target_file = "hero_images/" . time() . "_" . $image;
        if (move_uploaded_file($temp_image, $target_file)) {
            // Delete old
            $old = $con->query("SELECT bg_image FROM hero WHERE id=$id")->fetch_assoc();
            if (!empty($old['bg_image']) && file_exists($old['bg_image'])) unlink($old['bg_image']);

            $stmt = $con->prepare("UPDATE hero SET bg_image=?, sub_title=?, main_title=?, big_title=?, description=?, btn_text=?, btn_link=?, page_name=? WHERE id=?");
            $stmt->bind_param("ssssssssi", $target_file, $sub_title, $main_title, $big_title, $description, $btn_text, $btn_link, $page_name, $id);
            $stmt->execute();
        }
    } else {
        // Update without image
        $stmt = $con->prepare("UPDATE hero SET sub_title=?, main_title=?, big_title=?, description=?, btn_text=?, btn_link=?, page_name=? WHERE id=?");
        $stmt->bind_param("sssssssi", $sub_title, $main_title, $big_title, $description, $btn_text, $btn_link, $page_name, $id);
        $stmt->execute();
    }

    header("Location: hero_manager.php");
    exit();
}

// Fetch Edit Data
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit_data = $con->query("SELECT * FROM hero WHERE id=$id")->fetch_assoc();
}

// Handler: Add Hero
if (isset($_POST['add_hero'])) {
    $sub_title = $_POST['sub_title'];
    $main_title = $_POST['main_title'];
    $big_title = $_POST['big_title'];
    $description = $_POST['description'];
    $btn_text = $_POST['btn_text'];
    $btn_link = $_POST['btn_link'];
    $page_name = $_POST['page_name'];

    $image = $_FILES['hero_image']['name'];
    $temp_image = $_FILES['hero_image']['tmp_name'];

    $target_file = "hero_images/" . time() . "_" . $image;

    if (move_uploaded_file($temp_image, $target_file)) {
        $stmt = $con->prepare("INSERT INTO hero (bg_image, sub_title, main_title, big_title, description, btn_text, btn_link, page_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $target_file, $sub_title, $main_title, $big_title, $description, $btn_text, $btn_link, $page_name);
        $stmt->execute();
        $msg = "Hero section added successfully";
    } else {
        $error = "Failed to upload image.";
    }
}
?>
<?php
// Handler: Delete Hero
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $res = $con->query("SELECT bg_image FROM hero WHERE id=$id");
    if ($res->num_rows > 0) {
        $img = $res->fetch_assoc()['bg_image'];
        if (file_exists($img)) unlink($img);
    }
    $con->query("DELETE FROM hero WHERE id=$id");
    header("Location: hero_manager.php");
    exit();
}

// Handler: Activate Hero
if (isset($_GET['activate'])) {
    $id = intval($_GET['activate']);

    // Get page of this hero first
    $h = $con->query("SELECT page_name FROM hero WHERE id=$id")->fetch_assoc();
    $page = $h['page_name'];

    // Deactivate all FOR THIS PAGE
    $con->query("UPDATE hero SET is_active = 0 WHERE page_name = '$page'");
    // Activate selected
    $con->query("UPDATE hero SET is_active = 1 WHERE id = $id");
    header("Location: hero_manager.php");
    exit();
}

$heroes = $con->query("SELECT * FROM hero ORDER BY page_name ASC, id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hero Section Manager | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <!-- Favicon -->
    <link rel="icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/logo.png'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .split-view {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .form-card {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            height: fit-content;
        }

        .list-card {
            flex: 2;
        }

        .hero-item {
            display: flex;
            flex-direction: column;
            background: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            position: relative;
            border-left: 5px solid transparent;
        }

        .hero-item.active {
            border-left-color: var(--primary-color);
            background: #f0fdf4;
        }

        .hero-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .hero-details h4 {
            margin: 5px 0;
            color: #333;
        }

        .hero-details p {
            margin: 2px 0;
            font-size: 13px;
            color: #666;
        }

        .hero-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }

        .btn-action {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
        }

        .btn-active {
            background: var(--primary-color);
            color: white;
        }

        .btn-inactive {
            background: #ddd;
            color: #333;
        }

        .btn-delete {
            background: #fee2e2;
            color: #ef4444;
        }

        .input-group {
            margin-bottom: 10px;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 500;
        }

        .input-group input,
        .input-group textarea,
        .input-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>

<body>

    <?php include('admin_sidebar.php'); ?>

    <div class="main-content">
        <div class="header">
            <h1>Hero Section Manager</h1>
            <div class="user-info">
                <span><?php echo $display_admin_name; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </div>
        </div>

        <div class="split-view">
            <!-- Add/Edit Form -->
            <div class="form-card">
                <h3><?php echo $edit_data ? 'Edit Hero Design' : 'Add New Hero Design'; ?></h3>
                <form action="" method="POST" enctype="multipart/form-data">
                    <?php if ($edit_data) { ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                    <?php } ?>

                    <div class="input-group">
                        <label>Target Page</label>
                        <select name="page_name">
                            <?php $p = $edit_data ? $edit_data['page_name'] : 'index.php'; ?>
                            <option value="index.php" <?php echo $p == 'index.php' ? 'selected' : ''; ?>>Home Page (index.php)</option>
                            <option value="shop.php" <?php echo $p == 'shop.php' ? 'selected' : ''; ?>>Shop Page (shop.php)</option>
                            <option value="about.php" <?php echo $p == 'about.php' ? 'selected' : ''; ?>>About Page (about.php)</option>
                            <option value="contact.php" <?php echo $p == 'contact.php' ? 'selected' : ''; ?>>Contact Page (contact.php)</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Background Image <?php echo $edit_data ? '(Leave empty to keep current)' : ''; ?></label>
                        <input type="file" name="hero_image" <?php echo $edit_data ? '' : 'required'; ?> accept="image/*">
                        <?php if ($edit_data) echo "<small>Current: " . basename($edit_data['bg_image']) . "</small>"; ?>
                    </div>
                    <div class="input-group">
                        <label>Sub Title (Top small text)</label>
                        <input type="text" name="sub_title" value="<?php echo $edit_data['sub_title'] ?? ''; ?>" placeholder="e.g. Trade-in-offer">
                    </div>
                    <div class="input-group">
                        <label>Main Title (H2)</label>
                        <input type="text" name="main_title" value="<?php echo $edit_data['main_title'] ?? ''; ?>" placeholder="e.g. Super value deals">
                    </div>
                    <div class="input-group">
                        <label>Big Title (H1)</label>
                        <input type="text" name="big_title" value="<?php echo $edit_data['big_title'] ?? ''; ?>" placeholder="e.g. On all products">
                    </div>
                    <div class="input-group">
                        <label>Description</label>
                        <input type="text" name="description" value="<?php echo $edit_data['description'] ?? ''; ?>" placeholder="e.g. Save more with coupons...">
                    </div>
                    <div class="input-group">
                        <label>Button Text</label>
                        <input type="text" name="btn_text" value="<?php echo $edit_data['btn_text'] ?? 'Shop Now'; ?>">
                    </div>
                    <div class="input-group">
                        <label>Button Link</label>
                        <input type="text" name="btn_link" value="<?php echo $edit_data['btn_link'] ?? 'shop.php'; ?>">
                    </div>

                    <?php if ($edit_data) { ?>
                        <div style="display:flex; gap:10px;">
                            <button type="submit" name="update_hero" class="btn" style="flex:1; background: #2196F3; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer;">Update Hero</button>
                            <a href="hero_manager.php" class="btn" style="background: #757575; color: white; text-decoration:none; padding: 10px; border-radius: 4px; text-align:center;">Cancel</a>
                        </div>
                    <?php } else { ?>
                        <button type="submit" name="add_hero" class="btn" style="background: var(--primary-color); color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer;">Save Hero Section</button>
                    <?php } ?>
                </form>
            </div>

            <!-- List -->
            <div class="list-card">
                <h3>Saved Hero Designs</h3>
                <?php while ($row = $heroes->fetch_assoc()) {
                    $isActive = $row['is_active'] == 1;
                ?>
                    <div class="hero-item <?php echo $isActive ? 'active' : ''; ?>">
                        <img src="<?php echo $row['bg_image']; ?>" class="hero-preview">
                        <div class="hero-details">
                            <div style="margin-bottom: 5px;">
                                <span style="background: #333; color: white; font-size: 11px; padding: 2px 6px; border-radius: 4px;"><?php echo $row['page_name']; ?></span>
                                <?php if ($isActive) echo '<span style="font-size: 11px; background: #4caf50; color: white; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">ACTIVE</span>'; ?>
                            </div>
                            <h4><?php echo $row['main_title']; ?></h4>

                            <p><strong>Sub:</strong> <?php echo $row['sub_title']; ?></p>
                            <p><strong>Big:</strong> <?php echo $row['big_title']; ?></p>
                            <p><strong>Desc:</strong> <?php echo $row['description']; ?></p>
                        </div>
                        <div class="hero-actions">
                            <a href="hero_manager.php?edit=<?php echo $row['id']; ?>" class="btn-action" style="background: #2196F3; color: white;">Edit</a>
                            <?php if (!$isActive) { ?>
                                <a href="hero_manager.php?activate=<?php echo $row['id']; ?>" class="btn-action btn-active">Set as Active</a>
                            <?php } ?>
                            <a href="hero_manager.php?delete=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete this hero design?')">Delete</a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

</body>

</html>