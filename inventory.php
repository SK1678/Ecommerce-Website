<?php
include("include/auth.php");

// --- DATABASE MIGRATION & SETUP ---

// 1. Create Pivot Tables
$con->query("CREATE TABLE IF NOT EXISTS `product_categories` (
    `pid` int(11) NOT NULL,
    `cat_id` int(11) NOT NULL,
    PRIMARY KEY (`pid`, `cat_id`)
)");

$con->query("CREATE TABLE IF NOT EXISTS `product_tags` (
    `pid` int(11) NOT NULL,
    `tag_id` int(11) NOT NULL,
    PRIMARY KEY (`pid`, `tag_id`)
)");

// 1.1 Add price column to order-details if not exists
$check_od = $con->query("SHOW COLUMNS FROM `order-details` LIKE 'price'");
if ($check_od->num_rows == 0) {
    $con->query("ALTER TABLE `order-details` ADD COLUMN `price` decimal(10,2) AFTER `pid` DEFAULT 0");
    // Backfill prices from products table for existing records
    $con->query("UPDATE `order-details` od JOIN products p ON od.pid = p.pid SET od.price = p.price WHERE od.price = 0");
}

// Add is_active column if not exists
$check_col = $con->query("SHOW COLUMNS FROM products LIKE 'is_active'");
if ($check_col->num_rows == 0) {
    $con->query("ALTER TABLE products ADD COLUMN is_active TINYINT(1) DEFAULT 1");
}

// Add stock_reduced column to orders if not exists
$check_col = $con->query("SHOW COLUMNS FROM orders LIKE 'stock_reduced'");
if ($check_col->num_rows == 0) {
    $con->query("ALTER TABLE orders ADD COLUMN stock_reduced TINYINT(1) DEFAULT 0");
}

// 2. Migration: Move 'category' string from products table to product_categories table
// We check if we have any data in product_categories. If empty, we migrate.
$check_mig = $con->query("SELECT count(*) as c FROM product_categories");
$row_mig = $check_mig->fetch_assoc();
if ($row_mig['c'] == 0) {
    $all_prods = $con->query("SELECT pid, category FROM products");
    while ($p = $all_prods->fetch_assoc()) {
        $cat_name = trim($p['category']);
        if (!empty($cat_name)) {
            // Find or Create Category ID
            $cat_res = $con->query("SELECT cat_id FROM categories WHERE cat_name = '$cat_name'");
            if ($cat_res->num_rows > 0) {
                $cat_id = $cat_res->fetch_assoc()['cat_id'];
            } else {
                $con->query("INSERT INTO categories (cat_name) VALUES ('$cat_name')");
                $cat_id = $con->insert_id;
            }
            // Insert into pivot
            $con->query("INSERT INTO product_categories (pid, cat_id) VALUES ({$p['pid']}, $cat_id)");
        }
    }
}

// --- HELPER FUNCTIONS ---

function getProductCategories($con, $pid)
{
    $ids = [];
    $res = $con->query("SELECT cat_id FROM product_categories WHERE pid = $pid");
    while ($r = $res->fetch_assoc()) $ids[] = $r['cat_id'];
    return $ids;
}

function getProductTags($con, $pid)
{
    $ids = [];
    $res = $con->query("SELECT tag_id FROM product_tags WHERE pid = $pid");
    while ($r = $res->fetch_assoc()) $ids[] = $r['tag_id'];
    return $ids;
}

function handleNewItems($con, $table, $col, $items_json)
{
    // items_json might contain new strings that need creating
    // This helper logic is tricky with the UI I plan.
    // Instead, I will handle "Create New" directly in the POST handler.
}

// --- HANDLERS ---

