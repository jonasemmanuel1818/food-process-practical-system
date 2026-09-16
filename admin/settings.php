<?php

require_once "../config.php";

/* =========================================================
   PROTECT ADMIN SETTINGS
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   GET CURRENT USER
========================================================= */

$stmt = $conn->prepare("
    SELECT full_name, username, password, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Database query failed.");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   CHECK USER
========================================================= */

if (!$user) {

    session_destroy();

    header("Location: ../index.php");
    exit();
}


/* =========================================================
   CURRENT USER INFORMATION
========================================================= */

$current_full_name = $user['full_name'];
$current_username = $user['username'];
$current_password_hash = $user['password'];
$current_role = $user['role'];


/* =========================================================
   ONLY ADMIN AND LECTURER
========================================================= */

if (!in_array($current_role, ['admin', 'lecturer'], true)) {
    die("Access Denied");
}


/* =========================================================
   VARIABLES
========================================================= */

$success = "";
$error = "";


/* =========================================================
   SYSTEM INFORMATION
========================================================= */

$total_students = 0;
$total_practicals = 4;
$total_submissions = 0;
$total_simulations = 0;


/* =========================================================
   TOTAL STUDENTS
========================================================= */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM users
    WHERE role = 'student'
");

if ($stmt) {

    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $total_students = (int) $row['total'];
    }

    $stmt->close();
}


/* =========================================================
   TOTAL SUBMISSIONS
========================================================= */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM practical_submissions
");

if ($stmt) {

    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $total_submissions = (int) $row['total'];
    }

    $stmt->close();
}


/* =========================================================
   TOTAL SIMULATIONS
========================================================= */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM simulation_results
");

if ($stmt) {

    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $total_simulations = (int) $row['total'];
    }

    $stmt->close();
}


