
<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Please submit the registration form first.");
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$course = trim($_POST["course"] ?? "");
$message = trim($_POST["message"] ?? "");

$errors = [];

if ($name === "" || strlen($name) > 80) {
    $errors[] = "Enter a name with a maximum of 80 characters.";
}

if (
    $email === "" ||
    strlen($email) > 150 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    $errors[] = "Enter a valid email address.";
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    $errors[] = "Phone number must contain exactly 10 digits.";
}

$allowedCourses = [
    "Computer Science",
    "Information Technology",
    "Web Development",
    "Other"
];

if (!in_array($course, $allowedCourses, true)) {
    $errors[] = "Please select a valid course.";
}

if ($message === "" || strlen($message) > 500) {
    $errors[] = "Message is required and must be under 500 characters.";
}

$success = false;

if (empty($errors)) {
    $folder = __DIR__ . "/data";
    $file = $folder . "/registrations.csv";

    if (!is_dir($folder)) {
        $errors[] = "The data folder does not exist.";
    } else {
        $handle = fopen($file, "a");

        if ($handle === false) {
            $errors[] = "Unable to open the CSV file.";
        } else {
            if (flock($handle, LOCK_EX)) {
                $fileSize = fstat($handle)["size"];

                if ($fileSize === 0) {
                    fputcsv($handle, [
                        "Date",
                        "Name",
                        "Email",
                        "Phone",
                        "Course",
                        "Message"
                    ]);
                }

                $saved = fputcsv($handle, [
                    date("Y-m-d H:i:s"),
                    $name,
                    $email,
                    $phone,
                    $course,
                    $message
                ]);

                fflush($handle);
                flock($handle, LOCK_UN);

                if ($saved !== false) {
                    $success = true;
                } else {
                    $errors[] = "Could not save the registration.";
                }
            } else {
                $errors[] = "Could not lock the CSV file.";
            }

            fclose($handle);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub | Registration Result</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <section class="login-section">
        <div class="login-container">

            <?php if ($success): ?>
                <h1>Registration Successful!</h1>
                <p>Your information has been saved.</p>
            <?php else: ?>
                <h1>Registration Error</h1>

                <?php foreach ($errors as $error): ?>
                    <p style="color: red;">
                        <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                    </p>
                <?php endforeach; ?>
            <?php endif; ?>

            <p><a href="signup.php">Back to Sign Up</a></p>
            <p><a href="view.php">View Registrations</a></p>

        </div>
    </section>

</body>
</html>
