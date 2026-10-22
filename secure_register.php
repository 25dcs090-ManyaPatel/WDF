<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub | Secure Registration</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <div class="logo"><span>Student Hub</span></div>
    <nav>
        <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="secure_register.php">Secure Registration</a></li>
            <li><a href="login.html">Login</a></li>
        </ul>
    </nav>
</header>

<section class="login-section">
    <div class="login-container">
        <h1>Secure Registration</h1>
        <p>Create your Student Hub account</p>

        <form action="register_process.php" method="POST">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                minlength="3"
                maxlength="50"
                pattern="[A-Za-z0-9_]+"
                title="Use 3–50 letters, numbers or underscores"
                required
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required
            >

            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required
            >

            <button type="submit">Create Account</button>
        </form>

        <p><a href="index.html">Back to Home</a></p>
    </div>
</section>

</body>
</html>