// Handler: Add New Product
if (isset($_POST['add_product'])) {
    $pname = $_POST['name'];
    // $category = $_POST['category']; // Legacy column, we might keep it for backward compat or ignore it.
    // Let's update the legacy columns for simple display, but real data is in pivot.
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];
    $brand = $_POST['brand'];
    $image = $_FILES['photo']['name'];
    $temp_image = $_FILES['photo']['tmp_name'];

    // Move Image
    if (!empty($image)) {
        move_uploaded_file($temp_image, "product_images/$image");
    }

    // Insert Product
    // We store the FIRST category in the legacy 'category' column just in case other pages break
    $cats_input = isset($_POST['categories']) ? $_POST['categories'] : []; // Array of IDs
    // Check for NEW categories (Format: "NEW:CategoryName")
    $final_cat_ids = [];
    foreach ($cats_input as $val) {
        if (strpos($val, "NEW:") === 0) {
            $new_name = substr($val, 4);
            $con->query("INSERT INTO categories (cat_name) VALUES ('$new_name')");
            $final_cat_ids[] = $con->insert_id;
        } else {
            $final_cat_ids[] = intval($val);
        }
    }

    $legacy_cat = "";
    if (count($final_cat_ids) > 0) {
        // Get name of first cat
        $first_id = $final_cat_ids[0];
        $r = $con->query("SELECT cat_name FROM categories WHERE cat_id = $first_id")->fetch_assoc();
        $legacy_cat = $r['cat_name'];
    }

    $query = "INSERT INTO `products`(pname, category, description, price, qtyavail, img, brand) VALUES ('$pname', '$legacy_cat', '$description', '$price', '$quantity', '$image', '$brand')";
    mysqli_query($con, $query);
    $pid = $con->insert_id;

    // Save Categories
    foreach ($final_cat_ids as $cid) {
        $con->query("INSERT INTO product_categories (pid, cat_id) VALUES ($pid, $cid)");
    }

    // Save Tags
    $tags_input = isset($_POST['tags']) ? $_POST['tags'] : [];
    foreach ($tags_input as $val) {
        if (strpos($val, "NEW:") === 0) {
            $new_name = substr($val, 4);
            $con->query("INSERT INTO tags (tag_name) VALUES ('$new_name')");
            $tag_id = $con->insert_id;
        } else {
            $tag_id = intval($val);
        }
        $con->query("INSERT INTO product_tags (pid, tag_id) VALUES ($pid, $tag_id)");
    }

    header("Location: inventory.php");
    exit();
}

// Handler: Update Product
if (isset($_POST['update_product'])) {
    $pid = $_POST['pid'];
    $pname = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $brand = $_POST['brand'];

    $image = $_FILES['photo']['name'];
    $temp_image = $_FILES['photo']['tmp_name'];

    $update_img_sql = "";
    if (!empty($image)) {
        move_uploaded_file($temp_image, "product_images/$image");
        $update_img_sql = ", img = '$image'";
    }

    // Categories Logic
    $cats_input = isset($_POST['categories']) ? $_POST['categories'] : [];
    $final_cat_ids = [];
    foreach ($cats_input as $val) {
        if (strpos($val, "NEW:") === 0) {
            $new_name = substr($val, 4);
            $con->query("INSERT INTO categories (cat_name) VALUES ('$new_name')");
            $final_cat_ids[] = $con->insert_id;
        } else {
            $final_cat_ids[] = intval($val);
        }
    }
    // Update Legacy Column
    $legacy_cat = "";
    if (count($final_cat_ids) > 0) {
        $first_id = $final_cat_ids[0];
        $r = $con->query("SELECT cat_name FROM categories WHERE cat_id = $first_id")->fetch_assoc();
        $legacy_cat = $r['cat_name'];
    }

    // Update Product Table
    $query = "UPDATE products SET pname='$pname', category='$legacy_cat', description='$description', price='$price', brand='$brand' $update_img_sql WHERE pid=$pid";
    mysqli_query($con, $query);

    // Update Pivots - Clear and Re-insert
    $con->query("DELETE FROM product_categories WHERE pid=$pid");
    foreach ($final_cat_ids as $cid) {
        $con->query("INSERT INTO product_categories (pid, cat_id) VALUES ($pid, $cid)");
    }

    // Tags Logic
    $tags_input = isset($_POST['tags']) ? $_POST['tags'] : [];
    $con->query("DELETE FROM product_tags WHERE pid=$pid");
    foreach ($tags_input as $val) {
        if (strpos($val, "NEW:") === 0) {
            $new_name = substr($val, 4);
            $con->query("INSERT INTO tags (tag_name) VALUES ('$new_name')");
            $tag_id = $con->insert_id;
        } else {
            $tag_id = intval($val);
        }
        $con->query("INSERT INTO product_tags (pid, tag_id) VALUES ($pid, $tag_id)");
    }

    header("Location: inventory.php");
    exit();
}

