<?php

require_once "../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$user_name = $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? $_SESSION['studentName']
    ?? $_SESSION['full_name']
    ?? "Student";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Physical Separation Simulation | Food Process System</title>

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

        /* =========================
           GENERAL
        ========================= */

        body {
            margin: 0;
            background: #f4f6f8;
            color: #263238;
            font-family: Arial, Helvetica, sans-serif;
        }

        /* =========================
           TOP HEADER
        ========================= */

        .top-header {
            background: #1f5f75;
            color: #ffffff;
            padding: 18px 0;
            border-bottom: 4px solid #17495a;
        }

        .system-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 45px;
            height: 45px;
            background: #ffffff;
            color: #1f5f75;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .system-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .system-subtitle {
            font-size: 12px;
            opacity: 0.85;
            margin: 2px 0 0;
        }

        .student-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .student-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #ffffff;
            color: #1f5f75;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /* =========================
           PAGE HEADER
        ========================= */

        .page-heading {
            background: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 8px;
            padding: 22px 24px;
            margin-bottom: 20px;
        }

        .page-heading h1 {
            font-size: 25px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #1f5f75;
        }

        .page-heading p {
            margin: 0;
            color: #68737a;
        }

        .practical-badge {
            display: inline-block;
            background: #e8f1f4;
            color: #1f5f75;
            border: 1px solid #c9dce2;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-bottom: 10px;
        }

        /* =========================
           CARDS
        ========================= */

        .simulation-card {
            background: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .simulation-card .card-body {
            padding: 24px;
        }

        /* =========================
           SECTION HEADINGS
        ========================= */

        .section-title {
            color: #1f5f75;
            font-size: 19px;
            font-weight: 700;
            padding-bottom: 12px;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e4e7;
        }

        .section-title i {
            margin-right: 7px;
        }

        /* =========================
           ACTION AREA
        ========================= */

        .action-panel {
            background: #ffffff;
            border: 1px solid #d9dee3;
            border-left: 4px solid #1f5f75;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 20px;
        }

        .action-title {
            font-size: 14px;
            font-weight: 700;
            color: #46545b;
            margin-bottom: 12px;
        }

        .action-buttons {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }

        /* =========================
           BUTTONS
        ========================= */

        .btn-primary {
            background: #1f5f75;
            border-color: #1f5f75;
        }

        .btn-primary:hover {
            background: #17495a;
            border-color: #17495a;
        }

        .btn-outline-primary {
            color: #1f5f75;
            border-color: #1f5f75;
        }

        .btn-outline-primary:hover {
            background: #1f5f75;
            border-color: #1f5f75;
        }

        .btn-outline-success {
            color: #39745b;
            border-color: #39745b;
        }

        .btn-outline-success:hover {
            background: #39745b;
            border-color: #39745b;
        }

        /* =========================
           STATUS
        ========================= */

        .simulation-status {
            background: #f7f9fa;
            border: 1px solid #d9dee3;
            border-radius: 6px;
            padding: 14px 16px;
            color: #526068;
        }

        .status-label {
            color: #1f5f75;
            font-weight: 700;
        }

        /* =========================
           FORM CONTROLS
        ========================= */

        .form-label {
            color: #37474f;
            font-size: 14px;
        }

        .form-control {
            border: 1px solid #cbd3d8;
            border-radius: 5px;
            padding: 10px 12px;
        }

        .form-control:focus {
            border-color: #1f5f75;
            box-shadow: 0 0 0 0.15rem rgba(31, 95, 117, 0.15);
        }

        /* =========================
           FORMULA
        ========================= */

        .formula-box {
            background: #eef4f6;
            border: 1px solid #cbdde3;
            border-left: 4px solid #1f5f75;
            padding: 14px 16px;
            border-radius: 5px;
            margin-top: 20px;
            color: #263238;
            font-family: Consolas, "Courier New", monospace;
            font-size: 14px;
        }

        /* =========================
           RESULT BOXES
        ========================= */

        .result-box {
            background: #f8fafb;
            border: 1px solid #d9dee3;
            border-top: 3px solid #1f5f75;
            padding: 18px;
            border-radius: 6px;
            height: 100%;
        }

        .result-box h5,
        .result-box h6 {
            color: #59666d;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .result-box h2,
        .result-box h3 {
            color: #1f5f75;
            font-weight: 700;
            margin: 0;
        }

        /* =========================
           INTERPRETATION
        ========================= */

        #centrifugeInterpretation {
            border-radius: 6px;
            border: 1px solid #c9dfe7;
            background: #eef7fa;
            color: #315765;
        }

        /* =========================
           TABLES
        ========================= */

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #e8f1f4;
            color: #1f5f75;
            border-color: #cbd8dd;
            font-size: 13px;
            font-weight: 700;
            vertical-align: middle;
        }

        .table td {
            border-color: #d9dee3;
            vertical-align: middle;
            font-size: 14px;
        }

        .table tbody tr:hover {
            background: #f7f9fa;
        }

        /* =========================
           CHART AREAS
        ========================= */

        .chart-container {
            background: #fafbfc;
            border: 1px solid #e0e4e7;
            border-radius: 6px;
            padding: 18px;
            margin-top: 15px;
        }

        canvas {
            max-height: 350px;
        }

        /* =========================
           CONCLUSION
        ========================= */

        #simulationConclusion {
            background: #f7f9fa;
            border: 1px solid #d9dee3;
            border-left: 4px solid #1f5f75;
            color: #46545b;
            border-radius: 5px;
            padding: 18px;
        }

        /* =========================
           FOOTER NAVIGATION
        ========================= */

        .bottom-navigation {
            background: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 30px;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 768px) {

            .top-header {
                padding: 14px 0;
            }

            .student-area {
                margin-top: 12px;
                width: 100%;
                justify-content: space-between;
            }

            .page-heading h1 {
                font-size: 21px;
            }

            .simulation-card .card-body {
                padding: 18px;
            }

            .action-buttons .btn {
                width: 100%;
            }

            .bottom-navigation .btn {
                width: 100%;
                margin-bottom: 8px;
            }

        }

        /* =========================
           PRINT
        ========================= */

        @media print {

            body {
                background: #ffffff !important;
            }

            .no-print,
            .action-panel,
            button,
            a {
                display: none !important;
            }

            .top-header {
                background: #ffffff !important;
                color: #000000 !important;
                border-bottom: 2px solid #1f5f75;
            }

            .brand-icon {
                border: 1px solid #1f5f75;
            }

            .simulation-card,
            .page-heading,
            .bottom-navigation {
                box-shadow: none !important;
                border: 1px solid #cccccc !important;
                page-break-inside: avoid;
            }

            canvas {
                max-height: 300px;
            }

        }

    </style>

