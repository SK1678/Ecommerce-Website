<?php
session_start();
include_once("include/connect.php");

if (isset($_POST['submit'])) {
  include("include/connect.php");
  $pid = $_GET['pid'];
  $aid = $_SESSION['aid'];
  $qty = $_POST['qty'];

  if ($aid < 0) {
    header("Location: login.php");
    exit();
  }

  $query = "select * from `cart`  where aid = $aid and pid = $pid";

  $result = mysqli_query($con, $query);
  $row = mysqli_fetch_assoc($result);

  // Check if product is in stock
  $stockQuery = mysqli_query($con, "SELECT qtyavail FROM products WHERE pid = $pid");
  $stockData = mysqli_fetch_assoc($stockQuery);
  if (!$stockData || $stockData['qtyavail'] <= 0) {
    echo "<script> alert('This product is out of stock.'); window.location.href='sproduct.php?pid=$pid'; </script>";
    exit();
  }

  if ($row) {
    echo "<script> alert('item already added to cart') </script>";

    header("Location: cart.php");
    exit();
  } else {

    $query = "INSERT INTO `cart` (aid, pid, cqty) values ($aid, $pid, $qty)";
    $result = mysqli_query($con, $query);
    header("Location: shop.php");
    exit();
  }
}
if (isset($_GET['w'])) {
  include("include/connect.php");
  $aid = $_SESSION['aid'];
  if ($aid < 0) {
    header("Location: login.php");
    exit();
  }
  $pid = $_GET['w'];

  $query = "INSERT INTO `WISHLIST` (aid, pid) values ($aid, $pid)";

  $result = mysqli_query($con, $query);
  header("Location: sproduct.php?pid=$pid");
  exit();
}
if (isset($_GET['nw'])) {
  include("include/connect.php");
  $aid = $_SESSION['aid'];
  $pid = $_GET['nw'];

  $query = "DELETE from `WISHLIST` where aid = $aid and pid = $pid";

  $result = mysqli_query($con, $query);
  header("Location: sproduct.php?pid=$pid");
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

  <style>
    .heart {
      margin-left: 25px;
      display: inline-flex;
      justify-content: center;
      align-items: center;
    }

    .star i {
      font-size: 12px;
      color: rgb(243, 181, 25);
    }

    .tb {
      max-height: 400px;
      overflow-x: auto;
      overflow-y: auto;
    }



    .tb tr {
      height: 60px;
      margin: 10px;
    }

    .tb td {
      text-align: center;
      margin: 10px;
      padding-left: 40px;
      padding-right: 40px;
    }

    .rev {
      margin: 70px;
    }
  </style>

</head>

<body>
  <?php include('header.php'); ?>

  <?php
  include("include/connect.php");

  if (isset($_GET['pid'])) {
    $pid = $_GET['pid'];
    $query = "SELECT* FROM PRODUCTS WHERE pid = $pid";

    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);

    $pidd = $row['pid'];
    $pname = $row['pname'];

    $desc = $row['description'];
    $qty = $row['qtyavail'];
    $price = $row['price'];
    $cat = $row['category'];
    $img = $row['img'];
    $brand = $row['brand'];

    $aid = $_SESSION['aid'];
    $query = "select * from wishlist where aid = $aid and pid = $pid";
    $result = mysqli_query($con, $query);
    $row = mysqli_fetch_assoc($result);


    // Calculate Average Rating
    $rating_query = "SELECT AVG(rating) AS average_rating FROM reviews WHERE pid = $pid";
    $rating_result = mysqli_query($con, $rating_query);
    $rating_row = mysqli_fetch_assoc($rating_result);
    $stars = $rating_row['average_rating'] ? round($rating_row['average_rating']) : 0;
    $star_html = "";
    for ($i = 1; $i <= 5; $i++) {
      if ($i <= $stars) $star_html .= '<i class="fas fa-star"></i> ';
      else $star_html .= '<i class="far fa-star"></i> ';
    }

    echo "
      <section id='prodetails' class='section-p1'>
        <div class='single-pro-image'>
          <img src='product_images/$img' width='100%' id='MainImg' alt=' ' />
        </div>
        <div class='single-pro-details'>
          <h2>$pname</h2>
          <div class='star' style='margin-bottom: 10px;'>
            $star_html
          </div>
          <h4>$cat - $brand</h4>
          <h4>{$web_settings['currency']}$price</h4>
          <form method='post'>
          " . ($qty > 0 ? "
          <input type='number' name='qty' value='1' min='1' max='$qty'/>
          <button class='normal' name='submit'>Add to Cart</button>
          " : "
          <div style='color: #d32f2f; font-weight: 700; font-size: 18px; margin-bottom: 15px;'>
            <i class='fas fa-times-circle'></i> Out of Stock
          </div>
          <button class='normal' disabled style='background-color: #ccc; cursor: not-allowed;'>Out of Stock</button>
          ") . "";

    if ($row)
      echo "<a  class ='heart' href='sproduct.php?nw=$pid'><img src='img/full.png' style='
            margin: auto; width='40px' height='40px'   alt=' ' /></a>";
    else
      echo "<a class ='heart' href='sproduct.php?w=$pid'><img src='img/empty.png' style='
            margin: auto; ' width='40px' height='40px'  alt=' ' /></a>";

    echo "
            </form>
            <h4>Product Details</h4>
            <span>$desc
            </span>";



    echo "</div></section>";
  }

  $query = "select * from reviews join orders on reviews.oid = orders.oid join accounts on orders.aid = accounts.aid where reviews.pid = $pid";
  $result = mysqli_query($con, $query);

  $row = mysqli_fetch_assoc($result);

  if (!empty($row)) {
    $result = mysqli_query($con, $query);

    echo "
<div class = 'rev'>
<h2>Reviews</h2>
<div class='tb'>
<table><thead><tr><th>username</th>
<th style='min-width: 100px;'>rating</th>
<th>text</th></thead><tbody>";

    while ($row = mysqli_fetch_assoc($result)) {
      $user = $row['username'];
      $rtext = $row['rtext'];
      $stars = $row['rating'];

      $empty = 5 - $stars;

      echo "<tr><td>$user</td>
           
            <td style='min-width: 200px;'><div class='star' >";
      for ($i = 1; $i <= $stars; $i++) {
        echo "<i class='fas fa-star'></i>";
      }
      for ($i = 1; $i <= $empty; $i++) {
        echo "<i class='far fa-star'></i>";
      }
      echo "</div></td>
            <td><span>$rtext<span></td></tr>";
    }

    echo "</tbody></table></div></div>";
  }
  ?>


  <?php include('footer.php'); ?>

  <script>
    var MainImg = document.getElementById("MainImg");
    var smallimg = document.getElementsByClassName("small-img");

    smallimg[0].onclick = function() {
      MainImg.src = smallimg[0].src;
    };
    smallimg[1].onclick = function() {
      MainImg.src = smallimg[1].src;
    };
    smallimg[2].onclick = function() {
      MainImg.src = smallimg[2].src;
    };
    smallimg[3].onclick = function() {
      MainImg.src = smallimg[3].src;
    };
  </script>
  <script src="script.js"></script>
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