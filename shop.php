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
    <link rel="shortcut icon" href="<?php echo !empty($web_settings['favicon']) ? $web_settings['favicon'] : 'img/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" />

    <link rel="stylesheet" href="style.css" />

    <style>
        .search-container {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            background: #e3e6f3;
            padding: 10px;
        }

        #category-filter {
            padding: 6px;
            margin-right: 10px;
            border: none;
            border-radius: 4px;
        }

        #search {
            padding: 6px;
            margin-right: 10px;
            border: none;
            border-radius: 4px;
        }

        #search-btn {
            outline: none;
            border: none;
            padding: 10px 30px;
            background-color: navy;
            color: white;
            border-radius: 1rem;
            cursor: pointer;
        }
    </style>


</head>

<body>
    <?php include('header.php'); ?>

    <?php
    // Fetch Active Hero for Shop Page
    $hero_res = $con->query("SELECT * FROM hero WHERE is_active = 1 AND page_name = 'shop.php' LIMIT 1");
    if ($hero_res && $hero_res->num_rows > 0) {
        $hero = $hero_res->fetch_assoc();
        $bg_style = "background-image: url('{$hero['bg_image']}');";
        // Override Titles/Desc if needed, but Page Header usually has simple structure.
        // #page-header style usually just centers content. We can inject dynamic texts.
    ?>
        <section id="page-header" style="<?php echo $bg_style; ?>">
            <h2><?php echo $hero['main_title'] ? $hero['main_title'] : '#stayhome'; ?></h2>
            <p><?php echo $hero['description'] ? $hero['description'] : 'Save more with coupons & up to 70% off!'; ?></p>
        </section>
    <?php } else { ?>
        <!-- Fallback Static -->
        <section id="page-header">
            <h2>Premium Gaming</h2>
            <p>Save more with coupons & up to 70% off!</p>
        </section>
    <?php } ?>

    <div class="search-container">
        <form id="search-form" method="post">
            <label for="search">Search:</label>
            <input type="text" id="search" name="search">
            <label for="category-filter">Category:</label>
            <select id="category-filter" name="cat">
                <option value="all">All</option>
                <option value="keyboard">Keyboard</option>
                <option value="motherboard">Motherboard</option>
                <option value="mouse">Mouse</option>
                <option value="cpu">CPU</option>
                <option value="gpu">GPU</option>
                <option value="ram">RAM</option>
            </select>
            <button type="submit" id="search-btn" name="search1">Search</button>
        </form>
    </div>

    <?php
    if (isset($_POST['search1'])) {
        $search = $_POST['search'];
        $category = $_POST['cat'];
        $query = "";
        if (!empty($search))
            $query = "select* from `products` where ((pname like '%$search%') or (brand like '%$search%') or (description like '%$search%'))";
        else
            $query = "select * from `products`";

        if ($category != "all") {
            if (empty($search)) {
                $query = $query . "where category = '$category'";
            } else {
                $query = $query . "and category = '$category'";
            }
        }

        $result = mysqli_query($con, $query);

        if ($result) {
            echo "<section id='product1' class='section-p1'>
                    <div class='pro-container'>";
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $pid = $row['pid'];
            $pname = $row['pname'];
            if (strlen($pname) > 35) {
                $pname = substr($pname, 0, 35) . '...';
            }
            $desc = $row['description'];
            $qty = $row['qtyavail'];
            $price = $row['price'];
            $cat = $row['category'];
            $img = $row['img'];
            $brand = $row['brand'];


            $query2 = "SELECT pid, AVG(rating) AS average_rating FROM reviews where pid = $pid GROUP BY pid ";

            $result2 = mysqli_query($con, $query2);

            $row2 = mysqli_fetch_assoc($result2);

            if ($row2) {
                $stars = $row2['average_rating'];
            } else {
                $stars = 0;
            }
            $stars = round($stars, 0);
            $empty = 5 - $stars;

            echo "
                    <div class='pro' onclick='topage($pid)'>
                      <img src='product_images/$img' height='235px' width = '235px' alt='' />
                      <div class='des'>
                        <span>$brand</span>
                        <h5>$pname</h5>
                        <div class='star'>";
            for ($i = 1; $i <= $stars; $i++) {
                echo "<i class='fas fa-star'></i>";
            }
            for ($i = 1; $i <= $empty; $i++) {
                echo "<i class='far fa-star'></i>";
            }
            echo "</div>
                        <h4 style='color: #088178; margin-top: 7px;'>{$web_settings['currency']}$price</h4>
                      </div>" . ($qty > 0 ? "
                      <a onclick='topage($pid)'><i class='fal fa-shopping-cart cart'></i></a>
                      " : "
                      <div class='out-of-stock-badge' style='position: absolute; right: 10px; bottom: 10px; background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; border: 1px solid #f5c6cb;'>OUT OF STOCK</div>
                      ") . "
                    </div>
                 ";
        }

        if ($result) {

            echo "</div></section>";
        }
    } else {
        include("include/connect.php");

        $select = "SELECT * FROM products ORDER BY rand()";
        $result = mysqli_query($con, $select);

        if ($result) {
            echo "<section id='product1' class='section-p1'>
                    <div class='pro-container'>";
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $pid = $row['pid'];
            $pname = $row['pname'];
            if (strlen($pname) > 35) {
                $pname = substr($pname, 0, 35) . '...';
            }
            $desc = $row['description'];
            $qty = $row['qtyavail'];
            $price = $row['price'];
            $cat = $row['category'];
            $img = $row['img'];
            $brand = $row['brand'];

            $query2 = "SELECT pid, AVG(rating) AS average_rating FROM reviews where pid = $pid GROUP BY pid ";

            $result2 = mysqli_query($con, $query2);

            $row2 = mysqli_fetch_assoc($result2);

            if ($row2) {
                $stars = $row2['average_rating'];
            } else {
                $stars = 0;
            }
            $stars = round($stars, 0);

            $empty = 5 - $stars;

            echo "
                    <div class='pro' onclick='topage($pid)'>
                      <img src='product_images/$img' height='235px' width = '235px' alt='' />
                      <div class='des'>
                        <span>$brand</span>
                        <h5>$pname</h5>
                        <div class='star'>";
            for ($i = 1; $i <= $stars; $i++) {
                echo "<i class='fas fa-star'></i>";
            }
            for ($i = 1; $i <= $empty; $i++) {
                echo "<i class='far fa-star'></i>";
            }
            echo "</div>
                        <h4 style='color: #088178; margin-top: 7px;'>{$web_settings['currency']}$price</h4>
                      </div>" . ($qty > 0 ? "
                      <a onclick='topage($pid)'><i class='fal fa-shopping-cart cart'></i></a>
                      " : "
                      <div class='out-of-stock-badge' style='position: absolute; right: 10px; bottom: 10px; background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; border: 1px solid #f5c6cb;'>OUT OF STOCK</div>
                      ") . "
                    </div>
                 ";
        }

        if ($result) {

            echo "</div></section>";
        }
    }
    ?>


    <?php include('footer.php'); ?>

    <script src="script.js"></script>
</body>

</html>

<script>
    function topage(pid) {
        window.location.href = `sproduct.php?pid=${pid}`;
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