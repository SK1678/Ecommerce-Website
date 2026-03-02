<?php
session_start();
include_once("include/connect.php");

// Get payment settings
$payment_settings = $con->query("SELECT * FROM payment_settings LIMIT 1")->fetch_assoc();

if (!$payment_settings['stripe_enabled']) {
    die(json_encode(['error' => 'Stripe payment is not enabled']));
}

// Set Stripe API key
$stripe_secret_key = $payment_settings['stripe_secret_key'];

if (empty($stripe_secret_key)) {
    die(json_encode(['error' => 'Stripe is not configured properly']));
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$amount = floatval($input['amount'] ?? 0);
$order_id = intval($input['order_id'] ?? 0);

if (empty($token) || $amount <= 0) {
    die(json_encode(['error' => 'Invalid payment details']));
}

// Convert amount to cents
$amount_cents = intval($amount * 100);

// Prepare Stripe API request
$ch = curl_init('https://api.stripe.com/v1/charges');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_USERPWD, $stripe_secret_key . ':');
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'amount' => $amount_cents,
    'currency' => 'bdt', // Change to your currency
    'source' => $token,
    'description' => 'Order #' . $order_id,
]));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($http_code == 200 && isset($result['id'])) {
    // Payment successful
    $transaction_id = $result['id'];

    // Update order with transaction details
    if ($order_id > 0) {
        $stmt = $con->prepare("UPDATE orders SET payment_method='stripe', payment_status='paid', transaction_id=? WHERE oid=?");
        $stmt->bind_param("si", $transaction_id, $order_id);
        if ($stmt->execute()) {
            include_once("include/mail_helper.php");
            sendOrderInvoiceEmail($order_id);
        }
    }

    echo json_encode([
        'success' => true,
        'transaction_id' => $transaction_id,
        'message' => 'Payment successful'
    ]);
}
else {
    // Payment failed
    $error_message = $result['error']['message'] ?? 'Payment failed';
    echo json_encode([
        'success' => false,
        'error' => $error_message
    ]);
}
