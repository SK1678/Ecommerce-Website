<?php
// Centrally handle administrative authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once("include/connect.php");

// 1. Basic session check
if (empty($_SESSION['username'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    } else {
        header("Location: admin.php");
    }
    exit();
}

// 2. Role check (Fetch from DB to ensure it's up to date)
$session_username = $_SESSION['username'];
$auth_query = mysqli_query($con, "SELECT user_role FROM accounts WHERE username = '$session_username'");
$auth_user = mysqli_fetch_assoc($auth_query);

$allowed_roles = ['admin', 'superadmin', 'operator'];
$current_role = $auth_user['user_role'] ?? 'user';

if (!in_array($current_role, $allowed_roles)) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    } else {
        header("Location: index.php");
    }
    exit();
}

// Store role in session for UI use
$_SESSION['user_role'] = $current_role;
