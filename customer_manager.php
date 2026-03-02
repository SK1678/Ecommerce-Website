<?php
include("include/auth.php");

// Handle Delete User
if (isset($_GET['delete'])) {
    $aid = intval($_GET['delete']);

    // Start transaction for cascading deletes
    mysqli_begin_transaction($con);

    try {
        // Delete user's reviews
        mysqli_query($con, "DELETE FROM reviews WHERE aid = $aid");

        // Delete order details for user's orders
        $order_ids_result = mysqli_query($con, "SELECT oid FROM orders WHERE aid = $aid");
        while ($order_row = mysqli_fetch_assoc($order_ids_result)) {
            mysqli_query($con, "DELETE FROM order_details WHERE oid = " . $order_row['oid']);
        }

        // Delete user's orders
        mysqli_query($con, "DELETE FROM orders WHERE aid = $aid");

        // Delete the user account
        mysqli_query($con, "DELETE FROM accounts WHERE aid = $aid");

        mysqli_commit($con);
        header("Location: customer_manager.php");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($con);
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Check if user_role column exists
$check_role_col = mysqli_query($con, "SHOW COLUMNS FROM accounts LIKE 'user_role'");
$has_role_column = mysqli_num_rows($check_role_col) > 0;

// Fetch customers (users without admin roles)
if ($has_role_column) {
    // If user_role column exists, filter by user_role
    $customer_query = "SELECT * FROM accounts WHERE (user_role IS NULL OR user_role = 'user' OR user_role = '') AND username != 'admin1' ORDER BY aid DESC";
} else {
    // If no user_role column, get all users except admin1
    $customer_query = "SELECT * FROM accounts WHERE username != 'admin1' ORDER BY aid DESC";
}

$customers = mysqli_query($con, $customer_query);

// Check for query errors
if (!$customers) {
    die("Query failed: " . mysqli_error($con));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Manager | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 0 5px;
            transition: all 0.3s ease;
        }

        .btn-view {
            color: #2196F3;
        }

        .btn-view:hover {
            color: #0d47a1;
        }

        .btn-block {
            color: #ff9800;
        }

        .btn-block:hover {
            color: #e65100;
        }

        .btn-delete {
            color: #f44336;
        }

        .btn-delete:hover {
            color: #c62828;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }

        .pay-paid {
            background: #d1e7dd;
            color: #0f5132;
        }

        .pay-unpaid {
            background: #fff3cd;
            color: #664d03;
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
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .user-detail {
            margin: 15px 0;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .user-detail strong {
            display: inline-block;
            width: 120px;
            color: #333;
        }
    </style>
</head>

<body>

    <?php include('admin_sidebar.php'); ?>

    <div class="main-content">
        <div class="header">
            <h1>Customer Manager</h1>
            <div class="user-info">
                <span><?php echo $display_admin_name; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </div>
        </div>

        <div class="recent-orders">
            <h2>Customers (General Users)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Avatar</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($customer = mysqli_fetch_assoc($customers)) {
                        $cust_img_path = 'img/users/' . $customer['profile_img'];
                        $cust_img = (!empty($customer['profile_img']) && file_exists($cust_img_path)) ? $cust_img_path : "https://ui-avatars.com/api/?name=" . urlencode($customer['afname'] . ' ' . $customer['alname']) . "&background=random&color=fff";

                        $status = $customer['status'] ?? 'Active';
                        $statusClass = $status === 'Active' ? 'status-badge pay-paid' : 'status-badge pay-unpaid';
                    ?>
                        <tr id="user-<?php echo $customer['aid']; ?>">
                            <td><img src="<?php echo $cust_img; ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"></td>
                            <td><?php echo $customer['afname'] . ' ' . $customer['alname']; ?></td>
                            <td><?php echo $customer['username']; ?></td>
                            <td><?php echo $customer['email']; ?></td>
                            <td><span class="<?php echo $statusClass; ?>" id="status-<?php echo $customer['aid']; ?>"><?php echo $status; ?></span></td>
                            <td>
                                <button class="action-btn btn-view" onclick='viewUser(<?php echo json_encode($customer); ?>)' title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="action-btn btn-block" onclick="toggleStatus(<?php echo $customer['aid']; ?>, '<?php echo $status; ?>')" id="block-btn-<?php echo $customer['aid']; ?>" title="<?php echo $status === 'Active' ? 'Block' : 'Unblock'; ?>">
                                    <i class="fas fa-<?php echo $status === 'Active' ? 'ban' : 'check'; ?>"></i>
                                </button>
                                <a href="customer_manager.php?delete=<?php echo $customer['aid']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete this user and all associated data?')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="margin-bottom: 20px; color: var(--secondary-color);">Customer Details</h2>
            <div id="userDetails"></div>
        </div>
    </div>

    <script>
        function viewUser(user) {
            const modal = document.getElementById('userModal');
            const detailsDiv = document.getElementById('userDetails');

            const imgPath = user.profile_img ? 'img/users/' + user.profile_img : `https://ui-avatars.com/api/?name=${encodeURIComponent(user.afname + ' ' + user.alname)}&background=random&color=fff`;

            detailsDiv.innerHTML = `
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="${imgPath}" alt="Avatar" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                </div>
                <div class="user-detail"><strong>Name:</strong> ${user.afname} ${user.alname}</div>
                <div class="user-detail"><strong>Username:</strong> ${user.username}</div>
                <div class="user-detail"><strong>Email:</strong> ${user.email}</div>
                <div class="user-detail"><strong>Phone:</strong> ${user.phone || 'N/A'}</div>
                <div class="user-detail"><strong>Address:</strong> ${user.address || 'N/A'}</div>
                <div class="user-detail"><strong>Status:</strong> ${user.status || 'Active'}</div>
            `;

            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        function toggleStatus(aid, currentStatus) {
            const newStatus = currentStatus === 'Active' ? 'Blocked' : 'Active';

            fetch('update_user_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `aid=${aid}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const statusSpan = document.getElementById('status-' + aid);
                        const blockBtn = document.getElementById('block-btn-' + aid);

                        statusSpan.textContent = newStatus;
                        statusSpan.className = newStatus === 'Active' ? 'status-badge pay-paid' : 'status-badge pay-unpaid';

                        blockBtn.title = newStatus === 'Active' ? 'Block' : 'Unblock';
                        blockBtn.innerHTML = '<i class="fas fa-' + (newStatus === 'Active' ? 'ban' : 'check') + '"></i>';
                        blockBtn.setAttribute('onclick', `toggleStatus(${aid}, '${newStatus}')`);
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }

        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

</body>

</html>