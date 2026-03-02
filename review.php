<?php
session_start();
include_once("include/connect.php");

// 1. Basic session check
if (empty($_SESSION['aid']) || $_SESSION['aid'] < 0) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['aid'];

// Fetch user info for sidebar
$user_query = mysqli_query($con, "SELECT * FROM accounts WHERE aid = '$user_id'");
$user = mysqli_fetch_assoc($user_query);

// Get order ID from URL
if (!isset($_GET['odd'])) {
  header("Location: profile.php");
  exit();
}

$oid = intval($_GET['odd']);

// Verify order belongs to user
$order_check = mysqli_query($con, "SELECT * FROM orders WHERE oid = '$oid' AND aid = '$user_id'");
if (mysqli_num_rows($order_check) == 0) {
  header("Location: profile.php");
  exit();
}

// Handle form submission
$success_msg = "";
$error_msg = "";
if (isset($_POST['submit_reviews'])) {
  $items_query = mysqli_query($con, "SELECT pid FROM `order-details` WHERE oid = '$oid'");
  $submit_success = true;

  while ($item = mysqli_fetch_assoc($items_query)) {
    $pid = $item['pid'];
    $review_text = mysqli_real_escape_string($con, $_POST["review_$pid"]);
    $rating = intval($_POST["rating_$pid"] ?? 0);

    if ($rating > 0) {
      // Check if review already exists
      $check_existing = mysqli_query($con, "SELECT * FROM reviews WHERE oid = '$oid' AND pid = '$pid'");
      if (mysqli_num_rows($check_existing) > 0) {
        $query = "UPDATE reviews SET rtext = '$review_text', rating = '$rating' WHERE oid = '$oid' AND pid = '$pid'";
      } else {
        $query = "INSERT INTO reviews (oid, pid, rtext, rating) VALUES ('$oid', '$pid', '$review_text', '$rating')";
      }

      if (!mysqli_query($con, $query)) {
        $submit_success = false;
      }
    }
  }

  if ($submit_success) {
    $success_msg = "Reviews submitted successfully!";
  } else {
    $error_msg = "Something went wrong while saving reviews.";
  }
}

// Fetch items for this order
$items_query = "SELECT od.*, p.pname, p.img, p.price, r.rtext as existing_review, r.rating as existing_rating
                FROM `order-details` od 
                JOIN products p ON od.pid = p.pid 
                LEFT JOIN reviews r ON od.oid = r.oid AND od.pid = r.pid
                WHERE od.oid = '$oid'";