/* =========================================================
   HANDLE SETTINGS UPDATE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST['full_name'] ?? "");
    $username = trim($_POST['username'] ?? "");

    $current_password = $_POST['current_password'] ?? "";
    $new_password = $_POST['new_password'] ?? "";
    $confirm_password = $_POST['confirm_password'] ?? "";


    /* =====================================================
       BASIC VALIDATION
    ===================================================== */

    if ($full_name === "" || $username === "") {

        $error = "Full name and username are required.";

    } elseif (strlen($full_name) < 2) {

        $error = "Please enter a valid full name.";

    } elseif (strlen($username) < 3) {

        $error = "Username must contain at least 3 characters.";

    } else {


        /* =================================================
           CHECK USERNAME
        ================================================= */

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            AND id != ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param(
                "si",
                $username,
                $user_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->fetch_assoc()) {

                $error = "That username is already being used.";
            }

            $stmt->close();
        }


        /* =================================================
           PASSWORD VALIDATION
        ================================================= */

        if (
            $error === "" &&
            (
                $current_password !== "" ||
                $new_password !== "" ||
                $confirm_password !== ""
            )
        ) {

            if ($current_password === "") {

                $error = "Enter your current password.";

            } elseif (
                !password_verify(
                    $current_password,
                    $current_password_hash
                )
            ) {

                $error = "Current password is incorrect.";

            } elseif ($new_password === "") {

                $error = "Enter a new password.";

            } elseif (strlen($new_password) < 6) {

                $error = "New password must contain at least 6 characters.";

            } elseif ($new_password !== $confirm_password) {

                $error = "New password and confirmation password do not match.";
            }
        }


        /* =================================================
           UPDATE ACCOUNT
        ================================================= */

        if ($error === "") {


            /* ---------------------------------------------
               UPDATE WITH NEW PASSWORD
            --------------------------------------------- */

            if (
                $current_password !== "" &&
                $new_password !== ""
            ) {

                $new_password_hash = password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );


                $stmt = $conn->prepare("
                    UPDATE users
                    SET
                        full_name = ?,
                        username = ?,
                        password = ?
                    WHERE id = ?
                ");

                if ($stmt) {

                    $stmt->bind_param(
                        "sssi",
                        $full_name,
                        $username,
                        $new_password_hash,
                        $user_id
                    );


                    if ($stmt->execute()) {

                        $success =
                            "Your account settings have been updated successfully.";

                    } else {

                        $error =
                            "Unable to update your account settings.";
                    }

                    $stmt->close();
                }


            /* ---------------------------------------------
               UPDATE WITHOUT PASSWORD
            --------------------------------------------- */

            } else {

                $stmt = $conn->prepare("
                    UPDATE users
                    SET
                        full_name = ?,
                        username = ?
                    WHERE id = ?
                ");

                if ($stmt) {

                    $stmt->bind_param(
                        "ssi",
                        $full_name,
                        $username,
                        $user_id
                    );


                    if ($stmt->execute()) {

                        $success =
                            "Your account information has been updated successfully.";

                    } else {

                        $error =
                            "Unable to update your account information.";
                    }

                    $stmt->close();
                }
            }


            /* =================================================
               UPDATE SESSION
            ================================================= */

            if ($success !== "") {

                $_SESSION['name'] = $full_name;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $current_role;

                $current_full_name = $full_name;
                $current_username = $username;

                $_POST['current_password'] = "";
                $_POST['new_password'] = "";
                $_POST['confirm_password'] = "";
            }
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

<title>
    Settings | Food Process System
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
    font-family: Arial, Helvetica, sans-serif;
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
    font-size: 14px;
    transition: 0.2s ease;
}

.sidebar-menu a:hover {
    background: rgba(255,255,255,0.08);
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
    background: rgba(255,255,255,0.12);
    margin: 22px 10px;
}


/* =========================================================
   MAIN CONTENT
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
   PAGE INTRO
========================================================= */

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


/* =========================================================
   ALERTS
========================================================= */

.alert {
    border-radius: 7px;
    font-size: 12px;
    border-width: 1px;
}

.alert-success {
    color: #18794e;
    background: #e8f5ee;
    border-color: #c8e8d7;
}

.alert-danger {
    color: #9b2c2c;
    background: #fcecec;
    border-color: #f0cccc;
}


/* =========================================================
   SETTINGS LAYOUT
========================================================= */

.settings-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 310px;
    gap: 20px;
    align-items: start;
}


/* =========================================================
   SETTINGS CARD
========================================================= */

.settings-card {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 9px;
    overflow: hidden;
}

.settings-header {
    padding: 20px 22px;
    border-bottom: 1px solid var(--border);
}

.settings-header-content {
    display: flex;
    align-items: center;
    gap: 14px;
}

.settings-icon {
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

.settings-header h3 {
    margin: 0;
    color: var(--lab-dark);
    font-size: 17px;
    font-weight: 700;
}

.settings-header p {
    margin: 4px 0 0;
    color: var(--muted);
    font-size: 12px;
}

.settings-body {
    padding: 22px;
}


/* =========================================================
   FORM
========================================================= */

.form-label {
    color: #3d4a50;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 7px;
}

.form-control {
    height: 45px;
    border: 1px solid var(--border);
    border-radius: 7px;
    font-size: 13px;
}

.form-control:focus {
    border-color: var(--lab-blue);
    box-shadow: 0 0 0 0.15rem rgba(31,95,117,0.12);
}

.form-text {
    font-size: 11px;
    color: var(--muted);
}


/* =========================================================
   ROLE BADGE
========================================================= */

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 10px;
    background: var(--lab-light);
    color: var(--lab-blue);
    border: 1px solid #cbdfe5;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
}


/* =========================================================
   DIVIDER
========================================================= */

.section-divider {
    border: 0;
    border-top: 1px solid var(--border);
    margin: 25px 0;
}


/* =========================================================
   PASSWORD SECTION
========================================================= */

