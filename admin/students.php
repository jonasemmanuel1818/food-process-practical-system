<?php

require_once "../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT role
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$role = $stmt->get_result()->fetch_assoc()['role'] ?? '';
$stmt->close();

if (!in_array($role, ['admin', 'lecturer'], true)) {
    die("Access Denied");
}


/* Search */
$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $stmt = $conn->prepare("
        SELECT id, full_name, username, created_at
        FROM users
        WHERE role = 'student'
        AND (
            full_name LIKE ?
            OR username LIKE ?
        )
        ORDER BY full_name ASC
    ");

    $like = "%".$search."%";

    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();

    $students = $stmt->get_result();

} else {

    $students = $conn->query("
        SELECT id, full_name, username, created_at
        FROM users
        WHERE role = 'student'
        ORDER BY full_name ASC
    ");

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Students | Food Process System</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
rel="stylesheet">

<style>

body {
    background:#f4f6f8;
    font-family:Arial,sans-serif;
    color:#27343b;
}

.header {
    background:#fff;
    border-bottom:1px solid #d9dee3;
    padding:20px 30px;
}

.header h2 {
    margin:0;
    color:#17495a;
}

.main {
    padding:30px;
}

.card {
    border:1px solid #d9dee3;
    border-radius:9px;
}

.table thead th {
    background:#f7f9fa;
    color:#5e6b72;
    font-size:12px;
    text-transform:uppercase;
}

.badge-complete {
    background:#e8f5ee;
    color:#18794e;
}

.badge-progress {
    background:#fff4d6;
    color:#8a6500;
}

</style>

</head>

<body>

<div class="header">

<div class="d-flex justify-content-between align-items-center">

<div>

<h2>
<i class="bi bi-people"></i>
Students
</h2>

<div class="text-muted small">
Registered students and practical progress
</div>

</div>

<a href="dashboard.php"
class="btn btn-outline-primary">
Dashboard
</a>

</div>

</div>


<main class="main">

<div class="card">

<div class="card-body">


<form method="GET"
class="row g-2 mb-4">

<div class="col-md-8">

<input
type="text"
name="search"
class="form-control"
placeholder="Search student name or username..."
value="<?= htmlspecialchars($search) ?>">

</div>

<div class="col-md-2">

<button class="btn btn-primary w-100">
<i class="bi bi-search"></i>
Search
</button>

</div>

<div class="col-md-2">

<a href="students.php"
class="btn btn-outline-secondary w-100">
Clear
</a>

</div>

</form>


<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>

<tr>

<th>Student</th>
<th>Username</th>
<th>P1</th>
<th>P2</th>
<th>P3</th>
<th>P4</th>
<th>Joined</th>
<th></th>

</tr>

</thead>

<tbody>

<?php if ($students->num_rows > 0): ?>

<?php while ($student = $students->fetch_assoc()): ?>

<?php

$progress = [];

$stmt = $conn->prepare("
    SELECT practical_number, status
    FROM practical_progress
    WHERE user_id = ?
");

$stmt->bind_param("i", $student['id']);
$stmt->execute();

$result = $stmt->get_result();

while ($p = $result->fetch_assoc()) {
    $progress[(int)$p['practical_number']]= $p['status'];
}

$stmt->close();

?>

<tr>

<td>
<strong>
<?= htmlspecialchars($student['full_name']) ?>
</strong>
</td>

<td>
<?= htmlspecialchars($student['username']) ?>
</td>


<?php for ($p = 1; $p <= 4; $p++): ?>

<td>

<?php if (($progress[$p] ?? '') === 'completed'): ?>

<span class="badge badge-complete">
Completed
</span>

<?php elseif (($progress[$p] ?? '') === 'in_progress'): ?>

<span class="badge badge-progress">
In Progress
</span>

<?php else: ?>

<span class="text-muted small">
Not Started
</span>

<?php endif; ?>

</td>

<?php endfor; ?>


<td>
<?= htmlspecialchars(date(
    'Y-m-d',
    strtotime($student['created_at'])
)) ?>
</td>


<td>

<a
href="student_details.php?id=<?= (int)$student['id'] ?>"
class="btn btn-sm btn-outline-primary">

View

</a>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="8"
class="text-center text-muted py-4">

No students found.

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</div>

</main>

</body>
</html>