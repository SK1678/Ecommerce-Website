<?php
session_start();
include_once("include/connect.php");

if ($_SESSION['aid'] < 0) {
    header("Location: login.php");
}

if (isset($_GET['re'])) {
    include("include/connect.php");
    $aid = $_SESSION['aid'];
    $pid = $_GET['re'];
    $query = "DELETE FROM CART WHERE aid = $aid and pid = $pid";

    $result = mysqli_query($con, $query);
    header("Location: cart.php");
    exit();
}

if (isset($_GET['pid'])) {
    include("include/connect.php");
    $aid = $_SESSION['aid'];
    $pid = intval($_GET['pid']);

    if ($aid < 0) {
        header("Location: login.php");
        exit();
    }

    // Check stock
    $stockCheck = mysqli_query($con, "SELECT qtyavail FROM products WHERE pid = $pid");
    $stockData = mysqli_fetch_assoc($stockCheck);
    if ($stockData && $stockData['qtyavail'] > 0) {
        // Check if already in cart
        $cartCheck = mysqli_query($con, "SELECT * FROM cart WHERE aid = $aid AND pid = $pid");
        if (mysqli_num_rows($cartCheck) == 0) {
            mysqli_query($con, "INSERT INTO cart (aid, pid, cqty) VALUES ($aid, $pid, 1)");
        }
    }
    header("Location: cart.php");
    exit();
}

if (isset($_POST['check'])) {
    include("include/connect.php");

    $aid = $_SESSION['aid'];

    $query = "SELECT * FROM cart JOIN products ON cart.pid = products.pid WHERE aid = $aid";

    $result = mysqli_query($con, $query);

    $result2 = mysqli_query($con, $query);
    $row2 = mysqli_fetch_assoc($result2);

    if (empty($row2['pid'])) {
        header("Location: shop.php");
        exit();
    }

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

        $newqty = $_POST["$pid-qt"];

        $query = "UPDATE CART SET cqty = $newqty where aid = $aid and pid = $pid";

        mysqli_query($con, $query);
    }
    header("Location: checkout.php");
    exit();
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
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />

    <link rel="stylesheet" href="style.css" />


</head>

<body onload="totala()">
    <?php include('header.php'); ?>

    <section id="page-header" class="about-header">
        <h2>#GameTillTheEnd</h2>

        <p>Providing premium gaming experience</p>
    </section>


    <section id="cart" class="section-p1">
        <table width="100%">
            <thead>
                <tr>
                    <td>Remove</td>
                    <td>Image</td>
                    <td>Product</td>
                    <td>Price</td>
                    <td>Quantity</td>
                    <td>Subtotal</td>
                </tr>
            </thead>
            <tbody>

                <?php

                include("include/connect.php");

                $aid = $_SESSION['aid'];

                $query = "SELECT * FROM cart JOIN products ON cart.pid = products.pid WHERE aid = $aid";

                $result = mysqli_query($con, $query);


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
                    echo "

            <tr>
              <td>
                <a href='cart.php?re=$pid'><i class='far fa-times-circle'></i></a>
              </td>
              <td><img src='product_images/$img' alt='' /></td>
              <td>$pname</td>
              <td class='pr'>{$web_settings['currency']}$price</td>
              <td><input type='number' class = 'aqt' value='$cqty' min = '1' max = '$qty' onchange='subprice()' /></td>
              <td class = 'atd'>{$web_settings['currency']}$a</td>
            </tr>
            ";
                }
                ?>

            </tbody>
        </table>
    </section>

    <section id="cart-add" class="section-p1">
        <div id="coupon">

        </div>
        <div id="subtotal">
            <h3>Cart Totals</h3>
            <table>
                <tr>
                    <td>Cart Subtotal</td>
                    <td id='tot1' onload="totala()"><?php echo $web_settings['currency'] ?? '$'; ?></td>
                </tr>
                <tr>
                    <td>Shipping</td>
                    <td>Free</td>
                </tr>
                <tr>
                    <td><strong>Total</strong></td>
                    <td id='tot' onload="totala()"><strong><?php echo $web_settings['currency'] ?? '$'; ?></strong></td>
                </tr>
            </table>

            <form method="post">
                <?php

                include("include/connect.php");

                $aid = $_SESSION['aid'];

                $query = "SELECT * FROM cart JOIN products ON cart.pid = products.pid WHERE aid = $aid";

                $result = mysqli_query($con, $query);


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
                    echo "

              <input style='display: none;' name='$pid-p' class='inp' type = 'number' value = '$pid'/>
              <input style='display: none;' name='$pid-qt' class='inq' type = 'number' value = '$cqty'/>
              ";
                }
                ?>
                <button class="normal" name="check">Proceed to checkout</button>
            </form>
            </a>
        </div>
    </section>

    <?php include('footer.php'); ?>

    <script src="script.js"></script>
</body>

</html>

<script>
    function subprice() {
        var qty = document.getElementsByClassName("aqt");
        var sub = document.getElementsByClassName("atd");
        var pri = document.getElementsByClassName("pr");
        var upd = document.getElementsByClassName("inq");

        for (var i = 0; i < qty.length; i++) {
            var quantity = parseInt(qty[i].value);
            var currency = "<?php echo $web_settings['currency'] ?? '$'; ?>";
            var price = parseFloat(pri[i].innerText.replace(currency, ''));
            sub[i].innerHTML = `${currency}${quantity * price}`;
            upd[i].value = parseInt(qty[i].value);
        }

        totala();
    }

    function totala() {
        var pri = document.getElementsByClassName("atd");
        var currency = "<?php echo $web_settings['currency'] ?? '$'; ?>";
        let yes = 0;
        for (var i = 0; i < pri.length; i++) {
            yes = yes + parseFloat(pri[i].innerText.replace(currency, '').trim());
        }

        document.getElementById('tot').innerHTML = currency + yes;
        document.getElementById('tot1').innerHTML = currency + yes;
    }
</script>

<script>
    window.addEventListener("unload", function() {
        // Call a PHP script to log out the user
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "logout.php", false);
        xhr.send();
    });
</script>