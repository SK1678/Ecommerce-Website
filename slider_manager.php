<?php
include("include/auth.php");

// Create slider table if not exists with detailed columns
$con->query("CREATE TABLE IF NOT EXISTS `slider` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `image` varchar(255) NOT NULL,
    `title` varchar(255) DEFAULT '',
    `subtitle` varchar(255) DEFAULT '',
    `btn_text` varchar(100) DEFAULT 'Explore More',
    `btn_link` varchar(255) DEFAULT 'shop.php',
    `sort_order` int(11) DEFAULT 0,
    PRIMARY KEY (`id`)
)");

// Handler: Add Slider
if (isset($_POST['add_slider'])) {
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $btn_text = $_POST['btn_text'];
    $btn_link = $_POST['btn_link'];

    $image = $_FILES['slider_image']['name'];
    $temp_image = $_FILES['slider_image']['tmp_name'];

    // Ensure unique filename to prevent overwrites
    $target_file = "slider_images/" . time() . "_" . $image;

    if (move_uploaded_file($temp_image, $target_file)) {
        // Store just the filename/path relative to root or folder
        // Here we store path 'slider_images/filename'
        $db_image_path = $target_file;

        $stmt = $con->prepare("INSERT INTO slider (image, title, subtitle, btn_text, btn_link) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $db_image_path, $title, $subtitle, $btn_text, $btn_link);
        $stmt->execute();
        $msg = "Slider added successfully";
    } else {
        $error = "Failed to upload image.";
    }
}

// Handler: Delete Slider
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Get image path to delete file
    $res = $con->query("SELECT image FROM slider WHERE id=$id");
    if ($res->num_rows > 0) {
        $img = $res->fetch_assoc()['image'];
        if (file_exists($img)) unlink($img);
    }

    $con->query("DELETE FROM slider WHERE id=$id");
    header("Location: slider_manager.php");
    exit();
}

$sliders = $con->query("SELECT * FROM slider ORDER BY sort_order ASC, id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slider Manager | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
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

        .slider-item {
            display: flex;
            align-items: center;
            background: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .slider-preview {
            width: 150px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 20px;
            background: #f0f0f0;
        }

        .slider-info {
            flex: 1;
        }

        .slider-info h4 {
            margin: 0 0 5px;
            color: #333;
        }

        .slider-info p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .input-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-upload {
            background: var(--primary-color);
            color: white;
            width: 100%;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <?php include('admin_sidebar.php'); ?>

    <div class="main-content">
        <div class="header">
            <h1>Slider Management</h1>
            <div class="user-info">
                <span><?php echo $display_admin_name; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </div>
        </div>

        <div class="split-view">
            <!-- Add Form -->
            <div class="form-card">
                <h3>Add New Slider</h3>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="input-group">
                        <label>Image (Recommended 1920x600)</label>
                        <input type="file" name="slider_image" required accept="image/*">
                    </div>
                    <div class="input-group">
                        <label>Title (Optional)</label>
                        <input type="text" name="title" placeholder="e.g. Summer Sale">
                    </div>
                    <div class="input-group">
                        <label>Subtitle (Optional)</label>
                        <input type="text" name="subtitle" placeholder="e.g. Up to 70% Off">
                    </div>
                    <div class="input-group">
                        <label>Button Text (Optional)</label>
                        <input type="text" name="btn_text" value="Explore More">
                    </div>
                    <div class="input-group">
                        <label>Link (Optional)</label>
                        <input type="text" name="btn_link" value="shop.php">
                    </div>
                    <button type="submit" name="add_slider" class="btn-upload">Upload Slider</button>
                </form>
            </div>

            <!-- List -->
            <div class="list-card">
                <h3>Current Sliders</h3>
                <?php while ($row = $sliders->fetch_assoc()) { ?>
                    <div class="slider-item">
                        <img src="<?php echo $row['image']; ?>" class="slider-preview">
                        <div class="slider-info">
                            <h4><?php echo $row['title'] ? $row['title'] : '(No Title)'; ?></h4>
                            <p><?php echo $row['subtitle']; ?></p>
                        </div>
                        <a href="slider_manager.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this slider?')" style="color: #ff4444;"><i class="fas fa-trash"></i></a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

</body>

</html>