<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mysqli_db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) ||
    strtolower((string)($_SESSION['role'] ?? '')) !== 'admin') {
    http_response_code(403);
    exit('Access denied. Please log in with an administrator account.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

if (empty($_SESSION['p11_csrf'])) {
    $_SESSION['p11_csrf'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirectToList($message, $type = 'success') {
    $_SESSION['p11_message'] = $message;
    $_SESSION['p11_message_type'] = $type;
    header('Location: students.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) ||
        !hash_equals($_SESSION['p11_csrf'], $token)) {
        http_response_code(403);
        exit('Invalid request token. Refresh the page and try again.');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save') {
            $id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $course = trim($_POST['course'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if ($name === '' || $course === '' ||
                !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                redirectToList('Enter a name, a valid email and a course.', 'error');
            }

            if (strlen($name) > 100 || strlen($email) > 150 ||
                strlen($course) > 100 || strlen($phone) > 20) {
                redirectToList('One or more fields exceed the allowed length.', 'error');
            }

            if ($id) {
                $stmt = $conn->prepare(
                    'UPDATE practical11_students
                     SET name = ?, email = ?, course = ?, phone = ?
                     WHERE student_id = ?'
                );
                $stmt->bind_param('ssssi', $name, $email, $course, $phone, $id);
                $stmt->execute();

                redirectToList(
                    $stmt->affected_rows >= 0
                        ? 'Student record updated successfully.'
                        : 'Unable to update student.',
                    'success'
                );
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO practical11_students
                     (name, email, course, phone)
                     VALUES (?, ?, ?, ?)'
                );
                $stmt->bind_param('ssss', $name, $email, $course, $phone);
                $stmt->execute();

                redirectToList('Student added successfully.');
            }
        }

        if ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);

            if (!$id) {
                redirectToList('Invalid student ID.', 'error');
            }

            $stmt = $conn->prepare(
                'DELETE FROM practical11_students WHERE student_id = ?'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();

            redirectToList(
                $stmt->affected_rows === 1
                    ? 'Student deleted successfully.'
                    : 'Student record was not found.',
                $stmt->affected_rows === 1 ? 'success' : 'error'
            );
        }
    } catch (mysqli_sql_exception $ex) {
        if ((int)$ex->getCode() === 1062) {
            redirectToList('That email address already exists.', 'error');
        }

        error_log($ex->getMessage());
        redirectToList('Database operation failed. Please check the input and try again.', 'error');
    }

    redirectToList('Invalid action.', 'error');
}

$q = trim($_GET['q'] ?? '');
$courseFilter = trim($_GET['course'] ?? '');
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

$editStudent = [
    'student_id' => '',
    'name' => '',
    'email' => '',
    'course' => '',
    'phone' => ''
];

if ($editId) {
    $stmt = $conn->prepare(
        'SELECT student_id, name, email, course, phone
         FROM practical11_students WHERE student_id = ?'
    );
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $found = $result->fetch_assoc();

    if ($found) {
        $editStudent = $found;
    } else {
        $_SESSION['p11_message'] = 'Student record not found.';
        $_SESSION['p11_message_type'] = 'error';
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $searchLike = '%' . $q . '%';
    $courseLike = $courseFilter;

    $stmt = $conn->prepare(
        "SELECT student_id, name, email, course, phone, created_at
         FROM practical11_students
         WHERE (name LIKE ? OR email LIKE ?)
         AND (? = '' OR course = ?)
         ORDER BY student_id DESC"
    );
    $stmt->bind_param('ssss', $searchLike, $searchLike, $courseLike, $courseLike);
    $stmt->execute();
    $result = $stmt->get_result();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="student-list.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Name', 'Email', 'Course', 'Phone', 'Created At']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($out, [
            $row['student_id'], $row['name'], $row['email'],
            $row['course'], $row['phone'], $row['created_at']
        ]);
    }

    fclose($out);
    exit;
}

$countResult = $conn->query(
    'SELECT COUNT(*) AS total FROM practical11_students'
);
$totalStudents = (int)$countResult->fetch_assoc()['total'];

$courseResult = $conn->query(
    'SELECT DISTINCT course FROM practical11_students ORDER BY course'
);
$courses = [];
while ($row = $courseResult->fetch_assoc()) {
    $courses[] = $row['course'];
}

$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$searchLike = '%' . $q . '%';
$courseLike = $courseFilter;

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM practical11_students
     WHERE (name LIKE ? OR email LIKE ?)
     AND (? = '' OR course = ?)"
);
$stmt->bind_param('ssss', $searchLike, $searchLike, $courseLike, $courseLike);
$stmt->execute();
$filteredTotal = (int)$stmt->get_result()->fetch_assoc()['total'];

