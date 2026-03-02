<?php
session_start();
include_once("include/connect.php");

// ── Auth Guard: redirect to login if not logged in ───────────────────────────
if (empty($_SESSION['aid']) || (int)$_SESSION['aid'] <= 0) {
  header("Location: login.php");
  exit();
}

if (isset($_GET['lo'])) {
  $_SESSION['aid'] = -1;
  session_destroy();
  header("Location: index.php");
  exit();
}


// Handle Order Cancellation
if (isset($_GET['cancel_ord'])) {
  $cancel_oid = intval($_GET['cancel_ord']);
  $aid = $_SESSION['aid'];

  // Verify ownership and status
  $check_query = "SELECT status FROM orders WHERE oid = $cancel_oid AND aid = $aid";
  $check_res = mysqli_query($con, $check_query);
  $order_to_cancel = mysqli_fetch_assoc($check_res);

  if ($order_to_cancel && !in_array($order_to_cancel['status'], ['Shipped', 'Delivered', 'Cancelled'])) {
    mysqli_query($con, "UPDATE orders SET status = 'Cancelled' WHERE oid = $cancel_oid");
    header("Location: profile.php?msg=cancelled");
    exit();
  }
}

// Handle Order Update (Customer Edit) - RESTRICTED: Address & Phone only
if (isset($_POST['update_customer_order'])) {
  $oid = intval($_POST['oid']);
  $aid = $_SESSION['aid'];
  $address = mysqli_real_escape_string($con, $_POST['address']);
  $phone = mysqli_real_escape_string($con, $_POST['phone']);

  // Update address and phone
  $update_query = "UPDATE orders o 
                   JOIN accounts a ON o.aid = a.aid 
                   SET o.address = '$address', a.phone = '$phone' 
                   WHERE o.oid = $oid AND o.aid = $aid";

  // Note: Updating account phone updates it globally for the user, 
  // if you want per-order phone, you'd need a phone column in orders table.
  // Assuming strict requirement "contact details can be changed here", 
  // we will update the accounts phone number as orders link to accounts.

  if (mysqli_query($con, $update_query)) {
    header("Location: profile.php?msg=updated");
  }
  else {
    header("Location: profile.php?msg=error");
  }
  exit();
}

if (isset($_POST['submit'])) {
  include("include/connect.php");
  $aid = $_SESSION['aid'];

  $firstname = $_POST['a1'];
  $lastname = $_POST['a2'];
  $email = $_POST['a3'];
  $cnic = $_POST['a4'];
  $phone = $_POST['a5'];
  $dob = $_POST['a6'];

  $query = "select * from accounts where (cnic='$cnic' or phone='$phone' or email='$email') and aid != $aid ";

  $result = mysqli_query($con, $query);
  $row = mysqli_fetch_assoc($result);
  if (!empty($row['aid'])) {
    echo "<script> alert('Credentials already exists'); setTimeout(function(){ window.location.href = 'profile.php'; }, 10); </script>";
    exit();
  }
  if (strtotime($dob) > time()) {
    echo "<script> alert('invalid date'); setTimeout(function(){ window.location.href = 'profile.php'; }, 10); </script>";
    exit();
  }
  if (preg_match('/\D/', $cnic) || strlen($cnic) != 13) {
    echo "<script> alert('invalid cnic'); setTimeout(function(){ window.location.href = 'profile.php'; }, 10); </script>";
    exit();
  }
  if (preg_match('/\D/', $phone) || strlen($phone) != 11) {
    echo "<script> alert('invalid number'); setTimeout(function(){ window.location.href = 'profile.php'; }, 10); </script>";
    exit();
  }

  $profile_img_query = "";
  if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['profileImage']['tmp_name'];
    $fileName = $_FILES['profileImage']['name'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));
    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
    $uploadFileDir = 'img/users/';

    if (!is_dir($uploadFileDir)) {
      mkdir($uploadFileDir, 0777, true);
    }

    $dest_path = $uploadFileDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $dest_path)) {
      $profile_img_query = ", profile_img='$newFileName'";
    }
  }

  $query = "UPDATE ACCOUNTS SET afname = '$firstname', alname='$lastname', email='$email', phone='$phone', cnic='$cnic', dob='$dob' $profile_img_query WHERE aid = $aid";

  $result = mysqli_query($con, $query);
  header("Location: profile.php");
  exit();
}


