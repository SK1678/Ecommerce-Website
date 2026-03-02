<?php
include("include/auth.php");

// Fetch Orders based on filter
$filter = 'all';
$query = "SELECT orders.*, accounts.username, accounts.afname, accounts.alname FROM orders JOIN accounts ON orders.aid = accounts.aid ORDER BY orders.oid DESC";

if (isset($_GET['d'])) {
    $filter = 'delivered';
    $query = "SELECT orders.*, accounts.username, accounts.afname, accounts.alname FROM orders JOIN accounts ON orders.aid = accounts.aid WHERE orders.status = 'Delivered' ORDER BY orders.oid DESC";
} elseif (isset($_GET['u'])) {
    $filter = 'undelivered';
    $query = "SELECT orders.*, accounts.username, accounts.afname, accounts.alname FROM orders JOIN accounts ON orders.aid = accounts.aid WHERE orders.status != 'Delivered' OR orders.status IS NULL ORDER BY orders.oid DESC";
} elseif (isset($_GET['status'])) {
    $status = mysqli_real_escape_string($con, $_GET['status']);
    $filter = strtolower($status);
    $query = "SELECT orders.*, accounts.username, accounts.afname, accounts.alname FROM orders JOIN accounts ON orders.aid = accounts.aid WHERE orders.status = '$status' ORDER BY orders.oid DESC";
}

