<?php

require_once "../config.php";

/* =========================================================
   PROTECT ADMIN SETTINGS
   ========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

/* Only admin and lecturer can access this page */
if (
    !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'lecturer'])
) {
    die("Access Denied");
}

$user_id = (int) $_SESSION['user_id'];

$success = "";
$error = "";


/* =========================================================
   GET CURRENT ADMIN INFORMATION
   ========================================================= */

$stmt = $conn->prepare("
    SELECT full_name, username, password, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param("i", $user_id);

    $stmt->execute();

    $result = $stmt->get_result();

    $user = $result->fetch_assoc();

    $stmt->close();
}


if (!$user) {

    session_destroy();

    header("Location: ../index.php");

    exit();
}


$current_full_name = $user['full_name'];
$current_username = $user['username'];
$current_password_hash = $user['password'];
$current_role = $user['role'];


/* =========================================================
   SYSTEM INFORMATION
   ========================================================= */

$total_students = 0;
$total_practicals = 4;
$total_submissions = 0;
$total_simulations = 0;


/* Total students */

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


/* Total submissions */

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


/* Total simulation results */

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
   HANDLE FORM SUBMISSION
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST['full_name'] ?? "");
    $username = trim($_POST['username'] ?? "");

    $current_password = $_POST['current_password'] ?? "";
    $new_password = $_POST['new_password'] ?? "";
    $confirm_password = $_POST['confirm_password'] ?? "";


    /* -----------------------------------------------------
       BASIC VALIDATION
       ----------------------------------------------------- */

    if ($full_name === "" || $username === "") {

        $error = "Full name and username are required.";

    } elseif (strlen($username) < 3) {

        $error = "Username must contain at least 3 characters.";

    } else {


        /* -------------------------------------------------
           CHECK USERNAME
           ------------------------------------------------- */

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


        /* -------------------------------------------------
           PASSWORD VALIDATION
           ------------------------------------------------- */

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


        /* -------------------------------------------------
           UPDATE ACCOUNT
           ------------------------------------------------- */

        if ($error === "") {

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
                            "Your admin account settings have been updated successfully.";

                    } else {

                        $error =
                            "Unable to update your account settings.";
                    }

                    $stmt->close();
                }

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


            /* -------------------------------------------------
               UPDATE SESSION
               ------------------------------------------------- */

            if ($success !== "") {

                $_SESSION['name'] = $full_name;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['username'] = $username;

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
    Admin Settings | Food Process Practical Learning System
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
   GENERAL
   ========================================================= */

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


/* =========================================================
   HEADER
   ========================================================= */

.top-header {

    height: 68px;

    background: #ffffff;

    border-bottom: 1px solid #d9dee3;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 28px;

    position: fixed;

    top: 0;

    left: 0;

    right: 0;

    z-index: 1000;
}


.brand {

    display: flex;

    align-items: center;

    gap: 12px;

    color: #263238;

    font-size: 18px;

    font-weight: 600;
}


.brand-icon {

    width: 40px;

    height: 40px;

    background: #1f5f75;

    color: #ffffff;

    border-radius: 5px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;
}


.brand-text small {

    display: block;

    color: #7b8790;

    font-size: 11px;

    font-weight: normal;

    margin-top: 2px;
}


.user-area {

    display: flex;

    align-items: center;

    gap: 11px;
}


.user-name {

    font-size: 14px;

    font-weight: 600;

    color: #455a64;
}


.user-avatar {

    width: 38px;

    height: 38px;

    border-radius: 50%;

    background: #e8eef1;

    color: #1f5f75;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 18px;
}


/* =========================================================
   SIDEBAR
   ========================================================= */

.sidebar {

    width: 245px;

    position: fixed;

    top: 68px;

    left: 0;

    bottom: 0;

    background: #ffffff;

    border-right: 1px solid #d9dee3;

    padding: 22px 15px;

    overflow-y: auto;
}


.sidebar-heading {

    padding: 0 11px;

    margin-bottom: 12px;

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.8px;

    color: #8a959d;
}


.nav-link-custom {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 11px 12px;

    margin-bottom: 4px;

    border-radius: 5px;

    color: #53636c;

    font-size: 14px;

    transition:
        background 0.15s ease,
        color 0.15s ease;
}


.nav-link-custom i {

    width: 21px;

    font-size: 17px;
}


.nav-link-custom:hover {

    background: #eef3f5;

    color: #1f5f75;
}


.nav-link-custom.active {

    background: #e7f0f3;

    color: #1f5f75;

    font-weight: 600;

    border-left: 3px solid #1f5f75;

    padding-left: 9px;
}


.nav-section-divider {

    height: 1px;

    background: #edf0f2;

    margin: 20px 10px;
}


.logout-link {

    color: #9b4141;
}


.logout-link:hover {

    background: #faeeee;

    color: #8a3030;
}


/* =========================================================
   MAIN CONTENT
   ========================================================= */

.main-content {

    margin-left: 245px;

    padding: 96px 30px 90px;

    min-height: 100vh;
}


.page-header {

    margin-bottom: 25px;
}


.page-header h1 {

    margin: 0 0 5px;

    font-size: 25px;

    font-weight: 600;

    color: #263238;
}


.page-header p {

    margin: 0;

    color: #7b8790;

    font-size: 14px;
}


/* =========================================================
   SETTINGS CARD
   ========================================================= */

.settings-card {

    background: #ffffff;

    border: 1px solid #d9dee3;

    border-radius: 6px;

    margin-bottom: 22px;
}


.settings-header {

    padding: 17px 20px;

    border-bottom: 1px solid #e6eaed;

    display: flex;

    align-items: center;

    gap: 12px;
}


.settings-icon {

    width: 40px;

    height: 40px;

    background: #edf4f6;

    color: #1f5f75;

    border-radius: 5px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;
}


.settings-header h5 {

    margin: 0;

    font-size: 16px;

    font-weight: 600;

    color: #37474f;
}


.settings-header p {

    margin: 3px 0 0;

    color: #87939b;

    font-size: 12px;
}


.settings-body {

    padding: 22px;
}


/* =========================================================
   FORM
   ========================================================= */

.form-label {

    font-size: 13px;

    font-weight: 600;

    color: #455a64;

    margin-bottom: 7px;
}


.form-control {

    border: 1px solid #d5dce0;

    border-radius: 4px;

    padding: 10px 12px;

    font-size: 13px;

    color: #37474f;
}


.form-control:focus {

    border-color: #1f5f75;

    box-shadow:
        0 0 0 0.15rem rgba(31,95,117,0.12);
}


.form-text {

    font-size: 11px;

    color: #89949b;
}


/* =========================================================
   SAVE BUTTON
   ========================================================= */

.btn-save {

    background: #1f5f75;

    border: 1px solid #1f5f75;

    color: #ffffff;

    padding: 10px 18px;

    border-radius: 4px;

    font-size: 13px;

    font-weight: 600;
}


.btn-save:hover {

    background: #17495a;

    border-color: #17495a;

    color: #ffffff;
}


/* =========================================================
   SYSTEM STATISTICS
   ========================================================= */

.stat-card {

    background: #ffffff;

    border: 1px solid #d9dee3;

    border-radius: 6px;

    padding: 18px;

    height: 100%;

    display: flex;

    align-items: center;

    gap: 13px;
}


.stat-icon {

    width: 42px;

    height: 42px;

    background: #edf4f6;

    color: #1f5f75;

    border-radius: 5px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;

    flex-shrink: 0;
}


.stat-label {

    font-size: 11px;

    color: #7d8990;

    margin-bottom: 4px;
}


.stat-number {

    font-size: 23px;

    font-weight: 600;

    color: #37474f;
}


/* =========================================================
   INFO BOX
   ========================================================= */

.info-box {

    background: #f8fafb;

    border: 1px solid #e1e6e8;

    border-radius: 5px;

    padding: 14px;

    color: #66757d;

    font-size: 12px;

    line-height: 1.6;
}


.info-box i {

    color: #1f5f75;

    margin-right: 6px;
}


/* =========================================================
   FOOTER
   ========================================================= */

.footer {

    position: fixed;

    bottom: 0;

    left: 245px;

    right: 0;

    height: 43px;

    background: #ffffff;

    border-top: 1px solid #d9dee3;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #89949b;

    font-size: 11px;

    z-index: 900;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 992px) {

    .sidebar {
        width: 220px;
    }

    .main-content {
        margin-left: 220px;
    }

    .footer {
        left: 220px;
    }
}


@media (max-width: 768px) {

    .top-header {
        padding: 0 18px;
    }

    .brand-text {
        display: none;
    }

    .sidebar {

        width: 68px;

        padding: 18px 8px;
    }

    .sidebar-heading {
        display: none;
    }

    .nav-link-custom {

        justify-content: center;

        padding: 12px 5px;
    }

    .nav-link-custom span {
        display: none;
    }

    .nav-link-custom i {
        width: auto;
    }

    .nav-section-divider {
        margin: 15px 5px;
    }

    .main-content {

        margin-left: 68px;

        padding: 90px 18px 75px;
    }

    .footer {
        left: 68px;
    }

    .user-name {
        display: none;
    }
}


@media (max-width: 576px) {

    .main-content {

        padding-left: 12px;

        padding-right: 12px;
    }

    .page-header h1 {
        font-size: 21px;
    }

    .settings-body {
        padding: 15px;
    }
}

</style>

</head>


<body>


<!-- =========================================================
     HEADER
     ========================================================= -->

<header class="top-header">

    <div class="brand">

        <div class="brand-icon">

            <i class="bi bi-flask"></i>

        </div>


        <div class="brand-text">

            Food Process Practical Learning System

            <small>
                Administration Panel
            </small>

        </div>

    </div>


    <div class="user-area">

        <div class="user-name">

            <?php
            echo htmlspecialchars($current_full_name);
            ?>

        </div>


        <div class="user-avatar">

            <i class="bi bi-person-gear"></i>

        </div>

    </div>

</header>


<!-- =========================================================
     SIDEBAR
     ========================================================= -->

<aside class="sidebar">

    <div class="sidebar-heading">
        Administration
    </div>


    <a
        href="dashboard.php"
        class="nav-link-custom"
    >

        <i class="bi bi-speedometer2"></i>

        <span>
            Dashboard
        </span>

    </a>


    <a
        href="students.php"
        class="nav-link-custom"
    >

        <i class="bi bi-people"></i>

        <span>
            Students
        </span>

    </a>


    <a
        href="submissions.php"
        class="nav-link-custom"
    >

        <i class="bi bi-file-earmark-text"></i>

        <span>
            Submissions
        </span>

    </a>


    <a
        href="simulation_results.php"
        class="nav-link-custom"
    >

        <i class="bi bi-bar-chart"></i>

        <span>
            Simulation Results
        </span>

    </a>


    <a
        href="register.php"
        class="nav-link-custom"
    >

        <i class="bi bi-person-plus"></i>

        <span>
            Register Admin
        </span>

    </a>


    <div class="nav-section-divider"></div>


    <div class="sidebar-heading">
        Account
    </div>


    <a
        href="settings.php"
        class="nav-link-custom active"
    >

        <i class="bi bi-gear"></i>

        <span>
            Settings
        </span>

    </a>


    <a
        href="../logout.php"
        class="nav-link-custom logout-link"
    >

        <i class="bi bi-box-arrow-right"></i>

        <span>
            Log Out
        </span>

    </a>

</aside>


<!-- =========================================================
     MAIN CONTENT
     ========================================================= -->

<main class="main-content">


    <div class="page-header">

        <h1>
            Admin Settings
        </h1>

        <p>
            Manage your administrator account and view system information.
        </p>

    </div>


    <!-- SUCCESS -->

    <?php if ($success !== ""): ?>

        <div class="alert alert-success">

            <i class="bi bi-check-circle me-2"></i>

            <?php echo htmlspecialchars($success); ?>

        </div>

    <?php endif; ?>


    <!-- ERROR -->

    <?php if ($error !== ""): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-circle me-2"></i>

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         ACCOUNT SETTINGS
         ===================================================== -->

    <div class="settings-card">

        <div class="settings-header">

            <div class="settings-icon">

                <i class="bi bi-person-gear"></i>

            </div>

            <div>

                <h5>
                    Administrator Account
                </h5>

                <p>
                    Update your administrator account information.
                </p>

            </div>

        </div>


        <div class="settings-body">

            <form method="POST" action="settings.php">


                <div class="row g-3">


                    <div class="col-md-6">

                        <label
                            for="full_name"
                            class="form-label"
                        >
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            class="form-control"
                            value="<?php echo htmlspecialchars($current_full_name); ?>"
                            required
                        >

                    </div>


                    <div class="col-md-6">

                        <label
                            for="username"
                            class="form-label"
                        >
                            Username
                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            value="<?php echo htmlspecialchars($current_username); ?>"
                            required
                        >

                        <div class="form-text">
                            Username must contain at least 3 characters.
                        </div>

                    </div>


                </div>


                <div class="mt-3">

                    <span class="badge text-bg-secondary">

                        <i class="bi bi-shield-check me-1"></i>

                        Role:
                        <?php echo htmlspecialchars(ucfirst($current_role)); ?>

                    </span>

                </div>


                <hr class="my-4">


                <!-- PASSWORD -->

                <h6 class="mb-3">

                    <i class="bi bi-lock me-2"></i>

                    Change Password

                </h6>


                <div class="row g-3">


                    <div class="col-md-4">

                        <label
                            for="current_password"
                            class="form-label"
                        >
                            Current Password
                        </label>

                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            class="form-control"
                            autocomplete="current-password"
                        >

                    </div>


                    <div class="col-md-4">

                        <label
                            for="new_password"
                            class="form-label"
                        >
                            New Password
                        </label>

                        <input
                            type="password"
                            id="new_password"
                            name="new_password"
                            class="form-control"
                            autocomplete="new-password"
                        >

                    </div>


                    <div class="col-md-4">

                        <label
                            for="confirm_password"
                            class="form-label"
                        >
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            autocomplete="new-password"
                        >

                    </div>


                </div>


                <div class="info-box mt-4">

                    <i class="bi bi-info-circle"></i>

                    Leave the password fields empty if you only want to
                    update your name or username. A new password must
                    contain at least 6 characters.

                </div>


                <div class="mt-4">

                    <button
                        type="submit"
                        class="btn btn-save"
                    >

                        <i class="bi bi-check2-circle me-2"></i>

                        Save Changes

                    </button>

                </div>


            </form>

        </div>

    </div>


    <!-- =====================================================
         SYSTEM INFORMATION
         ===================================================== -->

    <div class="settings-card">

        <div class="settings-header">

            <div class="settings-icon">

                <i class="bi bi-bar-chart-line"></i>

            </div>

            <div>

                <h5>
                    System Information
                </h5>

                <p>
                    Current information about the practical learning system.
                </p>

            </div>

        </div>


        <div class="settings-body">

            <div class="row g-3">


                <div class="col-6 col-lg-3">

                    <div class="stat-card">

                        <div class="stat-icon">

                            <i class="bi bi-people"></i>

                        </div>

                        <div>

                            <div class="stat-label">
                                Students
                            </div>

                            <div class="stat-number">
                                <?php echo $total_students; ?>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-6 col-lg-3">

                    <div class="stat-card">

                        <div class="stat-icon">

                            <i class="bi bi-journal-check"></i>

                        </div>

                        <div>

                            <div class="stat-label">
                                Practicals
                            </div>

                            <div class="stat-number">
                                <?php echo $total_practicals; ?>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-6 col-lg-3">

                    <div class="stat-card">

                        <div class="stat-icon">

                            <i class="bi bi-file-earmark-text"></i>

                        </div>

                        <div>

                            <div class="stat-label">
                                Submissions
                            </div>

                            <div class="stat-number">
                                <?php echo $total_submissions; ?>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-6 col-lg-3">

                    <div class="stat-card">

                        <div class="stat-icon">

                            <i class="bi bi-activity"></i>

                        </div>

                        <div>

                            <div class="stat-label">
                                Simulations
                            </div>

                            <div class="stat-number">
                                <?php echo $total_simulations; ?>
                            </div>

                        </div>

                    </div>

                </div>


            </div>


            <div class="info-box mt-4">

                <i class="bi bi-info-circle"></i>

                The system currently contains
                <strong>4 laboratory practicals</strong>
                covering laboratory orientation, physical separation,
                thermal processing, and drying.

            </div>

        </div>

    </div>


</main>


<!-- =========================================================
     FOOTER
     ========================================================= -->

<footer class="footer">

    Food Process Practical Learning System

    &nbsp; | &nbsp;

    Administration Panel

</footer>


</body>

</html>