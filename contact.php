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

</head>

<body>
    <?php include('header.php'); ?>

    <?php
    // Fetch Active Hero for Contact Page
    $hero_res = $con->query("SELECT * FROM hero WHERE is_active = 1 AND page_name = 'contact.php' LIMIT 1");
    if ($hero_res && $hero_res->num_rows > 0) {
        $hero = $hero_res->fetch_assoc();
        $bg_style = "background-image: url('{$hero['bg_image']}');";
    ?>
        <section id="page-header" class="about-header" style="<?php echo $bg_style; ?>">
            <h2><?php echo $hero['main_title'] ? $hero['main_title'] : '#letstalk'; ?></h2>
            <p><?php echo $hero['description'] ? $hero['description'] : 'LEAVE A MESSAGE, We love to hear from you!'; ?></p>
        </section>
    <?php } else { ?>
        <!-- Fallback Static -->
        <section id="page-header" class="about-header">
            <h2>#GameTillTheEnd</h2>
            <p>Providing Premium Gaming Experience</p>
        </section>
    <?php } ?>

    <section id="contact-details" class="section-p1">
        <div class="details">
            <span>GET IN TOUCH</span>
            <h2>Visit one of our agency locations or contact us today</h2>
            <h3>Head Office</h3>
            <div>
                <li>
                    <i class="fal fa-map"></i>
                    <p><?php echo $web_settings['address']; ?></p>
                </li>
                <li>
                    <i class="fal fa-envelope"></i>
                    <p><?php echo $web_settings['email']; ?></p>
                </li>
                <li>
                    <i class="fal fa-phone-alt"></i>
                    <p><?php echo $web_settings['phone']; ?></p>
                </li>
                <li>
                    <i class="fal fa-clock"></i>
                    <p><?php echo $web_settings['hours']; ?></p>

                </li>
            </div>
        </div>
        <div class="map">
            <?php if (!empty($web_settings['map_embed_url'])): ?>
                <iframe
                    src="<?php echo $web_settings['map_embed_url']; ?>"
                    width="600" height="450" style="border: 0" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            <?php else: ?>
                <!-- Fallback if no map is set -->
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3402.5655490104923!2d74.29819482695312!3d31.481135199999983!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3919035b866b934b%3A0x8f191ce0dac7aa28!2sFast%20University!5e0!3m2!1sen!2s!4v1679911544138!5m2!1sen!2s"
                    width="600" height="450" style="border: 0" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            <?php endif; ?>
        </div>
    </section>

    
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