if (isset($_POST['abc'])) {
  include("include/connect.php");

  $oid = $_GET['odd'];

  $query = "select * from `order-details` where oid = $oid";
  $result = mysqli_query($con, $query);

  while ($row = mysqli_fetch_assoc($result)) {
    include("include/connect.php");

    $pid = $row['pid'];


    $text = $_POST["$pid-te"];
    $star = $_POST["$pid-rating"];
    $query;
    if (empty($text))
      $query = "insert into `reviews` (oid, pid, rtext, rating) values ($oid, $pid, NULL, $star)";
    else
      $query = "insert into `reviews` (oid, pid, rtext, rating) values ($oid, $pid, '$text', $star)";


    $result2 = mysqli_query($con, $query);
  }

  header("Location: profile.php");
  exit();
}

if (isset($_GET['c'])) {
  header("Location: profile.php");
  exit();
}

// Handle Wishlist Removal from Profile
if (isset($_GET['wish_re'])) {
  $aid = $_SESSION['aid'];
  $pid = intval($_GET['wish_re']);
  mysqli_query($con, "DELETE FROM wishlist WHERE aid = $aid AND pid = $pid");
  header("Location: profile.php?w=1&msg=removed");
  exit();
}

// Handle Add to Cart from Wishlist
if (isset($_GET['wish_cart'])) {
  $aid = $_SESSION['aid'];
  $pid = intval($_GET['wish_cart']);

  // Check stock
  $stockCheck = mysqli_query($con, "SELECT qtyavail FROM products WHERE pid = $pid");
  $stockData = mysqli_fetch_assoc($stockCheck);
  if ($stockData && $stockData['qtyavail'] > 0) {
    // Check if already in cart
    $cartCheck = mysqli_query($con, "SELECT * FROM cart WHERE aid = $aid AND pid = $pid");
    if (mysqli_num_rows($cartCheck) == 0) {
      mysqli_query($con, "INSERT INTO cart (aid, pid, cqty) VALUES ($aid, $pid, 1)");
    }
    // Remove from wishlist after adding to cart? User said "can order", usually add to cart is first step.
    // mysqli_query($con, "DELETE FROM wishlist WHERE aid = $aid AND pid = $pid");
    header("Location: profile.php?w=1&msg=added_to_cart");
  }
  else {
    header("Location: profile.php?w=1&msg=out_of_stock");
  }
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
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="profile_redesign.css" />

  <style>
    .rating {
      display: inline-block;
      font-size: 0;
      line-height: 0;
      border: none;
      border-style: none;
    }

    .rating label {
      display: inline-block;
      font-size: 24px;
      color: #ddd;
      cursor: pointer;
    }

    .rating label:before {
      content: '\2606';
    }

    .rating label.checked:before,
    .rating label:hover:before {
      content: '\2605';
      color: #ffc107;
    }

    input[type="radio"] {
      display: none;
    }
  </style>
  <script>
    window.addEventListener("unload", function() {
      // Call a PHP script to log out the user
      var xhr = new XMLHttpRequest();
      xhr.open("GET", "logout.php", false);
      xhr.send();
    });
  </script>

</head>

<body>
  <?php include('header.php'); ?>

  <div class="profile-container">
    <!-- Sidebar -->
    <div class="profile-sidebar">
      <?php
$aid = $_SESSION['aid'];
$img_query = mysqli_query($con, "SELECT profile_img FROM accounts WHERE aid = $aid");
$img_row = mysqli_fetch_assoc($img_query);
$user_img = !empty($img_row['profile_img']) ? 'img/users/' . $img_row['profile_img'] : 'https://imdezcode.files.wordpress.com/2020/02/imdezcode-logo.png';

$query = "SELECT * FROM ACCOUNTS WHERE aid = $aid";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);
$full_name = $row['afname'] . " " . $row['alname'];
?>
      <img src="<?php echo $user_img; ?>" alt="Profile" width="100" height="100" style="border-radius: 50%; object-fit: cover; margin-bottom: 15px;">
      <h3><?php echo $full_name; ?></h3>
      <p style="color: #666; font-size: 14px; margin-bottom: 20px;">Customer</p>

      <div class="sidebar-nav">
        <a href="profile.php" class="sidebar-link <?php echo(!isset($_GET['upd']) && !isset($_GET['odd']) && !isset($_GET['w'])) ? 'active' : ''; ?>">Dashboard</a>
        <a href="profile.php?upd=1" class="sidebar-link <?php echo isset($_GET['upd']) ? 'active' : ''; ?>">Edit Profile</a>
        <a href="profile.php?w=1" class="sidebar-link <?php echo isset($_GET['w']) ? 'active' : ''; ?>">My Wishlist</a>
        <a href="profile.php?lo=1" class="sidebar-link" style="color: #d32f2f;">Log Out</a>
        <?php if (isset($_GET['odd'])) { ?>
          <a href="profile.php" class="sidebar-link" style="margin-top: 10px; background: #ffebee; color: #d32f2f;">Cancel Review</a>
        <?php
}?>
      </div>
    </div>

    <!-- Main Content -->
    <div class="profile-main">
      <?php
