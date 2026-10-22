<?php
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $role = $_POST["role"] ?? "student";
    $message = trim($_POST["message"] ?? "");

    if ($name === "" || $email === "" || $username === "" ||
        $password === "" || $confirmPassword === "") {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (!in_array($role, ["student", "admin"], true)) {
        $error = "Please select a valid role.";
    } elseif ($role === "admin") {
        $error = "Admin accounts must be created by an authorized administrator. Please register as a student.";
    } else {
        require_once "mysqli_db.php";

        $stmt = $conn->prepare(
            "SELECT user_id FROM users WHERE email = ? OR username = ? LIMIT 1"
        );

        if (!$stmt) {
            $error = "Unable to check the account. Please check your database setup.";
        } else {
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "That email or username is already registered.";
                $stmt->close();
            } else {
                $stmt->close();

                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $role = "student";

                $insert = $conn->prepare(
                    "INSERT INTO users (username, email, password, role)
                     VALUES (?, ?, ?, ?)"
                );

                if (!$insert) {
                    $error = "Unable to create the account. Check your users table.";
                } else {
                    $insert->bind_param(
                        "ssss",
                        $username,
                        $email,
                        $passwordHash,
                        $role
                    );

                    if ($insert->execute()) {
                        $success = "Registration successful! You can now log in.";
                        $name = "";
                        $email = "";
                        $username = "";
                        $message = "";
                    } else {
                        $error = "Registration failed. Please try again.";
                    }

                    $insert->close();
                }
            }
        }

        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub | Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .signup-message {
            width: 100%;
            height: 100px;
            resize: none;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font: inherit;
        }

        .signup-container input,
        .signup-container select {
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 14px;
        }

        .signup-container label {
            display: block;
            margin-bottom: 6px;
        }

        .form-message {
            padding: 10px;
            margin: 12px 0;
            border-radius: 5px;
        }

        .form-error {
            color: #a40000;
            background: #ffe8e8;
        }

        .form-success {
            color: #155724;
            background: #e5f6e8;
        }
    </style>
</head>
<body>

<header>
    <div class="logo"><span>Student Hub</span></div>
    <nav>
        <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="login.php">Login</a></li>
        </ul>
    </nav>
</header>

<section class="login-section">
    <div class="login-container signup-container">
        <h1>Create Account</h1>
        <p>Register for your Student Hub account</p>

        <?php if ($error !== ""): ?>
            <div class="form-message form-error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="form-message form-success" role="status">
                <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
                <a href="login.php">Go to Login</a>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
            <label for="name">Full Name</label>
            <input
                type="text"
                id="name"
                name="name"
                placeholder="Enter your full name"
                maxlength="80"
                value="<?= htmlspecialchars($name ?? "", ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="Choose a username"
                maxlength="50"
                value="<?= htmlspecialchars($username ?? "", ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                maxlength="150"
                value="<?= htmlspecialchars($email ?? "", ENT_QUOTES, "UTF-8") ?>"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="At least 8 characters"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Re-enter your password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <label for="role">Register As</label>
            <select id="role" name="role" required>
                <option value="student" selected>Student</option>
                <option value="admin">Admin (authorized accounts only)</option>
            </select>

            <label for="message">Message (Optional)</label>
            <textarea
                class="signup-message"
                id="message"
                name="message"
                placeholder="Write a message here..."
                maxlength="500"
            ><?= htmlspecialchars($message ?? "", ENT_QUOTES, "UTF-8") ?></textarea>

            <button type="submit">Create Account</button>
        </form>

        <p class="signup-link">
            Already have an account?
            <a href="login.php">Login</a>
        </p>
    </div>
</section>

<footer>
    <div class="footer-content">
        <div>
            <h3>Student Hub</h3>
            <p>Your one-stop platform for managing your academic journey.</p>
        </div>
        <div>
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
    </div>
    <hr>
    <p class="copyright">2026 Student Hub. All Rights Reserved.</p>
</footer>

</body>
</html>