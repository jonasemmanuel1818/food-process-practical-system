<?php
require_once "../config.php";

/* =========================================================
   ACCESS CONTROL
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$current_user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, full_name, username, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $current_user_id);
$stmt->execute();

$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (
    !$current_user ||
    !in_array($current_user['role'], ['admin', 'lecturer'], true)
) {
    http_response_code(403);
    exit("Access Denied");
}


/* =========================================================
   FILTER
========================================================= */

$filter = isset($_GET['practical'])
    ? (int) $_GET['practical']
    : 0;

if (!in_array($filter, [0, 2, 3, 4], true)) {
    $filter = 0;
}


/* =========================================================
   STATISTICS
========================================================= */

$total_simulations = 0;
$p2_count = 0;
$p3_count = 0;
$p4_count = 0;

$result = mysqli_query(
    $conn,
    "
    SELECT
        COUNT(*) AS total,
        SUM(practical_number = 2) AS p2,
        SUM(practical_number = 3) AS p3,
        SUM(practical_number = 4) AS p4
    FROM simulation_results
    WHERE practical_number IN (2,3,4)
    "
);

if ($result) {
    $row = mysqli_fetch_assoc($result);

    $total_simulations = (int) ($row['total'] ?? 0);
    $p2_count = (int) ($row['p2'] ?? 0);
    $p3_count = (int) ($row['p3'] ?? 0);
    $p4_count = (int) ($row['p4'] ?? 0);
}


/* =========================================================
   LOAD SIMULATION RESULTS
========================================================= */

$simulations = [];

if ($filter === 0) {

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
        WHERE sr.practical_number IN (2,3,4)
        ORDER BY sr.created_at DESC
    ");

} else {

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
        WHERE sr.practical_number = ?
        ORDER BY sr.created_at DESC
    ");

    $stmt->bind_param("i", $filter);
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $input_data = json_decode(
        $row['input_data'] ?? '{}',
        true
    );

    $result_data = json_decode(
        $row['result_data'] ?? '{}',
        true
    );

    if (!is_array($input_data)) {
        $input_data = [];
    }

    if (!is_array($result_data)) {
        $result_data = [];
    }

    $row['input_array'] = $input_data;
    $row['result_array'] = $result_data;

    $simulations[] = $row;
}

$stmt->close();


/* =========================================================
   PRACTICAL INFORMATION
========================================================= */

$practical_names = [
    2 => "Physical Separation",
    3 => "Thermal Processing",
    4 => "Drying"
];

$practical_icons = [
    2 => "bi-filter-circle",
    3 => "bi-thermometer-half",
    4 => "bi-droplet-half"
];

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Simulation Results | Food Process System
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet"
>

<script
    src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"
></script>


<style>

:root {
    --lab-blue: #1f5f75;
    --lab-dark: #17495a;
    --background: #f4f6f8;
    --border: #d9dee3;
    --text: #263238;
    --muted: #74818a;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--background);
    color: var(--text);
    font-family: Arial, Helvetica, sans-serif;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;

    width: 245px;
    height: 100vh;

    background: var(--lab-dark);
    color: white;

    padding: 28px 16px;

    overflow-y: auto;
    z-index: 1000;
}

.sidebar-title {
    padding: 0 13px 16px;

    font-size: 12px;
    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: .08em;

    color: #b9d0d8;
}

.sidebar a {
    display: flex;
    align-items: center;

    gap: 12px;

    padding: 12px 13px;
    margin-bottom: 5px;

    border-radius: 7px;

    color: #dce9ed;
    text-decoration: none;

    font-size: 14px;

    transition: .2s;
}

.sidebar a:hover {
    background: rgba(255,255,255,.08);
    color: white;
}

.sidebar a.active {
    background: rgba(255,255,255,.14);
    color: white;
}

.sidebar a i {
    width: 20px;
    font-size: 17px;
}

.nav-section-divider {
    height: 1px;

    background: rgba(255,255,255,.12);

    margin: 22px 8px;
}

.sidebar-heading {
    padding: 0 13px 12px;

    font-size: 11px;

    text-transform: uppercase;
    letter-spacing: .08em;

    color: #9fbcc5;
}

.logout {
    margin-top: 20px;
}


/* =========================================================
   MAIN
========================================================= */

