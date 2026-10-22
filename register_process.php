<?php
require_once "mysqli_db.php";

function showMessage($message) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Registration Result</title>
        <link rel='stylesheet' href='css/style.css'>
    </head>
    <body>
        <section class='login-section'>
            <div class='login-container'>
                <h1>Registration Result</h1>
                <p>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>
                <p><a href='secure_register.php'>Back to Registration</a></p>
                <p><a href='index.html'>Home</a></p>
            </div>
        </section>
    </body>
    </html>";
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: secure_register.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

if (
    !preg_match('/\A[A-Za-z0-9_]{3,50}\z/', $username) ||
    strlen($email) > 150 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($password) < 8 ||
    strlen($password) > 72 ||
    !hash_equals($password, $confirmPassword)
) {
    showMessage("Invalid input. Check your username, email and password.");
    exit;
}

$stmt = $conn->prepare(
    "SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1"
);
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    showMessage("Username or email already exists. Please use different details.");
    exit;
}
$stmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
);
$stmt->bind_param("sss", $username, $email, $hashedPassword);

try {
    if ($stmt->execute()) {
        $stmt->close();
        showMessage("Registration successful! Your account has been created.");
    } else {
        $stmt->close();
        showMessage("Registration could not be completed.");
    }
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());

    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }

    showMessage("Registration could not be completed. Check whether the username or email is already in use.");
}

$conn->close();
?>