<?php
include("include/auth.php");

// --- Analytics Logic ---
$currentMonth = date('Y-m-01');
$prevMonth = date('Y-m-01', strtotime('-1 month'));

// 1. User Stats
$userCount = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM accounts"))['count'];

// 2. Revenue & Growth
$revData = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(total) as revenue FROM orders WHERE dateod >= '$currentMonth'"));
$currRev = $revData['revenue'] ?? 0;
$prevRevData = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(total) as revenue FROM orders WHERE dateod >= '$prevMonth' AND dateod < '$currentMonth'"));
$prevRev = $prevRevData['revenue'] ?? 0;
$revGrowth = ($prevRev > 0) ? (($currRev - $prevRev) / $prevRev) * 100 : 0;

// 3. Orders & Growth
$ordData = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM orders WHERE dateod >= '$currentMonth'"));
$currOrd = $ordData['count'] ?? 0;
$prevOrdData = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM orders WHERE dateod >= '$prevMonth' AND dateod < '$currentMonth'"));
$prevOrd = $prevOrdData['count'] ?? 0;
$ordGrowth = ($prevOrd > 0) ? (($currOrd - $prevOrd) / $prevOrd) * 100 : 0;

// 4. Products Total
$productCount = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM products"))['count'];
$totalRevenue = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(total) as total FROM orders"))['total'] ?? 0;

// 5. Top Selling Products
$topProductsQuery = "SELECT p.pname, p.img, SUM(od.qty) as sold, SUM(od.qty * p.price) as revenue 
                    FROM `order-details` od 
                    JOIN products p ON od.pid = p.pid 
                    GROUP BY p.pid 
                    ORDER BY sold DESC LIMIT 5";
$topProductsResult = mysqli_query($con, $topProductsQuery);

// 6. Revenue by Category
$catRevenueQuery = "SELECT category, SUM(od.qty * p.price) as revenue 
                   FROM `order-details` od 
                   JOIN products p ON od.pid = p.pid 
                   GROUP BY category 
                   ORDER BY revenue DESC";
$catRevenueResult = mysqli_query($con, $catRevenueQuery);

