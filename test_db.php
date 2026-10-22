
<?php
require_once "db.php";

$stmt = $pdo->prepare("SELECT id, name, email, course FROM students");
$stmt->execute();
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Hub - Students</title>
</head>
<body>
    <h1>Student Hub Students</h1>

    <?php if (count($students) > 0): ?>
        <?php foreach ($students as $student): ?>
            <div>
                <p>Name: <?= htmlspecialchars($student["name"]) ?></p>
                <p>Email: <?= htmlspecialchars($student["email"]) ?></p>
                <p>Course: <?= htmlspecialchars($student["course"]) ?></p>
                <hr>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No students found.</p>
    <?php endif; ?>
</body>
</html>