// Handler: Delete Product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Check if product has sales records (order details)
    $check_sales = $con->query("SELECT COUNT(*) as cases FROM `order-details` WHERE pid = $id");
    $sales_data = $check_sales->fetch_assoc();

    if ($sales_data['cases'] > 0) {
        echo "<script>alert('This product has sales records and cannot be deleted. Try blocking it instead.'); window.location.href='inventory.php';</script>";
        exit();
    }

    mysqli_query($con, "DELETE FROM products WHERE pid = '$id'");
    header("Location: inventory.php");
    exit();
}

// Handler: Stock
if (isset($_POST['update_stock'])) {
    $pid = $_POST['pid'];
    $amount = intval($_POST['amount']);
    $action = $_POST['action'];

    if ($amount > 0) {
        if ($action == 'in') {
            $query = "UPDATE products SET qtyavail = qtyavail + $amount WHERE pid = $pid";
        } elseif ($action == 'out') {
            $check = mysqli_fetch_assoc(mysqli_query($con, "SELECT qtyavail FROM products WHERE pid = $pid"));
            if ($check['qtyavail'] >= $amount) {
                $query = "UPDATE products SET qtyavail = qtyavail - $amount WHERE pid = $pid";
            } else {
                echo "<script>alert('Insufficient stock!'); window.location.href='inventory.php';</script>";
                exit();
            }
        }
        if (!empty($query)) mysqli_query($con, $query);
    }
    header("Location: inventory.php");
    exit();
}

// Handler: Toggle Active Status
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $curr = $con->query("SELECT is_active FROM products WHERE pid = $id")->fetch_assoc()['is_active'];
    $new = $curr == 1 ? 0 : 1;
    $con->query("UPDATE products SET is_active = $new WHERE pid = $id");
    header("Location: inventory.php");
    exit();
}

// Search & Filter
$search = $_GET['search'] ?? '';
$cat_filter = $_GET['cat'] ?? 'all';

$query = "SELECT * FROM products WHERE 1=1";
if (!empty($search)) {
    $query .= " AND (pname LIKE '%$search%' OR brand LIKE '%$search%')";
}
if ($cat_filter != 'all') {
    $query .= " AND category = '$cat_filter'";
}
$query .= " ORDER BY pid DESC";
$products = mysqli_query($con, $query);

// Fetch All Categories and Tags for JS
$all_cats = [];
$res = $con->query("SELECT * FROM categories ORDER BY cat_name");
while ($r = $res->fetch_assoc()) $all_cats[] = $r;

