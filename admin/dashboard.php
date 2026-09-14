<?php
require_once "../config.php";

/* =========================
   ACCESS CONTROL
========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$current_user = null;

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, full_name, username, role
     FROM users
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$current_user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (
    !$current_user ||
    !in_array($current_user['role'], ['admin', 'lecturer'], true)
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
            body {
                font-family: Arial, sans-serif;
                background: #f4f6f8;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
            }

            .box {
                background: white;
                padding: 40px;
                border-radius: 12px;
                text-align: center;
                box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            }

            h2 {
                color: #17495a;
                margin-bottom: 10px;
            }

            p {
                color: #666;
            }

            a {
                display: inline-block;
                margin-top: 15px;
                padding: 10px 18px;
                background: #1f5f75;
                color: white;
                text-decoration: none;
                border-radius: 6px;
            }
        </style>
    </head>

    <body>

        <div class="box">
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

    exit;
}


/* =========================
   DASHBOARD STATISTICS
========================= */

/* Total students */
$total_students = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'student'"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_students = (int)$row['total'];
}


/* Total submissions */
$total_submissions = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM practical_submissions"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_submissions = (int)$row['total'];
}


/* Total simulations */
$total_simulations = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM simulation_results"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_simulations = (int)$row['total'];
}


/* Completed practical progress */
$completed_practicals = 0;

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM practical_progress
     WHERE status = 'completed'"
);

if ($result) {
    $row = mysqli_fetch_assoc($result);
    $completed_practicals = (int)$row['total'];
}


/* =========================
   RECENT SUBMISSIONS
========================= */

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


/* =========================
   PRACTICAL COUNTS
========================= */

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
        $number = (int)$row['practical_number'];

        if (isset($practical_counts[$number])) {
            $practical_counts[$number] = (int)$row['total'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Dashboard | Food Process System</title>

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
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f8;
            color: #24343b;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: #17495a;
            padding: 25px 16px;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-title {
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 15px;
            margin-bottom: 18px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #dbe8ed;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 7px;
            font-size: 14px;
            transition: 0.2s ease;
        }

        .sidebar a i {
            font-size: 17px;
            width: 20px;
        }

        .sidebar a:hover {
            background: #1f5f75;
            color: #ffffff;
        }

        .sidebar a.active {
            background: #ffffff;
            color: #17495a;
            font-weight: 600;
        }

        /* Divider */
        .nav-section-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.20);
            margin: 20px 10px;
        }

        .sidebar-heading {
            color: #a9c0c9;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 15px;
            margin-bottom: 10px;
        }

        .logout {
            margin-top: 5px;
        }

        .logout a {
            color: #f1d5d5;
        }

        .logout a:hover {
            background: #7d3434;
            color: #ffffff;
        }

        /* =========================
           MAIN CONTENT
        ========================= */

        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        /* =========================
           TOP BAR
        ========================= */

        .topbar {
            height: 72px;
            background: #ffffff;
            border-bottom: 1px solid #d9dee3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .topbar h1 {
            font-size: 22px;
            margin: 0;
            color: #17495a;
            font-weight: 700;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1f5f75;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .admin-details {
            line-height: 1.2;
        }

        .admin-name {
            font-size: 14px;
            font-weight: 600;
            color: #24343b;
        }

        .admin-role {
            font-size: 12px;
            color: #7b858b;
            text-transform: capitalize;
        }

        /* =========================
           CONTENT
        ========================= */

        .content {
            padding: 30px;
        }

        .welcome-box {
            background: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 25px;
        }

        .welcome-box h2 {
            margin: 0 0 7px;
            font-size: 23px;
            color: #17495a;
        }

        .welcome-box p {
            margin: 0;
            color: #6c757d;
            font-size: 14px;
        }

        /* =========================
           STAT CARDS
        ========================= */

        .stat-card {
            background: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 10px;
            padding: 22px;
            height: 100%;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            background: #eaf2f5;
            color: #1f5f75;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-size: 28px;
            margin: 0;
            color: #17495a;
        }

        .stat-card p {
            margin: 5px 0 0;
            color: #6c757d;
            font-size: 13px;
        }

        /* =========================
           SECTION CARDS
        ========================= */

        .section-card {
            background: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
        }

        .section-header {
            padding: 18px 20px;
            border-bottom: 1px solid #d9dee3;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h3 {
            margin: 0;
            font-size: 17px;
            color: #17495a;
        }

        .section-body {
            padding: 20px;
        }

        /* =========================
           QUICK ACTIONS
        ========================= */

        .quick-action {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            padding: 14px;
            border: 1px solid #d9dee3;
            border-radius: 8px;
            color: #24343b;
            margin-bottom: 10px;
            transition: 0.2s ease;
        }

        .quick-action:hover {
            border-color: #1f5f75;
            background: #f7fafb;
            color: #17495a;
        }

        .quick-action-icon {
            width: 40px;
            height: 40px;
            background: #eaf2f5;
            color: #1f5f75;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quick-action strong {
            display: block;
            font-size: 14px;
        }

        .quick-action small {
            color: #7b858b;
            font-size: 12px;
        }

        /* =========================
           PRACTICAL CARDS
        ========================= */

        .practical-card {
            border: 1px solid #d9dee3;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .practical-card:last-child {
            margin-bottom: 0;
        }

        .practical-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .practical-title strong {
            color: #17495a;
            font-size: 14px;
        }

        .practical-count {
            font-size: 12px;
            color: #6c757d;
        }

        /* =========================
           TABLE
        ========================= */

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #f7f8f9;
            color: #17495a;
            font-size: 12px;
            font-weight: 700;
            border-bottom: 1px solid #d9dee3;
            white-space: nowrap;
        }

        .table tbody td {
            font-size: 13px;
            vertical-align: middle;
        }

        .badge-practical {
            background: #eaf2f5;
            color: #17495a;
            padding: 6px 9px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
        }

        /* =========================
           EMPTY STATE
        ========================= */

        .empty-state {
            text-align: center;
            padding: 35px 20px;
            color: #7b858b;
        }

        .empty-state i {
            font-size: 38px;
            margin-bottom: 10px;
            color: #aeb9be;
        }

        .empty-state p {
            margin: 0;
            font-size: 13px;
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 991px) {

            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
            }

        }

        @media (max-width: 768px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                min-height: auto;
            }

            .main {
                margin-left: 0;
            }

            .topbar {
                height: auto;
                padding: 18px 20px;
                gap: 15px;
            }

            .topbar h1 {
                font-size: 19px;
            }

            .content {
                padding: 20px;
            }

        }

        @media (max-width: 576px) {

            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .admin-info {
                width: 100%;
            }

        }

    </style>

