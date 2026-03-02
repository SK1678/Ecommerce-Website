<?php
include("include/auth.php");

// Create features table
$con->query("CREATE TABLE IF NOT EXISTS `features` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(100) NOT NULL,
    `image` varchar(255) NOT NULL,
    `bg_color` varchar(20) DEFAULT '#fddde4',
    PRIMARY KEY (`id`)
)");

// Handler: Add Feature
if (isset($_POST['add_feature'])) {
    $title = $_POST['title'];
    $bg_color = $_POST['bg_color'];

    $image = $_FILES['feature_image']['name'];
    $temp_image = $_FILES['feature_image']['tmp_name'];

    // Create directory if not exists
    if (!file_exists('img/features')) {
        mkdir('img/features', 0777, true);
    }

    $target_file = "img/features/" . time() . "_" . $image;

    if (move_uploaded_file($temp_image, $target_file)) {
        $stmt = $con->prepare("INSERT INTO features (title, image, bg_color) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $target_file, $bg_color);
        $stmt->execute();
        header("Location: feature_manager.php");
        exit();
    } else {
        $error = "Failed to upload image.";
    }
}

// Handler: Update Feature
if (isset($_POST['update_feature'])) {
    $id = intval($_POST['edit_id']);
    $title = $_POST['title'];
    $bg_color = $_POST['bg_color'];

    if (!empty($_FILES['feature_image']['name'])) {
        $image = $_FILES['feature_image']['name'];
        $temp_image = $_FILES['feature_image']['tmp_name'];
        $target_file = "img/features/" . time() . "_" . $image;

        if (move_uploaded_file($temp_image, $target_file)) {
            // Delete old
            $old = $con->query("SELECT image FROM features WHERE id=$id")->fetch_assoc();
            if (!empty($old['image']) && file_exists($old['image'])) unlink($old['image']);

            $stmt = $con->prepare("UPDATE features SET title=?, image=?, bg_color=? WHERE id=?");
            $stmt->bind_param("sssi", $title, $target_file, $bg_color, $id);
            $stmt->execute();
        }
    } else {
        $stmt = $con->prepare("UPDATE features SET title=?, bg_color=? WHERE id=?");
        $stmt->bind_param("ssi", $title, $bg_color, $id);
        $stmt->execute();
    }
    header("Location: feature_manager.php");
    exit();
}

// Handler: Delete Feature
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $res = $con->query("SELECT image FROM features WHERE id=$id");
    if ($res->num_rows > 0) {
        $img = $res->fetch_assoc()['image'];
        if (file_exists($img)) unlink($img);
    }
    $con->query("DELETE FROM features WHERE id=$id");
    header("Location: feature_manager.php");
    exit();
}

// Fetch Edit Data
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit_data = $con->query("SELECT * FROM features WHERE id=$id")->fetch_assoc();
}

$features = $con->query("SELECT * FROM features ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature Manager | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
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

        .fe-item {
            display: flex;
            align-items: center;
            background: white;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            gap: 15px;
        }

        .fe-preview {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 4px;
            background: #f9f9f9;
        }

        .fe-details h4 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .color-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            vertical-align: middle;
            border: 1px solid #ddd;
        }

        .fe-actions {
            margin-left: auto;
            display: flex;
            gap: 10px;
        }

        .btn-action {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
        }

        .btn-edit {
            background: #2196F3;
            color: white;
        }

        .btn-delete {
            background: #fee2e2;
            color: #ef4444;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 13px;
            font-weight: 500;
        }

        .input-group input {
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
            <h1>Feature Manager</h1>
            <div class="user-info">
                <span><?php echo $display_admin_name; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </div>
        </div>

        <div class="split-view">
            <!-- Form -->
            <div class="form-card">
                <h3><?php echo $edit_data ? 'Edit Feature' : 'Add New Feature'; ?></h3>
                <form action="" method="POST" enctype="multipart/form-data">
                    <?php if ($edit_data) { ?>
                        <input type="hidden" name="edit_id" value="<?php echo $edit_data['id']; ?>">
                    <?php } ?>

                    <div class="input-group">
                        <label>Title</label>
                        <input type="text" name="title" required value="<?php echo $edit_data['title'] ?? ''; ?>" placeholder="e.g. Free Shipping">
                    </div>

                    <div class="input-group">
                        <label>Icon/Image <?php echo $edit_data ? '(Leave empty to keep)' : ''; ?></label>
                        <input type="file" name="feature_image" <?php echo $edit_data ? '' : 'required'; ?> accept="image/*">
                        <?php if ($edit_data) echo "<img src='" . $edit_data['image'] . "' height='50' style='margin-top:5px;'>"; ?>
                    </div>

                    <div class="input-group">
                        <label>Tag Background Color</label>
                        <input type="color" name="bg_color" value="<?php echo $edit_data['bg_color'] ?? '#fddde4'; ?>" style="height: 40px; padding: 2px;">
                    </div>

                    <?php if ($edit_data) { ?>
                        <div style="display:flex; gap:10px;">
                            <button type="submit" name="update_feature" class="btn" style="flex:1; background: #2196F3; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer;">Update</button>
                            <a href="feature_manager.php" class="btn" style="background: #757575; color: white; text-decoration:none; padding: 10px; border-radius: 4px; text-align:center;">Cancel</a>
                        </div>
                    <?php } else { ?>
                        <button type="submit" name="add_feature" class="btn" style="background: var(--primary-color); color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer;">Add Feature</button>
                    <?php } ?>
                </form>
            </div>

            <!-- List -->
            <div class="list-card">
                <h3>Current Features</h3>
                <?php while ($row = $features->fetch_assoc()) { ?>
                    <div class="fe-item">
                        <img src="<?php echo $row['image']; ?>" class="fe-preview">
                        <div class="fe-details">
                            <h4><?php echo $row['title']; ?></h4>
                            <div><span class="color-dot" style="background: <?php echo $row['bg_color']; ?>;"></span> <?php echo $row['bg_color']; ?></div>
                        </div>
                        <div class="fe-actions">
                            <a href="feature_manager.php?edit=<?php echo $row['id']; ?>" class="btn-action btn-edit">Edit</a>
                            <a href="feature_manager.php?delete=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Delete?')">Delete</a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

</body>

</html>