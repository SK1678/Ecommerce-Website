<?php
include("include/auth.php");

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['oid']) && isset($_POST['status'])) {
    $oid = intval($_POST['oid']);
    $status = mysqli_real_escape_string($con, $_POST['status']);

    // Check if order is already delivered
    $checkQ = mysqli_query($con, "SELECT status FROM orders WHERE oid = $oid");
    $checkD = mysqli_fetch_assoc($checkQ);
    if ($checkD && $checkD['status'] === 'Delivered') {
        echo json_encode(['success' => false, 'message' => 'Delivered orders cannot be changed.']);
        exit();
    }

    // Validate status
    $valid_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }

    // Update delivery date if status is Delivered
    if ($status === 'Delivered') {
        $update_sql = "UPDATE orders SET status = '$status', datedel = CURDATE() WHERE oid = $oid";
    }
    else {
        $update_sql = "UPDATE orders SET status = '$status' WHERE oid = $oid";
    }

    if (mysqli_query($con, $update_sql)) {
        // Send email notification
        require_once("include/mail_helper.php");
        sendOrderStatusUpdateEmail($oid, $status);

        // Handle Stock Reduction if status is Shipped or Delivered
        if ($status === 'Shipped' || $status === 'Delivered') {
            // Check if already reduced
            $stockCheck = mysqli_query($con, "SELECT stock_reduced FROM orders WHERE oid = $oid");
            $stockData = mysqli_fetch_assoc($stockCheck);

            if ($stockData && $stockData['stock_reduced'] == 0) {
                // Reduce stock for all items in this order
                $items_res = mysqli_query($con, "SELECT pid, qty FROM `order-details` WHERE oid = $oid");
                while ($item = mysqli_fetch_assoc($items_res)) {
                    $pid = $item['pid'];
                    $qty = $item['qty'];
                    mysqli_query($con, "UPDATE products SET qtyavail = qtyavail - $qty WHERE pid = $pid");
                }
                // Mark as reduced
                mysqli_query($con, "UPDATE orders SET stock_reduced = 1 WHERE oid = $oid");
            }
        }
        // Get updated order info
        $result = mysqli_query($con, "SELECT status, datedel FROM orders WHERE oid = $oid");
        $order = mysqli_fetch_assoc($result);

        echo json_encode([
            'success' => true,
            'message' => 'Order status updated successfully',
            'status' => $order['status'],
            'datedel' => $order['datedel']
        ]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($con)]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
