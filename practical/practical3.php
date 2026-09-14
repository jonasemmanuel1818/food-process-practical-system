<?php

require_once "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* -------------------------------------------------
   CHECK LOGIN
------------------------------------------------- */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$practical_number = 3;


/* -------------------------------------------------
   GET STUDENT NAME
------------------------------------------------- */

$full_name = "Student";

$stmt = $conn->prepare("
    SELECT full_name
    FROM users
    WHERE id = ?
");

if ($stmt) {

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $full_name = $row['full_name'];
    }

    $stmt->close();
}


/* -------------------------------------------------
   MARK PRACTICAL 3 AS IN PROGRESS
------------------------------------------------- */

$stmt = $conn->prepare("
    SELECT id
    FROM practical_progress
    WHERE user_id = ?
      AND practical_number = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "ii",
        $user_id,
        $practical_number
    );

    $stmt->execute();

    $progress_result = $stmt->get_result();

    if ($progress_result->num_rows == 0) {

        $stmt->close();

        $stmt = $conn->prepare("
            INSERT INTO practical_progress
            (
                user_id,
                practical_number,
                status,
                started_at
            )
            VALUES (?, ?, 'in_progress', NOW())
        ");

        if ($stmt) {

            $stmt->bind_param(
                "ii",
                $user_id,
                $practical_number
            );

            $stmt->execute();
            $stmt->close();
        }

    } else {

        $stmt->close();

        /*
        Only change not_started to in_progress.
        Do not change completed back to in_progress.
        */

        $stmt = $conn->prepare("
            UPDATE practical_progress
            SET status = 'in_progress'
            WHERE user_id = ?
              AND practical_number = ?
              AND status = 'not_started'
        ");

        if ($stmt) {

            $stmt->bind_param(
                "ii",
                $user_id,
                $practical_number
            );

            $stmt->execute();
            $stmt->close();
        }
    }
}


/* -------------------------------------------------
   DEFAULT STUDENT RECORD
------------------------------------------------- */

$results_text = "";
$observations_text = "";
$conclusion_text = "";


/* -------------------------------------------------
   LOAD PREVIOUS SUBMISSION
------------------------------------------------- */

$stmt = $conn->prepare("
    SELECT
        results,
        observations,
        conclusion
    FROM practical_submissions
    WHERE user_id = ?
      AND practical_number = ?
    ORDER BY id DESC
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "ii",
        $user_id,
        $practical_number
    );

    $stmt->execute();

    $submission_result = $stmt->get_result();

    if ($row = $submission_result->fetch_assoc()) {

        $results_text =
            $row['results'] ?? "";

        $observations_text =
            $row['observations'] ?? "";

        $conclusion_text =
            $row['conclusion'] ?? "";
    }

    $stmt->close();
}


/* -------------------------------------------------
   LOAD ACTIVITY PROGRESS
------------------------------------------------- */

$activity_progress = [
    1 => false,
    2 => false,
    3 => false,
    4 => false,
    5 => false,
    6 => false,
    7 => false,
    8 => false,
    9 => false
];

$stmt = $conn->prepare("
    SELECT
        activity_number,
        completed
    FROM practical_activity_progress
    WHERE user_id = ?
      AND practical_number = 3
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $activity_result = $stmt->get_result();

    while ($row = $activity_result->fetch_assoc()) {

        $activity_number =
            (int) $row['activity_number'];

        if (isset($activity_progress[$activity_number])) {

            $activity_progress[$activity_number] =
                ((int) $row['completed'] === 1);
        }
    }

    $stmt->close();
}


/* -------------------------------------------------
   COUNT COMPLETED ACTIVITIES
------------------------------------------------- */

$completed_count = 0;

foreach ($activity_progress as $completed) {

    if ($completed) {
        $completed_count++;
    }
}

$total_activities = 9;

$progress_percentage =
    ($completed_count / $total_activities) * 100;


