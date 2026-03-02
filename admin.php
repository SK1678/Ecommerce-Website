<?php
session_start();
include("include/connect.php");

if (isset($_POST['submit'])) {

    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    // Check if user exists with correct credentials and an administrative role
    $query = "SELECT * FROM accounts WHERE username='$username' AND password='$password' AND user_role IN ('admin', 'superadmin', 'operator')";
    $result = mysqli_query($con, $query);

    if (mysqli_num_rows($result) > 0) {
        $user_data = mysqli_fetch_assoc($result);
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['user_role'] = $user_data['user_role'];
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "<script> alert('Invalid credentials or unauthorized access') </script>";
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
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />

    <link rel="stylesheet" href="style.css" />

</head>

<body>
    <?php include('header.php'); ?>


    <form method="post" id="form">
        <h3 style="color: darkred; margin: auto"></h3>
        <input class="input1" id="user" name="username" type="text" placeholder="Username *">
        <input class="input1" id="pass" name="password" type="password" placeholder="Password *">
        <button type="submit" class="btn" name="submit">login</button>

    </form>


    <?php include('footer.php'); ?>

    <script src="script.js"></script>
</body>

</html>