.main {
    margin-left: 245px;
    min-height: 100vh;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {
    height: 72px;

    background: white;

    border-bottom: 1px solid var(--border);

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 32px;

    position: sticky;
    top: 0;

    z-index: 900;
}

.topbar h1 {
    margin: 0;

    font-size: 21px;
    font-weight: 700;

    color: var(--lab-dark);
}

.admin-info {
    display: flex;
    align-items: center;

    gap: 11px;
}

.admin-avatar {
    width: 40px;
    height: 40px;

    border-radius: 50%;

    background: var(--lab-blue);
    color: white;

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: 700;
}

.admin-name {
    font-size: 13px;
    font-weight: 700;
}

.admin-role {
    font-size: 11px;
    color: var(--muted);

    text-transform: capitalize;
}


/* =========================================================
   CONTENT
========================================================= */

.content {
    padding: 32px;
}

.page-header {
    margin-bottom: 25px;
}

.page-header h2 {
    margin: 0 0 7px;

    font-size: 26px;
    font-weight: 700;

    color: var(--lab-dark);
}

.page-header p {
    margin: 0;

    color: var(--muted);
    font-size: 14px;
}


/* =========================================================
   STAT CARDS
========================================================= */

.stat-card {
    background: white;

    border: 1px solid var(--border);
    border-radius: 9px;

    padding: 22px;

    height: 100%;
}

.stat-icon {
    width: 42px;
    height: 42px;

    border-radius: 8px;

    background: #edf5f7;
    color: var(--lab-blue);

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 20px;

    margin-bottom: 14px;
}

.stat-value {
    font-size: 27px;
    font-weight: 700;

    color: var(--lab-dark);
}

.stat-label {
    color: var(--muted);
    font-size: 13px;

    margin-top: 3px;
}


/* =========================================================
   FILTER
========================================================= */

.panel {
    background: white;

    border: 1px solid var(--border);
    border-radius: 9px;

    padding: 22px;

    margin-bottom: 24px;
}

.panel-title {
    color: var(--lab-dark);

    font-size: 17px;
    font-weight: 700;

    margin-bottom: 17px;
}

.filter-buttons {
    display: flex;
    flex-wrap: wrap;

    gap: 8px;
}

.filter-btn {
    border: 1px solid var(--border);

    background: white;

    color: #52616a;

    padding: 8px 15px;

    border-radius: 6px;

    text-decoration: none;

    font-size: 13px;
}

.filter-btn:hover {
    border-color: var(--lab-blue);
    color: var(--lab-blue);
}

.filter-btn.active {
    background: var(--lab-blue);
    color: white;

    border-color: var(--lab-blue);
}


/* =========================================================
   TABLE
========================================================= */

.table-panel {
    background: white;

    border: 1px solid var(--border);
    border-radius: 9px;

    overflow: hidden;
}

.table-panel-header {
    padding: 20px 22px;

    border-bottom: 1px solid var(--border);

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;
}

.table-panel-header h3 {
    margin: 0;

    color: var(--lab-dark);

    font-size: 17px;
    font-weight: 700;
}

.table-panel-header span {
    font-size: 12px;
    color: var(--muted);
}

.table {
    margin-bottom: 0;
}

.table thead th {
    background: #f7f9fa;

    color: #5e6b72;

    font-size: 11px;

    text-transform: uppercase;
    letter-spacing: .04em;

    border-bottom: 1px solid var(--border);

    padding: 14px;
}

.table tbody td {
    font-size: 13px;

    padding: 15px 14px;

    vertical-align: middle;
}

.student-name {
    font-weight: 700;
    color: var(--lab-dark);
}

.student-username {
    font-size: 11px;
    color: var(--muted);
}

.practical-badge {
    display: inline-flex;
    align-items: center;

    gap: 6px;

    padding: 6px 9px;

    border-radius: 5px;

    background: #edf5f7;
    color: var(--lab-blue);

    font-size: 11px;
    font-weight: 700;
}

.simulation-name {
    font-weight: 600;
}

.date-text {
    color: var(--muted);
    font-size: 12px;
}

.view-btn {
    border: 1px solid var(--lab-blue);

    color: var(--lab-blue);

    background: white;

    padding: 7px 11px;

    border-radius: 5px;

    font-size: 12px;

    text-decoration: none;

    display: inline-flex;
    align-items: center;

    gap: 5px;
}

.view-btn:hover {
    background: var(--lab-blue);
    color: white;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty-state {
    padding: 55px 20px;

    text-align: center;

    color: var(--muted);
}

.empty-state i {
    display: block;

    font-size: 40px;

    margin-bottom: 12px;

    color: #aebbc1;
}

.empty-state strong {
    display: block;

    color: var(--lab-dark);

    margin-bottom: 5px;
}


/* =========================================================
   MODAL
========================================================= */

.modal-content {
    border: 0;

    border-radius: 10px;

    overflow: hidden;
}

.modal-header {
    background: var(--lab-dark);

    color: white;

    padding: 18px 22px;
}

.modal-title {
    font-size: 17px;
    font-weight: 700;
}

.modal-header .btn-close {
    filter: invert(1);
}

.modal-body {
    padding: 24px;
}

.result-student {
    margin-bottom: 20px;
}

.result-student h4 {
    margin: 0 0 3px;

    color: var(--lab-dark);

    font-size: 19px;
}

.result-student p {
    margin: 0;

    color: var(--muted);

    font-size: 12px;
}

.result-section {
    border: 1px solid var(--border);

    border-radius: 8px;

    padding: 18px;

    margin-bottom: 18px;
}

.result-section h5 {
    color: var(--lab-dark);

    font-size: 15px;
    font-weight: 700;

    margin-bottom: 15px;
}

.result-box {
    background: #f7f9fa;

    border: 1px solid #e6eaed;

    border-radius: 6px;

    padding: 11px 13px;

    height: 100%;
}

.result-box small {
    display: block;

    color: var(--muted);

    font-size: 10px;

    text-transform: uppercase;
}

.result-box strong {
    display: block;

    color: var(--lab-dark);

    font-size: 15px;

    margin-top: 4px;

    word-break: break-word;
}

.chart-container {
    position: relative;

    width: 100%;

    height: 390px;
}

.no-chart {
    padding: 40px 15px;

    text-align: center;

    color: var(--muted);

    background: #f7f9fa;

    border: 1px dashed var(--border);

    border-radius: 7px;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 900px) {

    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;
    }

    .content {
        padding: 24px;
    }

}

@media (max-width: 700px) {

    .sidebar {
        display: none;
    }

    .main {
        margin-left: 0;
    }

    .topbar {
        height: auto;

        padding: 16px 20px;

        gap: 15px;
    }

    .topbar h1 {
        font-size: 18px;
    }

    .content {
        padding: 20px;
    }

    .chart-container {
        height: 300px;
    }

}

@media (max-width: 576px) {

    .admin-details {
        display: none;
    }

    .page-header h2 {
        font-size: 22px;
    }

    .table-panel-header {
        align-items: flex-start;

        flex-direction: column;
    }

    .modal-body {
        padding: 17px;
    }

}

</style>

</head>

<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">

    <div class="sidebar-title">
        Administration
    </div>

    <a href="dashboard.php">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
    </a>

    <a href="students.php">
        <i class="bi bi-people"></i>
        <span>Students</span>
    </a>

    <a href="submissions.php">
        <i class="bi bi-file-earmark-text"></i>
        <span>Submissions</span>
    </a>

    <a
        href="simulation_results.php"
        class="active"
    >
        <i class="bi bi-graph-up"></i>
        <span>Simulation Results</span>
    </a>

    <a href="register.php">
        <i class="bi bi-person-plus"></i>
        <span>Register Admin</span>
    </a>

    <div class="nav-section-divider"></div>

    <div class="sidebar-heading">
        Account
    </div>

    <a href="settings.php">
        <i class="bi bi-gear"></i>
        <span>Settings</span>
    </a>

    <div class="logout">
        <a href="../logout.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Log Out</span>
        </a>
    </div>

</aside>


<!-- =========================================================
     MAIN
========================================================= -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <h1>
            Simulation Results
        </h1>

        <div class="admin-info">

            <div class="admin-avatar">

                <?= strtoupper(
                    substr(
                        $current_user['full_name'],
                        0,
                        1
                    )
                ) ?>

            </div>

            <div class="admin-details">

                <div class="admin-name">
                    <?= htmlspecialchars(
                        $current_user['full_name']
                    ) ?>
                </div>

                <div class="admin-role">
                    <?= htmlspecialchars(
                        $current_user['role']
                    ) ?>
                </div>

            </div>

        </div>

    </div>


    <!-- CONTENT -->

    <div class="content">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <h2>
                Student Simulation Results
            </h2>

            <p>
                Review saved simulation calculations,
                parameters and graphical results.
            </p>

        </div>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="row g-4 mb-4">


            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>

                    <div class="stat-value">
                        <?= $total_simulations ?>
                    </div>

                    <div class="stat-label">
                        Total Simulations
                    </div>

                </div>

            </div>


            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-filter-circle"></i>
                    </div>

                    <div class="stat-value">
                        <?= $p2_count ?>
                    </div>

                    <div class="stat-label">
                        Practical 2
                    </div>

                </div>

            </div>


            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-thermometer-half"></i>
                    </div>

                    <div class="stat-value">
                        <?= $p3_count ?>
                    </div>

                    <div class="stat-label">
                        Practical 3
                    </div>

                </div>

            </div>


            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-droplet-half"></i>
                    </div>

                    <div class="stat-value">
                        <?= $p4_count ?>
                    </div>

                    <div class="stat-label">
                        Practical 4
                    </div>

                </div>

            </div>


        </div>


        <!-- =================================================
             FILTER
        ================================================== -->

        <div class="panel">

            <div class="panel-title">
                Filter Results
            </div>

            <div class="filter-buttons">

                <a
                    href="simulation_results.php"
                    class="filter-btn <?= $filter === 0 ? 'active' : '' ?>"
                >
                    All Simulations
                </a>

                <a
                    href="simulation_results.php?practical=2"
                    class="filter-btn <?= $filter === 2 ? 'active' : '' ?>"
                >
                    Practical 2
                </a>

                <a
                    href="simulation_results.php?practical=3"
                    class="filter-btn <?= $filter === 3 ? 'active' : '' ?>"
                >
                    Practical 3
                </a>

                <a
                    href="simulation_results.php?practical=4"
                    class="filter-btn <?= $filter === 4 ? 'active' : '' ?>"
                >
                    Practical 4
                </a>

            </div>

        </div>


        <!-- =================================================
             RESULTS TABLE
        ================================================== -->

        <div class="table-panel">

            <div class="table-panel-header">

                <h3>
                    Saved Simulation Records
                </h3>

                <span>
                    <?= count($simulations) ?>
                    result(s) displayed
                </span>

            </div>


            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead>

                        <tr>

                            <th>
                                Student
                            </th>

                            <th>
                                Practical
                            </th>

                            <th>
                                Simulation
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (count($simulations) > 0): ?>

                        <?php foreach ($simulations as $simulation): ?>

                            <?php

                            $p = (int)
                                $simulation['practical_number'];

                            $simulation_id =
                                (int) $simulation['id'];

                            $modal_id =
                                "simulationModal_" .
                                $simulation_id;

                            $input_json =
                                htmlspecialchars(
                                    json_encode(
                                        $simulation['input_array'],
                                        JSON_UNESCAPED_UNICODE |
                                        JSON_UNESCAPED_SLASHES
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                            $result_json =
                                htmlspecialchars(
                                    json_encode(
                                        $simulation['result_array'],
                                        JSON_UNESCAPED_UNICODE |
                                        JSON_UNESCAPED_SLASHES
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                            ?>

                            <tr>

                                <td>

                                    <div class="student-name">

                                        <?= htmlspecialchars(
                                            $simulation['full_name']
                                        ) ?>

                                    </div>

                                    <div class="student-username">

                                        @<?= htmlspecialchars(
                                            $simulation['username']
                                        ) ?>

                                    </div>

                                </td>


                                <td>

                                    <span class="practical-badge">

                                        <i
                                            class="bi <?= $practical_icons[$p] ?? 'bi-graph-up' ?>"
                                        ></i>

                                        Practical <?= $p ?>

                                    </span>

                                </td>


                                <td>

                                    <div class="simulation-name">

                                        <?= htmlspecialchars(
                                            $simulation['simulation_type']
                                        ) ?>

                                    </div>

                                </td>


                                <td>

                                    <div class="date-text">

                                        <?= htmlspecialchars(
                                            $simulation['created_at']
                                        ) ?>

                                    </div>

                                </td>


                                <td>

                                    <button
                                        type="button"
                                        class="view-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#<?= $modal_id ?>"
                                    >

                                        <i class="bi bi-bar-chart"></i>

                                        View Results

                                    </button>

                                </td>

                            </tr>


                            <!-- =================================================
                                 RESULT MODAL
                            ================================================== -->

                            <div
                                class="modal fade simulation-modal"
                                id="<?= $modal_id ?>"
                                tabindex="-1"
                                aria-hidden="true"
                                data-practical="<?= $p ?>"
                            >

                                <div
                                    class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"
                                >

                                    <div class="modal-content">


                                        <div class="modal-header">

                                            <h5 class="modal-title">

                                                <i class="bi bi-graph-up me-2"></i>

                                                Simulation Result

                                            </h5>

                                            <button
                                                type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"
                                            ></button>

                                        </div>


                                        <div class="modal-body">


                                            <!-- STUDENT -->

                                            <div class="result-student">

                                                <h4>

                                                    <?= htmlspecialchars(
                                                        $simulation['full_name']
                                                    ) ?>

                                                </h4>

                                                <p>

                                                    @<?= htmlspecialchars(
                                                        $simulation['username']
                                                    ) ?>

                                                    &nbsp; • &nbsp;

                                                    Practical <?= $p ?>

                                                    &nbsp; • &nbsp;

                                                    <?= htmlspecialchars(
                                                        $simulation['simulation_type']
                                                    ) ?>

                                                </p>

                                            </div>


                                            <!-- RESULTS -->

                                            <div class="result-section">

                                                <h5>

                                                    <i class="bi bi-calculator me-2"></i>

                                                    Calculated Results

                                                </h5>

                                                <div
                                                    class="row g-3 simulation-results"
                                                    id="resultValues_<?= $simulation_id ?>"
                                                    data-results="<?= $result_json ?>"
                                                >

                                                    <div class="col-12">

                                                        <div class="no-chart">

                                                            Loading saved results...

                                                        </div>

                                                    </div>

                                                </div>

                                            </div>


                                            <!-- GRAPH -->

                                            <div class="result-section">

                                                <h5>

                                                    <i class="bi bi-bar-chart-line me-2"></i>

                                                    Simulation Graph

                                                </h5>

                                                <div class="chart-container">

                                                    <canvas
                                                        id="chart_<?= $simulation_id ?>"
                                                    ></canvas>

                                                </div>

                                                <div
                                                    id="chartMessage_<?= $simulation_id ?>"
                                                    class="no-chart d-none chart-message"
                                                ></div>

                                            </div>


                                            <!-- INPUT PARAMETERS -->

                                            <div class="result-section">

                                                <h5>

                                                    <i class="bi bi-sliders me-2"></i>

                                                    Input Parameters

                                                </h5>

                                                <div
                                                    class="row g-3 simulation-inputs"
                                                    id="inputValues_<?= $simulation_id ?>"
                                                    data-inputs="<?= $input_json ?>"
                                                >

                                                    <div class="col-12">

                                                        <div class="no-chart">

                                                            Loading input parameters...

                                                        </div>

                                                    </div>

                                                </div>

                                            </div>


                                        </div>

                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>


                    <?php else: ?>

                        <tr>

                            <td colspan="5">

                                <div class="empty-state">

                                    <i class="bi bi-graph-up"></i>

                                    <strong>
                                        No simulation results found
                                    </strong>

                                    <span>
                                        Saved student simulations will appear here.
                                    </span>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


    </div>

</div>


<!-- Bootstrap -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

/* =========================================================
   CHART STORAGE
   Prevents duplicate Chart.js instances
========================================================= */

const simulationCharts = {};


/* =========================================================
   FORMAT LABEL
========================================================= */

function formatLabel(value) {

    if (
        value === null ||
        value === undefined
    ) {
        return "";
    }

    return String(value)
        .replace(/_/g, " ")
        .replace(
            /([a-z])([A-Z])/g,
            "$1 $2"
        )
        .replace(
            /\b\w/g,
            function(letter) {
                return letter.toUpperCase();
            }
        );
}


/* =========================================================
   FORMAT VALUE
========================================================= */

function formatValue(value) {

    if (
        value === null ||
        value === undefined ||
        value === ""
    ) {
        return "—";
    }

    if (typeof value === "number") {

        if (!Number.isFinite(value)) {
            return "—";
        }

        return Number.isInteger(value)
            ? value.toString()
            : value
                .toFixed(3)
                .replace(/0+$/, "")
                .replace(/\.$/, "");
    }

    if (typeof value === "boolean") {
        return value ? "Yes" : "No";
    }

    if (typeof value === "object") {
        return JSON.stringify(value);
    }

    return String(value);
}


/* =========================================================
   BUILD VALUE BOXES
========================================================= */

function buildValueBoxes(container, data) {

    container.innerHTML = "";

    if (
        !data ||
        typeof data !== "object"
    ) {

        container.innerHTML = `
            <div class="col-12">
                <div class="no-chart">
                    No saved values available.
                </div>
            </div>
        `;

        return;
    }


    const entries =
        Object.entries(data).filter(
            function(entry) {

                const value = entry[1];

                return (
                    value === null ||
                    typeof value !== "object"
                );

            }
        );


    if (entries.length === 0) {

        container.innerHTML = `
            <div class="col-12">
                <div class="no-chart">
                    No displayable values available.
                </div>
            </div>
        `;

        return;
    }


    entries.forEach(
        function(entry) {

            const key = entry[0];
            const value = entry[1];

            const col =
                document.createElement("div");

            col.className =
                "col-6 col-md-4 col-lg-3";

            const box =
                document.createElement("div");

            box.className =
                "result-box";

            const small =
                document.createElement("small");

            small.textContent =
                formatLabel(key);

            const strong =
                document.createElement("strong");

            strong.textContent =
                formatValue(value);

            box.appendChild(small);
            box.appendChild(strong);

            col.appendChild(box);

            container.appendChild(col);

        }
    );
}


/* =========================================================
   FIND ARRAY
========================================================= */

function findArray(data, possibleKeys) {

    if (
        !data ||
        typeof data !== "object"
    ) {
        return null;
    }


    for (
        const key of possibleKeys
    ) {

        if (
            Array.isArray(data[key]) &&
            data[key].length > 0
        ) {

            return data[key];

        }

    }

    return null;
}


/* =========================================================
   DRAW SIMULATION CHART
========================================================= */

function drawSimulationChart(
    canvas,
    messageBox,
    simulation
) {

    const resultData =
        simulation.results || {};

    /*
     * IMPORTANT:
     * This is the actual practical belonging
     * to the simulation record.
     *
     * It is NOT the page filter.
     */
    const practical =
        Number(simulation.practical || 0);


    /* ---------------------------------------------------------
       Remove previous chart
    --------------------------------------------------------- */

    if (canvas._chartInstance) {

        try {
            canvas._chartInstance.destroy();
        } catch (error) {
            console.warn(error);
        }

        canvas._chartInstance = null;
    }


    if (simulationCharts[canvas.id]) {

        try {
            simulationCharts[
                canvas.id
            ].destroy();
        } catch (error) {
            console.warn(error);
        }

        simulationCharts[
            canvas.id
        ] = null;
    }


    canvas.style.display = "block";

    messageBox.classList.add("d-none");

    messageBox.innerHTML = "";


    /* =========================================================
       PRACTICAL 2
       SIEVE ANALYSIS
    ========================================================= */

    if (practical === 2) {

        let labels =
            findArray(
                resultData,
                [
                    "labels",
                    "sieve_sizes",
                    "sieve_size",
                    "particle_sizes"
                ]
            );

        let values =
            findArray(
                resultData,
                [
                    "values",
                    "percentage_retained",
                    "percent_retained",
                    "percentage_passing",
                    "percent_passing"
                ]
            );


        /*
         * IMPORTANT FIX:
         *
         * Practical 2 stores graph information
         * inside:
         *
         * resultData.sieveResults
         *
         */

        if (
            (!labels || !values) &&
            Array.isArray(
                resultData.sieveResults
            ) &&
            resultData.sieveResults.length > 0
        ) {

            labels =
                resultData.sieveResults.map(
                    function(item) {

                        if (
                            !item ||
                            typeof item !== "object"
                        ) {
                            return "";
                        }

                        return (
                            item.sieve ??
                            item.sieveSize ??
                            item.sieve_size ??
                            item.name ??
                            ""
                        );
                    }
                );


            values =
                resultData.sieveResults.map(
                    function(item) {

                        if (
                            !item ||
                            typeof item !== "object"
                        ) {
                            return 0;
                        }

                        const value =
                            item.percentageRetained ??
                            item.percentRetained ??
                            item.percentage_retained ??
                            item.percentagePassing ??
                            item.percentPassing ??
                            item.percentage_passing ??
                            0;

                        const number =
                            Number(value);

                        return Number.isFinite(number)
                            ? number
                            : 0;
                    }
                );
        }


        /*
         * Check nested chart object
         */

        if (
            (!labels || !values) &&
            resultData.chart &&
            typeof resultData.chart === "object"
        ) {

            labels =
                findArray(
                    resultData.chart,
                    [
                        "labels",
                        "sieve_sizes",
                        "sieve_size",
                        "particle_sizes"
                    ]
                );

            values =
                findArray(
                    resultData.chart,
                    [
                        "values",
                        "data",
                        "percentage_retained",
                        "percent_retained",
                        "percentage_passing",
                        "percent_passing"
                    ]
                );
        }


        if (
            labels &&
            values
        ) {

            const length =
                Math.min(
                    labels.length,
                    values.length
                );

            labels =
                labels.slice(
                    0,
                    length
                );

            values =
                values.slice(
                    0,
                    length
                ).map(
                    function(value) {

                        const number =
                            Number(value);

                        return Number.isFinite(number)
                            ? number
                            : 0;
                    }
                );


            const chart =
                new Chart(
                    canvas.getContext("2d"),
                    {
                        type: "bar",

                        data: {

                            labels:
                                labels.map(
                                    formatLabel
                                ),

                            datasets: [

                                {
                                    label:
                                        "Percentage Retained",

                                    data:
                                        values,

                                    borderWidth: 1
                                }

                            ]

                        },

                        options: {

                            responsive: true,

                            maintainAspectRatio: false,

                            plugins: {

                                legend: {
                                    display: true
                                }

                            },

                            scales: {

                                x: {

                                    title: {
                                        display: true,
                                        text: "Sieve Size"
                                    }

                                },

                                y: {

                                    beginAtZero: true,

                                    title: {
                                        display: true,
                                        text: "Percentage"
                                    }

                                }

                            }

                        }

                    }
                );


            canvas._chartInstance =
                chart;

            simulationCharts[
                canvas.id
            ] = chart;

            return;
        }
    }


    /* =========================================================
       PRACTICAL 3
       THERMAL PROCESSING
    ========================================================= */

    if (practical === 3) {

        let labels =
            findArray(
                resultData,
                [
                    "labels",
                    "times",
                    "time",
                    "simulation_time",
                    "time_data"
                ]
            );

        let values =
            findArray(
                resultData,
                [
                    "values",
                    "temperature",
                    "temperatures",
                    "product_temperature",
                    "product_temperatures",
                    "temperature_data"
                ]
            );


        /*
         * Check nested chart object
         */

        if (
            (!labels || !values) &&
            resultData.chart &&
            typeof resultData.chart === "object"
        ) {

            labels =
                findArray(
                    resultData.chart,
                    [
                        "labels",
                        "times",
                        "time",
                        "simulation_time"
                    ]
                );

            values =
                findArray(
                    resultData.chart,
                    [
                        "values",
                        "temperature",
                        "temperatures",
                        "product_temperature",
                        "product_temperatures",
                        "data"
                    ]
                );
        }


        if (
            labels &&
            values
        ) {

            const length =
                Math.min(
                    labels.length,
                    values.length
                );

            labels =
                labels.slice(
                    0,
                    length
                );

            values =
                values.slice(
                    0,
                    length
                ).map(
                    function(value) {

                        const number =
                            Number(value);

                        return Number.isFinite(number)
                            ? number
                            : null;
                    }
                );


            const chart =
                new Chart(
                    canvas.getContext("2d"),
                    {
                        type: "line",

                        data: {

                            labels:
                                labels.map(
                                    formatLabel
                                ),

                            datasets: [

                                {
                                    label:
                                        "Product Temperature",

                                    data:
                                        values,

                                    borderWidth: 2,

                                    pointRadius: 2,

                                    tension: 0.25,

                                    fill: false
                                }

                            ]

                        },

                        options: {

                            responsive: true,

                            maintainAspectRatio: false,

                            interaction: {

                                intersect: false,

                                mode: "index"

                            },

                            plugins: {

                                legend: {
                                    display: true
                                }

                            },

                            scales: {

                                x: {

                                    title: {

                                        display: true,

                                        text:
                                            "Simulation Time"

                                    }

                                },

                                y: {

                                    title: {

                                        display: true,

                                        text:
                                            "Temperature"

                                    }

                                }

                            }

                        }

                    }
                );


            canvas._chartInstance =
                chart;

            simulationCharts[
                canvas.id
            ] = chart;

            return;
        }
    }


    /* =========================================================
       PRACTICAL 4
       DRYING
    ========================================================= */

    if (practical === 4) {

        let labels =
            findArray(
                resultData,
                [
                    "labels",
                    "times",
                    "time",
                    "simulation_time"
                ]
            );

        let values =
            findArray(
                resultData,
                [
                    "values",
                    "moisture",
                    "moisture_content",
                    "drying_rate",
                    "data"
                ]
            );


        /*
         * Check nested chart object
         */

        if (
            (!labels || !values) &&
            resultData.chart &&
            typeof resultData.chart === "object"
        ) {

            labels =
                findArray(
                    resultData.chart,
                    [
                        "labels",
                        "times",
                        "time",
                        "simulation_time"
                    ]
                );

            values =
                findArray(
                    resultData.chart,
                    [
                        "values",
                        "data",
                        "moisture",
                        "moisture_content",
                        "drying_rate"
                    ]
                );
        }


        if (
            labels &&
            values
        ) {

            const length =
                Math.min(
                    labels.length,
                    values.length
                );

            labels =
                labels.slice(
                    0,
                    length
                );

            values =
                values.slice(
                    0,
                    length
                ).map(
                    function(value) {

                        const number =
                            Number(value);

                        return Number.isFinite(number)
                            ? number
                            : null;
                    }
                );


            const chart =
                new Chart(
                    canvas.getContext("2d"),
                    {
                        type: "line",

                        data: {

                            labels:
                                labels.map(
                                    formatLabel
                                ),

                            datasets: [

                                {
                                    label:
                                        "Drying Process",

                                    data:
                                        values,

                                    borderWidth: 2,

                                    pointRadius: 2,

                                    tension: 0.25,

                                    fill: false
                                }

                            ]

                        },

                        options: {

                            responsive: true,

                            maintainAspectRatio: false,

                            interaction: {

                                intersect: false,

                                mode: "index"

                            },

                            plugins: {

                                legend: {
                                    display: true
                                }

                            },

                            scales: {

                                x: {

                                    title: {

                                        display: true,

                                        text:
                                            "Simulation Time"

                                    }

                                },

                                y: {

                                    beginAtZero: true,

                                    title: {

                                        display: true,

                                        text:
                                            "Moisture / Drying Value"

                                    }

                                }

                            }

                        }

                    }
                );


            canvas._chartInstance =
                chart;

            simulationCharts[
                canvas.id
            ] = chart;

            return;
        }
    }


    /* =========================================================
       NO GRAPH DATA
    ========================================================= */

    canvas.style.display = "none";

    messageBox.classList.remove("d-none");

    messageBox.innerHTML = `
        <i class="bi bi-bar-chart me-2"></i>
        The saved result contains calculated values,
        but no graph data was stored for this simulation.
    `;

}


/* =========================================================
   MODAL EVENTS
========================================================= */

document
    .querySelectorAll(".simulation-modal")
    .forEach(
        function(modal) {

            modal.addEventListener(
                "shown.bs.modal",
                function() {


                    /*
                     * Get the simulation ID
                     */

                    const id =
                        modal.id.replace(
                            "simulationModal_",
                            ""
                        );


                    const resultContainer =
                        document.getElementById(
                            "resultValues_" + id
                        );

                    const inputContainer =
                        document.getElementById(
                            "inputValues_" + id
                        );

                    const canvas =
                        document.getElementById(
                            "chart_" + id
                        );

                    const messageBox =
                        document.getElementById(
                            "chartMessage_" + id
                        );


                    if (
                        !resultContainer ||
                        !inputContainer ||
                        !canvas ||
                        !messageBox
                    ) {
                        return;
                    }


                    /*
                     * =================================================
                     * IMPORTANT FIX
                     *
                     * Read the ACTUAL practical number belonging
                     * to this simulation record.
                     *
                     * Do NOT use PHP $filter here.
                     *
                     * When "All Simulations" is selected,
                     * $filter = 0.
                     *
                     * The modal itself may belong to P2, P3 or P4.
                     * =================================================
                     */

                    const practical =
                        parseInt(
                            modal.dataset.practical || "0",
                            10
                        );


                    let results = {};
                    let inputs = {};


                    /*
                     * Read saved results
                     */

                    try {

                        const rawResults =
                            resultContainer.dataset.results;

                        if (rawResults) {

                            results =
                                JSON.parse(
                                    rawResults
                                );
                        }

                    } catch (error) {

                        console.error(
                            "Unable to read simulation results:",
                            error
                        );

                        results = {};
                    }


                    /*
                     * Read saved inputs
                     */

                    try {

                        const rawInputs =
                            inputContainer.dataset.inputs;

                        if (rawInputs) {

                            inputs =
                                JSON.parse(
                                    rawInputs
                                );
                        }

                    } catch (error) {

                        console.error(
                            "Unable to read simulation inputs:",
                            error
                        );

                        inputs = {};
                    }


                    /*
                     * Display calculated values
                     */

                    buildValueBoxes(
                        resultContainer,
                        results
                    );


                    /*
                     * Display input parameters
                     */

                    buildValueBoxes(
                        inputContainer,
                        inputs
                    );


                    /*
                     * Draw correct graph
                     */

                    drawSimulationChart(
                        canvas,
                        messageBox,
                        {
                            practical:
                                practical,

                            results:
                                results,

                            inputs:
                                inputs
                        }
                    );

                }
            );


            /*
             * Clean chart when modal closes
             */

            modal.addEventListener(
                "hidden.bs.modal",
                function() {

                    const canvas =
                        modal.querySelector(
                            "canvas"
                        );

                    if (!canvas) {
                        return;
                    }


                    if (
                        canvas._chartInstance
                    ) {

                        try {

                            canvas
                                ._chartInstance
                                .destroy();

                        } catch (error) {

                            console.warn(
                                error
                            );
                        }

                        canvas._chartInstance =
                            null;
                    }


                    if (
                        simulationCharts[
                            canvas.id
                        ]
                    ) {

                        try {

                            simulationCharts[
                                canvas.id
                            ].destroy();

                        } catch (error) {

                            console.warn(
                                error
                            );
                        }

                        simulationCharts[
                            canvas.id
                        ] = null;
                    }

                }
            );

        }
    );

</script>

</body>

</html>