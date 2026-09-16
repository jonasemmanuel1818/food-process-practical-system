<?php
require_once "../config.php";

/* =========================================================
   ACCESS CONTROL
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$current_user = null;

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, full_name, username, role
     FROM users
     WHERE id = ?
     LIMIT 1"
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $_SESSION['user_id']
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $current_user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
}


/* Only admin and lecturer */

if (
    !$current_user ||
    !in_array(
        $current_user['role'],
        ['admin', 'lecturer'],
        true
    )
) {

    http_response_code(403);

    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Denied</title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: Arial, Helvetica, sans-serif;
                background: #f4f6f8;

                min-height: 100vh;

                display: flex;
                align-items: center;
                justify-content: center;
            }

            .denied-box {
                width: min(430px, 90%);
                background: #ffffff;

                border: 1px solid #d9dee3;
                border-radius: 8px;

                padding: 35px;

                text-align: center;
            }

            .denied-icon {
                width: 50px;
                height: 50px;

                margin: 0 auto 15px;

                background: #eaf2f5;
                color: #1f5f75;

                border-radius: 50%;

                display: flex;
                align-items: center;
                justify-content: center;

                font-size: 21px;
            }

            h2 {
                color: #17495a;
                margin: 0 0 10px;
                font-size: 21px;
            }

            p {
                color: #68777f;
                font-size: 14px;
                line-height: 1.6;
            }

            a {
                display: inline-block;

                margin-top: 10px;

                padding: 10px 18px;

                background: #1f5f75;
                color: #ffffff;

                text-decoration: none;

                border-radius: 4px;

                font-size: 13px;
                font-weight: 600;
            }

            a:hover {
                background: #17495a;
            }

        </style>
    </head>

    <body>

        <div class="denied-box">

            <div class="denied-icon">
                <i>!</i>
            </div>

            <h2>Access Denied</h2>

            <p>
                You do not have permission to access
                the administration area.
            </p>

            <a href="../index.php">
                Return to Home
            </a>

        </div>

    </body>
    </html>
    ';

    exit();
}


/* =========================================================
   DASHBOARD STATISTICS
========================================================= */


/* Total Students */

$total_students = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'student'"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_students = (int) $row['total'];
}


/* Total Submissions */

$total_submissions = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM practical_submissions"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_submissions = (int) $row['total'];
}


/* Total Simulations */

$total_simulations = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM simulation_results"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $total_simulations = (int) $row['total'];
}


/* Completed Practicals */

$completed_practicals = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM practical_progress
     WHERE status = 'completed'"
);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $completed_practicals = (int) $row['total'];
}


/* =========================================================
   RECENT SUBMISSIONS
========================================================= */

$recent_submissions = [];

$query = "
    SELECT
        ps.id,
        ps.practical_number,
        ps.submitted_at,
        u.full_name
    FROM practical_submissions ps
    INNER JOIN users u
        ON ps.user_id = u.id
    ORDER BY ps.submitted_at DESC
    LIMIT 8
";

$result = mysqli_query($conn, $query);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $recent_submissions[] = $row;

    }
}


/* =========================================================
   PRACTICAL COUNTS
========================================================= */

$practical_counts = [
    1 => 0,
    2 => 0,
    3 => 0,
    4 => 0
];

$result = mysqli_query(
    $conn,
    "SELECT practical_number, COUNT(*) AS total
     FROM practical_submissions
     GROUP BY practical_number"
);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $number = (int) $row['practical_number'];

        if (isset($practical_counts[$number])) {

            $practical_counts[$number] =
                (int) $row['total'];

        }
    }
}


/* =========================================================
   ADMIN INITIAL
========================================================= */

