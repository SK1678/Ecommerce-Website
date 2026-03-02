<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch current admin user info centrally
if (isset($_SESSION['username'])) {
    $admin_username = $_SESSION['username'];
    $admin_query = mysqli_query($con, "SELECT * FROM accounts WHERE username = '$admin_username'");
    $admin_user = mysqli_fetch_assoc($admin_query);

    if ($admin_user) {
        $admin_img_path = 'img/users/' . $admin_user['profile_img'];
        $admin_img = (!empty($admin_user['profile_img']) && file_exists($admin_img_path)) ? $admin_img_path : "https://ui-avatars.com/api/?name=" . urlencode($admin_user['afname'] . ' ' . $admin_user['alname']) . "&background=random&color=fff";
        $display_admin_name = $admin_user['username'];
    }
    else {
        $admin_img = "https://ui-avatars.com/api/?name=" . urlencode($admin_username) . "&background=random&color=fff";
        $display_admin_name = $admin_username;
    }
}
?>
<div class="sidebar">
    <div class="sidebar-logo" style="padding: 20px; text-align: center;">
        <img src="<?php echo !empty($web_settings['logo']) ? $web_settings['logo'] : 'img/logo.png'; ?>" alt="Logo" style="width: 100%; max-width: 150px;">
    </div>
    <ul>
        <li><a href="index.php" target="_blank"><i class="fas fa-globe"></i> View Website</a></li>
        <li><a href="admin_dashboard.php" class="<?php echo($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
        <li><a href="inventory.php" class="<?php echo($current_page == 'inventory.php' || $current_page == 'add_product.php') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Products</a></li>
        <li><a href="categories.php" class="<?php echo($current_page == 'categories.php') ? 'active' : ''; ?>"><i class="fas fa-tags"></i> Categories</a></li>
        <li><a href="orders.php" class="<?php echo($current_page == 'orders.php') ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Orders</a></li>
        <!-- Settings Parent Menu -->
        <li class="has-submenu">
            <a href="javascript:void(0)" class="menu-toggle <?php echo(in_array($current_page, ['settings_manager.php', 'slider_manager.php', 'hero_manager.php', 'feature_manager.php', 'payment_settings.php', 'mail_settings.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-cogs"></i> Settings <i class="fas fa-chevron-down submenu-icon"></i>
            </a>
            <ul class="submenu" style="display: <?php echo(in_array($current_page, ['settings_manager.php', 'slider_manager.php', 'hero_manager.php', 'feature_manager.php', 'payment_settings.php', 'mail_settings.php'])) ? 'block' : 'none'; ?>;">
                <li><a href="settings_manager.php" class="<?php echo($current_page == 'settings_manager.php') ? 'active' : ''; ?>"><i class="fas fa-tools"></i> Global Settings</a></li>
                <li><a href="payment_settings.php" class="<?php echo($current_page == 'payment_settings.php') ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Payment Settings</a></li>
                <li><a href="slider_manager.php" class="<?php echo($current_page == 'slider_manager.php') ? 'active' : ''; ?>"><i class="fas fa-images"></i> Sliders</a></li>
                <li><a href="hero_manager.php" class="<?php echo($current_page == 'hero_manager.php') ? 'active' : ''; ?>"><i class="fas fa-star"></i> Hero Section</a></li>
                <li><a href="feature_manager.php" class="<?php echo($current_page == 'feature_manager.php') ? 'active' : ''; ?>"><i class="fas fa-check-circle"></i> Features</a></li>
                <li><a href="mail_settings.php" class="<?php echo($current_page == 'mail_settings.php') ? 'active' : ''; ?>"><i class="fas fa-envelope-open-text"></i> Mail Settings</a></li>
            </ul>
        </li>

        <!-- Users Parent Menu -->
        <li class="has-submenu">
            <a href="javascript:void(0)" class="menu-toggle <?php echo(in_array($current_page, ['users.php', 'employee_manager.php', 'customer_manager.php'])) ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Users <i class="fas fa-chevron-down submenu-icon"></i>
            </a>
            <ul class="submenu" style="display: <?php echo(in_array($current_page, ['employee_manager.php', 'customer_manager.php'])) ? 'block' : 'none'; ?>;">
                <li><a href="employee_manager.php" class="<?php echo($current_page == 'employee_manager.php') ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> Employee Manager</a></li>
                <li><a href="customer_manager.php" class="<?php echo($current_page == 'customer_manager.php') ? 'active' : ''; ?>"><i class="fas fa-user"></i> Customer Manager</a></li>
            </ul>
        </li>

        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<style>
    /* Submenu Styles */
    .has-submenu .menu-toggle {
        position: relative;
    }

    .submenu-icon {
        position: absolute;
        right: 15px;
        font-size: 12px;
        transition: transform 0.3s ease;
    }

    .has-submenu .menu-toggle.open .submenu-icon {
        transform: rotate(180deg);
    }

    .submenu {
        list-style: none;
        padding-left: 0;
        margin-top: 5px;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .submenu li {
        margin-bottom: 3px;
    }

    .submenu li a {
        color: #8a8a8a;
        text-decoration: none;
        font-size: 14px;
        display: flex;
        align-items: center;
        padding: 8px 15px 8px 45px;
        border-radius: 6px;
        transition: var(--transition);
    }

    .submenu li a i {
        margin-right: 8px;
        width: 16px;
        text-align: center;
        font-size: 13px;
    }

    .submenu li a:hover,
    .submenu li a.active {
        background: rgba(8, 129, 120, 0.2);
        color: var(--primary-color);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggles = document.querySelectorAll('.menu-toggle');

        menuToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const parent = this.parentElement;
                const submenu = parent.querySelector('.submenu');

                // Toggle open class
                this.classList.toggle('open');

                // Toggle submenu visibility
                if (submenu.style.display === 'none' || submenu.style.display === '') {
                    submenu.style.display = 'block';
                } else {
                    submenu.style.display = 'none';
                }
            });
        });
    });
</script>