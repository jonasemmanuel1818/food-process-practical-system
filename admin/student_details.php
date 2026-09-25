<?php

require_once "../config.php";


/* =========================================================
   PROTECT ADMIN / LECTURER
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$admin_id = (int) $_SESSION['user_id'];


/* =========================================================
   CHECK ADMIN ROLE
========================================================= */

$stmt = $conn->prepare("
    SELECT role
    FROM users
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Database query failed.");
}

$stmt->bind_param("i", $admin_id);
$stmt->execute();

$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$stmt->close();

$role = $admin['role'] ?? '';

if (!in_array($role, ['admin', 'lecturer'], true)) {
    die("Access Denied");
}


/* =========================================================
   GET STUDENT ID
========================================================= */

$student_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($student_id <= 0) {
    header("Location: students.php");
    exit();
}


/* =========================================================
   GET STUDENT INFORMATION
========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        full_name,
        username,
        created_at
    FROM users
    WHERE id = ?
    AND role = 'student'
    LIMIT 1
");

if (!$stmt) {
    die("Database query failed.");
}

$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();

$stmt->close();


if (!$student) {
    die("Student not found.");
}


/* =========================================================
   PRACTICAL PROGRESS
========================================================= */

$progress = [];

$stmt = $conn->prepare("
    SELECT
        practical_number,
        status,
        started_at,
        completed_at
    FROM practical_progress
    WHERE user_id = ?
    ORDER BY practical_number
");

if ($stmt) {

    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $progress[(int) $row['practical_number']] = $row;
    }

    $stmt->close();
}


/* =========================================================
   ACTIVITY PROGRESS
========================================================= */

$activities = [];

$stmt = $conn->prepare("
    SELECT
        practical_number,
        activity_number,
        completed
    FROM practical_activity_progress
    WHERE user_id = ?
    ORDER BY practical_number, activity_number
");

if ($stmt) {

    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $activities[] = $row;
    }

    $stmt->close();
}


/* =========================================================
   SUBMISSIONS
========================================================= */

$submissions = [];

$stmt = $conn->prepare("
    SELECT
        id,
        practical_number,
        results,
        observations,
        conclusion,
        submitted_at
    FROM practical_submissions
    WHERE user_id = ?
    ORDER BY practical_number
");

if ($stmt) {

    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $submissions[] = $row;
    }

    $stmt->close();
}


/* =========================================================
   SIMULATION RESULTS
========================================================= */

$simulations = [];

$stmt = $conn->prepare("
    SELECT
        id,
        practical_number,
        simulation_type,
        input_data,
        result_data,
        created_at
    FROM simulation_results
    WHERE user_id = ?
    ORDER BY created_at DESC
");

if ($stmt) {

    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $simulations[] = $row;
    }

    $stmt->close();
}


/* =========================================================
   PRACTICAL NAMES
========================================================= */

$practical_names = [

    1 => "Laboratory Orientation",

    2 => "Physical Separation",

    3 => "Thermal Processing",

    4 => "Drying",

    5 => "Filtration and Separation"

];


/* =========================================================
   PRACTICAL ICONS
========================================================= */

$practical_icons = [

    1 => "bi-shield-check",

    2 => "bi-funnel",

    3 => "bi-thermometer-half",

    4 => "bi-wind",

    5 => "bi-filter-circle"

];


/* =========================================================
   PRACTICAL COUNTS
========================================================= */

$total_completed_practicals = 0;
$total_in_progress = 0;

for ($p = 1; $p <= 5; $p++) {

    $status = $progress[$p]['status'] ?? 'not_started';

    if ($status === 'completed') {

        $total_completed_practicals++;

    } elseif ($status === 'in_progress') {

        $total_in_progress++;
    }
}


$total_activities = count($activities);

$total_completed_activities = 0;

foreach ($activities as $activity) {

    if ((int) $activity['completed'] === 1) {

        $total_completed_activities++;
    }
}


$total_submissions = count($submissions);
$total_simulations = count($simulations);


/* =========================================================
   OVERALL PRACTICAL PERCENTAGE
========================================================= */

$overall_progress = round(
    ($total_completed_practicals / 5) * 100
);

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
    <?= htmlspecialchars($student['full_name']) ?>
    | Student Details
