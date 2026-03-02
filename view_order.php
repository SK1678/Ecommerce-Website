<?php
include("include/auth.php");

// Get order ID
if (!isset($_GET['oid'])) {
    header("Location: orders.php");
    exit();
}

$oid = intval($_GET['oid']);

// Fetch order details with customer info
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

// Fetch order items
$itemsQuery = "SELECT od.*, p.pname, p.img 
               FROM `order-details` od 
               JOIN products p ON od.pid = p.pid 
               WHERE od.oid = $oid";
$itemsResult = mysqli_query($con, $itemsQuery);

// Status badge classes
$statusClasses = [
    'Pending' => 'status-pending',
    'Processing' => 'status-processing',
    'Shipped' => 'status-shipped',
    'Delivered' => 'status-delivered',
    'Cancelled' => 'status-cancelled'
];
$status = $order['status'] ?? 'Pending';
$statusClass = $statusClasses[$status] ?? 'status-pending';

// Determine Payment Status for Watermark
$payment_status_db = $order['payment_status'] ?? 'pending';
$payment_method_db = $order['payment_method'] ?? 'cod';
$isPaid = (!empty($order['account']) || strtolower($payment_status_db) == 'paid');

// Channel logic
$channel = 'COD';
if ($payment_method_db == 'stripe') $channel = 'Stripe';
elseif ($payment_method_db == 'bkash') $channel = 'bKash';
elseif (!empty($order['account'])) $channel = 'Card';

// COD Delivered adjustment
if ($status === 'Delivered' && !$isPaid && $channel == 'COD') {
    $isPaid = true;
}

$watermarkText = $isPaid ? 'PAID' : 'UNPAID';
$watermarkClass = $isPaid ? 'watermark-paid' : 'watermark-unpaid';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order #<?php echo $oid; ?> | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
</head>