$items_result = mysqli_query($con, $items_query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Write a Review | ByteBazaar</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="profile_redesign.css">
  <style>
    .star-rating {
      display: flex !important;
      flex-direction: row-reverse !important;
      justify-content: flex-end !important;
      gap: 5px !important;
      width: 150px !important;
      min-width: 150px !important;
      overflow: visible !important;
      white-space: nowrap !important;
    }

    .star-rating input {
      display: none !important;
    }

    .star-rating label {
      cursor: pointer !important;
      width: 28px !important;
      height: 28px !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      transition: all 0.2s !important;
      font-family: "Font Awesome 6 Free" !important;
      font-family: "Font Awesome 6 Free" !important;
      font-weight: 400;
      font-size: 24px !important;
      color: #ccc !important;
      flex-shrink: 0 !important;
      position: relative !important;
      margin: 0 !important;
      padding: 0 !important;
      left: 0 !important;
      top: 0 !important;
    }

    .star-rating label::before {
      content: "\f005";
    }

    .star-rating input:checked~label,
    .star-rating label:hover,
    .star-rating label:hover~label {
      color: #ffc107 !important;
      font-weight: 900 !important;
    }

    .star-rating label:active {
      transform: scale(0.9);
    }

    .review-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .review-table th,
    .review-table td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }

    .review-table th {
      font-weight: 600;
      color: #666;
      text-transform: uppercase;
      font-size: 13px;
    }

    .product-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .product-img {
      width: 50px;
      height: 50px;
      border-radius: 5px;
      object-fit: cover;
    }

    .review-input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      resize: vertical;
      font-family: inherit;
    }

    .alert {
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .btn-submit {
      margin-top: 20px;
      float: right;
    }

    .btn-cancel {
      background: #f8d7da;
      color: #721c24;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn-cancel:hover {
      background: #f5c6cb;
    }
  </style>
</head>

<body>
  <?php include('header.php'); ?>

  <div class="profile-container">
    <!-- Sidebar -->
    <div class="profile-sidebar">
      <div class="profile-user">
        <div class="profile-img-container" style="margin-bottom: 15px;">
          <img src="img/users/<?php echo $user['profile_img'] ? $user['profile_img'] : 'default-avatar.png'; ?>"
            style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;"
            onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($user['afname'] . ' ' . $user['alname']); ?>&background=random&color=fff'">
        </div>
        <h3><?php echo $user['afname'] . ' ' . $user['alname']; ?></h3>
        <p style="color: #888;">Customer</p>
      </div>

      <div class="sidebar-nav">
        <a href="profile.php" class="sidebar-link">
          <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="profile.php?edit=1" class="sidebar-link">
          <i class="fas fa-user-edit"></i> Edit Profile
        </a>
        <a href="logout.php" class="sidebar-link">
          <i class="fas fa-sign-out-alt"></i> Log Out
        </a>
        <a href="profile.php" class="sidebar-link btn-cancel" style="margin-top: 20px;">
          Cancel Review
        </a>
      </div>
    </div>

    <!-- Main Content -->
    <div class="profile-main">
      <div class="profile-card">
        <div class="profile-header">
          <h2>Write a Review</h2>
          <p style="color: #888; margin-top: 5px;">Help others by sharing your experience with these products from Order #<?php echo $oid; ?>.</p>
        </div>

        <?php if ($success_msg): ?>
          <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
          <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST">
          <table class="review-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Image</th>
                <th>Price</th>
                <th>Review</th>
                <th style="width: 150px;">Rating</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($item = mysqli_fetch_assoc($items_result)):
                $pid = $item['pid'];
                $rating = $item['existing_rating'] ?? 0;
              ?>
                <tr>
                  <td style="font-weight: 500; font-size: 15px;"><?php echo $item['pname']; ?></td>
                  <td><img src="product_images/<?php echo $item['img']; ?>" class="product-img"></td>
                  <td><?php echo $web_settings['currency'] ?? '$'; ?><?php echo $item['price']; ?></td>
                  <td>
                    <textarea name="review_<?php echo $pid; ?>" class="review-input" placeholder="What did you think of this product?" rows="2"><?php echo htmlspecialchars($item['existing_review'] ?? ''); ?></textarea>
                  </td>
                  <td>
                    <div class="star-rating">
                      <input type="radio" id="star-5-<?php echo $pid; ?>" name="rating_<?php echo $pid; ?>" value="5" <?php echo $rating == 5 ? 'checked' : ''; ?> />
                      <label for="star-5-<?php echo $pid; ?>" title="5 stars"></label>

                      <input type="radio" id="star-4-<?php echo $pid; ?>" name="rating_<?php echo $pid; ?>" value="4" <?php echo $rating == 4 ? 'checked' : ''; ?> />
                      <label for="star-4-<?php echo $pid; ?>" title="4 stars"></label>

                      <input type="radio" id="star-3-<?php echo $pid; ?>" name="rating_<?php echo $pid; ?>" value="3" <?php echo $rating == 3 ? 'checked' : ''; ?> />
                      <label for="star-3-<?php echo $pid; ?>" title="3 stars"></label>

                      <input type="radio" id="star-2-<?php echo $pid; ?>" name="rating_<?php echo $pid; ?>" value="2" <?php echo $rating == 2 ? 'checked' : ''; ?> />
                      <label for="star-2-<?php echo $pid; ?>" title="2 stars"></label>

                      <input type="radio" id="star-1-<?php echo $pid; ?>" name="rating_<?php echo $pid; ?>" value="1" <?php echo $rating == 1 ? 'checked' : ''; ?> />
                      <label for="star-1-<?php echo $pid; ?>" title="1 star"></label>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>

          <button type="submit" name="submit_reviews" class="btn-primary btn-submit">
            Submit Reviews
          </button>
          <div style="clear: both;"></div>
        </form>
      </div>
    </div>
  </div>

  <?php include('footer.php'); ?>
</body>

</html>