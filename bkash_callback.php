<?php
session_start();
include_once("include/connect.php");

// Get payment ID from callback
$paymentID = $_GET['paymentID'] ?? '';
$status = $_GET['status'] ?? '';

if ($status == 'success' && !empty($paymentID)) {
    // Redirect to execute payment
    header("Location: process_bkash_payment.php?action=execute&paymentID=" . $paymentID);
    exit();
} else {
    // Payment cancelled or failed
    header("Location: checkout.php?payment_error=bkash_cancelled");
    exit();
}
