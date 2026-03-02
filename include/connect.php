<?php
$con = mysqli_connect('localhost', 'root', '', 'project');
if (!$con) {
    die("Connection failed" . ($con ? ": " . mysqli_error($con) : ""));
}

// Fetch Global Website Settings
$web_settings = array();
$setting_res = $con->query("SELECT * FROM website_settings LIMIT 1");
if ($setting_res && $setting_res->num_rows > 0) {
    $web_settings = $setting_res->fetch_assoc();
} else {
    // Defaults if table/data missing (migration fallback)
    $web_settings = [
        'site_title' => 'ByteBazaar',
        'site_tagline' => 'Premium Tech Store',
        'logo' => 'img/logo.png',
        'favicon' => 'img/favicon.ico',
        'address' => 'Street 2, Johar Town Block A, Lahore',
        'phone' => '+92324953752',
        'hours' => '9am-5pm',
        'footer_about' => 'Secured Payment Gateways',
        'copyright' => '2021. byteBazaar. HTML CSS',
        'currency' => '$'
    ];
}
