<?php
include("include/auth.php");

// Delete User Handler
if (isset($_GET['del'])) {
    $aid = intval($_GET['del']);

    // Start transaction for data integrity
    mysqli_begin_transaction($con);

    try {
        // 1. Find all orders by this user
        $orderQuery = mysqli_query($con, "SELECT oid FROM orders WHERE aid = $aid");

        while ($order = mysqli_fetch_assoc($orderQuery)) {
            $oid = $order['oid'];
            // 2. Delete order details for each order
            mysqli_query($con, "DELETE FROM `order-details` WHERE oid = $oid");
            // 3. Delete reviews for each order
            mysqli_query($con, "DELETE FROM reviews WHERE oid = $oid");
        }

        // 4. Delete the orders
        mysqli_query($con, "DELETE FROM orders WHERE aid = $aid");

        // 5. Finally, delete the user account
        mysqli_query($con, "DELETE FROM accounts WHERE aid = $aid");

        mysqli_commit($con);
    } catch (Exception $e) {
        mysqli_rollback($con);
        // Optional: Set a session error message
    }

    header("Location: users.php");
    exit();
}

// Fetch users
$users = mysqli_query($con, "SELECT * FROM accounts WHERE username != 'admin1' ORDER BY aid DESC");

// Fetch current admin user info
$admin_username = $_SESSION['username'];
$admin_query = mysqli_query($con, "SELECT * FROM accounts WHERE username = '$admin_username'");
$admin_user = mysqli_fetch_assoc($admin_query);
$admin_img_path = 'img/users/' . $admin_user['profile_img'];
$admin_img = (!empty($admin_user['profile_img']) && file_exists($admin_img_path)) ? $admin_img_path : "https://ui-avatars.com/api/?name=" . urlencode($admin_user['afname'] . ' ' . $admin_user['alname']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | ByteBazaar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        .user-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e0e0e0;
        }

        .action-btn {
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 4px;
            /* Slightly smaller radius */
            font-size: 12px;
            margin-right: 4px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .view-btn {
            background: #17a2b8;
            color: white;
        }

        .view-btn:hover {
            background: #138496;
            transform: translateY(-2px);
        }

        .block-btn {
            background: #ffc107;
            color: white;
        }

        .block-btn.blocked {
            background: #6c757d;
            /* Grey for Unblock */
        }

        .block-btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Status Badge */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-blocked {
            background: #f8d7da;
            color: #721c24;
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
            animation: fadeIn 0.3s;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 0;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s;
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .close-modal {
            color: #aaa;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #333;
        }

        .modal-body {
            padding: 20px;
        }

        .user-detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .user-detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            width: 120px;
            font-weight: 600;
            color: #666;
        }

        .detail-value {
            flex: 1;
            color: #333;
        }

        .modal-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 15px;
            object-fit: cover;
            border: 3px solid #088178;
        }

        .modal-profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <?php include('admin_sidebar.php'); ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Users Management</h1>
            <div class="user-info">
                <span><?php echo $admin_user['username']; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <td>ID</td>
                        <td>Image</td>
                        <td>Name</td>
                        <td>Email</td>
                        <td>Phone</td>
                        <td>CNIC</td>
                        <td>Status</td> <!-- Added Status Column -->
                        <td>Joined</td>
                        <td>Action</td>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($users)) {
                        // Image Handling with Placeholder Fallback
                        $img_path = 'img/users/' . $row['profile_img'];
                        if (!empty($row['profile_img']) && file_exists($img_path)) {
                            $display_img = $img_path;
                        } else {
                            // Fallback to UI Avatars
                            $name = urlencode($row['afname'] . ' ' . $row['alname']);
                            $display_img = "https://ui-avatars.com/api/?name={$name}&background=random&color=fff";
                        }

                        $status = $row['status'] ?? 'Active'; // Default to Active
                        $statusClass = ($status == 'Blocked') ? 'status-blocked' : 'status-active';
                        $blockBtnText = ($status == 'Blocked') ? 'Unblock' : 'Block';
                        $blockBtnClass = ($status == 'Blocked') ? 'blocked' : '';
                        $blockIcon = ($status == 'Blocked') ? 'fa-unlock' : 'fa-ban';
                        $blockTitle = ($status == 'Blocked') ? 'Unblock User' : 'Block User';
                    ?>
                        <tr id="user-row-<?php echo $row['aid']; ?>"> <!-- Added ID for AJAX updates -->
                            <td>#<?php echo $row['aid']; ?></td>
                            <td><img src="<?php echo $display_img; ?>" alt="User" class="user-img"></td>
                            <td><?php echo $row['afname'] . ' ' . $row['alname']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo $row['cnic']; ?></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>" id="status-badge-<?php echo $row['aid']; ?>"><?php echo $status; ?></span></td>
                            <td><?php echo isset($row['created_at']) ? $row['created_at'] : 'N/A'; ?></td>
                            <td>
                                <!-- View Button -->
                                <button class="action-btn view-btn" onclick='viewUser(<?php echo json_encode($row); ?>, "<?php echo $display_img; ?>")' title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>

                                <!-- Block Button -->
                                <button class="action-btn block-btn <?php echo $blockBtnClass; ?>" id="block-btn-<?php echo $row['aid']; ?>" onclick="toggleBlock(<?php echo $row['aid']; ?>)" title="<?php echo $blockTitle; ?>">
                                    <i class="fas <?php echo $blockIcon; ?>"></i>
                                </button>

                                <!-- Delete Button -->
                                <a href="users.php?del=<?php echo $row['aid']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this user? This cannot be undone.');" title="Delete User">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- User View Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>User Details</h2>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="modal-profile-header">
                    <img src="" alt="User Avatar" id="modal-avatar" class="modal-avatar">
                    <h3 id="modal-name" style="margin: 0; color: #333;"></h3>
                    <span id="modal-username" style="color: #666; font-size: 14px;"></span>
                </div>

                <div class="user-detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value" id="modal-email"></span>
                </div>
                <div class="user-detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value" id="modal-phone"></span>
                </div>
                <div class="user-detail-row">
                    <span class="detail-label">CNIC:</span>
                    <span class="detail-value" id="modal-cnic"></span>
                </div>
                <div class="user-detail-row">
                    <span class="detail-label">Gender:</span>
                    <span class="detail-value" id="modal-gender"></span>
                </div>
                <div class="user-detail-row">
                    <span class="detail-label">Date of Birth:</span>
                    <span class="detail-value" id="modal-dob"></span>
                </div>
                <div class="user-detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value" id="modal-status"></span>
                </div>
                <div class="user-detail-row">
                    <span class="detail-label">Joined:</span>
                    <span class="detail-value" id="modal-joined"></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        const modal = document.getElementById('viewUserModal');

        function viewUser(user, imgSrc) {
            document.getElementById('modal-avatar').src = imgSrc;
            document.getElementById('modal-name').textContent = user.afname + ' ' + user.alname;
            document.getElementById('modal-username').textContent = '@' + user.username;
            document.getElementById('modal-email').textContent = user.email;
            document.getElementById('modal-phone').textContent = user.phone;
            document.getElementById('modal-cnic').textContent = user.cnic;
            document.getElementById('modal-gender').textContent = user.gender;
            document.getElementById('modal-dob').textContent = user.dob;
            document.getElementById('modal-status').textContent = user.status || 'Active';
            document.getElementById('modal-joined').textContent = user.created_at || 'N/A';

            modal.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        // Toggle Block Status
        function toggleBlock(aid) {
            const btn = document.getElementById('block-btn-' + aid);
            const badge = document.getElementById('status-badge-' + aid);

            // Determine current status based on button class or badge text
            const currentStatus = badge.textContent.trim();
            const newStatus = (currentStatus === 'Active') ? 'Blocked' : 'Active';

            if (!confirm(`Are you sure you want to ${newStatus === 'Blocked' ? 'block' : 'unblock'} this user?`)) {
                return;
            }

            // AJAX Request
            const formData = new FormData();
            formData.append('aid', aid);
            formData.append('status', newStatus);

            fetch('update_user_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        badge.textContent = newStatus;
                        btn.title = (newStatus === 'Blocked') ? 'Unblock User' : 'Block User';

                        if (newStatus === 'Blocked') {
                            badge.className = 'status-badge status-blocked';
                            btn.className = 'action-btn block-btn blocked';
                            btn.innerHTML = '<i class="fas fa-unlock"></i>';
                        } else {
                            badge.className = 'status-badge status-active';
                            btn.className = 'action-btn block-btn';
                            btn.innerHTML = '<i class="fas fa-ban"></i>';
                        }
                    } else {
                        alert('Failed to update status: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred properly.');
                });
        }
    </script>
</body>

</html>