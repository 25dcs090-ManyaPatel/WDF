
<?php
require_once "auth.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "student") {
    header("Location: admin_dashboard.php");
    exit;
}

header("Location: dashboard.html");
exit;
?>
