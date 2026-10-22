
<?php
require_once "auth.php";

// ------------------------------------
// 1. ROLE-BASED ACCESS CONTROL
// ------------------------------------
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION["role"] ?? "") !== "admin") {
    header("Location: student_dashboard.php");
    exit;
}

require_once "mysqli_db.php";

$username = $_SESSION["username"] ?? "Admin";
$adminId = (int) $_SESSION["user_id"];

// ------------------------------------
// 2. HELPER FUNCTIONS
// ------------------------------------
function tableExists($conn, $tableName) {
    $allowedTables = [
        "users", "students", "events",
        "registrations", "audit_logs"
    ];

    if (!in_array($tableName, $allowedTables, true)) {
        return false;
    }

    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
         AND table_name = ?"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $tableName);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int) $count > 0;
}

function columnExists($conn, $tableName, $columnName) {
    $allowed = [
        "registrations" => [
            "registration_id", "student_id",
            "event_id", "registered_at"
        ],
        "students" => ["student_id", "name", "full_name"],
        "events" => ["event_id", "event_name", "title"]
    ];

    if (
        !isset($allowed[$tableName]) ||
        !in_array($columnName, $allowed[$tableName], true)
    ) {
        return false;
    }

    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = ?
           AND column_name = ?"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $tableName, $columnName);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return (int) $count > 0;
}

function getCount($conn, $tableName, $condition = "") {
    $allowedTables = [
        "users", "students", "events", "registrations"
    ];

    if (!in_array($tableName, $allowedTables, true)) {
        return 0;
    }

    if (!tableExists($conn, $tableName)) {
        return 0;
    }

    $sql = "SELECT COUNT(*) FROM `$tableName`";

    // Only use this optional condition for the fixed query below.
    if ($condition === "student_role") {
        $sql .= " WHERE role = 'student'";
    }

    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_row();
    $result->free();

    return (int) $row[0];
}

// ------------------------------------
// 3. CREATE AUDIT LOG TABLE
// ------------------------------------
$auditReady = false;

$auditSQL = "
    CREATE TABLE IF NOT EXISTS audit_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        username VARCHAR(100) NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
";

if ($conn->query($auditSQL)) {
    $auditReady = true;
}

// ------------------------------------
// 4. LOG ADMIN DASHBOARD ACCESS
// ------------------------------------
if ($auditReady) {
    $action = "Viewed Admin Dashboard";
    $details = "Admin opened the dashboard.";

    $stmt = $conn->prepare(
        "INSERT INTO audit_logs
         (user_id, username, action, details)
         VALUES (?, ?, ?, ?)"
    );

    if ($stmt) {
        $stmt->bind_param(
            "isss",
            $adminId,
            $username,
            $action,
            $details
        );
        $stmt->execute();
        $stmt->close();
    }
}

// ------------------------------------
// 5. FETCH DASHBOARD STATISTICS
// ------------------------------------
$totalUsers = getCount($conn, "users");
$totalStudents = getCount($conn, "users", "student_role");
$totalEvents = getCount($conn, "events");
$totalRegistrations = getCount($conn, "registrations");

// ------------------------------------
// 6. FETCH LATEST REGISTRATIONS
// ------------------------------------
$latestRegistrations = [];
$registrationMessage = "";

$canReadRegistrations =
    tableExists($conn, "registrations") &&
    columnExists($conn, "registrations", "registration_id") &&
    columnExists($conn, "registrations", "student_id") &&
    columnExists($conn, "registrations", "event_id") &&
    columnExists($conn, "registrations", "registered_at") &&
    tableExists($conn, "students") &&
    tableExists($conn, "events") &&
    columnExists($conn, "students", "student_id") &&
    columnExists($conn, "events", "event_id");

if ($canReadRegistrations) {
    $studentNameColumn = "";

    if (columnExists($conn, "students", "name")) {
        $studentNameColumn = "name";
    } elseif (columnExists($conn, "students", "full_name")) {
        $studentNameColumn = "full_name";
    }

    $eventNameColumn = "";

    if (columnExists($conn, "events", "event_name")) {
        $eventNameColumn = "event_name";
    } elseif (columnExists($conn, "events", "title")) {
        $eventNameColumn = "title";
    }

    if ($studentNameColumn !== "" && $eventNameColumn !== "") {
        $sql = "
            SELECT
                r.registration_id,
                s.`$studentNameColumn` AS student_name,
                e.`$eventNameColumn` AS event_name,
                r.registered_at
            FROM registrations r
            LEFT JOIN students s
                ON r.student_id = s.student_id
            LEFT JOIN events e
                ON r.event_id = e.event_id
            ORDER BY r.registered_at DESC
            LIMIT 10
        ";

        $result = $conn->query($sql);

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $latestRegistrations[] = $row;
            }
            $result->free();
        } else {
            $registrationMessage =
                "Check the column names in your registrations table.";
        }
    } else {
        $registrationMessage =
            "Student or event name columns were not found.";
    }
} else {
    $registrationMessage =
        "The registrations, students, or events table does not match "
        . "the expected structure.";
}

// ------------------------------------
// 7. FETCH RECENT AUDIT LOGS
// ------------------------------------
$auditEntries = [];

