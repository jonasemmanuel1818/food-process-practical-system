<?php

require_once "config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"]);
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($full_name === "" || $username === "" || $password === "") {

        $message = "Please fill in all fields.";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM users WHERE username = ?"
        );

        $check->bind_param("s", $username);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Username already exists.";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users
                (full_name, username, password)
                VALUES (?, ?, ?)"
            );

            $stmt->bind_param(
                "sss",
                $full_name,
                $username,
                $hashed_password
            );

            if ($stmt->execute()) {

                header("Location: login.php?registered=1");
                exit;

            } else {

                $message = "Registration failed.";
            }

            $stmt->close();
        }

        $check->close();
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
        Create Account | Food Process Practical Learning System
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

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            min-height: 100vh;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f4f6f8;

            color: #263238;

            display: flex;

            flex-direction: column;
        }


        /* =====================================================
           TOP HEADER
           ===================================================== */

        .top-header {

            height: 68px;

            background: #ffffff;

            border-bottom: 1px solid #d9dee3;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 30px;
        }


        .brand {

            display: flex;

            align-items: center;

            gap: 12px;

            color: #263238;
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


        .brand-text {

            font-size: 17px;

            font-weight: 600;
        }


        .brand-text small {

            display: block;

            color: #7b8790;

            font-size: 11px;

            font-weight: normal;

            margin-top: 2px;
        }


        .header-label {

            color: #7b8790;

            font-size: 12px;
        }


        /* =====================================================
           MAIN REGISTER AREA
           ===================================================== */

        .register-wrapper {

            flex: 1;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 35px 20px 55px;
        }


        .register-card {

            width: 100%;

            max-width: 470px;

            background: #ffffff;

            border: 1px solid #d9dee3;

            border-radius: 7px;

            padding: 32px 36px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.04);
        }


        /* =====================================================
           REGISTER HEADING
           ===================================================== */

        .register-heading {

            text-align: center;

            margin-bottom: 25px;
        }


        .register-icon {

            width: 58px;

            height: 58px;

            margin: 0 auto 15px;

            background: #edf4f6;

            color: #1f5f75;

            border-radius: 6px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;
        }


        .register-heading h1 {

            margin: 0 0 6px;

            color: #263238;

            font-size: 22px;

            font-weight: 600;
        }


        .register-heading p {

            margin: 0;

            color: #7b8790;

            font-size: 13px;

            line-height: 1.5;
        }


        /* =====================================================
           ERROR MESSAGE
           ===================================================== */

        .message {

            padding: 11px 13px;

            border-radius: 4px;

            margin-bottom: 19px;

            background: #faeeee;

            border: 1px solid #efd2d2;

            color: #963b3b;

            font-size: 12px;

            line-height: 1.5;

            display: flex;

            align-items: flex-start;

            gap: 8px;
        }


        /* =====================================================
           FORM
           ===================================================== */

        .form-group {

            margin-bottom: 16px;
        }


        .form-label {

            display: block;

            margin-bottom: 7px;

            color: #455a64;

            font-size: 13px;

            font-weight: 600;
        }


        .input-wrapper {

            position: relative;
        }


        .input-icon {

            position: absolute;

            left: 13px;

            top: 50%;

            transform: translateY(-50%);

            color: #8a969d;

            font-size: 16px;

            pointer-events: none;
        }


        .form-control {

            width: 100%;

            height: 44px;

            border: 1px solid #ccd4d8;

            border-radius: 4px;

            padding: 10px 13px 10px 40px;

            color: #263238;

            background: #ffffff;

            font-size: 13px;

            outline: none;

            transition:
                border-color 0.15s ease,
                box-shadow 0.15s ease;
        }


        .form-control::placeholder {

            color: #a0a9ae;
        }


        .form-control:focus {

            border-color: #1f5f75;

            box-shadow:
                0 0 0 2px rgba(31,95,117,0.10);
        }


        /* =====================================================
           CREATE ACCOUNT BUTTON
           ===================================================== */

        .btn-register {

            width: 100%;

            height: 44px;

            margin-top: 3px;

            border: 1px solid #1f5f75;

            border-radius: 4px;

            background: #1f5f75;

            color: #ffffff;

            font-size: 13px;

            font-weight: 600;

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            cursor: pointer;

            transition:
                background 0.15s ease;
        }


        .btn-register:hover {

            background: #17495a;
        }


        /* =====================================================
           LOGIN LINK
           ===================================================== */

        .login-area {

            text-align: center;

            margin-top: 21px;

            padding-top: 18px;

            border-top: 1px solid #edf0f2;
        }


        .login-area p {

            margin: 0;

            color: #7b8790;

            font-size: 12px;
        }


        .login-area a {

            color: #1f5f75;

            font-weight: 600;

            text-decoration: none;
        }


        .login-area a:hover {

            text-decoration: underline;
        }


        /* =====================================================
           SYSTEM NOTE
           ===================================================== */

        .system-note {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            margin-top: 17px;

            color: #89949b;

            font-size: 11px;
        }


        .system-note i {

            color: #1f5f75;
        }


        /* =====================================================
           FOOTER
           ===================================================== */

        .footer {

            height: 43px;

            background: #ffffff;

            border-top: 1px solid #d9dee3;

            display: flex;

            align-items: center;

            justify-content: center;

            color: #89949b;

            font-size: 11px;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 576px) {

            .top-header {

                padding: 0 18px;
            }


            .brand-text {

                font-size: 15px;
            }


            .brand-text small {

                display: none;
            }


            .header-label {

                display: none;
            }


            .register-wrapper {

                padding: 25px 15px 45px;
            }


            .register-card {

                padding: 28px 22px;
            }


            .register-heading h1 {

                font-size: 20px;
            }
        }

    </style>