</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- Bootstrap Icons -->

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>


<style>

/* =========================================================
   ROOT
========================================================= */

:root {

    --lab-blue: #1f5f75;

    --lab-dark: #17495a;

    --lab-light: #eef5f7;

    --border: #d9dee3;

    --background: #f4f6f8;

    --text: #27343b;

    --muted: #6c757d;

}


/* =========================================================
   GLOBAL
========================================================= */

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    background: var(--background);

    color: var(--text);

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}

a {
    text-decoration: none;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 245px;

    height: 100vh;

    background: var(--lab-dark);

    color: #ffffff;

    padding: 25px 16px;

    z-index: 1000;

}


.brand {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 0 10px 25px;

    border-bottom:
        1px solid
        rgba(255,255,255,0.15);

    margin-bottom: 25px;

}


.brand-icon {

    width: 42px;

    height: 42px;

    border-radius: 9px;

    background: var(--lab-blue);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

    flex-shrink: 0;

}


.brand-text strong {

    display: block;

    font-size: 15px;

    line-height: 1.2;

}


.brand-text span {

    display: block;

    font-size: 11px;

    color:
        rgba(255,255,255,0.68);

    margin-top: 3px;

}


.sidebar-title {

    padding: 0 12px;

    font-size: 10px;

    font-weight: 700;

    letter-spacing: 1px;

    color:
        rgba(255,255,255,0.48);

    margin-bottom: 8px;

}


.sidebar-menu {

    list-style: none;

    padding: 0;

    margin: 0;

}


.sidebar-menu li {

    margin-bottom: 4px;

}


.sidebar-menu a {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 11px 12px;

    border-radius: 7px;

    color:
        rgba(255,255,255,0.82);

    font-size: 14px;

    transition: 0.2s ease;

}


.sidebar-menu a:hover {

    background:
        rgba(255,255,255,0.08);

    color: #ffffff;

}


.sidebar-menu a.active {

    background: var(--lab-blue);

    color: #ffffff;

}


.sidebar-menu i {

    width: 19px;

    font-size: 17px;

}


.sidebar-divider {

    height: 1px;

    background:
        rgba(255,255,255,0.12);

    margin: 22px 10px;

}


/* =========================================================
   MAIN
========================================================= */

.main-content {

    margin-left: 245px;

    min-height: 100vh;

}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    height: 76px;

    background: #ffffff;

    border-bottom:
        1px solid
        var(--border);

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 30px;

}


.page-title h1 {

    font-size: 21px;

    color: var(--lab-dark);

    margin: 0;

    font-weight: 700;

}


.page-title p {

    margin: 4px 0 0;

    color: var(--muted);

    font-size: 12px;

}


.topbar-actions {

    display: flex;

    align-items: center;

    gap: 10px;

}


.topbar-actions .btn {

    font-size: 13px;

    border-radius: 7px;

}


/* =========================================================
   MOBILE MENU
========================================================= */

.mobile-menu-btn {

    display: none;

    border: none;

    background: transparent;

    color: var(--lab-dark);

    font-size: 23px;

    margin-right: 10px;

}


.sidebar-overlay {

    display: none;

}


/* =========================================================
   PAGE
========================================================= */

.page {

    padding: 30px;

}


/* =========================================================
   BACK BUTTON
========================================================= */

.btn-back {

    color: var(--lab-blue);

    border:
        1px solid
        var(--lab-blue);

    background: #ffffff;

    font-size: 12px;

    border-radius: 7px;

}


.btn-back:hover {

    background: var(--lab-blue);

    border-color: var(--lab-blue);

    color: #ffffff;

}


/* =========================================================
   STUDENT HEADER
========================================================= */

.student-header {

    background: #ffffff;

    border:
        1px solid
        var(--border);

    border-radius: 9px;

    padding: 22px;

    margin-bottom: 22px;

}


.student-header-main {

    display: flex;

    align-items: center;

    gap: 16px;

}


.student-avatar {

    width: 58px;

    height: 58px;

    border-radius: 9px;

    background: var(--lab-light);

    color: var(--lab-blue);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    flex-shrink: 0;

}


.student-header h2 {

    margin: 0;

    color: var(--lab-dark);

    font-size: 22px;

    font-weight: 700;

}