if ($auditReady) {
    $result = $conn->query(
        "SELECT username, action, details, created_at
         FROM audit_logs
         ORDER BY log_id DESC
         LIMIT 10"
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $auditEntries[] = $row;
        }
        $result->free();
    }
}

function e($value) {
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

    <title>Admin Dashboard | Student Hub</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">

    <style>
        .admin-page {
            max-width: 1250px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .admin-heading {
            margin-bottom: 25px;
        }

        .admin-heading h1 {
            margin-bottom: 8px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
            margin: 25px 0 35px;
        }

        .stat-card {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 3px 14px rgba(0,0,0,.08);
            border-left: 4px solid #2563eb;
        }

        .stat-card .stat-icon {
            font-size: 25px;
            color: #2563eb;
            margin-bottom: 12px;
        }

        .stat-card h2 {
            font-size: 30px;
            margin: 5px 0;
        }

        .stat-card p {
            margin: 0;
            color: #555;
        }

        .admin-panel {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 3px 14px rgba(0,0,0,.08);
            margin-bottom: 30px;
            overflow-x: auto;
        }

        .admin-panel h2 {
            margin-top: 0;
            margin-bottom: 18px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .admin-table th,
        .admin-table td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .admin-table th {
            background: #eff6ff;
            color: #1e40af;
        }

        .admin-links {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .admin-link {
            display: block;
            padding: 18px;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            text-decoration: none;
            color: #1d4ed8;
            background: #f8fbff;
        }

        .admin-link i {
            margin-right: 8px;
        }

        .admin-message {
            padding: 12px;
            background: #fff7ed;
            border-radius: 8px;
            color: #9a3412;
        }

        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .admin-links {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 550px) {
            .stats-grid,
            .admin-links {
                grid-template-columns: 1fr;
            }
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
            <li><a href="registration_report.php">Registration Report</a></li>
            <li><a href="events_api.html">Event API</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>

<main class="admin-page">

    <section class="admin-heading">
        <h1>Admin Dashboard</h1>
        <p>
            Welcome,
            <strong><?= e($username) ?></strong>!
            You are logged in as an administrator.
        </p>
        <p>Monitor users, events, registrations, and system activity.</p>
    </section>

    <!-- Dashboard Statistics -->
    <section class="stats-grid">

        <article class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <p>Total Users</p>
            <h2><?= $totalUsers ?></h2>
        </article>

        <article class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <p>Total Students</p>
            <h2><?= $totalStudents ?></h2>
        </article>

        <article class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <p>Total Events</p>
            <h2><?= $totalEvents ?></h2>
        </article>

        <article class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <p>Total Registrations</p>
            <h2><?= $totalRegistrations ?></h2>
        </article>

    </section>

    <!-- Admin Menu -->
    <section class="admin-panel">
        <h2><i class="fa-solid fa-sliders"></i> Administration</h2>

        <div class="admin-links">
            <a class="admin-link" href="students.php">
                <i class="fa-solid fa-user-graduate"></i>
                Manage Students
            </a>

            <a class="admin-link" href="registration_report.php">
                <i class="fa-solid fa-file-lines"></i>
                Registration Report
            </a>

            <a class="admin-link" href="events_api.html">
                <i class="fa-solid fa-calendar-check"></i>
                Event API Management
            </a>

            <a class="admin-link" href="audit_logs.php">
                <i class="fa-solid fa-clock-rotate-left"></i>
                View Audit Logs
            </a>
        </div>
    </section>

    <!-- Latest Registrations -->
    <section class="admin-panel">
        <h2>
            <i class="fa-solid fa-list"></i>
            Latest Registrations
        </h2>

        <?php if ($registrationMessage !== ""): ?>
            <p class="admin-message"><?= e($registrationMessage) ?></p>
        <?php elseif (count($latestRegistrations) === 0): ?>
            <p>No registrations found.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Event</th>
                        <th>Registered At</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($latestRegistrations as $row): ?>
                        <tr>
                            <td><?= e($row["registration_id"]) ?></td>
                            <td><?= e($row["student_name"] ?? "Unknown") ?></td>
                            <td><?= e($row["event_name"] ?? "Unknown") ?></td>
                            <td><?= e($row["registered_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <!-- Audit Log Preview -->
    <section class="admin-panel">
        <h2>
            <i class="fa-solid fa-shield-halved"></i>
            Recent Audit Logs
        </h2>

        <?php if (!$auditReady): ?>
            <p class="admin-message">
                Audit logging could not be initialized. Check database permissions.
            </p>
        <?php elseif (count($auditEntries) === 0): ?>
            <p>No audit entries found.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Date and Time</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($auditEntries as $entry): ?>
                        <tr>
                            <td><?= e($entry["username"]) ?></td>
                            <td><?= e($entry["action"]) ?></td>
                            <td><?= e($entry["details"] ?? "") ?></td>
                            <td><?= e($entry["created_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p>
            <a href="audit_logs.php">View all audit logs</a>
        </p>
    </section>

</main>

<footer>
    <p class="copyright">
        2026 Student Hub. All Rights Reserved.
    </p>
</footer>

</body>
</html>