/* -------------------------------------------------
   SAVE STUDENT WORK
------------------------------------------------- */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['save_submission'])
) {

    $results_text =
        trim($_POST['results'] ?? "");

    $observations_text =
        trim($_POST['observations'] ?? "");

    $conclusion_text =
        trim($_POST['conclusion'] ?? "");


    /* Check whether submission already exists */

    $stmt = $conn->prepare("
        SELECT id
        FROM practical_submissions
        WHERE user_id = ?
          AND practical_number = ?
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $user_id,
            $practical_number
        );

        $stmt->execute();

        $check = $stmt->get_result();

        if ($check->num_rows > 0) {

            $existing =
                $check->fetch_assoc();

            $submission_id =
                (int) $existing['id'];

            $stmt->close();

            $stmt = $conn->prepare("
                UPDATE practical_submissions
                SET
                    results = ?,
                    observations = ?,
                    conclusion = ?,
                    submitted_at = NOW()
                WHERE id = ?
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "sssi",
                    $results_text,
                    $observations_text,
                    $conclusion_text,
                    $submission_id
                );

                $stmt->execute();
                $stmt->close();
            }

        } else {

            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO practical_submissions
                (
                    user_id,
                    practical_number,
                    results,
                    observations,
                    conclusion,
                    submitted_at
                )
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "iisss",
                    $user_id,
                    $practical_number,
                    $results_text,
                    $observations_text,
                    $conclusion_text
                );

                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header("Location: practical3.php?saved=1");
    exit;
}


/* -------------------------------------------------
   RESET PRACTICAL
------------------------------------------------- */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['reset_practical'])
) {

    /* Delete written submission */

    $stmt = $conn->prepare("
        DELETE FROM practical_submissions
        WHERE user_id = ?
          AND practical_number = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $user_id,
            $practical_number
        );

        $stmt->execute();
        $stmt->close();
    }


    /* Delete measurements */

    $stmt = $conn->prepare("
        DELETE FROM practical_measurements
        WHERE user_id = ?
          AND practical_number = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $user_id,
            $practical_number
        );

        $stmt->execute();
        $stmt->close();
    }


    /* Delete simulation results */

    $stmt = $conn->prepare("
        DELETE FROM simulation_results
        WHERE user_id = ?
          AND practical_number = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $user_id,
            $practical_number
        );

        $stmt->execute();
        $stmt->close();
    }


    /* Delete activity progress */

    $stmt = $conn->prepare("
        DELETE FROM practical_activity_progress
        WHERE user_id = ?
          AND practical_number = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $user_id,
            $practical_number
        );

        $stmt->execute();
        $stmt->close();
    }


    /* Reset practical progress */

    $stmt = $conn->prepare("
        UPDATE practical_progress
        SET
            status = 'not_started',
            started_at = NULL,
            completed_at = NULL
        WHERE user_id = ?
          AND practical_number = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $user_id,
            $practical_number
        );

        $stmt->execute();
        $stmt->close();
    }


    header("Location: practical3.php?reset=1");
    exit;
}


