<?php
include("include/connect.php");
session_start();

// ── Only delete cart if a real user session (aid > 0) exists ─────────────────
if (!empty($_SESSION['aid']) && (int)$_SESSION['aid'] > 0) {
    $aid = (int)$_SESSION['aid'];
    mysqli_query($con, "DELETE FROM cart WHERE aid = $aid");
}

// ── Wipe all session data ─────────────────────────────────────────────────────
$_SESSION = array();

// ── Clear session cookie ──────────────────────────────────────────────────────
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ── Destroy the session ───────────────────────────────────────────────────────
session_destroy();

// ── Redirect to login ─────────────────────────────────────────────────────────
header("Location: login.php");
exit();
?>