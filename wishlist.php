<?php
session_start();
if (!isset($_SESSION['aid']) || $_SESSION['aid'] < 0) {
    header("Location: login.php");
}
else {
    header("Location: profile.php?w=1");
}
exit();
?>