.student-header p {

    margin: 4px 0 0;

    color: var(--muted);

    font-size: 12px;

}


/* =========================================================
   SUMMARY CARDS
========================================================= */

.summary-grid {

    display: grid;

    grid-template-columns:
        repeat(5, 1fr);

    gap: 14px;

    margin-bottom: 22px;

}


.summary-card {

    background: #ffffff;

    border:
        1px solid
        var(--border);

    border-radius: 8px;

    padding: 17px;

}


.summary-content {

    display: flex;

    align-items: center;

    gap: 12px;

}


.summary-icon {

    width: 42px;

    height: 42px;

    border-radius: 8px;

    background: var(--lab-light);

    color: var(--lab-blue);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 18px;

    flex-shrink: 0;

}


.summary-label {

    color: var(--muted);

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .4px;

    margin-bottom: 3px;

}


.summary-number {

    color: var(--lab-dark);

    font-size: 21px;

    font-weight: 700;

}


/* =========================================================
   GENERAL CARD
========================================================= */

.card-custom {

    background: #ffffff;

    border:
        1px solid
        var(--border);

    border-radius: 9px;

    overflow: hidden;

    margin-bottom: 22px;

}


.card-header-custom {

    padding: 18px 20px;

    border-bottom:
        1px solid
        var(--border);

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

}


.card-header-custom h3 {

    margin: 0;

    color: var(--lab-dark);

    font-size: 16px;

    font-weight: 700;

}


.card-header-custom p {

    margin: 3px 0 0;

    color: var(--muted);

    font-size: 11px;

}


.card-body-custom {

    padding: 20px;

}


/* =========================================================
   STUDENT INFORMATION
========================================================= */

.info-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

}


.info-label {

    color: #7b8790;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .5px;

    margin-bottom: 5px;

}


.info-value {

    color: #37474f;

    font-size: 13px;

    font-weight: 600;

}


.info-value i {

    color: var(--lab-blue);

    margin-right: 5px;

}


/* =========================================================
   PRACTICAL PROGRESS
========================================================= */

.practical-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 14px;

}


.practical-card {

    border:
        1px solid
        var(--border);

    border-radius: 8px;

    padding: 17px;

    background: #ffffff;

}


.practical-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 13px;

}


.practical-number {

    width: 32px;

    height: 32px;

    border-radius: 7px;

    background: var(--lab-light);

    color: var(--lab-blue);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 13px;

    font-weight: 700;

}


.practical-icon {

    color: var(--lab-blue);

    font-size: 18px;

}


.practical-card h4 {

    margin: 0 0 5px;

    color: #37474f;

    font-size: 13px;

    font-weight: 700;

}


.practical-card p {

    margin: 0 0 12px;

    color: var(--muted);

    font-size: 11px;

    line-height: 1.4;

}


.status-badge {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 5px 8px;

    border-radius: 5px;

    font-size: 10px;

    font-weight: 700;

}


.status-completed {

    background: #e8f5ee;

    color: #18794e;

}


.status-progress {

    background: #fff7df;

    color: #8a6500;

}


.status-not-started {

    background: #f0f2f3;

    color: #6c757d;

}


/* =========================================================
   PROGRESS BAR
========================================================= */

.progress-wrapper {

    margin-top: 12px;

}


.progress-label {

    display: flex;

    justify-content: space-between;

    color: var(--muted);

    font-size: 9px;

    margin-bottom: 5px;

}


.progress {

    height: 5px;

    background: #e9edef;

    border-radius: 10px;

}


.progress-bar {

    background: var(--lab-blue);

}


/* =========================================================
   TABLE
========================================================= */

.table {

    margin-bottom: 0;

}


.table thead th {

    background: #f7f9fa;

    color: #68767e;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .4px;

    border-bottom:
        1px solid
        var(--border);

    white-space: nowrap;

}


.table tbody td {

    color: #455a64;

    font-size: 12px;

    vertical-align: middle;

}


.table tbody tr:last-child td {

    border-bottom: none;

}


.activity-status {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    font-size: 11px;

    font-weight: 600;

}


/* =========================================================
   SUBMISSION
========================================================= */

