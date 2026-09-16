<?php
session_start();
include("config.php");

$error = "";

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($username) || empty($password) || empty($role)) {

        $error = "Please fill in all fields.";

    } else {

        $sql = "SELECT * FROM users
                WHERE username = '$username'
                AND role = '$role'
                LIMIT 1";

        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) === 1) {

            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if (
                    $user['role'] === 'admin' ||
                    $user['role'] === 'lecturer'
                ) {

                    header("Location: admin/dashboard.php");
                    exit();

                } else {

                    header("Location: dashboard.php");
                    exit();

                }

            } else {

                $error = "Incorrect password.";

            }

        } else {

            $error = "No account found with this username and selected role.";

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
        Login | Food Process Practical Learning System
    </title>


    <!-- Bootstrap Icons -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        html,
        body {

            margin: 0;
            padding: 0;

            width: 100%;
            min-height: 100%;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f8fa;

            color: #173f53;
        }


        /* =====================================================
           MAIN LOGIN PAGE
        ===================================================== */

        .login-page {

            width: 100%;
            min-height: 100vh;

            display: flex;

            overflow: hidden;

            background: #ffffff;
        }


        /* =====================================================
           LEFT SIDE
        ===================================================== */

        .left-panel {

            position: relative;

            width: 49%;
            min-height: 100vh;

            background-image:
                url("assets/images/lab-login.jpg");

            background-size: cover;

            background-position: center;

            display: flex;

            align-items: flex-start;

            color: #ffffff;

            overflow: hidden;
        }


        /* =====================================================
           BLUE IMAGE OVERLAY
        ===================================================== */

        .left-panel::before {

            content: "";

            position: absolute;

            inset: 0;

            background:
                rgba(15, 88, 112, 0.78);

            z-index: 1;
        }


        /* =====================================================
           BOTTOM IMAGE DARKENING
        ===================================================== */

        .left-panel::after {

            content: "";

            position: absolute;

            left: 0;
            right: 0;
            bottom: 0;

            height: 30%;

            background:
                rgba(7, 56, 72, 0.20);

            z-index: 1;
        }


        /* =====================================================
           LEFT MAIN CONTENT
        ===================================================== */

        .left-content {

            position: relative;

            z-index: 2;

            width: 100%;

            max-width: 690px;

            padding:
                70px 80px 0;

            margin: 0;
        }


        /* =====================================================
           LEFT TITLE
        ===================================================== */

        .left-title {

            margin: 0;

            color: #ffffff;

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size:
                clamp(40px, 3.7vw, 64px);

            line-height: 1.08;

            font-weight: 700;

            letter-spacing: -1.2px;
        }


        /* =====================================================
           LINE UNDER TITLE
        ===================================================== */

        .left-line {

            width: 48px;

            height: 2px;

            background: #ffffff;

            margin-top: 30px;

            margin-bottom: 25px;
        }


        /* =====================================================
           LEFT DESCRIPTION
        ===================================================== */

        .left-description {

            max-width: 530px;

            margin: 0;

            color: #ffffff;

            font-size: 18px;

            line-height: 1.65;

            font-weight: 400;
        }


        /* =====================================================
           LEFT FOOTER
        ===================================================== */

        .left-footer {

            position: absolute;

            left: 80px;

            bottom: 130px;

            z-index: 3;

            color: #ffffff;

            font-size: 14px;

            letter-spacing: 0.2px;
        }


        /* =====================================================
           FOOTER LINE
        ===================================================== */

        .footer-divider {

            width: 42px;

            height: 2px;

            background: #ffffff;

            margin-bottom: 20px;
        }


        /* =====================================================
           FOOTER ITEMS
        ===================================================== */

        .footer-items {

            display: flex;

            align-items: center;

            gap: 14px;

            white-space: nowrap;
        }


        .footer-separator {

            opacity: 0.7;
        }


        /* =====================================================
           RIGHT SIDE
        ===================================================== */

        .right-panel {

            position: relative;

            width: 51%;

            min-height: 100vh;

            background: #f8fafb;

            display: flex;

            align-items: center;

            justify-content: center;

            padding:
                60px 70px;

            overflow: hidden;
        }


        /* =====================================================
           TOP RIGHT CIRCLE DECORATION
        ===================================================== */

        .top-decoration {

            position: absolute;

            top: -250px;

            right: -180px;

            width: 480px;

            height: 480px;

            border:
                26px solid #e6f0f4;

            border-radius: 50%;

            pointer-events: none;
        }


        .top-decoration-inner {

            position: absolute;

            top: -205px;

            right: -135px;

            width: 390px;

            height: 390px;

            border:
                1px solid #e0ebef;

            border-radius: 50%;

            pointer-events: none;
        }


        /* =====================================================
           LEARNING MESSAGE
        ===================================================== */

        .top-message {

            position: absolute;

            top: 55px;

            left: 70px;

            width: 220px;

            color: #347492;

            font-size: 15px;

            line-height: 1.45;

            z-index: 2;
        }


        .top-message-line {

            width: 48px;

            height: 2px;

            background: #1684b2;

            margin-top: 17px;
        }


        /* =====================================================
           LOGIN CONTAINER
        ===================================================== */

        .login-container {

            position: relative;

            z-index: 5;

            width: 100%;

            max-width: 570px;

            margin-top: 75px;
        }


        /* =====================================================
           ERROR MESSAGE
        ===================================================== */

        .login-error {

            background: #fff2f2;

            border:
                1px solid #e6caca;

            color: #8c3f3f;

            border-radius: 7px;

            padding:
                12px 15px;

            margin-bottom: 25px;

            font-size: 13px;
        }


        /* =====================================================
           FIELD LABEL
        ===================================================== */

        .field-label {

            display: block;

            margin-bottom: 11px;

            color: #173f53;

            font-size: 16px;

            font-weight: 700;
        }


        /* =====================================================
           ACCOUNT TYPE
        ===================================================== */

        .account-type {

            margin-bottom: 25px;
        }


        .role-options {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 20px;
        }


        .role-option-wrapper {

            position: relative;
        }


        .role-option-wrapper input {

            position: absolute;

            opacity: 0;

            pointer-events: none;
        }


        .role-option {

            position: relative;

            height: 48px;

            width: 100%;

            border:
                1px solid #c9d8df;

            border-radius: 6px;

            background: #ffffff;

            display: flex;

            align-items: center;

            padding:
                0 16px;

            cursor: pointer;

            color: #35566a;

            font-size: 15px;

            transition:
                border-color 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease;
        }


        .role-option:hover {

            border-color: #1f7190;

            background: #fbfdfe;
        }


        .role-icon {

            font-size: 20px;

            color: #22627c;

            margin-right: 13px;
        }


        .role-text {

            flex: 1;
        }


        /* =====================================================
           RADIO CIRCLE
        ===================================================== */

        .radio-circle {

            width: 22px;

            height: 22px;

            border-radius: 50%;

            border:
                2px solid #bdd0da;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .radio-circle::after {

            content: "";

            width: 10px;

            height: 10px;

            border-radius: 50%;

            background: transparent;
        }


        /* =====================================================
           SELECTED ACCOUNT
        ===================================================== */

        .role-option-wrapper
        input:checked
        + .role-option {

            border-color: #1687ba;

            background: #fafdff;

            box-shadow:
                0 0 0 1px
                rgba(22, 135, 186, 0.15);
        }


        .role-option-wrapper
        input:checked
        + .role-option
        .radio-circle {

            border-color: #176b89;
        }


        .role-option-wrapper
        input:checked
        + .role-option
        .radio-circle::after {

            background: #176b89;
        }


        /* =====================================================
           FORM GROUP
        ===================================================== */

        .form-group {

            margin-bottom: 22px;
        }


        /* =====================================================
           INPUT WRAPPER
        ===================================================== */

        .input-wrapper {

            position: relative;
        }


        /* =====================================================
           INPUT
        ===================================================== */

        .form-control {

            width: 100%;

            height: 53px;

            padding:
                0 18px;

            border:
                1px solid #c7d5dc;

            border-radius: 6px;

            background: #ffffff;

            color: #294e62;

            font-size: 16px;

            outline: none;

            box-shadow: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }


        .form-control::placeholder {

            color: #678196;

            opacity: 1;
        }


        .form-control:focus {

            border-color: #1687ba;

            box-shadow:
                0 0 0 3px
                rgba(22, 135, 186, 0.10);
        }


        /* =====================================================
           PASSWORD INPUT
        ===================================================== */

        .password-input {

            padding-right: 55px;
        }


        /* =====================================================
           PASSWORD TOGGLE
        ===================================================== */

        .password-toggle {

            position: absolute;

            top: 50%;

            right: 17px;

            transform:
                translateY(-50%);

            border: none;

            background: transparent;

            padding: 4px;

            color: #3d6a81;

            font-size: 20px;

            cursor: pointer;
        }


        .password-toggle:hover {

            color: #155c77;
        }


        /* =====================================================
           SIGN IN BUTTON
        ===================================================== */

        .login-button {

            width: 100%;

            height: 59px;

            border: none;

            border-radius: 6px;

            background: #155e75;

            color: #ffffff;

            font-size: 20px;

            font-weight: 700;

            cursor: pointer;

            margin-top: 2px;

            transition:
                background 0.2s ease,
                transform 0.1s ease;
        }


        .login-button:hover {

            background: #104c60;
        }


        .login-button:active {

            transform:
                translateY(1px);
        }


        /* =====================================================
           REGISTER SECTION
        ===================================================== */

        .register-section {

            margin-top: 22px;

            padding-top: 18px;

            border-top:
                1px solid #cad7dd;

            text-align: center;
        }


        .register-section p {

            margin: 0;

            color: #173f53;

            font-size: 15px;

            line-height: 1.5;
        }


        .register-section a {

            color: #155e75;

            font-weight: 700;

            text-decoration: underline;

            margin-left: 6px;
        }


        .register-section a:hover {

            color: #0d4c62;
        }


        /* =====================================================
           BOTTOM RIGHT DOT PATTERN
        ===================================================== */

        .dot-pattern {

            position: absolute;

            right: 18px;

            bottom: 28px;

            width: 190px;

            height: 125px;

            background-image:
                radial-gradient(
                    #c4dfeb 2.5px,
                    transparent 2.5px
                );

            background-size:
                24px 20px;

            opacity: 0.9;

            pointer-events: none;
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 1100px) {

            .left-content {

                padding-left: 55px;

                padding-right: 45px;
            }


            .left-title {

                font-size: 43px;
            }


            .left-description {

                font-size: 16px;
            }


            .left-footer {

                left: 55px;

                bottom: 115px;
            }


            .right-panel {

                padding-left: 45px;

                padding-right: 45px;
            }


            .top-message {

                left: 45px;
            }


            .login-container {

                margin-top: 75px;
            }

        }


        /* =====================================================
           MOBILE VIEW
        ===================================================== */

        @media (max-width: 800px) {


            .login-page {

                display: block;

                overflow-x: hidden;

                overflow-y: visible;
            }


            /* ---------------------------------------------
               LEFT IMAGE SECTION
            --------------------------------------------- */

            .left-panel {

                width: 100%;

                min-height: 510px;

                display: block;

                align-items: initial;
            }


            .left-content {

                width: 100%;

                max-width: none;

                padding:
                    55px 35px 0;

                margin: 0;
            }


            .left-title {

                font-size: 40px;

                line-height: 1.12;

                letter-spacing: -0.7px;
            }


            .left-line {

                width: 42px;

                margin-top: 25px;

                margin-bottom: 20px;
            }


            .left-description {

                max-width: 520px;

                font-size: 16px;

                line-height: 1.65;
            }


            /* ---------------------------------------------
               LEFT FOOTER
            --------------------------------------------- */

            .left-footer {

                left: 35px;

                right: 35px;

                bottom: 48px;

                font-size: 12px;
            }


            .footer-divider {

                width: 38px;

                margin-bottom: 15px;
            }


            .footer-items {

                display: flex;

                flex-wrap: wrap;

                gap: 7px 9px;

                white-space: normal;

                line-height: 1.5;
            }


            /* ---------------------------------------------
               RIGHT LOGIN SECTION
            --------------------------------------------- */

            .right-panel {

                width: 100%;

                min-height: 700px;

                display: flex;

                align-items: flex-start;

                justify-content: center;

                padding:
                    75px 35px 60px;

                overflow: hidden;
            }


            /* ---------------------------------------------
               TOP DECORATION
            --------------------------------------------- */

            .top-decoration {

                top: -210px;

                right: -170px;

                width: 390px;

                height: 390px;

                border-width: 22px;

                opacity: 0.8;
            }


            .top-decoration-inner {

                top: -175px;

                right: -135px;

                width: 325px;

                height: 325px;

                opacity: 0.8;
            }


            /* ---------------------------------------------
               LEARNING MESSAGE
            --------------------------------------------- */

            .top-message {

                position: absolute;

                top: 28px;

                left: 35px;

                right: auto;

                width: 210px;

                font-size: 12px;

                line-height: 1.5;
            }


            .top-message-line {

                width: 42px;

                margin-top: 11px;
            }


            /* ---------------------------------------------
               LOGIN CONTAINER
            --------------------------------------------- */

            .login-container {

                width: 100%;

                max-width: 570px;

                margin-top: 72px;
            }


            /* ---------------------------------------------
               ACCOUNT TYPE
            --------------------------------------------- */

            .account-type {

                margin-bottom: 25px;
            }


            .role-options {

                grid-template-columns: 1fr;

                gap: 12px;
            }


            .role-option {

                height: 50px;

                padding:
                    0 15px;

                font-size: 15px;
            }


            .role-icon {

                font-size: 20px;

                margin-right: 13px;
            }


            .radio-circle {

                width: 20px;

                height: 20px;
            }


            .radio-circle::after {

                width: 9px;

                height: 9px;
            }


            /* ---------------------------------------------
               FORM GROUPS
            --------------------------------------------- */

            .form-group {

                margin-bottom: 21px;
            }


            .field-label {

                margin-bottom: 9px;

                font-size: 14px;
            }


            /* ---------------------------------------------
               INPUTS
            --------------------------------------------- */

            .form-control {

                width: 100%;

                height: 51px;

                font-size: 15px;

                padding-left: 15px;

                padding-right: 15px;
            }


            .password-input {

                padding-right: 52px;
            }


            /* ---------------------------------------------
               SIGN IN
            --------------------------------------------- */

            .login-button {

                height: 54px;

                font-size: 18px;

                margin-top: 2px;
            }


            /* ---------------------------------------------
               REGISTER
            --------------------------------------------- */

            .register-section {

                margin-top: 23px;

                padding-top: 18px;
            }


            .register-section p {

                font-size: 13px;

                line-height: 1.5;
            }


            /* ---------------------------------------------
               DOT PATTERN
            --------------------------------------------- */

            .dot-pattern {

                display: none;
            }

        }



        /* =====================================================
           SMALL MOBILE
        ===================================================== */

        @media (max-width: 520px) {


            /* ---------------------------------------------
               LEFT SIDE
            --------------------------------------------- */

            .left-panel {

                min-height: 520px;
            }


            .left-content {

                padding:
                    45px 25px 0;
            }


            .left-title {

                font-size: 33px;

                line-height: 1.12;

                letter-spacing: -0.5px;
            }


            .left-line {

                width: 38px;

                margin-top: 22px;

                margin-bottom: 18px;
            }


            .left-description {

                font-size: 14px;

                line-height: 1.65;

                max-width: 100%;
            }


            /* ---------------------------------------------
               LEFT FOOTER
            --------------------------------------------- */

            .left-footer {

                left: 25px;

                right: 25px;

                bottom: 35px;

                font-size: 10px;
            }


            .footer-divider {

                width: 35px;

                height: 1px;

                margin-bottom: 12px;
            }


            .footer-items {

                gap: 5px 7px;

                line-height: 1.4;
            }


            /* ---------------------------------------------
               RIGHT SIDE
            --------------------------------------------- */

            .right-panel {

                min-height: 680px;

                padding:
                    65px 25px 50px;
            }


            /* ---------------------------------------------
               DECORATION
            --------------------------------------------- */

            .top-decoration {

                top: -180px;

                right: -155px;

                width: 330px;

                height: 330px;

                border-width: 18px;
            }


            .top-decoration-inner {

                top: -150px;

                right: -120px;

                width: 275px;

                height: 275px;
            }


            /* ---------------------------------------------
               LEARNING MESSAGE
            --------------------------------------------- */

            .top-message {

                top: 24px;

                left: 25px;

                width: 190px;

                font-size: 11px;
            }


            .top-message-line {

                width: 38px;

                margin-top: 9px;
            }


            /* ---------------------------------------------
               LOGIN FORM
            --------------------------------------------- */

            .login-container {

                margin-top: 65px;
            }


            .account-type {

                margin-bottom: 23px;
            }


            .role-options {

                gap: 10px;
            }


            .role-option {

                height: 48px;

                padding:
                    0 13px;

                font-size: 14px;
            }


            .role-icon {

                font-size: 19px;

                margin-right: 11px;
            }


            .radio-circle {

                width: 19px;

                height: 19px;
            }


            .form-group {

                margin-bottom: 19px;
            }


            .field-label {

                font-size: 13px;

                margin-bottom: 8px;
            }


            .form-control {

                height: 49px;

                font-size: 14px;
            }


            .login-button {

                height: 52px;

                font-size: 17px;
            }


            .register-section {

                margin-top: 21px;

                padding-top: 16px;
            }


            .register-section p {

                font-size: 12px;
            }

        }

    </style>

</head>


<body>


<div class="login-page">


    <!-- =================================================
         LEFT IMAGE / BRANDING SIDE
    ================================================== -->

    <section class="left-panel">


        <div class="left-content">


            <h1 class="left-title">

                Food Process
                <br>

                Practical Learning
                <br>

                and Simulation System

            </h1>


            <div class="left-line"></div>


            <p class="left-description">

                Bridging practical knowledge and
                simulation for a safer, healthier
                food future.

            </p>


        </div>



        <!-- =================================================
             LEFT FOOTER
        ================================================== -->

        <div class="left-footer">


            <div class="footer-divider"></div>


            <div class="footer-items">

                <span>
                    Science
                </span>


                <span class="footer-separator">
                    |
                </span>


                <span>
                    Practice
                </span>


                <span class="footer-separator">
                    |
                </span>


                <span>
                    Food Safety
                </span>


                <span class="footer-separator">
                    |
                </span>


                <span>
                    Better Tomorrow
                </span>

            </div>


        </div>


    </section>



    <!-- =================================================
         RIGHT LOGIN SIDE
    ================================================== -->

    <section class="right-panel">


        <!-- TOP CIRCLE DECORATION -->

        <div class="top-decoration"></div>

        <div class="top-decoration-inner"></div>



        <!-- =================================================
             LEARNING MESSAGE
        ================================================== -->

        <div class="top-message">

            Learning today

            <br>

            for a healthier tomorrow.


            <div class="top-message-line"></div>

        </div>



        <!-- =================================================
             LOGIN FORM
        ================================================== -->

        <div class="login-container">


            <!-- ERROR MESSAGE -->

            <?php if (!empty($error)): ?>

                <div class="login-error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>



            <form method="POST">


                <!-- =================================================
                     ACCOUNT TYPE
                ================================================== -->

                <div class="account-type">


                    <label class="field-label">

                        Account Type

                    </label>


                    <div class="role-options">



                        <!-- STUDENT -->

                        <div class="role-option-wrapper">


                            <input
                                type="radio"
                                name="role"
                                id="student"
                                value="student"
                                checked
                            >


                            <label
                                for="student"
                                class="role-option"
                            >


                                <i
                                    class="bi bi-person-fill role-icon"
                                ></i>


                                <span class="role-text">

                                    Student

                                </span>


                                <span
                                    class="radio-circle"
                                ></span>


                            </label>


                        </div>



                        <!-- ADMINISTRATOR -->

                        <div class="role-option-wrapper">


                            <input
                                type="radio"
                                name="role"
                                id="admin"
                                value="admin"
                            >


                            <label
                                for="admin"
                                class="role-option"
                            >


                                <i
                                    class="bi bi-shield-fill role-icon"
                                ></i>


                                <span class="role-text">

                                    Administrator

                                </span>


                                <span
                                    class="radio-circle"
                                ></span>


                            </label>


                        </div>


                    </div>


                </div>



                <!-- =================================================
                     USERNAME
                ================================================== -->

                <div class="form-group">


                    <label
                        for="username"
                        class="field-label"
                    >

                        Username

                    </label>


                    <div class="input-wrapper">


                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-control"
                            placeholder="Enter your username"
                            autocomplete="username"
                            required
                        >


                    </div>


                </div>



                <!-- =================================================
                     PASSWORD
                ================================================== -->

                <div class="form-group">


                    <label
                        for="password"
                        class="field-label"
                    >

                        Password

                    </label>


                    <div class="input-wrapper">


                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control password-input"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >


                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            aria-label="Show password"
                        >


                            <i
                                class="bi bi-eye"
                                id="passwordIcon"
                            ></i>


                        </button>


                    </div>


                </div>



                <!-- =================================================
                     SIGN IN
                ================================================== -->

                <button
                    type="submit"
                    name="login"
                    class="login-button"
                >

                    Sign In

                </button>


            </form>



            <!-- =================================================
                 REGISTER
            ================================================== -->

            <div class="register-section">


                <p>

                    Don't have a student account?


                    <a href="register.php">

                        Register

                    </a>

                </p>


            </div>


        </div>



        <!-- =================================================
             DOT PATTERN
        ================================================== -->

        <div class="dot-pattern"></div>


    </section>


</div>



<!-- =====================================================
     PASSWORD SHOW / HIDE
====================================================== -->

<script>


    const passwordInput =
        document.getElementById("password");


    const passwordToggle =
        document.getElementById("passwordToggle");


    const passwordIcon =
        document.getElementById("passwordIcon");



    passwordToggle.addEventListener(
        "click",
        function () {


            if (
                passwordInput.type === "password"
            ) {


                passwordInput.type = "text";


                passwordIcon.classList.remove(
                    "bi-eye"
                );


                passwordIcon.classList.add(
                    "bi-eye-slash"
                );


                passwordToggle.setAttribute(
                    "aria-label",
                    "Hide password"
                );


            } else {


                passwordInput.type =
                    "password";


                passwordIcon.classList.remove(
                    "bi-eye-slash"
                );


                passwordIcon.classList.add(
                    "bi-eye"
                );


                passwordToggle.setAttribute(
                    "aria-label",
                    "Show password"
                );


            }

        }
    );

</script>


</body>

</html>