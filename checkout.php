<?php
session_start();
include_once("include/connect.php");

// Get payment settings
$payment_settings_query = $con->query("SELECT * FROM payment_settings LIMIT 1");
$payment_settings = $payment_settings_query ? $payment_settings_query->fetch_assoc() : null;

// Ensure columns exist in orders table
$con->query("ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `payment_method` varchar(50) DEFAULT 'cod'");
$con->query("ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `payment_status` varchar(50) DEFAULT 'pending'");
$con->query("ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `transaction_id` varchar(255) DEFAULT NULL");

if (isset($_POST['sub'])) {
    $aid = $_SESSION['aid'];
    $add = mysqli_real_escape_string($con, $_POST['houseadd']);
    $city = mysqli_real_escape_string($con, $_POST['city']);
    $country = mysqli_real_escape_string($con, $_POST['country']);
    $payment_method = $_POST['payment_method'] ?? 'cod';

    // Create order
    $query = "INSERT INTO `orders` (dateod, datedel, aid, address, city, country, account, total, payment_method, payment_status) 
              VALUES(CURDATE(), NULL, '$aid', '$add', '$city', '$country', NULL, 0, '$payment_method', 'pending')";
    $result = mysqli_query($con, $query);
    $oid = mysqli_insert_id($con);

    // Ensure order-details table has price column
    $checkColumn = mysqli_query($con, "SHOW COLUMNS FROM `order-details` LIKE 'price'");
    if (mysqli_num_rows($checkColumn) == 0) {
        mysqli_query($con, "ALTER TABLE `order-details` ADD COLUMN `price` decimal(10,2) NOT NULL AFTER `pid`");
    }

    // Add items to order
    $query = "SELECT * FROM cart JOIN products ON cart.pid = products.pid WHERE aid = $aid";
    $result = mysqli_query($con, $query);
    $tott = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $pid = $row['pid'];
        $price = $row['price'];
        $cqty = $row['cqty'];
        $itemTotal = $price * $cqty;
        $tott += $itemTotal;

        $query = "INSERT INTO `order-details` (oid, pid, price, qty) VALUES ($oid, $pid, $price, $cqty)";
        mysqli_query($con, $query);
    }

    // Update order total
    $query = "UPDATE orders SET total = $tott WHERE oid = $oid";
    mysqli_query($con, $query);

    // Clear cart
    $query = "DELETE FROM cart WHERE aid = $aid";
    mysqli_query($con, $query);

    // Store order ID and total in session for payment processing
    $_SESSION['pending_order_id'] = $oid;
    $_SESSION['pending_order_total'] = $tott;

    // Redirect based on payment method
    if ($payment_method == 'cod') {
        // COD - mark as pending and redirect to profile
        include_once("include/mail_helper.php");
        sendOrderInvoiceEmail($oid);

        header("Location: profile.php?order_success=1");
        exit();
    }
    else {
        // Online payment - redirect to payment page
        header("Location: payment_gateway.php?order_id=" . $oid . "&method=" . $payment_method);
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <link rel="stylesheet" href="style.css" />

    <style>
        #account-field {
            display: none;
        }

        .hidden {
            display: none;
        }

        .input11 {
            display: block;
            width: 80%;
            margin: 40px auto;
            padding: 10px 5px;
            border: none;
            border-bottom: 0.01rem dimgray solid;
            outline: none;
        }

        .payment-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            margin: 10px auto;
            width: 80%;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            border-color: #088178;
            background: #f8f9fa;
        }

        .payment-option.selected {
            border-color: #088178;
            background: #e8f5f3;
        }

        .payment-option input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .payment-option label {
            flex: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            font-weight: 500;
        }

        .payment-option img {
            height: 30px;
            margin-left: auto;
        }

        .payment-icon {
            font-size: 24px;
            color: #088178;
        }

        .table12 {
            margin: 0;
            padding: 0;
            width: 100%;
            overflow: auto;
        }

        .table12 tr {
            width: 100%;
            overflow: auto;
        }

        .payment-disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .payment-disabled::after {
            content: " (Disabled)";
            color: #999;
            font-size: 12px;
        }
    </style>

</head>

