
<?php
require_once "auth.php";

// ------------------------------------------
// 1. REDIRECT USERS WHO ARE ALREADY LOGGED IN
// ------------------------------------------
if (isset($_SESSION["user_id"])) {
    if (($_SESSION["role"] ?? "") === "admin") {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: student_dashboard.php");
    }
    exit;
}

$error = "";

// Display session timeout message if applicable.
if (isset($_GET["timeout"])) {
    $error = "Your session expired. Please log in again.";
}

// ------------------------------------------
// 2. PROCESS LOGIN FORM
// ------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $identity = trim($_POST["identity"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($identity === "" || $password === "") {

        $error = "Please enter your email/username and password.";

    } else {

        // Find user using username or email.
        $stmt = $conn->prepare(
            "SELECT user_id, username, email, password, role
             FROM users
             WHERE username = ? OR email = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $error = "Unable to process login. Check your database setup.";

        } else {

            $stmt->bind_param("ss", $identity, $identity);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $stmt->close();

            // ------------------------------------------
            // 3. VERIFY PASSWORD
            // ------------------------------------------
            if (
                $user &&
                password_verify($password, $user["password"])
            ) {

                // Accept only recognized roles.
                if (!in_array(
                    $user["role"],
                    ["admin", "student"],
                    true
                )) {
                    $error = "Your account role is invalid.";
                } else {

                    // ------------------------------------------
                    // 4. REGENERATE SESSION ID
                    // ------------------------------------------
                    session_regenerate_id(true);

                    $_SESSION["user_id"] = (int) $user["user_id"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["role"] = $user["role"];
                    $_SESSION["last_activity"] = time();

                    $userId = (int) $user["user_id"];

                    // ------------------------------------------
                    // 5. UPDATE LAST LOGIN
                    // ------------------------------------------
                    $update = $conn->prepare(
                        "UPDATE users
                         SET last_login = NOW()
                         WHERE user_id = ?"
                    );

                    if ($update) {
                        $update->bind_param("i", $userId);
                        $update->execute();
                        $update->close();
                    }

                    // ------------------------------------------
                    // 6. REMEMBER ME
                    // ------------------------------------------
                    if (!empty($_POST["remember"])) {

                        if (function_exists("createRememberToken")) {
                            createRememberToken($userId);
                        }

                    } else {

                        // Remove an existing persistent login token
                        // when the user chooses not to remember this login.
                        if (function_exists("clearRememberToken")) {
                            clearRememberToken($userId);
                        }
                    }

                    // ------------------------------------------
                    // 7. REDIRECT ACCORDING TO ROLE
                    // ------------------------------------------
                    if ($user["role"] === "admin") {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: student_dashboard.php");
                    }

                    exit;
                }

            } else {

                $error = "Invalid username/email or password.";

            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Hub | Login</title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"
    >

    <!-- Existing Student Hub CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- Header -->
    <header>
        <div class="logo">
            <span>Student Hub</span>
        </div>

        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="signup.php">Sign Up</a></li>
                <li>
                    <a href="secure_register.php">
                        Practical 9 Registration
                    </a>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Login Section -->
    <section class="login-section">

        <div class="login-container">

            <h1>Welcome Back</h1>

            <p>Login to your Student Hub account</p>

            <!-- Error Message -->
            <?php if ($error !== ""): ?>
                <p
                    role="alert"
                    style="color: red; margin-bottom: 15px;"
                >
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </p>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="login.php" method="POST">

                <label for="identity">
                    Email Address or Username
                </label>

                <input
                    type="text"
                    id="identity"
                    name="identity"
                    placeholder="Enter your email or username"
                    autocomplete="username"
                    required
                >

                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

                <!-- Remember Me -->
                <label>
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                    >
                    Remember Me
                </label>

                <button type="submit">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Login
                </button>

            </form>

            <!-- Sign Up Link -->
            <p class="signup-link">
                Don't have an account?
                <a href="signup.php">Sign Up</a>
            </p>

            <!-- Practical 9 Link -->
            <p class="signup-link">
                Need the Practical 9 registration page?
                <a href="secure_register.php">Register here</a>
            </p>

        </div>

    </section>

    <!-- Footer -->
    <footer>

        <div class="footer-content">

            <div>
                <h3>Student Hub</h3>

                <p>
                    Your one-stop platform for managing
                    your academic journey.
                </p>
            </div>

            <div>
                <h3>Quick Links</h3>

                <ul>
                    <li><a href="index.html">Home</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                    <li>
                        <a href="secure_register.php">
                            Practical 9 Registration
                        </a>
                    </li>
                </ul>
            </div>

        </div>

        <hr>

        <p class="copyright">
            2026 Student Hub. All Rights Reserved.
        </p>

    </footer>

</body>
</html>
