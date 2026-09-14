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
   LOAD SAVED THERMAL PROCESSING TRIALS
   ========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        input_data,
        result_data,
        created_at
    FROM simulation_results
    WHERE user_id = ?
      AND practical_number = 3
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$trials = [];

while ($row = $result->fetch_assoc()) {

    $input = json_decode(
        $row['input_data'],
        true
    );

    $output = json_decode(
        $row['result_data'],
        true
    );

    $trials[] = [
        'id' => (int)$row['id'],
        'created_at' => $row['created_at'],
        'input' => $input ?: [],
        'output' => $output ?: []
    ];
}

$stmt->close();


/* =========================================================
   REQUIRE AT LEAST TWO TRIALS
   ========================================================= */

if (count($trials) < 2) {
    header("Location: simulation3_history.php");
    exit();
}


/* =========================================================
   SELECT TRIALS
   ========================================================= */

$trialAId = isset($_GET['trial_a'])
    ? (int)$_GET['trial_a']
    : $trials[0]['id'];

$trialBId = isset($_GET['trial_b'])
    ? (int)$_GET['trial_b']
    : $trials[1]['id'];

$trialA = null;
$trialB = null;

foreach ($trials as $trial) {

    if ($trial['id'] === $trialAId) {
        $trialA = $trial;
    }

    if ($trial['id'] === $trialBId) {
        $trialB = $trial;
    }
}


/* =========================================================
   FALLBACK
   ========================================================= */

if (!$trialA) {
    $trialA = $trials[0];
}

if (!$trialB) {
    $trialB = $trials[1];
}


/* =========================================================
   HELPER FUNCTIONS
   ========================================================= */

function valueFrom($trial, $section, $key)
{
    return (float)(
        $trial[$section][$key] ?? 0
    );
}

function difference($a, $b)
{
    return $b - $a;
}

function percentageDifference($a, $b)
{
    if ($a == 0) {
        return 0;
    }

    return (($b - $a) / abs($a)) * 100;
}

function formatNumber($value, $decimals = 4)
{
    return number_format(
        (float)$value,
        $decimals
    );
}


/* =========================================================
   INPUT VALUES
   ========================================================= */

$aInitial =
    valueFrom($trialA, 'input', 'initialTemp');

$bInitial =
    valueFrom($trialB, 'input', 'initialTemp');

$aRetort =
    valueFrom($trialA, 'input', 'retortTemp');

$bRetort =
    valueFrom($trialB, 'input', 'retortTemp');

$aHeating =
    valueFrom($trialA, 'input', 'heatingTime');

$bHeating =
    valueFrom($trialB, 'input', 'heatingTime');

$aProcessing =
    valueFrom($trialA, 'input', 'processingTime');

$bProcessing =
    valueFrom($trialB, 'input', 'processingTime');

$aCooling =
    valueFrom($trialA, 'input', 'coolingTime');

$bCooling =
    valueFrom($trialB, 'input', 'coolingTime');


/* =========================================================
   RESULT VALUES
   ========================================================= */

$aMaxPT =
    valueFrom($trialA, 'output', 'maxTemperature');

$bMaxPT =
    valueFrom($trialB, 'output', 'maxTemperature');

$aHeatingRate =
    valueFrom($trialA, 'output', 'heatingRate');

$bHeatingRate =
    valueFrom($trialB, 'output', 'heatingRate');

$aCoolingRate =
    valueFrom($trialA, 'output', 'coolingRate');

$bCoolingRate =
    valueFrom($trialB, 'output', 'coolingRate');

$aThermalLag =
    valueFrom($trialA, 'output', 'thermalLag');

$bThermalLag =
    valueFrom($trialB, 'output', 'thermalLag');

$aRegressionSlope =
    valueFrom($trialA, 'output', 'regressionSlope');

$bRegressionSlope =
    valueFrom($trialB, 'output', 'regressionSlope');


/* =========================================================
   NESTED REGRESSION VALUES
   ========================================================= */

$aHeatingRegression =
    $trialA['output']['heatingRegression'] ?? [];

$bHeatingRegression =
    $trialB['output']['heatingRegression'] ?? [];

