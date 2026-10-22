<?php
require_once "mysqli_db.php";

$sql = "SELECT user_id, username, email, created_at
        FROM users
        ORDER BY user_id DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Report | Student Hub</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<section class="login-section">
    <div class="login-container">
        <h1>Registration Report</h1>

        <table border="1" cellpadding="8">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Created At</th>
            </tr>

            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= (int)$row["user_id"] ?></td>
                <td><?= htmlspecialchars($row["username"]) ?></td>
                <td><?= htmlspecialchars($row["email"]) ?></td>
                <td><?= htmlspecialchars($row["created_at"]) ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

        <p><a href="secure_register.php">Register Another User</a></p>
        <p><a href="index.html">Home</a></p>
    </div>
</section>

</body>
</html>
<?php $conn->close(); ?>
