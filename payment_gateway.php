<?php
session_start();
include_once("include/connect.php");

if (!isset($_SESSION['pending_order_id']) || !isset($_SESSION['pending_order_total'])) {
    header("Location: shop.php");
    exit();
}

$order_id = intval($_GET['order_id'] ?? 0);
$method = $_GET['method'] ?? '';
$amount = $_SESSION['pending_order_total'];

// Get payment settings
$payment_settings = $con->query("SELECT * FROM payment_settings LIMIT 1")->fetch_assoc();

if ($method == 'stripe' && !$payment_settings['stripe_enabled']) {
    header("Location: checkout.php?error=stripe_disabled");
    exit();
}

if ($method == 'bkash' && !$payment_settings['bkash_enabled']) {
    header("Location: checkout.php?error=bkash_disabled");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway | <?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <link rel="stylesheet" href="style.css" />

    <?php if ($method == 'stripe'): ?>
        <script src="https://js.stripe.com/v3/"></script>
    <?php endif; ?>

    <style>
        .payment-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .payment-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #088178;
        }

        .payment-header h2 {
            color: #088178;
            margin-bottom: 10px;
        }

        .payment-header .amount {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .order-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .order-info p {
            margin: 8px 0;
            color: #666;
        }

        .order-info strong {
            color: #333;
        }

        .stripe-form {
            margin-top: 20px;
        }

        .form-row {
            margin-bottom: 20px;
        }

        .form-row label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        #card-element {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
        }

        #card-errors {
            color: #dc3545;
            margin-top: 10px;
            font-size: 14px;
        }

        .btn-pay {
            width: 100%;
            padding: 15px;
            background: #088178;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-pay:hover {
            background: #066f66;
        }

        .btn-pay:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-bkash {
            background: #e2136e;
        }

        .btn-bkash:hover {
            background: #c91160;
        }

        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #088178;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        .payment-logo {
            height: 40px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include('header.php'); ?>

    <div class="payment-container">
        <div class="payment-header">
            <h2><i class="fas fa-lock"></i> Secure Payment</h2>
            <div class="amount"><?php echo $web_settings['currency'] . number_format($amount, 2); ?></div>
        </div>

        <div class="order-info">
            <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
            <p><strong>Payment Method:</strong> <?php echo strtoupper($method); ?></p>
        </div>

        <div id="payment-messages"></div>

        <?php if ($method == 'stripe'): ?>
            <div class="stripe-payment-section">
                <img src="https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg" alt="Stripe" class="payment-logo">

                <form id="payment-form" class="stripe-form">
                    <div class="form-row">
                        <label for="card-element">
                            Credit or Debit Card
                        </label>
                        <div id="card-element"></div>
                        <div id="card-errors" role="alert"></div>
                    </div>

                    <button type="submit" id="submit-button" class="btn-pay">
                        <i class="fas fa-lock"></i> Pay <?php echo $web_settings['currency'] . number_format($amount, 2); ?>
                    </button>
                </form>

                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Processing payment...</p>
                </div>
            </div>

            <script>
                const stripe = Stripe('<?php echo $payment_settings['stripe_publishable_key']; ?>');
                const elements = stripe.elements();
                const cardElement = elements.create('card', {
                    hidePostalCode: true,
                    style: {
                        base: {
                            fontSize: '16px',
                            color: '#32325d',
                            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                            '::placeholder': {
                                color: '#aab7c4'
                            }
                        },
                        invalid: {
                            color: '#fa755a',
                            iconColor: '#fa755a'
                        }
                    }
                });
                cardElement.mount('#card-element');

                cardElement.on('change', function(event) {
                    const displayError = document.getElementById('card-errors');
                    if (event.error) {
                        displayError.textContent = event.error.message;
                    } else {
                        displayError.textContent = '';
                    }
                });

                const form = document.getElementById('payment-form');
                form.addEventListener('submit', async function(event) {
                    event.preventDefault();

                    document.getElementById('submit-button').disabled = true;
                    document.getElementById('loading').classList.add('active');

                    const {
                        token,
                        error
                    } = await stripe.createToken(cardElement);

                    if (error) {
                        document.getElementById('card-errors').textContent = error.message;
                        document.getElementById('submit-button').disabled = false;
                        document.getElementById('loading').classList.remove('active');
                    } else {
                        // Send token to server
                        fetch('process_stripe_payment.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    token: token.id,
                                    amount: <?php echo $amount; ?>,
                                    order_id: <?php echo $order_id; ?>
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                document.getElementById('loading').classList.remove('active');

                                if (data.success) {
                                    showMessage('Payment successful! Redirecting...', 'success');
                                    setTimeout(() => {
                                        window.location.href = 'profile.php?payment_success=1';
                                    }, 2000);
                                } else {
                                    showMessage('Payment failed: ' + data.error, 'error');
                                    document.getElementById('submit-button').disabled = false;
                                }
                            })
                            .catch(error => {
                                document.getElementById('loading').classList.remove('active');
                                showMessage('Payment error: ' + error.message, 'error');
                                document.getElementById('submit-button').disabled = false;
                            });
                    }
                });
            </script>

        <?php elseif ($method == 'bkash'): ?>
            <div class="bkash-payment-section">
                <img src="https://seeklogo.com/images/B/bkash-logo-250D94B6A4-seeklogo.com.png" alt="bKash" class="payment-logo">

                <p style="text-align: center; margin: 20px 0; color: #666;">
                    You will be redirected to bKash to complete your payment securely.
                </p>

                <button type="button" id="bkash-button" class="btn-pay btn-bkash">
                    <i class="fas fa-mobile-alt"></i> Pay with bKash
                </button>

                <div class="loading" id="bkash-loading">
                    <div class="spinner"></div>
                    <p>Initiating bKash payment...</p>
                </div>
            </div>

            <script>
                document.getElementById('bkash-button').addEventListener('click', function() {
                    this.disabled = true;
                    document.getElementById('bkash-loading').classList.add('active');

                    fetch('process_bkash_payment.php?action=create', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                amount: <?php echo $amount; ?>,
                                order_id: <?php echo $order_id; ?>
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.bkashURL) {
                                // Redirect to bKash payment page
                                window.location.href = data.bkashURL;
                            } else {
                                document.getElementById('bkash-loading').classList.remove('active');
                                showMessage('bKash error: ' + (data.error || 'Unknown error'), 'error');
                                document.getElementById('bkash-button').disabled = false;
                            }
                        })
                        .catch(error => {
                            document.getElementById('bkash-loading').classList.remove('active');
                            showMessage('bKash error: ' + error.message, 'error');
                            document.getElementById('bkash-button').disabled = false;
                        });
                });
            </script>
        <?php endif; ?>
    </div>

    <?php include('footer.php'); ?>

    <script>
        function showMessage(message, type) {
            const messagesDiv = document.getElementById('payment-messages');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            messagesDiv.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
        }
    </script>
</body>

</html>