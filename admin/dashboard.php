<?php

require_once "../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| GET CURRENT USER
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, full_name, username, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$current_user = $stmt->get_result()->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| ACCESS CONTROL
|--------------------------------------------------------------------------
*/

if (
    !$current_user ||
    !in_array($current_user['role'], ['admin', 'lecturer'], true)
) {
    http_response_code(403);

    die("
        <div style='
            font-family:Arial;
            padding:50px;
            text-align:center;
        '>
            <h2>Access Denied</h2>

            <p>
                You do not have permission to access
                the administration area.
            </p>

            <a href='../dashboard.php'>
                Return to Student Dashboard
            </a>
        </div>
    ");
}


/*
|--------------------------------------------------------------------------
| TOTAL STUDENTS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'student'
");

$total_students = (int) $result->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| COMPLETED PRACTICALS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM practical_progress pp
    INNER JOIN users u
        ON pp.user_id = u.id
    WHERE u.role = 'student'
    AND pp.status = 'completed'
");

$total_completed = (int) $result->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| TOTAL SUBMISSIONS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM practical_submissions ps
    INNER JOIN users u
        ON ps.user_id = u.id
    WHERE u.role = 'student'
");

$total_submissions = (int) $result->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| TOTAL SIMULATIONS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM simulation_results sr
    INNER JOIN users u
        ON sr.user_id = u.id
    WHERE u.role = 'student'
");

$total_simulations = (int) $result->fetch_assoc()['total'];


/*
|--------------------------------------------------------------------------
| OVERALL PROGRESS
|--------------------------------------------------------------------------
*/

$total_possible = $total_students * 4;

if ($total_possible > 0) {

    $overall_progress = round(
        ($total_completed / $total_possible) * 100
    );

} else {

    $overall_progress = 0;

}


/*
|--------------------------------------------------------------------------
| PRACTICAL COMPLETION COUNTS
|--------------------------------------------------------------------------
*/

$practical_counts = [];

for ($i = 1; $i <= 4; $i++) {

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM practical_progress pp
        INNER JOIN users u
            ON pp.user_id = u.id
        WHERE u.role = 'student'
        AND pp.practical_number = ?
        AND pp.status = 'completed'
    ");

    $stmt->bind_param("i", $i);

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $practical_counts[$i] = (int) $row['total'];

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| RECENT SUBMISSIONS
|--------------------------------------------------------------------------
*/

$recent = $conn->query("
    SELECT
        ps.id,
        ps.user_id,
        ps.practical_number,
        ps.submitted_at,
        u.full_name,
        u.username
    FROM practical_submissions ps
    INNER JOIN users u
        ON ps.user_id = u.id
    WHERE u.role = 'student'
    ORDER BY ps.submitted_at DESC
    LIMIT 8
");

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
Admin Dashboard | Food Process System
</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
rel="stylesheet">


<style>

:root {
    --lab-blue: #1f5f75;
    --lab-dark: #17495a;
    --lab-light: #f4f6f8;
    --border: #d9dee3;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--lab-light);
    font-family: Arial, Helvetica, sans-serif;
    color: #27343b;
}


/* TOP BAR */

.topbar {
    height: 70px;
    background: white;
    border-bottom: 1px solid var(--border);

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 28px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand-icon {
    width: 42px;
    height: 42px;

    background: var(--lab-blue);
    color: white;

    border-radius: 8px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 21px;
}

.brand-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--lab-dark);
}

.brand-subtitle {
    font-size: 12px;
    color: #74818a;
}

.user-area {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar {
    width: 40px;
    height: 40px;

    border-radius: 50%;

    background: var(--lab-blue);
    color: white;

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: bold;
}


/* LAYOUT */

.layout {
    display: flex;
    min-height: calc(100vh - 70px);
}


/* SIDEBAR */

.sidebar {
    width: 245px;
    background: var(--lab-dark);

    padding: 24px 14px;

    flex-shrink: 0;
}

.sidebar-title {
    color: #b9cbd2;

    font-size: 11px;
    font-weight: bold;

    text-transform: uppercase;

    padding: 0 12px;

    margin-bottom: 10px;
}

.sidebar a {
    display: flex;
    align-items: center;

    gap: 12px;

    padding: 12px;

    margin-bottom: 4px;

    color: #e8f0f3;

    text-decoration: none;

    border-radius: 6px;

    font-size: 14px;
}

.sidebar a:hover,
.sidebar a.active {
    background: rgba(255,255,255,.12);
}

.sidebar a i {
    width: 20px;
}

.logout {
    margin-top: 25px;

    border-top: 1px solid rgba(255,255,255,.12);

    padding-top: 18px;
}


/* MAIN */

.main {
    flex: 1;

    padding: 30px;

    min-width: 0;
}

.page-title {
    margin-bottom: 25px;
}

.page-title h1 {
    color: var(--lab-dark);

    font-size: 27px;

    font-weight: 700;

    margin-bottom: 5px;
}

.page-title p {
    color: #74818a;

    font-size: 14px;

    margin: 0;
}


/* STAT CARDS */

.stat-card {
    background: white;

    border: 1px solid var(--border);

    border-radius: 9px;

    padding: 22px;

    height: 100%;
}

.stat-icon {
    width: 44px;
    height: 44px;

    background: #e8f1f4;

    color: var(--lab-blue);

    border-radius: 8px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 21px;

    margin-bottom: 15px;
}

.stat-value {
    font-size: 28px;

    font-weight: 700;

    color: var(--lab-dark);
}

.stat-label {
    color: #74818a;

    font-size: 13px;

    margin-top: 3px;
}


/* PANELS */

.panel {
    background: white;

    border: 1px solid var(--border);

    border-radius: 9px;

    padding: 22px;
}

.panel-title {
    color: var(--lab-dark);

    font-size: 17px;

    font-weight: 700;

    margin-bottom: 18px;
}


/* PRACTICAL */

.practical-row {
    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 14px 0;

    border-bottom: 1px solid #edf0f2;
}

.practical-row:last-child {
    border-bottom: 0;
}

.practical-count {
    color: var(--lab-blue);

    font-weight: 700;
}


/* TABLE */

.table thead th {
    background: #f7f9fa;

    color: #5e6b72;

    font-size: 12px;

    text-transform: uppercase;

    border-bottom: 1px solid var(--border);
}

.table tbody td {
    font-size: 13px;

    vertical-align: middle;
}


/* RESPONSIVE */

@media(max-width:700px) {

    .sidebar {
        display: none;
    }

    .main {
        padding: 18px;
    }

    .topbar {
        padding: 0 16px;
    }

}

</style>

</head>


<body>


<!-- =========================================================
     TOP BAR
========================================================= -->

<header class="topbar">

<div class="brand">

<div class="brand-icon">

<i class="bi bi-flask"></i>

</div>


<div>

<div class="brand-title">

Food Process Practical Learning System

</div>


<div class="brand-subtitle">

Administration & Laboratory Management

</div>

</div>

</div>


<div class="user-area">

<div class="text-end">

<strong>

<?= htmlspecialchars($current_user['full_name']) ?>

</strong>

<div class="text-muted small">

<?= htmlspecialchars($current_user['role']) ?>

</div>

</div>


<div class="avatar">

<?= strtoupper(
    substr($current_user['full_name'], 0, 1)
) ?>

</div>

</div>

</header>



<!-- =========================================================
     PAGE LAYOUT
========================================================= -->

<div class="layout">


<!-- SIDEBAR -->

<aside class="sidebar">


<div class="sidebar-title">

Administration

</div>


<a href="dashboard.php"
   class="active">

<i class="bi bi-speedometer2"></i>

Dashboard

</a>


<a href="students.php">

<i class="bi bi-people"></i>

Students

</a>


<a href="submissions.php">

<i class="bi bi-file-earmark-text"></i>

Submissions

</a>


<a href="simulation_results.php">

<i class="bi bi-graph-up"></i>

Simulation Results

</a>


<a href="../dashboard.php">

<i class="bi bi-person-workspace"></i>

Student View

</a>


<div class="logout">

<a href="../logout.php">

<i class="bi bi-box-arrow-right"></i>

Log Out

</a>

</div>


</aside>



<!-- MAIN CONTENT -->

<main class="main">


<div class="page-title">

<h1>

Administration Dashboard

</h1>


<p>

Monitor student practical work,
progress and submissions.

</p>

</div>



<!-- =========================================================
     STATISTICS
========================================================= -->

<div class="row g-4 mb-4">


<div class="col-md-6 col-xl-3">

<div class="stat-card">

<div class="stat-icon">

<i class="bi bi-people"></i>

</div>


<div class="stat-value">

<?= $total_students ?>

</div>


<div class="stat-label">

Registered Students

</div>

</div>

</div>



<div class="col-md-6 col-xl-3">

<div class="stat-card">

<div class="stat-icon">

<i class="bi bi-check2-circle"></i>

</div>


<div class="stat-value">

<?= $total_completed ?>

</div>


<div class="stat-label">

Completed Practicals

</div>

</div>

</div>



<div class="col-md-6 col-xl-3">

<div class="stat-card">

<div class="stat-icon">

<i class="bi bi-file-earmark-text"></i>

</div>


<div class="stat-value">

<?= $total_submissions ?>

</div>


<div class="stat-label">

Submissions

</div>

</div>

</div>



<div class="col-md-6 col-xl-3">

<div class="stat-card">

<div class="stat-icon">

<i class="bi bi-graph-up"></i>

</div>


<div class="stat-value">

<?= $overall_progress ?>%

</div>


<div class="stat-label">

Overall Progress

</div>

</div>

</div>


</div>



<!-- =========================================================
     PRACTICAL + QUICK ACTIONS
========================================================= -->

<div class="row g-4 mb-4">


<div class="col-lg-7">

<div class="panel">

<div class="panel-title">

Practical Completion

</div>


<div class="practical-row">

<span>
Practical 1 — Laboratory Orientation
</span>

<strong class="practical-count">

<?= $practical_counts[1] ?>

</strong>

</div>


<div class="practical-row">

<span>
Practical 2 — Physical Separation
</span>

<strong class="practical-count">

<?= $practical_counts[2] ?>

</strong>

</div>


<div class="practical-row">

<span>
Practical 3 — Thermal Processing
</span>

<strong class="practical-count">

<?= $practical_counts[3] ?>

</strong>

</div>


<div class="practical-row">

<span>
Practical 4 — Drying
</span>

<strong class="practical-count">

<?= $practical_counts[4] ?>

</strong>

</div>

</div>

</div>



<div class="col-lg-5">

<div class="panel">

<div class="panel-title">

Quick Actions

</div>


<a
href="students.php"
class="btn btn-outline-primary w-100 mb-2">

<i class="bi bi-people"></i>

Manage Students

</a>


<a
href="submissions.php"
class="btn btn-outline-primary w-100 mb-2">

<i class="bi bi-file-earmark-text"></i>

View Submissions

</a>


<a
href="simulation_results.php"
class="btn btn-outline-primary w-100">

<i class="bi bi-graph-up"></i>

Simulation Results

</a>


</div>

</div>


</div>



<!-- =========================================================
     RECENT SUBMISSIONS
========================================================= -->

<div class="panel">

<div class="panel-title">

Recent Submissions

</div>


<div class="table-responsive">

<table class="table table-hover align-middle">


<thead>

<tr>

<th>Student</th>

<th>Username</th>

<th>Practical</th>

<th>Submitted</th>

<th>Action</th>

</tr>

</thead>


<tbody>


<?php if ($recent && $recent->num_rows > 0): ?>


<?php while ($row = $recent->fetch_assoc()): ?>


<tr>


<td>

<strong>

<?= htmlspecialchars(
    $row['full_name']
) ?>

</strong>

</td>


<td>

<?= htmlspecialchars(
    $row['username']
) ?>

</td>


<td>

Practical
<?= (int)$row['practical_number'] ?>

</td>


<td>

<?= htmlspecialchars(
    $row['submitted_at'] ?? '—'
) ?>

</td>


<td>

<a
href="student_details.php?id=<?= (int)$row['user_id'] ?>"
class="btn btn-sm btn-outline-primary">

View

</a>

</td>


</tr>


<?php endwhile; ?>


<?php else: ?>


<tr>

<td
colspan="5"
class="text-center text-muted py-4">

No submissions found.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>

</div>

</div>


</main>

</div>


</body>

</html>