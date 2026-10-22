
<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../db.php";

function respond($success, $message, $data = null, $status = 200) {
    http_response_code($status);

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}

function getInput() {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        respond(false, "Invalid JSON input.", null, 400);
    }

    return $data;
}

$method = $_SERVER["REQUEST_METHOD"];

try {
    if ($method === "GET") {
        $stmt = $pdo->query(
            "SELECT event_id, title, category, event_date,
                    location, description, status, poster
             FROM events
             ORDER BY event_date ASC"
        );

        respond(true, "Events loaded successfully.", $stmt->fetchAll());
    }

    if ($method === "POST") {
        $data = getInput();

        $title = trim($data["title"] ?? "");
        $category = trim($data["category"] ?? "");
        $date = trim($data["event_date"] ?? "");
        $location = trim($data["location"] ?? "");
        $description = trim($data["description"] ?? "");
        $status = $data["status"] ?? "Open";

        if ($title === "" || $category === "" ||
            $date === "" || $location === "" ||
            $description === "") {
            respond(false, "Please fill in all required fields.", null, 422);
        }

        if (!in_array($status, ["Open", "Closed"], true)) {
            respond(false, "Invalid event status.", null, 422);
        }

        $dateObject = DateTime::createFromFormat("!Y-m-d", $date);

        if (!$dateObject || $dateObject->format("Y-m-d") !== $date) {
            respond(false, "Enter a valid event date.", null, 422);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO events
             (title, category, event_date, location, description, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $title, $category, $date,
            $location, $description, $status
        ]);

        respond(true, "Event added successfully.", [
            "event_id" => (int)$pdo->lastInsertId()
        ], 201);
    }

    if ($method === "PUT") {
        $data = getInput();

        $id = filter_var($data["event_id"] ?? null, FILTER_VALIDATE_INT);
        $title = trim($data["title"] ?? "");
        $category = trim($data["category"] ?? "");
        $date = trim($data["event_date"] ?? "");
        $location = trim($data["location"] ?? "");
        $description = trim($data["description"] ?? "");
        $status = $data["status"] ?? "Open";

        if (!$id || $id < 1) {
            respond(false, "A valid event_id is required.", null, 422);
        }

        if ($title === "" || $category === "" ||
            $date === "" || $location === "" ||
            $description === "") {
            respond(false, "Please fill in all required fields.", null, 422);
        }

        if (!in_array($status, ["Open", "Closed"], true)) {
            respond(false, "Invalid event status.", null, 422);
        }

        $dateObject = DateTime::createFromFormat("!Y-m-d", $date);

        if (!$dateObject || $dateObject->format("Y-m-d") !== $date) {
            respond(false, "Enter a valid event date.", null, 422);
        }

        $stmt = $pdo->prepare(
            "UPDATE events
             SET title = ?, category = ?, event_date = ?,
                 location = ?, description = ?, status = ?
             WHERE event_id = ?"
        );

        $stmt->execute([
            $title, $category, $date,
            $location, $description, $status, $id
        ]);

        if ($stmt->rowCount() === 0) {
            $check = $pdo->prepare(
                "SELECT event_id FROM events WHERE event_id = ?"
            );
            $check->execute([$id]);

            if (!$check->fetch()) {
                respond(false, "Event not found.", null, 404);
            }
        }

        respond(true, "Event updated successfully.");
    }

    if ($method === "DELETE") {
        $data = getInput();
        $id = filter_var($data["event_id"] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            respond(false, "A valid event_id is required.", null, 422);
        }

        $stmt = $pdo->prepare(
            "DELETE FROM events WHERE event_id = ?"
        );
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            respond(false, "Event not found.", null, 404);
        }

        respond(true, "Event deleted successfully.");
    }

    header("Allow: GET, POST, PUT, DELETE");
    respond(false, "Method not allowed.", null, 405);

} catch (PDOException $e) {
    error_log($e->getMessage());
    respond(false, "A database error occurred.", null, 500);
}
?>
