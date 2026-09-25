<?php

require_once "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   GET STUDENT NAME
========================================================= */

$student_name = "Student";

$stmt = $conn->prepare("
    SELECT full_name
    FROM users
    WHERE id = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param("i", $user_id);

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    if ($row && !empty($row['full_name'])) {
        $student_name = $row['full_name'];
    }

    $stmt->close();
}


/* =========================================================
   PRACTICAL 5 ACTIVITIES
========================================================= */

$activities = [

    1 => "Read the introduction and explain the purpose of filtration in food processing.",

    2 => "Identify the main equipment used during gravity filtration.",

    3 => "Explain the difference between the filtrate and the retained residue.",

    4 => "Set up the virtual filtration apparatus by positioning the sample and filter paper correctly.",

    5 => "Perform the interactive filtration simulation and observe the movement of liquid through the filter paper.",

    6 => "Record the initial sample mass, retained solid mass and filtration time.",

    7 => "Calculate and interpret the filtrate mass and percentage of solids retained.",

    8 => "Write your observations and explain how filtration can be applied in food processing."

];


/* =========================================================
   LOAD ACTIVITY PROGRESS
========================================================= */

$activity_progress = [];

for ($i = 1; $i <= 8; $i++) {
    $activity_progress[$i] = 0;
}


$stmt = $conn->prepare("
    SELECT activity_number, completed
    FROM practical_activity_progress
    WHERE user_id = ?
      AND practical_number = 5
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $activity_number =
            (int) $row['activity_number'];

        if (
            $activity_number >= 1
            &&
            $activity_number <= 8
        ) {

            $activity_progress[$activity_number] =
                (int) $row['completed'];
        }
    }

    $stmt->close();
}


/* =========================================================
   CALCULATE ACTIVITY PROGRESS
========================================================= */

$completed_activities = 0;

for ($i = 1; $i <= 8; $i++) {

    if ($activity_progress[$i] === 1) {
        $completed_activities++;
    }
}


$activity_percentage = (int) round(
    ($completed_activities / 8) * 100
);


/* =========================================================
   LOAD PRACTICAL STATUS
========================================================= */

$practical_status = "not_started";


$stmt = $conn->prepare("
    SELECT status
    FROM practical_progress
    WHERE user_id = ?
      AND practical_number = 5
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    if (
        $row
        &&
        !empty($row['status'])
    ) {

        $practical_status =
            $row['status'];
    }

    $stmt->close();
}


/* =========================================================
   CREATE PRACTICAL 5 PROGRESS
========================================================= */

if ($practical_status === "not_started") {

    $stmt = $conn->prepare("
        SELECT id
        FROM practical_progress
        WHERE user_id = ?
          AND practical_number = 5
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );

        $stmt->execute();

        $result =
            $stmt->get_result();

        $exists =
            $result->fetch_assoc();

        $stmt->close();


        if ($exists) {

            $stmt = $conn->prepare("
                UPDATE practical_progress
                SET status = 'in_progress'
                WHERE user_id = ?
                  AND practical_number = 5
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $user_id
                );

                $stmt->execute();

                $stmt->close();
            }

        } else {

            $stmt = $conn->prepare("
                INSERT INTO practical_progress
                (
                    user_id,
                    practical_number,
                    status,
                    started_at
                )
                VALUES
                (
                    ?,
                    5,
                    'in_progress',
                    NOW()
                )
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "i",
                    $user_id
                );

                $stmt->execute();

                $stmt->close();
            }
        }


        $practical_status =
            "in_progress";
    }
}


/* =========================================================
   LOAD PREVIOUS SUBMISSION
========================================================= */

$results = "";
$observations = "";
$conclusion = "";

$submission_id = 0;


$stmt = $conn->prepare("
    SELECT
        id,
        results,
        observations,
        conclusion
    FROM practical_submissions
    WHERE user_id = ?
      AND practical_number = 5
    ORDER BY id DESC
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    $row =
        $result->fetch_assoc();

    if ($row) {

        $submission_id =
            (int) $row['id'];

        $results =
            $row['results'] ?? "";

        $observations =
            $row['observations'] ?? "";

        $conclusion =
            $row['conclusion'] ?? "";
    }

    $stmt->close();
}