$result = mysqli_query($con, $query);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
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
            <h1>Order Management</h1>
            <div class="user-info">
                <span><?php echo $display_admin_name; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </div>
        </div>

        <div class="recent-orders">
            <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>All Orders</h2>
                <div class="btns">
                    <a href='orders.php' class="btn <?php echo $filter == 'all' ? 'active' : ''; ?>" style="margin-right: 10px; padding: 5px 10px; text-decoration: none; background: #088178; color: white; border-radius: 4px;">All</a>
                    <a href='orders.php?d=1' class="btn <?php echo $filter == 'delivered' ? 'active' : ''; ?>" style="margin-right: 10px; padding: 5px 10px; text-decoration: none; background: #088178; color: white; border-radius: 4px;">Delivered</a>
                    <a href='orders.php?u=1' class="btn <?php echo $filter == 'undelivered' ? 'active' : ''; ?>" style="padding: 5px 10px; text-decoration: none; background: #088178; color: white; border-radius: 4px;">Undelivered</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date Ordered</th>
                        <th>Date Delivered</th>
                        <th>Total</th>
                        <th>Address</th>
                        <th>Order Status</th>
                        <th>Payment Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) {
                        // Set default status if not set
                        $status = $row['status'] ?? 'Pending';
                        $datedel = $row['datedel'] ? $row['datedel'] : "-";

                        // Status badge classes
                        $statusClasses = [
                            'Pending' => 'status-pending',
                            'Processing' => 'status-processing',
                            'Shipped' => 'status-shipped',
                            'Delivered' => 'status-delivered',
                            'Cancelled' => 'status-cancelled'
                        ];
                        $statusClass = $statusClasses[$status] ?? 'status-pending';
                    ?>
                        <tr data-order-id="<?php echo $row['oid']; ?>">
                            <td>#<?php echo $row['oid']; ?></td>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo $row['dateod']; ?></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $datedel; ?></span></td>
                            <td><?php echo $web_settings['currency'] ?? '$'; ?><?php echo $row['total']; ?></td>
                            <td><?php echo $row['address']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                            </td>
                            <td>
                                <?php
                                $payment_status_db = $row['payment_status'] ?? 'pending';
                                $payment_method_db = $row['payment_method'] ?? 'cod';

                                $isPaid = (!empty($row['account']) || strtolower($payment_status_db) == 'paid');
                                $payClass = $isPaid ? 'pay-paid' : 'pay-unpaid';

                                $channel = 'COD';
                                if ($payment_method_db == 'stripe') $channel = 'Stripe';
                                elseif ($payment_method_db == 'bkash') $channel = 'bKash';
                                elseif (!empty($row['account'])) $channel = 'Card';

                                $paymentStatusValue = $isPaid ? "Paid ($channel)" : "Unpaid ($channel)";

                                // Auto-correct COD status if delivered
                                if ($status === 'Delivered' && !$isPaid && $channel == 'COD') {
                                    $paymentStatusValue = 'Paid (COD)';
                                    $payClass = 'pay-paid';
                                }
                                ?>
                                <span class="status-badge <?php echo $payClass; ?>"><?php echo $paymentStatusValue; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_order.php?oid=<?php echo $row['oid']; ?>" class="action-btn view-btn" title="View Order">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($status !== 'Delivered'): ?>
                                        <a href="edit_order.php?oid=<?php echo $row['oid']; ?>" class="action-btn edit-btn" title="Edit Order">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="action-btn delete-btn" data-order-id="<?php echo $row['oid']; ?>" title="Delete Order">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="action-btn lock-btn" title="Delivered orders cannot be edited or deleted">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>
        /* Status Badge Styles */
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

        /* Payment Status Badges */
        .pay-paid {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .pay-unpaid {
            background: #fff3cd;
            color: #664d03;
            border: 1px solid #ffecb5;
        }

        /* Action Buttons Styles */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .view-btn {
            background: #17a2b8;
            color: white;
        }

        .view-btn:hover {
            background: #138496;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
        }

        .edit-btn {
            background: #ffc107;
            color: white;
        }

        .edit-btn:hover {
            background: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.3);
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        .lock-btn {
            background: #6c757d;
            color: white;
            cursor: not-allowed;
            opacity: 0.7;
        }

        /* Status Dropdown Styles */
        .status-dropdown {
            padding: 8px 12px;
            border: 2px solid #088178;
            border-radius: 6px;
            background: white;
            color: #333;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            outline: none;
            min-width: 130px;
        }

        .status-dropdown:hover {
            background: #f0f9f8;
            border-color: #066d63;
        }

        .status-dropdown:focus {
            box-shadow: 0 0 0 3px rgba(8, 129, 120, 0.1);
            border-color: #088178;
        }

        /* Loading state */
        .status-dropdown.loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Success animation */
        @keyframes successPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .status-dropdown.success {
            animation: successPulse 0.3s ease;
        }

        /* Fade out animation for delete */
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }

            to {
                opacity: 0;
                transform: translateX(-20px);
            }
        }

        /* Notification styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 500;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease;
        }

        .notification.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .notification.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusDropdowns = document.querySelectorAll('.status-dropdown');

            statusDropdowns.forEach(dropdown => {
                dropdown.addEventListener('change', function() {
                    const orderId = this.dataset.orderId;
                    const newStatus = this.value;
                    const currentStatus = this.dataset.currentStatus;

                    // Confirm status change
                    if (!confirm(`Are you sure you want to change order #${orderId} status to "${newStatus}"?`)) {
                        this.value = currentStatus; // Revert to previous value
                        return;
                    }

                    // Add loading state
                    this.classList.add('loading');

                    // Send AJAX request
                    fetch('update_order_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `oid=${orderId}&status=${encodeURIComponent(newStatus)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            this.classList.remove('loading');

                            if (data.success) {
                                // Update current status
                                this.dataset.currentStatus = newStatus;
                                this.classList.add('success');
                                setTimeout(() => this.classList.remove('success'), 300);

                                // Update delivery date badge
                                const row = this.closest('tr');
                                const dateBadge = row.querySelector('.status-badge');

                                if (data.datedel) {
                                    dateBadge.textContent = data.datedel;
                                } else {
                                    dateBadge.textContent = '-';
                                }

                                // Update badge class
                                dateBadge.className = 'status-badge status-' + newStatus.toLowerCase();

                                // Show success notification
                                showNotification('Order status updated successfully!', 'success');
                            } else {
                                // Revert dropdown
                                this.value = currentStatus;
                                showNotification(data.message || 'Failed to update order status', 'error');
                            }
                        })
                        .catch(error => {
                            this.classList.remove('loading');
                            this.value = currentStatus;
                            showNotification('Network error. Please try again.', 'error');
                            console.error('Error:', error);
                        });
                });
            });

            // Delete Order Functionality
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const orderId = this.dataset.orderId;

                    if (confirm(`Are you sure you want to delete order #${orderId}? This action cannot be undone.`)) {
                        // Send delete request
                        fetch('delete_order.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `oid=${orderId}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Remove row with animation
                                    const row = this.closest('tr');
                                    row.style.animation = 'fadeOut 0.3s ease';
                                    setTimeout(() => row.remove(), 300);
                                    showNotification('Order deleted successfully!', 'success');
                                } else {
                                    showNotification(data.message || 'Failed to delete order', 'error');
                                }
                            })
                            .catch(error => {
                                showNotification('Network error. Please try again.', 'error');
                                console.error('Error:', error);
                            });
                    }
                });
            });

            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.textContent = message;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
        });
    </script>
</body>

</html>