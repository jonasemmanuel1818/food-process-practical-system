<?php

require_once "../config.php";

/* =========================
   SESSION & ACCESS CONTROL
========================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/* Check current user's role */
$stmt = $conn->prepare("
    SELECT role
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$current_role = $result->fetch_assoc()['role'] ?? '';

$stmt->close();

if (!in_array($current_role, ['admin', 'lecturer'], true)) {
    die("Access Denied");
}


/* =========================
   FORM VARIABLES
========================= */

$error = "";
$success = "";

$full_name = "";
$username = "";


/* =========================
   REGISTER ADMIN
========================= */

if (isset($_POST['register'])) {

    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    /* Validate fields */

    if (
        $full_name === '' ||
        $username === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {

        $error = "Please fill in all fields.";

    } elseif (strlen($full_name) < 2) {

        $error = "Please enter a valid full name.";

    } elseif (strlen($username) < 3) {

        $error = "Username must be at least 3 characters.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } else {

        /* Check username */

        $check_stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();

        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {

            $error =
                "Username already exists. Please choose another username.";

            $check_stmt->close();

        } else {

            $check_stmt->close();

            /* Hash password */

            $hashed_password =
                password_hash($password, PASSWORD_DEFAULT);


            /* Create admin account */

            $insert_stmt = $conn->prepare("
                INSERT INTO users
                    (full_name, username, password, role)
                VALUES
                    (?, ?, ?, 'admin')
            ");

            $insert_stmt->bind_param(
                "sss",
                $full_name,
                $username,
                $hashed_password
            );


            if ($insert_stmt->execute()) {

                $success =
                    "Admin account created successfully.";

                $full_name = "";
                $username = "";

            } else {

                $error =
                    "Registration failed. Please try again.";
            }

            $insert_stmt->close();
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
    content="width=device-width, initial-scale=1.0">

<title>
    Register Admin | Food Process System
</title>

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
    min-height: 100vh;
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
   REGISTER LAYOUT
========================= */

.register-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 330px;
    gap: 20px;
    align-items: start;
}


/* =========================
   REGISTER CARD
========================= */

.register-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 9px;
    overflow: hidden;
}

.card-header-custom {
    padding: 20px 22px;
    border-bottom: 1px solid var(--border);
}

.card-header-content {
    display: flex;
    align-items: center;
    gap: 14px;
}

.card-icon {
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

.form-section {
    padding: 22px;
}


/* =========================
   FORM
========================= */

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

.input-group .form-control {
    border-right: 0;
}

.input-group .btn {
    border: 1px solid var(--border);
    border-left: 0;
    background: #fff;
    color: #6c757d;
}

.input-group .btn:hover {
    background: #f7f9fa;
    color: var(--lab-blue);
}

.form-text {
    font-size: 11px;
    color: var(--muted);
}


/* =========================
   ADMIN NOTICE
========================= */

.admin-notice {
    background: var(--lab-light);
    border: 1px solid #cbdfe5;
    border-radius: 8px;
    padding: 14px 15px;
    margin-bottom: 22px;
}

.admin-notice-content {
    display: flex;
    align-items: flex-start;
    gap: 11px;
}

.admin-notice i {
    color: var(--lab-blue);
    font-size: 18px;
    margin-top: 1px;
}

.admin-notice strong {
    display: block;
    color: var(--lab-dark);
    font-size: 13px;
    margin-bottom: 3px;
}

.admin-notice span {
    display: block;
    color: #5e6b72;
    font-size: 11px;
    line-height: 1.5;
}


/* =========================
   BUTTONS
========================= */

.btn-register {
    width: 100%;
    height: 45px;
    border-radius: 7px;
    background: var(--lab-blue);
    border-color: var(--lab-blue);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
}

.btn-register:hover {
    background: var(--lab-dark);
    border-color: var(--lab-dark);
    color: #fff;
}


/* =========================
   SIDE INFORMATION
========================= */

.info-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 9px;
    overflow: hidden;
}

.info-header {
    padding: 18px;
    border-bottom: 1px solid var(--border);
}

.info-header h3 {
    margin: 0;
    color: var(--lab-dark);
    font-size: 15px;
    font-weight: 700;
}

.info-body {
    padding: 18px;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    padding: 11px 0;
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
    font-size: 16px;
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


/* =========================
   ALERTS
========================= */

.alert {
    border-radius: 7px;
    font-size: 12px;
    margin-bottom: 18px;
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


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1050px) {

    .register-layout {
        grid-template-columns: 1fr;
    }

    .info-card {
        max-width: none;
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

    .card-header-custom,
    .form-section,
    .info-header,
    .info-body {
        padding: 17px;
    }

}

@media (max-width: 480px) {

    .page-intro {
        margin-bottom: 18px;
    }

    .page-intro h2 {
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
            <a href="register.php" class="active">

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
                    Register Admin
                </h1>

                <p>
                    Create an administrator account
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

                <i class="bi bi-person-plus me-2"></i>

                Administrator Registration

            </h2>

            <p>
                Create a new account with administrative access to the system.
            </p>

        </div>


        <!-- CONTENT -->

        <div class="register-layout">


            <!-- REGISTER FORM -->

            <section class="register-card">


                <div class="card-header-custom">

                    <div class="card-header-content">

                        <div class="card-icon">

                            <i class="bi bi-person-plus"></i>

                        </div>

                        <div>

                            <h3>
                                Create Admin Account
                            </h3>

                            <p>
                                Enter the account details below.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="form-section">


                    <!-- NOTICE -->

                    <div class="admin-notice">

                        <div class="admin-notice-content">

                            <i class="bi bi-shield-lock"></i>

                            <div>

                                <strong>
                                    Administrator Access
                                </strong>

                                <span>
                                    This account will be created with the
                                    <strong>admin</strong> role and will have
                                    access to the administration panel.
                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- ALERTS -->

                    <?php if (!empty($error)): ?>

                        <div
                            class="alert alert-danger"
                            role="alert">

                            <i class="bi bi-exclamation-circle me-1"></i>

                            <?= htmlspecialchars($error) ?>

                        </div>

                    <?php endif; ?>


                    <?php if (!empty($success)): ?>

                        <div
                            class="alert alert-success"
                            role="alert">

                            <i class="bi bi-check-circle me-1"></i>

                            <?= htmlspecialchars($success) ?>

                        </div>

                    <?php endif; ?>


                    <!-- FORM -->

                    <form method="POST" autocomplete="off">


                        <!-- FULL NAME -->

                        <div class="mb-3">

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
                                placeholder="Enter admin full name"
                                value="<?= htmlspecialchars($full_name) ?>"
                                autocomplete="name"
                                required>

                        </div>


                        <!-- USERNAME -->

                        <div class="mb-3">

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
                                placeholder="Enter admin username"
                                value="<?= htmlspecialchars($username) ?>"
                                autocomplete="username"
                                required>

                        </div>


                        <!-- PASSWORD -->

                        <div class="mb-3">

                            <label
                                for="password"
                                class="form-label">

                                Password

                            </label>

                            <div class="input-group">

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Enter password"
                                    autocomplete="new-password"
                                    minlength="6"
                                    required>

                                <button
                                    type="button"
                                    class="btn"
                                    id="togglePassword"
                                    aria-label="Show password">

                                    <i class="bi bi-eye"></i>

                                </button>

                            </div>

                            <div class="form-text">
                                Minimum 6 characters.
                            </div>

                        </div>


                        <!-- CONFIRM PASSWORD -->

                        <div class="mb-4">

                            <label
                                for="confirm_password"
                                class="form-label">

                                Confirm Password

                            </label>

                            <div class="input-group">

                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    class="form-control"
                                    placeholder="Confirm password"
                                    autocomplete="new-password"
                                    minlength="6"
                                    required>

                                <button
                                    type="button"
                                    class="btn"
                                    id="toggleConfirmPassword"
                                    aria-label="Show password">

                                    <i class="bi bi-eye"></i>

                                </button>

                            </div>

                        </div>


                        <!-- SUBMIT -->

                        <button
                            type="submit"
                            name="register"
                            class="btn btn-register">

                            <i class="bi bi-person-plus me-1"></i>

                            Create Admin Account

                        </button>


                    </form>


                </div>

            </section>


            <!-- INFORMATION -->

            <aside class="info-card">


                <div class="info-header">

                    <h3>
                        Account Information
                    </h3>

                </div>


                <div class="info-body">


                    <div class="info-item">

                        <i class="bi bi-shield-check"></i>

                        <div>

                            <strong>
                                Admin Role
                            </strong>

                            <span>
                                The account is automatically assigned
                                administrator privileges.
                            </span>

                        </div>

                    </div>


                    <div class="info-item">

                        <i class="bi bi-key"></i>

                        <div>

                            <strong>
                                Password Security
                            </strong>

                            <span>
                                Passwords are securely hashed before being
                                stored in the database.
                            </span>

                        </div>

                    </div>


                    <div class="info-item">

                        <i class="bi bi-person-check"></i>

                        <div>

                            <strong>
                                Unique Username
                            </strong>

                            <span>
                                Each administrator must use a unique username.
                            </span>

                        </div>

                    </div>


                    <div class="info-item">

                        <i class="bi bi-speedometer2"></i>

                        <div>

                            <strong>
                                Administration Panel
                            </strong>

                            <span>
                                Admin accounts can access students,
                                submissions and simulation records.
                            </span>

                        </div>

                    </div>


                </div>

            </aside>


        </div>


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


/* =========================
   PASSWORD TOGGLE
========================= */

function setupPasswordToggle(buttonId, inputId) {

    const button =
        document.getElementById(buttonId);

    const input =
        document.getElementById(inputId);

    if (!button || !input) {
        return;
    }

    button.addEventListener(
        "click",
        function () {

            const icon =
                button.querySelector("i");

            if (input.type === "password") {

                input.type = "text";

                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");

                button.setAttribute(
                    "aria-label",
                    "Hide password"
                );

            } else {

                input.type = "password";

                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");

                button.setAttribute(
                    "aria-label",
                    "Show password"
                );

            }

        }
    );
}


setupPasswordToggle(
    "togglePassword",
    "password"
);

setupPasswordToggle(
    "toggleConfirmPassword",
    "confirm_password"
);


/* =========================
   CONFIRM PASSWORD CHECK
========================= */

const form =
    document.querySelector("form");

if (form) {

    form.addEventListener(
        "submit",
        function (event) {

            const password =
                document.getElementById("password").value;

            const confirmPassword =
                document.getElementById("confirm_password").value;

            if (password !== confirmPassword) {

                event.preventDefault();

                alert("Passwords do not match.");

            }

        }
    );

}

</script>


</body>
</html>