.submission-box {

    background: #f8fafb;

    border:
        1px solid
        #e1e5e8;

    border-radius: 7px;

    padding: 17px;

    margin-bottom: 14px;

}


.submission-box:last-child {

    margin-bottom: 0;

}


.submission-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    margin-bottom: 14px;

}


.submission-title {

    color: var(--lab-dark);

    font-size: 13px;

    font-weight: 700;

}


.submission-date {

    color: var(--muted);

    font-size: 10px;

}


.submission-section {

    margin-bottom: 12px;

}


.submission-section:last-child {

    margin-bottom: 0;

}


.submission-label {

    color: #6d7880;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .4px;

    margin-bottom: 4px;

}


.submission-text {

    color: #4b5a61;

    font-size: 12px;

    line-height: 1.6;

    white-space: normal;

    word-break: break-word;

}


/* =========================================================
   SIMULATION
========================================================= */

.simulation-name {

    font-weight: 600;

    color: var(--lab-dark);

}


.data-preview {

    max-width: 320px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

    color: var(--muted);

    font-size: 11px;

}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty-state {

    text-align: center;

    padding: 35px 20px;

    color: var(--muted);

}


.empty-state i {

    display: block;

    font-size: 32px;

    color: #b7c2c7;

    margin-bottom: 10px;

}