$aCoolingRegression =
    $trialA['output']['coolingRegression'] ?? [];

$bCoolingRegression =
    $trialB['output']['coolingRegression'] ?? [];


/* Heating */

$aHeatingF =
    (float)($aHeatingRegression['f'] ?? 0);

$bHeatingF =
    (float)($bHeatingRegression['f'] ?? 0);

$aHeatingJ =
    (float)($aHeatingRegression['j'] ?? 0);

$bHeatingJ =
    (float)($bHeatingRegression['j'] ?? 0);

$aHeatingSlope =
    (float)($aHeatingRegression['slope'] ?? 0);

$bHeatingSlope =
    (float)($bHeatingRegression['slope'] ?? 0);

$aHeatingIntercept =
    (float)($aHeatingRegression['intercept'] ?? 0);

$bHeatingIntercept =
    (float)($bHeatingRegression['intercept'] ?? 0);

$aHeatingR2 =
    (float)($aHeatingRegression['r2'] ?? 0);

$bHeatingR2 =
    (float)($bHeatingRegression['r2'] ?? 0);


/* Cooling */

$aCoolingF =
    (float)($aCoolingRegression['fc'] ?? 0);

$bCoolingF =
    (float)($bCoolingRegression['fc'] ?? 0);

$aCoolingJ =
    (float)($aCoolingRegression['jc'] ?? 0);

$bCoolingJ =
    (float)($bCoolingRegression['jc'] ?? 0);

$aCoolingSlope =
    (float)($aCoolingRegression['slope'] ?? 0);

$bCoolingSlope =
    (float)($bCoolingRegression['slope'] ?? 0);

$aCoolingIntercept =
    (float)($aCoolingRegression['intercept'] ?? 0);

$bCoolingIntercept =
    (float)($bCoolingRegression['intercept'] ?? 0);

$aCoolingR2 =
    (float)($aCoolingRegression['r2'] ?? 0);

$bCoolingR2 =
    (float)($bCoolingRegression['r2'] ?? 0);


/* =========================================================
   DIFFERENCES
   ========================================================= */

$maxPTDifference =
    difference($aMaxPT, $bMaxPT);

$heatingRateDifference =
    difference($aHeatingRate, $bHeatingRate);

$coolingRateDifference =
    difference($aCoolingRate, $bCoolingRate);

$thermalLagDifference =
    difference($aThermalLag, $bThermalLag);

$heatingR2Difference =
    difference($aHeatingR2, $bHeatingR2);

$coolingR2Difference =
    difference($aCoolingR2, $bCoolingR2);


/* =========================================================
   DETERMINE BETTER TRIAL
   ========================================================= */

$betterTemperatureTrial = '';

if ($aMaxPT > $bMaxPT) {

    $betterTemperatureTrial = 'A';

} elseif ($bMaxPT > $aMaxPT) {

    $betterTemperatureTrial = 'B';

} else {

    $betterTemperatureTrial = 'Equal';
}


$betterHeatingTrial = '';

if ($aHeatingRate > $bHeatingRate) {

    $betterHeatingTrial = 'A';

} elseif ($bHeatingRate > $aHeatingRate) {

    $betterHeatingTrial = 'B';

} else {

    $betterHeatingTrial = 'Equal';
}


$betterCoolingTrial = '';

if ($aCoolingRate > $bCoolingRate) {

    $betterCoolingTrial = 'A';

} elseif ($bCoolingRate > $aCoolingRate) {

    $betterCoolingTrial = 'B';

} else {

    $betterCoolingTrial = 'Equal';
}


/* =========================================================
   INTERPRETATION
   ========================================================= */

$interpretation = [];

if ($aMaxPT > $bMaxPT) {

    $interpretation[] =
        "Trial A reached a higher maximum product temperature than Trial B.";

} elseif ($bMaxPT > $aMaxPT) {

    $interpretation[] =
        "Trial B reached a higher maximum product temperature than Trial A.";

} else {

    $interpretation[] =
        "Both trials reached the same maximum product temperature.";
}