/* -------------------------------------------------
   COMPLETE PRACTICAL
------------------------------------------------- */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['complete_practical'])
) {

    $results_text =
        trim($_POST['results'] ?? "");

    $observations_text =
        trim($_POST['observations'] ?? "");

    $conclusion_text =
        trim($_POST['conclusion'] ?? "");


    /*
    Make sure all nine activities are completed
    before allowing completion.
    */

    $stmt = $conn->prepare("
        SELECT COUNT(*)
        AS completed_count
        FROM practical_activity_progress
        WHERE user_id = ?
          AND practical_number = 3
          AND completed = 1
    ");

    $all_completed = false;

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $all_completed =
                ((int) $row['completed_count'] >= 9);
        }

        $stmt->close();
    }


    if (!$all_completed) {

        header("Location: practical3.php?incomplete=1");
        exit;
    }


    /* Save written work */

    $stmt = $conn->prepare("
        SELECT id
        FROM practical_submissions
        WHERE user_id = ?
          AND practical_number = ?
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $user_id,
            $practical_number
        );

        $stmt->execute();

        $check = $stmt->get_result();

        if ($check->num_rows > 0) {

            $existing =
                $check->fetch_assoc();

            $submission_id =
                (int) $existing['id'];

            $stmt->close();

            $stmt = $conn->prepare("
                UPDATE practical_submissions
                SET
                    results = ?,
                    observations = ?,
                    conclusion = ?,
                    submitted_at = NOW()
                WHERE id = ?
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "sssi",
                    $results_text,
                    $observations_text,
                    $conclusion_text,
                    $submission_id
                );

                $stmt->execute();
                $stmt->close();
            }

        } else {

            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO practical_submissions
                (
                    user_id,
                    practical_number,
                    results,
                    observations,
                    conclusion,
                    submitted_at
                )
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "iisss",
                    $user_id,
                    $practical_number,
                    $results_text,
                    $observations_text,
                    $conclusion_text
                );

                $stmt->execute();
                $stmt->close();
            }
        }
    }


    /* Mark practical as completed */

    $stmt = $conn->prepare("
        UPDATE practical_progress
        SET
            status = 'completed',
            completed_at = NOW()
        WHERE user_id = ?
          AND practical_number = ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $user_id,
            $practical_number
        );

        $stmt->execute();
        $stmt->close();
    }


    header("Location: ../dashboard.php?completed=3");
    exit;
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
        Practical 3 - Thermal Processing
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


    <!-- System CSS -->

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
           ALERTS
        ===================================================== */

        .system-alert {

            border-radius: 5px;

            border: 1px solid;

            font-size: 14px;
        }


        /* =====================================================
           SECTIONS
        ===================================================== */

        .section-card {

            background: #ffffff;

            border: 1px solid #d9dee3;

            border-radius: 6px;

            padding: 25px 28px;

            margin-bottom: 18px;
        }


        .section-title {

            margin: 0 0 16px;

            padding-bottom: 12px;

            border-bottom: 1px solid #e8ecef;

            font-size: 18px;

            font-weight: 600;

            color: #37474f;
        }


        .section-title i {

            color: #1f5f75;

            margin-right: 7px;
        }


        .section-card p {

            color: #596870;

            font-size: 14px;

            line-height: 1.7;
        }


        .section-card li {

            color: #596870;

            font-size: 14px;

            line-height: 1.6;

            margin-bottom: 7px;
        }


        /* =====================================================
           OBJECTIVES
        ===================================================== */

        .objective-item {

            border-color: #e1e6e9 !important;

            color: #596870;

            font-size: 14px;

            padding: 11px 14px;
        }


        /* =====================================================
           MATERIALS
        ===================================================== */

        .material-item {

            margin-bottom: 6px;
        }


        .precaution-item {

            margin-bottom: 7px;
        }


        /* =====================================================
           EXPECTED RESULT
        ===================================================== */

        .expected-result {

            background: #edf6f1;

            border-left: 4px solid #198754;

            padding: 15px;

            border-radius: 4px;
        }


        .expected-result strong {

            color: #276749;
        }


        /* =====================================================
           CALCULATION BOX
        ===================================================== */

        .calculation-box {

            background: #f1f6f8;

            border: 1px solid #d8e5e9;

            border-left: 4px solid #1f5f75;

            padding: 18px;

            border-radius: 5px;

            margin-top: 15px;
        }


        .calculation-box h6 {

            color: #37474f;
        }


        .calculation-box p {

            margin-bottom: 0;

            color: #596870;
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table {

            font-size: 13px;
        }


        .table-primary {

            --bs-table-bg: #e8f0f3;

            --bs-table-color: #37474f;
        }


        .table td,
        .table th {

            vertical-align: middle;
        }


        /* =====================================================
           SIMULATION
        ===================================================== */

        .simulation-card {

            background: #ffffff;

            border: 1px solid #d9dee3;

            border-radius: 6px;

            padding: 25px 28px;

            margin-bottom: 18px;
        }


        .btn-primary {

            background: #1f5f75;

            border-color: #1f5f75;
        }


        .btn-primary:hover {

            background: #17495a;

            border-color: #17495a;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-label {

            color: #455a64;

            font-size: 14px;
        }


        textarea {

            min-height: 150px;

            resize: vertical;
        }


        .form-control {

            border-color: #d5dde1;

            border-radius: 4px;

            font-size: 14px;
        }


        .form-control:focus {

            border-color: #1f5f75;

            box-shadow:
                0 0 0 0.15rem
                rgba(31, 95, 117, 0.12);
        }


        /* =====================================================
           PROGRESS
        ===================================================== */

        .progress-card {

            background: #ffffff;

            border: 1px solid #d9dee3;

            border-radius: 6px;

            padding: 25px 28px;

            margin-bottom: 18px;
        }


        .progress-check {

            margin-bottom: 12px;
        }


        .progress-item {

            cursor: pointer;

            accent-color: #1f5f75;
        }


        .progress-check label {

            color: #596870;

            font-size: 14px;

            cursor: pointer;
        }


        .progress {

            background: #e9edef;

            border-radius: 5px;
        }


        .progress-bar {

            background: #1f5f75;

            font-size: 12px;

            font-weight: 600;
        }


        .activity-status {

            font-size: 13px;

            color: #64748b;

            margin-top: 15px;
        }


        /* =====================================================
           COMPLETE / RESET
        ===================================================== */

        .complete-button {

            border-radius: 4px;

            padding: 10px 18px;

            font-weight: 600;
        }


        .reset-button {

            border-radius: 4px;

            padding: 9px 16px;

            font-size: 13px;

            font-weight: 600;
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


            .section-card,
            .progress-card,
            .simulation-card {

                padding: 20px;
            }
        }


        @media (max-width: 576px) {

            .page-content {

                padding-left: 10px;

                padding-right: 10px;
            }


            .section-card,
            .progress-card,
            .simulation-card {

                padding: 17px;
            }


            .practical-header h1 {

                font-size: 21px;
            }


            .action-mobile {

                width: 100%;
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

            <?= htmlspecialchars($full_name) ?>

        </div>


        <div class="user-avatar">

            <?= strtoupper(
                htmlspecialchars(
                    substr($full_name, 0, 1)
                )
            ) ?>

        </div>

    </div>

</header>



<!-- =========================================================
     MAIN
========================================================= -->

<main class="page-content">

    <div class="practical-container">


        <!-- =================================================
             PRACTICAL HEADER
        ================================================== -->

        <div class="practical-header">

            <span class="practical-badge">

                PRACTICAL 3

            </span>


            <h1>

                Thermal Processing

            </h1>


            <p>

                Heat Penetration and Cold Point Determination

            </p>

        </div>



        <!-- =================================================
             SUCCESS MESSAGE
        ================================================== -->

        <?php if (isset($_GET['saved'])): ?>

            <div
                class="alert alert-success system-alert alert-dismissible fade show"
                role="alert"
            >

                <i class="bi bi-check-circle-fill me-2"></i>

                Your practical work has been saved successfully.

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>



        <!-- =================================================
             RESET MESSAGE
        ================================================== -->

        <?php if (isset($_GET['reset'])): ?>

            <div
                class="alert alert-warning system-alert alert-dismissible fade show"
                role="alert"
            >

                <i class="bi bi-arrow-counterclockwise me-2"></i>

                Practical 3 has been reset.

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>



        <!-- =================================================
             INCOMPLETE MESSAGE
        ================================================== -->

        <?php if (isset($_GET['incomplete'])): ?>

            <div
                class="alert alert-danger system-alert alert-dismissible fade show"
                role="alert"
            >

                <i class="bi bi-exclamation-triangle-fill me-2"></i>

                Please complete all Practical 3 activities
                before completing the practical.

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                ></button>

            </div>

        <?php endif; ?>



        <!-- =================================================
             WELCOME
        ================================================== -->

        <div class="section-card">

            <h3 class="section-title">

                <i class="bi bi-person-workspace"></i>

                Welcome,
                <?= htmlspecialchars($full_name) ?>

            </h3>


            <p>

                This practical introduces you to thermal processing,
                heat penetration, determination of the cold point and
                interpretation of temperature-time data.

            </p>


            <div class="alert alert-info mb-0">

                <i class="bi bi-info-circle-fill me-1"></i>

                <strong>Important:</strong>

                Use your actual laboratory readings when performing
                calculations and recording results.

            </div>

        </div>



        <!-- =================================================
             INTRODUCTION
        ================================================== -->

        <div class="section-card">

            <h3 class="section-title">

                <i class="bi bi-book"></i>

                1. Introduction

            </h3>


            <p>

                Thermal processing is a food preservation method in which
                food is heated to a specified temperature for a specified
                period of time to reduce microorganisms and improve product
                safety and shelf stability.

            </p>


            <p>

                During thermal processing, heat must penetrate from the
                heating medium into the food product. The point within the
                product that heats most slowly is known as the
                <strong>cold point</strong>.

            </p>


            <p class="mb-0">

                Determining the cold point is important because it helps
                evaluate whether the entire product receives adequate
                thermal treatment.

            </p>

        </div>



        <!-- =================================================
             OBJECTIVES
        ================================================== -->

        <div class="section-card">

            <h3 class="section-title">

                <i class="bi bi-bullseye"></i>

                2. Objectives

            </h3>


            <ul class="list-group">

                <li class="list-group-item objective-item">

                    Explain the concept of thermal processing.

                </li>


                <li class="list-group-item objective-item">

                    Understand the concept of the cold point.

                </li>


                <li class="list-group-item objective-item">

                    Determine the cold point of a processed product.

                </li>


                <li class="list-group-item objective-item">

                    Determine the heating curve of the product.

                </li>


                <li class="list-group-item objective-item">

                    Interpret temperature-time data.

                </li>


                <li class="list-group-item objective-item">

                    Perform basic thermal processing calculations.

                </li>

            </ul>

        </div>



        <!-- =================================================
             MATERIALS
        ================================================== -->

        <div class="section-card">

            <h3 class="section-title">

                <i class="bi bi-tools"></i>

                3. Materials and Methods

            </h3>


            <h5 class="fw-bold mt-3">

                Materials

            </h5>


            <ul>

                <li class="material-item">
                    Retort
                </li>

                <li class="material-item">
                    Food sample containers
                </li>

                <li class="material-item">
                    Thermocouples or temperature sensors
                </li>

                <li class="material-item">
                    Data logger
                </li>

                <li class="material-item">
                    Stopwatch
                </li>

                <li class="material-item">
                    Retort baskets or holders
                </li>

                <li class="material-item">
                    Measuring instruments
                </li>

                <li class="material-item">
                    Personal protective equipment
                </li>

            </ul>


            <h5 class="fw-bold mt-4">

                Methods

            </h5>


            <ol>

                <li>
                    Place the food samples inside the retort.
                </li>

                <li>
                    Position the temperature sensors at the required
                    locations inside the product.
                </li>

                <li>
                    Operate the retort according to the laboratory procedure.
                </li>

                <li>
                    Record product temperature at regular time intervals.
                </li>

                <li>
                    Compare temperature readings from different locations.
                </li>

                <li>
                    Identify the location that heats most slowly as the
                    cold point.
                </li>

            </ol>

        </div>



        <!-- =================================================
             PRECAUTIONS
        ================================================== -->

        <div class="section-card">

            <h3 class="section-title">

                <i class="bi bi-shield-check"></i>

                4. Precautions

            </h3>


            <ul>

                <li class="precaution-item">
                    Wear appropriate personal protective equipment.
                </li>

                <li class="precaution-item">
                    Follow all laboratory instructions.
                </li>

                <li class="precaution-item">
                    Do not operate the retort without proper instruction.
                </li>

                <li class="precaution-item">
                    Handle hot equipment carefully.
                </li>

                <li class="precaution-item">
                    Ensure temperature sensors are correctly positioned.
                </li>

                <li class="precaution-item">
                    Record temperature readings accurately.
                </li>

                <li class="precaution-item">
                    Do not touch hot surfaces directly.
                </li>

                <li class="precaution-item">
                    Report damaged or malfunctioning equipment to the
                    instructor.
                </li>

            </ul>

        </div>



        <!-- =================================================
             COLD POINT
        ================================================== -->

        <div class="section-card">

            <h3 class="section-title">

                <i class="bi bi-thermometer-half"></i>

                5. Finding the Cold Point of the Retort

            </h3>


            <h5 class="fw-bold">

                Procedure

            </h5>


            <ol>

                <li>
                    Place temperature sensors at different positions
                    within the food sample.
                </li>

                <li>
                    Start the thermal processing operation.
                </li>

                <li>
                    Record the temperature of each sensor at regular
                    intervals.
                </li>

                <li>
                    Compare the temperature-time profiles.
                </li>

                <li>
                    Identify the position with the lowest temperature
                    or slowest heating response.
                </li>

                <li>
                    Record this position as the cold point.
                </li>

            </ol>


            <div class="expected-result mt-3">

                <strong>

                    <i class="bi bi-check-circle me-1"></i>

                    Expected Result

                </strong>


                <p class="mb-0 mt-2">

                    The cold point should be identified as the location
                    within the product that heats most slowly during
                    thermal processing.

                </p>

            </div>

        </div>



        <!-- =================================================
             HEATING CURVE
        ================================================== -->

        <div class="section-card">

            <h3 class="section-title">

                <i class="bi bi-graph-up"></i>

                6. Determining the Heating Curve

            </h3>


            <h5 class="fw-bold">

                Procedure

            </h5>


            <ol>

                <li>
                    Record the initial product temperature.
                </li>

                <li>
                    Record the retort temperature.
                </li>

                <li>
                    Record product temperature at regular time intervals.
                </li>

                <li>
                    Plot product temperature against processing time.
                </li>

                <li>
                    Examine the resulting heating curve.
                </li>

            </ol>


            <h5 class="fw-bold mt-4">

                Sample Temperature Table

            </h5>


            <div class="table-responsive">

                <table class="table table-bordered table-striped">

                    <thead class="table-primary">

                        <tr>

                            <th>
                                Time
                            </th>

                            <th>
                                Product Temperature
                            </th>

                            <th>
                                Retort Temperature
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr>

                            <td>
                                0 min
                            </td>

                            <td>
                                Initial reading
                            </td>

                            <td>
                                Initial reading
                            </td>

                        </tr>


                        <tr>

                            <td>
                                5 min
                            </td>

                            <td>
                                Record reading
                            </td>

                            <td>
                                Record reading
                            </td>

                        </tr>


                        <tr>

                            <td>
                                10 min
                            </td>

                            <td>
                                Record reading
                            </td>

                            <td>
                                Record reading
                            </td>

                        </tr>


                        <tr>

                            <td>
                                15 min
                            </td>

                            <td>
                                Record reading
                            </td>

                            <td>
                                Record reading
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>



        <!-- =================================================
             CALCULATIONS
        ================================================== -->

        <div class="section-card">

            <h3 class="section-title">

                <i class="bi bi-calculator"></i>

                7. Calculations

            </h3>


            <div class="calculation-box">

                <h6 class="fw-bold">

                    Heating Rate

                </h6>


                <p>

                    Heating Rate =

                    (Final Temperature − Initial Temperature)

                    ÷ Processing Time

                </p>

            </div>


            <div class="calculation-box">

                <h6 class="fw-bold">

                    Temperature Difference

                </h6>


                <p>

                    Temperature Difference =

                    Retort Temperature − Product Temperature

                </p>

            </div>


            <div class="alert alert-warning mt-3 mb-0">

                <i class="bi bi-exclamation-triangle me-1"></i>

                Use your actual laboratory readings when carrying out
                the calculations.

            </div>

        </div>



        <!-- =================================================
             SIMULATION
        ================================================== -->

        <div class="simulation-card">

            <div
                class="d-flex justify-content-between
                align-items-center flex-wrap"
            >

                <div>

                    <h3 class="section-title mb-1">

                        <i class="bi bi-cpu"></i>

                        8. Thermal Processing Simulation

                    </h3>


                    <p class="text-muted mb-0">

                        Open the interactive simulation to study the
                        temperature profile and heating/cooling behaviour.

                    </p>

                </div>


                <a
                    href="../simulations/simulation3.php"
                    class="btn btn-primary mt-3 mt-md-0"
                >

                    <i class="bi bi-play-circle me-1"></i>

                    Open Simulation

                </a>

            </div>

        </div>



        <!-- =================================================
             STUDENT RECORD
        ================================================== -->

        <div class="section-card">

            <h3 class="section-title">

                <i class="bi bi-pencil-square"></i>

                9. Student Practical Record

            </h3>


            <form method="POST">


                <div class="mb-4">

                    <label class="form-label fw-bold">

                        Results

                    </label>


                    <textarea
                        name="results"
                        class="form-control"
                        placeholder="Enter your experimental results here..."
                    ><?= htmlspecialchars($results_text) ?></textarea>

                </div>



                <div class="mb-4">

                    <label class="form-label fw-bold">

                        Observations

                    </label>


                    <textarea
                        name="observations"
                        class="form-control"
                        placeholder="Enter your observations here..."
                    ><?= htmlspecialchars($observations_text) ?></textarea>

                </div>



                <div class="mb-4">

                    <label class="form-label fw-bold">

                        Conclusion

                    </label>


                    <textarea
                        name="conclusion"
                        class="form-control"
                        placeholder="Write your conclusion here..."
                    ><?= htmlspecialchars($conclusion_text) ?></textarea>

                </div>



                <button
                    type="submit"
                    name="save_submission"
                    class="btn btn-success"
                >

                    <i class="bi bi-save me-1"></i>

                    Save My Progress

                </button>

            </form>

        </div>



        <!-- =================================================
             PROGRESS
        ================================================== -->

        <div class="progress-card">

            <h3 class="section-title">

                <i class="bi bi-list-check"></i>

                10. Practical 3 Progress

            </h3>


            <p class="text-muted">

                Tick each item after studying or completing it.
                Your progress is saved automatically.

            </p>



            <!-- PROGRESS BAR -->

            <div
                class="progress mb-4"
                style="height: 25px;"
            >

                <div
                    id="progressBar"
                    class="progress-bar"
                    role="progressbar"
                    style="width: <?= $progress_percentage ?>%;"
                >

                    <span id="progressText">

                        <?= round($progress_percentage) ?>%

                    </span>

                </div>

            </div>



            <!-- ACTIVITY 1 -->

            <div class="form-check progress-check">

                <input
                    class="form-check-input progress-item"
                    type="checkbox"
                    id="step1"
                    data-activity="1"
                    <?= $activity_progress[1] ? 'checked' : '' ?>
                >


                <label
                    class="form-check-label"
                    for="step1"
                >

                    Read the introduction

                </label>

            </div>



            <!-- ACTIVITY 2 -->

            <div class="form-check progress-check">

                <input
                    class="form-check-input progress-item"
                    type="checkbox"
                    id="step2"
                    data-activity="2"
                    <?= $activity_progress[2] ? 'checked' : '' ?>
                >


                <label
                    class="form-check-label"
                    for="step2"
                >

                    Study the objectives

                </label>

            </div>



            <!-- ACTIVITY 3 -->

            <div class="form-check progress-check">

                <input
                    class="form-check-input progress-item"
                    type="checkbox"
                    id="step3"
                    data-activity="3"
                    <?= $activity_progress[3] ? 'checked' : '' ?>
                >


                <label
                    class="form-check-label"
                    for="step3"
                >

                    Review materials and methods

                </label>

            </div>



            <!-- ACTIVITY 4 -->

            <div class="form-check progress-check">

                <input
                    class="form-check-input progress-item"
                    type="checkbox"
                    id="step4"
                    data-activity="4"
                    <?= $activity_progress[4] ? 'checked' : '' ?>
                >


                <label
                    class="form-check-label"
                    for="step4"
                >

                    Study precautions

                </label>

            </div>



            <!-- ACTIVITY 5 -->

            <div class="form-check progress-check">

                <input
                    class="form-check-input progress-item"
                    type="checkbox"
                    id="step5"
                    data-activity="5"
                    <?= $activity_progress[5] ? 'checked' : '' ?>
                >


                <label
                    class="form-check-label"
                    for="step5"
                >

                    Study the cold point procedure

                </label>

            </div>



            <!-- ACTIVITY 6 -->

            <div class="form-check progress-check">

                <input
                    class="form-check-input progress-item"
                    type="checkbox"
                    id="step6"
                    data-activity="6"
                    <?= $activity_progress[6] ? 'checked' : '' ?>
                >


                <label
                    class="form-check-label"
                    for="step6"
                >

                    Study the heating curve procedure

                </label>

            </div>



            <!-- ACTIVITY 7 -->

            <div class="form-check progress-check">

                <input
                    class="form-check-input progress-item"
                    type="checkbox"
                    id="step7"
                    data-activity="7"
                    <?= $activity_progress[7] ? 'checked' : '' ?>
                >


                <label
                    class="form-check-label"
                    for="step7"
                >

                    Review calculations

                </label>

            </div>



            <!-- ACTIVITY 8 -->

            <div class="form-check progress-check">

                <input
                    class="form-check-input progress-item"
                    type="checkbox"
                    id="step8"
                    data-activity="8"
                    <?= $activity_progress[8] ? 'checked' : '' ?>
                >


                <label
                    class="form-check-label"
                    for="step8"
                >

                    Record experimental results and observations

                </label>

            </div>



            <!-- ACTIVITY 9 -->

            <div class="form-check progress-check">

                <input
                    class="form-check-input progress-item"
                    type="checkbox"
                    id="step9"
                    data-activity="9"
                    <?= $activity_progress[9] ? 'checked' : '' ?>
                >


                <label
                    class="form-check-label"
                    for="step9"
                >

                    Write the conclusion

                </label>

            </div>



            <div
                id="activityStatus"
                class="activity-status"
            >

                Progress is saved automatically.

            </div>


            <hr class="my-4">



            <!-- COMPLETE FORM -->

            <form
                method="POST"
                id="completeForm"
            >

                <input
                    type="hidden"
                    name="results"
                    id="completeResults"
                >


                <input
                    type="hidden"
                    name="observations"
                    id="completeObservations"
                >


                <input
                    type="hidden"
                    name="conclusion"
                    id="completeConclusion"
                >


                <button
                    type="submit"
                    name="complete_practical"
                    id="completeButton"
                    class="btn btn-primary btn-lg complete-button"
                    <?= $completed_count === 9 ? '' : 'disabled' ?>
                >

                    <i class="bi bi-check-circle me-1"></i>

                    Complete Practical 3

                </button>


                <p
                    id="completionMessage"
                    class="text-muted mt-2 mb-0"
                >

                    Complete all progress items before finishing
                    the practical.

                </p>

            </form>



            <!-- RESET -->

            <form
                method="POST"
                class="mt-3"
                onsubmit="return confirm('Are you sure you want to reset Practical 3? Your saved work, measurements, simulation results and activity progress will be deleted.');"
            >

                <button
                    type="submit"
                    name="reset_practical"
                    class="btn btn-outline-danger reset-button"
                >

                    <i class="bi bi-arrow-counterclockwise me-1"></i>

                    Reset Practical

                </button>

            </form>

        </div>



        <!-- =================================================
             BACK TO DASHBOARD
        ================================================== -->

        <div class="text-center mb-5">

            <a
                href="../dashboard.php"
                class="btn btn-secondary"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Back to Dashboard

            </a>

        </div>


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



<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>



<script>

/* -------------------------------------------------
   PRACTICAL 3 ACTIVITY PROGRESS
------------------------------------------------- */

const progressItems =
    document.querySelectorAll(".progress-item");


const progressBar =
    document.getElementById("progressBar");


const progressText =
    document.getElementById("progressText");


const completeButton =
    document.getElementById("completeButton");


const completionMessage =
    document.getElementById("completionMessage");


const activityStatus =
    document.getElementById("activityStatus");



/* -------------------------------------------------
   UPDATE VISUAL PROGRESS
------------------------------------------------- */

function updateProgress() {

    const total =
        progressItems.length;


    let completed = 0;


    progressItems.forEach(function(item) {

        if (item.checked) {

            completed++;

        }

    });


    const percentage =
        total > 0
            ? Math.round((completed / total) * 100)
            : 0;


    progressBar.style.width =
        percentage + "%";


    progressText.textContent =
        percentage + "%";


    /*
    Enable Complete button
    only when all 9 activities
    are completed.
    */

    if (completed === total) {

        completeButton.disabled = false;

        completionMessage.textContent =
            "Excellent! You have completed all Practical 3 activities.";

        completionMessage.className =
            "text-success mt-2 mb-0 fw-bold";

    } else {

        completeButton.disabled = true;

        completionMessage.textContent =
            "Complete all progress items before finishing the practical.";

        completionMessage.className =
            "text-muted mt-2 mb-0";
    }

}



/* -------------------------------------------------
   SAVE ACTIVITY TO DATABASE
------------------------------------------------- */

progressItems.forEach(function(item) {

    item.addEventListener(
        "change",
        async function() {

            const activityNumber =
                this.dataset.activity;


            const completed =
                this.checked ? 1 : 0;


            /*
            Update the progress bar immediately.
            */

            updateProgress();


            activityStatus.textContent =
                "Saving activity progress...";


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
                        "save_activity_progress3.php",
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

                    activityStatus.textContent =
                        "Progress saved successfully.";

                } else {

                    activityStatus.textContent =
                        data.message ||
                        "Could not save progress.";

                }


            } catch (error) {

                console.error(
                    "Activity progress error:",
                    error
                );


                activityStatus.textContent =
                    "Unable to save progress.";

            }

        }
    );

});



/* -------------------------------------------------
   INITIAL PROGRESS
------------------------------------------------- */

updateProgress();



/* -------------------------------------------------
   COPY WRITTEN WORK TO COMPLETE FORM
------------------------------------------------- */

document
    .getElementById("completeForm")
    .addEventListener(
        "submit",
        function() {

            const results =
                document.querySelector(
                    'textarea[name="results"]'
                ).value;


            const observations =
                document.querySelector(
                    'textarea[name="observations"]'
                ).value;


            const conclusion =
                document.querySelector(
                    'textarea[name="conclusion"]'
                ).value;


            document.getElementById(
                "completeResults"
            ).value = results;


            document.getElementById(
                "completeObservations"
            ).value = observations;


            document.getElementById(
                "completeConclusion"
            ).value = conclusion;

        }
    );

</script>


</body>

</html>