<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<section id="header">
    <a href="index.php"><img src="<?php echo !empty($web_settings['logo']) ? $web_settings['logo'] : 'img/logo.png'; ?>" class="logo" alt="" /></a>

    <div>
        <ul id="navbar">
            <li><a class="<?php echo($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">Home</a></li>
            <li><a class="<?php echo($current_page == 'shop.php' || $current_page == 'sproduct.php') ? 'active' : ''; ?>" href="shop.php">Shop</a></li>
            <li><a class="<?php echo($current_page == 'about.php') ? 'active' : ''; ?>" href="about.php">About</a></li>
            <li><a class="<?php echo($current_page == 'contact.php') ? 'active' : ''; ?>" href="contact.php">Contact</a></li>

            <?php
if (isset($_SESSION['aid']) && $_SESSION['aid'] < 0) {
    echo "<li><a href='login.php' class='" . (($current_page == 'login.php') ? 'active' : '') . "'>login</a></li>
                      <li><a href='signup.php' class='" . (($current_page == 'signup.php') ? 'active' : '') . "'>SignUp</a></li>";
}
else {
    echo "<li><a href='profile.php' class='" . (($current_page == 'profile.php') ? 'active' : '') . "'>profile</a></li>";
}
?>
            <li><a href="admin.php">Admin</a></li>
            <li id="lg-wish">
                <a href="profile.php?w=1" class="<?php echo($current_page == 'profile.php' && isset($_GET['w'])) ? 'active' : ''; ?>"><i class="far fa-heart"></i></a>
            </li>
            <li id="lg-bag">
                <a href="cart.php" class="<?php echo($current_page == 'cart.php') ? 'active' : ''; ?>"><i class="far fa-shopping-bag"></i></a>
            </li>
            <a href="#" id="close"><i class="far fa-times"></i></a>
        </ul>
    </div>
    <div id="mobile">
        <a href="profile.php?w=1"><i class="far fa-heart"></i></a>
        <a href="cart.php"><i class="far fa-shopping-bag"></i></a>
        <i id="bar" class="fas fa-outdent"></i>
    </div>
</section>