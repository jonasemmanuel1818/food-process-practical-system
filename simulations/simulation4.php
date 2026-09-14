<?php
require_once "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$student_name = "Student";
$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT full_name
    FROM users
    WHERE id = ?
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
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Practical 4 - Drying Simulation</title>

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

    <!-- Chart.js -->
    <script
        src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"
    ></script>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f6f8;
            font-family: Arial, Helvetica, sans-serif;
            color: #263238;
        }

        /* =====================================================
           TOP HEADER
        ===================================================== */

        .top-header {
            background: #ffffff;
            border-bottom: 1px solid #d9dee3;
            padding: 16px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .brand-area {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .brand-icon {
            width: 46px;
            height: 46px;
            background: #1f5f75;
            color: #ffffff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .brand-title {
            font-size: 19px;
            font-weight: 700;
            color: #1f5f75;
            margin: 0;
        }

        .brand-subtitle {
            color: #6c757d;
            font-size: 13px;
            margin-top: 2px;
        }

        .student-area {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #e9f1f4;
            color: #1f5f75;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .student-name {
            font-size: 14px;
            font-weight: 600;
            color: #34495e;
        }

        /* =====================================================
           PAGE TITLE
        ===================================================== */

        .page-title {
            background: #1f5f75;
            color: #ffffff;
            padding: 28px 0;
            margin-bottom: 25px;
        }

        .page-title h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 5px;
        }

        .page-title p {
            margin: 0;
            color: #e6f0f3;
        }

        .practical-badge {
            display: inline-block;
            background: #ffffff;
            color: #1f5f75;
            padding: 5px 11px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 9px;
            letter-spacing: .3px;
        }

        /* =====================================================
           CARDS
        ===================================================== */

        .simulation-card {
            background: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 8px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .simulation-card-header {
            padding: 16px 20px;
            background: #f8fafb;
            border-bottom: 1px solid #d9dee3;
            color: #1f5f75;
            font-size: 18px;
            font-weight: 700;
        }

        .simulation-card-header i {
            margin-right: 7px;
        }

        .simulation-card-body {
            padding: 22px;
        }

        /* =====================================================
           INTRODUCTION
        ===================================================== */

        .intro-text {
            line-height: 1.7;
            color: #4d5963;
            margin-bottom: 18px;
        }

        .info-panel {
            border: 1px solid #cddfe6;
            border-left: 4px solid #1f5f75;
            background: #f5fafc;
            padding: 15px 17px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .info-panel:last-child {
            margin-bottom: 0;
        }

        .info-panel strong {
            color: #1f5f75;
        }

        /* =====================================================
           SECTION HEADER
        ===================================================== */

        .section-heading {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 20px;
        }

        .section-heading-icon {
            width: 36px;
            height: 36px;
            background: #e9f1f4;
            color: #1f5f75;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .section-heading h2 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            color: #263238;
        }

        .section-heading p {
            margin: 2px 0 0;
            color: #6c757d;
            font-size: 13px;
        }

        /* =====================================================
           FORM
        ===================================================== */

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: #37474f;
            margin-bottom: 7px;
        }

        .form-control {
            border: 1px solid #cbd3d8;
            border-radius: 5px;
            padding: 10px 12px;
            min-height: 43px;
        }

        .form-control:focus {
            border-color: #1f5f75;
            box-shadow: 0 0 0 3px rgba(31,95,117,0.10);
        }

        .input-help {
            display: block;
            margin-top: 5px;
            color: #7a858c;
            font-size: 12px;
        }

        /* =====================================================
           BUTTONS
        ===================================================== */

        .btn {
            border-radius: 5px;
            font-weight: 600;
            padding: 9px 16px;
        }

        .btn-primary {
            background: #1f5f75;
            border-color: #1f5f75;
        }

        .btn-primary:hover {
            background: #174b5d;
            border-color: #174b5d;
        }

        .btn-outline-primary {
            color: #1f5f75;
            border-color: #1f5f75;
        }

        .btn-outline-primary:hover {
            background: #1f5f75;
            border-color: #1f5f75;
            color: #ffffff;
        }

        .btn-success {
            background: #39756a;
            border-color: #39756a;
        }

        .btn-success:hover {
            background: #2f6259;
            border-color: #2f6259;
        }

        .btn-outline-success {
            color: #39756a;
            border-color: #39756a;
        }

        .btn-outline-success:hover {
            background: #39756a;
            color: #ffffff;
        }

        .btn-secondary {
            background: #66727a;
            border-color: #66727a;
        }

        .btn-dark {
            background: #34444d;
            border-color: #34444d;
        }

        /* =====================================================
           SIMULATION CONTROLS
        ===================================================== */

        .control-panel {
            background: #f8fafb;
            border: 1px solid #e0e5e8;
            padding: 18px;
            border-radius: 6px;
        }

        .control-title {
            color: #1f5f75;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .simulation-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 20px;
        }

        .simulation-buttons .btn {
            margin: 0;
        }

        /* =====================================================
           LOADING
        ===================================================== */

        .simulation-loading {
            display: none;
            margin-top: 18px;
        }

        .simulation-loading.show {
            display: block;
        }

        .simulation-loading .progress {
            height: 9px;
            border-radius: 4px;
            background: #e4e8eb;
        }

        .simulation-loading .progress-bar {
            background: #1f5f75;
        }

        /* =====================================================
           CHART
        ===================================================== */

        .chart-section {
            margin-top: 22px;
            border: 1px solid #dfe4e7;
            background: #ffffff;
            border-radius: 6px;
            padding: 15px;
        }

        .chart-title {
            font-size: 14px;
            font-weight: 700;
            color: #455a64;
            margin-bottom: 10px;
        }

        .chart-container {
            position: relative;
            width: 100%;
            height: 380px;
        }

        .chart-container canvas {
            width: 100% !important;
            height: 100% !important;
        }

        /* =====================================================
           RESULTS
        ===================================================== */

        .result-box {
            margin-top: 20px;
            padding: 20px;
            background: #f8fafb;
            border: 1px solid #d9e0e4;
            border-left: 4px solid #1f5f75;
            border-radius: 6px;
        }

        .result-title {
            color: #1f5f75;
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #dce2e5;
            border-radius: 6px;
            padding: 15px;
            height: 100%;
        }

        .stat-label {
            display: block;
            color: #718096;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .result-value {
            color: #263238;
            font-size: 20px;
            font-weight: 700;
        }

        /* =====================================================
           GENERAL CONTROLS
        ===================================================== */

        .bottom-controls {
            background: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .bottom-controls .btn {
            margin: 4px;
        }

        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 768px) {

            .top-header {
                padding: 13px 0;
            }

            .brand-title {
                font-size: 16px;
            }

            .brand-subtitle {
                font-size: 11px;
            }

            .student-area {
                width: 100%;
                justify-content: flex-start;
            }

            .page-title {
                padding: 22px 0;
            }

            .page-title h1 {
                font-size: 23px;
            }

            .simulation-card-body {
                padding: 17px;
            }

            .chart-container {
                height: 300px;
            }

            .simulation-buttons {
                flex-direction: column;
            }

            .simulation-buttons .btn {
                width: 100%;
            }

        }

        /* =====================================================
           PRINT
        ===================================================== */

        @media print {

            body {
                background: #ffffff;
            }

            .no-print {
                display: none !important;
            }

            .top-header {
                box-shadow: none;
                border-bottom: 1px solid #ccc;
            }

            .page-title {
                background: #ffffff !important;
                color: #000000 !important;
                border-bottom: 2px solid #1f5f75;
            }

            .page-title p {
                color: #444444;
            }

            .practical-badge {
                border: 1px solid #1f5f75;
            }

            .simulation-card {
                box-shadow: none;
                break-inside: avoid;
            }

            .chart-container {
                height: 400px;
            }

        }

    </style>

</head>

<body>

<!-- =========================================================
     TOP HEADER
========================================================= -->

<header class="top-header">

    <div class="container">

        <div class="d-flex
                    justify-content-between
                    align-items-center
                    flex-wrap
                    gap-3">

            <div class="brand-area">

                <div class="brand-icon">
                    <i class="bi bi-flask"></i>
                </div>

                <div>

                    <h1 class="brand-title">
                        Food Process Practical Learning System
                    </h1>

                    <div class="brand-subtitle">
                        Laboratory Practical Management
                    </div>

                </div>

            </div>

            <div class="student-area">

                <div class="student-icon">
                    <i class="bi bi-person"></i>
                </div>

                <div class="student-name">
                    <?= htmlspecialchars($student_name) ?>
                </div>

            </div>

        </div>

    </div>

</header>


<!-- =========================================================
     PAGE TITLE
========================================================= -->

<section class="page-title">

    <div class="container">

        <div class="d-flex
                    justify-content-between
                    align-items-center
                    flex-wrap
                    gap-3">

            <div>

                <span class="practical-badge">
                    PRACTICAL 4
                </span>

                <h1>
                    Drying Process Simulation
                </h1>

                <p>
                    Spray Drying and Freeze Drying
                </p>

            </div>

            <div class="no-print">

                <a
                    href="../practical/practical4.php"
                    class="btn btn-light"
                >
                    <i class="bi bi-arrow-left"></i>
                    Back to Practical 4
                </a>

            </div>

        </div>

    </div>

</section>


<main class="container pb-4">


<!-- =========================================================
     ABOUT SIMULATION
========================================================= -->

<div class="simulation-card">

    <div class="simulation-card-header">

        <i class="bi bi-info-circle"></i>

        About the Simulation

    </div>

    <div class="simulation-card-body">

        <p class="intro-text">

            This simulation allows you to investigate drying
            behaviour under different operating conditions.
            You can perform both Spray Drying and Freeze Drying
            simulations and examine the calculated results.

        </p>

        <div class="info-panel">

            <strong>
                <i class="bi bi-wind"></i>
                Spray Drying
            </strong>

            <p class="mb-0 mt-1">

                The simulation shows how moisture decreases
                with drying time under selected inlet
                temperature and feed conditions.

            </p>

        </div>

        <div class="info-panel">

            <strong>
                <i class="bi bi-snow2"></i>
                Freeze Drying
            </strong>

            <p class="mb-0 mt-1">

                The simulation models moisture reduction
                during freeze drying under reduced pressure.
                The heating plate temperature is limited to
                20–40°C.

            </p>

        </div>

    </div>

</div>


<!-- =========================================================
     SPRAY DRYING
========================================================= -->

<div class="simulation-card">

    <div class="simulation-card-header">

        <i class="bi bi-wind"></i>

        Spray Drying Simulation

    </div>

    <div class="simulation-card-body">

        <div class="section-heading">

            <div class="section-heading-icon">
                <i class="bi bi-gear"></i>
            </div>

            <div>

                <h2>
                    Operating Conditions
                </h2>

                <p>
                    Enter the experimental parameters
                    before running the simulation.
                </p>

            </div>

        </div>


        <div class="control-panel">

            <div class="control-title">
                Simulation Inputs
            </div>

            <div class="row g-3">

                <!-- INLET TEMPERATURE -->

                <div class="col-md-6">

                    <label
                        for="sprayInletTemp"
                        class="form-label"
                    >
                        Inlet Air Temperature (°C)
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="sprayInletTemp"
                        value="205"
                        min="100"
                        max="300"
                        step="1"
                    >

                    <small class="input-help">
                        Recommended practical range:
                        200–210°C
                    </small>

                </div>


                <!-- FEED FLOW -->

                <div class="col-md-6">

                    <label
                        for="sprayFeedFlow"
                        class="form-label"
                    >
                        Feed Flow Rate (kg/h)
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="sprayFeedFlow"
                        value="10"
                        min="0.1"
                        step="0.1"
                    >

                </div>


                <!-- INITIAL MOISTURE -->

                <div class="col-md-6">

                    <label
                        for="sprayInitialMoisture"
                        class="form-label"
                    >
                        Initial Moisture (%)
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="sprayInitialMoisture"
                        value="60"
                        min="1"
                        max="99"
                        step="0.1"
                    >

                </div>


                <!-- FINAL MOISTURE -->

                <div class="col-md-6">

                    <label
                        for="sprayFinalMoisture"
                        class="form-label"
                    >
                        Final Moisture (%)
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="sprayFinalMoisture"
                        value="5"
                        min="0.1"
                        max="98"
                        step="0.1"
                    >

                </div>

            </div>


            <!-- BUTTONS -->

            <div class="simulation-buttons no-print">

                <button
                    type="button"
                    id="runSprayBtn"
                    class="btn btn-primary"
                >

                    <i class="bi bi-play-fill"></i>

                    Run Simulation

                </button>


                <button
                    type="button"
                    id="saveSprayBtn"
                    class="btn btn-success"
                    disabled
                >

                    <i class="bi bi-save"></i>

                    Save Result

                </button>


                <button
                    type="button"
                    id="printSprayBtn"
                    class="btn btn-secondary"
                    disabled
                >

                    <i class="bi bi-printer"></i>

                    Print Result

                </button>


                <button
                    type="button"
                    id="newSprayBtn"
                    class="btn btn-outline-primary"
                >

                    <i class="bi bi-arrow-clockwise"></i>

                    Start New Simulation

                </button>

            </div>


            <!-- LOADING -->

            <div
                id="sprayLoading"
                class="simulation-loading"
            >

                <div class="progress">

                    <div
                        id="sprayLoadingBar"
                        class="progress-bar progress-bar-striped progress-bar-animated"
                        style="width: 0%"
                    ></div>

                </div>

                <small class="input-help">
                    Running Spray Drying simulation...
                </small>

            </div>

        </div>


        <!-- CHART -->

        <div class="chart-section">

            <div class="chart-title">

                <i class="bi bi-graph-up"></i>

                Moisture Reduction During Spray Drying

            </div>

            <div class="chart-container">

                <canvas id="sprayChart"></canvas>

            </div>

        </div>


        <!-- RESULTS -->

        <div
            id="sprayResult"
            class="result-box"
        >

            <div id="sprayResultContent">

                <div class="result-title">

                    <i class="bi bi-clipboard-data"></i>

                    Spray Drying Results

                </div>

                <p class="text-muted mb-0">

                    Run the simulation to display
                    calculated results.

                </p>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     FREEZE DRYING
========================================================= -->

<div class="simulation-card">

    <div class="simulation-card-header">

        <i class="bi bi-snow2"></i>

        Freeze Drying Simulation

    </div>

    <div class="simulation-card-body">


        <div class="section-heading">

            <div class="section-heading-icon">
                <i class="bi bi-gear"></i>
            </div>

            <div>

                <h2>
                    Operating Conditions
                </h2>

                <p>
                    Enter the freeze drying parameters
                    before running the simulation.
                </p>

            </div>

        </div>


        <div class="control-panel">

            <div class="control-title">
                Simulation Inputs
            </div>


            <div class="row g-3">


                <!-- THICKNESS -->

                <div class="col-md-6">

                    <label
                        for="freezeThickness"
                        class="form-label"
                    >
                        Sample Thickness (mm)
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="freezeThickness"
                        value="10"
                        min="1"
                        max="50"
                        step="0.1"
                    >

                </div>


                <!-- PLATE TEMPERATURE -->

                <div class="col-md-6">

                    <label
                        for="freezePlateTemp"
                        class="form-label"
                    >
                        Heating Plate Temperature (°C)
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="freezePlateTemp"
                        value="30"
                        min="20"
                        max="40"
                        step="0.1"
                    >

                    <small class="input-help">
                        Allowed range: 20–40°C
                    </small>

                </div>


                <!-- PRESSURE -->

                <div class="col-md-6">

                    <label
                        for="freezePressure"
                        class="form-label"
                    >
                        Chamber Pressure (mbar)
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="freezePressure"
                        value="0.5"
                        min="0.01"
                        max="10"
                        step="0.01"
                    >

                    <small class="input-help">
                        Allowed range: 0.01–10 mbar
                    </small>

                </div>


                <!-- MOISTURE -->

                <div class="col-md-6">

                    <label
                        for="freezeMoisture"
                        class="form-label"
                    >
                        Initial Moisture (%)
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="freezeMoisture"
                        value="70"
                        min="6"
                        max="99"
                        step="0.1"
                    >

                </div>

            </div>


            <!-- BUTTONS -->

            <div class="simulation-buttons no-print">

                <button
                    type="button"
                    id="runFreezeBtn"
                    class="btn btn-primary"
                >

                    <i class="bi bi-play-fill"></i>

                    Run Simulation

                </button>


                <button
                    type="button"
                    id="saveFreezeBtn"
                    class="btn btn-success"
                    disabled
                >

                    <i class="bi bi-save"></i>

                    Save Result

                </button>


                <button
                    type="button"
                    id="printFreezeBtn"
                    class="btn btn-secondary"
                    disabled
                >

                    <i class="bi bi-printer"></i>

                    Print Result

                </button>


                <button
                    type="button"
                    id="newFreezeBtn"
                    class="btn btn-outline-primary"
                >

                    <i class="bi bi-arrow-clockwise"></i>

                    Start New Simulation

                </button>

            </div>


            <!-- LOADING -->

            <div
                id="freezeLoading"
                class="simulation-loading"
            >

                <div class="progress">

                    <div
                        id="freezeLoadingBar"
                        class="progress-bar progress-bar-striped progress-bar-animated"
                        style="width: 0%"
                    ></div>

                </div>

                <small class="input-help">
                    Running Freeze Drying simulation...
                </small>

            </div>

        </div>


        <!-- CHART -->

        <div class="chart-section">

            <div class="chart-title">

                <i class="bi bi-graph-up"></i>

                Moisture Reduction During Freeze Drying

            </div>

            <div class="chart-container">

                <canvas id="freezeChart"></canvas>

            </div>

        </div>


        <!-- RESULTS -->

        <div
            id="freezeResult"
            class="result-box"
        >

            <div id="freezeResultContent">

                <div class="result-title">

                    <i class="bi bi-clipboard-data"></i>

                    Freeze Drying Results

                </div>

                <p class="text-muted mb-0">

                    Run the simulation to display
                    calculated results.

                </p>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     GENERAL CONTROLS
========================================================= -->

<div class="bottom-controls no-print">

    <h5 class="fw-bold mb-3">
        Simulation Management
    </h5>

    <p class="text-muted small mb-3">
        Print your results, review saved trials,
        or return to the Practical 4 learning page.
    </p>


    <button
        type="button"
        id="printAllBtn"
        class="btn btn-dark"
    >

        <i class="bi bi-printer"></i>

        Print Simulation Results

    </button>


    <a
        href="simulation4_history.php"
        class="btn btn-primary"
    >

        <i class="bi bi-clock-history"></i>

        Results History

    </a>


    <a
        href="../practical/practical4.php"
        class="btn btn-outline-primary"
    >

        <i class="bi bi-journal-text"></i>

        Return to Practical 4

    </a>

</div>


</main>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script
    src="../assets/js/practical4_simulation.js"
></script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>