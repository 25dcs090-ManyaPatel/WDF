
<?php
require_once "auth.php";

if (isset($_SESSION["user_id"])) {
    clearRememberToken((int)$_SESSION["user_id"]);
} else {
    clearRememberCookie();
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(session_name(), "", [
        "expires" => time() - 42000,
        "path" => $params["path"],
        "domain" => $params["domain"],
        "secure" => $params["secure"],
        "httponly" => $params["httponly"],
        "samesite" => "Lax"
    ]);
}

session_destroy();

header("Location: login.php?logout=1");
exit;
?>
