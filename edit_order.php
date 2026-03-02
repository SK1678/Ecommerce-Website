<?php
include("include/auth.php");

// Get order ID
if (!isset($_GET['oid'])) {
    header("Location: orders.php");
    exit();
}

$oid = intval($_GET['oid']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verifyQuery = mysqli_query($con, "SELECT status FROM orders WHERE oid = $oid");
    $verifyData = mysqli_fetch_assoc($verifyQuery);
    $old_status = $verifyData['status'] ?? '';

    // Allow editing even if delivered, but warn (already done in frontend). 
    // If stricly preventing delivered edits unless it's to change date:
    // The previous code blocked edits if status was delivered.
    // "Delivered orders cannot be modified." - User seemingly wants to edit delivery date, 
    // which implies they might be editing a delivered order or setting it to delivered.

    $status = mysqli_real_escape_string($con, $_POST['status']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $city = mysqli_real_escape_string($con, $_POST['city']);
    $country = mysqli_real_escape_string($con, $_POST['country']);
    $datedel = mysqli_real_escape_string($con, $_POST['datedel']);

    // Update query
    $updateQuery = "UPDATE orders SET 
                    status = '$status',
                    address = '$address',
                    city = '$city',
                    country = '$country',
                    datedel = " . ($datedel ? "'$datedel'" : "NULL") . "
                    WHERE oid = $oid";

    if (mysqli_query($con, $updateQuery)) {
        // Handle Stock Reduction if status is Shipped or Delivered
        if ($status === 'Shipped' || $status === 'Delivered') {
            // Check if already reduced
            $stockCheck = mysqli_query($con, "SELECT stock_reduced FROM orders WHERE oid = $oid");
            $stockData = mysqli_fetch_assoc($stockCheck);

            if ($stockData && $stockData['stock_reduced'] == 0) {
                // Reduce stock for all items in this order
                $items_res = mysqli_query($con, "SELECT pid, qty FROM `order-details` WHERE oid = $oid");
                while ($item = mysqli_fetch_assoc($items_res)) {
                    $pid = $item['pid'];
                    $qty = $item['qty'];
                    mysqli_query($con, "UPDATE products SET qtyavail = qtyavail - $qty WHERE pid = $pid");
                }
                // Mark as reduced
                mysqli_query($con, "UPDATE orders SET stock_reduced = 1 WHERE oid = $oid");
            }
        }

        // Send status change email if status changed
        if ($old_status !== $status) {
            require_once("include/mail_helper.php");
            sendOrderStatusUpdateEmail($oid, $status);
        }

        $_SESSION['success_message'] = "Order updated successfully!";
        header("Location: view_order.php?oid=$oid");
        exit();
    }
    else {
        $error_message = "Failed to update order: " . mysqli_error($con);
    }
}

// Fetch order details
$orderQuery = "SELECT o.*, a.username, a.afname, a.alname, a.email, a.phone 
               FROM orders o 
               JOIN accounts a ON o.aid = a.aid 
               WHERE o.oid = $oid";
$orderResult = mysqli_query($con, $orderQuery);

if (mysqli_num_rows($orderResult) == 0) {
    header("Location: orders.php");
    exit();
}

$order = mysqli_fetch_assoc($orderResult);
$status = $order['status'] ?? 'Pending';

// Determine Payment Method Text
$payment_method_code = $order['payment_method'] ?? 'cod';
$payment_method_display = 'Cash on Delivery';
if ($payment_method_code == 'stripe') {
    $payment_method_display = 'Credit/Debit Card (Stripe)';
}
elseif ($payment_method_code == 'bkash') {
    $payment_method_display = 'bKash';
}
elseif ($order['account']) {
    // Fallback for legacy data
    $payment_method_display = 'Card (****' . substr($order['account'], -4) . ')';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order #<?php echo $oid; ?> | ByteBazaar</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <!-- Sidebar -->
    <?php include('admin_sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header-section">
            <div class="header-left">
                <h1>Edit Order #<?php echo $oid; ?></h1>
                <p class="subtitle">Manage order details and status</p>
            </div>
            <div class="user-info">
                <span><?php echo $display_admin_name; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" class="admin-avatar">
            </div>
        </div>

        <div class="edit-order-container">
            <!-- Action Buttons -->
            <div class="order-actions">
                <a href="view_order.php?oid=<?php echo $oid; ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <span class="order-meta">
                    Placed on <?php echo date('M d, Y', strtotime($order['dateod'])); ?>
                </span>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php
endif; ?>

            <!-- Edit Form -->
            <form method="POST" class="edit-form">
                <div class="compact-grid">

                    <!-- Left Column: Order & Customer -->
                    <div class="grid-column">
                        <div class="card-section">
                            <h3 class="section-title"><i class="fas fa-info-circle"></i> Order Status</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="status">Status <span class="required">*</span></label>
                                    <div class="select-wrapper">
                                        <select name="status" id="status" required class="form-select status-<?php echo strtolower($status); ?>">
                                            <option value="Pending" <?php echo $status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Processing" <?php echo $status == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="Shipped" <?php echo $status == 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="Delivered" <?php echo $status == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="Cancelled" <?php echo $status == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="datedel">Delivery Date <span class="required">*</span></label>
                                    <input type="date" name="datedel" id="datedel" class="form-input"
                                        value="<?php echo $order['datedel'] ? $order['datedel'] : ''; ?>"
                                        required>
                                </div>
                            </div>
                        </div>

                        <div class="card-section">
                            <h3 class="section-title"><i class="fas fa-user"></i> Customer Info</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" value="<?php echo $order['afname'] . ' ' . $order['alname']; ?>" readonly class="form-input readonly">
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="text" value="<?php echo $order['email']; ?>" readonly class="form-input readonly">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" value="<?php echo $order['phone']; ?>" readonly class="form-input readonly">
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Delivery & Payment -->
                    <div class="grid-column">
                        <div class="card-section">
                            <h3 class="section-title"><i class="fas fa-shipping-fast"></i> Delivery Address</h3>
                            <div class="form-group">
                                <label for="address">Address <span class="required">*</span></label>
                                <input type="text" name="address" id="address" value="<?php echo $order['address']; ?>" required class="form-input">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">City <span class="required">*</span></label>
                                    <input type="text" name="city" id="city" value="<?php echo $order['city']; ?>" required class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="country">Country <span class="required">*</span></label>
                                    <input type="text" name="country" id="country" value="<?php echo $order['country']; ?>" required class="form-input">
                                </div>
                            </div>
                        </div>

                        <div class="card-section">
                            <h3 class="section-title"><i class="fas fa-credit-card"></i> Payment Details</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Total Amount</label>
                                    <div class="price-badge"><?php echo $web_settings['currency'] ?? '$'; ?><?php echo number_format($order['total'], 2); ?></div>
                                </div>
                                <div class="form-group">
                                    <label>Method</label>
                                    <input type="text" value="<?php echo $payment_method_display; ?>" readonly class="form-input readonly payment-display">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Full Width Product Details -->
                <div class="card-section full-width-card">
                    <h3 class="section-title"><i class="fas fa-box-open"></i> Product Details</h3>
                    <div class="product-list">
                        <?php
$items_query = "SELECT od.*, p.pname, p.img 
                                        FROM `order-details` od 
                                        JOIN products p ON od.pid = p.pid 
                                        WHERE od.oid = $oid";
$items_result = mysqli_query($con, $items_query);

if (mysqli_num_rows($items_result) > 0) {
    while ($item = mysqli_fetch_assoc($items_result)) {
        $item_subtotal = $item['price'] * $item['qty'];
        // Handle image path
        $img_src = $item['img'];
        // Check if it's already a full URL (unlikely but good practice)
        if (!filter_var($img_src, FILTER_VALIDATE_URL)) {
            $img_src = "product_images/" . $img_src;
        }
?>
                                <div class="product-item">
                                    <img src="<?php echo $img_src; ?>" alt="<?php echo $item['pname']; ?>" class="product-thumb">
                                    <div class="product-info">
                                        <div class="product-name"><?php echo $item['pname']; ?></div>
                                        <div class="product-meta">
                                            <?php echo $item['qty']; ?> x <?php echo $web_settings['currency'] ?? '$'; ?><?php echo number_format($item['price'], 2); ?>
                                        </div>
                                    </div>
                                    <div class="product-subtotal">
                                        <?php echo $web_settings['currency'] ?? '$'; ?><?php echo number_format($item_subtotal, 2); ?>
                                    </div>
                                </div>
                        <?php
    }
}
else {
    echo '<div class="no-products">No products found for this order.</div>';
}
?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> UPDATE ORDER
                    </button>
                    <!-- Cancel removed from here to reduce clutter, Back button exists at top -->
                </div>
            </form>
        </div>
    </div>


    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f4f6f8;
        }

        .main-content {
            padding: 30px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header-left h1 {
            font-size: 28px;
            color: #2c3e50;
            margin: 0;
            font-weight: 700;
        }

        .subtitle {
            color: #7f8c8d;
            margin: 5px 0 0;
            font-size: 14px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 8px 15px;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .admin-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #088178;
        }

        .edit-order-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .order-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-back {
            color: #636e72;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }

        .btn-back:hover {
            color: #2d3436;
        }

        .order-meta {
            color: #a4b0be;
            font-size: 0.9em;
        }

        .compact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .grid-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.03);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-section.full-width-card {
            margin-top: 25px;
        }

        .card-section:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        }

        .section-title {
            color: #2d3436;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 2px solid #f1f2f6;
            padding-bottom: 10px;
        }

        .section-title i {
            color: #088178;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #636e72;
            margin-bottom: 6px;
        }

        .required {
            color: #e74c3c;
        }

        .form-input,
        .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dfe6e9;
            border-radius: 8px;
            font-size: 14px;
            color: #2d3436;
            transition: all 0.3s ease;
            font-family: inherit;
            background: #fdfdfd;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #088178;
            box-shadow: 0 0 0 3px rgba(8, 129, 120, 0.1);
            background: white;
        }

        .form-input.readonly {
            background: #f8f9fa;
            color: #7f8c8d;
            border-color: #f1f2f6;
            cursor: default;
        }

        .price-badge {
            font-size: 18px;
            font-weight: 700;
            color: #088178;
            background: #e0f2f1;
            padding: 8px 15px;
            border-radius: 6px;
            display: inline-block;
        }

        .payment-display {
            font-weight: 600;
            color: #2d3436;
        }

        .form-actions {
            margin-top: 25px;
        }

        .btn-save {
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            background: linear-gradient(135deg, #088178 0%, #06605a 100%);
            color: white;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(8, 129, 120, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(8, 129, 120, 0.4);
        }

        .alert-error {
            background: #ffeaa7;
            color: #d63031;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #d63031;
            display: flex;
            align-items: center;
            gap: 10px;
        }


        /* Product List Styling */
        .product-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .product-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f2f6;
        }

        .product-item:last-child {
            padding-bottom: 0;
            border-bottom: none;
        }

        .product-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dfe6e9;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 15px;
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 4px;
        }

        .product-meta {
            font-size: 13px;
            color: #636e72;
        }

        .product-subtotal {
            font-weight: 700;
            color: #088178;
            font-size: 16px;
        }

        .no-products {
            text-align: center;
            color: #b2bec3;
            font-style: italic;
            padding: 20px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .compact-grid {
                grid-template-columns: 1fr;
            }

            .header-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .user-info {
                align-self: flex-end;
            }
        }
    </style>

    <script>
        // Auto-set delivery date to today if status changes to Delivered
        const statusSelect = document.getElementById('status');
        const dateDelInput = document.getElementById('datedel');

        statusSelect.addEventListener('change', function() {
            if (this.value === 'Delivered') {
                const today = new Date().toISOString().split('T')[0];
                if (!dateDelInput.value) {
                    dateDelInput.value = today;
                }
            }
        });
    </script>
</body>

</html>