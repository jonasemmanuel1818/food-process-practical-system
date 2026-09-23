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

$stmt = $conn->prepare("
    SELECT full_name, username
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$full_name = $user['full_name'] ?? 'Student';
$username = $user['username'] ?? '';

$stmt->close();

$initial = strtoupper(
    substr(trim($full_name), 0, 1)
);
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
    Practical 3 | Thermal Processing Simulation
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


<style>

:root {

    --lab-blue: #1f5f75;
    --lab-dark: #17495a;
    --lab-light: #eaf3f6;

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

    font-family:
        Arial,
        Helvetica,
        sans-serif;

}


a {
    text-decoration: none;
}


/* =========================================================
   HEADER
========================================================= */

.system-header {

    background: #ffffff;

    border-bottom:
        1px solid var(--border);

    min-height: 74px;

    display: flex;

    align-items: center;

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

    background: var(--lab-blue);

    color: #ffffff;

    border-radius: 8px;

    font-size: 21px;

}


.brand-title {

    margin: 0;

    color: var(--lab-dark);

    font-size: 17px;

    font-weight: 700;

}


.brand-subtitle {

    margin: 2px 0 0;

    color: var(--muted);

    font-size: 11px;

}


.student-area {

    display: flex;

    align-items: center;

    gap: 9px;

}


.student-avatar {

    width: 38px;
    height: 38px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: var(--lab-light);

    color: var(--lab-blue);

    font-weight: 700;

}


.student-name {

    font-size: 13px;

    font-weight: 600;

}


.student-role {

    color: var(--muted);

    font-size: 11px;

}


/* =========================================================
   PAGE HEADING
========================================================= */

.page-heading {

    background: #ffffff;

    border-bottom:
        1px solid var(--border);

    padding: 25px 0;

}


.breadcrumb-text {

    margin-bottom: 6px;

    color: var(--muted);

    font-size: 12px;

}


.page-title {

    margin: 0 0 5px;

    color: var(--lab-dark);

    font-size: 27px;

    font-weight: 700;

}


.page-description {

    margin: 0;

    color: var(--muted);

    font-size: 14px;

}


/* =========================================================
   CARDS
========================================================= */

.lab-card {

    margin-bottom: 20px;

    background: #ffffff;

    border:
        1px solid var(--border);

    border-radius: 8px;

    overflow: hidden;

}


.lab-card-header {

    padding: 15px 18px;

    background: #fafbfc;

    border-bottom:
        1px solid var(--border);

    color: var(--lab-dark);

    font-size: 14px;

    font-weight: 700;

}


.lab-card-body {

    padding: 20px;

}


/* =========================================================
   INFORMATION
========================================================= */

.info-box {

    padding: 16px;

    background: var(--lab-light);

    border-left:
        4px solid var(--lab-blue);

    border-radius: 5px;

    font-size: 14px;

    line-height: 1.7;

}


.info-box p:last-child {
    margin-bottom: 0;
}


/* =========================================================
   PARAMETERS
========================================================= */

.parameter-box {

    height: 100%;

    padding: 15px;

    background: #ffffff;

    border:
        1px solid var(--border);

    border-radius: 7px;

}


.parameter-box label {

    display: block;

    margin-bottom: 8px;

    color: var(--lab-dark);

    font-size: 13px;

    font-weight: 700;

}


.parameter-help {

    display: block;

    margin-top: 6px;

    color: var(--muted);

    font-size: 11px;

}


.form-control {

    border-color: #cfd5da;

    border-radius: 5px;

}


.form-control:focus {

    border-color:
        var(--lab-blue);

    box-shadow:
        0 0 0 .15rem
        rgba(31,95,117,.12);

}


/* =========================================================
   CONTROLS
========================================================= */

.control-panel {

    margin-top: 20px;

    padding-top: 18px;

    border-top:
        1px solid var(--border);

}


.control-title {

    margin-bottom: 12px;

    color: var(--lab-dark);

    font-size: 13px;

    font-weight: 700;

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

    background: var(--lab-dark);

    border-color: var(--lab-dark);

    color: #ffffff;

}


/* =========================================================
   PROGRESS
========================================================= */

.progress {

    height: 20px;

    background: #e9ecef;

    border-radius: 4px;

}


.progress-bar {

    background: var(--lab-blue);

    font-size: 11px;

    font-weight: 700;

}


/* =========================================================
   RESULT CARDS
========================================================= */

.result-box {

    height: 100%;

    padding: 17px;

    background: #ffffff;

    border:
        1px solid var(--border);

    border-radius: 7px;

    text-align: center;

}


.result-icon {

    margin-bottom: 7px;

    color: var(--lab-blue);

    font-size: 23px;

}


.result-label {

    color: var(--muted);

    font-size: 12px;

    font-weight: 600;

    line-height: 1.4;

}


.result-value {

    margin-top: 5px;

    color: var(--lab-dark);

    font-size: 21px;

    font-weight: 700;

}


/* =========================================================
   ANALYSIS
========================================================= */

.analysis-box {

    height: 100%;

    padding: 18px;

    border:
        1px solid var(--border);

    border-radius: 7px;

}


.analysis-title {

    margin-bottom: 18px;

    color: var(--lab-dark);

    font-size: 15px;

    font-weight: 700;

}


.analysis-value {

    margin-top: 3px;

    color: var(--text);

    font-size: 15px;

    font-weight: 700;

}


/* =========================================================
   CHART
========================================================= */

.chart-container {

    position: relative;

    width: 100%;

    height: 350px;

}


/* =========================================================
   TABLE
========================================================= */

.table-container {

    max-height: 420px;

    overflow-y: auto;

}


.table thead th {

    position: sticky;

    top: 0;

    z-index: 2;

    background:
        var(--lab-blue);

    color: #ffffff;

    border-color:
        var(--lab-blue);

    white-space: nowrap;

}


/* =========================================================
   FORMULA
========================================================= */

.formula-box {

    padding: 13px 15px;

    margin-bottom: 10px;

    background: #fafbfc;

    border:
        1px solid var(--border);

    border-left:
        4px solid var(--lab-blue);

    border-radius: 5px;

    font-size: 13px;

    line-height: 1.6;

}


.formula-box strong {

    color: var(--lab-dark);

}


/* =========================================================
   FOOTER
========================================================= */

.system-footer {

    margin-top: 30px;

    padding: 20px 0;

    background: #ffffff;

    border-top:
        1px solid var(--border);

    color: var(--muted);

    text-align: center;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 768px) {

    .system-header {

        padding: 12px 0;

    }


    .student-area {

        width: 100%;

        justify-content: flex-end;

    }


    .page-title {

        font-size: 23px;

    }


    .simulation-controls .btn {

        flex:
            1 1 140px;

    }


    .chart-container {

        height: 280px;

    }

}


@media (max-width: 576px) {

    .brand-title {

        font-size: 14px;

    }


    .brand-subtitle {

        font-size: 10px;

    }


    .student-name,
    .student-role {

        display: none;

    }


    .page-heading {

        padding: 20px 0;

    }


    .page-title {

        font-size: 21px;

    }


    .lab-card-body {

        padding: 15px;

    }


    .chart-container {

        height: 240px;

    }

}


/* =========================================================
   PRINT
========================================================= */

@media print {

    .no-print {

        display: none !important;

    }


    body {

        background: #ffffff !important;

    }


    .lab-card {

        break-inside: avoid;

        box-shadow: none !important;

    }

}

</style>

</head>


<body>


<!-- =========================================================
     SYSTEM HEADER
========================================================= -->

<header class="system-header">

<div class="container">

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3">


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

<div class="student-avatar">

    <?= htmlspecialchars($initial) ?>

</div>


<div>

<div class="student-name">

    <?= htmlspecialchars($full_name) ?>

</div>


<div class="student-role">

    Student

</div>

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


<!-- =========================================================
     MAIN
========================================================= -->

<main class="container py-4">


<!-- =========================================================
     ABOUT
========================================================= -->

<div class="lab-card">

<div class="lab-card-header">

    <i class="bi bi-info-circle me-2"></i>

    About the Simulation

</div>


<div class="lab-card-body">

<div class="info-box">

<p>

    This simulation demonstrates the thermal response of a
    food product during heating, processing and cooling
    inside a retort.

</p>


<p>

    It generates temperature data and performs heat
    penetration and regression analysis.

</p>

</div>

</div>

</div>


<!-- =========================================================
     PARAMETERS
========================================================= -->

<div class="lab-card">

<div class="lab-card-header">

    <i class="bi bi-sliders me-2"></i>

    Simulation Parameters

</div>


<div class="lab-card-body">

<form id="simulationForm">

<div class="row g-3">


<!-- INITIAL TEMPERATURE -->

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
    min="0"
    step="0.1"
    required
>


<span class="parameter-help">

    Starting product temperature.

</span>

</div>

</div>


<!-- RETORT -->

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
    min="1"
    step="0.1"
    required
>


<span class="parameter-help">

    Processing temperature.

</span>

</div>

</div>


<!-- HEATING -->

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

    Duration of heating stage.

</span>

</div>

</div>


<!-- PROCESSING -->

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

    Duration at processing temperature.

</span>

</div>

</div>


<!-- COOLING -->

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

    Duration of cooling stage.

</span>

</div>

</div>


</div>


<!-- =====================================================
     CONTROLS
====================================================== -->

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


<!-- =========================================================
     STATUS
========================================================= -->

<div class="lab-card">

<div class="lab-card-header">

    <i class="bi bi-activity me-2"></i>

    Simulation Status

</div>


<div class="lab-card-body">


<div class="d-flex justify-content-between mb-3">

<span class="fw-semibold">

    Current Status

</span>


<span
    id="simulationStatus"
    class="badge bg-secondary"
>

    Ready

</span>

</div>


<!-- HEATING -->

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
    style="width:0%"
>

    0%

</div>

</div>

</div>


<!-- PROCESSING -->

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
    style="width:0%"
>

    0%

</div>

</div>

</div>


<!-- COOLING -->

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
    style="width:0%"
>

    0%

</div>

</div>

</div>


</div>

</div>


<!-- =========================================================
     RESULTS
========================================================= -->

<div class="lab-card">

<div class="lab-card-header">

    <i class="bi bi-bar-chart-line me-2"></i>

    Simulation Results

</div>


<div class="lab-card-body">

<div class="row g-3">


<!-- HEATING TIME -->

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


<!-- PROCESSING -->

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


<!-- COOLING -->

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


<!-- MAX PT -->

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


<!-- HEATING RATE -->

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


<!-- COOLING RATE -->

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


<!-- THERMAL LAG -->

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


<!-- REGRESSION -->

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


<!-- =========================================================
     TEMPERATURE PROFILE
========================================================= -->

<div class="lab-card">

<div class="lab-card-header">

    <i class="bi bi-graph-up me-2"></i>

    Temperature Profile

</div>


<div class="lab-card-body">

<div class="chart-container">

    <canvas
        id="temperatureChart"
    ></canvas>

</div>

</div>

</div>


<!-- =========================================================
     THERMAL ANALYSIS
========================================================= -->

<div class="lab-card">

<div class="lab-card-header">

    <i class="bi bi-calculator me-2"></i>

    Thermal Analysis

</div>


<div class="lab-card-body">

<div class="row g-3">


<!-- HEATING ANALYSIS -->

<div class="col-lg-6">

<div class="analysis-box">

<h5 class="analysis-title">

    <i class="bi bi-fire me-2"></i>

    Heating Analysis

</h5>


<div class="row g-3">


<div class="col-4">

<small class="text-muted">
    f
</small>

<div
    id="analysisHeatingF"
    class="analysis-value"
>
    —
</div>

</div>


<div class="col-4">

<small class="text-muted">
    j
</small>

<div
    id="analysisHeatingJ"
    class="analysis-value"
>
    —
</div>

</div>


<div class="col-4">

<small class="text-muted">
    Slope
</small>

<div
    id="heatingRegressionSlope"
    class="analysis-value"
>
    —
</div>

</div>


<div class="col-6">

<small class="text-muted">
    Intercept
</small>

<div
    id="heatingRegressionIntercept"
    class="analysis-value"
>
    —
</div>

</div>


<div class="col-6">

<small class="text-muted">
    R²
</small>

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


<!-- COOLING ANALYSIS -->

<div class="col-lg-6">

<div class="analysis-box">

<h5 class="analysis-title">

    <i class="bi bi-snow me-2"></i>

    Cooling Analysis

</h5>


<div class="row g-3">


<div class="col-4">

<small class="text-muted">
    fc
</small>

<div
    id="analysisCoolingF"
    class="analysis-value"
>
    —
</div>

</div>


<div class="col-4">

<small class="text-muted">
    jc
</small>

<div
    id="analysisCoolingJ"
    class="analysis-value"
>
    —
</div>

</div>


<div class="col-4">

<small class="text-muted">
    Slope
</small>

<div
    id="coolingRegressionSlope"
    class="analysis-value"
>
    —
</div>

</div>


<div class="col-6">

<small class="text-muted">
    Intercept
</small>

<div
    id="coolingRegressionIntercept"
    class="analysis-value"
>
    —
</div>

</div>


<div class="col-6">

<small class="text-muted">
    R²
</small>

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


<!-- MAXIMUM PT -->

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


<!-- =========================================================
     REGRESSION CHARTS
========================================================= -->

<div class="row g-4">


<!-- HEATING -->

<div class="col-lg-6">

<div class="lab-card">

<div class="lab-card-header">

    <i class="bi bi-graph-up me-2"></i>

    Heating Regression

</div>


<div class="lab-card-body">

<div class="chart-container">

    <canvas
        id="heatingRegressionChart"
    ></canvas>

</div>

</div>

</div>

</div>


<!-- COOLING -->

<div class="col-lg-6">

<div class="lab-card">

<div class="lab-card-header">

    <i class="bi bi-graph-down me-2"></i>

    Cooling Regression

</div>


<div class="lab-card-body">

<div class="chart-container">

    <canvas
        id="coolingRegressionChart"
    ></canvas>

</div>

</div>

</div>

</div>


</div>


<!-- =========================================================
     TEMPERATURE DATA
========================================================= -->

<div class="lab-card">

<div class="lab-card-header">

    <i class="bi bi-table me-2"></i>

    Temperature Data

</div>


<div class="lab-card-body">

<div class="table-container">

<table
    class="table table-bordered table-hover align-middle mb-0"
>

<thead>

<tr>

<th>
    Time (min)
</th>

<th>
    Retort Temperature (°C)
</th>

<th>
    Product Temperature (°C)
</th>

<th>
    Stage
</th>

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


<!-- =========================================================
     CALCULATIONS
========================================================= -->

<div class="lab-card">

<div class="lab-card-header">

    <i class="bi bi-journal-text me-2"></i>

    Calculations Used

</div>


<div class="lab-card-body">


<div class="formula-box">

<strong>
    Heating Rate:
</strong>

<br>

Heating Rate =
(Final Temperature − Initial Temperature)
÷ Heating Time

</div>


<div class="formula-box">

<strong>
    Temperature Difference:
</strong>

<br>

Temperature Difference =
Retort Temperature − Product Temperature

</div>


<div class="formula-box">

<strong>
    Thermal Lag:
</strong>

<br>

Thermal lag describes the response difference
between retort temperature and product temperature.

</div>


<div class="formula-box">

<strong>
    Regression:
</strong>

<br>

Linear regression is used to determine the
relationship between the selected temperature
data and time.

</div>


</div>

</div>


<!-- =========================================================
     NAVIGATION
========================================================= -->

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


<!-- =========================================================
     FOOTER
========================================================= -->

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


<!-- Bootstrap -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<!-- Chart.js -->

<script
    src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"
></script>


<!-- Practical 3 Simulation -->

<script
    src="../assets/js/practical3_simulation.js"
></script>


</body>

</html>