/* =========================================================
   SAVE PRACTICAL REPORT
========================================================= */

$message = "";
$message_type = "success";


if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['save_submission'])
) {

    $results =
        trim(
            $_POST['results'] ?? ""
        );

    $observations =
        trim(
            $_POST['observations'] ?? ""
        );

    $conclusion =
        trim(
            $_POST['conclusion'] ?? ""
        );


    if ($submission_id > 0) {

        $stmt = $conn->prepare("
            UPDATE practical_submissions
            SET
                results = ?,
                observations = ?,
                conclusion = ?,
                submitted_at = NOW()
            WHERE id = ?
              AND user_id = ?
              AND practical_number = 5
        ");

        if ($stmt) {

            $stmt->bind_param(
                "sssii",
                $results,
                $observations,
                $conclusion,
                $submission_id,
                $user_id
            );

            if ($stmt->execute()) {

                $message =
                    "Your Practical 5 work was saved successfully.";

            } else {

                $message =
                    "Could not save your practical report.";

                $message_type =
                    "danger";
            }

            $stmt->close();

        } else {

            $message =
                "Could not prepare the database request.";

            $message_type =
                "danger";
        }

    } else {

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
            VALUES
            (
                ?,
                5,
                ?,
                ?,
                ?,
                NOW()
            )
        ");

        if ($stmt) {

            $stmt->bind_param(
                "isss",
                $user_id,
                $results,
                $observations,
                $conclusion
            );

            if ($stmt->execute()) {

                $message =
                    "Your Practical 5 work was saved successfully.";

            } else {

                $message =
                    "Could not save your practical report.";

                $message_type =
                    "danger";
            }

            $stmt->close();

        } else {

            $message =
                "Could not prepare the database request.";

            $message_type =
                "danger";
        }
    }
}


/* =========================================================
   COMPLETE PRACTICAL 5
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['complete_practical'])
) {

    $results_text =
        trim(
            $_POST['results'] ?? ""
        );

    $observations_text =
        trim(
            $_POST['observations'] ?? ""
        );

    $conclusion_text =
        trim(
            $_POST['conclusion'] ?? ""
        );


    /* -----------------------------------------------
       CHECK ACTIVITIES
    ----------------------------------------------- */

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS completed_count
        FROM practical_activity_progress
        WHERE user_id = ?
          AND practical_number = 5
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

        if (
            $row =
                $result->fetch_assoc()
        ) {

            $all_completed =
                (
                    (int)
                    $row['completed_count']
                    >= 8
                );
        }

        $stmt->close();
    }


    /* -----------------------------------------------
       CHECK WRITTEN REPORT
    ----------------------------------------------- */

    $report_complete =
        $results_text !== ""
        &&
        $observations_text !== ""
        &&
        $conclusion_text !== "";


    if (
        !$all_completed
        ||
        !$report_complete
    ) {

        header(
            "Location: practical5.php?incomplete=1"
        );

        exit();
    }


    /* -----------------------------------------------
       SAVE / UPDATE SUBMISSION
    ----------------------------------------------- */

    $stmt = $conn->prepare("
        SELECT id
        FROM practical_submissions
        WHERE user_id = ?
          AND practical_number = 5
        LIMIT 1
    ");


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );

        $stmt->execute();

        $check =
            $stmt->get_result();


        if ($check->num_rows > 0) {

            $existing =
                $check->fetch_assoc();

            $existing_id =
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
                  AND user_id = ?
                  AND practical_number = 5
            ");


            if ($stmt) {

                $stmt->bind_param(
                    "sssii",
                    $results_text,
                    $observations_text,
                    $conclusion_text,
                    $existing_id,
                    $user_id
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
                VALUES
                (
                    ?,
                    5,
                    ?,
                    ?,
                    ?,
                    NOW()
                )
            ");


            if ($stmt) {

                $stmt->bind_param(
                    "isss",
                    $user_id,
                    $results_text,
                    $observations_text,
                    $conclusion_text
                );

                $stmt->execute();

                $stmt->close();
            }
        }
    }


    /* -----------------------------------------------
       MARK PRACTICAL COMPLETED
    ----------------------------------------------- */

    $stmt = $conn->prepare("
        UPDATE practical_progress
        SET
            status = 'completed',
            completed_at = NOW()
        WHERE user_id = ?
          AND practical_number = 5
    ");


    if ($stmt) {

        $stmt->bind_param(
            "i",
            $user_id
        );

        $stmt->execute();

        $stmt->close();
    }


    header(
        "Location: ../dashboard.php?completed=5"
    );

    exit();
}


/* =========================================================
   COMPLETION STATE
========================================================= */

$report_complete =
    trim($results) !== ""
    &&
    trim($observations) !== ""
    &&
    trim($conclusion) !== "";


$can_complete =
    (
        $completed_activities === 8
    )
    &&
    $report_complete;

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
        Practical 5 - Filtration and Separation
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        :root {

            --lab-blue: #1f5f75;
            --lab-dark: #17495a;
            --lab-bg: #f4f6f8;
            --lab-border: #d9dee3;
            --lab-text: #243746;
            --lab-muted: #667783;

        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background:
                var(--lab-bg);

            color:
                var(--lab-text);

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            line-height: 1.65;

        }


        /* =====================================================
           HEADER
        ===================================================== */

        .system-header {

            background:
                #ffffff;

            border-bottom:
                1px solid
                var(--lab-border);

            box-shadow:
                0 2px 8px
                rgba(31,95,117,.08);

        }


        .system-header-inner {

            min-height: 72px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding:
                12px 0;

        }


        .brand-area {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .brand-icon {

            width: 42px;

            height: 42px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background:
                var(--lab-blue);

            color:
                #ffffff;

            font-size: 21px;

        }


        .brand-title {

            margin: 0;

            color:
                var(--lab-blue);

            font-size:
                1.05rem;

            font-weight: 700;

        }


        .brand-subtitle {

            margin:
                2px 0 0;

            color:
                var(--lab-muted);

            font-size:
                .78rem;

        }


        .student-area {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .student-avatar {

            width: 38px;

            height: 38px;

            border-radius: 50%;

            background:
                #e8f0f3;

            color:
                var(--lab-blue);

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: 700;

        }


        /* =====================================================
           PAGE HEADING
        ===================================================== */

        .page-heading {

            background:
                #ffffff;

            border-bottom:
                1px solid
                var(--lab-border);

        }


        .page-heading-inner {

            padding:
                24px 0;

        }


        .page-kicker {

            color:
                var(--lab-blue);

            font-size:
                .78rem;

            font-weight:
                700;

            text-transform:
                uppercase;

            letter-spacing:
                .04em;

        }


        .page-heading h1 {

            margin:
                4px 0;

            font-size:
                clamp(1.55rem, 3vw, 2rem);

            font-weight:
                700;

        }


        .page-heading p {

            margin: 0;

            color:
                var(--lab-muted);

        }


        /* =====================================================
           CARDS
        ===================================================== */

        .practical-card {

            background:
                #ffffff;

            border:
                1px solid
                var(--lab-border);

            border-radius:
                10px;

            box-shadow:
                0 2px 8px
                rgba(31,55,68,.04);

            margin-bottom:
                20px;

        }


        .section-title {

            color:
                var(--lab-blue);

            font-weight:
                700;

        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .info-box {

            padding:
                16px;

            border-radius:
                8px;

            background:
                #eef5f7;

            border-left:
                4px solid
                var(--lab-blue);

        }


        .principle-box {

            background:
                #fafbfc;

            border:
                1px solid
                var(--lab-border);

            border-radius:
                8px;

            padding:
                18px;

        }


        .equipment-list li,
        .procedure-list li {

            margin-bottom:
                8px;

        }


        /* =====================================================
           ACTIVITIES
        ===================================================== */

        .activity {

            border:
                1px solid
                var(--lab-border);

            border-radius:
                8px;

            padding:
                13px 14px;

            margin-bottom:
                9px;

            background:
                #ffffff;

        }


        .activity.done {

            background:
                #f1f8f5;

            border-color:
                #b8d8c7;

        }


        .activity .form-check-input {

            cursor:
                pointer;

            margin-top:
                .32rem;

        }


        .activity .form-check-label {

            cursor:
                pointer;

        }


        /* =====================================================
           PROGRESS
        ===================================================== */

        .progress {

            height:
                12px;

            border-radius:
                20px;

            background:
                #e8edf0;

        }


        .progress-bar {

            background:
                var(--lab-blue);

        }


        /* =====================================================
           FORMS
        ===================================================== */

        textarea {

            min-height:
                140px;

            border-radius:
                8px !important;

            border-color:
                var(--lab-border) !important;

        }


        textarea:focus,
        .form-control:focus {

            border-color:
                var(--lab-blue) !important;

            box-shadow:
                0 0 0 .15rem
                rgba(31,95,117,.12) !important;

        }


        /* =====================================================
           SIDE CARD
        ===================================================== */

        .sticky-card {

            position:
                sticky;

            top:
                20px;

        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .btn-primary {

            background:
                var(--lab-blue);

            border-color:
                var(--lab-blue);

        }


        .btn-primary:hover {

            background:
                var(--lab-dark);

            border-color:
                var(--lab-dark);

        }


        .btn-outline-primary {

            color:
                var(--lab-blue);

            border-color:
                var(--lab-blue);

        }


        .btn-outline-primary:hover {

            background:
                var(--lab-blue);

            border-color:
                var(--lab-blue);

        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .system-footer {

            text-align:
                center;

            padding:
                25px 15px;

            color:
                var(--lab-muted);

            font-size:
                .85rem;

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 767px) {

            .brand-subtitle,
            .student-name-text {

                display:
                    none;

            }


            .sticky-card {

                position:
                    static;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="system-header">

    <div class="container">

        <div class="system-header-inner">


            <div class="brand-area">


                <div class="brand-icon">

                    <i class="bi bi-funnel-fill"></i>

                </div>


                <div>

                    <p class="brand-title">

                        Food Process Practical Learning System

                    </p>


                    <p class="brand-subtitle">

                        Laboratory Practical Management

                    </p>

                </div>


            </div>



            <div class="student-area">


                <div
                    class="student-name-text text-end"
                >

                    <small
                        class="text-muted"
                    >
                        Student
                    </small>

                    <div class="fw-semibold">

                        <?= htmlspecialchars($student_name) ?>

                    </div>

                </div>


                <div class="student-avatar">

                    <?= strtoupper(
                        substr(
                            trim($student_name),
                            0,
                            1
                        )
                    ) ?>

                </div>


            </div>


        </div>

    </div>

</header>



<!-- =========================================================
     PAGE HEADING
========================================================= -->

<section class="page-heading">

    <div class="container">

        <div class="page-heading-inner">


            <div
                class="
                    d-flex
                    justify-content-between
                    align-items-center
                    flex-wrap
                    gap-3
                "
            >


                <div>

                    <div class="page-kicker">

                        Practical 5

                    </div>


                    <h1>

                        <i
                            class="bi bi-funnel me-2"
                        ></i>

                        Filtration and Separation

                    </h1>


                    <p>

                        Gravity filtration of insoluble
                        particles from a liquid mixture.

                    </p>

                </div>


                <a
                    href="../dashboard.php"
                    class="btn btn-outline-primary"
                >

                    <i
                        class="bi bi-arrow-left me-1"
                    ></i>

                    Back to Dashboard

                </a>


            </div>


        </div>

    </div>

</section>



<div class="container py-4">


<!-- =========================================================
     ALERT
========================================================= -->

<?php if (!empty($message)): ?>

    <div
        class="
            alert
            alert-<?= htmlspecialchars($message_type) ?>
            alert-dismissible
            fade
            show
        "
    >

        <?= htmlspecialchars($message) ?>


        <button
            type="button"
            class="btn-close"
            data-bs-dismiss="alert"
        ></button>

    </div>

<?php endif; ?>


<?php if (isset($_GET['incomplete'])): ?>

    <div class="alert alert-warning">

        <i
            class="bi bi-exclamation-triangle me-1"
        ></i>

        Please complete all 8 activities and provide
        your Results, Observations and Conclusion
        before completing Practical 5.

    </div>

<?php endif; ?>



<div class="row g-4">


<!-- =========================================================
     MAIN COLUMN
========================================================= -->

<div class="col-lg-8">


<!-- =========================================================
     INTRODUCTION
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">


        <h4 class="section-title mb-3">

            Introduction

        </h4>


        <p>

            Filtration is a physical separation operation
            used to separate insoluble solid particles from
            a liquid by passing the mixture through a porous
            filtering medium.

        </p>


        <p>

            During gravity filtration, the liquid passes
            through the pores of the filter paper while the
            insoluble solid particles are retained on the
            filter paper. The liquid that passes through the
            filter is called the <strong>filtrate</strong>,
            while the material retained by the filter paper
            is called the <strong>residue</strong>.

        </p>


        <div class="info-box mt-3">

            <strong>

                Food Processing Application

            </strong>

            <br>

            Filtration is used in food processing for
            clarification of beverages, removal of suspended
            particles, separation of solids from liquid
            food products and treatment of process streams.

        </div>


    </div>

</div>



<!-- =========================================================
     OBJECTIVES
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">


        <h5 class="section-title mb-3">

            <i class="bi bi-bullseye me-2"></i>

            Objectives

        </h5>


        <ol>

            <li>
                To understand the principle of filtration.
            </li>

            <li>
                To identify the equipment used in gravity filtration.
            </li>

            <li>
                To distinguish between filtrate and residue.
            </li>

            <li>
                To perform a simulated filtration experiment.
            </li>

            <li>
                To calculate filtration-related results.
            </li>

            <li>
                To relate filtration to food processing applications.
            </li>

        </ol>


    </div>

</div>



<!-- =========================================================
     MATERIALS
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">


        <h5 class="section-title mb-3">

            <i class="bi bi-tools me-2"></i>

            Materials and Equipment

        </h5>


        <ul class="equipment-list">

            <li>
                Sample mixture containing liquid and
                insoluble particles.
            </li>

            <li>
                Filtration funnel.
            </li>

            <li>
                Filter paper.
            </li>

            <li>
                Receiving beaker.
            </li>

            <li>
                Laboratory stand or support.
            </li>

            <li>
                Digital balance for mass measurement.
            </li>

            <li>
                Stopwatch or timer.
            </li>

        </ul>


    </div>

</div>



<!-- =========================================================
     PRINCIPLE
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">


        <h5 class="section-title mb-3">

            <i class="bi bi-lightbulb me-2"></i>

            Principle of Filtration

        </h5>


        <div class="principle-box">


            <p>

                Filtration operates because the filter
                medium contains pores that allow the liquid
                phase to pass through while particles larger
                than the effective pore openings are retained.

            </p>


            <p class="mb-0">

                The separation performance can be evaluated
                by comparing the initial mass of the sample
                with the mass of solids retained after
                filtration.

            </p>


        </div>


    </div>

</div>



<!-- =========================================================
     PROCEDURE
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">


        <h5 class="section-title mb-3">

            <i class="bi bi-list-ol me-2"></i>

            Procedure

        </h5>


        <ol class="procedure-list">

            <li>
                Prepare the sample mixture containing
                liquid and insoluble solid particles.
            </li>

            <li>
                Place the filtration funnel in position.
            </li>

            <li>
                Install the filter paper inside the funnel.
            </li>

            <li>
                Place the receiving beaker directly below
                the funnel stem.
            </li>

            <li>
                Transfer the sample mixture into the
                filtration funnel.
            </li>

            <li>
                Allow gravity to drive the liquid through
                the filter paper.
            </li>

            <li>
                Observe the residue remaining on the filter
                paper and the filtrate collected below.
            </li>

            <li>
                Record the required measurements and
                calculate the results.
            </li>

        </ol>


    </div>

</div>



<!-- =========================================================
     SAFETY
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">


        <h5 class="section-title mb-3">

            <i class="bi bi-shield-check me-2"></i>

            Safety Precautions

        </h5>


        <ul>

            <li>
                Handle laboratory glassware carefully.
            </li>

            <li>
                Ensure that the filtration apparatus is
                stable before starting the experiment.
            </li>

            <li>
                Avoid overfilling the filtration funnel.
            </li>

            <li>
                Keep the working area clean and dry.
            </li>

            <li>
                Follow laboratory safety instructions
                throughout the practical.
            </li>

        </ul>


    </div>

</div>



<!-- =========================================================
     ACTIVITIES
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">


        <div
            class="
                d-flex
                justify-content-between
                align-items-center
                flex-wrap
                gap-2
                mb-3
            "
        >

            <h5 class="section-title mb-0">

                <i class="bi bi-check2-square me-2"></i>

                Practical Activities

            </h5>


            <span
                class="badge text-bg-light"
                id="activityCountBadge"
            >

                <?= $completed_activities ?>/8 Completed

            </span>


        </div>


        <?php foreach ($activities as $number => $activity): ?>


            <div
                class="
                    activity
                    <?= $activity_progress[$number]
                        ? 'done'
                        : ''
                    ?>
                "
                id="activityBox<?= $number ?>"
            >


                <div class="form-check">


                    <input
                        class="form-check-input activity-check"
                        type="checkbox"
                        id="activity<?= $number ?>"
                        data-activity="<?= $number ?>"
                        <?= $activity_progress[$number]
                            ? 'checked'
                            : ''
                        ?>
                    >


                    <label
                        class="form-check-label"
                        for="activity<?= $number ?>"
                    >

                        <strong>
                            Activity <?= $number ?>:
                        </strong>

                        <?= htmlspecialchars($activity) ?>

                    </label>


                </div>


            </div>


        <?php endforeach; ?>


    </div>

</div>



<!-- =========================================================
     PROGRESS
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">


        <div
            class="
                d-flex
                justify-content-between
                mb-2
            "
        >

            <strong>
                Practical 5 Progress
            </strong>


            <span id="progressText">

                <?= $activity_percentage ?>%

            </span>


        </div>


        <div class="progress">

            <div
                class="progress-bar"
                id="activityProgress"
                role="progressbar"
                style="
                    width:
                    <?= $activity_percentage ?>%;
                "
            ></div>

        </div>


        <p
            class="text-muted mt-2 mb-0"
            id="completedActivityText"
        >

            <?= $completed_activities ?>
            of 8 activities completed.

        </p>


    </div>

</div>



<!-- =========================================================
     RESULTS AND REPORT
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">


        <h5 class="section-title mb-3">

            <i class="bi bi-clipboard-data me-2"></i>

            Results, Observations and Conclusion

        </h5>


        <form method="POST">


            <div class="mb-4">


                <label
                    for="results"
                    class="form-label fw-semibold"
                >

                    Results

                </label>


                <textarea
                    name="results"
                    id="results"
                    class="form-control"
                    placeholder="Enter your experimental results here..."
                ><?= htmlspecialchars($results) ?></textarea>


            </div>



            <div class="mb-4">


                <label
                    for="observations"
                    class="form-label fw-semibold"
                >

                    Observations

                </label>


                <textarea
                    name="observations"
                    id="observations"
                    class="form-control"
                    placeholder="Describe what you observed during filtration..."
                ><?= htmlspecialchars($observations) ?></textarea>


            </div>



            <div class="mb-4">


                <label
                    for="conclusion"
                    class="form-label fw-semibold"
                >

                    Conclusion

                </label>


                <textarea
                    name="conclusion"
                    id="conclusion"
                    class="form-control"
                    placeholder="Write your conclusion about the filtration experiment..."
                ><?= htmlspecialchars($conclusion) ?></textarea>


            </div>



            <button
                type="submit"
                name="save_submission"
                class="btn btn-outline-primary"
            >

                <i class="bi bi-save me-1"></i>

                Save Practical Work

            </button>


        </form>


    </div>

</div>



<!-- =========================================================
     COMPLETE PRACTICAL
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">


        <h5 class="section-title mb-3">

            <i class="bi bi-check-circle me-2"></i>

            Complete Practical 5

        </h5>


        <div
            class="
                alert
                <?= $can_complete
                    ? 'alert-success'
                    : 'alert-warning'
                ?>
            "
            id="completionRequirements"
        >


            <?php if ($can_complete): ?>

                <i
                    class="bi bi-check-circle-fill me-1"
                ></i>

                All activities and written work
                are complete. You can finish
                Practical 5.

            <?php else: ?>

                <i
                    class="bi bi-exclamation-circle me-1"
                ></i>

                Complete all 8 activities and fill
                in Results, Observations and Conclusion
                before completing the practical.

            <?php endif; ?>


        </div>


        <form method="POST">


            <input
                type="hidden"
                name="results"
                id="completeResults"
                value="<?= htmlspecialchars($results) ?>"
            >


            <input
                type="hidden"
                name="observations"
                id="completeObservations"
                value="<?= htmlspecialchars($observations) ?>"
            >


            <input
                type="hidden"
                name="conclusion"
                id="completeConclusion"
                value="<?= htmlspecialchars($conclusion) ?>"
            >


            <button
                type="submit"
                name="complete_practical"
                id="completePracticalBtn"
                class="btn btn-primary"
                <?= $can_complete
                    ? ''
                    : 'disabled'
                ?>
            >

                <i class="bi bi-check-circle me-1"></i>

                Complete Practical 5

            </button>


        </form>


    </div>

</div>


</div>



<!-- =========================================================
     SIDE COLUMN
========================================================= -->

<div class="col-lg-4">


    <div class="card practical-card sticky-card">

        <div class="card-body p-4">


            <h5 class="section-title">

                <i class="bi bi-info-circle me-2"></i>

                Practical Information

            </h5>


            <hr>


            <p>

                <strong>
                    Filtration
                </strong>

                is a physical separation process used
                to remove insoluble particles from a liquid.

            </p>


            <p>

                <strong>
                    Filtrate
                </strong>

                is the liquid that passes through the
                filter paper.

            </p>


            <p>

                <strong>
                    Residue
                </strong>

                is the solid material retained by the
                filter paper.

            </p>


            <p>

                <strong>
                    Main Equipment
                </strong>

                Funnel, filter paper, sample mixture
                and receiving beaker.

            </p>


            <hr>


            <div class="mb-3">


                <small class="text-muted">

                    Your Progress

                </small>


                <h5>

                    <span id="sideProgress">

                        <?= $activity_percentage ?>%

                    </span>

                </h5>


            </div>



            <a
                href="../simulations/simulation5.php"
                class="btn btn-primary w-100 mb-2"
            >

                <i class="bi bi-play-circle me-1"></i>

                Run Filtration Simulation

            </a>



            <a
                href="../dashboard.php"
                class="btn btn-outline-secondary w-100"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Back to Dashboard

            </a>


        </div>

    </div>


</div>


</div>

</div>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer class="system-footer">

    <p class="mb-0">

        Food Process Engineering Practical Learning
        &amp; Simulation System

    </p>

</footer>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

const activityChecks =
    document.querySelectorAll(
        ".activity-check"
    );


const progressBar =
    document.getElementById(
        "activityProgress"
    );


const progressText =
    document.getElementById(
        "progressText"
    );


const completedActivityText =
    document.getElementById(
        "completedActivityText"
    );


const activityCountBadge =
    document.getElementById(
        "activityCountBadge"
    );


const sideProgress =
    document.getElementById(
        "sideProgress"
    );


const completeBtn =
    document.getElementById(
        "completePracticalBtn"
    );


const completionRequirements =
    document.getElementById(
        "completionRequirements"
    );


const resultsField =
    document.getElementById(
        "results"
    );


const observationsField =
    document.getElementById(
        "observations"
    );


const conclusionField =
    document.getElementById(
        "conclusion"
    );


const completeResults =
    document.getElementById(
        "completeResults"
    );


const completeObservations =
    document.getElementById(
        "completeObservations"
    );


const completeConclusion =
    document.getElementById(
        "completeConclusion"
    );


/* =========================================================
   UPDATE PROGRESS
========================================================= */

function updateProgress() {

    const total =
        activityChecks.length;


    const completed =
        [
            ...activityChecks
        ].filter(
            checkbox =>
                checkbox.checked
        ).length;


    const percentage =
        total > 0
            ? Math.round(
                (completed / total) * 100
            )
            : 0;


    progressBar.style.width =
        percentage + "%";


    progressText.textContent =
        percentage + "%";


    completedActivityText.textContent =
        completed +
        " of " +
        total +
        " activities completed.";


    activityCountBadge.textContent =
        completed +
        "/" +
        total +
        " Completed";


    sideProgress.textContent =
        percentage + "%";


    activityChecks.forEach(
        function (checkbox) {

            const box =
                document.getElementById(
                    "activityBox" +
                    checkbox.dataset.activity
                );


            if (box) {

                box.classList.toggle(
                    "done",
                    checkbox.checked
                );
            }

        }
    );


    updateCompletionState();

}


/* =========================================================
   COMPLETION STATE
========================================================= */

function updateCompletionState() {

    const activitiesComplete =
        [
            ...activityChecks
        ].every(
            checkbox =>
                checkbox.checked
        );


    const reportComplete =
        resultsField.value.trim() !== ""
        &&
        observationsField.value.trim() !== ""
        &&
        conclusionField.value.trim() !== "";


    const canComplete =
        activitiesComplete
        &&
        reportComplete;


    if (completeBtn) {

        completeBtn.disabled =
            !canComplete;

    }


    if (completionRequirements) {

        completionRequirements.className =
            canComplete
                ? "alert alert-success"
                : "alert alert-warning";


        completionRequirements.innerHTML =
            canComplete

                ? `
                    <i
                        class="bi bi-check-circle-fill me-1"
                    ></i>

                    All activities and written work
                    are complete. You can finish
                    Practical 5.
                  `

                : `
                    <i
                        class="bi bi-exclamation-circle me-1"
                    ></i>

                    Complete all 8 activities and fill
                    in Results, Observations and Conclusion
                    before completing the practical.
                  `;
    }


    /*
       Keep hidden completion fields synchronized
       with the visible report fields.
    */

    completeResults.value =
        resultsField.value;

    completeObservations.value =
        observationsField.value;

    completeConclusion.value =
        conclusionField.value;

}


/* =========================================================
   SAVE ACTIVITY PROGRESS
========================================================= */

activityChecks.forEach(
    function (checkbox) {

        checkbox.addEventListener(
            "change",
            async function () {

                updateProgress();


                const body =
                    new URLSearchParams();


                body.append(
                    "activity_number",
                    this.dataset.activity
                );


                body.append(
                    "completed",
                    this.checked
                        ? "1"
                        : "0"
                );


                try {

                    const response =
                        await fetch(
                            "save_activity_progress5.php",
                            {
                                method:
                                    "POST",

                                headers: {
                                    "Content-Type":
                                        "application/x-www-form-urlencoded"
                                },

                                body:
                                    body.toString()
                            }
                        );


                    const data =
                        await response.json();


                    if (!data.success) {

                        alert(
                            data.message ||
                            "Could not save activity progress."
                        );

                    }

                }
                catch (error) {

                    console.error(
                        error
                    );

                    alert(
                        "Could not connect to the server."
                    );

                }

            }
        );

    }
);


/* =========================================================
   REPORT FIELD CHANGES
========================================================= */

[
    resultsField,
    observationsField,
    conclusionField
].forEach(
    function (field) {

        field.addEventListener(
            "input",
            updateCompletionState
        );

    }
);


/* =========================================================
   INITIAL STATE
========================================================= */

updateProgress();

</script>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>