if (isset($_GET['upd'])) {
  // UPDATE FORM
  $aid = $_SESSION['aid'];
  $query = "SELECT * FROM ACCOUNTS WHERE aid = $aid";
  $result = mysqli_query($con, $query);
  $row = mysqli_fetch_assoc($result);

  $afname = $row['afname'];
  $alname = $row['alname'];
  $phone = $row['phone'];
  $email = $row['email'];
  $cnic = $row['cnic'];
  $dob = $row['dob'];
?>
        <div class="profile-card">
          <div class="profile-header">
            <h2>Update Profile Details</h2>
          </div>
          <form method="post" enctype="multipart/form-data">
            <div class="profile-img-upload">
              <div style="flex: 1;">
                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Profile Picture</label>
                <p style="font-size: 13px; color: #888; margin: 0;">Upload a new avatar. Recommended size: 500x500px.</p>
              </div>
              <input type="file" name="profileImage" class="form-control" style="flex: 2;">
            </div>

            <div class="profile-form-grid">
              <div class="form-group">
                <label>First Name</label>
                <input type="text" name="a1" value="<?php echo $afname; ?>" class="form-control" placeholder="First Name">
              </div>
              <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="a2" value="<?php echo $alname; ?>" class="form-control" placeholder="Last Name">
              </div>
              <div class="form-group">
                <label>Email Address</label>
                <input type="text" name="a3" value="<?php echo $email; ?>" class="form-control" placeholder="Email">
              </div>
              <div class="form-group">
                <label>CNIC</label>
                <input type="text" name="a4" value="<?php echo $cnic; ?>" class="form-control" placeholder="CNIC">
              </div>
              <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="a5" value="<?php echo $phone; ?>" class="form-control" placeholder="Phone">
              </div>
              <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="a6" value="<?php echo $dob; ?>" class="form-control">
              </div>
            </div>

            <div style="margin-top: 25px; text-align: right;">
              <a href="profile.php" class="btn" style="padding: 12px 20px; text-decoration: none; color: #555; margin-right: 15px;">Cancel</a>
              <button type="submit" name="submit" class="btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
        <?php
}
elseif (isset($_GET['w'])) {
  // WISHLIST VIEW
  $aid = $_SESSION['aid'];
  $query = "SELECT w.*, p.pname, p.price, p.img, p.qtyavail 
            FROM wishlist w 
            JOIN products p ON w.pid = p.pid 
            WHERE w.aid = $aid";
  $result = mysqli_query($con, $query);
?>
        <div class="profile-card">
          <div class="profile-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Your Wishlist</h2>
            <?php if (isset($_GET['msg'])): ?>
              <span style="font-size: 14px; color: #088178; font-weight: 600;">
                <?php
    if ($_GET['msg'] == 'added_to_cart')
      echo "<i class='fas fa-check-circle'></i> Item added to cart!";
    if ($_GET['msg'] == 'removed')
      echo "<i class='fas fa-trash'></i> Item removed!";
    if ($_GET['msg'] == 'out_of_stock')
      echo "<i class='fas fa-exclamation-triangle'></i> Out of stock!";
?>
              </span>
            <?php
  endif; ?>
          </div>
          
          <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
              <thead>
                <tr style="background: #f9f9f9; border-bottom: 2px solid #eee;">
                  <th style="padding: 12px;">Product</th>
                  <th style="padding: 12px; text-align: center;">Price</th>
                  <th style="padding: 12px; text-align: center;">Availability</th>
                  <th style="padding: 12px; text-align: right;">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
  if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)):
      $is_in_stock = ($row['qtyavail'] > 0);