.empty-state p {

    margin: 0;

    font-size: 12px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1200px) {

    .summary-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

    .practical-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media (max-width: 1000px) {

    .sidebar {

        transform:
            translateX(-100%);

        transition:
            transform .25s ease;

    }

    .sidebar.show {

        transform:
            translateX(0);

    }

    .sidebar-overlay.show {

        display: block;

        position: fixed;

        inset: 0;

        background:
            rgba(0,0,0,.35);

        z-index: 999;

    }

    .main-content {

        margin-left: 0;

    }

    .mobile-menu-btn {

        display: inline-block;

    }

}


@media (max-width: 768px) {

    .topbar {

        height: 70px;

        padding: 0 18px;

    }

    .page {

        padding: 20px 16px;

    }

    .page-title h1 {

        font-size: 18px;

    }

    .page-title p {

        display: none;

    }

    .topbar-actions .btn span {

        display: none;

    }

    .topbar-actions .btn {

        padding: 8px 10px;

    }

    .student-header-main {

        align-items: flex-start;

    }

    .info-grid {

        grid-template-columns: 1fr;

        gap: 13px;

    }

}


@media (max-width: 576px) {

    .summary-grid {

        grid-template-columns: 1fr;

    }

    .practical-grid {

        grid-template-columns: 1fr;

    }

    .student-header {

        padding: 17px;

    }

    .student-header h2 {

        font-size: 18px;

    }

    .card-header-custom,
    .card-body-custom {

        padding: 16px;

    }

    .submission-top {

        align-items: flex-start;

        flex-direction: column;

    }

}

</style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside
    class="sidebar"
    id="sidebar">


    <!-- BRAND -->

    <div class="brand">

        <div class="brand-icon">

            <i class="bi bi-flask"></i>

        </div>


        <div class="brand-text">

            <strong>
                Food Process System
            </strong>

            <span>
                Administration Panel
            </span>

        </div>

    </div>


    <!-- ADMINISTRATION -->

    <div class="sidebar-title">

        ADMINISTRATION

    </div>


    <ul class="sidebar-menu">


        <li>

            <a href="dashboard.php">

                <i class="bi bi-grid-1x2"></i>

                <span>
                    Dashboard
                </span>

            </a>

        </li>


        <li>

            <a
                href="students.php"
                class="active">

                <i class="bi bi-people"></i>

                <span>
                    Students
                </span>

            </a>

        </li>


        <li>

            <a href="submissions.php">

                <i class="bi bi-file-earmark-text"></i>

                <span>
                    Submissions
                </span>

            </a>

        </li>


        <li>

            <a href="simulation_results.php">

                <i class="bi bi-bar-chart-line"></i>

                <span>
                    Simulation Results
                </span>

            </a>

        </li>


        <li>

            <a href="register.php">

                <i class="bi bi-person-plus"></i>

                <span>
                    Register Admin
                </span>

            </a>

        </li>


    </ul>


    <!-- DIVIDER -->

    <div class="sidebar-divider"></div>


    <!-- ACCOUNT -->

    <div class="sidebar-title">

        ACCOUNT

    </div>


    <ul class="sidebar-menu">


        <li>

            <a href="settings.php">

                <i class="bi bi-gear"></i>

                <span>
                    Settings
                </span>

            </a>

        </li>


        <li>

            <a href="../logout.php">

                <i class="bi bi-box-arrow-right"></i>

                <span>
                    Log Out
                </span>

            </a>

        </li>


    </ul>


</aside>


<!-- =========================================================
     OVERLAY
========================================================= -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay">
</div>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="main-content">


    <!-- =====================================================
         TOPBAR
    ===================================================== -->

    <header class="topbar">


        <div class="d-flex align-items-center">


            <button
                type="button"
                class="mobile-menu-btn"
                id="mobileMenuBtn"
                aria-label="Open menu">

                <i class="bi bi-list"></i>

            </button>


            <div class="page-title">

                <h1>
                    Student Details
                </h1>

                <p>
                    View student learning progress and practical records
                </p>

            </div>


        </div>


        <div class="topbar-actions">


            <a
                href="students.php"
                class="btn btn-back">

                <i class="bi bi-arrow-left me-1"></i>

                <span>
                    Back to Students
                </span>

            </a>


        </div>


    </header>


    <!-- =====================================================
         PAGE
    ===================================================== -->

    <main class="page">


        <!-- =================================================
             STUDENT HEADER
        ================================================= -->

        <section class="student-header">


            <div class="student-header-main">


                <div class="student-avatar">

                    <i class="bi bi-person"></i>

                </div>


                <div>

                    <h2>

                        <?= htmlspecialchars(
                            $student['full_name']
                        ) ?>

                    </h2>


                    <p>

                        <i class="bi bi-person-badge me-1"></i>

                        @<?= htmlspecialchars(
                            $student['username']
                        ) ?>

                        <span class="mx-2">
                            •
                        </span>

                        <i class="bi bi-calendar3 me-1"></i>

                        Registered
                        <?= htmlspecialchars(
                            $student['created_at']
                        ) ?>

                    </p>

                </div>


            </div>


        </section>


        <!-- =================================================
             SUMMARY
        ================================================= -->

        <section class="summary-grid">


            <!-- COMPLETED PRACTICALS -->

            <div class="summary-card">

                <div class="summary-content">


                    <div class="summary-icon">

                        <i class="bi bi-check2-circle"></i>

                    </div>


                    <div>

                        <div class="summary-label">

                            Completed Practicals

                        </div>


                        <div class="summary-number">

                            <?= $total_completed_practicals ?>

                            <span
                                style="
                                    font-size:11px;
                                    color:#8a959d;
                                    font-weight:400;
                                ">

                                / 4

                            </span>

                        </div>

                    </div>


                </div>

            </div>


            <!-- ACTIVITIES -->

            <div class="summary-card">

                <div class="summary-content">


                    <div class="summary-icon">

                        <i class="bi bi-list-check"></i>

                    </div>


                    <div>

                        <div class="summary-label">

                            Activities Completed

                        </div>


                        <div class="summary-number">

                            <?= $total_completed_activities ?>

                        </div>

                    </div>


                </div>

            </div>


            <!-- SUBMISSIONS -->

            <div class="summary-card">

                <div class="summary-content">


                    <div class="summary-icon">

                        <i class="bi bi-file-earmark-check"></i>

                    </div>


                    <div>

                        <div class="summary-label">

                            Submissions

                        </div>


                        <div class="summary-number">

                            <?= $total_submissions ?>

                        </div>

                    </div>


                </div>

            </div>


            <!-- SIMULATIONS -->

            <div class="summary-card">

                <div class="summary-content">


                    <div class="summary-icon">

                        <i class="bi bi-activity"></i>

                    </div>


                    <div>

                        <div class="summary-label">

                            Simulations

                        </div>


                        <div class="summary-number">

                            <?= $total_simulations ?>

                        </div>

                    </div>


                </div>

            </div>


        </section>


        <!-- =================================================
             STUDENT INFORMATION
        ================================================= -->

        <section class="card-custom">


            <div class="card-header-custom">


                <div>

                    <h3>

                        <i class="bi bi-person-vcard me-2"></i>

                        Student Information

                    </h3>

                    <p>
                        Basic account information
                    </p>

                </div>


            </div>


            <div class="card-body-custom">


                <div class="info-grid">


                    <!-- FULL NAME -->

                    <div>

                        <div class="info-label">
                            Full Name
                        </div>

                        <div class="info-value">

                            <i class="bi bi-person"></i>

                            <?= htmlspecialchars(
                                $student['full_name']
                            ) ?>

                        </div>

                    </div>


                    <!-- USERNAME -->

                    <div>

                        <div class="info-label">
                            Username
                        </div>

                        <div class="info-value">

                            <i class="bi bi-person-badge"></i>

                            <?= htmlspecialchars(
                                $student['username']
                            ) ?>

                        </div>

                    </div>


                    <!-- REGISTERED -->

                    <div>

                        <div class="info-label">
                            Registered
                        </div>

                        <div class="info-value">

                            <i class="bi bi-calendar3"></i>

                            <?= htmlspecialchars(
                                $student['created_at']
                            ) ?>

                        </div>

                    </div>


                </div>


            </div>


        </section>


        <!-- =================================================
             PRACTICAL PROGRESS
        ================================================= -->

        <section class="card-custom">


            <div class="card-header-custom">


                <div>

                    <h3>

                        <i class="bi bi-journal-check me-2"></i>

                        Practical Progress

                    </h3>

                    <p>
                        Student progress across all four practicals
                    </p>

                </div>


                <span
                    class="status-badge
                    <?= $overall_progress === 100
                        ? 'status-completed'
                        : 'status-progress'
                    ?>">

                    <?= $overall_progress ?>% Overall

                </span>


            </div>


            <div class="card-body-custom">


                <div class="practical-grid">


                    <?php for ($p = 1; $p <= 5; $p++): ?>


                        <?php

                        $status =
                            $progress[$p]['status']
                            ?? 'not_started';

                        $status_text = "Not Started";

                        $status_class =
                            "status-not-started";

                        $status_icon =
                            "bi-circle";


                        if ($status === 'completed') {

                            $status_text =
                                "Completed";

                            $status_class =
                                "status-completed";

                            $status_icon =
                                "bi-check-circle";

                        } elseif ($status === 'in_progress') {

                            $status_text =
                                "In Progress";

                            $status_class =
                                "status-progress";

                            $status_icon =
                                "bi-clock";

                        }

                        ?>


                        <div class="practical-card">


                            <div class="practical-top">


                                <div
                                    class="practical-number">

                                    P<?= $p ?>

                                </div>


                                <i
                                    class="bi
                                    <?= $practical_icons[$p] ?>
                                    practical-icon">
                                </i>


                            </div>


                            <h4>

                                <?= htmlspecialchars(
                                    $practical_names[$p]
                                ) ?>

                            </h4>


                            <p>

                                Practical <?= $p ?>

                            </p>


                            <span
                                class="status-badge
                                <?= $status_class ?>">

                                <i
                                    class="bi
                                    <?= $status_icon ?>">
                                </i>

                                <?= $status_text ?>

                            </span>


                            <?php if ($status === 'completed'): ?>

                                <div class="progress-wrapper">


                                    <div class="progress-label">

                                        <span>
                                            Progress
                                        </span>

                                        <span>
                                            100%
                                        </span>

                                    </div>


                                    <div class="progress">

                                        <div
                                            class="progress-bar"
                                            style="width:100%">
                                        </div>

                                    </div>


                                </div>

                            <?php elseif ($status === 'in_progress'): ?>

                                <div class="progress-wrapper">


                                    <div class="progress-label">

                                        <span>
                                            Progress
                                        </span>

                                        <span>
                                            In Progress
                                        </span>

                                    </div>


                                    <div class="progress">

                                        <div
                                            class="progress-bar"
                                            style="width:50%">
                                        </div>

                                    </div>


                                </div>

                            <?php endif; ?>


                        </div>


                    <?php endfor; ?>


                </div>


            </div>


        </section>


        <!-- =================================================
             ACTIVITIES
        ================================================= -->

        <section class="card-custom">


            <div class="card-header-custom">


                <div>

                    <h3>

                        <i class="bi bi-list-check me-2"></i>

                        Activity Progress

                    </h3>

                    <p>
                        Activities completed by the student
                    </p>

                </div>


                <span
                    class="badge rounded-pill text-bg-light">

                    <?= $total_completed_activities ?>

                    completed

                </span>


            </div>


            <div class="card-body-custom"
                 style="padding:0;">


                <?php if (count($activities) > 0): ?>


                    <div class="table-responsive">


                        <table
                            class="table table-hover align-middle">


                            <thead>

                                <tr>

                                    <th>
                                        Practical
                                    </th>

                                    <th>
                                        Activity
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $activities
                                    as $activity
                                ): ?>


                                    <tr>


                                        <td>

                                            <strong>

                                                Practical
                                                <?= (int)
                                                    $activity[
                                                        'practical_number'
                                                    ] ?>

                                            </strong>

                                        </td>


                                        <td>

                                            Activity
                                            <?= (int)
                                                $activity[
                                                    'activity_number'
                                                ] ?>

                                        </td>


                                        <td>


                                            <?php
                                            if (
                                                (int)
                                                $activity[
                                                    'completed'
                                                ] === 1
                                            ):
                                            ?>


                                                <span
                                                    class="activity-status
                                                    text-success">

                                                    <i
                                                        class="bi
                                                        bi-check-circle">
                                                    </i>

                                                    Completed

                                                </span>


                                            <?php else: ?>


                                                <span
                                                    class="activity-status
                                                    text-muted">

                                                    <i
                                                        class="bi
                                                        bi-circle">
                                                    </i>

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


                    <div class="empty-state">

                        <i class="bi bi-list-check"></i>

                        <p>
                            No activity progress has been recorded
                            for this student.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


        </section>


        <!-- =================================================
             SUBMISSIONS
        ================================================= -->

        <section class="card-custom">


            <div class="card-header-custom">


                <div>

                    <h3>

                        <i class="bi bi-file-earmark-text me-2"></i>

                        Practical Submissions

                    </h3>

                    <p>
                        Results submitted by the student
                    </p>

                </div>


                <span
                    class="badge rounded-pill text-bg-light">

                    <?= $total_submissions ?>

                    submissions

                </span>


            </div>


            <div class="card-body-custom">


                <?php if (count($submissions) > 0): ?>


                    <?php foreach (
                        $submissions
                        as $submission
                    ): ?>


                        <div class="submission-box">


                            <div class="submission-top">


                                <div
                                    class="submission-title">

                                    <i
                                        class="bi
                                        bi-file-earmark-check
                                        me-1">
                                    </i>

                                    Practical
                                    <?= (int)
                                        $submission[
                                            'practical_number'
                                        ] ?>

                                </div>


                                <div
                                    class="submission-date">

                                    <i
                                        class="bi
                                        bi-calendar3 me-1">
                                    </i>

                                    <?= htmlspecialchars(
                                        $submission[
                                            'submitted_at'
                                        ]
                                        ?? 'Not submitted'
                                    ) ?>

                                </div>


                            </div>


                            <!-- RESULTS -->

                            <div class="submission-section">


                                <div
                                    class="submission-label">

                                    Results

                                </div>


                                <div
                                    class="submission-text">

                                    <?php

                                    $results =
                                        trim(
                                            $submission[
                                                'results'
                                            ] ?? ''
                                        );

                                    echo $results !== ''
                                        ? nl2br(
                                            htmlspecialchars(
                                                $results
                                            )
                                        )
                                        : '<span class="text-muted">
                                            No results provided.
                                           </span>';

                                    ?>

                                </div>


                            </div>


                            <!-- OBSERVATIONS -->

                            <div class="submission-section">


                                <div
                                    class="submission-label">

                                    Observations

                                </div>


                                <div
                                    class="submission-text">

                                    <?php

                                    $observations =
                                        trim(
                                            $submission[
                                                'observations'
                                            ] ?? ''
                                        );

                                    echo $observations !== ''
                                        ? nl2br(
                                            htmlspecialchars(
                                                $observations
                                            )
                                        )
                                        : '<span class="text-muted">
                                            No observations provided.
                                           </span>';

                                    ?>

                                </div>


                            </div>


                            <!-- CONCLUSION -->

                            <div class="submission-section">


                                <div
                                    class="submission-label">

                                    Conclusion

                                </div>


                                <div
                                    class="submission-text">

                                    <?php

                                    $conclusion =
                                        trim(
                                            $submission[
                                                'conclusion'
                                            ] ?? ''
                                        );

                                    echo $conclusion !== ''
                                        ? nl2br(
                                            htmlspecialchars(
                                                $conclusion
                                            )
                                        )
                                        : '<span class="text-muted">
                                            No conclusion provided.
                                           </span>';

                                    ?>

                                </div>


                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="empty-state">

                        <i
                            class="bi
                            bi-file-earmark-text">
                        </i>

                        <p>
                            This student has not submitted
                            any practical results.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


        </section>


        <!-- =================================================
             SIMULATION RESULTS
        ================================================= -->

        <section class="card-custom">


            <div class="card-header-custom">


                <div>

                    <h3>

                        <i class="bi bi-activity me-2"></i>

                        Simulation Results

                    </h3>

                    <p>
                        Simulation records generated by the student
                    </p>

                </div>


                <span
                    class="badge rounded-pill text-bg-light">

                    <?= $total_simulations ?>

                    simulations

                </span>


            </div>


            <div
                class="card-body-custom"
                style="padding:0;">


                <?php if (count($simulations) > 0): ?>


                    <div class="table-responsive">


                        <table
                            class="table table-hover align-middle">


                            <thead>

                                <tr>

                                    <th>
                                        Practical
                                    </th>

                                    <th>
                                        Simulation Type
                                    </th>

                                    <th>
                                        Input Data
                                    </th>

                                    <th>
                                        Result Data
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $simulations
                                    as $simulation
                                ): ?>


                                    <tr>


                                        <td>

                                            <strong>

                                                Practical
                                                <?= (int)
                                                    $simulation[
                                                        'practical_number'
                                                    ] ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <span
                                                class="simulation-name">

                                                <?= htmlspecialchars(
                                                    $simulation[
                                                        'simulation_type'
                                                    ]
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>


                                            <div
                                                class="data-preview"
                                                title="<?=
                                                    htmlspecialchars(
                                                        $simulation[
                                                            'input_data'
                                                        ] ?? ''
                                                    )
                                                ?>">

                                                <?= htmlspecialchars(
                                                    $simulation[
                                                        'input_data'
                                                    ] ?? '—'
                                                ) ?>

                                            </div>


                                        </td>


                                        <td>


                                            <div
                                                class="data-preview"
                                                title="<?=
                                                    htmlspecialchars(
                                                        $simulation[
                                                            'result_data'
                                                        ] ?? ''
                                                    )
                                                ?>">

                                                <?= htmlspecialchars(
                                                    $simulation[
                                                        'result_data'
                                                    ] ?? '—'
                                                ) ?>

                                            </div>


                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $simulation[
                                                    'created_at'
                                                ]
                                            ) ?>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <div class="empty-state">

                        <i
                            class="bi bi-activity">
                        </i>

                        <p>
                            No simulation results have been
                            recorded for this student.
                        </p>

                    </div>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   MOBILE SIDEBAR
========================================================= */

const sidebar =
    document.getElementById("sidebar");

const overlay =
    document.getElementById("sidebarOverlay");

const menuBtn =
    document.getElementById("mobileMenuBtn");


if (menuBtn) {

    menuBtn.addEventListener(
        "click",
        function () {

            sidebar.classList.add("show");

            overlay.classList.add("show");

        }
    );

}


if (overlay) {

    overlay.addEventListener(
        "click",
        function () {

            sidebar.classList.remove("show");

            overlay.classList.remove("show");

        }
    );

}


/* =========================================================
   CLOSE SIDEBAR AFTER NAVIGATION
========================================================= */

document
    .querySelectorAll(".sidebar-menu a")
    .forEach(function (link) {

        link.addEventListener(
            "click",
            function () {

                sidebar.classList.remove("show");

                overlay.classList.remove("show");

            }
        );

    });

</script>


</body>

</html>