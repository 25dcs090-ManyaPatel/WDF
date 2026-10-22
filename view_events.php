
<?php
require_once "db.php";

$stmt = $pdo->prepare(
    "SELECT event_id, title, event_date, venue FROM events"
);
$stmt->execute();
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Hub Events</title>
</head>
<body>
    <h1>Student Hub Events</h1>

    <?php if ($events): ?>
        <?php foreach ($events as $event): ?>
            <h3><?= htmlspecialchars($event["title"]) ?></h3>
            <p>Date: <?= htmlspecialchars($event["event_date"]) ?></p>
            <p>Venue: <?= htmlspecialchars($event["venue"]) ?></p>
            <hr>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No events found.</p>
    <?php endif; ?>
</body>
</html>
