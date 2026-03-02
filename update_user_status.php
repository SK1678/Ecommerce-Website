<?php
include("include/auth.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aid']) && isset($_POST['status'])) {
    $aid = intval($_POST['aid']);
    $status = $_POST['status']; // 'Active' or 'Blocked'

    // Validate status
    if (!in_array($status, ['Active', 'Blocked'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }

    $query = "UPDATE accounts SET status = '$status' WHERE aid = $aid";
    if (mysqli_query($con, $query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
