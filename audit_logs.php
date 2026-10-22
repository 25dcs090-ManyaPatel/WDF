
<?php
require_once "auth.php";

// Protect the audit log page.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: student_dashboard.php");
    exit;
}

require_once "mysqli_db.php";

// Create the table if it does not already exist.
$conn->query("
    CREATE TABLE IF NOT EXISTS audit_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        username VARCHAR(100) NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$logs = [];

$result = $conn->query("
    SELECT log_id, user_id, username, action, details, created_at
    FROM audit_logs
    ORDER BY log_id DESC
    LIMIT 200
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $result->free();
}

function escapeHTML($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Audit Logs | Student Hub</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .audit-container {
            max-width: 1200px;
            margin: 35px auto;
            padding: 20px;
        }

        .audit-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 3px 14px rgba(0, 0, 0, .08);
            overflow-x: auto;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        .audit-table th,
        .audit-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .audit-table th {
            background: #eff6ff;
            color: #1e40af;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <span>Student Hub</span>
    </div>

    <nav>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="students.php">Manage Students</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<main class="audit-container">
    <a class="back-link" href="admin_dashboard.php">
        &larr; Back to Admin Dashboard
    </a>

    <div class="audit-card">
        <h1>Audit Logs</h1>
        <p>Showing the latest 200 recorded actions.</p>

        <?php if (count($logs) === 0): ?>
            <p>No audit logs have been recorded yet.</p>
        <?php else: ?>
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Date and Time</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= escapeHTML($log["log_id"]) ?></td>
                            <td><?= escapeHTML($log["user_id"] ?? "") ?></td>
                            <td><?= escapeHTML($log["username"]) ?></td>
                            <td><?= escapeHTML($log["action"]) ?></td>
                            <td><?= escapeHTML($log["details"] ?? "") ?></td>
                            <td><?= escapeHTML($log["created_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
