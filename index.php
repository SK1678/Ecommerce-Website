<?php
session_start();
include("include/connect.php");

if (empty($_SESSION['aid']))
    $_SESSION['aid'] = -1;
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
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />

    <link rel="stylesheet" href="style.css" />


</head>

<body>
    <?php include('header.php'); ?>

    <?php
    // Fetch Active Hero
    $hero_res = $con->query("SELECT * FROM hero WHERE is_active = 1 AND page_name = 'index.php' LIMIT 1");
    if ($hero_res && $hero_res->num_rows > 0) {
        $hero = $hero_res->fetch_assoc();
        // Dynamic Style for BG
        $hero_style = "background-image: url('{$hero['bg_image']}');";
    ?>
        <section id="hero" style="<?php echo $hero_style; ?>">
            <h4><?php echo $hero['sub_title']; ?></h4>
            <h2><?php echo $hero['main_title']; ?></h2>
            <h1><?php echo $hero['big_title']; ?></h1>
            <p><?php echo $hero['description']; ?></p>
            <a href="<?php echo $hero['btn_link']; ?>">
                <button><?php echo $hero['btn_text']; ?></button>
            </a>
        </section>
    <?php } else { ?>
        <!-- Fallback Static Hero -->
        <section id="hero">
            <h4>Trade-in-offer</h4>
            <h2>Super value deals</h2>
            <h1>On all products</h1>
            <p>Save more with coupons & up to 70% off!</p>
            <a href="shop.php">
                <button>Shop Now</button>
            </a>
        </section>
    <?php } ?>

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

    <section id="slider-container" style="overflow: hidden; width: 100%;">
        <?php
        // Fetch sliders
        $slider_res = $con->query("SELECT * FROM slider ORDER BY sort_order ASC, id DESC");
        if ($slider_res && $slider_res->num_rows > 0) {
        ?>
            <div class="slider-wrapper" style="position: relative; width: 100%; height: 40vh;">
                <?php
                $i = 0;
                while ($slide = $slider_res->fetch_assoc()) {
                    $styleDisplay = $i === 0 ? 'flex' : 'none';
                ?>
                    <div class="banner-slide" style="
                display: <?php echo $styleDisplay; ?>;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
                background-image: url('<?php echo $slide['image']; ?>');
                width: 100%;
                height: 100%;
                background-size: cover;
                background-position: center;
                animation: fadeIn 1s;
                position: absolute;
                top: 0; left: 0;
            ">
                        <h4 style="color: #fff; font-size: 16px;"><?php echo $slide['subtitle']; ?></h4>
                        <h2 style="color: #fff; font-size: 30px; padding: 10px 0;"><?php echo $slide['title']; ?></h2>
                        <a href="<?php echo $slide['btn_link']; ?>">
                            <button class="normal" style="
                        background-image: url('img/button.png');
                        background-color: transparent;
                        color: #088178;
                        border: 0;
                        padding: 14px 80px 14px 65px;
                        background-repeat: no-repeat;
                        cursor: pointer;
                        font-weight: 700;
                        font-size: 15px;
                    "><?php echo $slide['btn_text']; ?></button>
                        </a>
                    </div>
                <?php $i++;
                } ?>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        let slides = document.querySelectorAll('.banner-slide');
                        if (slides.length > 1) {
                            let current = 0;
                            setInterval(() => {
                                slides[current].style.display = 'none';
                                current = (current + 1) % slides.length;
                                slides[current].style.display = 'flex';
                            }, 5000); // 5 Seconds rotation
                        }
                    });
                </script>
            </div>
        <?php } else { ?>
            <!-- Fallback Static Banner if no slider exists -->
            <section id="banner" class="section-m1">
                <h4>Summer Sale</h4>
                <h2>Up to <span>70% Off</span> - All CPUs & GPUs</h2>
                <a href="shop.php">
                    <button class="normal">Explore More</button>
                </a>
            </section>
        <?php } ?>
    </section>

    <section id="product2" class="section-p1">
        <h2>Top Selling Products</h2>
        <p>Best Sellers of the Month</p>
        <div class="pro-container">
            <?php
            // Query for top selling products linked to order-details
            $top_query = "SELECT p.*, SUM(od.qty) as total_sold
                          FROM products p
                          JOIN `order-details` od ON p.pid = od.pid
                          WHERE p.is_active = 1 OR p.is_active IS NULL
                          GROUP BY p.pid
                          ORDER BY total_sold DESC
                          LIMIT 12";

            $top_result = mysqli_query($con, $top_query);

            if (mysqli_num_rows($top_result) > 0) {
                while ($row = mysqli_fetch_assoc($top_result)) {
            ?>
                    <div class="pro" onclick="window.location.href='sproduct.php?pid=<?php echo $row['pid']; ?>'">
                        <img src="product_images/<?php echo $row['img']; ?>" alt="">
                        <div class="des">
                            <span><?php echo $row['brand']; ?></span>
                            <h5><?php echo $row['pname']; ?></h5>
                            <div class="star">
                                <?php
                                $pid = $row['pid'];
                                $rating_query = "SELECT AVG(rating) AS average_rating FROM reviews WHERE pid = $pid";
                                $rating_result = mysqli_query($con, $rating_query);
                                $rating_row = mysqli_fetch_assoc($rating_result);
                                $stars = $rating_row['average_rating'] ? round($rating_row['average_rating']) : 0;

                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $stars) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <h4><?php echo $web_settings['currency'] ?? '$'; ?><?php echo $row['price']; ?></h4>
                        </div>
                        <?php if ($row['qtyavail'] > 0): ?>
                            <a href="cart.php?pid=<?php echo $row['pid']; ?>"><i class="fal fa-shopping-cart cart"></i></a>
                        <?php else: ?>
                            <div class="out-of-stock-badge" style="position: absolute; right: 10px; bottom: 10px; background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; border: 1px solid #f5c6cb;">OUT OF STOCK</div>
                        <?php endif; ?>
                    </div>
            <?php
                }
            } else {
                echo "<p>No top selling products found yet.</p>";
            }
            ?>
        </div>
    </section>

    <section id="product1" class="section-p1">
        <h2>Featured Products</h2>
        <p>New Morden Design</p>
        <div class="pro-container">
            <?php
            $feat_query = "SELECT DISTINCT p.* 
                           FROM products p
                           JOIN product_tags pt ON p.pid = pt.pid
                           JOIN tags t ON pt.tag_id = t.tag_id
                           WHERE (t.tag_name = 'feature' OR t.tag_name = 'featured' OR t.tag_name = 'Feature')
                           AND (p.is_active IS NULL OR p.is_active = 1)
                           ORDER BY p.pid DESC LIMIT 8";

            $feat_result = mysqli_query($con, $feat_query);

            if (mysqli_num_rows($feat_result) > 0) {
                while ($row = mysqli_fetch_assoc($feat_result)) {
            ?>
                    <div class="pro" onclick="window.location.href='sproduct.php?pid=<?php echo $row['pid']; ?>'">
                        <img src="product_images/<?php echo $row['img']; ?>" alt="">
                        <div class="des">
                            <span><?php echo $row['brand']; ?></span>
                            <h5><?php echo $row['pname']; ?></h5>
                            <div class="star">
                                <?php
                                $pid = $row['pid'];
                                $rating_query = "SELECT AVG(rating) AS average_rating FROM reviews WHERE pid = $pid";
                                $rating_result = mysqli_query($con, $rating_query);
                                $rating_row = mysqli_fetch_assoc($rating_result);
                                $stars = $rating_row['average_rating'] ? round($rating_row['average_rating']) : 0;

                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $stars) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <h4><?php echo $web_settings['currency'] ?? '$'; ?><?php echo $row['price']; ?></h4>
                        </div>
                        <?php if ($row['qtyavail'] > 0): ?>
                            <a href="cart.php?pid=<?php echo $row['pid']; ?>"><i class="fal fa-shopping-cart cart"></i></a>
                        <?php else: ?>
                            <div class="out-of-stock-badge" style="position: absolute; right: 10px; bottom: 10px; background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; border: 1px solid #f5c6cb;">OUT OF STOCK</div>
                        <?php endif; ?>
                    </div>
            <?php
                }
            } else {
                echo "<p>No featured products found.</p>";
            }
            ?>
        </div>
    </section>






    <?php include('footer.php'); ?>

    <script src="script.js"></script>
</body>

</html>

<script>
    window.addEventListener("onunload", function() {
        // Call a PHP script to log out the user
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "logout.php", false);
        xhr.send();
    });
</script>