.password-title {
    color: var(--lab-dark);
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 5px;
}

.password-description {
    color: var(--muted);
    font-size: 11px;
    margin-bottom: 18px;
}


/* =========================================================
   INFO BOX
========================================================= */

.info-box {
    background: #f7f9fa;
    border: 1px solid #e1e5e8;
    border-radius: 7px;
    padding: 13px 14px;
    color: #64727a;
    font-size: 11px;
    line-height: 1.55;
}

.info-box i {
    color: var(--lab-blue);
    margin-right: 6px;
}


/* =========================================================
   SAVE BUTTON
========================================================= */

.btn-save {
    height: 44px;
    background: var(--lab-blue);
    border: 1px solid var(--lab-blue);
    color: #ffffff;
    border-radius: 7px;
    padding: 0 18px;
    font-size: 13px;
    font-weight: 700;
}

.btn-save:hover {
    background: var(--lab-dark);
    border-color: var(--lab-dark);
    color: #ffffff;
}


/* =========================================================
   INFORMATION CARD
========================================================= */

.info-card {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 9px;
    overflow: hidden;
}

.info-card-header {
    padding: 18px;
    border-bottom: 1px solid var(--border);
}

.info-card-header h3 {
    margin: 0;
    color: var(--lab-dark);
    font-size: 15px;
    font-weight: 700;
}

.info-card-body {
    padding: 18px;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    padding: 12px 0;
    border-bottom: 1px solid #edf0f2;
}

.info-item:first-child {
    padding-top: 0;
}

.info-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.info-item i {
    color: var(--lab-blue);
    font-size: 17px;
    margin-top: 1px;
}

.info-item strong {
    display: block;
    color: #3d4a50;
    font-size: 12px;
    margin-bottom: 3px;
}

.info-item span {
    display: block;
    color: var(--muted);
    font-size: 11px;
    line-height: 1.45;
}


/* =========================================================
   SYSTEM INFORMATION
========================================================= */

.system-card {
    margin-top: 20px;
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 9px;
    overflow: hidden;
}

.system-header {
    padding: 20px 22px;
    border-bottom: 1px solid var(--border);
}

.system-header-content {
    display: flex;
    align-items: center;
    gap: 14px;
}

.system-icon {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    background: var(--lab-light);
    color: var(--lab-blue);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
}

.system-header h3 {
    margin: 0;
    color: var(--lab-dark);
    font-size: 17px;
    font-weight: 700;
}

.system-header p {
    margin: 4px 0 0;
    color: var(--muted);
    font-size: 12px;
}

.system-body {
    padding: 22px;
}


/* =========================================================
   STAT GRID
========================================================= */

.stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}


/* =========================================================
   STAT CARD
========================================================= */

.stat-card {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 17px;
}

