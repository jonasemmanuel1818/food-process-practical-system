<?php

require_once "../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$admin_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT role
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $admin_id);
$stmt->execute();

$role = $stmt->get_result()->fetch_assoc()['role'] ?? '';
$stmt->close();

if (!in_array($role, ['admin', 'lecturer'], true)) {
    die("Access Denied");
}


$student_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($student_id <= 0) {
    header("Location: students.php");
    exit();
}


/* Student */
$stmt = $conn->prepare("
    SELECT id, full_name, username, created_at
    FROM users
    WHERE id = ?
    AND role = 'student'
    LIMIT 1
");

$stmt->bind_param("i", $student_id);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found.");
}


/* Practical progress */
$progress = [];

$stmt = $conn->prepare("
    SELECT practical_number, status, started_at, completed_at
    FROM practical_progress
    WHERE user_id = ?
    ORDER BY practical_number
");

$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $progress[(int)$row['practical_number']] = $row;
}

$stmt->close();


/* Activity progress */
$activities = [];

$stmt = $conn->prepare("
    SELECT practical_number,
           activity_number,
           completed
    FROM practical_activity_progress
    WHERE user_id = ?
    ORDER BY practical_number, activity_number
");

$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

$stmt->close();


/* Submissions */
$stmt = $conn->prepare("
    SELECT id,
           practical_number,
           results,
           observations,
           conclusion,
           submitted_at
    FROM practical_submissions
    WHERE user_id = ?
    ORDER BY practical_number
");

$stmt->bind_param("i", $student_id);
$stmt->execute();

$submissions = $stmt->get_result();


/* Simulation results */
$stmt = $conn->prepare("
    SELECT id,
           practical_number,
           simulation_type,
           input_data,
           result_data,
           created_at
    FROM simulation_results
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $student_id);
$stmt->execute();

$simulations = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
<?= htmlspecialchars($student['full_name']) ?>
| Student Details
</title>

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
    color:#17495a;
    margin:0;
}

.main {
    padding:30px;
}

.card {
    border:1px solid #d9dee3;
    border-radius:9px;
    background:white;
}

.section-title {
    color:#17495a;
    font-weight:700;
}

.info-label {
    color:#78858d;
    font-size:12px;
}

.info-value {
    font-weight:600;
}

.practical-card {
    border:1px solid #d9dee3;
    border-radius:8px;
    padding:18px;
    background:white;
}

.complete {
    color:#18794e;
    font-weight:bold;
}

.progressing {
    color:#8a6500;
    font-weight:bold;
}

.not-started {
    color:#777;
}

.submission-box {
    background:#f8fafb;
    border:1px solid #e1e5e8;
    border-radius:7px;
    padding:16px;
    margin-bottom:15px;
}

.label {
    font-size:12px;
    color:#6d7880;
    font-weight:bold;
    text-transform:uppercase;
}

</style>

</head>

<body>

<div class="header">

<div class="d-flex justify-content-between align-items-center">

<div>

<h2>
<i class="bi bi-person"></i>
Student Details
</h2>

<div class="text-muted">
<?= htmlspecialchars($student['full_name']) ?>
</div>

</div>

<div>

<a href="students.php"
class="btn btn-outline-primary">
Back to Students
</a>

</div>

</div>

</div>


<main class="main">


<!-- STUDENT INFORMATION -->

<div class="card mb-4">

<div class="card-body">

<h5 class="section-title mb-4">
Student Information
</h5>

<div class="row">

<div class="col-md-4 mb-3">

<div class="info-label">
FULL NAME
</div>

<div class="info-value">
<?= htmlspecialchars($student['full_name']) ?>
</div>

</div>


<div class="col-md-4 mb-3">

<div class="info-label">
USERNAME
</div>

<div class="info-value">
<?= htmlspecialchars($student['username']) ?>
</div>

</div>


<div class="col-md-4 mb-3">

<div class="info-label">
REGISTERED
</div>

<div class="info-value">
<?= htmlspecialchars($student['created_at']) ?>
</div>

</div>

</div>

</div>

</div>


<!-- PRACTICAL PROGRESS -->

<h5 class="section-title mb-3">
Practical Progress
</h5>

<div class="row g-3 mb-4">

<?php

$practical_names = [
    1 => "Laboratory Orientation",
    2 => "Physical Separation",
    3 => "Thermal Processing",
    4 => "Drying"
];

?>

<?php for ($p = 1; $p <= 4; $p++): ?>

<?php

$status = $progress[$p]['status'] ?? 'not_started';

?>

<div class="col-md-6 col-xl-3">

<div class="practical-card">

<strong>
Practical <?= $p ?>
</strong>

<div class="small text-muted mb-2">
<?= $practical_names[$p] ?>
</div>

<?php if ($status === 'completed'): ?>

<div class="complete">
<i class="bi bi-check-circle"></i>
Completed
</div>

<?php elseif ($status === 'in_progress'): ?>

<div class="progressing">
<i class="bi bi-clock"></i>
In Progress
</div>

<?php else: ?>

<div class="not-started">
<i class="bi bi-circle"></i>
Not Started
</div>

<?php endif; ?>

</div>

</div>

<?php endfor; ?>

</div>


<!-- ACTIVITIES -->

<div class="card mb-4">

<div class="card-body">

<h5 class="section-title mb-3">
Completed Activities
</h5>

<?php if (count($activities) > 0): ?>

<div class="table-responsive">

<table class="table table-sm">

<thead>

<tr>
<th>Practical</th>
<th>Activity</th>
<th>Status</th>
</tr>

</thead>

<tbody>

<?php foreach ($activities as $activity): ?>

<tr>

<td>
Practical <?= (int)$activity['practical_number'] ?>
</td>

<td>
Activity <?= (int)$activity['activity_number'] ?>
</td>

<td>

<?php if ((int)$activity['completed'] === 1): ?>

<span class="text-success">
<i class="bi bi-check-circle"></i>
Completed
</span>

<?php else: ?>

<span class="text-muted">
Not Completed
</span>

<?php endif; ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php else: ?>

<p class="text-muted mb-0">
No activity progress recorded.
</p>

<?php endif; ?>

</div>

</div>


<!-- SUBMISSIONS -->

<div class="card mb-4">

<div class="card-body">

<h5 class="section-title mb-3">
Practical Submissions
</h5>

<?php if ($submissions->num_rows > 0): ?>

<?php while ($submission = $submissions->fetch_assoc()): ?>

<div class="submission-box">

<h6>
Practical <?= (int)$submission['practical_number'] ?>
</h6>

<div class="label">
Results
</div>

<p>
<?= nl2br(htmlspecialchars($submission['results'] ?? '')) ?>
</p>


<div class="label">
Observations
</div>

<p>
<?= nl2br(htmlspecialchars($submission['observations'] ?? '')) ?>
</p>


<div class="label">
Conclusion
</div>

<p>
<?= nl2br(htmlspecialchars($submission['conclusion'] ?? '')) ?>
</p>


<small class="text-muted">
Submitted:
<?= htmlspecialchars($submission['submitted_at'] ?? 'Not submitted') ?>
</small>

</div>

<?php endwhile; ?>

<?php else: ?>

<p class="text-muted">
This student has not submitted any practical results.
</p>

<?php endif; ?>

</div>

</div>


<!-- SIMULATIONS -->

<div class="card">

<div class="card-body">

<h5 class="section-title mb-3">
Simulation Results
</h5>

<?php if ($simulations->num_rows > 0): ?>

<div class="table-responsive">

<table class="table table-hover">

<thead>

<tr>

<th>Practical</th>
<th>Simulation</th>
<th>Date</th>

</tr>

</thead>

<tbody>

<?php while ($simulation = $simulations->fetch_assoc()): ?>

<tr>

<td>
Practical <?= (int)$simulation['practical_number'] ?>
</td>

<td>
<?= htmlspecialchars($simulation['simulation_type']) ?>
</td>

<td>
<?= htmlspecialchars($simulation['created_at']) ?>
</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

<?php else: ?>

<p class="text-muted mb-0">
No simulation results recorded.
</p>

<?php endif; ?>

</div>

</div>


</main>

</body>

</html>