
<?php
require_once "db.php";

$sql = "SELECT
            r.registration_id,
            s.name AS student_name,
            s.email,
            e.title AS event_name,
            e.event_date,
            e.venue
        FROM registrations r
        JOIN students s ON r.student_id = s.id
        JOIN events e ON r.event_id = e.event_id";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$registrations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Registrations</title>
</head>
<body>
    <h1>Student Event Registrations</h1>

    <?php if (count($registrations) > 0): ?>
        <table border="1" cellpadding="10">
            <tr>
                <th>Student</th>
                <th>Email</th>
                <th>Event</th>
                <th>Date</th>
                <th>Venue</th>
            </tr>

            <?php foreach ($registrations as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row["student_name"]) ?></td>
                    <td><?= htmlspecialchars($row["email"]) ?></td>
                    <td><?= htmlspecialchars($row["event_name"]) ?></td>
                    <td><?= htmlspecialchars($row["event_date"]) ?></td>
                    <td><?= htmlspecialchars($row["venue"]) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No registrations found yet. Add some registrations in phpMyAdmin first.</p>
    <?php endif; ?>
</body>
</html>