$totalPages = max(1, (int)ceil($filteredTotal / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $conn->prepare(
    "SELECT student_id, name, email, course, phone, created_at
     FROM practical11_students
     WHERE (name LIKE ? OR email LIKE ?)
     AND (? = '' OR course = ?)
     ORDER BY student_id DESC
     LIMIT ? OFFSET ?"
);
$stmt->bind_param(
    'ssssii',
    $searchLike, $searchLike, $courseLike, $courseLike, $perPage, $offset
);
$stmt->execute();
$students = $stmt->get_result();

$message = $_SESSION['p11_message'] ?? '';
$messageType = $_SESSION['p11_message_type'] ?? 'success';
unset($_SESSION['p11_message'], $_SESSION['p11_message_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management | Student Hub</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .p11-wrap{max-width:1200px;margin:35px auto;padding:20px}
        .p11-title{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}
        .p11-title h1{color:#1c2d5a;margin-bottom:8px}
        .p11-panel{background:#fff;padding:22px;border-radius:12px;box-shadow:0 4px 18px #1c2d5a12;margin:22px 0}
        .p11-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        .p11-field label{display:block;margin-bottom:6px;font-weight:600;color:#1c2d5a}
        .p11-field input,.p11-field select,.p11-search input,.p11-search select{width:100%;padding:11px;border:1px solid #ccd3e0;border-radius:7px;box-sizing:border-box}
        .p11-btn{display:inline-block;border:0;border-radius:7px;padding:10px 15px;background:#3559e8;color:#fff;text-decoration:none;cursor:pointer;font:inherit}
        .p11-btn.secondary{background:#64748b}
        .p11-btn.danger{background:#dc3545}
        .p11-actions{display:flex;gap:7px;align-items:center;flex-wrap:wrap}
        .p11-search{display:grid;grid-template-columns:2fr 1fr auto auto;gap:10px;align-items:end}
        .p11-table-wrap{overflow-x:auto}
        .p11-table{width:100%;border-collapse:collapse;min-width:800px}
        .p11-table th,.p11-table td{padding:12px;text-align:left;border-bottom:1px solid #e5e7eb}
        .p11-table th{background:#f4f6fb;color:#1c2d5a}
        .p11-alert{padding:12px 15px;border-radius:7px;margin:15px 0}
        .p11-alert.success{background:#dcfce7;color:#166534}
        .p11-alert.error{background:#fee2e2;color:#991b1b}
        .p11-pagination{display:flex;gap:8px;align-items:center;justify-content:center;flex-wrap:wrap;margin-top:20px}
        .p11-stats{font-size:14px;color:#64748b}
        @media(max-width:650px){.p11-form-grid,.p11-search{grid-template-columns:1fr}.p11-wrap{padding:12px}}
    </style>
</head>
<body>
<div class="p11-wrap">
    <div class="p11-title">
        <div>
            <h1>Student Management</h1>
            <p>Manage student records, search and filter by course.</p>
        </div>
        <div class="p11-actions">
            <a class="p11-btn secondary" href="admin_dashboard.php">Admin Dashboard</a>
            <a class="p11-btn" href="students.php">Refresh</a>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="p11-alert <?= e($messageType) ?>"><?= e($message) ?></div>
    <?php endif; ?>

    <div class="p11-panel">
        <h2><?= $editStudent['student_id'] ? 'Edit Student' : 'Add New Student' ?></h2>
        <form method="post" action="students.php">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['p11_csrf']) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="student_id" value="<?= e($editStudent['student_id']) ?>">

            <div class="p11-form-grid">
                <div class="p11-field">
                    <label for="name">Student Name *</label>
                    <input id="name" name="name" maxlength="100" required value="<?= e($editStudent['name']) ?>">
                </div>
                <div class="p11-field">
                    <label for="email">Email *</label>
                    <input id="email" name="email" type="email" maxlength="150" required value="<?= e($editStudent['email']) ?>">
                </div>
                <div class="p11-field">
                    <label for="course">Course *</label>
                    <select id="course" name="course" required>
                        <option value="">Select course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= e($course) ?>" <?= $editStudent['course'] === $course ? 'selected' : '' ?>>
                                <?= e($course) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php
                        $currentCourse = $editStudent['course'];
                        if ($currentCourse !== '' && !in_array($currentCourse, $courses, true)):
                        ?>
                            <option selected value="<?= e($currentCourse) ?>"><?= e($currentCourse) ?></option>
                        <?php endif; ?>
                    </select>
                    <small>If adding a new course, enter it in the field below.</small>
                    <input name="new_course" id="new_course" maxlength="100" placeholder="Or enter a new course">
                </div>
                <div class="p11-field">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" maxlength="20" value="<?= e($editStudent['phone']) ?>">
                </div>
            </div>
            <p class="p11-actions" style="margin-top:18px">
                <button class="p11-btn" type="submit"><?= $editStudent['student_id'] ? 'Save Changes' : 'Add Student' ?></button>
                <?php if ($editStudent['student_id']): ?>
                    <a class="p11-btn secondary" href="students.php">Cancel Edit</a>
                <?php endif; ?>
            </p>
        </form>
    </div>

    <div class="p11-panel">
        <h2>Student Records</h2>
        <p class="p11-stats">
            Total records: <?= $totalStudents ?> |
            Matching search/filter: <?= $filteredTotal ?>
        </p>

        <form class="p11-search" method="get" action="students.php">
            <div class="p11-field">
                <label for="q">Search name or email</label>
                <input id="q" name="q" value="<?= e($q) ?>" placeholder="Type a name or email">
            </div>
            <div class="p11-field">
                <label for="courseFilter">Filter by course</label>
                <select id="courseFilter" name="course">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= e($course) ?>" <?= $courseFilter === $course ? 'selected' : '' ?>>
                            <?= e($course) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="p11-btn" type="submit">Search / Filter</button>
            <a class="p11-btn secondary" href="students.php">Clear</a>
        </form>

        <p style="margin:16px 0">
            <a class="p11-btn secondary" href="students.php?<?= e(http_build_query(['q'=>$q,'course'=>$courseFilter,'export'=>'csv'])) ?>">Export CSV</a>
        </p>

        <div class="p11-table-wrap">
            <table class="p11-table">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Course</th>
                        <th>Phone</th><th>Created</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($students->num_rows === 0): ?>
                    <tr><td colspan="7">No student records found.</td></tr>
                <?php else: ?>
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($student['student_id']) ?></td>
                            <td><?= e($student['name']) ?></td>
                            <td><?= e($student['email']) ?></td>
                            <td><?= e($student['course']) ?></td>
                            <td><?= e($student['phone']) ?></td>
                            <td><?= e($student['created_at']) ?></td>
                            <td>
                                <div class="p11-actions">
                                    <a class="p11-btn" href="students.php?edit=<?= (int)$student['student_id'] ?>">Edit</a>
                                    <form method="post" action="students.php" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['p11_csrf']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="student_id" value="<?= (int)$student['student_id'] ?>">
                                        <button class="p11-btn danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="p11-pagination">
            <?php if ($page > 1): ?>
                <a class="p11-btn secondary" href="students.php?<?= e(http_build_query(['q'=>$q,'course'=>$courseFilter,'page'=>$page-1])) ?>">Previous</a>
            <?php endif; ?>
            <span>Page <?= $page ?> of <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a class="p11-btn secondary" href="students.php?<?= e(http_build_query(['q'=>$q,'course'=>$courseFilter,'page'=>$page+1])) ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const courseSelect = document.getElementById('course');
const newCourse = document.getElementById('new_course');

newCourse.addEventListener('input', function () {
    if (this.value.trim() !== '') {
        courseSelect.required = false;
        courseSelect.value = '';
    } else {
        courseSelect.required = true;
    }
});

document.querySelector('form[action="students.php"] input[name="action"][value="save"]')
    .closest('form').addEventListener('submit', function () {
        if (newCourse.value.trim() !== '') {
            courseSelect.name = '';
            newCourse.name = 'course';
        }
    });
</script>
</body>
</html>