.stat-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.stat-icon {
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

.stat-label {
    color: var(--muted);
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 3px;
}

.stat-number {
    color: var(--lab-dark);
    font-size: 21px;
    font-weight: 700;
}


/* =========================================================
   SYSTEM NOTE
========================================================= */

.system-note {
    background: #f7f9fa;
    border: 1px solid #e1e5e8;
    border-radius: 7px;
    padding: 14px;
    color: #64727a;
    font-size: 11px;
    line-height: 1.55;
}

.system-note i {
    color: var(--lab-blue);
    margin-right: 6px;
}


/* =========================================================
   STUDENT VIEW BUTTON
========================================================= */

.btn-student-view {
    color: var(--lab-blue);
    border: 1px solid var(--lab-blue);
    background: #ffffff;
}

.btn-student-view:hover {
    background: var(--lab-blue);
    border-color: var(--lab-blue);
    color: #ffffff;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

    .settings-layout {
        grid-template-columns: 1fr;
    }

    .stat-grid {
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

    .settings-header,
    .settings-body,
    .system-header,
    .system-body,
    .info-card-header,
    .info-card-body {
        padding: 17px;
    }

    .stat-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
}


@media (max-width: 480px) {

    .stat-grid {
        grid-template-columns: 1fr;
    }

    .settings-header-content,
    .system-header-content {
        align-items: flex-start;
    }

    .topbar-actions {
        gap: 5px;
    }

}

</style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar" id="sidebar">


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

            <a href="students.php">

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

            <a
                href="settings.php"
                class="active">

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
     SIDEBAR OVERLAY
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


            <!-- MOBILE MENU -->

            <button
                type="button"
                class="mobile-menu-btn"
                id="mobileMenuBtn"
                aria-label="Open menu">

                <i class="bi bi-list"></i>

            </button>


            <!-- PAGE TITLE -->

            <div class="page-title">

                <h1>
                    Settings
                </h1>

                <p>
                    Manage your administrator account
                </p>

            </div>


        </div>


        <!-- TOPBAR ACTIONS -->

        <div class="topbar-actions">


            <a
                href="../dashboard.php"
                class="btn btn-student-view">

                <i class="bi bi-mortarboard"></i>

                <span>
                    Student View
                </span>

            </a>


        </div>


    </header>


    <!-- =====================================================
         PAGE CONTENT
    ===================================================== -->

    <main class="page">


        <!-- PAGE INTRO -->

        <div class="page-intro">

            <h2>

                <i class="bi bi-gear me-2"></i>

                Account Settings

            </h2>

            <p>
                Update your administrator account and view system information.
            </p>

        </div>


        <!-- =================================================
             SUCCESS MESSAGE
        ================================================= -->

        <?php if ($success !== ""): ?>

            <div
                class="alert alert-success"
                role="alert">

                <i class="bi bi-check-circle me-2"></i>

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             ERROR MESSAGE
        ================================================= -->

        <?php if ($error !== ""): ?>

            <div
                class="alert alert-danger"
                role="alert">

                <i class="bi bi-exclamation-circle me-2"></i>

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             SETTINGS LAYOUT
        ================================================= -->

        <div class="settings-layout">


            <!-- =================================================
                 ACCOUNT SETTINGS CARD
            ================================================= -->

            <section class="settings-card">


                <!-- HEADER -->

                <div class="settings-header">


                    <div class="settings-header-content">


                        <div class="settings-icon">

                            <i class="bi bi-person-gear"></i>

                        </div>


                        <div>

                            <h3>
                                Administrator Account
                            </h3>

                            <p>
                                Update your account information and password.
                            </p>

                        </div>


                    </div>


                </div>


                <!-- BODY -->

                <div class="settings-body">


                    <form
                        method="POST"
                        action="settings.php">


                        <!-- =================================================
                             BASIC ACCOUNT INFORMATION
                        ================================================= -->

                        <div class="row g-3">


                            <!-- FULL NAME -->

                            <div class="col-md-6">


                                <label
                                    for="full_name"
                                    class="form-label">

                                    Full Name

                                </label>


                                <input
                                    type="text"
                                    id="full_name"
                                    name="full_name"
                                    class="form-control"
                                    value="<?= htmlspecialchars($current_full_name) ?>"
                                    required>


                            </div>


                            <!-- USERNAME -->

                            <div class="col-md-6">


                                <label
                                    for="username"
                                    class="form-label">

                                    Username

                                </label>


                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    class="form-control"
                                    value="<?= htmlspecialchars($current_username) ?>"
                                    minlength="3"
                                    required>


                                <div class="form-text">

                                    Username must contain at least
                                    3 characters.

                                </div>


                            </div>


                        </div>


                        <!-- ROLE -->

                        <div class="mt-3">

                            <span class="role-badge">

                                <i class="bi bi-shield-check"></i>

                                Role:

                                <?= htmlspecialchars(
                                    ucfirst($current_role)
                                ) ?>

                            </span>

                        </div>


                        <!-- DIVIDER -->

                        <hr class="section-divider">


                        <!-- =================================================
                             PASSWORD SECTION
                        ================================================= -->

                        <div>

                            <div class="password-title">

                                <i class="bi bi-lock me-2"></i>

                                Change Password

                            </div>


                            <div class="password-description">

                                Leave all password fields empty if you
                                only want to update your name or username.

                            </div>

                        </div>


                        <div class="row g-3">


                            <!-- CURRENT PASSWORD -->

                            <div class="col-md-4">


                                <label
                                    for="current_password"
                                    class="form-label">

                                    Current Password

                                </label>


                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    class="form-control"
                                    autocomplete="current-password">


                            </div>


                            <!-- NEW PASSWORD -->

                            <div class="col-md-4">


                                <label
                                    for="new_password"
                                    class="form-label">

                                    New Password

                                </label>


                                <input
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    class="form-control"
                                    minlength="6"
                                    autocomplete="new-password">


                                <div class="form-text">

                                    Minimum 6 characters.

                                </div>


                            </div>


                            <!-- CONFIRM PASSWORD -->

                            <div class="col-md-4">


                                <label
                                    for="confirm_password"
                                    class="form-label">

                                    Confirm New Password

                                </label>


                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    class="form-control"
                                    minlength="6"
                                    autocomplete="new-password">


                            </div>


                        </div>


                        <!-- PASSWORD INFORMATION -->

                        <div class="info-box mt-4">

                            <i class="bi bi-info-circle"></i>

                            To change your password, enter your current
                            password together with the new password.
                            Passwords are securely hashed before being
                            stored.

                        </div>


                        <!-- SAVE BUTTON -->

                        <div class="mt-4">

                            <button
                                type="submit"
                                class="btn btn-save">

                                <i class="bi bi-check2-circle me-2"></i>

                                Save Changes

                            </button>

                        </div>


                    </form>


                </div>


            </section>


            <!-- =================================================
                 ACCOUNT INFORMATION
            ================================================= -->

            <aside class="info-card">


                <div class="info-card-header">

                    <h3>
                        Account Information
                    </h3>

                </div>


                <div class="info-card-body">


                    <!-- ACCOUNT NAME -->

                    <div class="info-item">

                        <i class="bi bi-person-check"></i>

                        <div>

                            <strong>
                                Account Name
                            </strong>

                            <span>
                                <?= htmlspecialchars(
                                    $current_full_name
                                ) ?>
                            </span>

                        </div>

                    </div>


                    <!-- USERNAME -->

                    <div class="info-item">

                        <i class="bi bi-person"></i>

                        <div>

                            <strong>
                                Username
                            </strong>

                            <span>
                                <?= htmlspecialchars(
                                    $current_username
                                ) ?>
                            </span>

                        </div>

                    </div>


                    <!-- ROLE -->

                    <div class="info-item">

                        <i class="bi bi-shield-check"></i>

                        <div>

                            <strong>
                                Account Role
                            </strong>

                            <span>
                                <?= htmlspecialchars(
                                    ucfirst($current_role)
                                ) ?>
                            </span>

                        </div>

                    </div>


                    <!-- SECURITY -->

                    <div class="info-item">

                        <i class="bi bi-lock"></i>

                        <div>

                            <strong>
                                Password
                            </strong>

                            <span>
                                Securely protected using password hashing.
                            </span>

                        </div>

                    </div>


                    <!-- SYSTEM -->

                    <div class="info-item">

                        <i class="bi bi-flask"></i>

                        <div>

                            <strong>
                                System
                            </strong>

                            <span>
                                Food Process Practical Learning and
                                Simulation System.
                            </span>

                        </div>

                    </div>


                </div>


            </aside>


        </div>


        <!-- =================================================
             SYSTEM INFORMATION
        ================================================= -->

        <section class="system-card">


            <!-- HEADER -->

            <div class="system-header">


                <div class="system-header-content">


                    <div class="system-icon">

                        <i class="bi bi-bar-chart-line"></i>

                    </div>


                    <div>

                        <h3>
                            System Information
                        </h3>

                        <p>
                            Current information about the practical
                            learning system.
                        </p>

                    </div>


                </div>


            </div>


            <!-- BODY -->

            <div class="system-body">


                <div class="stat-grid">


                    <!-- STUDENTS -->

                    <div class="stat-card">

                        <div class="stat-content">


                            <div class="stat-icon">

                                <i class="bi bi-people"></i>

                            </div>


                            <div>

                                <div class="stat-label">
                                    Students
                                </div>

                                <div class="stat-number">

                                    <?= $total_students ?>

                                </div>

                            </div>


                        </div>

                    </div>


                    <!-- PRACTICALS -->

                    <div class="stat-card">

                        <div class="stat-content">


                            <div class="stat-icon">

                                <i class="bi bi-journal-check"></i>

                            </div>


                            <div>

                                <div class="stat-label">
                                    Practicals
                                </div>

                                <div class="stat-number">

                                    <?= $total_practicals ?>

                                </div>

                            </div>


                        </div>

                    </div>


                    <!-- SUBMISSIONS -->

                    <div class="stat-card">

                        <div class="stat-content">


                            <div class="stat-icon">

                                <i class="bi bi-file-earmark-text"></i>

                            </div>


                            <div>

                                <div class="stat-label">
                                    Submissions
                                </div>

                                <div class="stat-number">

                                    <?= $total_submissions ?>

                                </div>

                            </div>


                        </div>

                    </div>


                    <!-- SIMULATIONS -->

                    <div class="stat-card">

                        <div class="stat-content">


                            <div class="stat-icon">

                                <i class="bi bi-activity"></i>

                            </div>


                            <div>

                                <div class="stat-label">
                                    Simulations
                                </div>

                                <div class="stat-number">

                                    <?= $total_simulations ?>

                                </div>

                            </div>


                        </div>

                    </div>


                </div>


                <!-- SYSTEM NOTE -->

                <div class="system-note mt-4">

                    <i class="bi bi-info-circle"></i>

                    The system currently contains
                    <strong>4 laboratory practicals</strong>
                    covering laboratory orientation, physical separation,
                    thermal processing, and drying.

                </div>


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
   CLOSE MOBILE SIDEBAR AFTER CLICKING LINK
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


/* =========================================================
   PASSWORD VALIDATION
========================================================= */

const settingsForm =
    document.querySelector("form");


if (settingsForm) {

    settingsForm.addEventListener(
        "submit",
        function (event) {

            const currentPassword =
                document.getElementById(
                    "current_password"
                ).value.trim();

            const newPassword =
                document.getElementById(
                    "new_password"
                ).value;

            const confirmPassword =
                document.getElementById(
                    "confirm_password"
                ).value;


            /*
             * If any password field is filled,
             * all password requirements must be met.
             */

            if (
                currentPassword !== "" ||
                newPassword !== "" ||
                confirmPassword !== ""
            ) {


                if (currentPassword === "") {

                    event.preventDefault();

                    alert(
                        "Please enter your current password."
                    );

                    return;
                }


                if (newPassword === "") {

                    event.preventDefault();

                    alert(
                        "Please enter a new password."
                    );

                    return;
                }


                if (newPassword.length < 6) {

                    event.preventDefault();

                    alert(
                        "New password must contain at least 6 characters."
                    );

                    return;
                }


                if (confirmPassword === "") {

                    event.preventDefault();

                    alert(
                        "Please confirm your new password."
                    );

                    return;
                }


                if (newPassword !== confirmPassword) {

                    event.preventDefault();

                    alert(
                        "New password and confirmation password do not match."
                    );

                    return;
                }

            }

        }
    );

}

</script>


</body>

</html>