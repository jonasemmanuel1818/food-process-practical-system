<?php

require_once "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Check login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Get student name
|--------------------------------------------------------------------------
*/

$student_name =
    $_SESSION['user_name'] ??
    $_SESSION['name'] ??
    $_SESSION['studentName'] ??
    $_SESSION['full_name'] ??
    "Student";

/*
|--------------------------------------------------------------------------
| Mark Practical 2 as in progress
|--------------------------------------------------------------------------
| practical_progress does NOT have a unique key for user + practical.
| Therefore we use SELECT first, then UPDATE or INSERT.
|--------------------------------------------------------------------------
*/

$check_progress = $conn->prepare("
    SELECT id
    FROM practical_progress
    WHERE user_id = ?
      AND practical_number = 2
    LIMIT 1
");

if ($check_progress) {

    $check_progress->bind_param("i", $user_id);
    $check_progress->execute();
    $check_progress->store_result();

    if ($check_progress->num_rows > 0) {

        $check_progress->close();

        $update_progress = $conn->prepare("
            UPDATE practical_progress
            SET status = 'in_progress'
            WHERE user_id = ?
              AND practical_number = 2
        ");

        if ($update_progress) {
            $update_progress->bind_param("i", $user_id);
            $update_progress->execute();
            $update_progress->close();
        }

    } else {

        $check_progress->close();

        $insert_progress = $conn->prepare("
            INSERT INTO practical_progress
            (
                user_id,
                practical_number,
                status,
                started_at
            )
            VALUES (?, 2, 'in_progress', NOW())
        ");

        if ($insert_progress) {
            $insert_progress->bind_param("i", $user_id);
            $insert_progress->execute();
            $insert_progress->close();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Load Practical 2 activity progress
|--------------------------------------------------------------------------
*/

$activity_progress = [
    1 => false,
    2 => false,
    3 => false,
    4 => false,
    5 => false
];

$get_activities = $conn->prepare("
    SELECT activity_number, completed
    FROM practical_activity_progress
    WHERE user_id = ?
      AND practical_number = 2
");

if ($get_activities) {

    $get_activities->bind_param("i", $user_id);
    $get_activities->execute();

    $result = $get_activities->get_result();

    while ($row = $result->fetch_assoc()) {

        $activity_number = (int) $row['activity_number'];

        if (isset($activity_progress[$activity_number])) {

            $activity_progress[$activity_number] =
                ((int) $row['completed'] === 1);

        }
    }

    $get_activities->close();
}

/*
|--------------------------------------------------------------------------
| Count completed activities
|--------------------------------------------------------------------------
*/

$completed_count = 0;

foreach ($activity_progress as $completed) {

    if ($completed) {
        $completed_count++;
    }
}

$progress_percentage = ($completed_count / 5) * 100;

/*
|--------------------------------------------------------------------------
| Complete Practical 2
|--------------------------------------------------------------------------
*/

if (isset($_POST['complete_practical2'])) {

    $check_complete = $conn->prepare("
        SELECT id
        FROM practical_progress
        WHERE user_id = ?
          AND practical_number = 2
        LIMIT 1
    ");

    if ($check_complete) {

        $check_complete->bind_param("i", $user_id);
        $check_complete->execute();
        $check_complete->store_result();

        if ($check_complete->num_rows > 0) {

            $check_complete->close();

            $complete_update = $conn->prepare("
                UPDATE practical_progress
                SET
                    status = 'completed',
                    completed_at = NOW()
                WHERE user_id = ?
                  AND practical_number = 2
            ");

            if ($complete_update) {
                $complete_update->bind_param("i", $user_id);
                $complete_update->execute();
                $complete_update->close();
            }

        } else {

            $check_complete->close();

            $complete_insert = $conn->prepare("
                INSERT INTO practical_progress
                (
                    user_id,
                    practical_number,
                    status,
                    started_at,
                    completed_at
                )
                VALUES (?, 2, 'completed', NOW(), NOW())
            ");

            if ($complete_insert) {
                $complete_insert->bind_param("i", $user_id);
                $complete_insert->execute();
                $complete_insert->close();
            }
        }
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
        Practical 2 - Physical Separation
    </title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- Main System CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f8;
            color: #263238;
        }

        a {
            text-decoration: none;
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

            transition: 0.2s ease;
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

            font-size: 17px;

            font-weight: 600;
        }


        /* =====================================================
           MAIN CONTENT
        ===================================================== */

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
        ===================================================== */

        .practical-header {

            background: #ffffff;

            border: 1px solid #d9dee3;

            border-radius: 6px;

            padding: 28px 30px;

            margin-bottom: 20px;
        }


        .practical-badge {

            display: inline-block;

            background: #e7f0f3;

            color: #1f5f75;

            font-size: 10px;

            letter-spacing: 0.5px;

            padding: 7px 10px;

            border-radius: 4px;

            font-weight: 700;
        }


        .practical-header h1 {

            margin: 12px 0 6px;

            font-size: 27px;

            font-weight: 600;

            color: #263238;

            line-height: 1.3;
        }


        .practical-header p {

            margin: 0;

            color: #7b8790;

            font-size: 14px;
        }


        /* =====================================================
           PRACTICAL SECTIONS
        ===================================================== */

        .practical-section {

            background: #ffffff;

            border: 1px solid #d9dee3;

            border-radius: 6px;

            padding: 25px 28px;

            margin-bottom: 18px;
        }


        .practical-section h3 {

            margin: 0 0 16px;

            padding-bottom: 12px;

            border-bottom: 1px solid #e8ecef;

            font-size: 18px;

            font-weight: 600;

            color: #37474f;
        }


        .practical-section h4 {

            color: #455a64;

            font-size: 15px;

            font-weight: 600;

            margin-top: 18px;

            margin-bottom: 10px;
        }


        .practical-section p,
        .practical-section li {

            color: #596870;

            font-size: 14px;

            line-height: 1.65;
        }


        .practical-section li {

            margin-bottom: 8px;
        }


        .practical-section strong {

            color: #455a64;
        }


        /* =====================================================
           ACTIVITY LIST
        ===================================================== */

        .activity-list {

            display: flex;

            flex-direction: column;

            gap: 10px;

            margin-top: 15px;
        }


        .activity-item {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding: 15px;

            border: 1px solid #dce2e5;

            border-radius: 5px;

            background: #ffffff;

            cursor: pointer;

            transition: 0.15s ease;
        }


        .activity-item:hover {

            border-color: #b8c8cf;

            background: #f7f9fa;
        }


        .activity-item input[type="checkbox"] {

            width: 18px;

            height: 18px;

            margin-top: 2px;

            cursor: pointer;

            flex-shrink: 0;

            accent-color: #1f5f75;
        }


        .activity-item span {

            color: #596870;

            font-size: 14px;

            line-height: 1.5;
        }


        .activity-item.completed {

            background: #edf6f1;

            border-color: #c4dfd0;
        }


        .activity-item.completed span {

            color: #49675a;
        }


        /* =====================================================
           ACTIVITY SAVING
        ===================================================== */

        .activity-saving {

            font-size: 12px;

            color: #7b8790;

            margin-top: 12px;
        }


        /* =====================================================
           PROGRESS
        ===================================================== */

        .progress-wrapper {

            margin-top: 20px;
        }


        .progress-label {

            display: flex;

            justify-content: space-between;

            margin-bottom: 7px;

            font-size: 12px;

            color: #66757d;

            font-weight: 600;
        }


        .progress-bar-container {

            width: 100%;

            height: 8px;

            background: #e9edef;

            border-radius: 10px;

            overflow: hidden;
        }


        .progress-bar-fill {

            height: 100%;

            background: #1f5f75;

            border-radius: 10px;

            transition: width 0.3s ease;
        }


        /* =====================================================
           CALCULATION BOX
        ===================================================== */

        .calculation-box {

            padding: 18px;

            margin: 15px 0;

            border-radius: 5px;

            background: #f1f6f8;

            border: 1px solid #d8e5e9;

            border-left: 4px solid #1f5f75;
        }


        .formula {

            font-weight: 600;

            font-family: monospace;

            font-size: 15px;

            margin: 10px 0;

            color: #37474f;

            background: #ffffff;

            padding: 10px;

            border-radius: 4px;

            border: 1px solid #e2e8eb;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table-wrapper {

            overflow-x: auto;
        }


        .practical-table {

            width: 100%;

            border-collapse: collapse;

            margin-top: 15px;

            font-size: 13px;
        }


        .practical-table th,
        .practical-table td {

            padding: 10px;

            border: 1px solid #dce2e5;

            text-align: left;
        }


        .practical-table th {

            background: #f1f4f5;

            color: #455a64;

            font-weight: 600;
        }


        /* =====================================================
           SIMULATION BUTTON
        ===================================================== */

        .simulation-link {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            margin-top: 15px;

            padding: 10px 16px;

            border-radius: 4px;

            background: #1f5f75;

            color: #ffffff;

            font-size: 13px;

            font-weight: 600;

            transition: 0.2s ease;
        }


        .simulation-link:hover {

            background: #17495a;

            color: #ffffff;
        }


        /* =====================================================
           ACTION BUTTONS
        ===================================================== */

        .action-buttons {

            display: flex;

            flex-wrap: wrap;

            gap: 10px;

            margin-top: 25px;
        }


        .action-buttons button,
        .action-buttons a {

            border-radius: 4px;

            padding: 9px 15px;

            font-size: 13px;

            font-weight: 600;

            text-decoration: none;
        }


        .complete-btn {

            background: #1f5f75;

            color: #ffffff;

            border: 1px solid #1f5f75;

            transition: 0.2s ease;
        }


        .complete-btn:hover:not(:disabled) {

            background: #17495a;
        }


        .complete-btn:disabled {

            opacity: 0.5;

            cursor: not-allowed;
        }


        .secondary-btn {

            background: #ffffff;

            color: #1f5f75;

            border: 1px solid #1f5f75;
        }


        .secondary-btn:hover {

            background: #eef3f5;

            color: #17495a;
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .system-footer {

            background: #ffffff;

            border-top: 1px solid #d9dee3;

            color: #89949b;

            text-align: center;

            font-size: 11px;

            padding: 13px 15px;
        }


        .system-footer p {

            margin: 0;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

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


            .action-buttons {

                flex-direction: column;
            }


            .action-buttons button,
            .action-buttons a {

                width: 100%;

                text-align: center;
            }


            .practical-header h1 {

                font-size: 21px;
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

            <?= htmlspecialchars($student_name) ?>

        </div>


        <div class="user-avatar">

            <?= strtoupper(
                htmlspecialchars(
                    substr($student_name, 0, 1)
                )
            ) ?>

        </div>

    </div>

</header>



<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="page-content">

    <div class="practical-container">


        <!-- =================================================
             PRACTICAL HEADER
        ================================================== -->

        <div class="practical-header">

            <span class="practical-badge">

                PRACTICAL 2

            </span>


            <h1>

                Physical Separation:
                Centrifugation and Sieve Analysis

            </h1>


            <p>

                Study the principles and procedures of
                physical separation techniques used in
                food processing.

            </p>

        </div>



        <!-- =================================================
             INTRODUCTION
        ================================================== -->

        <section class="practical-section">

            <h3>

                Introduction

            </h3>


            <p>

                Physical separation is an important operation
                in food processing used to separate components
                of a mixture based on differences in their
                physical properties.

            </p>


            <p>

                In this practical, two physical separation
                techniques are studied:
                <strong>centrifugation</strong> and
                <strong>sieve analysis</strong>.

            </p>

        </section>



        <!-- =================================================
             OBJECTIVES
        ================================================== -->

        <section class="practical-section">

            <h3>

                Objectives

            </h3>


            <ul>

                <li>

                    To understand the principle of
                    centrifugation.

                </li>


                <li>

                    To understand the principle of
                    sieve analysis.

                </li>


                <li>

                    To perform physical separation using
                    appropriate equipment.

                </li>


                <li>

                    To carry out calculations associated
                    with physical separation.

                </li>


                <li>

                    To interpret experimental results.

                </li>

            </ul>

        </section>



        <!-- =================================================
             MATERIALS
        ================================================== -->

        <section class="practical-section">

            <h3>

                Materials and Equipment

            </h3>


            <h4>

                Centrifugation

            </h4>


            <ul>

                <li>
                    Centrifuge
                </li>

                <li>
                    Centrifuge tubes
                </li>

                <li>
                    Sample mixture
                </li>

                <li>
                    Measuring equipment
                </li>

            </ul>


            <h4>

                Sieve Analysis

            </h4>


            <ul>

                <li>
                    Set of sieves
                </li>

                <li>
                    Sieve shaker
                </li>

                <li>
                    Sample
                </li>

                <li>
                    Electronic balance
                </li>

                <li>
                    Collection pan
                </li>

            </ul>

        </section>



        <!-- =================================================
             PART A
        ================================================== -->

        <section class="practical-section">

            <h3>

                Part A: Centrifugation

            </h3>


            <h4>

                Principle

            </h4>


            <p>

                Centrifugation separates components of a
                mixture by applying centrifugal force.
                Particles with different densities or
                sizes move at different rates under
                centrifugal force.

            </p>


            <div class="calculation-box">

                <strong>

                    Relative Centrifugal Force (RCF)

                </strong>


                <div class="formula">

                    RCF = 1.118 × 10⁻⁵ × r × RPM²

                </div>


                <p>

                    Where:

                </p>


                <ul>

                    <li>

                        <strong>r</strong> =
                        radius of rotation in cm

                    </li>


                    <li>

                        <strong>RPM</strong> =
                        revolutions per minute

                    </li>

                </ul>

            </div>


            <h4>

                Procedure

            </h4>


            <ol>

                <li>

                    Prepare the sample mixture.

                </li>


                <li>

                    Transfer equal amounts of the sample
                    into centrifuge tubes.

                </li>


                <li>

                    Balance the centrifuge tubes.

                </li>


                <li>

                    Place the tubes into the centrifuge.

                </li>


                <li>

                    Set the required centrifugation speed
                    and time.

                </li>


                <li>

                    Start the centrifuge.

                </li>


                <li>

                    After centrifugation, carefully remove
                    the tubes.

                </li>


                <li>

                    Observe and record the separated
                    components.

                </li>

            </ol>

        </section>



        <!-- =================================================
             PART B
        ================================================== -->

        <section class="practical-section">

            <h3>

                Part B: Sieve Analysis

            </h3>


            <h4>

                Principle

            </h4>


            <p>

                Sieve analysis separates particles according
                to their size. Particles smaller than the
                sieve openings pass through while larger
                particles remain on the sieve.

            </p>


            <h4>

                Procedure

            </h4>


            <ol>

                <li>

                    Weigh the sample before sieving.

                </li>


                <li>

                    Arrange the sieves from the largest
                    opening at the top to the smallest
                    at the bottom.

                </li>


                <li>

                    Place the sample on the top sieve.

                </li>


                <li>

                    Secure the sieve stack in the sieve
                    shaker.

                </li>


                <li>

                    Operate the sieve shaker for the
                    required time.

                </li>


                <li>

                    Weigh the material retained on each
                    sieve.

                </li>


                <li>

                    Record and analyse the results.

                </li>

            </ol>

        </section>



        <!-- =================================================
             CALCULATIONS
        ================================================== -->

        <section class="practical-section">

            <h3>

                Calculations

            </h3>


            <div class="calculation-box">


                <p>

                    <strong>
                        Percentage retained:
                    </strong>

                </p>


                <div class="formula">

                    % Retained =
                    (Mass retained / Total sample mass)
                    × 100

                </div>


                <p>

                    <strong>
                        Cumulative percentage retained:
                    </strong>

                </p>


                <div class="formula">

                    Cumulative % retained =
                    Sum of percentage retained

                </div>


                <p>

                    <strong>
                        Percentage passing:
                    </strong>

                </p>


                <div class="formula">

                    % Passing =
                    100 − Cumulative % retained

                </div>

            </div>

        </section>



        <!-- =================================================
             SIMULATION
        ================================================== -->

        <section class="practical-section">

            <h3>

                Interactive Simulation

            </h3>


            <p>

                Use the Practical 2 simulation to explore
                physical separation and observe calculated
                results.

            </p>


            <a
                href="../simulations/simulation2.php"
                class="simulation-link"
            >

                <i class="bi bi-play-circle"></i>

                Open Practical 2 Simulation

            </a>

        </section>



        <!-- =================================================
             EXPECTED RESULTS
        ================================================== -->

        <section class="practical-section">

            <h3>

                Expected Results

            </h3>


            <p>

                After centrifugation, the sample should show
                separation of components according to their
                physical properties.

            </p>


            <p>

                During sieve analysis, different particle
                sizes should be retained on different sieves.
                The recorded masses can then be used to
                calculate percentage retained, cumulative
                percentage retained and percentage passing.

            </p>

        </section>



        <!-- =================================================
             PRECAUTIONS
        ================================================== -->

        <section class="practical-section">

            <h3>

                Precautions

            </h3>


            <ul>

                <li>

                    Always balance centrifuge tubes before
                    operating the centrifuge.

                </li>


                <li>

                    Do not open the centrifuge while it is
                    still rotating.

                </li>


                <li>

                    Handle laboratory equipment carefully.

                </li>


                <li>

                    Ensure sieves are properly arranged.

                </li>


                <li>

                    Avoid loss of sample during weighing
                    and transfer.

                </li>


                <li>

                    Follow all laboratory safety rules.

                </li>

            </ul>

        </section>



        <!-- =================================================
             ACTIVITY PROGRESS
        ================================================== -->

        <section class="practical-section">

            <h3>

                Practical 2 Activity Progress

            </h3>


            <p>

                Complete each activity as you work through
                the practical. Your progress is automatically
                saved.

            </p>


            <div class="activity-list">


                <!-- ACTIVITY 1 -->

                <label
                    class="activity-item
                    <?= $activity_progress[1] ? 'completed' : '' ?>"
                >

                    <input
                        type="checkbox"
                        class="activity-checkbox"
                        data-activity="1"
                        <?= $activity_progress[1] ? 'checked' : '' ?>
                    >


                    <span>

                        <strong>
                            Activity 1:
                        </strong>

                        Study the principle of
                        centrifugation.

                    </span>

                </label>



                <!-- ACTIVITY 2 -->

                <label
                    class="activity-item
                    <?= $activity_progress[2] ? 'completed' : '' ?>"
                >

                    <input
                        type="checkbox"
                        class="activity-checkbox"
                        data-activity="2"
                        <?= $activity_progress[2] ? 'checked' : '' ?>
                    >


                    <span>

                        <strong>
                            Activity 2:
                        </strong>

                        Perform or study the
                        centrifugation procedure.

                    </span>

                </label>



                <!-- ACTIVITY 3 -->

                <label
                    class="activity-item
                    <?= $activity_progress[3] ? 'completed' : '' ?>"
                >

                    <input
                        type="checkbox"
                        class="activity-checkbox"
                        data-activity="3"
                        <?= $activity_progress[3] ? 'checked' : '' ?>
                    >


                    <span>

                        <strong>
                            Activity 3:
                        </strong>

                        Perform the sieve analysis
                        procedure.

                    </span>

                </label>



                <!-- ACTIVITY 4 -->

                <label
                    class="activity-item
                    <?= $activity_progress[4] ? 'completed' : '' ?>"
                >

                    <input
                        type="checkbox"
                        class="activity-checkbox"
                        data-activity="4"
                        <?= $activity_progress[4] ? 'checked' : '' ?>
                    >


                    <span>

                        <strong>
                            Activity 4:
                        </strong>

                        Complete the physical separation
                        calculations.

                    </span>

                </label>



                <!-- ACTIVITY 5 -->

                <label
                    class="activity-item
                    <?= $activity_progress[5] ? 'completed' : '' ?>"
                >

                    <input
                        type="checkbox"
                        class="activity-checkbox"
                        data-activity="5"
                        <?= $activity_progress[5] ? 'checked' : '' ?>
                    >


                    <span>

                        <strong>
                            Activity 5:
                        </strong>

                        Complete the Practical 2
                        simulation.

                    </span>

                </label>

            </div>



            <div
                class="activity-saving"
                id="activityMessage"
            >

                Progress is saved automatically.

            </div>



            <!-- =================================================
                 PROGRESS BAR
            ================================================== -->

            <div class="progress-wrapper">

                <div class="progress-label">

                    <span>

                        Overall Progress

                    </span>


                    <span id="progressText">

                        <?= $completed_count ?> / 5

                    </span>

                </div>


                <div class="progress-bar-container">

                    <div
                        class="progress-bar-fill"
                        id="progressBar"
                        style="width: <?= $progress_percentage ?>%;"
                    ></div>

                </div>

            </div>



            <!-- =================================================
                 COMPLETE BUTTON
            ================================================== -->

            <form
                method="POST"
                class="action-buttons"
            >

                <button
                    type="submit"
                    name="complete_practical2"
                    class="complete-btn"
                    id="completeButton"
                    <?= $completed_count < 5 ? 'disabled' : '' ?>
                >

                    <i class="bi bi-check-circle me-1"></i>

                    Complete Practical 2

                </button>


                <a
                    href="../dashboard.php"
                    class="secondary-btn"
                >

                    <i class="bi bi-grid me-1"></i>

                    Back to Dashboard

                </a>


                <a
                    href="practical3.php"
                    class="secondary-btn"
                >

                    Next Practical

                    <i class="bi bi-arrow-right ms-1"></i>

                </a>

            </form>

        </section>


    </div>

</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="system-footer">

    <p>

        Food Process Engineering Practical Learning
        &amp; Simulation System

    </p>

</footer>



<!-- Bootstrap JS -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>



<script>

/*
|--------------------------------------------------------------------------
| Activity Progress
|--------------------------------------------------------------------------
*/

const activityCheckboxes =
    document.querySelectorAll(".activity-checkbox");


const progressBar =
    document.getElementById("progressBar");


const progressText =
    document.getElementById("progressText");


const completeButton =
    document.getElementById("completeButton");


const activityMessage =
    document.getElementById("activityMessage");



/*
|--------------------------------------------------------------------------
| Update visual progress
|--------------------------------------------------------------------------
*/

function updateProgress() {

    const totalActivities =
        activityCheckboxes.length;


    let completedActivities = 0;


    activityCheckboxes.forEach(function(checkbox) {

        const item =
            checkbox.closest(".activity-item");


        if (checkbox.checked) {

            completedActivities++;


            if (item) {

                item.classList.add("completed");

            }

        } else {

            if (item) {

                item.classList.remove("completed");

            }

        }

    });


    const percentage =
        totalActivities > 0
            ? (completedActivities / totalActivities) * 100
            : 0;


    progressBar.style.width =
        percentage + "%";


    progressText.textContent =
        completedActivities +
        " / " +
        totalActivities;


    /*
    |--------------------------------------------------------------------------
    | Enable Complete button only when all activities are done
    |--------------------------------------------------------------------------
    */

    completeButton.disabled =
        completedActivities !== totalActivities;

}



/*
|--------------------------------------------------------------------------
| Save activity progress
|--------------------------------------------------------------------------
*/

activityCheckboxes.forEach(function(checkbox) {

    checkbox.addEventListener(
        "change",
        async function() {

            const activityNumber =
                this.dataset.activity;


            const completed =
                this.checked ? 1 : 0;


            /*
            |--------------------------------------------------------------------------
            | Update screen immediately
            |--------------------------------------------------------------------------
            */

            updateProgress();


            activityMessage.textContent =
                "Saving progress...";


            const formData =
                new URLSearchParams();


            formData.append(
                "activity_number",
                activityNumber
            );


            formData.append(
                "completed",
                completed
            );


            try {

                const response =
                    await fetch(
                        "save_activity_progress2.php",
                        {
                            method: "POST",

                            headers: {
                                "Content-Type":
                                    "application/x-www-form-urlencoded"
                            },

                            body:
                                formData.toString()
                        }
                    );


                const data =
                    await response.json();


                if (data.success) {

                    activityMessage.textContent =
                        "Progress saved successfully.";

                } else {

                    activityMessage.textContent =
                        data.message ||
                        "Could not save progress.";

                }


            } catch (error) {

                console.error(
                    "Activity progress error:",
                    error
                );


                activityMessage.textContent =
                    "Unable to save progress. Check your connection.";

            }

        }
    );

});



/*
|--------------------------------------------------------------------------
| Initial progress calculation
|--------------------------------------------------------------------------
*/

updateProgress();

</script>


</body>

</html>