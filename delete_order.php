<?php
include("include/auth.php");

// Handle AJAX delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['oid'])) {
    $oid = intval($_POST['oid']);

    // Start transaction for data integrity
    mysqli_begin_transaction($con);

    try {
        // First check the status
        $statusCheck = mysqli_query($con, "SELECT status FROM orders WHERE oid = $oid");
        $orderData = mysqli_fetch_assoc($statusCheck);
        if ($orderData && $orderData['status'] === 'Delivered') {
            echo json_encode(['success' => false, 'message' => 'Delivered orders cannot be deleted.']);
            exit();
        }

        // Delete order details first (foreign key constraint)
        $query1 = "DELETE FROM `order-details` WHERE oid = $oid";
        mysqli_query($con, $query1);

        // Delete reviews associated with the order
        $query2 = "DELETE FROM reviews WHERE oid = $oid";
        mysqli_query($con, $query2);

        // Delete the order itself
        $query3 = "DELETE FROM orders WHERE oid = $oid";
        $result = mysqli_query($con, $query3);

        if ($result) {
            // Commit transaction
            mysqli_commit($con);
            echo json_encode([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete order');
        }
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($con);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