</head>


<body>


<!-- =========================================================
     TOP HEADER
     ========================================================= -->

<header class="top-header">


    <div class="brand">

        <div class="brand-icon">

            <i class="bi bi-flask"></i>

        </div>


        <div class="brand-text">

            Food Process Practical Learning System

            <small>
                Laboratory Practical Management
            </small>

        </div>

    </div>


    <div class="header-label">

        Student Registration

    </div>


</header>



<!-- =========================================================
     REGISTER AREA
     ========================================================= -->

<main class="register-wrapper">


    <div class="register-card">


        <!-- REGISTER HEADING -->

        <div class="register-heading">


            <div class="register-icon">

                <i class="bi bi-person-plus"></i>

            </div>


            <h1>
                Create Student Account
            </h1>


            <p>
                Register to access laboratory practicals,
                simulations and results.
            </p>


        </div>



        <!-- ERROR MESSAGE -->

        <?php if ($message): ?>


            <div class="message">

                <i class="bi bi-exclamation-circle"></i>


                <span>

                    <?php
                    echo htmlspecialchars($message);
                    ?>

                </span>

            </div>


        <?php endif; ?>



        <!-- REGISTRATION FORM -->

        <form
            method="POST"
            autocomplete="on"
        >


            <!-- FULL NAME -->

            <div class="form-group">

                <label
                    for="full_name"
                    class="form-label"
                >

                    Full Name

                </label>


                <div class="input-wrapper">

                    <i
                        class="bi bi-person-vcard input-icon"
                    ></i>


                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        class="form-control"
                        placeholder="Enter your full name"
                        autocomplete="name"
                        required
                    >

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

                    <i
                        class="bi bi-person input-icon"
                    ></i>


                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Choose a username"
                        autocomplete="username"
                        required
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

                    <i
                        class="bi bi-lock input-icon"
                    ></i>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Create a password"
                        autocomplete="new-password"
                        required
                    >

                </div>

            </div>



            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label
                    for="confirm_password"
                    class="form-label"
                >

                    Confirm Password

                </label>


                <div class="input-wrapper">

                    <i
                        class="bi bi-lock-fill input-icon"
                    ></i>


                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Confirm your password"
                        autocomplete="new-password"
                        required
                    >

                </div>

            </div>



            <!-- CREATE ACCOUNT -->

            <button
                type="submit"
                class="btn-register"
            >

                <i class="bi bi-person-plus"></i>

                Create Account

            </button>


        </form>



        <!-- LOGIN LINK -->

        <div class="login-area">

            <p>

                Already have an account?

                <a href="login.php">
                    Login
                </a>

            </p>

        </div>



        <!-- SYSTEM NOTE -->

        <div class="system-note">

            <i class="bi bi-shield-check"></i>

            Laboratory learning environment

        </div>


    </div>


</main>



<!-- =========================================================
     FOOTER
     ========================================================= -->

<footer class="footer">

    Food Process Practical Learning System

    &nbsp; | &nbsp;

    Laboratory Practical Management

</footer>


</body>

</html>