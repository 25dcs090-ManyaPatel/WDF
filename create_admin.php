<?php
require_once "mysqli_db.php";
$username = "admin";
$email = "admin@studenthub.local";
$password = "Admin@12345";
$role = "admin";
try {
    $check = $conn->prepare(
        "SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1"
    );
    $check->bind_param("ss", $username, $email);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        exit("An account with this username or email already exists. Delete this setup file and check your users table.");
    }
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        "INSERT INTO users (username, email, password, role)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);
    $stmt->execute();

    echo "Admin account created successfully.<br>";
    echo "Username: admin<br>";
    echo "Email: admin@studenthub.local<br>";
    echo "Password: Admin@12345<br><br>";
    echo "Now delete create_admin.php from your project.";
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    echo "Admin creation failed. Check your database connection and users table columns.";
}
?>
             