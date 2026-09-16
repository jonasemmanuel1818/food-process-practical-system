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
   SEARCH STUDENTS
========================= */
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

    $like = "%" . $search . "%";
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

/* =========================
   TOTAL STUDENTS
========================= */
$total_students = $students ? $students->num_rows : 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Students | Food Process System</title>

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
    --lab-light: #eef5f7;
    --border: #d9dee3;
    --background: #f4f6f8;
    --text: #27343b;
    --muted: #6c757d;
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
   PAGE
========================= */

.page {
    padding: 30px;
}

/* =========================
   PAGE INTRO
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
   SUMMARY CARD
========================= */

.summary-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 9px;
    padding: 20px 22px;
    margin-bottom: 20px;
}

.summary-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.summary-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.summary-icon {
    width: 48px;
    height: 48px;
    background: var(--lab-light);
    color: var(--lab-blue);
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
}

.summary-label {
    color: var(--muted);
    font-size: 12px;
    margin-bottom: 3px;
}

.summary-number {
    font-size: 25px;
    font-weight: 700;
    color: var(--lab-dark);
}

/* =========================
   MAIN CARD
========================= */

.students-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 9px;
    overflow: hidden;
}

.card-header-custom {
    padding: 20px 22px;
    border-bottom: 1px solid var(--border);
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
   SEARCH
========================= */

.search-section {
    padding: 20px 22px;
    background: #fafbfc;
    border-bottom: 1px solid var(--border);
}

.search-section .form-control {
    height: 43px;
    border-color: var(--border);
    font-size: 13px;
    border-radius: 7px;
}

.search-section .form-control:focus {
    border-color: var(--lab-blue);
    box-shadow: 0 0 0 0.15rem rgba(31,95,117,0.12);
}

.search-section .btn {
    height: 43px;
    border-radius: 7px;
    font-size: 13px;
}

.btn-primary {
    background: var(--lab-blue);
    border-color: var(--lab-blue);
}

.btn-primary:hover {
    background: var(--lab-dark);
    border-color: var(--lab-dark);
}

/* =========================
   TABLE
========================= */

.table-wrapper {
    overflow-x: auto;
}

.students-table {
    margin: 0;
    min-width: 900px;
}

.students-table thead th {
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

.students-table tbody td {
    padding: 15px 16px;
    font-size: 13px;
    border-bottom: 1px solid #edf0f2;
    vertical-align: middle;
}

.students-table tbody tr:last-child td {
    border-bottom: none;
}

.students-table tbody tr:hover {
    background: #fafcfd;
}

.student-name {
    color: var(--lab-dark);
    font-weight: 700;
}

.username {
    color: #5e6b72;
}

.joined {
    color: #6c757d;
    white-space: nowrap;
}

/* =========================
   PROGRESS BADGES
========================= */

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 8px;
    border-radius: 5px;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.badge-complete {
    background: #e8f5ee;
    color: #18794e;
}

.badge-progress {
    background: #fff4d6;
    color: #8a6500;
}

.badge-not-started {
    background: #f1f3f5;
    color: #6c757d;
}

/* =========================
   VIEW BUTTON
========================= */

.view-btn {
    font-size: 12px;
    border-radius: 6px;
    padding: 6px 11px;
    white-space: nowrap;
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
   MOBILE MENU
========================= */

.mobile-menu-btn {
    display: none;
    border: none;
    background: transparent;
    color: var(--lab-dark);
    font-size: 23px;
}

.sidebar-overlay {
    display: none;
}

/* =========================
   RESPONSIVE
========================= */

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
        margin-right: 10px;
    }

}

@media (max-width: 700px) {

    .topbar {
        padding: 0 18px;
        height: 70px;
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

    .summary-card {
        padding: 17px;
    }

    .summary-content {
        align-items: flex-start;
    }

    .page-intro h2 {
        font-size: 20px;
    }

    .card-header-custom,
    .search-section {
        padding: 17px;
    }

}

@media (max-width: 480px) {

    .topbar-actions {
        gap: 5px;
    }

    .summary-number {
        font-size: 22px;
    }

    .summary-icon {
        width: 43px;
        height: 43px;
        font-size: 19px;
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
            <strong>Food Process System</strong>
            <span>Administration Panel</span>
        </div>

    </div>


    <div class="sidebar-title">
        ADMINISTRATION
    </div>

    <ul class="sidebar-menu">

        <li>
            <a href="dashboard.php">
                <i class="bi bi-grid-1x2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li>
            <a href="students.php" class="active">
                <i class="bi bi-people"></i>
                <span>Students</span>
            </a>
        </li>

        <li>
            <a href="submissions.php">
                <i class="bi bi-file-earmark-text"></i>
                <span>Submissions</span>
            </a>
        </li>

        <li>
            <a href="simulation_results.php">
                <i class="bi bi-bar-chart-line"></i>
                <span>Simulation Results</span>
            </a>
        </li>

        <li>
            <a href="register.php">
                <i class="bi bi-person-plus"></i>
                <span>Register Admin</span>
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
                <span>Settings</span>
            </a>
        </li>

        <li>
            <a href="../logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span>Log Out</span>
            </a>
        </li>

    </ul>

</aside>


<div class="sidebar-overlay" id="sidebarOverlay"></div>


<!-- =========================
     MAIN CONTENT
========================= -->

<div class="main-content">


    <!-- TOPBAR -->

    <header class="topbar">

        <div class="d-flex align-items-center">

            <button
                class="mobile-menu-btn"
                id="mobileMenuBtn"
                type="button"
                aria-label="Open menu">

                <i class="bi bi-list"></i>

            </button>

            <div class="page-title">

                <h1>Students</h1>

                <p>
                    Registered students and practical progress
                </p>

            </div>

        </div>


        <div class="topbar-actions">

            <a
                href="../dashboard.php"
                class="btn btn-outline-primary">

                <i class="bi bi-mortarboard"></i>

                <span>Student View</span>

            </a>

        </div>

    </header>


    <!-- PAGE -->

    <main class="page">


        <!-- INTRO -->

        <div class="page-intro">

            <h2>
                <i class="bi bi-people me-2"></i>
                Student Management
            </h2>

            <p>
                View registered students and monitor their practical progress.
            </p>

        </div>


        <!-- SUMMARY -->

        <div class="summary-card">

            <div class="summary-content">

                <div class="summary-left">

                    <div class="summary-icon">
                        <i class="bi bi-person-check"></i>
                    </div>

                    <div>

                        <div class="summary-label">
                            REGISTERED STUDENTS
                        </div>

                        <div class="summary-number">
                            <?= $total_students ?>
                        </div>

                    </div>

                </div>

                <?php if ($search !== ''): ?>

                    <div class="small text-muted">
                        Search results for:
                        <strong>
                            <?= htmlspecialchars($search) ?>
                        </strong>
                    </div>

                <?php endif; ?>

            </div>

        </div>


        <!-- STUDENTS CARD -->

        <section class="students-card">


            <div class="card-header-custom">

                <h3>
                    Student List
                </h3>

                <p>
                    Practical completion status for each student
                </p>

            </div>


            <!-- SEARCH -->

            <div class="search-section">

                <form method="GET" class="row g-2">

                    <div class="col-md-8">

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            placeholder="Search student name or username..."
                            value="<?= htmlspecialchars($search) ?>"
                            autocomplete="off">

                    </div>

                    <div class="col-md-2">

                        <button
                            type="submit"
                            class="btn btn-primary w-100">

                            <i class="bi bi-search me-1"></i>
                            Search

                        </button>

                    </div>

                    <div class="col-md-2">

                        <a
                            href="students.php"
                            class="btn btn-outline-secondary w-100">

                            <i class="bi bi-x-lg me-1"></i>
                            Clear

                        </a>

                    </div>

                </form>

            </div>


            <!-- TABLE -->

            <div class="table-wrapper">

                <table class="table students-table align-middle">

                    <thead>

                        <tr>

                            <th>Student</th>

                            <th>Username</th>

                            <th>P1</th>

                            <th>P2</th>

                            <th>P3</th>

                            <th>P4</th>

                            <th>Joined</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if ($students && $students->num_rows > 0): ?>

                        <?php while ($student = $students->fetch_assoc()): ?>

                            <?php

                            /* =========================
                               GET PRACTICAL PROGRESS
                            ========================= */

                            $progress = [];

                            $progress_stmt = $conn->prepare("
                                SELECT practical_number, status
                                FROM practical_progress
                                WHERE user_id = ?
                            ");

                            $progress_stmt->bind_param(
                                "i",
                                $student['id']
                            );

                            $progress_stmt->execute();

                            $progress_result =
                                $progress_stmt->get_result();

                            while (
                                $progress_row =
                                $progress_result->fetch_assoc()
                            ) {

                                $progress[
                                    (int)$progress_row['practical_number']
                                ] = $progress_row['status'];

                            }

                            $progress_stmt->close();

                            ?>


                            <tr>


                                <!-- STUDENT -->

                                <td>

                                    <div class="student-name">

                                        <?= htmlspecialchars(
                                            $student['full_name']
                                        ) ?>

                                    </div>

                                </td>


                                <!-- USERNAME -->

                                <td>

                                    <span class="username">

                                        <?= htmlspecialchars(
                                            $student['username']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- PRACTICALS -->

                                <?php for ($p = 1; $p <= 4; $p++): ?>

                                    <td>

                                        <?php
                                        $status =
                                            $progress[$p] ?? 'not_started';
                                        ?>


                                        <?php if ($status === 'completed'): ?>

                                            <span class="status-badge badge-complete">

                                                <i class="bi bi-check-circle"></i>

                                                Completed

                                            </span>


                                        <?php elseif ($status === 'in_progress'): ?>

                                            <span class="status-badge badge-progress">

                                                <i class="bi bi-clock"></i>

                                                In Progress

                                            </span>


                                        <?php else: ?>

                                            <span class="status-badge badge-not-started">

                                                <i class="bi bi-dash-circle"></i>

                                                Not Started

                                            </span>

                                        <?php endif; ?>

                                    </td>

                                <?php endfor; ?>


                                <!-- JOINED -->

                                <td class="joined">

                                    <?= htmlspecialchars(
                                        date(
                                            'Y-m-d',
                                            strtotime(
                                                $student['created_at']
                                            )
                                        )
                                    ) ?>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="student_details.php?id=<?= (int)$student['id'] ?>"
                                        class="btn btn-sm btn-outline-primary view-btn">

                                        <i class="bi bi-eye me-1"></i>

                                        View

                                    </a>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>

                        <tr>

                            <td colspan="8">

                                <div class="empty-state">

                                    <i class="bi bi-person-x"></i>

                                    <strong>
                                        No students found
                                    </strong>

                                    <span>
                                        Try another name or username.
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

const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("sidebarOverlay");
const menuBtn = document.getElementById("mobileMenuBtn");

if (menuBtn) {

    menuBtn.addEventListener("click", function () {

        sidebar.classList.add("show");
        overlay.classList.add("show");

    });

}

if (overlay) {

    overlay.addEventListener("click", function () {

        sidebar.classList.remove("show");
        overlay.classList.remove("show");

    });

}

document.querySelectorAll(".sidebar-menu a").forEach(function(link) {

    link.addEventListener("click", function() {

        sidebar.classList.remove("show");
        overlay.classList.remove("show");

    });

});

</script>

</body>
</html>