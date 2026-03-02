<?php
include("include/auth.php");

// Check if user_role column exists
$check_role_col = mysqli_query($con, "SHOW COLUMNS FROM accounts LIKE 'user_role'");
$has_role_column = mysqli_num_rows($check_role_col) > 0;

// Make cnic, dob, and gender nullable (for employee creation without these fields)
$con->query("ALTER TABLE accounts MODIFY COLUMN cnic char(13) NULL DEFAULT NULL");
$con->query("ALTER TABLE accounts MODIFY COLUMN dob date NULL DEFAULT NULL");
$con->query("ALTER TABLE accounts MODIFY COLUMN gender varchar(10) NULL DEFAULT NULL");

// Handle Add/Edit Employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_employee'])) {
    $aid = isset($_POST['aid']) ? intval($_POST['aid']) : null;
    $fname = mysqli_real_escape_string($con, $_POST['afname']);
    $lname = mysqli_real_escape_string($con, $_POST['alname']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $user_role = mysqli_real_escape_string($con, $_POST['user_role']);

    if ($aid) {
        // Update
        $query = "UPDATE accounts SET afname=?, alname=?, email=?, phone=?, username=?, user_role=? WHERE aid=?";
        $stmt = $con->prepare($query);
        $stmt->bind_param("ssssssi", $fname, $lname, $email, $phone, $username, $user_role, $aid);
    } else {
        // Add - explicitly set cnic, dob, gender to NULL
        $password = mysqli_real_escape_string($con, $_POST['password']);
        $query = "INSERT INTO accounts (afname, alname, email, phone, username, password, user_role, status, cnic, dob, gender) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', NULL, NULL, NULL)";
        $stmt = $con->prepare($query);
        $stmt->bind_param("sssssss", $fname, $lname, $email, $phone, $username, $password, $user_role);
    }

    if ($stmt->execute()) {
        header("Location: employee_manager.php?success=1");
        exit();
    } else {
        $error = "Error: " . $stmt->error;
    }
}

// Fetch employees (admin, superadmin, operator roles)
if ($has_role_column) {
    // If user_role column exists, filter by user_role
    $employee_query = "SELECT * FROM accounts WHERE user_role IN ('admin', 'superadmin', 'operator') ORDER BY aid DESC";
} else {
    // If no user_role column, only show admin1 user
    $employee_query = "SELECT * FROM accounts WHERE username = 'admin1' ORDER BY aid DESC";
}

$employees = mysqli_query($con, $employee_query);

