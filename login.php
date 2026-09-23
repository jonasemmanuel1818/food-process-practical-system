<?php

session_start();

require_once "config.php";
require_once "rate_limit.php";


/*
|--------------------------------------------------------------------------
| ERROR MESSAGE
|--------------------------------------------------------------------------
*/

$error = "";


/*
|--------------------------------------------------------------------------
| GET CLIENT IP
|--------------------------------------------------------------------------
*/

$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';


/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/

if (isset($_POST['login'])) {

    $username = trim(
        $_POST['username'] ?? ''
    );

    $password = $_POST['password'] ?? '';

    $role = trim(
        $_POST['role'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $username === '' ||
        $password === '' ||
        $role === ''
    ) {

        $error = "Please fill in all fields.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | VALIDATE ROLE
        |--------------------------------------------------------------------------
        */

        $allowed_roles = [
            'student',
            'admin'
        ];

        if (!in_array(
            $role,
            $allowed_roles,
            true
        )) {

            $error = "Invalid account type.";

        } else {


            /*
            |--------------------------------------------------------------------------
            | LOGIN RATE-LIMIT KEY
            |--------------------------------------------------------------------------
            */

            $rate_key =
                "login_" .
                strtolower($username) .
                "_" .
                $client_ip;


            /*
            |--------------------------------------------------------------------------
            | CHECK FAILED LOGIN LIMIT
            |--------------------------------------------------------------------------
            |
            | Maximum 5 FAILED attempts within 15 minutes.
            |
            | This check does NOT record an attempt.
            |--------------------------------------------------------------------------
            */

            $rate_status = get_rate_limit_status(
                $rate_key,
                5,
                900
            );


            /*
            |--------------------------------------------------------------------------
            | BLOCK IF LIMIT REACHED
            |--------------------------------------------------------------------------
            */

            if (!$rate_status['allowed']) {

                $minutes = ceil(
                    $rate_status['retry_after'] / 60
                );

                if ($minutes < 1) {
                    $minutes = 1;
                }

                $error =
                    "Too many failed login attempts. "
                    . "Please try again in approximately "
                    . $minutes
                    . " minute(s).";

            } else {


                /*
                |--------------------------------------------------------------------------
                | FIND USER
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    SELECT
                        id,
                        full_name,
                        username,
                        password,
                        role
                    FROM users
                    WHERE username = ?
                    AND role = ?
                    LIMIT 1
                ");


                if (!$stmt) {

                    $error =
                        "A system error occurred. Please try again.";

                } else {


                    /*
                    |--------------------------------------------------------------------------
                    | BIND USERNAME AND ROLE
                    |--------------------------------------------------------------------------
                    */

                    $stmt->bind_param(
                        "ss",
                        $username,
                        $role
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | EXECUTE QUERY
                    |--------------------------------------------------------------------------
                    */

                    $stmt->execute();

                    $result = $stmt->get_result();


                    /*
                    |--------------------------------------------------------------------------
                    | USER FOUND
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $result &&
                        $result->num_rows === 1
                    ) {

                        $user =
                            $result->fetch_assoc();


                        /*
                        |--------------------------------------------------------------------------
                        | VERIFY PASSWORD
                        |--------------------------------------------------------------------------
                        */

                        if (
                            password_verify(
                                $password,
                                $user['password']
                            )
                        ) {


                            /*
                            |--------------------------------------------------------------------------
                            | SUCCESSFUL LOGIN
                            |--------------------------------------------------------------------------
                            |
                            | Clear any previous failed attempts.
                            |--------------------------------------------------------------------------
                            */

                            clear_rate_limit(
                                $rate_key
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | REGENERATE SESSION ID
                            |--------------------------------------------------------------------------
                            */

                            session_regenerate_id(true);


                            /*
                            |--------------------------------------------------------------------------
                            | STORE SESSION INFORMATION
                            |--------------------------------------------------------------------------
                            */

                            $_SESSION['user_id'] =
                                (int) $user['id'];

                            $_SESSION['full_name'] =
                                $user['full_name'];

                            $_SESSION['username'] =
                                $user['username'];

                            $_SESSION['role'] =
                                $user['role'];


                            /*
                            |--------------------------------------------------------------------------
                            | REDIRECT
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $user['role'] === 'admin'
                            ) {

                                header(
                                    "Location: admin/dashboard.php"
                                );

                                exit();

                            } else {

                                header(
                                    "Location: dashboard.php"
                                );

                                exit();
                            }


                        } else {


                            /*
                            |--------------------------------------------------------------------------
                            | INCORRECT PASSWORD
                            |--------------------------------------------------------------------------
                            |
                            | Record ONLY the failed attempt.
                            |--------------------------------------------------------------------------
                            */

                            record_rate_limit_attempt(
                                $rate_key,
                                5,
                                900
                            );

                            $error =
                                "Incorrect password.";
                        }


                    } else {


                        /*
                        |--------------------------------------------------------------------------
                        | USERNAME / ROLE NOT FOUND
                        |--------------------------------------------------------------------------
                        |
                        | This also counts as a failed login attempt.
                        |--------------------------------------------------------------------------
                        */

                        record_rate_limit_attempt(
                            $rate_key,
                            5,
                            900
                        );

                        $error =
                            "No account found with this username and selected role.";
                    }


                    $stmt->close();
                }
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
        Login | Food Process Practical Learning & Simulation System
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

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

            min-height: 100vh;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background: #f4f6f8;

            color: #263238;
        }


        .login-page {

            min-height: 100vh;

            display: flex;

            align-items: stretch;
        }


        /*
        ============================================================
        LEFT PANEL
        ============================================================
        */

        .left-panel {

            width: 50%;

            min-height: 100vh;

            position: relative;

            overflow: hidden;

            background:
                linear-gradient(
                    rgba(15, 88, 112, 0.78),
                    rgba(15, 88, 112, 0.78)
                ),
                url("assets/images/lab-login.jpg")
                center / cover no-repeat;

            color: #ffffff;
        }


        .left-content {

            position: relative;

            z-index: 2;

            height: 100%;

            min-height: 100vh;

            padding:
                65px
                70px
                180px;
        }


        .brand {

            display: flex;

            align-items: center;

            gap: 12px;

            font-size: 15px;

            font-weight: 600;

            letter-spacing: 0.4px;
        }


        .brand-icon {

            width: 38px;

            height: 38px;

            border-radius: 8px;

            background: rgba(255,255,255,0.16);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;
        }


        .left-title {

            margin-top: 110px;

            max-width: 610px;

            font-size: 52px;

            line-height: 1.08;

            font-weight: 700;

            letter-spacing: -1.2px;
        }


        .left-description {

            max-width: 580px;

            margin-top: 24px;

            font-size: 17px;

            line-height: 1.75;

            color: rgba(255,255,255,0.90);
        }


        .left-footer {

            position: absolute;

            left: 70px;

            right: 70px;

            bottom: 130px;

            display: flex;

            align-items: center;

            gap: 10px;

            font-size: 13px;

            color: rgba(255,255,255,0.78);
        }


        /*
        ============================================================
        RIGHT PANEL
        ============================================================
        */

        .right-panel {

            width: 50%;

            min-height: 100vh;

            background: #ffffff;

            position: relative;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 50px;
        }


        .right-panel::before {

            content: "";

            position: absolute;

            width: 150px;

            height: 150px;

            right: 35px;

            bottom: 30px;

            background-image:
                radial-gradient(
                    #dce7eb 1.2px,
                    transparent 1.2px
                );

            background-size: 12px 12px;

            opacity: 0.7;

            pointer-events: none;
        }


        .login-wrapper {

            width: 100%;

            max-width: 470px;

            position: relative;

            z-index: 2;

            margin-top: 75px;
        }


        .top-message {

            position: absolute;

            top: 48px;

            left: 50px;

            right: 50px;

            text-align: center;

            color: #17495a;

            font-size: 15px;

            font-weight: 600;
        }


        .top-message span {

            color: #64748b;

            font-weight: 400;
        }


        .login-heading {

            font-size: 34px;

            font-weight: 700;

            color: #17495a;

            margin-bottom: 9px;
        }


        .login-subheading {

            font-size: 15px;

            color: #6b7280;

            margin-bottom: 25px;
        }


        /*
        ============================================================
        ERROR MESSAGE
        ============================================================
        */

        .error-message {

            background: #fff4f4;

            border: 1px solid #f1c4c4;

            color: #a33a3a;

            border-radius: 8px;

            padding: 12px 14px;

            margin-bottom: 18px;

            font-size: 14px;

            line-height: 1.5;

            display: flex;

            align-items: flex-start;

            gap: 9px;
        }


        .error-message i {

            margin-top: 2px;
        }


        /*
        ============================================================
        ACCOUNT TYPE
        ============================================================
        */

        .account-label {

            display: block;

            color: #374151;

            font-size: 14px;

            font-weight: 600;

            margin-bottom: 10px;
        }


        .account-options {

            display: flex;

            gap: 10px;

            margin-bottom: 25px;
        }


        .account-option {

            flex: 1;

            position: relative;
        }


        .account-option input {

            position: absolute;

            opacity: 0;

            pointer-events: none;
        }


        .account-card {

            height: 48px;

            border: 1px solid #d9dee3;

            border-radius: 8px;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            color: #52606d;

            background: #ffffff;

            cursor: pointer;

            font-size: 15px;

            font-weight: 500;

            transition:
                border-color 0.2s ease,
                background 0.2s ease,
                color 0.2s ease;
        }


        .account-card i {

            font-size: 20px;
        }


        .account-option input:checked + .account-card {

            border-color: #1f5f75;

            background: #eef7fa;

            color: #17495a;

            box-shadow:
                0 0 0 2px rgba(31,95,117,0.08);
        }


        /*
        ============================================================
        FORM
        ============================================================
        */

        .form-group {

            margin-bottom: 22px;
        }


        .form-label {

            display: block;

            font-size: 14px;

            font-weight: 600;

            color: #374151;

            margin-bottom: 8px;
        }


        .input-wrapper {

            position: relative;
        }


        .input-wrapper > i {

            position: absolute;

            left: 15px;

            top: 50%;

            transform: translateY(-50%);

            color: #82909a;

            font-size: 17px;

            z-index: 2;
        }


        .form-control {

            height: 51px;

            border: 1px solid #d9dee3;

            border-radius: 8px;

            padding-left: 44px;

            padding-right: 44px;

            font-size: 15px;

            color: #263238;

            box-shadow: none;
        }


        .form-control:focus {

            border-color: #1f5f75;

            box-shadow:
                0 0 0 3px rgba(31,95,117,0.10);
        }


        .password-toggle {

            position: absolute;

            right: 14px;

            top: 50%;

            transform: translateY(-50%);

            border: 0;

            background: transparent;

            color: #82909a;

            padding: 3px;

            cursor: pointer;

            font-size: 17px;

            z-index: 3;
        }


        .password-toggle:hover {

            color: #1f5f75;
        }


        /*
        ============================================================
        LOGIN BUTTON
        ============================================================
        */

        .login-button {

            width: 100%;

            height: 54px;

            border: 0;

            border-radius: 8px;

            background: #1f5f75;

            color: #ffffff;

            font-size: 15px;

            font-weight: 600;

            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }


        .login-button:hover {

            background: #17495a;

            color: #ffffff;
        }


        .login-button:active {

            transform: translateY(1px);
        }


        /*
        ============================================================
        REGISTER
        ============================================================
        */

        .register-section {

            margin-top: 22px;

            padding-top: 18px;

            border-top: 1px solid #e6eaed;

            text-align: center;

            color: #6b7280;

            font-size: 14px;
        }


        .register-section a {

            color: #1f5f75;

            font-weight: 600;

            text-decoration: none;
        }


        .register-section a:hover {

            text-decoration: underline;
        }


        /*
        ============================================================
        RESPONSIVE
        ============================================================
        */

        @media (max-width: 1000px) {

            .left-content {

                padding-left: 45px;

                padding-right: 45px;
            }


            .left-title {

                font-size: 44px;
            }


            .left-footer {

                left: 45px;

                right: 45px;
            }


            .right-panel {

                padding: 35px;
            }


            .top-message {

                left: 35px;

                right: 35px;
            }
        }


        @media (max-width: 800px) {

            .login-page {

                display: block;
            }


            .left-panel {

                width: 100%;

                min-height: 510px;
            }


            .left-content {

                min-height: 510px;

                padding:
                    55px
                    35px
                    0;
            }


            .left-title {

                margin-top: 85px;

                font-size: 40px;

                max-width: 650px;
            }


            .left-description {

                font-size: 16px;

                line-height: 1.65;
            }


            .left-footer {

                left: 35px;

                right: 35px;

                bottom: 48px;

                line-height: 1.5;

                flex-wrap: wrap;
            }


            .right-panel {

                width: 100%;

                min-height: 700px;

                padding:
                    75px
                    35px
                    60px;

                align-items: flex-start;
            }


            .top-message {

                left: 35px;

                right: 35px;

                top: 28px;
            }


            .login-wrapper {

                margin-top: 72px;
            }
        }


        @media (max-width: 520px) {

            .left-panel {

                min-height: 520px;
            }


            .left-content {

                min-height: 520px;

                padding:
                    45px
                    25px
                    0;
            }


            .left-title {

                margin-top: 70px;

                font-size: 33px;
            }


            .left-description {

                margin-top: 18px;

                font-size: 15px;
            }


            .left-footer {

                left: 25px;

                right: 25px;

                bottom: 35px;

                font-size: 12px;
            }


            .right-panel {

                padding:
                    65px
                    25px
                    50px;
            }


            .top-message {

                left: 25px;

                right: 25px;

                top: 25px;

                font-size: 14px;
            }


            .login-wrapper {

                margin-top: 65px;
            }


            .login-heading {

                font-size: 29px;
            }


            .account-options {

                flex-direction: column;

                gap: 8px;
            }


            .account-card {

                height: 48px;
            }


            .form-control {

                height: 49px;
            }


            .login-button {

                height: 52px;
            }


            .register-section {

                font-size: 13px;
            }
        }

    </style>

</head>


<body>


<div class="login-page">


    <!-- =========================================================
         LEFT PANEL
    ========================================================== -->

    <section class="left-panel">

        <div class="left-content">


            <div class="brand">

                <div class="brand-icon">

                    <i class="bi bi-beaker"></i>

                </div>

                Food Process Practical Learning & Simulation System

            </div>


            <h1 class="left-title">

                Learn practical food processing through experience.

            </h1>


            <p class="left-description">

                Explore laboratory practicals, perform simulations,
                record observations and understand food processing
                operations through an interactive learning environment.

            </p>


            <div class="left-footer">

                <i class="bi bi-shield-check"></i>

                Academic learning environment for practical food
                processing education.

            </div>

        </div>

    </section>



    <!-- =========================================================
         RIGHT PANEL
    ========================================================== -->

    <section class="right-panel">


        <div class="top-message">

            Learning today

            <span>/ for a healthier tomorrow.</span>

        </div>


        <div class="login-wrapper">


            <h2 class="login-heading">

                Sign in

            </h2>


            <p class="login-subheading">

                Access your practical learning workspace.

            </p>


            <?php if ($error !== ""): ?>

                <div class="error-message">

                    <i class="bi bi-exclamation-circle-fill"></i>

                    <span>
                        <?= htmlspecialchars($error) ?>
                    </span>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                autocomplete="off"
            >


                <!-- ACCOUNT TYPE -->

                <label class="account-label">

                    Account type

                </label>


                <div class="account-options">


                    <div class="account-option">

                        <input
                            type="radio"
                            name="role"
                            id="studentRole"
                            value="student"
                            checked
                        >

                        <label
                            for="studentRole"
                            class="account-card"
                        >

                            <i class="bi bi-person"></i>

                            Student

                        </label>

                    </div>


                    <div class="account-option">

                        <input
                            type="radio"
                            name="role"
                            id="adminRole"
                            value="admin"
                        >

                        <label
                            for="adminRole"
                            class="account-card"
                        >

                            <i class="bi bi-shield-lock"></i>

                            Admin

                        </label>

                    </div>


                </div>



                <!-- USERNAME -->

                <div class="form-group">

                    <label
                        for="username"
                        class="form-label"
                    >

                        Username

                    </label>


                    <div class="input-wrapper">

                        <i class="bi bi-person"></i>

                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-control"
                            placeholder="Enter your username"
                            value=""
                            required
                            autocomplete="off"
                        >

                    </div>

                </div>



                <!-- PASSWORD -->

                <div class="form-group">

                    <label
                        for="password"
                        class="form-label"
                    >

                        Password

                    </label>


                    <div class="input-wrapper">

                        <i class="bi bi-lock"></i>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            placeholder="Enter your password"
                            value=""
                            required
                            autocomplete="new-password"
                        >


                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Show password"
                        >

                            <i class="bi bi-eye"></i>

                        </button>

                    </div>

                </div>



                <!-- LOGIN BUTTON -->

                <button
                    type="submit"
                    name="login"
                    class="login-button"
                >

                    <i class="bi bi-box-arrow-in-right me-2"></i>

                    Sign In

                </button>


            </form>



            <!-- REGISTER -->

            <div class="register-section">

                Don't have a student account?

                <a href="register.php">
                    Register here
                </a>

            </div>


        </div>

    </section>

</div>



<script>

    /*
    ============================================================
    PASSWORD VISIBILITY
    ============================================================
    */

    const passwordInput =
        document.getElementById("password");

    const togglePassword =
        document.getElementById("togglePassword");


    if (
        passwordInput &&
        togglePassword
    ) {

        togglePassword.addEventListener(
            "click",
            function () {

                const icon =
                    this.querySelector("i");


                if (
                    passwordInput.type === "password"
                ) {

                    passwordInput.type = "text";

                    icon.classList.remove(
                        "bi-eye"
                    );

                    icon.classList.add(
                        "bi-eye-slash"
                    );

                    this.setAttribute(
                        "aria-label",
                        "Hide password"
                    );

                } else {

                    passwordInput.type = "password";

                    icon.classList.remove(
                        "bi-eye-slash"
                    );

                    icon.classList.add(
                        "bi-eye"
                    );

                    this.setAttribute(
                        "aria-label",
                        "Show password"
                    );
                }

            }
        );
    }

</script>


</body>

</html>