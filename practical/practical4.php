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
   PRACTICAL 4 ACTIVITIES
========================================================= */

$activities = [
    1 => "Read the introduction and explain the purpose of drying in food processing.",
    2 => "Identify the main components of a spray dryer.",
    3 => "Record the spray-drying inlet temperature used in the practical.",
    4 => "Record the outlet/product temperature and room temperature.",
    5 => "Record or calculate the amount of material spray dried.",
    6 => "Determine the dry-matter content of the milk and milk powder.",
    7 => "Calculate the amount of powder collected.",
    8 => "Read the freeze-drying introduction and explain the principle of sublimation.",
    9 => "Explain how product thickness affects freeze-drying time.",
    10 => "Record the heating-plate temperature and chamber pressure.",
    11 => "Compare theoretical and experimental drying behaviour.",
    12 => "Write observations, answer the practical questions and state your conclusion."
];

/* =========================================================
   LOAD ACTIVITY PROGRESS
========================================================= */

$activity_progress = [];

for ($i = 1; $i <= 12; $i++) {
    $activity_progress[$i] = 0;
}

$stmt = $conn->prepare("
    SELECT activity_number, completed
    FROM practical_activity_progress
    WHERE user_id = ?
      AND practical_number = 4
");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $activity_number = (int) $row['activity_number'];

        if ($activity_number >= 1 && $activity_number <= 12) {
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

for ($i = 1; $i <= 12; $i++) {
    if ($activity_progress[$i] === 1) {
        $completed_activities++;
    }
}

$activity_percentage = (int) round(
    ($completed_activities / 12) * 100
);

/* =========================================================
   LOAD PRACTICAL STATUS
========================================================= */

$practical_status = "not_started";

$stmt = $conn->prepare("
    SELECT status
    FROM practical_progress
    WHERE user_id = ?
      AND practical_number = 4
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && !empty($row['status'])) {
        $practical_status = $row['status'];
    }

    $stmt->close();
}

/* =========================================================
   MAKE SURE PRACTICAL PROGRESS EXISTS
========================================================= */

if ($practical_status === "not_started") {

    $stmt = $conn->prepare("
        SELECT id
        FROM practical_progress
        WHERE user_id = ?
          AND practical_number = 4
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();

        $stmt->close();

        if ($exists) {

            $stmt = $conn->prepare("
                UPDATE practical_progress
                SET status = 'in_progress'
                WHERE user_id = ?
                  AND practical_number = 4
            ");

            if ($stmt) {
                $stmt->bind_param("i", $user_id);
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
                (?, 4, 'in_progress', NOW())
            ");

            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        $practical_status = "in_progress";
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
      AND practical_number = 4
    ORDER BY id DESC
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {

        $submission_id = (int) $row['id'];

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
        trim($_POST['results'] ?? "");

    $observations =
        trim($_POST['observations'] ?? "");

    $conclusion =
        trim($_POST['conclusion'] ?? "");

    /*
       Update the latest submission if one exists.
       Otherwise create a new submission.
    */

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
              AND practical_number = 4
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
                    "Your Practical 4 work was saved successfully.";

            } else {

                $message =
                    "Could not save your practical report.";

                $message_type = "danger";
            }

            $stmt->close();

        } else {

            $message =
                "Could not prepare the database request.";

            $message_type = "danger";
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
            (?, 4, ?, ?, ?, NOW())
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
                    "Your Practical 4 work was saved successfully.";

            } else {

                $message =
                    "Could not save your practical report.";

                $message_type = "danger";
            }

            $stmt->close();

        } else {

            $message =
                "Could not prepare the database request.";

            $message_type = "danger";
        }
    }
}

/* =========================================================
   CHECK IF PRACTICAL CAN BE COMPLETED
========================================================= */

$report_complete =
    trim($results) !== ""
    &&
    trim($observations) !== ""
    &&
    trim($conclusion) !== "";

$can_complete =
    ($completed_activities === 12)
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

    <title>Practical 4 - Drying</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <!-- Shared System CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        :root {
            --lab-blue: #1f5f75;
            --lab-blue-dark: #17485a;
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
            background: var(--lab-bg);
            color: var(--lab-text);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.65;
        }

        /* =====================================================
           SYSTEM HEADER
        ===================================================== */

        .system-header {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: #ffffff;
            border-bottom: 1px solid var(--lab-border);
            box-shadow: 0 2px 8px rgba(31, 95, 117, 0.08);
        }

        .system-header-inner {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 12px 0;
        }

        .brand-area {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: var(--lab-blue);
            color: #ffffff;
            font-size: 21px;
        }

        .brand-title {
            margin: 0;
            color: var(--lab-blue);
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .brand-subtitle {
            margin: 2px 0 0;
            color: var(--lab-muted);
            font-size: 0.78rem;
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
            background: #e8f0f3;
            color: var(--lab-blue);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .student-name {
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* =====================================================
           PAGE HEADING
        ===================================================== */

        .page-heading {
            background: #ffffff;
            border-bottom: 1px solid var(--lab-border);
        }

        .page-heading-inner {
            padding: 24px 0;
        }

        .page-kicker {
            color: var(--lab-blue);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .page-heading h1 {
            color: var(--lab-text);
            font-size: clamp(1.55rem, 3vw, 2rem);
            font-weight: 700;
            margin: 0;
        }

        .page-heading p {
            color: var(--lab-muted);
            margin: 5px 0 0;
        }

        /* =====================================================
           CARDS
        ===================================================== */

        .practical-card {
            background: #ffffff;
            border: 1px solid var(--lab-border);
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(31, 55, 68, 0.04);
            margin-bottom: 20px;
        }

        .section-title {
            color: var(--lab-blue);
            font-weight: 700;
        }

        h5 {
            color: var(--lab-text);
            font-weight: 700;
        }

        /* =====================================================
           ACTIVITIES
        ===================================================== */

        .activity {
            border: 1px solid var(--lab-border);
            border-radius: 8px;
            padding: 13px 14px;
            margin-bottom: 9px;
            background: #ffffff;
            transition:
                background-color 0.15s ease,
                border-color 0.15s ease;
        }

        .activity:hover {
            border-color: #bccbd2;
            box-shadow: none;
            transform: none;
        }

        .activity.done {
            background: #f1f8f5;
            border-color: #b8d8c7;
        }

        .activity .form-check-input {
            cursor: pointer;
            margin-top: 0.32rem;
        }

        .activity .form-check-label {
            cursor: pointer;
        }

        /* =====================================================
           PROGRESS
        ===================================================== */

        .progress {
            height: 12px;
            border-radius: 20px;
            background: #e8edf0;
        }

        .progress-bar {
            background: var(--lab-blue);
        }

        /* =====================================================
           FORMS
        ===================================================== */

        textarea {
            min-height: 140px;
            border-radius: 8px !important;
            border-color: var(--lab-border) !important;
        }

        textarea:focus,
        .form-control:focus {
            border-color: var(--lab-blue) !important;
            box-shadow:
                0 0 0 0.15rem rgba(31, 95, 117, 0.12) !important;
        }

        /* =====================================================
           SIDE INFORMATION
        ===================================================== */

        .sticky-card {
            position: sticky;
            top: 92px;
        }

        .info-box {
            border-radius: 8px;
            padding: 15px;
            background: #eef5f7;
            border-left: 4px solid var(--lab-blue);
        }

        /* =====================================================
           BUTTONS
        ===================================================== */

        .simulation-btn {
            padding: 10px 18px;
            border-radius: 7px;
            font-weight: 600;
        }

        .completion-card {
            border: 1px solid var(--lab-border);
        }

        .status-badge {
            font-size: 0.78rem;
            padding: 7px 11px;
            border-radius: 999px;
            font-weight: 600;
        }

        .btn-primary {
            background: var(--lab-blue);
            border-color: var(--lab-blue);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--lab-blue-dark);
            border-color: var(--lab-blue-dark);
        }

        .btn-outline-primary {
            color: var(--lab-blue);
            border-color: var(--lab-blue);
        }

        .btn-outline-primary:hover {
            background: var(--lab-blue);
            border-color: var(--lab-blue);
        }

        .text-primary {
            color: var(--lab-blue) !important;
        }

        .alert {
            border-radius: 8px;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 767.98px) {

            .system-header-inner {
                min-height: 64px;
            }

            .brand-subtitle,
            .student-name {
                display: none;
            }

            .page-heading-inner {
                padding: 18px 0;
            }

            .sticky-card {
                position: static;
            }
        }

        /* =====================================================
           PRINT
        ===================================================== */

        @media print {

            .no-print,
            .system-header,
            .page-heading a,
            button {
                display: none !important;
            }

            body {
                background: #ffffff;
            }

            .practical-card {
                box-shadow: none;
                border: 1px solid #cccccc;
                break-inside: avoid;
            }
        }

    </style>

</head>

<body>

<!-- =========================================================
     SYSTEM HEADER
========================================================= -->

<header class="system-header no-print">

    <div class="container">

        <div class="system-header-inner">

            <div class="brand-area">

                <div class="brand-icon">
                    <i class="bi bi-flask"></i>
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

                <div class="text-end d-none d-sm-block">

                    <div class="small text-muted">
                        Student
                    </div>

                    <div class="student-name">
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
                        Practical 4
                    </div>

                    <h1>

                        <i class="bi bi-droplet-half me-2"></i>

                        Drying

                    </h1>

                    <p>
                        Spray Drying and Freeze Drying
                    </p>

                </div>


                <a
                    href="../dashboard.php"
                    class="btn btn-outline-primary no-print"
                >

                    <i class="bi bi-arrow-left me-1"></i>

                    Back to Dashboard

                </a>

            </div>

        </div>

    </div>

</section>


<div class="container py-4">


<!-- =========================================================
     MESSAGE
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


<div class="row">


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="col-lg-8">


<!-- =========================================================
     PRACTICAL OVERVIEW
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">

        <div
            class="
                d-flex
                justify-content-between
                align-items-start
                gap-3
                flex-wrap
            "
        >

            <div>

                <h4 class="section-title mb-2">

                    Practical 4: Drying

                </h4>

                <p class="text-muted mb-0">

                    This practical covers spray drying and
                    freeze drying, including operating
                    conditions, measurements, drying behaviour
                    and simulation.

                </p>

            </div>


            <span class="badge bg-primary status-badge">

                <?= $completed_activities ?>/12 Activities

            </span>

        </div>

    </div>

</div>


<!-- =========================================================
     SPRAY DRYING
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">

        <h4 class="section-title">

            4.1 Spray Drying

        </h4>

        <p>

            Spray drying is a method used to remove
            moisture from liquid food materials and
            produce a dry powder.

        </p>

        <p>

            In this process, the liquid feed is converted
            into small droplets and contacted with heated
            air inside a drying chamber. Moisture
            evaporates rapidly and the dried particles
            are collected as powder.

        </p>


        <h5 class="mt-4">

            Main Components

        </h5>

        <ul>

            <li>
                Air heating and circulation system
            </li>

            <li>
                Atomizer or spray-forming device
            </li>

            <li>
                Drying chamber
            </li>

            <li>
                Cyclone/product recovery system
            </li>

            <li>
                Feed pump
            </li>

        </ul>


        <h5 class="mt-4">

            Spray Drying Procedure

        </h5>

        <ol>

            <li>
                Prepare the spray dryer and connect
                the necessary components.
            </li>

            <li>
                Start air circulation.
            </li>

            <li>
                Set the required inlet air temperature.
            </li>

            <li>
                Start the feed pump and atomizer.
            </li>

            <li>
                Allow the feed to pass through the
                drying chamber.
            </li>

            <li>
                Collect the dried powder.
            </li>

            <li>
                Record the required measurements.
            </li>

        </ol>


        <div class="info-box mt-4">

            <strong>

                <i class="bi bi-info-circle"></i>

                Simulation

            </strong>

            <p class="mb-3 mt-2">

                Use the Practical 4 simulation to investigate
                how inlet temperature, feed flow and moisture
                affect drying behaviour.

            </p>

            <a
                href="../simulations/simulation4.php"
                target="_blank"
                class="btn btn-primary simulation-btn"
            >

                <i class="bi bi-play-circle"></i>

                Open Drying Simulation

            </a>

        </div>

    </div>

</div>


<!-- =========================================================
     FREEZE DRYING
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">

        <h4 class="section-title">

            4.2 Freeze Drying

        </h4>

        <p>

            Freeze drying is a dehydration process in
            which frozen water is removed mainly by
            sublimation under reduced pressure.

        </p>

        <p>

            The product is first frozen and then placed
            under vacuum. Heat is supplied carefully so
            that ice changes directly from solid ice into
            water vapour.

        </p>


        <h5 class="mt-4">

            Freeze Drying Procedure

        </h5>

        <ol>

            <li>
                Prepare samples with different thicknesses.
            </li>

            <li>
                Freeze the samples completely.
            </li>

            <li>
                Place the samples in the freeze dryer.
            </li>

            <li>
                Connect the temperature measuring devices.
            </li>

            <li>
                Set the heating plate temperature.
            </li>

            <li>
                Establish the required chamber pressure.
            </li>

            <li>
                Record temperature and pressure at intervals.
            </li>

            <li>
                Continue drying until the required
                final moisture is reached.
            </li>

            <li>
                Compare the drying behaviour of different
                operating conditions.
            </li>

        </ol>


        <div class="alert alert-info mt-4">

            <strong>

                <i class="bi bi-thermometer-half"></i>

                Important:

            </strong>

            For this practical simulation, the freeze-drying
            plate temperature is restricted to
            <strong>20–40°C</strong>.

        </div>


        <a
            href="../simulations/simulation4.php"
            target="_blank"
            class="
                btn
                btn-primary
                simulation-btn
                mt-2
            "
        >

            <i class="bi bi-graph-up-arrow"></i>

            Run Drying Simulation

        </a>

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
                mb-2
            "
        >

            <h4 class="section-title mb-0">

                <i class="bi bi-list-check"></i>

                Activities & Progress

            </h4>

            <span
                id="activityCountBadge"
                class="badge bg-secondary status-badge"
            >

                <?= $completed_activities ?>/12

            </span>

        </div>


        <p class="text-muted">

            Complete each activity and tick the checkbox.
            Your progress is saved automatically.

        </p>


        <?php for ($i = 1; $i <= 12; $i++): ?>

            <div
                id="activityBox<?= $i ?>"
                class="
                    activity
                    <?= $activity_progress[$i] ? 'done' : '' ?>
                "
            >

                <div class="form-check">

                    <input
                        type="checkbox"
                        class="form-check-input activity-check"
                        id="activity<?= $i ?>"
                        data-activity="<?= $i ?>"
                        <?= $activity_progress[$i] ? 'checked' : '' ?>
                    >

                    <label
                        class="form-check-label"
                        for="activity<?= $i ?>"
                    >

                        <strong>
                            Activity <?= $i ?>:
                        </strong>

                        <?= htmlspecialchars($activities[$i]) ?>

                    </label>

                </div>

            </div>

        <?php endfor; ?>


        <!-- PROGRESS -->

        <div class="mt-4">

            <div
                class="
                    d-flex
                    justify-content-between
                    align-items-center
                    mb-2
                "
            >

                <strong>
                    Activity Progress
                </strong>

                <span id="progressText">

                    <?= $activity_percentage ?>%

                </span>

            </div>


            <div class="progress">

                <div
                    id="activityProgress"
                    class="progress-bar"
                    role="progressbar"
                    style="width: <?= $activity_percentage ?>%"
                    aria-valuenow="<?= $activity_percentage ?>"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >

                    <?= $activity_percentage ?>%

                </div>

            </div>


            <div class="mt-2 text-muted small">

                <span id="completedActivityText">

                    <?= $completed_activities ?> of 12 activities completed

                </span>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     STUDENT REPORT
========================================================= -->

<div class="card practical-card">

    <div class="card-body p-4">

        <h4 class="section-title mb-2">

            <i class="bi bi-file-earmark-text"></i>

            Student Report

        </h4>

        <p class="text-muted">

            Enter your results, observations and conclusion
            from the practical and simulations.

        </p>


        <form method="POST" id="reportForm">


            <!-- RESULTS -->

            <div class="mb-4">

                <label class="form-label fw-semibold">

                    Results / Calculations

                </label>

                <textarea
                    name="results"
                    id="results"
                    class="form-control"
                    placeholder="Enter your measured results, calculations and simulation results..."
                ><?= htmlspecialchars($results) ?></textarea>

            </div>


            <!-- OBSERVATIONS -->

            <div class="mb-4">

                <label class="form-label fw-semibold">

                    Observations

                </label>

                <textarea
                    name="observations"
                    id="observations"
                    class="form-control"
                    placeholder="Enter your observations from the practical and simulations..."
                ><?= htmlspecialchars($observations) ?></textarea>

            </div>


            <!-- CONCLUSION -->

            <div class="mb-4">

                <label class="form-label fw-semibold">

                    Conclusion

                </label>

                <textarea
                    name="conclusion"
                    id="conclusion"
                    class="form-control"
                    placeholder="Write your conclusion..."
                ><?= htmlspecialchars($conclusion) ?></textarea>

            </div>


            <div class="d-flex gap-2 flex-wrap">

                <button
                    type="submit"
                    name="save_submission"
                    class="btn btn-success"
                >

                    <i class="bi bi-save"></i>

                    Save Practical 4

                </button>


                <button
                    type="button"
                    onclick="window.print()"
                    class="btn btn-primary"
                >

                    <i class="bi bi-printer"></i>

                    Print

                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     COMPLETE PRACTICAL
========================================================= -->

<div class="card practical-card completion-card">

    <div class="card-body p-4 text-center">

        <div class="mb-3">

            <?php if ($practical_status === 'completed'): ?>

                <div
                    class="
                        rounded-circle
                        bg-success
                        text-white
                        d-inline-flex
                        align-items-center
                        justify-content-center
                    "
                    style="width:70px;height:70px;"
                >

                    <i class="bi bi-check-lg fs-2"></i>

                </div>

            <?php else: ?>

                <div
                    class="
                        rounded-circle
                        bg-light
                        text-primary
                        d-inline-flex
                        align-items-center
                        justify-content-center
                    "
                    style="width:70px;height:70px;"
                >

                    <i class="bi bi-flag fs-2"></i>

                </div>

            <?php endif; ?>

        </div>


        <h4 class="section-title">

            <?php if ($practical_status === 'completed'): ?>

                Practical 4 Completed

            <?php else: ?>

                Complete Practical 4

            <?php endif; ?>

        </h4>


        <?php if ($practical_status === 'completed'): ?>

            <p class="text-success">

                You have successfully completed Practical 4.

            </p>

            <a
                href="../dashboard.php?completed=4"
                class="btn btn-success"
            >

                <i class="bi bi-speedometer2"></i>

                Return to Dashboard

            </a>

        <?php else: ?>

            <p class="text-muted">

                Complete all 12 activities and save your
                Results, Observations and Conclusion before
                completing this practical.

            </p>


            <div
                id="completionRequirements"
                class="
                    alert
                    <?= $can_complete
                        ? 'alert-success'
                        : 'alert-warning'
                    ?>
                    text-start
                    mx-auto
                "
                style="max-width:600px;"
            >

                <div class="mb-2">

                    <i
                        class="
                            bi
                            <?= $completed_activities === 12
                                ? 'bi-check-circle-fill text-success'
                                : 'bi-circle text-warning'
                            ?>
                        "
                        id="activitiesRequirementIcon"
                    ></i>

                    <strong>
                        Activities:
                    </strong>

                    <span id="activitiesRequirement">

                        <?= $completed_activities ?>/12 completed

                    </span>

                </div>


                <div>

                    <i
                        class="
                            bi
                            <?= $report_complete
                                ? 'bi-check-circle-fill text-success'
                                : 'bi-circle text-warning'
                            ?>
                        "
                        id="reportRequirementIcon"
                    ></i>

                    <strong>
                        Report:
                    </strong>

                    <span id="reportRequirement">

                        <?= $report_complete
                            ? 'Complete'
                            : 'Results, Observations and Conclusion required'
                        ?>

                    </span>

                </div>

            </div>


            <button
                type="button"
                id="completePracticalBtn"
                class="
                    btn
                    <?= $can_complete
                        ? 'btn-success'
                        : 'btn-secondary'
                    ?>
                    px-4
                "
                <?= $can_complete ? '' : 'disabled' ?>
            >

                <i class="bi bi-check-circle"></i>

                Complete Practical 4

            </button>


            <div
                id="completionMessage"
                class="mt-3"
            ></div>

        <?php endif; ?>

    </div>

</div>


</div>


<!-- =========================================================
     SIDE INFORMATION
========================================================= -->

<div class="col-lg-4">

    <div class="card practical-card sticky-card">

        <div class="card-body p-4">

            <h5 class="section-title">

                <i class="bi bi-info-circle"></i>

                Practical Information

            </h5>

            <hr>


            <p>

                <strong>
                    Spray Drying
                </strong>

                converts a liquid feed into
                dry powder using heated air.

            </p>


            <p>

                <strong>
                    Freeze Drying
                </strong>

                removes frozen moisture mainly
                through sublimation under reduced
                pressure.

            </p>


            <p>

                <strong>
                    Plate Temperature
                </strong>

                20–40°C

            </p>


            <p>

                <strong>
                    Important Measurements
                </strong>

                Temperature, moisture,
                pressure, thickness,
                feed flow and collected product.

            </p>


            <hr>


            <div class="mb-3">

                <small class="text-muted">
                    Your Progress
                </small>

                <h5 class="mb-0">

                    <span id="sideProgress">

                        <?= $activity_percentage ?>%

                    </span>

                </h5>

            </div>


            <a
                href="../simulations/simulation4.php"
                target="_blank"
                class="
                    btn
                    btn-primary
                    w-100
                    mb-2
                "
            >

                <i class="bi bi-play-circle"></i>

                Run Simulation

            </a>


            <a
                href="../dashboard.php"
                class="btn btn-outline-secondary w-100"
            >

                <i class="bi bi-arrow-left"></i>

                Back to Dashboard

            </a>

        </div>

    </div>

</div>


</div>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   ACTIVITY ELEMENTS
========================================================= */

const activityChecks =
    document.querySelectorAll(".activity-check");

const progressBar =
    document.getElementById("activityProgress");

const progressText =
    document.getElementById("progressText");

const completedActivityText =
    document.getElementById("completedActivityText");

const activityCountBadge =
    document.getElementById("activityCountBadge");

const sideProgress =
    document.getElementById("sideProgress");

const completeBtn =
    document.getElementById("completePracticalBtn");

const completionMessage =
    document.getElementById("completionMessage");

const resultsField =
    document.getElementById("results");

const observationsField =
    document.getElementById("observations");

const conclusionField =
    document.getElementById("conclusion");


/* =========================================================
   UPDATE ACTIVITY PROGRESS
========================================================= */

function updateProgress() {

    const total =
        activityChecks.length;

    const completed =
        [...activityChecks].filter(
            checkbox => checkbox.checked
        ).length;

    const percentage =
        total > 0
            ? Math.round((completed / total) * 100)
            : 0;


    /* Progress bar */

    if (progressBar) {

        progressBar.style.width =
            percentage + "%";

        progressBar.textContent =
            percentage + "%";

        progressBar.setAttribute(
            "aria-valuenow",
            percentage
        );
    }


    /* Progress text */

    if (progressText) {

        progressText.textContent =
            percentage + "%";
    }


    /* Activity count */

    if (completedActivityText) {

        completedActivityText.textContent =
            completed +
            " of " +
            total +
            " activities completed";
    }


    /* Badge */

    if (activityCountBadge) {

        activityCountBadge.textContent =
            completed + "/" + total;

        if (completed === total) {

            activityCountBadge.classList.remove(
                "bg-secondary"
            );

            activityCountBadge.classList.add(
                "bg-success"
            );

        } else {

            activityCountBadge.classList.remove(
                "bg-success"
            );

            activityCountBadge.classList.add(
                "bg-secondary"
            );
        }
    }


    /* Side progress */

    if (sideProgress) {

        sideProgress.textContent =
            percentage + "%";
    }


    /* Activity boxes */

    activityChecks.forEach(
        checkbox => {

            const activityNumber =
                checkbox.dataset.activity;

            const box =
                document.getElementById(
                    "activityBox" +
                    activityNumber
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
   CHECK REPORT
========================================================= */

function isReportComplete() {

    if (
        !resultsField ||
        !observationsField ||
        !conclusionField
    ) {

        return false;
    }

    return (
        resultsField.value.trim() !== "" &&
        observationsField.value.trim() !== "" &&
        conclusionField.value.trim() !== ""
    );
}


/* =========================================================
   UPDATE COMPLETION STATE
========================================================= */

function updateCompletionState() {

    if (!completeBtn) {
        return;
    }

    const completed =
        [...activityChecks].filter(
            checkbox => checkbox.checked
        ).length;

    const activitiesComplete =
        completed === activityChecks.length;

    const reportComplete =
        isReportComplete();

    const canComplete =
        activitiesComplete &&
        reportComplete;


    completeBtn.disabled =
        !canComplete;


    if (canComplete) {

        completeBtn.classList.remove(
            "btn-secondary"
        );

        completeBtn.classList.add(
            "btn-success"
        );

    } else {

        completeBtn.classList.remove(
            "btn-success"
        );

        completeBtn.classList.add(
            "btn-secondary"
        );
    }


    /* Update requirements */

    const activitiesRequirement =
        document.getElementById(
            "activitiesRequirement"
        );

    const reportRequirement =
        document.getElementById(
            "reportRequirement"
        );

    const activitiesIcon =
        document.getElementById(
            "activitiesRequirementIcon"
        );

    const reportIcon =
        document.getElementById(
            "reportRequirementIcon"
        );


    if (activitiesRequirement) {

        activitiesRequirement.textContent =
            completed +
            "/" +
            activityChecks.length +
            " completed";
    }


    if (reportRequirement) {

        reportRequirement.textContent =
            reportComplete
                ? "Complete"
                : "Results, Observations and Conclusion required";
    }


    if (activitiesIcon) {

        activitiesIcon.className =
            activitiesComplete
                ? "bi bi-check-circle-fill text-success"
                : "bi bi-circle text-warning";
    }


    if (reportIcon) {

        reportIcon.className =
            reportComplete
                ? "bi bi-check-circle-fill text-success"
                : "bi bi-circle text-warning";
    }


    const requirements =
        document.getElementById(
            "completionRequirements"
        );

    if (requirements) {

        requirements.classList.remove(
            "alert-success",
            "alert-warning"
        );

        requirements.classList.add(
            canComplete
                ? "alert-success"
                : "alert-warning"
        );
    }
}


/* =========================================================
   SAVE ACTIVITY PROGRESS
========================================================= */

activityChecks.forEach(
    checkbox => {

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
                            "save_activity_progress4.php",
                            {
                                method: "POST",

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

                    alert(
                        "Could not connect to the server."
                    );

                }

            }
        );

    });


/* =========================================================
   WATCH REPORT FIELDS
========================================================= */

[
    resultsField,
    observationsField,
    conclusionField
].forEach(
    field => {

        if (field) {

            field.addEventListener(
                "input",
                updateCompletionState
            );

        }

    }
);


/* =========================================================
   COMPLETE PRACTICAL 4
========================================================= */

if (completeBtn) {

    completeBtn.addEventListener(
        "click",
        async function () {

            if (completeBtn.disabled) {
                return;
            }


            if (!isReportComplete()) {

                alert(
                    "Please complete Results, Observations and Conclusion first."
                );

                return;
            }


            const completed =
                [...activityChecks].filter(
                    checkbox => checkbox.checked
                ).length;


            if (completed !== 12) {

                alert(
                    "Please complete all 12 activities first."
                );

                return;
            }


            completeBtn.disabled = true;

            completeBtn.innerHTML = `
                <span
                    class="spinner-border spinner-border-sm me-2"
                ></span>
                Completing...
            `;


            try {

                const response =
                    await fetch(
                        "complete_practical4.php",
                        {
                            method: "POST"
                        }
                    );


                const data =
                    await response.json();


                if (data.success) {

                    if (completionMessage) {

                        completionMessage.innerHTML = `
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i>
                                ${data.message}
                            </div>
                        `;
                    }


                    completeBtn.innerHTML = `
                        <i class="bi bi-check-circle-fill"></i>
                        Practical 4 Completed
                    `;


                    setTimeout(
                        function () {

                            window.location.href =
                                "../dashboard.php?completed=4";

                        },
                        1500
                    );

                } else {

                    if (completionMessage) {

                        completionMessage.innerHTML = `
                            <div class="alert alert-warning">
                                ${data.message}
                            </div>
                        `;
                    }


                    completeBtn.disabled = false;

                    completeBtn.innerHTML = `
                        <i class="bi bi-check-circle"></i>
                        Complete Practical 4
                    `;

                }

            }

            catch (error) {

                console.error(error);


                if (completionMessage) {

                    completionMessage.innerHTML = `
                        <div class="alert alert-danger">
                            Something went wrong while completing
                            Practical 4. Please try again.
                        </div>
                    `;
                }


                completeBtn.disabled = false;

                completeBtn.innerHTML = `
                    <i class="bi bi-check-circle"></i>
                    Complete Practical 4
                `;

            }

        }
    );

}


/* =========================================================
   INITIAL STATE
========================================================= */

updateProgress();

</script>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>