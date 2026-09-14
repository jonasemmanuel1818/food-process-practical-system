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


$practical = isset($_GET['practical'])
    ? (int)$_GET['practical']
    : 0;


if ($practical >= 1 && $practical <= 4) {

    $stmt = $conn->prepare("
        SELECT
            ps.id,
            ps.user_id,
            ps.practical_number,
            ps.results,
            ps.observations,
            ps.conclusion,
            ps.submitted_at,
            u.full_name,
            u.username
        FROM practical_submissions ps
        INNER JOIN users u
            ON ps.user_id = u.id
        WHERE u.role = 'student'
        AND ps.practical_number = ?
        ORDER BY ps.submitted_at DESC
    ");

    $stmt->bind_param("i", $practical);
    $stmt->execute();

    $submissions = $stmt->get_result();

} else {

    $submissions = $conn->query("
        SELECT
            ps.id,
            ps.user_id,
            ps.practical_number,
            ps.results,
            ps.observations,
            ps.conclusion,
            ps.submitted_at,
            u.full_name,
            u.username
        FROM practical_submissions ps
        INNER JOIN users u
            ON ps.user_id = u.id
        WHERE u.role = 'student'
        ORDER BY ps.submitted_at DESC
    ");

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Submissions | Food Process System</title>

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
}

.header {
    background:white;
    border-bottom:1px solid #d9dee3;
    padding:20px 30px;
}

.header h2 {
    color:#17495a;
    margin:0;
}

.main {
    padding:30px;
}

.card {
    border:1px solid #d9dee3;
    border-radius:9px;
}

.submission-preview {
    max-width:400px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

</style>

</head>

<body>

<div class="header">

<div class="d-flex justify-content-between align-items-center">

<div>

<h2>
<i class="bi bi-file-earmark-text"></i>
Practical Submissions
</h2>

<div class="text-muted small">
Review student practical work
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


<div class="mb-4">

<a href="submissions.php"
class="btn btn-sm <?= $practical === 0 ? 'btn-primary' : 'btn-outline-primary' ?>">
All
</a>

<?php for ($i = 1; $i <= 4; $i++): ?>

<a
href="submissions.php?practical=<?= $i ?>"
class="btn btn-sm <?= $practical === $i ? 'btn-primary' : 'btn-outline-primary' ?>">

Practical <?= $i ?>

</a>

<?php endfor; ?>

</div>


<div class="table-responsive">

<table class="table table-hover align-middle">

<thead>

<tr>

<th>Student</th>
<th>Username</th>
<th>Practical</th>
<th>Submitted</th>
<th>Results</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php if ($submissions->num_rows > 0): ?>

<?php while ($row = $submissions->fetch_assoc()): ?>

<tr>

<td>
<strong>
<?= htmlspecialchars($row['full_name']) ?>
</strong>
</td>

<td>
<?= htmlspecialchars($row['username']) ?>
</td>

<td>
Practical <?= (int)$row['practical_number'] ?>
</td>

<td>
<?= htmlspecialchars($row['submitted_at'] ?? '—') ?>
</td>

<td>

<div class="submission-preview">

<?= htmlspecialchars($row['results'] ?? '') ?>

</div>

</td>

<td>

<a
href="student_details.php?id=<?= (int)$row['user_id'] ?>"
class="btn btn-sm btn-primary">

View Student

</a>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="6"
class="text-center text-muted py-5">

No submissions found.

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