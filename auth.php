<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set("session.use_strict_mode", "1");

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/StudentHub/",
        "secure" => !empty($_SERVER["HTTPS"])
            && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

require_once __DIR__ . "/mysqli_db.php";

define("SESSION_TIMEOUT", 900);

function clearRememberCookie() {
    setcookie("studenthub_remember", "", [
        "expires" => time() - 3600,
        "path" => "/StudentHub/",
        "secure" => !empty($_SERVER["HTTPS"])
            && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ]);
}

function clearRememberToken($userId) {
    global $conn;

    $stmt = $conn->prepare(
        "UPDATE users
         SET remember_selector = NULL,
             remember_token_hash = NULL,
             remember_expires = NULL
         WHERE user_id = ?"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    clearRememberCookie();
}

function createRememberToken($userId) {
    global $conn;

    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $validator);
    $expires = date("Y-m-d H:i:s", time() + 30 * 24 * 60 * 60);

    $stmt = $conn->prepare(
        "UPDATE users
         SET remember_selector = ?,
             remember_token_hash = ?,
             remember_expires = ?
         WHERE user_id = ?"
    );
    $stmt->bind_param(
        "sssi",
        $selector,
        $tokenHash,
        $expires,
        $userId
    );
    $stmt->execute();
    $stmt->close();

    setcookie("studenthub_remember", $selector . ":" . $validator, [
        "expires" => time() + 30 * 24 * 60 * 60,
        "path" => "/StudentHub/",
        "secure" => !empty($_SERVER["HTTPS"])
            && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ]);
}

function restoreRememberedLogin() {
    global $conn;

    $cookie = $_COOKIE["studenthub_remember"] ?? "";

    if (!preg_match(
        '/\A([a-f0-9]{32}):([a-f0-9]{64})\z/',
        $cookie,
        $matches
    )) {
        return;
    }

    $selector = $matches[1];
    $validator = $matches[2];

    $stmt = $conn->prepare(
        "SELECT user_id, username, role, remember_token_hash
         FROM users
         WHERE remember_selector = ?
           AND remember_expires > NOW()
         LIMIT 1"
    );
    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (
        !$user ||
        empty($user["remember_token_hash"]) ||
        !hash_equals(
            $user["remember_token_hash"],
            hash("sha256", $validator)
        )
    ) {
        clearRememberCookie();
        return;
    }

    session_regenerate_id(true);

    $_SESSION["user_id"] = (int)$user["user_id"];
    $_SESSION["username"] = $user["username"];
    $_SESSION["role"] = $user["role"];
    $_SESSION["last_activity"] = time();

    createRememberToken((int)$user["user_id"]);
}

if (isset($_SESSION["user_id"])) {
    if (
        isset($_SESSION["last_activity"]) &&
        time() - $_SESSION["last_activity"] > SESSION_TIMEOUT
    ) {
        $expiredUserId = (int)$_SESSION["user_id"];
        clearRememberToken($expiredUserId);
        $_SESSION = [];
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }

    $_SESSION["last_activity"] = time();
} elseif (isset($_COOKIE["studenthub_remember"])) {
    restoreRememberedLogin();
}
?>
