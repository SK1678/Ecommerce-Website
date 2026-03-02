<?php
include("include/auth.php");

// Create tables if they don't exist (Auto-migration/Setup)
$create_cats_table = "CREATE TABLE IF NOT EXISTS `categories` (
    `cat_id` int(11) NOT NULL AUTO_INCREMENT,
    `cat_name` varchar(100) NOT NULL UNIQUE,
    PRIMARY KEY (`cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($con, $create_cats_table);

$create_tags_table = "CREATE TABLE IF NOT EXISTS `tags` (
    `tag_id` int(11) NOT NULL AUTO_INCREMENT,
    `tag_name` varchar(100) NOT NULL UNIQUE,
    PRIMARY KEY (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($con, $create_tags_table);

// Sync existing categories from products table to categories table (one-time sync logic)
$existing_cats_query = mysqli_query($con, "SELECT DISTINCT category FROM products");
while ($row = mysqli_fetch_assoc($existing_cats_query)) {
    $c = $row['category'];
    if (!empty($c)) {
        mysqli_query($con, "INSERT IGNORE INTO categories (cat_name) VALUES ('$c')");
    }
}

// Handler: Add Category
if (isset($_POST['add_category'])) {
    $cat_name = trim($_POST['cat_name']);
    if (!empty($cat_name)) {
        $query = "INSERT INTO categories (cat_name) VALUES ('$cat_name')";
        if (mysqli_query($con, $query)) {
            $msg = "Category added successfully!";
        } else {
            $error = "Error adding category (might already exist).";
        }
    }
}

// Handler: Update Category
if (isset($_POST['update_category'])) {
    $cat_id = $_POST['cat_id'];
    $cat_name = trim($_POST['cat_name']);
    if (!empty($cat_name)) {
        $query = "UPDATE categories SET cat_name='$cat_name' WHERE cat_id=$cat_id";
        if (mysqli_query($con, $query)) {
            $msg = "Category updated successfully!";
        } else {
            $error = "Error updating category.";
        }
    }
}

// Handler: Delete Category
if (isset($_GET['del_cat'])) {
    $id = $_GET['del_cat'];
    mysqli_query($con, "DELETE FROM categories WHERE cat_id = $id");
    header("Location: categories.php");
    exit();
}

// Handler: Add Tag
if (isset($_POST['add_tag'])) {
    $tag_name = trim($_POST['tag_name']);
    if (!empty($tag_name)) {
        $query = "INSERT INTO tags (tag_name) VALUES ('$tag_name')";
        if (mysqli_query($con, $query)) {
            $msg = "Tag added successfully!";
        } else {
            $error = "Error adding tag (might already exist).";
        }
    }
}

// Handler: Update Tag
if (isset($_POST['update_tag'])) {
    $tag_id = $_POST['tag_id'];
    $tag_name = trim($_POST['tag_name']);
    if (!empty($tag_name)) {
        $query = "UPDATE tags SET tag_name='$tag_name' WHERE tag_id=$tag_id";
        if (mysqli_query($con, $query)) {
            $msg = "Tag updated successfully!";
        } else {
            $error = "Error updating tag.";
        }
    }
}

// Handler: Delete Tag
if (isset($_GET['del_tag'])) {
    $id = $_GET['del_tag'];
    mysqli_query($con, "DELETE FROM tags WHERE tag_id = $id");
    header("Location: categories.php");
    exit();
}

$categories = mysqli_query($con, "SELECT * FROM categories ORDER BY cat_name");
$tags = mysqli_query($con, "SELECT * FROM tags ORDER BY tag_name");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories & Tags | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <!-- Favicon -->
    <link rel="icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/logo.png'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .split-layout {
            display: flex;
            gap: 30px;
        }

        .manager-card {
            flex: 1;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .manager-card h2 {
            margin-bottom: 20px;
            color: var(--secondary-color);
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .add-form {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .add-form input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .list-group {
            list-style: none;
        }

        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }

        .list-item:hover {
            background: #f9f9f9;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .delete-btn {
            color: #ff4444;
            cursor: pointer;
            transition: color 0.2s;
        }

        .edit-btn {
            color: #2196f3;
            cursor: pointer;
            transition: color 0.2s;
        }

        .delete-btn:hover {
            color: #cc0000;
        }

        .edit-btn:hover {
            color: #0d47a1;
        }

        .tag-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include('admin_sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="inventory.php" style="text-decoration: none; color: #666; font-size: 18px;"><i class="fas fa-arrow-left"></i> Back</a>
                <h1>Category & Tag Manager</h1>
            </div>
            <div class="user-info">
                <span><?php echo $display_admin_name; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </div>
        </div>

        <?php if (isset($error)) {
            echo "<div style='background: #ffebee; color: #c62828; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>$error</div>";
        } ?>
        <?php if (isset($msg)) {
            echo "<div style='background: #e8f5e9; color: #2e7d32; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>$msg</div>";
        } ?>

        <div class="split-layout">

            <!-- Category Manager -->
            <div class="manager-card">
                <h2>Categories</h2>
                <form method="POST" class="add-form" id="cat-form">
                    <input type="hidden" name="cat_id" id="cat-id-input">
                    <input type="text" name="cat_name" id="cat-name-input" placeholder="New Category Name..." required>
                    <button type="submit" name="add_category" id="cat-btn" class="btn" style="background: var(--primary-color); color: white; border: none; padding: 0 20px; border-radius: 5px; cursor: pointer;">Add</button>
                    <button type="button" id="cat-cancel" onclick="cancelEditCat()" style="display:none; background: #999; color: white; border: none; padding: 0 15px; border-radius: 5px; cursor: pointer;">X</button>
                </form>

                <ul class="list-group">
                    <?php while ($cat = mysqli_fetch_assoc($categories)) { ?>
                        <li class="list-item">
                            <span style="font-weight: 500;"><?php echo htmlspecialchars($cat['cat_name']); ?></span>
                            <div class="action-btns">
                                <span class="edit-btn" onclick="editCategory(<?php echo $cat['cat_id']; ?>, '<?php echo addslashes($cat['cat_name']); ?>')"><i class="fas fa-edit"></i></span>
                                <a href="categories.php?del_cat=<?php echo $cat['cat_id']; ?>" class="delete-btn" onclick="return confirm('Delete this category?');"><i class="fas fa-trash"></i></a>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </div>

            <!-- Tag Manager -->
            <div class="manager-card">
                <h2>Tags</h2>
                <form method="POST" class="add-form" id="tag-form">
                    <input type="hidden" name="tag_id" id="tag-id-input">
                    <input type="text" name="tag_name" id="tag-name-input" placeholder="New Tag Name..." required>
                    <button type="submit" name="add_tag" id="tag-btn" class="btn" style="background: var(--primary-color); color: white; border: none; padding: 0 20px; border-radius: 5px; cursor: pointer;">Add</button>
                    <button type="button" id="tag-cancel" onclick="cancelEditTag()" style="display:none; background: #999; color: white; border: none; padding: 0 15px; border-radius: 5px; cursor: pointer;">X</button>
                </form>

                <ul class="list-group">
                    <?php if (mysqli_num_rows($tags) > 0) {
                        while ($tag = mysqli_fetch_assoc($tags)) { ?>
                            <li class="list-item">
                                <span class="tag-badge">#<?php echo htmlspecialchars($tag['tag_name']); ?></span>
                                <div class="action-btns">
                                    <span class="edit-btn" onclick="editTag(<?php echo $tag['tag_id']; ?>, '<?php echo addslashes($tag['tag_name']); ?>')"><i class="fas fa-edit"></i></span>
                                    <a href="categories.php?del_tag=<?php echo $tag['tag_id']; ?>" class="delete-btn" onclick="return confirm('Delete this tag?');"><i class="fas fa-trash"></i></a>
                                </div>
                            </li>
                    <?php }
                    } else {
                        echo "<li class='list-item' style='color: #888;'>No tags created yet.</li>";
                    } ?>
                </ul>
            </div>

        </div>
    </div>

    <script>
        function editCategory(id, name) {
            document.getElementById('cat-id-input').value = id;
            document.getElementById('cat-name-input').value = name;

            var btn = document.getElementById('cat-btn');
            btn.innerText = "Update";
            btn.name = "update_category";
            btn.style.background = "#ff9800";

            document.getElementById('cat-cancel').style.display = 'block';
        }

        function cancelEditCat() {
            document.getElementById('cat-form').reset();
            document.getElementById('cat-id-input').value = '';

            var btn = document.getElementById('cat-btn');
            btn.innerText = "Add";
            btn.name = "add_category";
            btn.style.background = "var(--primary-color)";

            document.getElementById('cat-cancel').style.display = 'none';
        }

        function editTag(id, name) {
            document.getElementById('tag-id-input').value = id;
            document.getElementById('tag-name-input').value = name;

            var btn = document.getElementById('tag-btn');
            btn.innerText = "Update";
            btn.name = "update_tag";
            btn.style.background = "#ff9800";

            document.getElementById('tag-cancel').style.display = 'block';
        }

        function cancelEditTag() {
            document.getElementById('tag-form').reset();
            document.getElementById('tag-id-input').value = '';

            var btn = document.getElementById('tag-btn');
            btn.innerText = "Add";
            btn.name = "add_tag";
            btn.style.background = "var(--primary-color)";

            document.getElementById('tag-cancel').style.display = 'none';
        }
    </script>

</body>

</html>