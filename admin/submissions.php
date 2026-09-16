<?php

require_once "../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/* =========================
   CHECK ADMIN / LECTURER
========================= */

$stmt = $conn->prepare("
    SELECT role
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user_result = $stmt->get_result();
$role = $user_result->fetch_assoc()['role'] ?? '';
$stmt->close();

if (!in_array($role, ['admin', 'lecturer'], true)) {
    die("Access Denied");
}


/* =========================
   PRACTICAL FILTER
========================= */

$practical = isset($_GET['practical'])
    ? (int) $_GET['practical']
    : 0;


/* =========================
   GET SUBMISSIONS
========================= */

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


/* =========================
   COUNTS
========================= */

$total_submissions = 0;
$practical_counts = [
    1 => 0,
    2 => 0,
    3 => 0,
    4 => 0
];

/*
 * Count submissions separately so the main
 * result set can still be used normally.
 */

$count_result = $conn->query("
    SELECT practical_number, COUNT(*) AS total
    FROM practical_submissions ps
    INNER JOIN users u
        ON ps.user_id = u.id
    WHERE u.role = 'student'
    GROUP BY practical_number
");

if ($count_result) {

    while ($count = $count_result->fetch_assoc()) {

        $number = (int) $count['practical_number'];
        $total = (int) $count['total'];

        if (isset($practical_counts[$number])) {
            $practical_counts[$number] = $total;
        }

        $total_submissions += $total;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>Submissions | Food Process System</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet">

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet">


<style>

/* =========================
   ROOT
========================= */

:root {
    --lab-blue: #1f5f75;
    --lab-dark: #17495a;
    --lab-light: #eef5f7;
    --border: #d9dee3;
    --background: #f4f6f8;
    --text: #27343b;
    --muted: #6c757d;
}


/* =========================
   GLOBAL
========================= */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--background);
    color: var(--text);
    font-family: Arial, Helvetica, sans-serif;
}


/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 245px;
    height: 100vh;
    background: var(--lab-dark);
    color: #fff;
    padding: 25px 16px;
    z-index: 1000;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 10px 25px;
    border-bottom: 1px solid rgba(255,255,255,0.15);
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
    color: rgba(255,255,255,0.68);
    margin-top: 3px;
}

.sidebar-title {
    padding: 0 12px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    color: rgba(255,255,255,0.48);
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
    color: rgba(255,255,255,0.82);
    text-decoration: none;
    font-size: 14px;
    transition: 0.2s ease;
}

.sidebar-menu a:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
}

.sidebar-menu a.active {
    background: var(--lab-blue);
    color: #fff;
}

.sidebar-menu i {
    width: 19px;
    font-size: 17px;
}

.sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,0.12);
    margin: 22px 10px;
}


/* =========================
   MAIN CONTENT
========================= */

.main-content {
    margin-left: 245px;
    min-height: 100vh;
}


/* =========================
   TOPBAR
========================= */

.topbar {
    height: 76px;
    background: #fff;
    border-bottom: 1px solid var(--border);
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


/* =========================
   MOBILE MENU
========================= */

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


/* =========================
   PAGE
========================= */

.page {
    padding: 30px;
}


/* =========================
   INTRO
========================= */

.page-intro {
    margin-bottom: 22px;
}

.page-intro h2 {
    margin: 0;
    color: var(--lab-dark);
    font-size: 23px;
    font-weight: 700;
}

.page-intro p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 13px;
}


/* =========================
   SUMMARY
========================= */

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

.summary-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 9px;
    padding: 18px;
}

.summary-card-content {
    display: flex;
    align-items: center;
    gap: 13px;
}

.summary-icon {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: var(--lab-light);
    color: var(--lab-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    flex-shrink: 0;
}

.summary-label {
    color: var(--muted);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 3px;
}

.summary-number {
    color: var(--lab-dark);
    font-size: 22px;
    font-weight: 700;
}


/* =========================
   SUBMISSION CARD
========================= */

.submissions-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 9px;
    overflow: hidden;
}

.card-header-custom {
    padding: 20px 22px;
    border-bottom: 1px solid var(--border);
}

.card-header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.card-header-custom h3 {
    margin: 0;
    color: var(--lab-dark);
    font-size: 17px;
    font-weight: 700;
}

.card-header-custom p {
    margin: 4px 0 0;
    color: var(--muted);
    font-size: 12px;
}


/* =========================
   FILTERS
========================= */

.filter-section {
    padding: 18px 22px;
    background: #fafbfc;
    border-bottom: 1px solid var(--border);
}

.filter-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.filter-buttons .btn {
    font-size: 12px;
    border-radius: 6px;
    padding: 7px 12px;
}

.btn-primary {
    background: var(--lab-blue);
    border-color: var(--lab-blue);
}