if ($aHeatingRate > $bHeatingRate) {

    $interpretation[] =
        "Trial A had the higher heating rate.";

} elseif ($bHeatingRate > $aHeatingRate) {

    $interpretation[] =
        "Trial B had the higher heating rate.";
}


if ($aCoolingRate > $bCoolingRate) {

    $interpretation[] =
        "Trial A had the higher cooling rate.";

} elseif ($bCoolingRate > $aCoolingRate) {

    $interpretation[] =
        "Trial B had the higher cooling rate.";
}


if ($aHeatingR2 > $bHeatingR2) {

    $interpretation[] =
        "Trial A produced the stronger heating regression fit based on R².";

} elseif ($bHeatingR2 > $aHeatingR2) {

    $interpretation[] =
        "Trial B produced the stronger heating regression fit based on R².";
}


if ($aCoolingR2 > $bCoolingR2) {

    $interpretation[] =
        "Trial A produced the stronger cooling regression fit based on R².";

} elseif ($bCoolingR2 > $aCoolingR2) {

    $interpretation[] =
        "Trial B produced the stronger cooling regression fit based on R².";
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
        Practical 3 - Compare Thermal Trials
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
            --lab-blue-dark: #17485a;
            --lab-blue-light: #eaf3f6;

            --page-bg: #f4f6f8;
            --border: #d9dee3;

            --success: #198754;
            --danger: #dc3545;
            --warning: #ffc107;

            --text: #25313a;
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


        /* =====================================================
           TOP HEADER
           ===================================================== */

        .top-header {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            min-height: 76px;
            display: flex;
            align-items: center;
        }


        .brand-area {
            display: flex;
            align-items: center;
            gap: 14px;
        }


        .brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;

            background: var(--lab-blue);
            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
        }


        .system-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--lab-blue-dark);
            margin: 0;
        }


        .system-subtitle {
            font-size: 12px;
            color: var(--muted);
            margin: 2px 0 0;
        }


        /* =====================================================
           PAGE HEADER
           ===================================================== */

        .page-header {
            background: var(--lab-blue);
            color: #ffffff;
            padding: 28px 0;
        }


        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 5px;
        }


        .page-header p {
            margin: 0;
            opacity: .9;
        }


        .practical-badge {
            display: inline-block;
            background: #ffffff;
            color: var(--lab-blue);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .5px;
            margin-bottom: 10px;
        }


        /* =====================================================
           MAIN
           ===================================================== */

        main {
            padding-top: 28px;
            padding-bottom: 45px;
        }


        /* =====================================================
           CARDS
           ===================================================== */

        .compare-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 22px;
            overflow: hidden;
        }


        .compare-card .card-body {
            padding: 24px;
        }


        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;

            color: var(--lab-blue-dark);

            font-size: 20px;
            font-weight: 700;

            margin-bottom: 20px;
        }


        .section-title i {
            color: var(--lab-blue);
        }


        /* =====================================================
           TRIAL CARDS
           ===================================================== */

        .trial-card {
            height: 100%;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }


        .trial-card-header {
            padding: 15px 18px;
            font-weight: 700;
            color: #ffffff;
        }


        .trial-a-header {
            background: var(--lab-blue);
        }


        .trial-b-header {
            background: #5b6870;
        }


        .trial-card-body {
            padding: 20px;
        }


        .trial-date {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 18px;
        }


        .input-metric {
            padding: 12px;
            background: #f8f9fa;
            border: 1px solid var(--border);
            border-radius: 6px;
            height: 100%;
        }


        .input-metric-label {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 4px;
        }


        .input-metric-value {
            color: var(--lab-blue-dark);
            font-size: 17px;
            font-weight: 700;
        }


        /* =====================================================
           SELECTION AREA
           ===================================================== */

        .selection-card {
            border-left: 4px solid var(--lab-blue);
        }


        .form-label {
            color: #39464e;
            font-size: 13px;
            margin-bottom: 6px;
        }


        .form-select {
            border-color: var(--border);
            min-height: 43px;
        }


        .form-select:focus {
            border-color: var(--lab-blue);
            box-shadow:
                0 0 0 .2rem rgba(31, 95, 117, .12);
        }


        /* =====================================================
           TABLES
           ===================================================== */

        .table {
            margin-bottom: 0;
        }


        .table thead th {
            font-size: 13px;
            font-weight: 700;
            vertical-align: middle;
            border-color: var(--border);
        }


        .table tbody td {
            font-size: 14px;
            border-color: var(--border);
            padding: 12px;
        }


        .main-table thead th {
            background: var(--lab-blue);
            color: #ffffff;
        }


        .heating-table thead th {
            background: var(--lab-blue-light);
            color: var(--lab-blue-dark);
        }


        .cooling-table thead th {
            background: #eef5f7;
            color: var(--lab-blue-dark);
        }


        .metric-name {
            font-weight: 600;
            color: #36434b;
        }


        .trial-value-a {
            font-weight: 700;
            color: var(--lab-blue);
        }


        .trial-value-b {
            font-weight: 700;
            color: #4f5c64;
        }


        .difference-cell {
            font-weight: 700;
        }


        .difference-positive {
            color: var(--success);
        }


        .difference-negative {
            color: var(--danger);
        }


        /* =====================================================
           SUMMARY METRICS
           ===================================================== */

        .summary-box {
            background: #f8f9fa;
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 16px;
            height: 100%;
        }


        .summary-label {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 5px;
        }


        .summary-value {
            font-size: 21px;
            font-weight: 700;
            color: var(--lab-blue-dark);
        }


        /* =====================================================
           INTERPRETATION
           ===================================================== */

        .interpretation-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;

            padding: 13px 15px;

            background: #f8f9fa;

            border: 1px solid var(--border);
            border-left: 4px solid var(--lab-blue);

            border-radius: 5px;

            margin-bottom: 10px;

            font-size: 14px;
        }


        .interpretation-item i {
            color: var(--success);
            margin-top: 2px;
        }


        .info-note {
            background: #eef6f8;
            border: 1px solid #cfe2e7;
            color: #36525c;
            border-radius: 6px;
            padding: 15px;
            font-size: 13px;
        }


        /* =====================================================
           BUTTONS
           ===================================================== */

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


        .btn-outline-lab {
            color: var(--lab-blue);
            border-color: var(--lab-blue);
            background: #ffffff;
        }


        .btn-outline-lab:hover {
            background: var(--lab-blue);
            border-color: var(--lab-blue);
            color: #ffffff;
        }


        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }


        /* =====================================================
           FOOTER
           ===================================================== */

        .page-footer {
            border-top: 1px solid var(--border);
            background: #ffffff;
            color: var(--muted);
            text-align: center;
            padding: 18px;
            font-size: 12px;
        }


        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 767px) {

            .top-header {
                min-height: auto;
                padding: 14px 0;
            }

            .system-title {
                font-size: 15px;
            }

            .system-subtitle {
                display: none;
            }

            .page-header {
                padding: 22px 0;
            }

            .page-header h1 {
                font-size: 23px;
            }

            .compare-card .card-body {
                padding: 18px;
            }

            .action-buttons .btn {
                width: 100%;
            }

        }


        /* =====================================================
           PRINT
           ===================================================== */

        @media print {

            body {
                background: #ffffff !important;
            }

            .no-print {
                display: none !important;
            }

            .top-header {
                border-bottom: 1px solid #999;
            }

            .page-header {
                background: #ffffff !important;
                color: #000000 !important;
                border-bottom: 2px solid #333;
            }

            .practical-badge {
                border: 1px solid #555;
                color: #000000;
            }

            .compare-card {
                box-shadow: none !important;
                break-inside: avoid;
            }

            .page-footer {
                border-top: 1px solid #999;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     SYSTEM HEADER
     ========================================================= -->

<header class="top-header">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center">

            <div class="brand-area">

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


            <div class="no-print">

                <a
                    href="simulation3_history.php"
                    class="btn btn-outline-lab btn-sm"
                >

                    <i class="bi bi-clock-history"></i>

                    Results History

                </a>

            </div>

        </div>

    </div>

</header>



<!-- =========================================================
     PAGE HEADER
     ========================================================= -->

<section class="page-header">

    <div class="container">

        <span class="practical-badge">
            PRACTICAL 3
        </span>

        <h1>
            Compare Thermal Processing Trials
        </h1>

        <p>
            Thermal Processing Simulation — Heat Penetration Analysis
        </p>

    </div>

</section>



<!-- =========================================================
     MAIN CONTENT
     ========================================================= -->

<main class="container">


    <!-- =====================================================
         TRIAL SELECTION
         ===================================================== -->

    <div class="compare-card selection-card no-print">

        <div class="card-body">

            <div class="section-title">

                <i class="bi bi-sliders"></i>

                <span>
                    Select Trials to Compare
                </span>

            </div>


            <form
                method="GET"
                class="row g-3"
            >

                <div class="col-md-5">

                    <label class="form-label fw-bold">

                        Trial A

                    </label>

                    <select
                        name="trial_a"
                        class="form-select"
                    >

                        <?php foreach ($trials as $trial): ?>

                            <option
                                value="<?= $trial['id'] ?>"
                                <?= $trial['id'] === $trialA['id']
                                    ? 'selected'
                                    : '' ?>
                            >

                                Trial #<?= $trial['id'] ?>

                                —

                                <?= htmlspecialchars(
                                    $trial['created_at']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-5">

                    <label class="form-label fw-bold">

                        Trial B

                    </label>

                    <select
                        name="trial_b"
                        class="form-select"
                    >

                        <?php foreach ($trials as $trial): ?>

                            <option
                                value="<?= $trial['id'] ?>"
                                <?= $trial['id'] === $trialB['id']
                                    ? 'selected'
                                    : '' ?>
                            >

                                Trial #<?= $trial['id'] ?>

                                —

                                <?= htmlspecialchars(
                                    $trial['created_at']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-2 d-flex align-items-end">

                    <button
                        type="submit"
                        class="btn btn-lab w-100"
                    >

                        <i class="bi bi-arrow-repeat"></i>

                        Compare

                    </button>

                </div>

            </form>

        </div>

    </div>



    <!-- =====================================================
         TRIAL INFORMATION
         ===================================================== -->

    <div class="row g-4 mb-4">


        <!-- TRIAL A -->

        <div class="col-md-6">

            <div class="trial-card">

                <div class="trial-card-header trial-a-header">

                    <i class="bi bi-1-circle me-2"></i>

                    Trial A

                </div>


                <div class="trial-card-body">

                    <div class="trial-date">

                        <i class="bi bi-calendar3 me-1"></i>

                        Saved:

                        <?= htmlspecialchars(
                            $trialA['created_at']
                        ) ?>

                    </div>


                    <div class="row g-3">


                        <div class="col-6">

                            <div class="input-metric">

                                <div class="input-metric-label">

                                    Initial Temperature

                                </div>

                                <div class="input-metric-value">

                                    <?= formatNumber(
                                        $aInitial,
                                        2
                                    ) ?>

                                    °C

                                </div>

                            </div>

                        </div>


                        <div class="col-6">

                            <div class="input-metric">

                                <div class="input-metric-label">

                                    Retort Temperature

                                </div>

                                <div class="input-metric-value">

                                    <?= formatNumber(
                                        $aRetort,
                                        2
                                    ) ?>

                                    °C

                                </div>

                            </div>

                        </div>


                        <div class="col-4">

                            <div class="input-metric">

                                <div class="input-metric-label">

                                    Heating

                                </div>

                                <div class="input-metric-value">

                                    <?= formatNumber(
                                        $aHeating,
                                        0
                                    ) ?>

                                    min

                                </div>

                            </div>

                        </div>


                        <div class="col-4">

                            <div class="input-metric">

                                <div class="input-metric-label">

                                    Processing

                                </div>

                                <div class="input-metric-value">

                                    <?= formatNumber(
                                        $aProcessing,
                                        0
                                    ) ?>

                                    min

                                </div>

                            </div>

                        </div>


                        <div class="col-4">

                            <div class="input-metric">

                                <div class="input-metric-label">

                                    Cooling

                                </div>

                                <div class="input-metric-value">

                                    <?= formatNumber(
                                        $aCooling,
                                        0
                                    ) ?>

                                    min

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- TRIAL B -->

        <div class="col-md-6">

            <div class="trial-card">

                <div class="trial-card-header trial-b-header">

                    <i class="bi bi-2-circle me-2"></i>

                    Trial B

                </div>


                <div class="trial-card-body">

                    <div class="trial-date">

                        <i class="bi bi-calendar3 me-1"></i>

                        Saved:

                        <?= htmlspecialchars(
                            $trialB['created_at']
                        ) ?>

                    </div>


                    <div class="row g-3">


                        <div class="col-6">

                            <div class="input-metric">

                                <div class="input-metric-label">

                                    Initial Temperature

                                </div>

                                <div class="input-metric-value">

                                    <?= formatNumber(
                                        $bInitial,
                                        2
                                    ) ?>

                                    °C

                                </div>

                            </div>

                        </div>


                        <div class="col-6">

                            <div class="input-metric">

                                <div class="input-metric-label">

                                    Retort Temperature

                                </div>

                                <div class="input-metric-value">

                                    <?= formatNumber(
                                        $bRetort,
                                        2
                                    ) ?>

                                    °C

                                </div>

                            </div>

                        </div>


                        <div class="col-4">

                            <div class="input-metric">

                                <div class="input-metric-label">

                                    Heating

                                </div>

                                <div class="input-metric-value">

                                    <?= formatNumber(
                                        $bHeating,
                                        0
                                    ) ?>

                                    min

                                </div>

                            </div>

                        </div>


                        <div class="col-4">

                            <div class="input-metric">

                                <div class="input-metric-label">

                                    Processing

                                </div>

                                <div class="input-metric-value">

                                    <?= formatNumber(
                                        $bProcessing,
                                        0
                                    ) ?>

                                    min

                                </div>

                            </div>

                        </div>


                        <div class="col-4">

                            <div class="input-metric">

                                <div class="input-metric-label">

                                    Cooling

                                </div>

                                <div class="input-metric-value">

                                    <?= formatNumber(
                                        $bCooling,
                                        0
                                    ) ?>

                                    min

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         MAIN THERMAL RESULTS
         ===================================================== -->

    <div class="compare-card">

        <div class="card-body">

            <div class="section-title">

                <i class="bi bi-bar-chart-line"></i>

                <span>
                    Thermal Results Comparison
                </span>

            </div>


            <div class="table-responsive">

                <table class="table table-bordered align-middle main-table">

                    <thead>

                        <tr>

                            <th>
                                Parameter
                            </th>

                            <th class="text-center">
                                Trial A
                            </th>

                            <th class="text-center">
                                Trial B
                            </th>

                            <th class="text-center">
                                Difference
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <tr>

                            <td class="metric-name">

                                Maximum Product Temperature

                            </td>

                            <td class="text-center trial-value-a">

                                <?= formatNumber(
                                    $aMaxPT,
                                    2
                                ) ?>

                                °C

                            </td>

                            <td class="text-center trial-value-b">

                                <?= formatNumber(
                                    $bMaxPT,
                                    2
                                ) ?>

                                °C

                            </td>

                            <td class="text-center difference-cell">

                                <?= formatNumber(
                                    $maxPTDifference,
                                    2
                                ) ?>

                                °C

                            </td>

                        </tr>


                        <tr>

                            <td class="metric-name">

                                Heating Rate (f)

                            </td>

                            <td class="text-center trial-value-a">

                                <?= formatNumber(
                                    $aHeatingRate
                                ) ?>

                            </td>

                            <td class="text-center trial-value-b">

                                <?= formatNumber(
                                    $bHeatingRate
                                ) ?>

                            </td>

                            <td class="text-center difference-cell">

                                <?= formatNumber(
                                    $heatingRateDifference
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <td class="metric-name">

                                Cooling Rate (fc)

                            </td>

                            <td class="text-center trial-value-a">

                                <?= formatNumber(
                                    $aCoolingRate
                                ) ?>

                            </td>

                            <td class="text-center trial-value-b">

                                <?= formatNumber(
                                    $bCoolingRate
                                ) ?>

                            </td>

                            <td class="text-center difference-cell">

                                <?= formatNumber(
                                    $coolingRateDifference
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <td class="metric-name">

                                Thermal Lag (j)

                            </td>

                            <td class="text-center trial-value-a">

                                <?= formatNumber(
                                    $aThermalLag
                                ) ?>

                            </td>

                            <td class="text-center trial-value-b">

                                <?= formatNumber(
                                    $bThermalLag
                                ) ?>

                            </td>

                            <td class="text-center difference-cell">

                                <?= formatNumber(
                                    $thermalLagDifference
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <td class="metric-name">

                                Regression Slope

                            </td>

                            <td class="text-center trial-value-a">

                                <?= formatNumber(
                                    $aRegressionSlope
                                ) ?>

                            </td>

                            <td class="text-center trial-value-b">

                                <?= formatNumber(
                                    $bRegressionSlope
                                ) ?>

                            </td>

                            <td class="text-center difference-cell">

                                <?= formatNumber(
                                    difference(
                                        $aRegressionSlope,
                                        $bRegressionSlope
                                    )
                                ) ?>

                            </td>

                        </tr>


                    </tbody>

                </table>

            </div>

        </div>

    </div>



    <!-- =====================================================
         QUICK COMPARISON SUMMARY
         ===================================================== -->

    <div class="compare-card">

        <div class="card-body">

            <div class="section-title">

                <i class="bi bi-clipboard-data"></i>

                <span>
                    Comparison Summary
                </span>

            </div>


            <div class="row g-3">


                <div class="col-md-4">

                    <div class="summary-box">

                        <div class="summary-label">

                            Higher Maximum Temperature

                        </div>

                        <div class="summary-value">

                            <?php if ($betterTemperatureTrial === 'Equal'): ?>

                                Equal

                            <?php else: ?>

                                Trial <?= $betterTemperatureTrial ?>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="summary-box">

                        <div class="summary-label">

                            Higher Heating Rate

                        </div>

                        <div class="summary-value">

                            <?php if ($betterHeatingTrial === 'Equal'): ?>

                                Equal

                            <?php else: ?>

                                Trial <?= $betterHeatingTrial ?>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="summary-box">

                        <div class="summary-label">

                            Higher Cooling Rate

                        </div>

                        <div class="summary-value">

                            <?php if ($betterCoolingTrial === 'Equal'): ?>

                                Equal

                            <?php else: ?>

                                Trial <?= $betterCoolingTrial ?>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>



    <!-- =====================================================
         HEATING REGRESSION
         ===================================================== -->

    <div class="compare-card">

        <div class="card-body">

            <div class="section-title">

                <i class="bi bi-thermometer-high"></i>

                <span>
                    Heating Regression Comparison
                </span>

            </div>


            <div class="table-responsive">

                <table class="table table-bordered align-middle heating-table">

                    <thead>

                        <tr>

                            <th>
                                Parameter
                            </th>

                            <th class="text-center">
                                Trial A
                            </th>

                            <th class="text-center">
                                Trial B
                            </th>

                            <th class="text-center">
                                Difference
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <tr>

                            <td class="metric-name">
                                Heating f
                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $aHeatingF
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $bHeatingF
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    difference(
                                        $aHeatingF,
                                        $bHeatingF
                                    )
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <td class="metric-name">
                                Heating j
                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $aHeatingJ
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $bHeatingJ
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    difference(
                                        $aHeatingJ,
                                        $bHeatingJ
                                    )
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <td class="metric-name">
                                Slope
                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $aHeatingSlope
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $bHeatingSlope
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    difference(
                                        $aHeatingSlope,
                                        $bHeatingSlope
                                    )
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <td class="metric-name">
                                Intercept
                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $aHeatingIntercept
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $bHeatingIntercept
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    difference(
                                        $aHeatingIntercept,
                                        $bHeatingIntercept
                                    )
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <td class="metric-name fw-bold">

                                R²

                            </td>

                            <td class="text-center fw-bold">

                                <?= formatNumber(
                                    $aHeatingR2
                                ) ?>

                            </td>

                            <td class="text-center fw-bold">

                                <?= formatNumber(
                                    $bHeatingR2
                                ) ?>

                            </td>

                            <td class="text-center fw-bold">

                                <?= formatNumber(
                                    $heatingR2Difference
                                ) ?>

                            </td>

                        </tr>


                    </tbody>

                </table>

            </div>

        </div>

    </div>



    <!-- =====================================================
         COOLING REGRESSION
         ===================================================== -->

    <div class="compare-card">

        <div class="card-body">

            <div class="section-title">

                <i class="bi bi-snow2"></i>

                <span>
                    Cooling Regression Comparison
                </span>

            </div>


            <div class="table-responsive">

                <table class="table table-bordered align-middle cooling-table">

                    <thead>

                        <tr>

                            <th>
                                Parameter
                            </th>

                            <th class="text-center">
                                Trial A
                            </th>

                            <th class="text-center">
                                Trial B
                            </th>

                            <th class="text-center">
                                Difference
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <tr>

                            <td class="metric-name">
                                Cooling fc
                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $aCoolingF
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $bCoolingF
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    difference(
                                        $aCoolingF,
                                        $bCoolingF
                                    )
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <td class="metric-name">
                                Cooling jc
                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $aCoolingJ
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $bCoolingJ
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    difference(
                                        $aCoolingJ,
                                        $bCoolingJ
                                    )
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <td class="metric-name">
                                Slope
                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $aCoolingSlope
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $bCoolingSlope
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    difference(
                                        $aCoolingSlope,
                                        $bCoolingSlope
                                    )
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <td class="metric-name">
                                Intercept
                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $aCoolingIntercept
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    $bCoolingIntercept
                                ) ?>

                            </td>

                            <td class="text-center">

                                <?= formatNumber(
                                    difference(
                                        $aCoolingIntercept,
                                        $bCoolingIntercept
                                    )
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <td class="metric-name fw-bold">

                                R²

                            </td>

                            <td class="text-center fw-bold">

                                <?= formatNumber(
                                    $aCoolingR2
                                ) ?>

                            </td>

                            <td class="text-center fw-bold">

                                <?= formatNumber(
                                    $bCoolingR2
                                ) ?>

                            </td>

                            <td class="text-center fw-bold">

                                <?= formatNumber(
                                    $coolingR2Difference
                                ) ?>

                            </td>

                        </tr>


                    </tbody>

                </table>

            </div>

        </div>

    </div>



    <!-- =====================================================
         INTERPRETATION
         ===================================================== -->

    <div class="compare-card">

        <div class="card-body">

            <div class="section-title">

                <i class="bi bi-lightbulb"></i>

                <span>
                    Comparison Interpretation
                </span>

            </div>


            <?php foreach ($interpretation as $item): ?>

                <div class="interpretation-item">

                    <i class="bi bi-check-circle-fill"></i>

                    <span>

                        <?= htmlspecialchars($item) ?>

                    </span>

                </div>

            <?php endforeach; ?>


            <div class="info-note mt-4">

                <i class="bi bi-info-circle me-2"></i>

                <strong>Important:</strong>

                A higher maximum product temperature or
                heating/cooling rate is not automatically better.
                The results should be interpreted according to
                the processing objective and the conditions of
                the experiment.

            </div>

        </div>

    </div>



    <!-- =====================================================
         ACTIONS
         ===================================================== -->

    <div class="action-buttons mb-4 no-print">

        <a
            href="simulation3_history.php"
            class="btn btn-outline-lab"
        >

            <i class="bi bi-clock-history"></i>

            Results History

        </a>


        <a
            href="simulation3.php"
            class="btn btn-lab"
        >

            <i class="bi bi-play-fill"></i>

            New Simulation

        </a>


        <button
            type="button"
            onclick="window.print()"
            class="btn btn-outline-secondary"
        >

            <i class="bi bi-printer"></i>

            Print Comparison

        </button>

    </div>


</main>



<!-- =========================================================
     FOOTER
     ========================================================= -->

<footer class="page-footer">

    Food Process Practical Learning System

    <span class="mx-2">•</span>

    Practical 3 — Thermal Processing

</footer>


</body>

</html>