?>
                  <tr style="border-bottom: 1px solid #f0f0f0;">
                    <td style="padding: 12px;">
                      <div style="display: flex; align-items: center; gap: 15px;">
                        <img src="product_images/<?php echo $row['img']; ?>" width="50" height="50" style="object-fit: contain; border-radius: 8px; background: #f9f9f9; padding: 5px;">
                        <div>
                          <div style="font-weight: 600; color: #333; font-size: 14px;"><?php echo $row['pname']; ?></div>
                          <div style="font-size: 11px; color: #888;">#PID-<?php echo $row['pid']; ?></div>
                        </div>
                      </div>
                    </td>
                    <td style="padding: 12px; text-align: center; font-weight: 700; color: #088178; font-size: 14px;">
                      <?php echo $web_settings['currency'] ?? '$'; ?><?php echo number_format($row['price'], 2); ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                      <?php if ($is_in_stock): ?>
                        <span style="color: #27ae60; font-size: 12px; font-weight: 600;"><i class="fas fa-check"></i> In Stock</span>
                      <?php
      else: ?>
                        <span style="color: #e74c3c; font-size: 12px; font-weight: 600;"><i class="fas fa-times"></i> Out of Stock</span>
                      <?php
      endif; ?>
                    </td>
                    <td style="padding: 12px; text-align: right;">
                      <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <?php if ($is_in_stock): ?>
                          <a href="profile.php?wish_cart=<?php echo $row['pid']; ?>" class="btn-action btn-cart" title="Add to Cart">
                            <i class="fas fa-shopping-cart"></i>
                          </a>
                        <?php
      endif; ?>
                        <a href="profile.php?wish_re=<?php echo $row['pid']; ?>" class="btn-action btn-remove" title="Remove" onclick="return confirm('Remove this item from wishlist?')">
                          <i class="fas fa-trash-alt"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php
    endwhile;
  }
  else {
    echo "<tr><td colspan='4' style='padding: 40px; text-align: center; color: #888;'>
                          <i class='far fa-heart' style='font-size: 40px; margin-bottom: 10px; display: block;'></i>
                          Your wishlist is empty.
                        </td></tr>";
  }
?>
              </tbody>
            </table>
          </div>
        </div>