.btn-primary:hover {
    background: var(--lab-dark);
    border-color: var(--lab-dark);
}

.btn-outline-primary {
    color: var(--lab-blue);
    border-color: var(--lab-blue);
}

.btn-outline-primary:hover {
    background: var(--lab-blue);
    border-color: var(--lab-blue);
    color: #fff;
}


/* =========================
   TABLE
========================= */

.table-wrapper {
    overflow-x: auto;
}

.submissions-table {
    min-width: 1000px;
    margin: 0;
}

.submissions-table thead th {
    background: #f7f9fa;
    color: #5e6b72;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 700;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}

.submissions-table tbody td {
    padding: 15px 16px;
    font-size: 13px;
    border-bottom: 1px solid #edf0f2;
    vertical-align: middle;
}

.submissions-table tbody tr:last-child td {
    border-bottom: none;
}

.submissions-table tbody tr:hover {
    background: #fafcfd;
}


/* =========================
   STUDENT
========================= */

.student-name {
    color: var(--lab-dark);
    font-weight: 700;
}

.username {
    color: #5e6b72;
}


/* =========================
   PRACTICAL BADGE
========================= */

.practical-badge {
    display: inline-flex;
    align-items: center;
    padding: 5px 9px;
    border-radius: 5px;
    background: var(--lab-light);
    color: var(--lab-blue);
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}


/* =========================
   SUBMISSION DATE
========================= */

.submitted-date {
    color: #5e6b72;
    font-size: 12px;
    white-space: nowrap;
}


/* =========================
   RESULTS PREVIEW
========================= */

.submission-preview {
    max-width: 330px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #5e6b72;
    font-size: 12px;
}


/* =========================
   VIEW BUTTON
========================= */

.view-btn {
    font-size: 11px;
    border-radius: 6px;
    padding: 6px 10px;
    white-space: nowrap;
}


/* =========================
   EMPTY STATE
========================= */

.empty-state {
    text-align: center;
    padding: 55px 20px;
    color: var(--muted);
}

.empty-state i {
    display: block;
    font-size: 38px;
    color: #b7c1c6;
    margin-bottom: 12px;
}

.empty-state strong {
    display: block;
    color: #5e6b72;
    font-size: 14px;
    margin-bottom: 5px;
}

.empty-state span {
    font-size: 12px;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {

    .summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 1000px) {

    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.25s ease;
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .sidebar-overlay.show {
        display: block;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.35);
        z-index: 999;
    }

    .main-content {
        margin-left: 0;
    }

    .mobile-menu-btn {
        display: inline-block;
    }

}

@media (max-width: 700px) {

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

    .page-intro h2 {
        font-size: 20px;
    }

    .summary-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .summary-card {
        padding: 14px;
    }

    .summary-card-content {
        gap: 10px;
    }

    .summary-icon {
        width: 39px;
        height: 39px;
        font-size: 17px;
    }

    .summary-number {
        font-size: 19px;
    }

    .card-header-custom,
    .filter-section {
        padding: 17px;
    }

}

@media (max-width: 480px) {

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .card-header-top {
        align-items: flex-start;
        flex-direction: column;
    }

}

</style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar" id="sidebar">

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
            <a href="students.php">

                <i class="bi bi-people"></i>

                <span>
                    Students
                </span>

            </a>
        </li>


        <li>
            <a href="submissions.php" class="active">

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


    <div class="sidebar-divider"></div>


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


<div
    class="sidebar-overlay"
    id="sidebarOverlay">
</div>


<!-- =========================
     MAIN CONTENT
========================= -->

<div class="main-content">


    <!-- TOPBAR -->

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
                    Submissions
                </h1>

                <p>
                    Review student practical work
                </p>

            </div>

        </div>


        <div class="topbar-actions">

            <a
                href="../dashboard.php"
                class="btn btn-outline-primary">

                <i class="bi bi-mortarboard"></i>

                <span>
                    Student View
                </span>

            </a>

        </div>

    </header>


    <!-- PAGE -->

    <main class="page">


        <!-- INTRO -->

        <div class="page-intro">

            <h2>

                <i class="bi bi-file-earmark-text me-2"></i>

                Practical Submissions

            </h2>

            <p>
                Review and access practical results submitted by students.
            </p>

        </div>


        <!-- SUMMARY CARDS -->

        <div class="summary-grid">


            <!-- TOTAL -->

            <div class="summary-card">

                <div class="summary-card-content">

                    <div class="summary-icon">
                        <i class="bi bi-files"></i>
                    </div>

                    <div>

                        <div class="summary-label">
                            Total Submissions
                        </div>

                        <div class="summary-number">
                            <?= $total_submissions ?>
                        </div>

                    </div>

                </div>

            </div>


            <!-- P1 -->

            <div class="summary-card">

                <div class="summary-card-content">

                    <div class="summary-icon">
                        <i class="bi bi-1-circle"></i>
                    </div>

                    <div>

                        <div class="summary-label">
                            Practical 1
                        </div>

                        <div class="summary-number">
                            <?= $practical_counts[1] ?>
                        </div>

                    </div>

                </div>

            </div>


            <!-- P2 -->

            <div class="summary-card">

                <div class="summary-card-content">

                    <div class="summary-icon">
                        <i class="bi bi-2-circle"></i>
                    </div>

                    <div>

                        <div class="summary-label">
                            Practical 2
                        </div>

                        <div class="summary-number">
                            <?= $practical_counts[2] ?>
                        </div>

                    </div>

                </div>

            </div>


            <!-- P3/P4 -->

            <div class="summary-card">

                <div class="summary-card-content">

                    <div class="summary-icon">
                        <i class="bi bi-activity"></i>
                    </div>

                    <div>

                        <div class="summary-label">
                            P3 + P4
                        </div>

                        <div class="summary-number">
                            <?= $practical_counts[3] + $practical_counts[4] ?>
                        </div>

                    </div>

                </div>

            </div>


        </div>


        <!-- SUBMISSIONS -->

        <section class="submissions-card">


            <!-- HEADER -->

            <div class="card-header-custom">

                <div class="card-header-top">

                    <div>

                        <h3>
                            Submitted Practical Work
                        </h3>

                        <p>
                            Select a practical to filter the submissions.
                        </p>

                    </div>

                </div>

            </div>


            <!-- FILTERS -->

            <div class="filter-section">

                <div class="filter-buttons">


                    <a
                        href="submissions.php"
                        class="btn <?= $practical === 0
                            ? 'btn-primary'
                            : 'btn-outline-primary' ?>">

                        <i class="bi bi-grid me-1"></i>

                        All

                    </a>


                    <?php for ($i = 1; $i <= 4; $i++): ?>

                        <a
                            href="submissions.php?practical=<?= $i ?>"
                            class="btn <?= $practical === $i
                                ? 'btn-primary'
                                : 'btn-outline-primary' ?>">

                            Practical <?= $i ?>

                        </a>

                    <?php endfor; ?>


                </div>

            </div>


            <!-- TABLE -->

            <div class="table-wrapper">

                <table class="table submissions-table align-middle">

                    <thead>

                        <tr>

                            <th>
                                Student
                            </th>

                            <th>
                                Username
                            </th>

                            <th>
                                Practical
                            </th>

                            <th>
                                Submitted
                            </th>

                            <th>
                                Results
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if ($submissions && $submissions->num_rows > 0): ?>


                        <?php while ($row = $submissions->fetch_assoc()): ?>


                            <tr>


                                <!-- STUDENT -->

                                <td>

                                    <div class="student-name">

                                        <?= htmlspecialchars(
                                            $row['full_name']
                                        ) ?>

                                    </div>

                                </td>


                                <!-- USERNAME -->

                                <td>

                                    <span class="username">

                                        <?= htmlspecialchars(
                                            $row['username']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- PRACTICAL -->

                                <td>

                                    <span class="practical-badge">

                                        <i class="bi bi-flask me-1"></i>

                                        Practical
                                        <?= (int) $row['practical_number'] ?>

                                    </span>

                                </td>


                                <!-- SUBMITTED -->

                                <td>

                                    <span class="submitted-date">

                                        <?= htmlspecialchars(
                                            $row['submitted_at'] ?? '—'
                                        ) ?>

                                    </span>

                                </td>


                                <!-- RESULTS -->

                                <td>

                                    <div
                                        class="submission-preview"
                                        title="<?= htmlspecialchars(
                                            $row['results'] ?? ''
                                        ) ?>">

                                        <?= htmlspecialchars(
                                            $row['results'] ?? 'No results provided'
                                        ) ?>

                                    </div>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="student_details.php?id=<?= (int) $row['user_id'] ?>"
                                        class="btn btn-sm btn-outline-primary view-btn">

                                        <i class="bi bi-eye me-1"></i>

                                        View Student

                                    </a>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td colspan="6">

                                <div class="empty-state">

                                    <i class="bi bi-file-earmark-x"></i>

                                    <strong>
                                        No submissions found
                                    </strong>

                                    <span>

                                        <?php if ($practical >= 1 && $practical <= 4): ?>

                                            No submissions have been recorded
                                            for Practical <?= $practical ?> yet.

                                        <?php else: ?>

                                            No student practical submissions
                                            have been recorded yet.

                                        <?php endif; ?>

                                    </span>

                                </div>

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>


        </section>


    </main>

</div>


<script>

/* =========================
   MOBILE SIDEBAR
========================= */

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