<?php
include("include/connect.php");

// Check if column exists
$check_query = "SHOW COLUMNS FROM accounts LIKE 'profile_img'";
$result = mysqli_query($con, $check_query);

if (mysqli_num_rows($result) == 0) {
    // Column doesn't exist, add it
    $alter_query = "ALTER TABLE accounts ADD profile_img VARCHAR(255) DEFAULT 'default.png'";
    if (mysqli_query($con, $alter_query)) {
        echo "Column 'profile_img' added successfully.";
    } else {
        echo "Error adding column: " . mysqli_error($con);
    }
} else {
    echo "Column 'profile_img' already exists.";
}