<body>

    <!-- Sidebar -->
    <?php include('admin_sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Order Details #<?php echo $oid; ?></h1>
            <div class="user-info">
                <span><?php echo $display_admin_name; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; margin: 20px 0; border-radius: 8px;">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message'];
                                                    unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 8px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message'];
                                                            unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="order-view-container">
            <div class="watermark <?php echo $watermarkClass; ?>">
                <?php echo $watermarkText; ?>
            </div>
            <!-- Action Buttons -->
            <div class="order-actions">
                <a href="orders.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
                <?php if ($status !== 'Delivered'): ?>
                    <a href="edit_order.php?oid=<?php echo $oid; ?>" class="btn-edit">
                        <i class="fas fa-edit"></i> Edit Order
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary" style="background: #6c757d; color: white; cursor: not-allowed; opacity: 0.7; padding: 10px 20px; border-radius: 6px; border: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px;" title="Delivered orders cannot be edited">
                        <i class="fas fa-lock"></i> Edit Locked
                    </button>
                <?php endif; ?>
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print"></i> Print Invoice
                </button>
            </div>

            <!-- Print Header -->
            <div class="invoice-header">
                <div class="company-info">
                    <img src="<?php echo !empty($web_settings['logo']) ? $web_settings['logo'] : 'img/logo.png'; ?>" alt="Logo" style="height: 50px; margin-bottom: 10px;">
                    <p><?php echo !empty($web_settings['site_tagline']) ? $web_settings['site_tagline'] : 'Premium Tech Hub'; ?></p>
                    <p>Email: <?php echo !empty($web_settings['email']) ? $web_settings['email'] : 'support@bytebazaar.com'; ?></p>
                </div>
                <div class="invoice-title" style="text-align: right;">
                    <h1 style="margin: 0; color: #333;">INVOICE</h1>
                    <p style="margin: 5px 0;">Order #<?php echo $oid; ?></p>
                    <p style="margin: 0; font-size: 14px; color: #666;"><?php echo date('F d, Y'); ?></p>
                </div>
            </div>

            <!-- Order Information -->

            <!-- Order Information Table -->
            <table class="invoice-details-table">
                <tr>
                    <td class="section-header" colspan="2"><i class="fas fa-user-circle"></i> Customer & Delivery Details</td>
                    <td class="section-header" colspan="2"><i class="fas fa-file-invoice"></i> Order & Payment Details</td>
                </tr>
                <tr>
                    <td class="label">Customer Name:</td>
                    <td class="value"><?php echo $order['afname'] . ' ' . $order['alname']; ?></td>
                    <td class="label">Order ID:</td>
                    <td class="value">#<?php echo $order['oid']; ?></td>
                </tr>
                <tr>
                    <td class="label">Email:</td>
                    <td class="value"><?php echo $order['email']; ?></td>
                    <td class="label">Order Date:</td>
                    <td class="value"><?php echo date('F d, Y', strtotime($order['dateod'])); ?></td>
                </tr>
                <tr>
                    <td class="label">Phone:</td>
                    <td class="value"><?php echo $order['phone']; ?></td>
                    <td class="label">Order Status:</td>
                    <td class="value"><span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                </tr>
                <tr>
                    <td class="label" style="vertical-align: top;">Address:</td>
                    <td class="value" style="vertical-align: top;">
                        <?php echo $order['address']; ?><br>
                        <?php echo $order['city']; ?>, <?php echo $order['country']; ?>
                    </td>
                    <td class="label">Payment Method:</td>
                    <td class="value">
                        <?php
                        $pm = $order['payment_method'] ?? 'cod';
                        if ($pm == 'stripe') {
                            echo 'Credit/Debit Card (Stripe)';
                        } elseif ($pm == 'bkash') {
                            echo 'bKash';
                        } elseif (!empty($order['account'])) {
                            echo 'Card (****' . substr($order['account'], -4) . ')';
                        } else {
                            echo 'Cash on Delivery';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td class="label">Payment Status:</td>
                    <td class="value">
                        <?php
                        if ($status === 'Delivered' && !$isPaid && $channel == 'COD') {
                            echo 'Paid (COD)';
                        } else {
                            echo $isPaid ? "Paid ($channel)" : "Unpaid ($channel)";
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td class="label">Delivery Date:</td>
                    <td class="value"><?php echo $order['datedel'] ? date('F d, Y', strtotime($order['datedel'])) : 'Not delivered yet'; ?></td>
                </tr>
            </table>

            <!-- Order Items -->
            <div class="order-items-section">
                <h3><i class="fas fa-shopping-cart"></i> Order Items</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subtotal = 0;
                        while ($item = mysqli_fetch_assoc($itemsResult)) {
                            $itemTotal = $item['price'] * $item['qty'];
                            $subtotal += $itemTotal;
                        ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="product_images/<?php echo $item['img']; ?>" style="width: 40px; height: 40px; border-radius: 4px; object-fit: contain; border: 1px solid #eee;">
                                        <?php echo $item['pname']; ?>
                                    </div>
                                </td>
                                <td><?php echo $web_settings['currency'] ?? '$'; ?><?php echo $item['price']; ?></td>
                                <td><?php echo $item['qty']; ?></td>
                                <td><?php echo $web_settings['currency'] ?? '$'; ?><?php echo $itemTotal; ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                            <td style="font-weight: bold; color: #088178; font-size: 18px;"><?php echo $web_settings['currency'] ?? '$'; ?><?php echo $order['total']; ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <style>
        .order-view-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-top: 20px;
        }

        .order-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .btn-back,
        .btn-edit {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back {
            background: #6c757d;
            color: white;
        }

        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-edit {
            background: #ffc107;
            color: white;
        }

        .btn-edit:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .order-info-grid {
            display: none;
        }

        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #088178 !important;
        }

        .order-items-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }

        .order-items-section h3 {
            color: #088178;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .items-table thead {
            background: #088178;
            color: white;
        }

        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .items-table tbody tr:hover {
            background: #e9ecef;
        }

        .product-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }

        .items-table tfoot {
            background: #f8f9fa;
            font-weight: bold;
        }

        /* Status badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-processing {
            background: #cfe2ff;
            color: #084298;
            border: 1px solid #9ec5fe;
        }

        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Print styles */
        .btn-print {
            background: #088178;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-print:hover {
            background: #066d63;
            transform: translateY(-2px);
        }

        .invoice-header {
            display: none;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #088178;
        }

        /* Watermark Styles */
        .watermark {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 8rem;
            font-weight: 700;
            border: 8px solid;
            padding: 10px 40px;
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
            user-select: none;
            display: none;
            /* Hide on screen by default, show on print */
            letter-spacing: 10px;
            text-transform: uppercase;
        }

        .watermark-paid {
            color: #28a745;
            border-color: #28a745;
        }

        .watermark-unpaid {
            color: #dc3545;
            border-color: #dc3545;
        }

        /* Invoice Table Styles - Global */
        .invoice-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            border: 1px solid #dee2e6;
        }

        .invoice-details-table td {
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            font-size: 14px;
        }

        .invoice-details-table .section-header {
            background-color: #f8f9fa;
            color: #088178;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 15px;
            border-bottom: 2px solid #088178;
        }

        .invoice-details-table .label {
            font-weight: 600;
            color: #555;
            width: 15%;
            background-color: #fafafa;
        }

        .invoice-details-table .value {
            color: #333;
            width: 35%;
        }

        @media print {
            body {
                background: white !important;
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .sidebar,
            .header,
            .order-actions,
            .user-info,
            .btn-back,
            .btn-edit,
            .btn-print,
            .alert,
            .order-info-grid {
                display: none !important;
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .order-view-container {
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                position: relative;
                overflow: hidden;
            }

            /* Show watermark in print */
            .watermark {
                display: block !important;
            }

            .invoice-header {
                display: flex !important;
                margin-bottom: 20px !important;
                padding-bottom: 10px !important;
                border-bottom: 2px solid #088178;
            }

            .invoice-title h1 {
                font-size: 32px;
                color: #088178 !important;
            }

            /* Print overrides for table */
            .invoice-details-table {
                border: 1px solid #ccc !important;
            }

            .invoice-details-table td {
                border: 1px solid #ccc !important;
                padding: 6px 10px !important;
            }

            .invoice-details-table .section-header {
                background-color: #eee !important;
                color: #000 !important;
                border-bottom: 2px solid #333 !important;
            }
        }
    </style>
</body>

</html>