$all_tags = [];
$res = $con->query("SELECT * FROM tags ORDER BY tag_name");
while ($r = $res->fetch_assoc()) $all_tags[] = $r;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .inventory-layout {
            display: block;
        }

        .stock-control {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stock-control input {
            width: 50px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
        }

        .btn-in {
            background-color: #4caf50;
        }

        .btn-out {
            background-color: #f44336;
        }

        .product-img-small {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 30px;
            border: 1px solid #888;
            width: 90%;
            max-width: 800px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-full {
            grid-column: span 2;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        /* Multi Select UI */
        .multi-select-container {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            background: white;
            max-height: 150px;
            overflow-y: auto;
        }

        .multi-option {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }

        .multi-option input {
            width: auto;
        }

        .multi-option label {
            margin: 0;
            cursor: pointer;
            flex: 1;
            font-weight: 400;
        }

        .add-new-mini {
            display: flex;
            gap: 5px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .add-new-mini input {
            padding: 5px;
            font-size: 12px;
        }

        .add-new-mini button {
            padding: 5px 10px;
            background: #088178;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include('admin_sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Inventory Management</h1>
            <div class="user-info">
                <span><?php echo $display_admin_name; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </div>
        </div>

        <div class="inventory-layout">

            <div class="card" style="margin-bottom: 20px; padding: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <form action="inventory.php" method="GET" style="display: flex; gap: 10px; flex: 1; min-width: 300px;">
                    <input type="text" name="search" placeholder="Search by name or brand..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <select name="cat" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="all">All Categories</option>
                        <?php foreach ($all_cats as $c) { ?>
                            <option value="<?php echo $c['cat_name']; ?>" <?php echo $cat_filter == $c['cat_name'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst($c['cat_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <button type="submit" class="btn" style="background: var(--primary-color); color: white; border: none; padding: 0 20px; border-radius: 5px; cursor: pointer;">Filter</button>
                    <a href="categories.php" class="btn" style="background: var(--secondary-color); color: white; border: none; padding: 0 20px; border-radius: 5px; cursor: pointer; text-decoration: none; display: flex; align-items: center;">Categories</a>
                </form>

                <button onclick="openAddModal()" class="btn" style="background: var(--primary-color); color: white; border: none; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus"></i> Add New Product
                </button>
            </div>

            <div class="recent-orders">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Details</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Stock Action</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($products)) {
                            // Pre-fetch cats and tags for JS data attributes
                            $row_cats = getProductCategories($con, $row['pid']);
                            $row_tags = getProductTags($con, $row['pid']);

                            $is_active = isset($row['is_active']) ? $row['is_active'] : 1;
                            $status_icon = $is_active == 1 ? 'fa-ban' : 'fa-check';
                            $status_color = $is_active == 1 ? '#ff9800' : '#4caf50';
                            $status_title = $is_active == 1 ? 'Block' : 'Unblock';
                            $row_style = $is_active == 0 ? 'opacity: 0.6; background: #f9f9f9;' : '';
                        ?>
                            <tr style="<?php echo $row_style; ?>">
                                <td><img src="product_images/<?php echo $row['img']; ?>" class="product-img-small" alt=""></td>
                                <td>
                                    <strong><?php echo $row['pname']; ?></strong><br>
                                    <small><?php echo $row['brand']; ?> | <?php echo $row['category']; ?></small>
                                    <?php if ($is_active == 0) {
                                        echo '<br><span style="color:red; font-size:10px;">BLOCKED</span>';
                                    } ?>
                                </td>
                                <td><?php echo $web_settings['currency'] ?? '$'; ?><?php echo $row['price']; ?></td>
                                <td><span style="font-weight: bold; font-size: 16px;"><?php echo $row['qtyavail']; ?></span></td>
                                <td>
                                    <form action="inventory.php" method="POST" class="stock-control">
                                        <input type="hidden" name="pid" value="<?php echo $row['pid']; ?>">
                                        <input type="number" name="amount" min="1" value="1">
                                        <button type="submit" name="update_stock" value="in" class="btn-small btn-in" title="Stock In" onclick="this.form.action.value='in'"><i class="fas fa-plus"></i></button>
                                        <input type="hidden" name="action" id="action_<?php echo $row['pid']; ?>">
                                        <button type="submit" name="update_stock" value="out" class="btn-small btn-out" title="Stock Out" onclick="document.getElementById('action_<?php echo $row['pid']; ?>').value='out';"><i class="fas fa-minus"></i></button>
                                    </form>
                                </td>
                                <td>
                                    <a href="inventory.php?toggle_status=<?php echo $row['pid']; ?>" style="color: <?php echo $status_color; ?>; margin-right: 10px;" title="<?php echo $status_title; ?>" onclick="return confirm('Change status?')">
                                        <i class="fas <?php echo $status_icon; ?>"></i>
                                    </a>
                                    <button onclick='openEditModal(<?php echo json_encode($row); ?>, <?php echo json_encode($row_cats); ?>, <?php echo json_encode($row_tags); ?>)' style="background: none; border: none; cursor: pointer; color: #088178; font-size: 16px; margin-right: 10px;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php
                                    // Check if can delete
                                    $pid_check = $row['pid'];
                                    $has_sales = $con->query("SELECT pid FROM `order-details` WHERE pid = $pid_check LIMIT 1")->num_rows > 0;
                                    if (!$has_sales):
                                    ?>
                                        <a href="inventory.php?delete=<?php echo $row['pid']; ?>" style="color: #f44336;" onclick="return confirm('Are you sure?')"><i class="fas fa-trash"></i></a>
                                    <?php else: ?>
                                        <span title="Product has sales and cannot be deleted" style="color: #ccc; cursor: not-allowed;"><i class="fas fa-trash"></i></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- The Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle" style="margin-bottom: 20px; color: var(--secondary-color);">Add New Product</h2>

            <form action="inventory.php" method="POST" enctype="multipart/form-data" id="productForm">
                <input type="hidden" name="pid" id="edit_pid">
                <input type="hidden" name="add_product" id="action_add">
                <input type="hidden" name="update_product" id="action_update">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" id="p_name" required>
                    </div>

                    <div class="form-group">
                        <label>Brand</label>
                        <input type="text" name="brand" id="p_brand" required>
                    </div>

                    <div class="form-group">
                        <label>Price ($)</label>
                        <input type="number" name="price" id="p_price" required min="0">
                    </div>

                    <div class="form-group" id="qty_group">
                        <label>Initial Quantity</label>
                        <input type="number" name="quantity" id="p_quantity" min="0">
                    </div>

                    <!-- Categories Selector -->
                    <div class="form-group form-full">
                        <label>Categories (Select Multiple)</label>
                        <div class="multi-select-container" id="cat_container">
                            <!-- Populated via PHP initially -->
                            <?php foreach ($all_cats as $c) { ?>
                                <div class="multi-option">
                                    <input type="checkbox" name="categories[]" value="<?php echo $c['cat_id']; ?>" id="cat_<?php echo $c['cat_id']; ?>">
                                    <label for="cat_<?php echo $c['cat_id']; ?>"><?php echo htmlspecialchars($c['cat_name']); ?></label>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="add-new-mini">
                            <input type="text" id="new_cat_input" placeholder="Add new category...">
                            <button type="button" onclick="addNewItem('cat_container', 'new_cat_input', 'categories')">Add</button>
                        </div>
                    </div>

                    <!-- Tags Selector -->
                    <div class="form-group form-full">
                        <label>Tags (Select Multiple)</label>
                        <div class="multi-select-container" id="tag_container">
                            <?php foreach ($all_tags as $t) { ?>
                                <div class="multi-option">
                                    <input type="checkbox" name="tags[]" value="<?php echo $t['tag_id']; ?>" id="tag_<?php echo $t['tag_id']; ?>">
                                    <label for="tag_<?php echo $t['tag_id']; ?>">#<?php echo htmlspecialchars($t['tag_name']); ?></label>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="add-new-mini">
                            <input type="text" id="new_tag_input" placeholder="Add new tag...">
                            <button type="button" onclick="addNewItem('tag_container', 'new_tag_input', 'tags')">Add</button>
                        </div>
                    </div>

                    <div class="form-group form-full">
                        <label>Description</label>
                        <textarea name="description" id="p_desc" rows="3" required></textarea>
                    </div>

                    <div class="form-group form-full">
                        <label>Image</label>
                        <div id="current_img_container" style="display:none; margin-bottom: 10px;">
                            <img id="current_img_preview" src="" width="60" style="border-radius: 5px;">
                            <p style="font-size: 12px; color: #666;">Current Image (Upload new to replace)</p>
                        </div>
                        <input type="file" name="photo" id="p_file">
                    </div>

                    <div class="form-group form-full">
                        <button type="submit" class="btn" id="submitBtn" style="width: 100%; background: var(--primary-color); color: white; padding: 12px; border: none; border-radius: 5px; font-weight: 600; cursor: pointer;">
                            Add Product
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        var modal = document.getElementById("productModal");
        var form = document.getElementById("productForm");

        function addNewItem(containerId, inputId, nameAttr) {
            var input = document.getElementById(inputId);
            var val = input.value.trim();
            if (!val) return;

            var container = document.getElementById(containerId);
            var div = document.createElement('div');
            div.className = 'multi-option';

            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = nameAttr + '[]';
            checkbox.value = 'NEW:' + val; // Special prefix to handle creation
            checkbox.checked = true;

            var label = document.createElement('label');
            label.textContent = (nameAttr === 'tags' ? '#' : '') + val + ' (New)';

            div.appendChild(checkbox);
            div.appendChild(label);
            container.appendChild(div);

            input.value = '';

            // Scroll to bottom
            container.scrollTop = container.scrollHeight;
        }

        function clearCheckboxes(containerId) {
            var container = document.getElementById(containerId);
            var checkboxes = container.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(cb => cb.checked = false);

            // Remove dynamically added new items (simple cleanup)
            var dynamicItems = container.querySelectorAll('.multi-option');
            dynamicItems.forEach(item => {
                var val = item.querySelector('input').value;
                if (val.startsWith('NEW:')) {
                    item.remove();
                }
            });
        }

        function openAddModal() {
            modal.style.display = "block";
            document.getElementById("modalTitle").innerText = "Add New Product";
            document.getElementById("submitBtn").innerText = "Add Product";

            form.reset();
            clearCheckboxes('cat_container');
            clearCheckboxes('tag_container');

            document.getElementById("action_add").disabled = false;
            document.getElementById("action_update").disabled = true;
            document.getElementById("edit_pid").value = "";

            document.getElementById("qty_group").style.visibility = "visible";
            // note: using visibility or display, previous used display:none but flex grid might mess up if elements disappear. 
            // Let's use display block/none inside form-group logic if needed, but here simple hide is fine.
            // Actually let's just keep it visible but disabled/hidden if update.
            document.getElementById("qty_group").style.display = "block";
            document.getElementById("p_quantity").required = true;

            document.getElementById("p_file").required = true;
            document.getElementById("current_img_container").style.display = "none";
        }

        function openEditModal(data, cats, tags) {
            modal.style.display = "block";
            document.getElementById("modalTitle").innerText = "Edit Product";
            document.getElementById("submitBtn").innerText = "Update Product";

            form.reset();
            clearCheckboxes('cat_container');
            clearCheckboxes('tag_container');

            document.getElementById("action_add").disabled = true;
            document.getElementById("action_update").disabled = false;
            document.getElementById("edit_pid").value = data.pid;

            document.getElementById("p_name").value = data.pname;
            document.getElementById("p_brand").value = data.brand;
            document.getElementById("p_price").value = data.price;
            document.getElementById("p_desc").value = data.description;

            document.getElementById("qty_group").style.display = "none";
            document.getElementById("p_quantity").required = false;

            document.getElementById("p_file").required = false;

            document.getElementById("current_img_container").style.display = "block";
            document.getElementById("current_img_preview").src = "product_images/" + data.img;

            // Check Categories
            cats.forEach(id => {
                var cb = document.getElementById('cat_' + id);
                if (cb) cb.checked = true;
            });

            // Check Tags
            tags.forEach(id => {
                var cb = document.getElementById('tag_' + id);
                if (cb) cb.checked = true;
            });
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>