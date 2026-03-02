<footer class="section-p1">
    <div class="col">
        <img class="logo" src="<?php echo !empty($web_settings['logo']) ? $web_settings['logo'] : 'img/logo.png'; ?>" />
        <h4>Contact</h4>
        <p><strong>Address: </strong> <?php echo $web_settings['address']; ?></p>
        <p><strong>Phone: </strong> <?php echo $web_settings['phone']; ?></p>
        <p><strong>Email: </strong> <?php echo $web_settings['email']; ?></p>
        <p><strong>Hours: </strong> <?php echo $web_settings['hours']; ?></p>
    </div>

    <div class="col">
        <h4>My Account</h4>
        <a href="cart.php">View Cart</a>
        <a href="wishlist.php">My Wishlist</a>
    </div>
    <div class="col install">
        <p><?php echo $web_settings['footer_about']; ?></p>
        <img src="img/pay/pay.png" />
    </div>
    <div class="copyright">
        <p><?php echo $web_settings['copyright']; ?></p>
    </div>
</footer>