<body>
    <?php include('header.php'); ?>

    <div class="container">
        <div class="titlecheck">
            <h2>Product Order Form</h2>
        </div>
        <div class="d-flex">
            <form method="post" id="form1">

                <h3 style="color: darkred; margin: auto"></h3>
                <input class="input11" type="text" name="houseadd" placeholder="Address" required>
                <input class="input11" type="text" name="city" placeholder="City" required>
                <input class="input11" type="text" name="country" placeholder="County/State" required>

                <h3 style="margin: 40px auto 20px; width: 80%; color: #088178;"><i class="fas fa-credit-card"></i> Select Payment Method</h3>

                <?php if ($payment_settings && $payment_settings['cod_enabled']): ?>
                    <div class="payment-option" onclick="selectPayment('cod')">
                        <input type="radio" id="payment_cod" name="payment_method" value="cod" checked>
                        <label for="payment_cod">
                            <i class="fas fa-money-bill-wave payment-icon"></i>
                            Cash on Delivery
                        </label>
                    </div>
                <?php
endif; ?>

                <?php if ($payment_settings && $payment_settings['stripe_enabled']): ?>
                    <div class="payment-option" onclick="selectPayment('stripe')">
                        <input type="radio" id="payment_stripe" name="payment_method" value="stripe">
                        <label for="payment_stripe">
                            <i class="fas fa-credit-card payment-icon"></i>
                            Credit/Debit Card (Visa, Mastercard)
                        </label>
                        <img src="https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg" alt="Stripe">
                    </div>
                <?php
endif; ?>

                <?php if ($payment_settings && $payment_settings['bkash_enabled']): ?>
                    <div class="payment-option" onclick="selectPayment('bkash')">
                        <input type="radio" id="payment_bkash" name="payment_method" value="bkash">
                        <label for="payment_bkash">
                            <i class="fas fa-mobile-alt payment-icon"></i>
                            bKash Payment
                        </label>
                        <img src="https://seeklogo.com/images/B/bkash-logo-250D94B6A4-seeklogo.com.png" alt="bKash" style="height: 40px;">
                    </div>
                <?php
endif; ?>

                <button name="sub" type="submit" class="btn112">Place Order</button>
            </form>
            <div class="Yorder">
                <table class="table12">
                    <tr class='tr1'>
                        <th class='th1' colspan='2'>Your order</th>
                    </tr>

                    <?php
include("include/connect.php");

$aid = $_SESSION['aid'];

$query = "SELECT * FROM cart JOIN products ON cart.pid = products.pid WHERE aid = $aid";

$result = mysqli_query($con, $query);

global $tot;

while ($row = mysqli_fetch_assoc($result)) {
    $pid = $row['pid'];
    $pname = $row['pname'];
    $desc = $row['description'];
    $qty = $row['qtyavail'];
    $price = $row['price'];
    $cat = $row['category'];
    $img = $row['img'];
    $brand = $row['brand'];
    $cqty = $row['cqty'];
    $a = $price * $cqty;
    $tot = $tot + $a;

    echo "
            
            <tr class='tr1'>
              <td class='td1'>$pname x $cqty(Qty)</td>
              <td class='td1'>$a</td>
            </tr>

              ";
}
echo "
            <tr class='tr1'>
            <td class='td1'>Subtotal</td>
            <td class='td1'>{$web_settings['currency']}$tot</td>
          </tr>
          <tr class='tr1'>
            <td class='td1'>Shipping</td>
            <td class='td1'>Free shipping</td>
          </tr>";
?>


                </table><br>
            </div><!-- Yorder -->
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script src="script.js"></script>

    <script>
        function selectPayment(method) {
            // Remove selected class from all options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });

            // Add selected class to clicked option
            event.currentTarget.classList.add('selected');

            // Select the radio button
            document.getElementById('payment_' + method).checked = true;
        }

        // Set initial selected state
        document.addEventListener('DOMContentLoaded', function() {
            const selectedRadio = document.querySelector('input[name="payment_method"]:checked');
            if (selectedRadio) {
                selectedRadio.parentElement.classList.add('selected');
            }
        });
    </script>
</body>

</html>

<script>
    window.addEventListener("unload", function() {
        // Call a PHP script to log out the user
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "logout.php", false);
        xhr.send();
    });
</script>