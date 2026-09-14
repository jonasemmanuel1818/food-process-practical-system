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
            sr.id,
            sr.user_id,
            sr.practical_number,
            sr.simulation_type,
            sr.input_data,
            sr.result_data,
            sr.created_at,
            u.full_name,
            u.username
        FROM simulation_results sr
        INNER JOIN users u
            ON sr.user_id = u.id
        WHERE u.role = 'student'
        AND sr.practical_number = ?
        ORDER BY sr.created_at DESC
    ");

    $stmt->bind_param("i", $practical);
    $stmt->execute();

    $simulations = $stmt->get_result();

} else {

    $simulations = $conn->query("
        SELECT
            sr.id,
            sr.user_id,
            sr.practical_number,
            sr.simulation_type,
            sr.input_data,
            sr.result_data,
            sr.created_at,
            u.full_name,
            u.username
        FROM simulation_results sr
        INNER JOIN users u
            ON sr.user_id = u.id
        WHERE u.role = 'student'
        ORDER BY sr.created_at DESC
    ");

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Simulation Results | Food Process System</title>

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

.data-box {
    background:#f7f9fa;
    border:1px solid #e1e5e8;
    border-radius:6px;
    padding:10px;
    max-width:350px;
    max-height:120px;
    overflow:auto;
    font-size:12px;
}

</style>

</head>

<body>

<div class="header">

<div class="d-flex justify-content-between align-items-center">

<div>

<h2>
<i class="bi bi-graph-up"></i>
Simulation Results
</h2>

<div class="text-muted small">
Saved student simulation records
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

<a
href="simulation_results.php"
class="btn btn-sm <?= $practical === 0 ? 'btn-primary' : 'btn-outline-primary' ?>">
All
</a>

<?php for ($i = 2; $i <= 4; $i++): ?>

<a
href="simulation_results.php?practical=<?= $i ?>"
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
<th>Practical</th>
<th>Simulation</th>
<th>Input Data</th>
<th>Result Data</th>
<th>Date</th>

</tr>

</thead>

<tbody>

<?php if ($simulations->num_rows > 0): ?>

<?php while ($row = $simulations->fetch_assoc()): ?>

<tr>

<td>

<strong>
<?= htmlspecialchars($row['full_name']) ?>
</strong>

<div class="small text-muted">
<?= htmlspecialchars($row['username']) ?>
</div>

</td>


<td>
Practical <?= (int)$row['practical_number'] ?>
</td>


<td>
<?= htmlspecialchars($row['simulation_type']) ?>
</td>


<td>

<div class="data-box">

<?= htmlspecialchars(
    $row['input_data'] ?? ''
) ?>

</div>

</td>


<td>

<div class="data-box">

<?= htmlspecialchars(
    $row['result_data'] ?? ''
) ?>

</div>

</td>


<td>

<?= htmlspecialchars($row['created_at']) ?>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="6"
class="text-center text-muted py-5">

No simulation results found.

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