// Fetch Recent Orders (kept as is but limit refreshed)
$ordersQuery = "SELECT o.oid, o.dateod, o.total, o.status, o.payment_status, o.payment_method, o.account, a.afname, a.alname
FROM orders o
JOIN accounts a ON o.aid = a.aid
ORDER BY o.oid DESC LIMIT 5";
$ordersResult = mysqli_query($con, $ordersQuery);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        /* Status Badges */
        .recent-orders tr {
            cursor: pointer;
            transition: background 0.2s;
        }

        .recent-orders tr:hover {
            background-color: #f8f9fa !important;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .status-pending {
            background: #ffeeba;
            color: #856404;
        }

        .status-processing {
            background: #b8daff;
            color: #004085;
        }

        .status-shipped {
            background: #d6d8d9;
            color: #383d41;
        }

        .status-delivered {
            background: #c3e6cb;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* Payment Status */
        .pay-paid {
            background: #d1e7dd;
            color: #0f5132;
        }

        .pay-unpaid {
            background: #fff3cd;
            color: #664d03;
        }

        /* Analytics Specific Styles */
        .growth-indicator {
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .growth-up {
            color: #2e7d32;
        }

        .growth-down {
            color: #d32f2f;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 25px;
        }

        .analytics-card {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .analytics-card h2 {
            font-size: 16px;
            color: var(--secondary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .mini-pro-img {
            width: 35px;
            height: 35px;
            border-radius: 4px;
            object-fit: cover;
        }

        .cat-bar-container {
            margin-bottom: 15px;
        }

        .cat-info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .cat-bar {
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
        }

        .cat-fill {
            height: 100%;
            background: var(--primary-color);
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include('admin_sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="user-info">
                <span><?php echo $display_admin_name; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </div>
        </div>

        <div class="cards">
            <div class="card">
                <div class="card-info">
                    <h3><?php echo number_format($userCount); ?></h3>
                    <p>Total Users</p>
                    <div class="growth-indicator <?php echo ($currOrd >= $prevOrd) ? 'growth-up' : 'growth-down'; ?>">
                        <i class="fas fa-chart-line"></i> User Management Active
                    </div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>

            <div class="card">
                <div class="card-info">
                    <h3><?php echo number_format($currOrd); ?></h3>
                    <p>Monthly Orders</p>
                    <div class="growth-indicator <?php echo ($ordGrowth >= 0) ? 'growth-up' : 'growth-down'; ?>">
                        <i class="fas <?php echo ($ordGrowth >= 0) ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                        <?php echo number_format(abs($ordGrowth), 1); ?>% vs last month
                    </div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>

            <div class="card">
                <div class="card-info">
                    <h3><?php echo $web_settings['currency'] ?? '$'; ?><?php echo number_format($currRev, 2); ?></h3>
                    <p>Monthly Revenue</p>
                    <div class="growth-indicator <?php echo ($revGrowth >= 0) ? 'growth-up' : 'growth-down'; ?>">
                        <i class="fas <?php echo ($revGrowth >= 0) ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                        <?php echo number_format(abs($revGrowth), 1); ?>% vs last month
                    </div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
            </div>

            <div class="card">
                <div class="card-info">
                    <h3><?php echo $web_settings['currency'] ?? '$'; ?><?php echo number_format($totalRevenue); ?></h3>
                    <p>Total Lifetime Revenue</p>
                    <div class="growth-indicator growth-up">
                        <i class="fas fa-globe"></i> Global Sales
                    </div>
                </div>
                <div class="card-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>

        <div class="analytics-grid">
            <!-- Top Selling Products -->
            <div class="analytics-card">
                <h2><i class="fas fa-trophy" style="color: #ffc107;"></i> Top Selling Products</h2>
                <table style="font-size: 13px;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <td>Product</td>
                            <td style="text-align: center;">Sold</td>
                            <td style="text-align: right;">Revenue</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($prod = mysqli_fetch_assoc($topProductsResult)): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="product_images/<?php echo $prod['img']; ?>" class="mini-pro-img">
                                        <span><?php echo $prod['pname']; ?></span>
                                    </div>
                                </td>
                                <td style="text-align: center; font-weight: 600;"><?php echo $prod['sold']; ?></td>
                                <td style="text-align: right; font-weight: 600;"><?php echo $web_settings['currency'] ?? '$'; ?><?php echo number_format($prod['revenue'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Revenue by Category -->
            <div class="analytics-card">
                <h2><i class="fas fa-pie-chart" style="color: #088178;"></i> Category Performance</h2>
                <?php
                $maxCatRev = 1; // avoid division by zero
                $cats = [];
                while ($c = mysqli_fetch_assoc($catRevenueResult)) {
                    $cats[] = $c;
                    if ($c['revenue'] > $maxCatRev) $maxCatRev = $c['revenue'];
                }

                foreach ($cats as $cat):
                    $width = ($cat['revenue'] / $maxCatRev) * 100;
                ?>
                    <div class="cat-bar-container">
                        <div class="cat-info">
                            <span style="text-transform: capitalize; font-weight: 500;"><?php echo $cat['category']; ?></span>
                            <span><?php echo $web_settings['currency'] ?? '$'; ?><?php echo number_format($cat['revenue']); ?></span>
                        </div>
                        <div class="cat-bar">
                            <div class="cat-fill" style="width: <?php echo $width; ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="recent-orders">
            <h2>Recent Orders</h2>
            <table>
                <thead>
                    <tr>
                        <td>Order ID</td>
                        <td>Customer</td>
                        <td>Date</td>
                        <td>Total</td>
                        <td>Order Status</td>
                        <td>Payment Status</td>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($ordersResult)) {
                        // Order Status Logic
                        $status = $row['status'] ? $row['status'] : 'Pending';
                        // Map status to class
                        $statusClassMap = [
                            'Pending' => 'status-pending',
                            'Processing' => 'status-processing',
                            'Shipped' => 'status-shipped',
                            'Delivered' => 'status-delivered',
                            'Cancelled' => 'status-cancelled'
                        ];
                        $statusClass = $statusClassMap[$status] ?? 'status-pending';

                        // Payment Logic 
                        $payment_status_db = $row['payment_status'] ?? 'pending';
                        $payment_method_db = $row['payment_method'] ?? 'cod';

                        $isPaid = (!empty($row['account']) || strtolower($payment_status_db) == 'paid');
                        $payClass = $isPaid ? 'pay-paid' : 'pay-unpaid';

                        $channel = 'COD';
                        if ($payment_method_db == 'stripe') $channel = 'Stripe';
                        elseif ($payment_method_db == 'bkash') $channel = 'bKash';
                        elseif (!empty($row['account'])) $channel = 'Card';

                        $paymentStatus = $isPaid ? "Paid ($channel)" : "Unpaid ($channel)";

                        // If delivered, COD is assumed paid
                        if ($status === 'Delivered' && !$isPaid && $channel == 'COD') {
                            $paymentStatus = 'Paid (COD)';
                            $payClass = 'pay-paid';
                        }
                    ?>
                        <tr onclick="window.location.href='view_order.php?oid=<?php echo $row['oid']; ?>'">
                            <td>#<?php echo $row['oid']; ?></td>
                            <td><?php echo $row['afname'] . ' ' . $row['alname']; ?></td>
                            <td><?php echo $row['dateod']; ?></td>
                            <td><?php echo $web_settings['currency'] ?? '$'; ?><?php echo $row['total']; ?></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                            <td><span class="status-badge <?php echo $payClass; ?>"><?php echo $paymentStatus; ?></span></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>