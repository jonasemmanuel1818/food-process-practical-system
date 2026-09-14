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

$stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$full_name = $user['full_name'] ?? 'Student';

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Practical 3 | Thermal Processing Simulation</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        :root {
            --lab-blue: #1f5f75;
            --lab-blue-dark: #17495a;
            --lab-blue-light: #eaf3f6;
            --page-bg: #f4f6f8;
            --border: #d9dee3;
            --text: #263238;
            --muted: #6c757d;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--page-bg);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
        }

        /* =========================
           TOP HEADER
        ========================= */

        .system-header {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            height: 76px;
            display: flex;
            align-items: center;
        }

        .system-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: var(--lab-blue);
            color: #ffffff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .brand-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: var(--lab-blue-dark);
        }

        .brand-subtitle {
            font-size: 12px;
            color: var(--muted);
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
            background: var(--lab-blue-light);
            color: var(--lab-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .student-name {
            font-weight: 600;
            font-size: 14px;
        }

        /* =========================
           PAGE HEADER
        ========================= */

        .page-heading {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 25px 0;
        }

        .breadcrumb-text {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 6px;
        }

        .page-title {
            font-size: 27px;
            font-weight: 700;
            color: var(--lab-blue-dark);
            margin-bottom: 5px;
        }

        .page-description {
            color: var(--muted);
            margin: 0;
        }

        /* =========================
           CARDS
        ========================= */

        .lab-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .lab-card-header {
            padding: 15px 18px;
            border-bottom: 1px solid var(--border);
            background: #fafbfc;
            font-weight: 700;
            color: var(--lab-blue-dark);
        }

        .lab-card-body {
            padding: 20px;
        }

        /* =========================
           INFORMATION BOX
        ========================= */

        .info-box {
            background: var(--lab-blue-light);
            border-left: 4px solid var(--lab-blue);
            padding: 16px;
            border-radius: 5px;
        }

        .info-box p:last-child {
            margin-bottom: 0;
        }

        /* =========================
           PARAMETERS
        ========================= */

        .parameter-box {
            height: 100%;
            border: 1px solid var(--border);
            background: #ffffff;
            border-radius: 7px;
            padding: 15px;
        }

        .parameter-box label {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .parameter-help {
            display: block;
            margin-top: 6px;
            color: var(--muted);
            font-size: 12px;
        }

        .form-control {
            border-color: #cfd5da;
            border-radius: 5px;
        }

        .form-control:focus {
            border-color: var(--lab-blue);
            box-shadow: 0 0 0 0.15rem rgba(31, 95, 117, 0.12);
        }

        /* =========================
           CONTROL PANEL
        ========================= */

        .control-panel {
            border-top: 1px solid var(--border);
            margin-top: 20px;
            padding-top: 18px;
        }

        .control-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--lab-blue-dark);
            margin-bottom: 12px;
        }

        .simulation-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .simulation-controls .btn {
            min-width: 105px;
        }

        .btn-lab {
            background: var(--lab-blue);
            border-color: var(--lab-blue);
            color: #ffffff;
        }

        .btn-lab:hover {
            background: var(--lab-blue-dark);
            border-color: var(--lab-blue-dark);
            color: #ffffff;
        }

        /* =========================
           STATUS
        ========================= */

        .status-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .status-label {
            font-weight: 700;
            color: var(--lab-blue-dark);
        }

        .progress {
            height: 20px;
            border-radius: 4px;
            background: #e9ecef;
        }

        .progress-bar {
            background: var(--lab-blue);
            font-size: 12px;
            font-weight: 700;
        }

        /* =========================
           RESULTS
        ========================= */

        .result-box {
            height: 100%;
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 18px;
            background: #ffffff;
            text-align: center;
        }

        .result-icon {
            font-size: 25px;
            color: var(--lab-blue);
            margin-bottom: 8px;
        }

        .result-label {
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
        }

        .result-value {
            color: var(--lab-blue-dark);
            font-size: 23px;
            font-weight: 700;
            margin-top: 5px;
        }

        /* =========================
           ANALYSIS
        ========================= */

        .analysis-box {
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 18px;
            height: 100%;
        }

        .analysis-title {
            color: var(--lab-blue-dark);
            font-weight: 700;
            margin-bottom: 18px;
        }

        .analysis-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
        }

        /* =========================
           CHARTS
        ========================= */

        .chart-container {
            position: relative;
            width: 100%;
            height: 350px;
        }

        /* =========================
           TABLE
        ========================= */

        .table-container {
            max-height: 400px;
            overflow-y: auto;
        }

        .table thead th {
            background: var(--lab-blue);
            color: #ffffff;
            border-color: var(--lab-blue);
            white-space: nowrap;
        }

        /* =========================
           FORMULAS
        ========================= */

        .formula-box {
            background: #fafbfc;
            border: 1px solid var(--border);
            border-left: 4px solid var(--lab-blue);
            padding: 13px 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .formula-box strong {
            color: var(--lab-blue-dark);
        }

        /* =========================
           FOOTER
        ========================= */

        .system-footer {
            background: #ffffff;
            border-top: 1px solid var(--border);
            margin-top: 30px;
            padding: 20px 0;
            color: var(--muted);
            text-align: center;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 768px) {

            .system-header {
                height: auto;
                padding: 12px 0;
            }

            .student-area {
                width: 100%;
                justify-content: space-between;
            }

            .page-title {
                font-size: 23px;
            }

            .lab-card-body {
                padding: 15px;
            }

            .chart-container {
                height: 280px;
            }

            .simulation-controls .btn {
                flex: 1 1 140px;
            }

        }

        /* =========================
           PRINT
        ========================= */

        @media print {

            .no-print {
                display: none !important;
            }

            body {
                background: #ffffff !important;
            }

            .lab-card {
                border: 1px solid #cccccc !important;
                break-inside: avoid;
            }

            .system-header,
            .page-heading {
                border-bottom: 1px solid #cccccc;
            }

        }

    </style>

</head>

<body>

<!-- SYSTEM HEADER -->

<header class="system-header">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div class="system-brand">

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

                <div class="student-avatar">
                    <i class="bi bi-person"></i>
                </div>

                <div>
                    <div class="student-name">
                        <?= htmlspecialchars($full_name) ?>
                    </div>

                    <small class="text-muted">
                        Student
                    </small>
                </div>

            </div>

        </div>

    </div>

</header>


<!-- PAGE HEADING -->

<section class="page-heading">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>

                <div class="breadcrumb-text">
                    Practical 3 / Simulation
                </div>

                <h1 class="page-title">
                    Thermal Processing Simulation
                </h1>

                <p class="page-description">
                    Heat Penetration and Thermal Response Analysis
                </p>

            </div>

            <div class="no-print">

                <a
                    href="../practical/practical3.php"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-arrow-left"></i>
                    Back to Practical 3
                </a>

                <a
                    href="../dashboard.php"
                    class="btn btn-lab"
                >
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>

            </div>

        </div>

    </div>

</section>


<main class="container py-4">


    <!-- INTRODUCTION -->

    <div class="lab-card">

        <div class="lab-card-header">

            <i class="bi bi-info-circle me-2"></i>
            About the Simulation

        </div>

        <div class="lab-card-body">

            <div class="info-box">

                <p>
                    This simulation demonstrates temperature changes during
                    heating, processing and cooling of a food product in a
                    retort.
                </p>

                <p>
                    The simulation calculates the maximum product temperature,
                    heating rate, cooling rate, thermal lag and regression
                    parameters.
                </p>

            </div>

        </div>

    </div>


    <!-- PARAMETERS -->

    <div class="lab-card">

        <div class="lab-card-header">

            <i class="bi bi-sliders me-2"></i>
            Simulation Parameters

        </div>

        <div class="lab-card-body">

            <form id="simulationForm">

                <div class="row g-3">

                    <div class="col-lg-4 col-md-6">

                        <div class="parameter-box">

                            <label for="initialTemp">
                                Initial Product Temperature (°C)
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                id="initialTemp"
                                value="25"
                                step="0.1"
                                required
                            >

                            <span class="parameter-help">
                                Starting temperature of the product.
                            </span>

                        </div>

                    </div>


                    <div class="col-lg-4 col-md-6">

                        <div class="parameter-box">

                            <label for="retortTemp">
                                Retort Temperature (°C)
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                id="retortTemp"
                                value="121"
                                step="0.1"
                                required
                            >

                            <span class="parameter-help">
                                Target processing temperature.
                            </span>

                        </div>

                    </div>


                    <div class="col-lg-4 col-md-6">

                        <div class="parameter-box">

                            <label for="heatingTime">
                                Heating Time (min)
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                id="heatingTime"
                                value="10"
                                min="1"
                                step="1"
                                required
                            >

                            <span class="parameter-help">
                                Duration of the heating stage.
                            </span>

                        </div>

                    </div>


                    <div class="col-lg-4 col-md-6">

                        <div class="parameter-box">

                            <label for="processingTime">
                                Processing Time (min)
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                id="processingTime"
                                value="15"
                                min="1"
                                step="1"
                                required
                            >

                            <span class="parameter-help">
                                Time maintained during processing.
                            </span>

                        </div>

                    </div>


                    <div class="col-lg-4 col-md-6">

                        <div class="parameter-box">

                            <label for="coolingTime">
                                Cooling Time (min)
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                id="coolingTime"
                                value="12"
                                min="1"
                                step="1"
                                required
                            >

                            <span class="parameter-help">
                                Duration of the cooling stage.
                            </span>

                        </div>

                    </div>

                </div>


                <!-- CONTROLS -->

                <div class="control-panel no-print">

                    <div class="control-title">

                        <i class="bi bi-sliders2-vertical me-1"></i>
                        Simulation Controls

                    </div>

                    <div class="simulation-controls">

                        <button
                            type="button"
                            class="btn btn-lab"
                            id="startSimulationButton"
                        >
                            <i class="bi bi-play-fill"></i>
                            Start
                        </button>


                        <button
                            type="button"
                            class="btn btn-warning"
                            id="pauseSimulationButton"
                            disabled
                        >
                            <i class="bi bi-pause-fill"></i>
                            Pause
                        </button>


                        <button
                            type="button"
                            class="btn btn-success"
                            id="resumeSimulationButton"
                            disabled
                        >
                            <i class="bi bi-play-circle"></i>
                            Resume
                        </button>


                        <button
                            type="button"
                            class="btn btn-danger"
                            id="stopSimulationButton"
                            disabled
                        >
                            <i class="bi bi-stop-fill"></i>
                            Stop
                        </button>


                        <button
                            type="button"
                            class="btn btn-secondary"
                            id="resetButton"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Reset
                        </button>


                        <button
                            type="button"
                            class="btn btn-dark"
                            id="generateReportButton"
                            disabled
                        >
                            <i class="bi bi-file-earmark-text"></i>
                            Generate Report
                        </button>


                        <button
                            type="button"
                            class="btn btn-info text-white"
                            id="saveResultsButton"
                            disabled
                        >
                            <i class="bi bi-database"></i>
                            Save Results
                        </button>


                        <a
                            href="simulation3_history.php"
                            class="btn btn-outline-primary"
                        >
                            <i class="bi bi-clock-history"></i>
                            Results History
                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- STATUS -->

    <div class="lab-card">

        <div class="lab-card-header">

            <i class="bi bi-activity me-2"></i>
            Simulation Status

        </div>

        <div class="lab-card-body">

            <div class="status-row">

                <span class="status-label">
                    Current Status
                </span>

                <span
                    id="simulationStatus"
                    class="badge bg-secondary"
                >
                    Ready
                </span>

            </div>


            <div class="mb-3">

                <div class="d-flex justify-content-between mb-1">

                    <span class="small fw-semibold">
                        Heating
                    </span>

                    <span
                        id="heatingPercent"
                        class="small"
                    >
                        0%
                    </span>

                </div>

                <div class="progress">

                    <div
                        id="heatingProgress"
                        class="progress-bar"
                        role="progressbar"
                        style="width: 0%"
                    >
                        0%
                    </div>

                </div>

            </div>


            <div class="mb-3">

                <div class="d-flex justify-content-between mb-1">

                    <span class="small fw-semibold">
                        Processing
                    </span>

                    <span
                        id="processingPercent"
                        class="small"
                    >
                        0%
                    </span>

                </div>

                <div class="progress">

                    <div
                        id="processingProgress"
                        class="progress-bar"
                        role="progressbar"
                        style="width: 0%"
                    >
                        0%
                    </div>

                </div>

            </div>


            <div>

                <div class="d-flex justify-content-between mb-1">

                    <span class="small fw-semibold">
                        Cooling
                    </span>

                    <span
                        id="coolingPercent"
                        class="small"
                    >
                        0%
                    </span>

                </div>

                <div class="progress">

                    <div
                        id="coolingProgress"
                        class="progress-bar"
                        role="progressbar"
                        style="width: 0%"
                    >
                        0%
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- RESULTS -->

    <div class="lab-card">

        <div class="lab-card-header">

            <i class="bi bi-bar-chart-line me-2"></i>
            Simulation Results

        </div>

        <div class="lab-card-body">

            <div class="row g-3">

                <div class="col-lg-4 col-md-6">

                    <div class="result-box">

                        <div class="result-icon">
                            <i class="bi bi-fire"></i>
                        </div>

                        <div class="result-label">
                            Heating Time
                        </div>

                        <div
                            id="resultHeatingTime"
                            class="result-value"
                        >
                            —
                        </div>

                    </div>

                </div>


                <div class="col-lg-4 col-md-6">

                    <div class="result-box">

                        <div class="result-icon">
                            <i class="bi bi-thermometer-half"></i>
                        </div>

                        <div class="result-label">
                            Processing Time
                        </div>

                        <div
                            id="resultProcessingTime"
                            class="result-value"
                        >
                            —
                        </div>

                    </div>

                </div>


                <div class="col-lg-4 col-md-6">

                    <div class="result-box">

                        <div class="result-icon">
                            <i class="bi bi-snow"></i>
                        </div>

                        <div class="result-label">
                            Cooling Time
                        </div>

                        <div
                            id="resultCoolingTime"
                            class="result-value"
                        >
                            —
                        </div>

                    </div>

                </div>


                <div class="col-lg-3 col-md-6">

                    <div class="result-box">

                        <div class="result-label">
                            Maximum Product Temperature
                        </div>

                        <div
                            id="maxTemperature"
                            class="result-value"
                        >
                            —
                        </div>

                    </div>

                </div>


                <div class="col-lg-3 col-md-6">

                    <div class="result-box">

                        <div class="result-label">
                            Heating Rate (f)
                        </div>

                        <div
                            id="heatingRate"
                            class="result-value"
                        >
                            —
                        </div>

                    </div>

                </div>


                <div class="col-lg-3 col-md-6">

                    <div class="result-box">

                        <div class="result-label">
                            Cooling Rate (fc)
                        </div>

                        <div
                            id="coolingRate"
                            class="result-value"
                        >
                            —
                        </div>

                    </div>

                </div>


                <div class="col-lg-3 col-md-6">

                    <div class="result-box">

                        <div class="result-label">
                            Thermal Lag (j)
                        </div>

                        <div
                            id="thermalLag"
                            class="result-value"
                        >
                            —
                        </div>

                    </div>

                </div>


                <div class="col-12">

                    <div class="result-box">

                        <div class="result-label">
                            Regression Slope
                        </div>

                        <div
                            id="regressionSlope"
                            class="result-value"
                        >
                            —
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- TEMPERATURE PROFILE -->

    <div class="lab-card">

        <div class="lab-card-header">

            <i class="bi bi-graph-up me-2"></i>
            Temperature Profile

        </div>

        <div class="lab-card-body">

            <div class="chart-container">

                <canvas id="temperatureChart"></canvas>

            </div>

        </div>

    </div>


    <!-- THERMAL ANALYSIS -->

    <div class="lab-card">

        <div class="lab-card-header">

            <i class="bi bi-calculator me-2"></i>
            Thermal Analysis

        </div>

        <div class="lab-card-body">

            <div class="row g-3">

                <div class="col-lg-6">

                    <div class="analysis-box">

                        <h5 class="analysis-title">
                            <i class="bi bi-fire me-2"></i>
                            Heating Analysis
                        </h5>

                        <div class="row g-3">

                            <div class="col-4">
                                <small class="text-muted">f</small>
                                <div
                                    id="analysisHeatingF"
                                    class="analysis-value"
                                >
                                    —
                                </div>
                            </div>

                            <div class="col-4">
                                <small class="text-muted">j</small>
                                <div
                                    id="analysisHeatingJ"
                                    class="analysis-value"
                                >
                                    —
                                </div>
                            </div>

                            <div class="col-4">
                                <small class="text-muted">Slope</small>
                                <div
                                    id="heatingRegressionSlope"
                                    class="analysis-value"
                                >
                                    —
                                </div>
                            </div>

                            <div class="col-6">
                                <small class="text-muted">Intercept</small>
                                <div
                                    id="heatingRegressionIntercept"
                                    class="analysis-value"
                                >
                                    —
                                </div>
                            </div>

                            <div class="col-6">
                                <small class="text-muted">R²</small>
                                <div
                                    id="heatingRegressionR2"
                                    class="analysis-value"
                                >
                                    —
                                </div>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-lg-6">

                    <div class="analysis-box">

                        <h5 class="analysis-title">
                            <i class="bi bi-snow me-2"></i>
                            Cooling Analysis
                        </h5>

                        <div class="row g-3">

                            <div class="col-4">
                                <small class="text-muted">fc</small>
                                <div
                                    id="analysisCoolingF"
                                    class="analysis-value"
                                >
                                    —
                                </div>
                            </div>

                            <div class="col-4">
                                <small class="text-muted">jc</small>
                                <div
                                    id="analysisCoolingJ"
                                    class="analysis-value"
                                >
                                    —
                                </div>
                            </div>

                            <div class="col-4">
                                <small class="text-muted">Slope</small>
                                <div
                                    id="coolingRegressionSlope"
                                    class="analysis-value"
                                >
                                    —
                                </div>
                            </div>

                            <div class="col-6">
                                <small class="text-muted">Intercept</small>
                                <div
                                    id="coolingRegressionIntercept"
                                    class="analysis-value"
                                >
                                    —
                                </div>
                            </div>

                            <div class="col-6">
                                <small class="text-muted">R²</small>
                                <div
                                    id="coolingRegressionR2"
                                    class="analysis-value"
                                >
                                    —
                                </div>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="col-12">

                    <div class="analysis-box">

                        <h5 class="analysis-title">
                            Maximum Product Temperature
                        </h5>

                        <div class="row g-3">

                            <div class="col-md-6">

                                <small class="text-muted">
                                    Maximum PT
                                </small>

                                <div
                                    id="analysisMaxPT"
                                    class="analysis-value"
                                >
                                    —
                                </div>

                            </div>

                            <div class="col-md-6">

                                <small class="text-muted">
                                    Thermal Lag
                                </small>

                                <div
                                    id="thermalLagAnalysis"
                                    class="analysis-value"
                                >
                                    —
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- REGRESSION CHARTS -->

    <div class="row g-4">

        <div class="col-lg-6">

            <div class="lab-card">

                <div class="lab-card-header">

                    <i class="bi bi-graph-up me-2"></i>
                    Heating Regression

                </div>

                <div class="lab-card-body">

                    <div class="chart-container">

                        <canvas id="heatingRegressionChart"></canvas>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-lg-6">

            <div class="lab-card">

                <div class="lab-card-header">

                    <i class="bi bi-graph-down me-2"></i>
                    Cooling Regression

                </div>

                <div class="lab-card-body">

                    <div class="chart-container">

                        <canvas id="coolingRegressionChart"></canvas>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- TEMPERATURE DATA -->

    <div class="lab-card">

        <div class="lab-card-header">

            <i class="bi bi-table me-2"></i>
            Temperature Data

        </div>

        <div class="lab-card-body">

            <div class="table-container">

                <table class="table table-bordered table-hover align-middle mb-0">

                    <thead>

                        <tr>

                            <th>Time (min)</th>
                            <th>Retort Temperature (°C)</th>
                            <th>Product Temperature (°C)</th>
                            <th>Stage</th>

                        </tr>

                    </thead>

                    <tbody id="temperatureTableBody">

                        <tr>

                            <td
                                colspan="4"
                                class="text-center text-muted py-4"
                            >
                                Run the simulation to generate data.
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- CALCULATIONS -->

    <div class="lab-card">

        <div class="lab-card-header">

            <i class="bi bi-journal-text me-2"></i>
            Calculations Used

        </div>

        <div class="lab-card-body">

            <div class="formula-box">

                <strong>Heating Rate:</strong><br>

                Heating Rate =
                (Final Temperature − Initial Temperature)
                ÷ Heating Time

            </div>


            <div class="formula-box">

                <strong>Temperature Difference:</strong><br>

                Temperature Difference =
                Retort Temperature − Product Temperature

            </div>


            <div class="formula-box">

                <strong>Thermal Lag:</strong><br>

                Thermal lag describes the difference in response
                between the retort temperature and product temperature.

            </div>


            <p class="text-muted mb-0">

                The simulation values are generated from the mathematical
                model implemented for Practical 3.

            </p>

        </div>

    </div>


    <!-- NAVIGATION -->

    <div class="d-flex flex-wrap gap-2 mb-4 no-print">

        <a
            href="../practical/practical3.php"
            class="btn btn-outline-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Practical 3
        </a>

        <a
            href="simulation3_history.php"
            class="btn btn-outline-primary"
        >
            <i class="bi bi-clock-history"></i>
            Results History
        </a>

        <a
            href="../dashboard.php"
            class="btn btn-lab"
        >
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>

    </div>

</main>


<footer class="system-footer">

    <div class="container">

        <div class="fw-semibold">
            Food Process Practical Learning & Simulation System
        </div>

        <small>
            Practical 3 — Thermal Processing Simulation
        </small>

    </div>

</footer>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="../assets/js/practical3_simulation.js"></script>

</body>

</html>