</head>

<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <!-- ADMINISTRATION -->
    <div class="sidebar-title">
        Administration
    </div>

    <a href="dashboard.php" class="active">
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

    <a href="simulation_results.php">
        <i class="bi bi-graph-up"></i>
        <span>Simulation Results</span>
    </a>

    <a href="register.php">
        <i class="bi bi-person-plus"></i>
        <span>Register Admin</span>
    </a>


    <!-- DIVIDER -->
    <div class="nav-section-divider"></div>


    <!-- ACCOUNT -->
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


<!-- =========================
     MAIN
========================= -->

<div class="main">


    <!-- TOP BAR -->
    <div class="topbar">

        <h1>
            Admin Dashboard
        </h1>

        <div class="admin-info">

            <div class="admin-avatar">

                <?php

                echo strtoupper(
                    substr(
                        htmlspecialchars($current_user['full_name']),
                        0,
                        1
                    )
                );

                ?>

            </div>

            <div class="admin-details">

                <div class="admin-name">
                    <?= htmlspecialchars($current_user['full_name']) ?>
                </div>

                <div class="admin-role">
                    <?= htmlspecialchars($current_user['role']) ?>
                </div>

            </div>

        </div>

    </div>


    <!-- CONTENT -->
    <div class="content">


        <!-- WELCOME -->
        <div class="welcome-box">

            <h2>
                Welcome,
                <?= htmlspecialchars($current_user['full_name']) ?>
            </h2>

            <p>
                Manage students, practical submissions, simulations,
                and administration of the Food Process Practical
                Learning and Simulation System.
            </p>

        </div>


        <!-- =========================
             STATISTICS
        ========================= -->

        <div class="row g-4 mb-4">


            <!-- STUDENTS -->
            <div class="col-md-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>

                    <h3>
                        <?= $total_students ?>
                    </h3>

                    <p>
                        Total Students
                    </p>

                </div>

            </div>


            <!-- SUBMISSIONS -->
            <div class="col-md-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-file-earmark-check"></i>
                    </div>

                    <h3>
                        <?= $total_submissions ?>
                    </h3>

                    <p>
                        Total Submissions
                    </p>

                </div>

            </div>


            <!-- SIMULATIONS -->
            <div class="col-md-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>

                    <h3>
                        <?= $total_simulations ?>
                    </h3>

                    <p>
                        Total Simulations
                    </p>

                </div>

            </div>


            <!-- COMPLETED -->
            <div class="col-md-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>

                    <h3>
                        <?= $completed_practicals ?>
                    </h3>

                    <p>
                        Completed Practicals
                    </p>

                </div>

            </div>

        </div>


        <!-- =========================
             QUICK ACTIONS
             + PRACTICAL SUBMISSIONS
        ========================= -->

        <div class="row g-4 mb-4">


            <!-- QUICK ACTIONS -->
            <div class="col-lg-5">

                <div class="section-card">

                    <div class="section-header">

                        <h3>
                            Quick Actions
                        </h3>

                    </div>


                    <div class="section-body">


                        <!-- MANAGE STUDENTS -->
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


                        <!-- REVIEW SUBMISSIONS -->
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


                        <!-- SIMULATION RESULTS -->
                        <a
                            href="simulation_results.php"
                            class="quick-action"
                        >

                            <div class="quick-action-icon">

                                <i class="bi bi-bar-chart"></i>

                            </div>

                            <div>

                                <strong>
                                    Simulation Results
                                </strong>

                                <small>
                                    Review student simulation results
                                </small>

                            </div>

                        </a>


                        <!-- REGISTER ADMIN -->
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


            <!-- PRACTICAL SUBMISSIONS -->
            <div class="col-lg-7">

                <div class="section-card">


                    <div class="section-header">

                        <h3>
                            Practical Submissions
                        </h3>

                        <span class="text-muted small">
                            Overview
                        </span>

                    </div>


                    <div class="section-body">


                        <?php for ($i = 1; $i <= 4; $i++): ?>

                            <div class="practical-card">


                                <div class="practical-title">

                                    <strong>
                                        Practical <?= $i ?>
                                    </strong>

                                    <span class="practical-count">

                                        <?= $practical_counts[$i] ?>

                                        submission(s)

                                    </span>

                                </div>


                                <div
                                    class="progress"
                                    style="height: 7px;"
                                >

                                    <?php

                                    $percentage = $total_students > 0
                                        ? min(
                                            100,
                                            (
                                                $practical_counts[$i]
                                                / $total_students
                                            ) * 100
                                        )
                                        : 0;

                                    ?>

                                    <div
                                        class="progress-bar"
                                        role="progressbar"
                                        style="
                                            width: <?= $percentage ?>%;
                                            background:#1f5f75;
                                        "
                                    ></div>

                                </div>


                            </div>

                        <?php endfor; ?>


                    </div>

                </div>

            </div>

        </div>


        <!-- =========================
             RECENT SUBMISSIONS
        ========================= -->

        <div class="section-card">


            <div class="section-header">

                <h3>
                    Recent Submissions
                </h3>

                <a
                    href="submissions.php"
                    class="btn btn-sm btn-outline-secondary"
                >
                    View All
                </a>

            </div>


            <div class="table-responsive">


                <?php if (!empty($recent_submissions)): ?>


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
                                    Submitted
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($recent_submissions as $submission): ?>


                            <tr>


                                <td>
                                    <?= htmlspecialchars(
                                        $submission['full_name']
                                    ) ?>
                                </td>


                                <td>

                                    <span class="badge-practical">

                                        Practical
                                        <?= (int)$submission['practical_number'] ?>

                                    </span>

                                </td>


                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $submission['submitted_at']
                                        )
                                    ) {

                                        echo date(
                                            "d M Y, H:i",
                                            strtotime(
                                                $submission['submitted_at']
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
                                        class="btn btn-sm btn-outline-primary"
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

                        <p>
                            No practical submissions have been recorded yet.
                        </p>

                    </div>


                <?php endif; ?>


            </div>

        </div>


    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>