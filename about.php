<?php
session_start();
include("include/connect.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo !empty($web_settings['site_title']) ? $web_settings['site_title'] : 'ByteBazaar'; ?></title>
    <!-- Favicon -->
    <link rel="icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/logo.png'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />

    <link rel="stylesheet" href="style.css" />

    <style>
        .paragraph {
            line-height: 1.5;
        }
    </style>
</head>

<body>
    <?php include('header.php'); ?>

    <?php
    // Fetch Active Hero for About Page
    $hero_res = $con->query("SELECT * FROM hero WHERE is_active = 1 AND page_name = 'about.php' LIMIT 1");
    if ($hero_res && $hero_res->num_rows > 0) {
        $hero = $hero_res->fetch_assoc();
        $bg_style = "background-image: url('{$hero['bg_image']}');";
    ?>
        <section id="page-header" class="about-header" style="<?php echo $bg_style; ?>">
            <h2><?php echo $hero['main_title'] ? $hero['main_title'] : '#KnowUs'; ?></h2>
            <p><?php echo $hero['description'] ? $hero['description'] : 'Lorem ipsum dolor sit amet, consectetur'; ?></p>
        </section>
    <?php } else { ?>
        <!-- Fallback Static -->
        <section id="page-header" class="about-header">
            <h2>#GameTillTheEnd</h2>
            <p>Providing Premium Gaming Peripherals</p>
        </section>
    <?php } ?>

    <section id="about-head" class="section-p1">
        <img src="img/about/x2.jpg" alt="" />
        <div>
            <h2>About Us?</h2>
            <p class="paragraph">
                Our platform is a modern online marketplace offering a wide selection of premium computer hardware products. Founded with a passion for technology and innovation, we aim to deliver a reliable and seamless online shopping experience. Our platform features the latest computer components, peripherals, and accessories sourced from trusted and leading global brands. With an easy-to-use website and dedicated customer support, One stop Solution is committed to meeting the needs of tech enthusiasts, students, and professionals .
            </p>
            <br /><br />
            <marquee bgcolor="#ccc" loop="-1" scrollamount="5">Game till you win</marquee>
        </div>
    </section>

    <?php
    $feat_res = $con->query("SELECT * FROM features ORDER BY id ASC");
    if ($feat_res && $feat_res->num_rows > 0) {
    ?>
        <section id="feature" class="section-p1">
            <?php while ($feat = $feat_res->fetch_assoc()) { ?>
                <div class="fe-box">
                    <img src="<?php echo $feat['image']; ?>" alt="" />
                    <h6 style="background-color: <?php echo $feat['bg_color']; ?>"><?php echo $feat['title']; ?></h6>
                </div>
            <?php } ?>
        </section>
    <?php } else { ?>
        <!-- Fallback Static Features -->
        <section id="feature" class="section-p1">
            <div class="fe-box">
                <img src="img/features/f1.png" alt="" />
                <h6>Free Shipping</h6>
            </div>
            <div class="fe-box">
                <img src="img/features/f2.png" alt="" />
                <h6>Online Order</h6>
            </div>
            <div class="fe-box">
                <img src="img/features/f3.png" alt="" />
                <h6>Save Money</h6>
            </div>
            <div class="fe-box">
                <img src="img/features/f4.png" alt="" />
                <h6>Promotions</h6>
            </div>
            <div class="fe-box">
                <img src="img/features/f5.png" alt="" />
                <h6>Happy Sell</h6>
            </div>
            <div class="fe-box">
                <img src="img/features/f6.png" alt="" />
                <h6>24/7 Support</h6>
            </div>
        </section>
    <?php } ?>


    <?php include('footer.php'); ?>

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