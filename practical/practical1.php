<?php

require_once "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================================
   CHECK LOGIN
   ========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   GET STUDENT NAME
   ========================================================= */

$user_name = $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? $_SESSION['studentName']
    ?? $_SESSION['full_name']
    ?? "Student";


/* =========================================================
   FINISH LABORATORY ORIENTATION
   ========================================================= */

if (isset($_POST['finish_orientation'])) {

    $stmt = $conn->prepare("
        INSERT INTO practical_progress
            (user_id, practical_number, status, started_at, completed_at)
        VALUES
            (?, 1, 'completed', NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            status = 'completed',
            completed_at = NOW()
    ");

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: ../dashboard.php");
    exit();
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
        Laboratory Orientation | Food Process Practical Learning System
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
           TOP HEADER
        ====================================================== */

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


        .header-right {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .back-dashboard {

            border: 1px solid #d2d9dd;

            color: #53636c;

            background: #ffffff;

            padding: 8px 12px;

            border-radius: 4px;

            font-size: 13px;

            font-weight: 600;

            transition: 0.15s ease;
        }


        .back-dashboard:hover {

            background: #eef3f5;

            color: #1f5f75;
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


        /* =====================================================
           MAIN CONTENT
        ====================================================== */

        .page-content {

            padding: 96px 30px 75px;

            min-height: 100vh;
        }


        .practical-container {

            max-width: 1100px;

            margin: 0 auto;
        }


        /* =====================================================
           PRACTICAL HEADER
        ====================================================== */

        .practical-header {

            background: #ffffff;

            border: 1px solid #d9dee3;

            border-radius: 6px;

            padding: 28px 30px;

            margin-bottom: 20px;
        }


        .practical-header .badge {

            background: #e7f0f3 !important;

            color: #1f5f75 !important;

            font-size: 10px;

            letter-spacing: 0.5px;

            padding: 7px 10px;
        }


        .practical-header h1 {

            margin: 12px 0 6px;

            font-size: 28px;

            font-weight: 600;

            color: #263238;
        }


        .practical-header p {

            margin: 0;

            color: #7b8790;

            font-size: 14px;
        }


        /* =====================================================
           PRACTICAL SECTIONS
        ====================================================== */

        .practical-section {

            background: #ffffff;

            border: 1px solid #d9dee3;

            border-radius: 6px;

            padding: 25px 28px;

            margin-bottom: 18px;
        }


        .practical-section h2 {

            display: flex;

            align-items: center;

            gap: 9px;

            margin: 0 0 16px;

            padding-bottom: 12px;

            border-bottom: 1px solid #e8ecef;

            font-size: 18px;

            font-weight: 600;

            color: #37474f;
        }


        .practical-section h2 i {

            color: #1f5f75;
        }


        .practical-section p,
        .practical-section li {

            color: #596870;

            font-size: 14px;

            line-height: 1.65;
        }


        .practical-section ul,
        .practical-section ol {

            margin-bottom: 0;
        }


        .practical-section li {

            margin-bottom: 8px;
        }


        /* =====================================================
           INFORMATION BOX
        ====================================================== */

        .info-box {

            background: #f1f6f8;

            border: 1px solid #d8e5e9;

            border-left: 4px solid #1f5f75;

            border-radius: 4px;

            padding: 17px 18px;
        }


        .info-box strong {

            color: #37474f;
        }


        /* =====================================================
           SAFETY BOX
        ====================================================== */

        .safety-box {

            background: #f1f6f8;

            border: 1px solid #d8e5e9;

            border-left: 4px solid #1f5f75;

            border-radius: 4px;

            padding: 17px 18px;
        }


        /* =====================================================
           TABLE
        ====================================================== */

        .table {

            margin-bottom: 0;

            font-size: 13px;
        }


        .table th {

            color: #455a64;

            font-weight: 600;
        }


        .table td {

            color: #596870;
        }


        /* =====================================================
           EMERGENCY BOX
        ====================================================== */

        .emergency-box {

            background: #fff8e8;

            border: 1px solid #eadfbd;

            border-left: 4px solid #b28a2e;

            border-radius: 4px;

            padding: 17px 18px;
        }


        .emergency-box p,
        .emergency-box li {

            color: #665b3f;
        }


        /* =====================================================
           FINISH SECTION
        ====================================================== */

        .finish-card {

            background: #1f5f75;

            color: #ffffff;

            border-radius: 6px;

            padding: 30px;

            margin: 22px 0;

            text-align: center;
        }


        .finish-icon {

            width: 48px;
            height: 48px;

            margin: 0 auto 12px;

            border-radius: 50%;

            background: rgba(255,255,255,0.14);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;
        }


        .finish-card h2 {

            font-size: 21px;

            margin-bottom: 9px;
        }


        .finish-card p {

            max-width: 760px;

            margin: 0 auto 20px;

            color: #e6f0f3;

            font-size: 14px;

            line-height: 1.6;
        }


        .finish-btn {

            color: #1f5f75;

            font-weight: 600;

            border-radius: 4px;

            padding: 10px 18px;
        }


        .finish-btn:hover {

            color: #17495a;
        }


        /* =====================================================
           NAVIGATION
        ====================================================== */

        .navigation-area {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            flex-wrap: wrap;

            margin-bottom: 25px;
        }


        .btn-outline-lab {

            border: 1px solid #1f5f75;

            color: #1f5f75;

            background: #ffffff;

            border-radius: 4px;

            padding: 9px 15px;

            font-size: 13px;

            font-weight: 600;
        }


        .btn-outline-lab:hover {

            background: #eef3f5;

            color: #17495a;
        }


        .btn-lab {

            border: 1px solid #1f5f75;

            background: #1f5f75;

            color: #ffffff;

            border-radius: 4px;

            padding: 9px 15px;

            font-size: 13px;

            font-weight: 600;
        }


        .btn-lab:hover {

            background: #17495a;

            color: #ffffff;
        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .system-footer {

            background: #ffffff;

            border-top: 1px solid #d9dee3;

            color: #89949b;

            text-align: center;

            font-size: 11px;

            padding: 13px 15px;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 768px) {

            .top-header {

                padding: 0 16px;
            }


            .brand-text {

                display: none;
            }


            .user-name {

                display: none;
            }


            .back-dashboard span {

                display: none;
            }


            .page-content {

                padding: 88px 15px 55px;
            }


            .practical-header {

                padding: 22px;
            }


            .practical-header h1 {

                font-size: 23px;
            }


            .practical-section {

                padding: 20px;
            }
        }


        @media (max-width: 576px) {

            .page-content {

                padding-left: 10px;

                padding-right: 10px;
            }


            .practical-section {

                padding: 17px;
            }


            .finish-card {

                padding: 24px 18px;
            }


            .navigation-area {

                align-items: stretch;
            }


            .navigation-area a {

                width: 100%;

                text-align: center;
            }
        }

    </style>

</head>


<body>


<!-- =========================================================
     TOP HEADER
========================================================== -->

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



    <div class="header-right">


        <a
            href="../dashboard.php"
            class="back-dashboard"
        >

            <i class="bi bi-arrow-left me-1"></i>

            <span>
                Back to Dashboard
            </span>

        </a>


        <div class="user-name">

            <?= htmlspecialchars($user_name) ?>

        </div>


        <div class="user-avatar">

            <i class="bi bi-person"></i>

        </div>


    </div>


</header>



<!-- =========================================================
     MAIN CONTENT
========================================================== -->

<main class="page-content">


    <div class="practical-container">


        <!-- =================================================
             PRACTICAL HEADER
        ================================================== -->

        <div class="practical-header">


            <span class="badge">

                PRACTICAL 1

            </span>


            <h1>

                Laboratory Orientation

            </h1>


            <p>

                Food Process Engineering Practical Learning System

            </p>


        </div>



        <!-- =================================================
             INTRODUCTION
        ================================================== -->

        <section class="practical-section">


            <h2>

                <i class="bi bi-info-circle"></i>

                Laboratory Orientation

            </h2>


            <p>

                Laboratory orientation provides students with the
                knowledge required to work safely and effectively
                in the food processing laboratory.

            </p>


            <p>

                Students should become familiar with the laboratory
                layout, available equipment, health and safety
                requirements, emergency procedures and the correct
                behaviour expected during practical sessions.

            </p>


            <div class="info-box">


                <strong>

                    Main Purpose

                </strong>


                <p class="mb-0 mt-2">

                    To prepare students to participate safely and
                    effectively in food processing laboratory practicals.

                </p>


            </div>


        </section>



        <!-- =================================================
             OBJECTIVES
        ================================================== -->

        <section class="practical-section">


            <h2>

                <i class="bi bi-bullseye"></i>

                Objectives

            </h2>


            <p>

                At the end of the orientation, the student should be
                able to:

            </p>


            <ul>

                <li>
                    Become familiar with the food processing laboratory.
                </li>

                <li>
                    Identify important laboratory health and safety equipment.
                </li>

                <li>
                    Understand laboratory safety and health rules.
                </li>

                <li>
                    Understand appropriate behaviour while conducting
                    practical work.
                </li>

                <li>
                    Become familiar with emergency procedures.
                </li>

            </ul>


        </section>



        <!-- =================================================
             HEALTH AND SAFETY EQUIPMENT
        ================================================== -->

        <section class="practical-section">


            <h2>

                <i class="bi bi-shield-check"></i>

                Health &amp; Safety Equipment

            </h2>


            <p>

                Students should identify and understand the purpose
                of the health and safety equipment available in the
                laboratory.

            </p>


            <div class="safety-box">


                <ul class="mb-0">

                    <li>
                        Laboratory coat
                    </li>

                    <li>
                        Protective gloves
                    </li>

                    <li>
                        Safety goggles
                    </li>

                    <li>
                        Appropriate protective footwear
                    </li>

                    <li>
                        First-aid equipment
                    </li>

                    <li>
                        Fire safety equipment
                    </li>

                    <li>
                        Emergency exits
                    </li>

                </ul>


            </div>


        </section>



        <!-- =================================================
             SAFETY RULES
        ================================================== -->

        <section class="practical-section">


            <h2>

                <i class="bi bi-exclamation-triangle"></i>

                Laboratory Safety &amp; Health Rules

            </h2>


            <ol>

                <li>
                    Wear the required personal protective equipment
                    during practical work.
                </li>

                <li>
                    Keep the working area clean and organized.
                </li>

                <li>
                    Follow instructions given by the laboratory instructor.
                </li>

                <li>
                    Do not operate laboratory equipment without
                    proper instruction.
                </li>

                <li>
                    Handle laboratory equipment carefully.
                </li>

                <li>
                    Report accidents, spills, damaged equipment or
                    unsafe conditions immediately.
                </li>

                <li>
                    Do not eat or drink in the laboratory.
                </li>

                <li>
                    Wash hands appropriately after practical work.
                </li>

                <li>
                    Dispose of laboratory waste according to the
                    laboratory instructions.
                </li>

                <li>
                    Know the location of emergency exits and
                    emergency equipment.
                </li>

            </ol>


        </section>



        <!-- =================================================
             PRACTICAL PROGRAMME
        ================================================== -->

        <section class="practical-section">


            <h2>

                <i class="bi bi-calendar3"></i>

                Practical Programme

            </h2>


            <div class="table-responsive">


                <table class="table table-bordered align-middle">


                    <thead class="table-light">

                        <tr>

                            <th>
                                Activity
                            </th>

                            <th>
                                Description
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <tr>

                            <td>
                                Laboratory Orientation
                            </td>

                            <td>
                                Introduction to the laboratory environment,
                                equipment, safety requirements and emergency
                                procedures.
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Physical Separation
                            </td>

                            <td>
                                Centrifugation and sieve analysis.
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Thermal Processing
                            </td>

                            <td>
                                Heat penetration, heating and cooling processes.
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Drying
                            </td>

                            <td>
                                Spray drying and freeze drying.
                            </td>

                        </tr>


                    </tbody>


                </table>


            </div>


        </section>



        <!-- =================================================
             EMERGENCY INFORMATION
        ================================================== -->

        <section class="practical-section">


            <h2>

                <i class="bi bi-telephone"></i>

                Emergency Telephone Numbers

            </h2>


            <div class="emergency-box">


                <p>

                    Record and keep the emergency telephone numbers
                    provided by your laboratory or institution.

                </p>


                <ul>


                    <li>

                        <strong>
                            Laboratory emergency contact:
                        </strong>

                        ______________________________

                    </li>


                    <li>

                        <strong>
                            Laboratory instructor:
                        </strong>

                        ______________________________

                    </li>


                    <li>

                        <strong>
                            First-aid contact:
                        </strong>

                        ______________________________

                    </li>


                    <li>

                        <strong>
                            Other emergency contact:
                        </strong>

                        ______________________________

                    </li>


                </ul>


            </div>


        </section>



        <!-- =================================================
             COMPLETION
        ================================================== -->

        <section class="finish-card">


            <div class="finish-icon">

                <i class="bi bi-check-circle"></i>

            </div>


            <h2 class="fw-bold">

                Finished the Orientation?

            </h2>


            <p>

                After reading and understanding the laboratory
                orientation, health and safety information, practical
                programme and emergency procedures, you can finish
                the orientation.

            </p>


            <form method="POST">


                <button
                    type="submit"
                    name="finish_orientation"
                    class="btn btn-light btn-lg finish-btn"
                >

                    <i class="bi bi-check2-circle me-1"></i>

                    I Have Finished the Orientation

                </button>


            </form>


        </section>



        <!-- =================================================
             NAVIGATION
        ================================================== -->

        <div class="navigation-area">


            <a
                href="../dashboard.php"
                class="btn-outline-lab"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Back to Dashboard

            </a>


            <a
                href="practical2.php"
                class="btn-lab"
            >

                Continue to Practical 2

                <i class="bi bi-arrow-right ms-1"></i>

            </a>


        </div>


    </div>


</main>



<!-- =========================================================
     FOOTER
========================================================== -->

<footer class="system-footer">

    Food Process Engineering Practical Learning System
    &copy; <?= date("Y") ?>

</footer>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>