$admin_initial = strtoupper(
    substr(
        trim($current_user['full_name']),
        0,
        1
    )
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
        Admin Dashboard | Food Process System
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Bootstrap Icons -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f4f6f8;

            color: #263238;

        }


        a {
            text-decoration: none;
        }


        /* =====================================================
           SIDEBAR
        ====================================================== */

        .sidebar {

            position: fixed;

            top: 0;
            left: 0;
            bottom: 0;

            width: 245px;

            background: #17495a;

            color: #ffffff;

            z-index: 1100;

            display: flex;

            flex-direction: column;

            overflow-y: auto;

        }


        .sidebar-brand {

            height: 72px;

            padding: 0 20px;

            display: flex;

            align-items: center;

            gap: 12px;

            border-bottom:
                1px solid rgba(255,255,255,0.10);

            flex-shrink: 0;

        }


        .brand-icon {

            width: 39px;
            height: 39px;

            background: #1f5f75;

            border-radius: 5px;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 19px;

        }


        .brand-text {

            font-size: 14px;

            font-weight: 600;

            line-height: 1.3;

        }


        .brand-text small {

            display: block;

            color: #a9c0c9;

            font-size: 10px;

            font-weight: normal;

            margin-top: 3px;

        }


        .sidebar-content {

            padding: 22px 13px;

            flex: 1;

        }


        .sidebar-heading {

            color: #91afb9;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1px;

            padding: 0 10px;

            margin-bottom: 9px;

        }


        .sidebar-menu {

            list-style: none;

            padding: 0;

            margin: 0;

        }


        .sidebar-menu li {

            margin-bottom: 3px;

        }


        .sidebar-menu a {

            display: flex;

            align-items: center;

            gap: 11px;

            color: #dbe8ed;

            padding: 10px 11px;

            border-radius: 4px;

            font-size: 13px;

            font-weight: 500;

            transition: 0.15s ease;

        }


        .sidebar-menu a i {

            width: 19px;

            font-size: 15px;

        }


        .sidebar-menu a:hover {

            background: rgba(255,255,255,0.08);

            color: #ffffff;

        }


        .sidebar-menu a.active {

            background: #ffffff;

            color: #17495a;

            font-weight: 600;

        }


        .sidebar-divider {

            height: 1px;

            background:
                rgba(255,255,255,0.10);

            margin: 22px 10px;

        }


        .sidebar-footer {

            padding: 15px;

            border-top:
                1px solid rgba(255,255,255,0.10);

        }


        .admin-profile {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .admin-avatar {

            width: 37px;
            height: 37px;

            flex-shrink: 0;

            border-radius: 50%;

            background: #e8eef1;

            color: #1f5f75;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 13px;

            font-weight: 700;

        }


        .admin-profile-name {

            max-width: 155px;

            overflow: hidden;

            white-space: nowrap;

            text-overflow: ellipsis;

            font-size: 12px;

            font-weight: 600;

            color: #ffffff;

        }


        .admin-profile-role {

            color: #9fb7c0;

            font-size: 10px;

            margin-top: 3px;

            text-transform: capitalize;

        }


        /* =====================================================
           MAIN
        ====================================================== */

        .main {

            margin-left: 245px;

            min-height: 100vh;

        }


        /* =====================================================
           TOPBAR
        ====================================================== */

        .topbar {

            height: 72px;

            background: #ffffff;

            border-bottom:
                1px solid #d9dee3;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 28px;

        }


        .mobile-menu {

            display: none;

            width: 37px;
            height: 37px;

            border:
                1px solid #d9dee3;

            background: #ffffff;

            color: #1f5f75;

            border-radius: 4px;

            align-items: center;
            justify-content: center;

        }


        .topbar-title h1 {

            margin: 0;

            font-size: 20px;

            font-weight: 600;

            color: #263238;

        }


        .topbar-title p {

            margin: 4px 0 0;

            color: #89949b;

            font-size: 11px;

        }


        .topbar-right {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .topbar-link {

            border:
                1px solid #d2d9dd;

            background: #ffffff;

            color: #53636c;

            padding: 8px 12px;

            border-radius: 4px;

            font-size: 12px;

            font-weight: 600;

        }


        .topbar-link:hover {

            background: #eef3f5;

            color: #1f5f75;

        }


        .top-avatar {

            width: 37px;
            height: 37px;

            border-radius: 50%;

            background: #e8eef1;

            color: #1f5f75;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 13px;

            font-weight: 700;

        }


        /* =====================================================
           CONTENT
        ====================================================== */

        .content {

            padding: 27px 28px 45px;

        }


        .welcome {

            margin-bottom: 22px;

        }


        .welcome h2 {

            margin: 0;

            font-size: 21px;

            font-weight: 600;

            color: #263238;

        }


        .welcome p {

            margin: 6px 0 0;

            color: #7b8790;

            font-size: 13px;

        }


        /* =====================================================
           STATISTICS
        ====================================================== */

        .stat-card {

            background: #ffffff;

            border:
                1px solid #d9dee3;

            border-radius: 6px;

            padding: 19px;

            height: 100%;

        }


        .stat-top {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 16px;

        }


        .stat-icon {

            width: 43px;
            height: 43px;

            background: #e8f1f4;

            color: #1f5f75;

            border-radius: 5px;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 18px;

        }


        .stat-number {

            font-size: 25px;

            line-height: 1;

            font-weight: 600;

            color: #263238;

        }


        .stat-label {

            color: #7b8790;

            font-size: 11px;

            margin-top: 6px;

        }


        /* =====================================================
           CARDS
        ====================================================== */

        .dashboard-card {

            background: #ffffff;

            border:
                1px solid #d9dee3;

            border-radius: 6px;

            overflow: hidden;

        }


        .card-header-custom {

            padding: 16px 18px;

            border-bottom:
                1px solid #e5e9ec;

            display: flex;

            align-items: center;

            justify-content: space-between;

        }


        .card-header-custom h3 {

            margin: 0;

            color: #37474f;

            font-size: 15px;

            font-weight: 600;

        }


        .card-header-custom a {

            color: #1f5f75;

            font-size: 11px;

            font-weight: 600;

        }


        .card-body-custom {

            padding: 0;

        }


        /* =====================================================
           QUICK ACTIONS
        ====================================================== */

        .quick-actions {

            padding: 17px;

        }


        .quick-action {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 12px;

            margin-bottom: 9px;

            border:
                1px solid #d9dee3;

            border-radius: 5px;

            color: #37474f;

            transition: 0.15s ease;

        }


        .quick-action:last-child {

            margin-bottom: 0;

        }


        .quick-action:hover {

            background: #f7fafb;

            border-color: #b9cbd2;

            color: #1f5f75;

        }


        .quick-action-icon {

            width: 36px;
            height: 36px;

            flex-shrink: 0;

            background: #e8f1f4;

            color: #1f5f75;

            border-radius: 4px;

            display: flex;

            align-items: center;
            justify-content: center;

        }


        .quick-action strong {

            display: block;

            font-size: 12px;

        }


        .quick-action small {

            display: block;

            margin-top: 3px;

            color: #929da3;

            font-size: 10px;

        }


        /* =====================================================
           PRACTICAL OVERVIEW
        ====================================================== */

        .practical-item {

            padding: 15px 17px;

            border-bottom:
                1px solid #edf0f2;

        }


        .practical-item:last-child {

            border-bottom: none;

        }


        .practical-top {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 9px;

        }


        .practical-name {

            color: #37474f;

            font-size: 12px;

            font-weight: 600;

        }


        .practical-count {

            color: #89949b;

            font-size: 10px;

        }


        .progress {

            height: 6px;

            background: #edf1f3;

            border-radius: 4px;

        }


        .progress-bar {

            background: #1f5f75;

            border-radius: 4px;

        }


        /* =====================================================
           SUBMISSIONS TABLE
        ====================================================== */

        .submission-table {

            width: 100%;

            margin: 0;

            border-collapse: collapse;

        }


        .submission-table th {

            background: #f7f9fa;

            color: #7b8790;

            font-size: 10px;

            font-weight: 600;

            text-transform: uppercase;

            padding: 11px 15px;

            border-bottom:
                1px solid #e5e9ec;

            white-space: nowrap;

        }


        .submission-table td {

            padding: 12px 15px;

            border-bottom:
                1px solid #edf0f2;

            color: #596870;

            font-size: 12px;

        }


        .submission-table tr:last-child td {

            border-bottom: none;

        }


        .student-name {

            color: #37474f;

            font-weight: 600;

        }


        .practical-badge {

            display: inline-block;

            background: #e8f1f4;

            color: #1f5f75;

            border-radius: 4px;

            padding: 5px 8px;

            font-size: 10px;

            font-weight: 600;

        }


        .view-button {

            border:
                1px solid #1f5f75;

            color: #1f5f75;

            background: #ffffff;

            border-radius: 4px;

            padding: 5px 9px;

            font-size: 10px;

            font-weight: 600;

        }


        .view-button:hover {

            background: #1f5f75;

            color: #ffffff;

        }


        /* =====================================================
           EMPTY STATE
        ====================================================== */

        .empty-state {

            text-align: center;

            padding: 35px 20px;

            color: #89949b;

            font-size: 12px;

        }


        .empty-state i {

            display: block;

            font-size: 28px;

            color: #b5c0c5;

            margin-bottom: 9px;

        }


        /* =====================================================
           MOBILE OVERLAY
        ====================================================== */

        .sidebar-overlay {

            display: none;

            position: fixed;

            inset: 0;

            background:
                rgba(0,0,0,0.25);

            z-index: 1050;

        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1050px) {

            .sidebar {

                width: 225px;

            }

            .main {

                margin-left: 225px;

            }

        }


        @media (max-width: 850px) {

            .sidebar {

                transform:
                    translateX(-100%);

                transition:
                    transform 0.2s ease;

            }


            .sidebar.show {

                transform:
                    translateX(0);

            }


            .sidebar-overlay.show {

                display: block;

            }


            .main {

                margin-left: 0;

            }


            .mobile-menu {

                display: flex;

            }


            .topbar {

                padding: 0 18px;

            }


            .content {

                padding: 22px 18px 35px;

            }

        }


        @media (max-width: 576px) {

            .topbar {

                height: auto;

                min-height: 70px;

                padding: 14px 15px;

            }


            .topbar-title h1 {

                font-size: 18px;

            }


            .topbar-title p {

                display: none;

            }


            .topbar-link {

                display: none;

            }


            .content {

                padding: 20px 12px 30px;

            }


            .welcome h2 {

                font-size: 19px;

            }


            .stat-card {

                padding: 17px;

            }


            .submission-table th:nth-child(3),
            .submission-table td:nth-child(3) {

                display: none;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR OVERLAY
========================================================== -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
    onclick="closeSidebar()"
></div>


<!-- =========================================================
     SIDEBAR
========================================================== -->

<aside
    class="sidebar"
    id="sidebar"
>


    <!-- BRAND -->

    <div class="sidebar-brand">

        <div class="brand-icon">

            <i class="bi bi-flask"></i>

        </div>


        <div class="brand-text">

            Food Process System

            <small>
                Administration
            </small>

        </div>

    </div>


    <!-- MENU -->

    <div class="sidebar-content">


        <div class="sidebar-heading">
            ADMINISTRATION
        </div>


        <ul class="sidebar-menu">


            <li>

                <a
                    href="dashboard.php"
                    class="active"
                >

                    <i class="bi bi-grid-1x2"></i>

                    Dashboard

                </a>

            </li>


            <li>

                <a href="students.php">

                    <i class="bi bi-people"></i>

                    Students

                </a>

            </li>


            <li>

                <a href="submissions.php">

                    <i class="bi bi-file-earmark-text"></i>

                    Submissions

                </a>

            </li>


            <li>

                <a href="simulation_results.php">

                    <i class="bi bi-graph-up"></i>

                    Simulation Results

                </a>

            </li>


            <li>

                <a href="register.php">

                    <i class="bi bi-person-plus"></i>

                    Register Admin

                </a>

            </li>


        </ul>


        <div class="sidebar-divider"></div>


        <div class="sidebar-heading">
            ACCOUNT
        </div>


        <ul class="sidebar-menu">


            <li>

                <a href="settings.php">

                    <i class="bi bi-gear"></i>

                    Settings

                </a>

            </li>


            <li>

                <a href="../logout.php">

                    <i class="bi bi-box-arrow-right"></i>

                    Log Out

                </a>

            </li>


        </ul>


    </div>


    <!-- PROFILE -->

    <div class="sidebar-footer">

        <div class="admin-profile">


            <div class="admin-avatar">

                <?= htmlspecialchars($admin_initial) ?>

            </div>


            <div>

                <div class="admin-profile-name">

                    <?= htmlspecialchars(
                        $current_user['full_name']
                    ) ?>

                </div>


                <div class="admin-profile-role">

                    <?= htmlspecialchars(
                        $current_user['role']
                    ) ?>

                </div>

            </div>


        </div>

    </div>


</aside>


<!-- =========================================================
     MAIN
========================================================== -->

<div class="main">


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">


        <div class="d-flex align-items-center gap-3">


            <button
                type="button"
                class="mobile-menu"
                onclick="openSidebar()"
            >

                <i class="bi bi-list"></i>

            </button>


            <div class="topbar-title">

                <h1>
                    Admin Dashboard
                </h1>

                <p>
                    Food Process Practical Learning System
                </p>

            </div>


        </div>


        <div class="topbar-right">


            <a
                href="../dashboard.php"
                class="topbar-link"
            >

                <i class="bi bi-box-arrow-up-right me-1"></i>

                Student View

            </a>


            <div class="top-avatar">

                <?= htmlspecialchars($admin_initial) ?>

            </div>


        </div>


    </header>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <main class="content">


        <!-- WELCOME -->

        <div class="welcome">

            <h2>

                Welcome,
                <?= htmlspecialchars(
                    $current_user['full_name']
                ) ?>

            </h2>


            <p>

                Manage students, practical submissions,
                simulations and system administration.

            </p>

        </div>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="row g-3 mb-4">


            <!-- STUDENTS -->

            <div class="col-12 col-sm-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon">

                            <i class="bi bi-people"></i>

                        </div>

                    </div>


                    <div class="stat-number">

                        <?= $total_students ?>

                    </div>


                    <div class="stat-label">

                        Total Students

                    </div>

                </div>

            </div>


            <!-- SUBMISSIONS -->

            <div class="col-12 col-sm-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon">

                            <i class="bi bi-file-earmark-check"></i>

                        </div>

                    </div>


                    <div class="stat-number">

                        <?= $total_submissions ?>

                    </div>


                    <div class="stat-label">

                        Practical Submissions

                    </div>

                </div>

            </div>


            <!-- SIMULATIONS -->

            <div class="col-12 col-sm-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon">

                            <i class="bi bi-activity"></i>

                        </div>

                    </div>


                    <div class="stat-number">

                        <?= $total_simulations ?>

                    </div>


                    <div class="stat-label">

                        Simulation Results

                    </div>

                </div>

            </div>


            <!-- COMPLETED -->

            <div class="col-12 col-sm-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon">

                            <i class="bi bi-check-circle"></i>

                        </div>

                    </div>


                    <div class="stat-number">

                        <?= $completed_practicals ?>

                    </div>


                    <div class="stat-label">

                        Completed Practicals

                    </div>

                </div>

            </div>


        </div>


        <!-- =================================================
             QUICK ACTIONS + PRACTICAL OVERVIEW
        ================================================== -->

        <div class="row g-3 mb-4">


            <!-- QUICK ACTIONS -->

            <div class="col-lg-5">

                <div class="dashboard-card">


                    <div class="card-header-custom">

                        <h3>
                            Quick Actions
                        </h3>

                    </div>


                    <div class="quick-actions">


                        <a
                            href="students.php"
                            class="quick-action"
                        >

                            <div class="quick-action-icon">

                                <i class="bi bi-people"></i>

                            </div>


                            <div>

                                <strong>
                                    Manage Students
                                </strong>

                                <small>
                                    View student accounts and progress
                                </small>

                            </div>

                        </a>


                        <a
                            href="submissions.php"
                            class="quick-action"
                        >

                            <div class="quick-action-icon">

                                <i class="bi bi-file-earmark-text"></i>

                            </div>


                            <div>

                                <strong>
                                    Review Submissions
                                </strong>

                                <small>
                                    View submitted practical results
                                </small>

                            </div>

                        </a>


                        <a
                            href="simulation_results.php"
                            class="quick-action"
                        >

                            <div class="quick-action-icon">

                                <i class="bi bi-graph-up"></i>

                            </div>


                            <div>

                                <strong>
                                    Simulation Results
                                </strong>

                                <small>
                                    Review student simulations
                                </small>

                            </div>

                        </a>


                        <a
                            href="register.php"
                            class="quick-action"
                        >

                            <div class="quick-action-icon">

                                <i class="bi bi-person-plus"></i>

                            </div>


                            <div>

                                <strong>
                                    Register Admin
                                </strong>

                                <small>
                                    Create another administrator account
                                </small>

                            </div>

                        </a>


                    </div>

                </div>

            </div>


            <!-- PRACTICAL OVERVIEW -->

            <div class="col-lg-7">

                <div class="dashboard-card">


                    <div class="card-header-custom">

                        <h3>
                            Practical Overview
                        </h3>


                        <a href="submissions.php">
                            View Submissions
                        </a>

                    </div>


                    <div class="card-body-custom">


                        <?php

                        $practical_names = [

                            1 => "Laboratory Orientation",

                            2 => "Physical Separation",

                            3 => "Thermal Processing",

                            4 => "Drying"

                        ];

                        ?>


                        <?php for ($i = 1; $i <= 4; $i++): ?>


                            <?php

                            $percentage = 0;

                            if ($total_students > 0) {

                                $percentage =
                                    min(
                                        100,
                                        (
                                            $practical_counts[$i]
                                            /
                                            $total_students
                                        ) * 100
                                    );

                            }

                            ?>


                            <div class="practical-item">


                                <div class="practical-top">


                                    <div class="practical-name">

                                        Practical <?= $i ?>

                                        —
                                        <?= htmlspecialchars(
                                            $practical_names[$i]
                                        ) ?>

                                    </div>


                                    <div class="practical-count">

                                        <?= $practical_counts[$i] ?>

                                        submission(s)

                                    </div>


                                </div>


                                <div class="progress">

                                    <div
                                        class="progress-bar"
                                        role="progressbar"
                                        style="width: <?= $percentage ?>%;"
                                    ></div>

                                </div>


                            </div>


                        <?php endfor; ?>


                    </div>

                </div>

            </div>


        </div>


        <!-- =================================================
             RECENT SUBMISSIONS
        ================================================== -->

        <div class="dashboard-card">


            <div class="card-header-custom">

                <h3>
                    Recent Submissions
                </h3>


                <a href="submissions.php">
                    View All
                </a>

            </div>


            <div class="table-responsive">


                <?php if (!empty($recent_submissions)): ?>


                    <table class="submission-table">


                        <thead>

                            <tr>

                                <th>
                                    Student
                                </th>

                                <th>
                                    Practical
                                </th>

                                <th>
                                    Submitted
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $recent_submissions
                                as $submission
                            ): ?>


                                <tr>


                                    <td>

                                        <span class="student-name">

                                            <?= htmlspecialchars(
                                                $submission['full_name']
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="practical-badge"
                                        >

                                            Practical
                                            <?= (int)
                                                $submission[
                                                    'practical_number'
                                                ] ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php

                                        if (
                                            !empty(
                                                $submission[
                                                    'submitted_at'
                                                ]
                                            )
                                        ) {

                                            echo date(
                                                "d M Y, H:i",
                                                strtotime(
                                                    $submission[
                                                        'submitted_at'
                                                    ]
                                                )
                                            );

                                        } else {

                                            echo "—";

                                        }

                                        ?>

                                    </td>


                                    <td>

                                        <a
                                            href="submissions.php"
                                            class="view-button"
                                        >

                                            View

                                        </a>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                <?php else: ?>


                    <div class="empty-state">

                        <i class="bi bi-inbox"></i>

                        No practical submissions have
                        been recorded yet.

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


<script>

/* =========================================================
   MOBILE SIDEBAR
========================================================= */

function openSidebar() {

    document
        .getElementById("sidebar")
        .classList.add("show");

    document
        .getElementById("sidebarOverlay")
        .classList.add("show");

}


function closeSidebar() {

    document
        .getElementById("sidebar")
        .classList.remove("show");

    document
        .getElementById("sidebarOverlay")
        .classList.remove("show");

}


/* Close sidebar after selecting a link on mobile */

document
    .querySelectorAll(".sidebar a")
    .forEach(function(link) {

        link.addEventListener(
            "click",
            function() {

                if (
                    window.innerWidth <= 850
                ) {

                    closeSidebar();

                }

            }
        );

    });

</script>


</body>

</html>