// Check for query errors
if (!$employees) {
    die("Query failed: " . mysqli_error($con));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Manager | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .role-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-admin {
            background: #e3f2fd;
            color: #1565c0;
        }

        .role-superadmin {
            background: #f3e5f5;
            color: #6a1b9a;
        }

        .role-operator {
            background: #fff3e0;
            color: #e65100;
        }

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

        .btn-edit {
            color: #4caf50;
        }

        .btn-block {
            color: #ff9800;
        }

        .btn-delete {
            color: #f44336;
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
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            margin-top: -10px;
        }

        .input-group {
            margin-bottom: 0;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn-save {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
            margin-top: 20px;
            grid-column: span 2;
        }

        .user-detail {
            margin: 0;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .user-detail strong {
            color: #888;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .user-detail span {
            color: #333;
            font-weight: 500;
            font-size: 15px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-add {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            cursor: pointer;
            border: none;
        }
    </style>
</head>

<body>

    <?php include('admin_sidebar.php'); ?>

    <div class="main-content">
        <div class="header">
            <h1>Employee Manager</h1>
            <div class="user-info">
                <span><?php echo $display_admin_name; ?></span>
                <img src="<?php echo $admin_img; ?>" alt="Admin" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </div>
        </div>

        <div class="recent-orders">
            <div class="section-header">
                <h2>Employees (Admin, Superadmin, Operator)</h2>
                <button class="btn-add" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Employee
                </button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Avatar</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($emp = mysqli_fetch_assoc($employees)) {
                        $emp_img_path = 'img/users/' . $emp['profile_img'];
                        $emp_img = (!empty($emp['profile_img']) && file_exists($emp_img_path)) ? $emp_img_path : "https://ui-avatars.com/api/?name=" . urlencode($emp['afname'] . ' ' . $emp['alname']) . "&background=random&color=fff";

                        $role = $emp['user_role'] ?? 'user';
                        $roleClass = 'role-' . strtolower($role);

                        $status = $emp['status'] ?? 'Active';
                        $statusClass = $status === 'Active' ? 'status-badge pay-paid' : 'status-badge pay-unpaid';
                    ?>
                        <tr id="emp-<?php echo $emp['aid']; ?>">
                            <td><img src="<?php echo $emp_img; ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"></td>
                            <td><?php echo $emp['afname'] . ' ' . $emp['alname']; ?></td>
                            <td><?php echo $emp['username']; ?></td>
                            <td><span class="role-badge <?php echo $roleClass; ?>"><?php echo ucfirst($role); ?></span></td>
                            <td><span class="<?php echo $statusClass; ?>" id="status-<?php echo $emp['aid']; ?>"><?php echo $status; ?></span></td>
                            <td>
                                <button class="action-btn btn-view" onclick='viewEmployee(<?php echo json_encode($emp); ?>)' title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="action-btn btn-edit" onclick='openEditModal(<?php echo json_encode($emp); ?>)' title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn btn-block" onclick="toggleStatus(<?php echo $emp['aid']; ?>, '<?php echo $status; ?>')" id="block-btn-<?php echo $emp['aid']; ?>" title="<?php echo $status === 'Active' ? 'Block' : 'Unblock'; ?>">
                                    <i class="fas fa-<?php echo $status === 'Active' ? 'ban' : 'check'; ?>"></i>
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Employee Modal -->
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('employeeModal')">&times;</span>
            <h2 id="modalTitle">Add New Employee</h2>
            <form action="" method="POST">
                <input type="hidden" name="aid" id="form_aid">
                <div class="modal-grid">
                    <div class="input-group">
                        <label>First Name</label>
                        <input type="text" name="afname" id="form_afname" required>
                    </div>
                    <div class="input-group">
                        <label>Last Name</label>
                        <input type="text" name="alname" id="form_alname" required>
                    </div>
                    <div class="input-group">
                        <label>Email</label>
                        <input type="email" name="email" id="form_email" required>
                    </div>
                    <div class="input-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="form_phone" required>
                    </div>
                    <div class="input-group">
                        <label>Username</label>
                        <input type="text" name="username" id="form_username" required>
                    </div>
                    <div class="input-group" id="pass_group">
                        <label>Password</label>
                        <input type="password" name="password" id="form_password">
                    </div>
                    <div class="input-group">
                        <label>Role</label>
                        <select name="user_role" id="form_user_role" required>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                            <option value="operator">Operator</option>
                        </select>
                    </div>
                    <button type="submit" name="save_employee" class="btn-save">Save Employee</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Employee Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('viewModal')">&times;</span>
            <h2>Employee Details</h2>
            <div id="viewDetails" style="margin-top:20px;"></div>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Employee';
            document.getElementById('form_aid').value = '';
            document.getElementById('form_afname').value = '';
            document.getElementById('form_alname').value = '';
            document.getElementById('form_email').value = '';
            document.getElementById('form_phone').value = '';
            document.getElementById('form_username').value = '';
            document.getElementById('pass_group').style.display = 'block';
            document.getElementById('form_password').required = true;
            document.getElementById('employeeModal').style.display = 'block';
        }

        function openEditModal(emp) {
            document.getElementById('modalTitle').textContent = 'Edit Employee';
            document.getElementById('form_aid').value = emp.aid;
            document.getElementById('form_afname').value = emp.afname;
            document.getElementById('form_alname').value = emp.alname;
            document.getElementById('form_email').value = emp.email;
            document.getElementById('form_phone').value = emp.phone;
            document.getElementById('form_username').value = emp.username;
            document.getElementById('form_user_role').value = emp.user_role;
            document.getElementById('pass_group').style.display = 'none';
            document.getElementById('form_password').required = false;
            document.getElementById('employeeModal').style.display = 'block';
        }

        function viewEmployee(emp) {
            const details = document.getElementById('viewDetails');
            const imgPath = emp.profile_img ? 'img/users/' + emp.profile_img : `https://ui-avatars.com/api/?name=${encodeURIComponent(emp.afname + ' ' + emp.alname)}&background=random&color=fff`;

            details.innerHTML = `
                <div style="text-align: center; margin-bottom: 30px;">
                    <img src="${imgPath}" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #f0f0f0;">
                </div>
                <div class="modal-grid">
                    <div class="user-detail"><strong>Name</strong> <span>${emp.afname} ${emp.alname}</span></div>
                    <div class="user-detail"><strong>Username</strong> <span>${emp.username}</span></div>
                    <div class="user-detail"><strong>Email</strong> <span>${emp.email}</span></div>
                    <div class="user-detail"><strong>Phone</strong> <span>${emp.phone}</span></div>
                    <div class="user-detail"><strong>Role</strong> <span>${emp.user_role.toUpperCase()}</span></div>
                    <div class="user-detail"><strong>Status</strong> <span>${emp.status}</span></div>
                </div>
            `;
            document.getElementById('viewModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
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
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const statusSpan = document.getElementById('status-' + aid);
                        const blockBtn = document.getElementById('block-btn-' + aid);
                        statusSpan.textContent = newStatus;
                        statusSpan.className = newStatus === 'Active' ? 'status-badge pay-paid' : 'status-badge pay-unpaid';
                        blockBtn.title = newStatus === 'Active' ? 'Block' : 'Unblock';
                        blockBtn.innerHTML = `<i class="fas fa-${newStatus === 'Active' ? 'ban' : 'check'}"></i>`;
                        blockBtn.setAttribute('onclick', `toggleStatus(${aid}, '${newStatus}')`);
                    }
                });
        }

        window.onclick = function(e) {
            if (e.target.className === 'modal') {
                e.target.style.display = 'none';
            }
        }
    </script>
</body>

</html>