<?php
}
elseif (isset($_GET['odd'])) {
  // REVIEW FORM
  $oid = $_GET['odd'];
  $query = "select * from `order-details` where oid = $oid";
  $result = mysqli_query($con, $query);
  echo "<div class='profile-card'>
                    <div class='profile-header'><h2>Write a Review</h2></div>
                    <form method='post'> 
                    <div style='overflow-x: auto;'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <thead>
                                <tr style='border-bottom: 2px solid #eee;'>
                                    <th style='padding: 15px; text-align: left;'>Product</th>
                                    <th style='padding: 15px; text-align: center;'>Image</th>
                                    <th style='padding: 15px; text-align: left;'>Price</th>
                                    <th style='padding: 15px; text-align: left;'>Review</th>
                                    <th style='padding: 15px; text-align: left;'>Rating</th>
                                </tr>
                            </thead>
                            <tbody>";

  while ($row = mysqli_fetch_assoc($result)) {
    include("include/connect.php");
    $pid = $row['pid'];
    $p_query = "SELECT od.*, p.img, p.pname FROM `order-details` od JOIN products p ON od.pid = p.pid WHERE od.pid = $pid AND od.oid = $oid";
    $p_res = mysqli_query($con, $p_query);
    $row2 = mysqli_fetch_assoc($p_res);
    $img = $row2['img'];
    $pname = $row2['pname'];
    $price = $row2['price'];
    echo "<tr style='border-bottom: 1px solid #f0f0f0;'>
                        <td style='padding: 15px;'>$pname</td>
                        <td style='padding: 15px; text-align: center;'><img src='product_images/$img' width='50' height='50' style='object-fit: contain;'></td>
                        <td style='padding: 15px;'>{$web_settings['currency']}$price</td>
                        <td style='padding: 15px;'><textarea name='$pid-te' class='form-control' style='width: 100%; min-width: 200px; height: 60px;'></textarea></td>
                        <td style='padding: 15px;'>
                          <fieldset class='rating' id='a-$pid-rating'>
                            <input type='radio' onclick='bruh(`$pid`)' id='$pid-rating1' name='$pid-rating' value='1' required><label for='$pid-rating1'></label>
                            <input type='radio' onclick='bruh(`$pid`)' id='$pid-rating2' name='$pid-rating' value='2'><label for='$pid-rating2'></label>
                            <input type='radio' onclick='bruh(`$pid`)' id='$pid-rating3' name='$pid-rating' value='3'><label for='$pid-rating3'></label>
                            <input type='radio' onclick='bruh(`$pid`)' id='$pid-rating4' name='$pid-rating' value='4'><label for='$pid-rating4'></label>
                            <input type='radio' onclick='bruh(`$pid`)' id='$pid-rating5' name='$pid-rating' value='5'><label for='$pid-rating5'></label>
                          </fieldset>
                        </td>
                      </tr><script>bruh(`$pid`);</script>";
  }
  echo "</tbody></table>
                   <div style='margin-top: 20px; text-align: right;'><button type='submit' name='abc' class='btn-primary'>Submit Reviews</button></div>
                   </form></div></div>";
}
elseif (isset($_GET['edit_ord'])) {
  // EDIT ORDER FORM (Customer)
  $oid = intval($_GET['edit_ord']);
  $aid = $_SESSION['aid'];

  $order_query = mysqli_query($con, "SELECT * FROM orders WHERE oid = $oid AND aid = $aid");
  $order = mysqli_fetch_assoc($order_query);

  if (!$order || in_array($order['status'], ['Shipped', 'Delivered', 'Cancelled'])) {
    echo "<div class='alert alert-error'>This order cannot be edited.</div>";
  }
  else {
    $items_query = mysqli_query($con, "SELECT od.*, p.pname, p.img FROM `order-details` od JOIN products p ON od.pid = p.pid WHERE od.oid = $oid");
?>
          <div class="profile-card">
            <div class="profile-header">
              <h2>Edit Order #<?php echo $oid; ?></h2>
              <p style="color: #888; margin-top: 5px;">You can only update your delivery address and contact phone number. To modify items, please cancel and re-order.</p>
            </div>

            <form method="POST">
              <input type="hidden" name="oid" value="<?php echo $oid; ?>">

              <div class="form-group" style="margin-bottom: 20px;">
                <label>Delivery Address</label>
                <textarea name="address" class="form-control" rows="3" required><?php echo $order['address']; ?></textarea>
              </div>

              <div class="form-group" style="margin-bottom: 30px;">
                <label>Contact Phone</label>
                <?php
    // Fetch current user phone if not directly in order table (orders table doesn't have phone col usually)
    $user_phone_query = mysqli_query($con, "SELECT phone FROM accounts WHERE aid = $aid");
    $user_phone = mysqli_fetch_assoc($user_phone_query)['phone'];
?>
                <input type="text" name="phone" class="form-control" value="<?php echo $user_phone; ?>" required>
              </div>

              <!-- Read-only Items View -->
              <h3 style="margin-bottom: 20px; color: #888; font-size: 16px;"><i class="fas fa-shopping-basket"></i> Order Items (Read Only)</h3>
              <div style="overflow-x: auto; margin-bottom: 25px; opacity: 0.7;">
                <table style="width: 100%; border-collapse: collapse;">
                  <thead>
                    <tr style="border-bottom: 2px solid #eee; background: #f9f9f9;">
                      <th style="padding: 12px;">Product</th>
                      <th style="padding: 12px; text-align: center;">Price</th>
                      <th style="padding: 12px; text-align: center;">Quantity</th>
                      <th style="padding: 12px; text-align: right;">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($item = mysqli_fetch_assoc($items_query)): ?>
                      <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;">
                          <div style="display: flex; align-items: center; gap: 10px;">
                            <img src="product_images/<?php echo $item['img']; ?>" width="40" height="40" style="object-fit: contain; border-radius: 4px;">
                            <span style="font-weight: 500;"><?php echo $item['pname']; ?></span>
                          </div>
                        </td>
                        <td style="padding: 12px; text-align: center;"><?php echo $web_settings['currency'] ?? '$'; ?><?php echo $item['price']; ?></td>
                        <td style="padding: 12px; text-align: center;"><?php echo $item['qty']; ?></td>
                        <td style="padding: 12px; text-align: right;"><?php echo $web_settings['currency'] ?? '$'; ?><?php echo number_format($item['price'] * $item['qty'], 2); ?></td>
                      </tr>
                    <?php
    endwhile; ?>
                  </tbody>
                </table>
              </div>

              <div style="text-align: right; border-top: 1px solid #eee; padding-top: 20px;">
                <a href="profile.php" class="btn" style="padding: 12px 25px; text-decoration: none; color: #666; font-weight: 600; margin-right: 15px;">Discard</a>
                <button type="submit" name="update_customer_order" class="btn-primary">Save Changes</button>
              </div>
            </form>
          </div>

          <script>
            function changeQty(pid, delta) {
              const input = document.getElementById('qty-' + pid);
              let val = parseInt(input.value) + delta;
              if (val < 1) val = 1;
              input.value = val;
            }
          </script>
      <?php
  }
}
else {
  // DASHBOARD VIEW
  $aid = $_SESSION['aid'];
  $query = "SELECT * FROM ACCOUNTS WHERE aid = $aid";
  $result = mysqli_query($con, $query);
  $row = mysqli_fetch_assoc($result);
  $afname = $row['afname'];
  $alname = $row['alname'];
  $phone = $row['phone'];
  $email = $row['email'];
  $cnic = $row['cnic'];
  $dob = $row['dob'];
  $user = $row['username'];
  $gender = $row['gender'];

  echo "<div class='profile-card'>
                    <div class='profile-header'><h2>Personal Information</h2></div>
                    <div class='data-row'><div class='data-label'>Full Name</div><div class='data-value'>$afname $alname</div></div>
                    <div class='data-row'><div class='data-label'>Username</div><div class='data-value'>$user</div></div>
                    <div class='data-row'><div class='data-label'>Email Address</div><div class='data-value'>$email</div></div>
                    <div class='data-row'><div class='data-label'>Phone Number</div><div class='data-value'>$phone</div></div>
                    <div class='data-row'><div class='data-label'>CNIC</div><div class='data-value'>$cnic</div></div>
                    <div class='data-row'><div class='data-label'>Date of Birth</div><div class='data-value'>$dob</div></div>
                    <div class='data-row' style='border-bottom: none;'><div class='data-label'>Gender</div><div class='data-value'>$gender</div></div>
                  </div>";

  // ORDERS SECTION moved to full width below
  echo "</div>"; // Close profile-main
  echo "</div>"; // Close profile-container

  echo "<div class='profile-container' style='display: block;'>";
  echo "<div class='profile-card' style='width: 100%; box-sizing: border-box;'>
                    <div class='profile-header'><h2>Recent Orders</h2></div>
                    <div style='overflow-x: auto;'>
                        <table style='width: 100%; border-collapse: collapse; text-align: left;'>
                            <thead>
                                <tr style='background: #f9f9f9; border-bottom: 2px solid #eee;'>
                                    <th style='padding: 12px;'>Order ID</th>
                                    <th style='padding: 12px;'>Ordered Date</th>
                                    <th style='padding: 12px;'>Delivered Date</th>
                                    <th style='padding: 12px;'>Total</th>
                                    <th style='padding: 12px;'>Address</th>
                                    <th style='padding: 12px;'>Order Status</th>
                                    <th style='padding: 12px;'>Payment Status</th>
                                    <th style='padding: 12px;'>Action</th>
                                </tr>
                            </thead>
                            <tbody>";
  $query = "SELECT orders.*, accounts.afname, accounts.alname FROM orders JOIN accounts ON orders.aid = accounts.aid WHERE orders.aid = $aid ORDER BY orders.dateod DESC";
  $result = mysqli_query($con, $query);
  while ($row = mysqli_fetch_assoc($result)) {
    $oid = $row['oid'];
    $dateod = $row['dateod'];
    $status = !empty($row['status']) ? $row['status'] : "Pending";
    $datedel = !empty($row['datedel']) ? $row['datedel'] : ($status == 'Cancelled' ? 'N/A' : "In Progress");

    $add = $row['address'];
    $pri = $row['total'];

    // Order Status Color Mapping (matching 2nd image/admin dashboard)
    $status_bg = "#f5f5f5";
    $status_text = "#666";

    switch ($status) {
      case 'Pending':
        $status_bg = "#ffeeba";
        $status_text = "#856404";
        break;
      case 'Processing':
        $status_bg = "#b8daff";
        $status_text = "#004085";
        break;
      case 'Shipped':
        $status_bg = "#d1ecf1";
        $status_text = "#0c5460";
        break;
      case 'Delivered':
        $status_bg = "#c3e6cb";
        $status_text = "#155724";
        break;
      case 'Cancelled':
        $status_bg = "#f8d7da";
        $status_text = "#721c24";
        break;
    }

    // Payment Status Logic
    $payment_status_db = $row['payment_status'] ?? 'pending';
    $isPaid = (!empty($row['account']) || strtolower($payment_status_db) == 'paid');

    $paymentStatus = $isPaid ? 'Paid' : 'Unpaid (COD)';
    if ($isPaid && !empty($row['payment_method']) && $row['payment_method'] != 'cod') {
      $paymentStatus = 'Paid (' . ucfirst($row['payment_method']) . ')';
    }

    $pay_bg = $isPaid ? '#d1e7dd' : '#fff3cd';
    $pay_text = $isPaid ? '#0f5132' : '#664d03';

    if ($status === 'Delivered' && !$isPaid) {
      $paymentStatus = 'Paid (COD)';
      $pay_bg = '#d1e7dd';
      $pay_text = '#0f5132';
    }

    echo "<tr style='border-bottom: 1px solid #f0f0f0;'>
                            <td style='padding: 12px;'>#$oid</td>
                            <td style='padding: 12px;'>$dateod</td>
                            <td style='padding: 12px;'><span style='color: #666;'>$datedel</span></td>
                            <td style='padding: 12px;'>{$web_settings['currency']}$pri</td>
                            <td style='padding: 12px;'>$add</td>
                            <td style='padding: 12px;'><span class='status-badge' style='background: " . $status_bg . "; color: " . $status_text . "; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;'>$status</span></td>
                            <td style='padding: 12px;'><span class='status-badge' style='background: " . $pay_bg . "; color: " . $pay_text . "; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;'>$paymentStatus</span></td>
                            <td style='padding: 12px; display: flex; gap: 8px;'>";

    // Action Logic
    $disabled_statuses = ['Shipped', 'Delivered', 'Cancelled'];
    $can_action = !in_array($status, $disabled_statuses);

    echo "<a href='order_details.php?oid=$oid' class='btn-action btn-view' title='View Details'><i class='fas fa-eye'></i></a>";

    if ($can_action) {
      echo "<a href='profile.php?edit_ord=$oid' class='btn-action btn-edit' title='Edit Order'><i class='fas fa-edit'></i></a>";
      echo "<a href='profile.php?cancel_ord=$oid' class='btn-action btn-cancel' onclick='return confirm(\"Are you sure you want to cancel this order?\")' title='Cancel Order'><i class='fas fa-times-circle'></i></a>";
    }
    else {
      echo "<span class='btn-action btn-disabled' title='Edit Disabled'><i class='fas fa-edit'></i></span>";
      echo "<span class='btn-action btn-disabled' title='Cancel Disabled'><i class='fas fa-times-circle'></i></span>";
    }

    if ($status == "Delivered") {
      $query1 = "select * from reviews where oid = $oid";
      $r = mysqli_query($con, $query1);
      if (mysqli_num_rows($r) == 0)
        echo "<a href='profile.php?odd=$oid' class='btn-action btn-review' title='Write Review'><i class='fas fa-star'></i></a>";
      else
        echo "<span style='color: #28a745; font-size: 16px; align-self: center; margin-left: 5px;' title='Reviewed'><i class='fas fa-check-circle'></i></span>";
    }

    echo "</td></tr>";
  }
  echo "</tbody></table></div></div></div>";
}
?>
    </div>
  </div>

  <?php include('footer.php'); ?>

  <script src="script.js"></script>

  <script>
    // Get all the rating fields on the page
    function bruh(param) {
      console.log(param);
      const ratingFields = document.querySelectorAll('#a-' + param + '-rating');

      // Loop through each rating field
      ratingFields.forEach(ratingField => {
        // Get all the stars in this rating field
        const stars = ratingField.querySelectorAll('input[type="radio"]');

        // Loop through each star
        stars.forEach(star => {
          // Listen for click events on this star
          star.addEventListener('click', function() {
            // Set the clicked star and all the stars before it to be checked and filled


            for (let i = 0; i < star.value; i++) {
              console.log('hello');
              stars[i].checked = true;
              stars[i].nextElementSibling.classList.add('checked');
            }

            // Set all the stars after the clicked star to be unchecked and empty
            for (let i = star.value; i < stars.length; i++) {
              stars[i].checked = false;
              console.log('hello');

              stars[i].nextElementSibling.classList.remove('checked');
            }
          });
        });
      });
    }
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