</head>

<body>


<!-- =====================================================
     SYSTEM HEADER
===================================================== -->

<header class="top-header">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center flex-wrap">

            <div class="system-brand">

                <div class="brand-icon">
                    <i class="bi bi-flask"></i>
                </div>

                <div>

                    <p class="system-title">
                        Food Process Practical Learning System
                    </p>

                    <p class="system-subtitle">
                        Laboratory Practical Management
                    </p>

                </div>

            </div>


            <div class="student-area">

                <div class="student-avatar">
                    <i class="bi bi-person"></i>
                </div>

                <span>
                    <?= htmlspecialchars($user_name) ?>
                </span>

                <a
                    href="../practical/practical2.php"
                    class="btn btn-light btn-sm"
                >
                    <i class="bi bi-arrow-left"></i>
                    Back to Practical
                </a>

            </div>

        </div>

    </div>

</header>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="container py-4">


    <!-- PAGE HEADING -->

    <div class="page-heading">

        <span class="practical-badge">
            PRACTICAL 2 · SIMULATION
        </span>

        <h1>
            Physical Separation Simulation
        </h1>

        <p>
            Centrifugation and Sieve Analysis
        </p>

    </div>


    <!-- =================================================
         ACTION PANEL
    ================================================== -->

    <div class="action-panel no-print">

        <div class="action-title">
            <i class="bi bi-sliders"></i>
            Simulation Controls
        </div>

        <div class="action-buttons">

            <button
                type="button"
                id="runSimulation"
                class="btn btn-primary"
            >
                <i class="bi bi-play-circle"></i>
                Run Simulation
            </button>


            <button
                type="button"
                id="resetSimulation"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-arrow-counterclockwise"></i>
                Reset
            </button>


            <button
                type="button"
                id="saveResults"
                class="btn btn-outline-primary"
            >
                <i class="bi bi-save"></i>
                Save Results
            </button>


            <button
                type="button"
                id="printResults"
                class="btn btn-dark"
            >
                <i class="bi bi-printer"></i>
                Print Results
            </button>


            <a
                href="simulation2_history.php"
                class="btn btn-outline-success"
            >
                <i class="bi bi-clock-history"></i>
                Results History
            </a>


            <a
                href="simulation2_compare.php"
                class="btn btn-outline-primary"
            >
                <i class="bi bi-bar-chart"></i>
                Compare Trials
            </a>

        </div>

    </div>


    <!-- =================================================
         STATUS
    ================================================== -->

    <div class="simulation-card">

        <div class="card-body">

            <div class="simulation-status">

                <span class="status-label">
                    <i class="bi bi-activity"></i>
                    Simulation Status:
                </span>

                <span id="simulationStatus">
                    Ready to run.
                </span>

            </div>

        </div>

    </div>


    <!-- =================================================
         PART A - CENTRIFUGATION
    ================================================== -->

    <section class="simulation-card">

        <div class="card-body">

            <h2 class="section-title">

                <i class="bi bi-arrow-repeat"></i>

                Part A: Centrifugation

            </h2>


            <!-- INPUTS -->

            <div class="row g-4">

                <div class="col-md-4">

                    <label class="form-label fw-bold">
                        RPM
                    </label>

                    <input
                        type="number"
                        id="rpm"
                        class="form-control"
                        value="3000"
                        min="1"
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label fw-bold">
                        Radius (cm)
                    </label>

                    <input
                        type="number"
                        id="radius"
                        class="form-control"
                        value="10"
                        min="0.1"
                        step="0.1"
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label fw-bold">
                        Centrifugation Time (min)
                    </label>

                    <input
                        type="number"
                        id="centrifugeTime"
                        class="form-control"
                        value="10"
                        min="1"
                    >

                </div>

            </div>


            <!-- FORMULA -->

            <div class="formula-box">

                <strong>RCF Formula:</strong>

                <br>

                RCF = 1.118 × 10⁻⁵ × r × RPM²

            </div>


            <!-- RESULTS -->

            <div class="row g-4 mt-2">

                <div class="col-md-6">

                    <div class="result-box">

                        <h5>
                            Relative Centrifugal Force
                        </h5>

                        <h2 id="rcfResult">
                            0
                        </h2>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="result-box">

                        <h5>
                            Separation Index
                        </h5>

                        <h2 id="separationResult">
                            0%
                        </h2>

                    </div>

                </div>

            </div>


            <!-- INTERPRETATION -->

            <div
                class="alert mt-4"
                id="centrifugeInterpretation"
            >
                Adjust the operating conditions and run the simulation.
            </div>


            <!-- PARAMETER TABLE -->

            <div class="table-responsive mt-4">

                <table class="table table-bordered">

                    <thead>

                        <tr>

                            <th>
                                Parameter
                            </th>

                            <th>
                                Value
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td>
                                RPM
                            </td>

                            <td id="rpmTable">
                                -
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Radius (cm)
                            </td>

                            <td id="radiusTable">
                                -
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Time (min)
                            </td>

                            <td id="timeTable">
                                -
                            </td>

                        </tr>


                        <tr>

                            <td>
                                RCF
                            </td>

                            <td id="rcfTable">
                                -
                            </td>

                        </tr>


                        <tr>

                            <td>
                                Separation Index
                            </td>

                            <td id="separationTable">
                                -
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            <!-- CHART -->

            <div class="mt-4">

                <h5 class="fw-bold">
                    <i class="bi bi-graph-up"></i>
                    Centrifugation Graph
                </h5>

                <div class="chart-container">

                    <canvas id="centrifugeChart"></canvas>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         PART B - SIEVE ANALYSIS
    ================================================== -->

    <section class="simulation-card">

        <div class="card-body">

            <h2 class="section-title">

                <i class="bi bi-filter"></i>

                Part B: Sieve Analysis

            </h2>


            <!-- SAMPLE MASS -->

            <div class="row g-4">

                <div class="col-md-4">

                    <label class="form-label fw-bold">
                        Total Sample Mass (g)
                    </label>

                    <input
                        type="number"
                        id="sampleMass"
                        class="form-control"
                        value="100"
                        min="0.1"
                        step="0.1"
                    >

                </div>

            </div>


            <!-- SIEVE INPUT TABLE -->

            <div class="table-responsive mt-4">

                <table class="table table-bordered">

                    <thead>

                        <tr>

                            <th>
                                Sieve Size
                            </th>

                            <th>
                                Mass Retained (g)
                            </th>

                        </tr>

                    </thead>

                    <tbody>


                        <tr>

                            <td>
                                2.0 mm
                            </td>

                            <td>

                                <input
                                    type="number"
                                    class="form-control sieve-mass"
                                    data-sieve="2.0 mm"
                                    value="10"
                                    min="0"
                                    step="0.1"
                                >

                            </td>

                        </tr>


                        <tr>

                            <td>
                                1.0 mm
                            </td>

                            <td>

                                <input
                                    type="number"
                                    class="form-control sieve-mass"
                                    data-sieve="1.0 mm"
                                    value="20"
                                    min="0"
                                    step="0.1"
                                >

                            </td>

                        </tr>


                        <tr>

                            <td>
                                0.5 mm
                            </td>

                            <td>

                                <input
                                    type="number"
                                    class="form-control sieve-mass"
                                    data-sieve="0.5 mm"
                                    value="25"
                                    min="0"
                                    step="0.1"
                                >

                            </td>

                        </tr>


                        <tr>

                            <td>
                                0.25 mm
                            </td>

                            <td>

                                <input
                                    type="number"
                                    class="form-control sieve-mass"
                                    data-sieve="0.25 mm"
                                    value="20"
                                    min="0"
                                    step="0.1"
                                >

                            </td>

                        </tr>


                        <tr>

                            <td>
                                Pan
                            </td>

                            <td>

                                <input
                                    type="number"
                                    class="form-control sieve-mass"
                                    data-sieve="Pan"
                                    value="25"
                                    min="0"
                                    step="0.1"
                                >

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            <!-- SUMMARY RESULTS -->

            <div class="row g-4 mt-2">

                <div class="col-md-4">

                    <div class="result-box">

                        <h6>
                            Total Retained
                        </h6>

                        <h3 id="totalRetained">
                            0 g
                        </h3>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="result-box">

                        <h6>
                            Mass Difference
                        </h6>

                        <h3 id="massDifference">
                            0 g
                        </h3>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="result-box">

                        <h6>
                            Mass Balance
                        </h6>

                        <h3 id="massBalance">
                            0%
                        </h3>

                    </div>

                </div>

            </div>


            <!-- SIEVE RESULTS TABLE -->

            <div class="table-responsive mt-4">

                <table class="table table-bordered">

                    <thead>

                        <tr>

                            <th>
                                Sieve
                            </th>

                            <th>
                                Mass Retained (g)
                            </th>

                            <th>
                                % Retained
                            </th>

                            <th>
                                Cumulative % Retained
                            </th>

                            <th>
                                % Passing
                            </th>

                        </tr>

                    </thead>

                    <tbody id="sieveTableBody">

                    </tbody>

                </table>

            </div>


            <!-- SIEVE CHART -->

            <div class="mt-4">

                <h5 class="fw-bold">

                    <i class="bi bi-bar-chart-line"></i>

                    Sieve Analysis Graph

                </h5>

                <div class="chart-container">

                    <canvas id="sieveChart"></canvas>

                </div>

            </div>

        </div>

    </section>


    <!-- =================================================
         CONCLUSION
    ================================================== -->

    <section class="simulation-card">

        <div class="card-body">

            <h2 class="section-title">

                <i class="bi bi-clipboard-check"></i>

                Simulation Conclusion

            </h2>

            <div
                id="simulationConclusion"
                class="alert"
            >

                Run the simulation to generate the conclusion.

            </div>

        </div>

    </section>


    <!-- =================================================
         BOTTOM NAVIGATION
    ================================================== -->

    <div class="bottom-navigation no-print">

        <div class="d-flex flex-wrap gap-2 justify-content-between">

            <div>

                <a
                    href="../practical/practical2.php"
                    class="btn btn-outline-success"
                >

                    <i class="bi bi-arrow-left"></i>

                    Back to Practical

                </a>

            </div>


            <div class="d-flex flex-wrap gap-2">

                <a
                    href="simulation2_history.php"
                    class="btn btn-outline-primary"
                >

                    <i class="bi bi-clock-history"></i>

                    Results History

                </a>


                <a
                    href="simulation2_compare.php"
                    class="btn btn-outline-primary"
                >

                    <i class="bi bi-bar-chart"></i>

                    Compare Trials

                </a>

            </div>

        </div>

    </div>


</main>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="../assets/js/practical2_simulation.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</body>

</html>