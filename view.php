<?php
$file = __DIR__ . "/data/registrations.csv";
$records = [];
if (is_file($file) && is_readable($file)) {
    $handle = fopen($file, "r");
    if ($handle !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            $records[] = $row;
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub | Saved Registrations</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .records {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
            overflow-wrap: anywhere;
        }

        th {
            background: #3559e0;
            color: white;
        }
    </style>
</head>
<body>

    <div class="records">
        <h1>Saved Student Registrations</h1>

        <?php if (count($records) <= 1): ?>
            <p>No registrations found yet.</p>
        <?php else: ?>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($records[0] as $heading): ?>
                                <th>
                                    <?= htmlspecialchars(
                                        $heading,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach (array_slice($records, 1) as $row): ?>
                            <tr>
                                <?php foreach ($records[0] as $index => $heading): ?>
                                    <td>
                                        <?= htmlspecialchars(
                                            $row[$index] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

        <p><a href="signup.php">Add New Registration</a></p>
        <p><a href="index.html">Back to Home</a></p>
    </div>

</body>
</html>
