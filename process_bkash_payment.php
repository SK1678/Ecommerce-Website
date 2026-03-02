<?php
session_start();
include_once("include/connect.php");

// Get payment settings
$payment_settings = $con->query("SELECT * FROM payment_settings LIMIT 1")->fetch_assoc();

if (!$payment_settings['bkash_enabled']) {
    die(json_encode(['error' => 'bKash payment is not enabled']));
}

// bKash API Configuration
$app_key = $payment_settings['bkash_app_key'];
$app_secret = $payment_settings['bkash_api_secret'];
$username = $payment_settings['bkash_username'];
$password = $payment_settings['bkash_password'];
$base_url = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'; // Use production URL in live mode

if (empty($app_key) || empty($app_secret)) {
    die(json_encode(['error' => 'bKash is not configured properly']));
}

// Get request action
$action = $_GET['action'] ?? '';

/**
 * Step 1: Get Grant Token
 */
function getBkashToken($app_key, $app_secret, $username, $password, $base_url)
{
    $ch = curl_init($base_url . '/tokenized/checkout/token/grant');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'username: ' . $username,
        'password: ' . $password
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'app_key' => $app_key,
        'app_secret' => $app_secret
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

/**
 * Step 2: Create Payment
 */
function createBkashPayment($token, $amount, $invoice_number, $base_url)
{
    $ch = curl_init($base_url . '/tokenized/checkout/create');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'authorization: ' . $token,
        'x-app-key: ' . $_SESSION['bkash_app_key']
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'mode' => '0011',
        'payerReference' => ' ',
        'callbackURL' => 'http://localhost/mosiur/bkash_callback.php',
        'amount' => $amount,
        'currency' => 'BDT',
        'intent' => 'sale',
        'merchantInvoiceNumber' => $invoice_number
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

/**
 * Step 3: Execute Payment
 */
function executeBkashPayment($token, $paymentID, $base_url)
{
    $ch = curl_init($base_url . '/tokenized/checkout/execute');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'authorization: ' . $token,
        'x-app-key: ' . $_SESSION['bkash_app_key']
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'paymentID' => $paymentID
    ]));

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Handle different actions
if ($action == 'create') {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $amount = floatval($input['amount'] ?? 0);
    $order_id = intval($input['order_id'] ?? 0);

    if ($amount <= 0) {
        die(json_encode(['error' => 'Invalid amount']));
    }

    // Get bKash token
    $tokenResponse = getBkashToken($app_key, $app_secret, $username, $password, $base_url);

    if (isset($tokenResponse['id_token'])) {
        $_SESSION['bkash_token'] = $tokenResponse['id_token'];
        $_SESSION['bkash_app_key'] = $app_key;

        // Create payment
        $invoice_number = 'INV' . time() . $order_id;
        $paymentResponse = createBkashPayment($tokenResponse['id_token'], $amount, $invoice_number, $base_url);

        if (isset($paymentResponse['paymentID'])) {
            $_SESSION['bkash_payment_id'] = $paymentResponse['paymentID'];
            $_SESSION['bkash_order_id'] = $order_id;

            echo json_encode([
                'success' => true,
                'paymentID' => $paymentResponse['paymentID'],
                'bkashURL' => $paymentResponse['bkashURL']
            ]);
        }
        else {
            echo json_encode([
                'success' => false,
                'error' => $paymentResponse['errorMessage'] ?? 'Failed to create payment'
            ]);
        }
    }
    else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to get bKash token'
        ]);
    }
}
elseif ($action == 'execute') {
    // Execute payment after user completes bKash flow
    $paymentID = $_GET['paymentID'] ?? '';

    if (empty($paymentID) || !isset($_SESSION['bkash_token'])) {
        die(json_encode(['error' => 'Invalid payment session']));
    }

    $executeResponse = executeBkashPayment($_SESSION['bkash_token'], $paymentID, $base_url);

    if (isset($executeResponse['transactionStatus']) && $executeResponse['transactionStatus'] == 'Completed') {
        // Payment successful
        $transaction_id = $executeResponse['trxID'];
        $order_id = $_SESSION['bkash_order_id'] ?? 0;

        // Update order
        if ($order_id > 0) {
            $stmt = $con->prepare("UPDATE orders SET payment_method='bkash', payment_status='paid', transaction_id=? WHERE oid=?");
            $stmt->bind_param("si", $transaction_id, $order_id);
            if ($stmt->execute()) {
                include_once("include/mail_helper.php");
                sendOrderInvoiceEmail($order_id);
            }
        }

        // Clear session
        unset($_SESSION['bkash_token']);
        unset($_SESSION['bkash_payment_id']);
        unset($_SESSION['bkash_order_id']);

        // Redirect to success page
        header("Location: profile.php?payment_success=1");
        exit();
    }
    else {
        // Redirect to failure page
        $error = $executeResponse['errorMessage'] ?? 'Payment failed';
        header("Location: checkout.php?payment_error=" . urlencode($error));
        exit();
    }
}
