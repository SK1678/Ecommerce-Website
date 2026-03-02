<?php
session_start();
include_once("include/connect.php");

// 1. Basic session check
if (empty($_SESSION['aid']) || $_SESSION['aid'] < 0) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['aid'];

// Get order ID from URL
if (!isset($_GET['oid'])) {
    header("Location: profile.php");
    exit();
}

$oid = intval($_GET['oid']);

// Fetch order details and verify ownership
$order_query = "SELECT o.*, a.afname, a.alname, a.email, a.phone, a.profile_img 
                FROM orders o 
                JOIN accounts a ON o.aid = a.aid 
                WHERE o.oid = $oid AND o.aid = $user_id";
$order_result = mysqli_query($con, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    header("Location: profile.php");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Fetch order items with reviews
$items_query = "SELECT od.*, p.pname, p.img, r.rtext, r.rating
                FROM `order-details` od 
                JOIN products p ON od.pid = p.pid 
                LEFT JOIN reviews r ON od.oid = r.oid AND od.pid = r.pid
                WHERE od.oid = $oid";
$items_result = mysqli_query($con, $items_query);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?php echo $oid; ?> | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="profile_redesign.css">
    <style>
        .invoice-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
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

        /* Invoice Table Styles */
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

        .invoice-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .status-pill {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .detail-item h4 {
            color: #888;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .detail-item p {
            font-weight: 600;
            color: #333;
        }

        .item-table {
            width: 100%;
            border-collapse: collapse;
        }

        .item-table th {
            text-align: left;
            padding: 15px;
            background: #f8f9fa;
            color: #666;
            font-size: 13px;
        }

        .item-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .total-section {
            margin-top: 30px;
            text-align: right;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        @media print {

            #header,
            .profile-sidebar,
            footer,
            .btn-print-hide {
                display: none !important;
            }

            .profile-main {
                width: 100% !important;
                margin: 0 !important;
            }

            .invoice-card {
                box-shadow: none;
                padding: 0;
                border: none;
            }

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

            .watermark {
                display: block !important;
                opacity: 0.1 !important;
            }
        }
    </style>
</head>

<body>
    <?php include('header.php'); ?>

    <div class="profile-container">
        <!-- Sidebar -->
        <div class="profile-sidebar">
            <div class="profile-user">
                <div class="profile-img-container" style="margin-bottom: 15px;">
                    <img src="img/users/<?php echo $order['profile_img'] ?? 'default-avatar.png'; ?>"
                        style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;"
                        onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($order['afname'] . ' ' . $order['alname']); ?>&background=random&color=fff'">
                </div>
                <h3><?php echo $order['afname'] . ' ' . $order['alname']; ?></h3>
                <p style="color: #888;">Order #<?php echo $oid; ?></p>
            </div>

            <div class="sidebar-nav">
                <a href="profile.php" class="sidebar-link">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
                <button onclick="window.print()" class="sidebar-link btn-print-hide" style="width: 100%; border: none; font-size: 15px; font-weight: 500; font-family: inherit; margin-top: 10px; cursor: pointer; text-align: left;">
                    <i class="fas fa-print" style="margin-right: 10px; width: 20px;"></i> Print Invoice
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="profile-main">
            <div class="invoice-card">
                <div class="invoice-header">
                    <div>
                        <img src="<?php echo !empty($web_settings['logo']) ? $web_settings['logo'] : 'img/logo.png'; ?>" alt="Logo" style="height: 45px; margin-bottom: 10px;">
                        <p style="color: #888;"><?php echo !empty($web_settings['site_tagline']) ? $web_settings['site_tagline'] : 'Premium Tech Store'; ?></p>
                    </div>
                    <div style="text-align: right;">
                        <h3 style="margin-bottom: 5px;">Invoice</h3>
                        <p style="color: #888;">Order ID: #<?php echo $oid; ?></p>
                    </div>
                </div>

                <?php
                // Payment Status Logic for Watermark & Display
                $status = $order['status'] ?? 'Pending';

                // Status badge classes (mapped for table display if needed, though we use standard text/badge)
                $status_bg = "#f5f5f5";
                $status_text = "#666";
                switch ($status) {
                    case 'Pending':
                        $status_bg = "#ffeeba";
                        $status_text = "#856404";
                        break;
                    case 'Processing':
                        $status_bg = "#b8daff";
                        $status_text = "#004085";
                        break;
                    case 'Shipped':
                        $status_bg = "#d1ecf1";
                        $status_text = "#0c5460";
                        break;
                    case 'Delivered':
                        $status_bg = "#c3e6cb";
                        $status_text = "#155724";
                        break;
                    case 'Cancelled':
                        $status_bg = "#f8d7da";
                        $status_text = "#721c24";
                        break;
                }

                $payment_status_db = $order['payment_status'] ?? 'pending';
                $payment_method_db = $order['payment_method'] ?? 'cod';
                $isPaid = (!empty($order['account']) || strtolower($payment_status_db) == 'paid');

                $channel = 'COD';
                if ($payment_method_db == 'stripe') $channel = 'Stripe';
                elseif ($payment_method_db == 'bkash') $channel = 'bKash';
                elseif (!empty($order['account'])) $channel = 'Card';

                if ($status === 'Delivered' && !$isPaid && $channel == 'COD') {
                    $isPaid = true;
                }

                $watermarkText = $isPaid ? 'PAID' : 'UNPAID';
                $watermarkClass = $isPaid ? 'watermark-paid' : 'watermark-unpaid';
                ?>

                <div class="watermark <?php echo $watermarkClass; ?>">
                    <?php echo $watermarkText; ?>
                </div>

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
                        <td class="value">
                            <span class="status-pill" style="background: <?php echo $status_bg; ?>; color: <?php echo $status_text; ?>; padding: 4px 10px; font-size: 11px;">
                                <?php echo $status; ?>
                            </span>
                        </td>
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
                            if ($channel == 'Stripe') echo 'Credit/Debit Card (Stripe)';
                            elseif ($channel == 'bKash') echo 'bKash';
                            elseif ($channel == 'Card') echo 'Card (****' . substr($order['account'], -4) . ')';
                            else echo 'Cash on Delivery';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td></td> <td></td>                       <td class="label">Payment Status:</td>
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

                <table class="item-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align: center;">Price</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $grand_total = 0;
                        while ($item = mysqli_fetch_assoc($items_result)):
                            $subtotal = $item['price'] * $item['qty'];
                            $grand_total += $subtotal;
                        ?>
                            <tr>
                                <td style="vertical-align: top;">
                                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                                        <img src="product_images/<?php echo $item['img']; ?>" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover; border: 1px solid #eee;">
                                        <div>
                                            <span style="font-weight: 600; display: block; margin-bottom: 4px;"><?php echo $item['pname']; ?></span>
                                            <?php if (!empty($item['rtext'])): ?>
                                                <div style="background: #fdfae9; padding: 10px; border-radius: 6px; border-left: 3px solid #ffc107; margin-top: 8px;">
                                                    <div style="display: flex; gap: 3px; font-size: 10px; color: #ffc107; margin-bottom: 5px;">
                                                        <?php for ($i = 1; $i <= 5; $i++) echo $i <= $item['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                                    </div>
                                                    <p style="font-size: 12px; color: #666; font-style: italic; margin: 0;">&ldquo;<?php echo htmlspecialchars($item['rtext']); ?>&rdquo;</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; vertical-align: top;"><?php echo $web_settings['currency'] ?? '$'; ?><?php echo number_format($item['price'], 2); ?></td>
                                <td style="text-align: center; vertical-align: top;"><?php echo $item['qty']; ?></td>
                                <td style="text-align: right; font-weight: 600; vertical-align: top;"><?php echo $web_settings['currency'] ?? '$'; ?><?php echo number_format($subtotal, 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="total-section">
                    <h4 style="color: #888; font-size: 14px; margin-bottom: 5px;">Grand Total</h4>
                    <h2 style="color: #088178; font-size: 32px;"><?php echo $web_settings['currency'] ?? '$'; ?><?php echo number_format($grand_total, 2); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>
</body>

</html>