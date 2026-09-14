<?php
require_once "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$practical_number = 4;

/* =========================================================
   STUDENT NAME
========================================================= */

$student_name = "Student";

if (!empty($_SESSION['full_name'])) {
    $student_name = $_SESSION['full_name'];
} elseif (!empty($_SESSION['name'])) {
    $student_name = $_SESSION['name'];
} elseif (!empty($_SESSION['username'])) {
    $student_name = $_SESSION['username'];
} else {

    $stmtUser = $conn->prepare("
        SELECT full_name
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    if ($stmtUser) {

        $stmtUser->bind_param("i", $user_id);
        $stmtUser->execute();

        $userResult = $stmtUser->get_result();

        if ($userRow = $userResult->fetch_assoc()) {

            if (!empty($userRow['full_name'])) {
                $student_name = $userRow['full_name'];
            }

        }

        $stmtUser->close();
    }
}


/* =========================================================
   LOAD ALL PRACTICAL 4 SIMULATION RESULTS
========================================================= */

$results = [];

$stmt = $conn->prepare("
    SELECT
        id,
        simulation_type,
        input_data,
        result_data,
        created_at
    FROM simulation_results
    WHERE user_id = ?
      AND practical_number = ?
    ORDER BY created_at DESC
");

if ($stmt) {

    $stmt->bind_param(
        "ii",
        $user_id,
        $practical_number
    );

    $stmt->execute();

    $resultQuery = $stmt->get_result();

    while ($row = $resultQuery->fetch_assoc()) {

        $row['input'] = [];
        $row['output'] = [];

        if (!empty($row['input_data'])) {

            $decodedInput =
                json_decode(
                    $row['input_data'],
                    true
                );

            if (is_array($decodedInput)) {
                $row['input'] = $decodedInput;
            }
        }

        if (!empty($row['result_data'])) {

            $decodedOutput =
                json_decode(
                    $row['result_data'],
                    true
                );

            if (is_array($decodedOutput)) {
                $row['output'] = $decodedOutput;
            }
        }

        $results[] = $row;
    }

    $stmt->close();
}


/* =========================================================
   SEPARATE SPRAY AND FREEZE RESULTS
========================================================= */

$sprayResults = [];
$freezeResults = [];

foreach ($results as $row) {

    $type = strtolower(
        $row['simulation_type'] ?? ''
    );

    if (strpos($type, 'spray') !== false) {

        $sprayResults[] = $row;

    } elseif (strpos($type, 'freeze') !== false) {

        $freezeResults[] = $row;
    }
}


/* =========================================================
   SELECT COMPARISON TYPE
========================================================= */

$type = strtolower(
    $_GET['type'] ?? 'spray'
);

if (!in_array($type, ['spray', 'freeze'])) {
    $type = 'spray';
}

$availableTrials =
    ($type === 'freeze')
    ? $freezeResults
    : $sprayResults;


/* =========================================================
   REQUIRE AT LEAST TWO TRIALS
========================================================= */

if (count($availableTrials) < 2) {

    header(
        "Location: simulation4_history.php"
    );

    exit;
}


/* =========================================================
   SELECT TRIAL A AND TRIAL B
========================================================= */

$trialAId =
    isset($_GET['trial_a'])
    ? (int)$_GET['trial_a']
    : 0;

$trialBId =
    isset($_GET['trial_b'])
    ? (int)$_GET['trial_b']
    : 0;

$trialA = null;
$trialB = null;

foreach ($availableTrials as $trial) {

    if ((int)$trial['id'] === $trialAId) {
        $trialA = $trial;
    }

    if ((int)$trial['id'] === $trialBId) {
        $trialB = $trial;
    }
}


/* =========================================================
   DEFAULT TO FIRST TWO TRIALS
========================================================= */

if (!$trialA || !$trialB) {

    $trialA = $availableTrials[0];
    $trialB = $availableTrials[1];

}


/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function valueFrom($array, $key, $default = 0)
{
    if (!is_array($array)) {
        return $default;
    }

    if (
        isset($array[$key]) &&
        $array[$key] !== ''
    ) {
        return $array[$key];
    }

    return $default;
}


function difference($a, $b)
{
    return (float)$b - (float)$a;
}


function percentageDifference($a, $b)
{
    if ((float)$a == 0) {
        return 0;
    }

    return (
        (($b - $a) / abs($a))
        * 100
    );
}


function formatNumber($value, $decimals = 2)
{
    if (
        $value === null ||
        $value === '' ||
        !is_numeric($value)
    ) {
        return '—';
    }

    return number_format(
        (float)$value,
        $decimals
    );
}


/* =========================================================
   COMMON DATA
========================================================= */

$inputA  = $trialA['input'];
$outputA = $trialA['output'];

$inputB  = $trialB['input'];
$outputB = $trialB['output'];


/* =========================================================
   SPRAY DRYING COMPARISON
========================================================= */

$sprayData = [];

if ($type === 'spray') {

    /* Inputs */

    $inletTempA =
        valueFrom(
            $inputA,
            'inletTemp'
        );

    $inletTempB =
        valueFrom(
            $inputB,
            'inletTemp'
        );


    $feedFlowA =
        valueFrom(
            $inputA,
            'feedFlow'
        );

    $feedFlowB =
        valueFrom(
            $inputB,
            'feedFlow'
        );


    $initialMoistureA =
        valueFrom(
            $inputA,
            'initialMoisture'
        );

    $initialMoistureB =
        valueFrom(
            $inputB,
            'initialMoisture'
        );


    $finalMoistureA =
        valueFrom(
            $inputA,
            'finalMoisture'
        );

    $finalMoistureB =
        valueFrom(
            $inputB,
            'finalMoisture'
        );


    /* Outputs */

    $dryingTimeA =
        valueFrom(
            $outputA,
            'dryingTime'
        );

    $dryingTimeB =
        valueFrom(
            $outputB,
            'dryingTime'
        );


    $waterRemovedA =
        valueFrom(
            $outputA,
            'waterRemoved'
        );

    $waterRemovedB =
        valueFrom(
            $outputB,
            'waterRemoved'
        );


    $dryProductA =
        valueFrom(
            $outputA,
            'dryProduct'
        );

    $dryProductB =
        valueFrom(
            $outputB,
            'dryProduct'
        );


    $heatDemandA =
        valueFrom(
            $outputA,
            'heatDemand'
        );

    $heatDemandB =
        valueFrom(
            $outputB,
            'heatDemand'
        );


    $efficiencyA =
        valueFrom(
            $outputA,
            'efficiency'
        );

    $efficiencyB =
        valueFrom(
            $outputB,
            'efficiency'
        );


    $sprayData = [

        'inputs' => [

            [
                'name' => 'Inlet Temperature',
                'unit' => '°C',
                'a' => $inletTempA,
                'b' => $inletTempB
            ],

            [
                'name' => 'Feed Flow',
                'unit' => '',
                'a' => $feedFlowA,
                'b' => $feedFlowB
            ],

            [
                'name' => 'Initial Moisture',
                'unit' => '%',
                'a' => $initialMoistureA,
                'b' => $initialMoistureB
            ],

            [
                'name' => 'Final Moisture',
                'unit' => '%',
                'a' => $finalMoistureA,
                'b' => $finalMoistureB
            ]

        ],

        'outputs' => [

            [
                'name' => 'Drying Time',
                'unit' => 'min',
                'a' => $dryingTimeA,
                'b' => $dryingTimeB
            ],

            [
                'name' => 'Water Removed',
                'unit' => '',
                'a' => $waterRemovedA,
                'b' => $waterRemovedB
            ],

            [
                'name' => 'Dry Product',
                'unit' => '',
                'a' => $dryProductA,
                'b' => $dryProductB
            ],

            [
                'name' => 'Heat Demand',
                'unit' => '',
                'a' => $heatDemandA,
                'b' => $heatDemandB
            ],

            [
                'name' => 'Efficiency',
                'unit' => '%',
                'a' => $efficiencyA,
                'b' => $efficiencyB
            ]

        ]

    ];


    /*
     * Interpretation
     */

    if ($dryingTimeB < $dryingTimeA) {

        $sprayInterpretation =
            "Trial B required less drying time than Trial A, "
            . "indicating faster moisture removal under its "
            . "operating conditions.";

    } elseif ($dryingTimeB > $dryingTimeA) {

        $sprayInterpretation =
            "Trial A required less drying time than Trial B, "
            . "indicating faster moisture removal under its "
            . "operating conditions.";

    } else {

        $sprayInterpretation =
            "Both trials produced the same calculated drying time.";
    }

}


/* =========================================================
   FREEZE DRYING COMPARISON
========================================================= */

$freezeData = [];

if ($type === 'freeze') {

    /* Inputs */

    $thicknessA =
        valueFrom(
            $inputA,
            'thickness'
        );

    $thicknessB =
        valueFrom(
            $inputB,
            'thickness'
        );


    $plateTempA =
        valueFrom(
            $inputA,
            'plateTemp'
        );

    $plateTempB =
        valueFrom(
            $inputB,
            'plateTemp'
        );


    $pressureA =
        valueFrom(
            $inputA,
            'pressure'
        );

    $pressureB =
        valueFrom(
            $inputB,
            'pressure'
        );


    $moistureA =
        valueFrom(
            $inputA,
            'moisture'
        );

    $moistureB =
        valueFrom(
            $inputB,
            'moisture'
        );


    /* Outputs */

    $theoreticalTimeA =
        valueFrom(
            $outputA,
            'theoreticalTime'
        );

    $theoreticalTimeB =
        valueFrom(
            $outputB,
            'theoreticalTime'
        );


    $experimentalTimeA =
        valueFrom(
            $outputA,
            'experimentalTime'
        );

    $experimentalTimeB =
        valueFrom(
            $outputB,
            'experimentalTime'
        );


    $finalMoistureA =
        valueFrom(
            $outputA,
            'finalMoisture'
        );

    $finalMoistureB =
        valueFrom(
            $outputB,
            'finalMoisture'
        );


    $differenceA =
        valueFrom(
            $outputA,
            'difference'
        );

    $differenceB =
        valueFrom(
            $outputB,
            'difference'
        );


    $percentageDifferenceA =
        valueFrom(
            $outputA,
            'percentageDifference'
        );

    $percentageDifferenceB =
        valueFrom(
            $outputB,
            'percentageDifference'
        );


    $freezeData = [

        'inputs' => [

            [
                'name' => 'Product Thickness',
                'unit' => '',
                'a' => $thicknessA,
                'b' => $thicknessB
            ],

            [
                'name' => 'Plate Temperature',
                'unit' => '°C',
                'a' => $plateTempA,
                'b' => $plateTempB
            ],

            [
                'name' => 'Chamber Pressure',
                'unit' => 'mbar',
                'a' => $pressureA,
                'b' => $pressureB
            ],

            [
                'name' => 'Initial Moisture',
                'unit' => '%',
                'a' => $moistureA,
                'b' => $moistureB
            ]

        ],

        'outputs' => [

            [
                'name' => 'Theoretical Time',
                'unit' => 'min',
                'a' => $theoreticalTimeA,
                'b' => $theoreticalTimeB
            ],

            [
                'name' => 'Experimental Time',
                'unit' => 'min',
                'a' => $experimentalTimeA,
                'b' => $experimentalTimeB
            ],

            [
                'name' => 'Final Moisture',
                'unit' => '%',
                'a' => $finalMoistureA,
                'b' => $finalMoistureB
            ],

            [
                'name' => 'Time Difference',
                'unit' => 'min',
                'a' => $differenceA,
                'b' => $differenceB
            ],

            [
                'name' => 'Percentage Difference',
                'unit' => '%',
                'a' => $percentageDifferenceA,
                'b' => $percentageDifferenceB
            ]

        ]

    ];


    if ($experimentalTimeB < $experimentalTimeA) {

        $freezeInterpretation =
            "Trial B produced a shorter experimental drying time "
            . "than Trial A.";

    } elseif ($experimentalTimeB > $experimentalTimeA) {

        $freezeInterpretation =
            "Trial A produced a shorter experimental drying time "
            . "than Trial B.";

    } else {

        $freezeInterpretation =
            "Both trials produced the same experimental drying time.";
    }

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
        Practical 4 - Compare Trials
    </title>


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


    <style>

        :root {

            --lab-blue: #1f5f75;
            --lab-blue-dark: #17495b;
            --lab-blue-light: #eaf3f6;

            --page-bg: #f4f6f8;
            --card-bg: #ffffff;

            --border: #d9dee3;

            --text: #243447;
            --muted: #68727c;

            --success: #287d4a;
            --warning: #a66b00;
            --danger: #a33a3a;
        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background: var(--page-bg);

            color: var(--text);

            font-family:
                "Segoe UI",
                Tahoma,
                Geneva,
                Verdana,
                sans-serif;
        }


        /* =====================================================
           HEADER
        ===================================================== */

        .top-header {

            position: sticky;

            top: 0;

            z-index: 1000;

            min-height: 68px;

            background: #ffffff;

            border-bottom:
                1px solid var(--border);

            display: flex;

            align-items: center;

            padding:
                10px 28px;

            box-shadow:
                0 2px 8px
                rgba(0, 0, 0, 0.04);
        }


        .brand-area {

            display: flex;

            align-items: center;

            gap: 13px;
        }


        .brand-icon {

            width: 40px;

            height: 40px;

            border-radius: 8px;

            background:
                var(--lab-blue);

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;
        }


        .brand-title {

            font-size: 17px;

            font-weight: 700;

            color:
                var(--lab-blue-dark);
        }


        .brand-subtitle {

            font-size: 12px;

            color: var(--muted);
        }


        .student-area {

            margin-left: auto;

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .student-name {

            font-size: 14px;

            font-weight: 600;
        }


        .student-avatar {

            width: 38px;

            height: 38px;

            border-radius: 50%;

            background:
                var(--lab-blue-light);

            color:
                var(--lab-blue);

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: 700;
        }


        /* =====================================================
           PAGE
        ===================================================== */

        .page-container {

            max-width: 1250px;

            margin: 0 auto;

            padding:
                32px 24px 50px;
        }


        .page-heading {

            margin-bottom: 25px;
        }


        .heading-title {

            font-size: 27px;

            font-weight: 700;

            color:
                var(--lab-blue-dark);

            margin-bottom: 5px;
        }


        .heading-description {

            margin: 0;

            color: var(--muted);

            font-size: 14px;
        }


        /* =====================================================
           CARDS
        ===================================================== */

        .lab-card {

            background: white;

            border:
                1px solid var(--border);

            border-radius: 10px;

            box-shadow:
                0 2px 8px
                rgba(0, 0, 0, 0.035);

            margin-bottom: 22px;
        }


        .lab-card-header {

            padding:
                17px 20px;

            border-bottom:
                1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;
        }


        .lab-card-title {

            display: flex;

            align-items: center;

            gap: 10px;

            margin: 0;

            font-size: 16px;

            font-weight: 700;

            color:
                var(--lab-blue-dark);
        }


        .lab-card-title i {

            color:
                var(--lab-blue);
        }


        .lab-card-body {

            padding: 20px;
        }


        /* =====================================================
           COMPARISON SELECTOR
        ===================================================== */

        .comparison-selector {

            display: flex;

            flex-wrap: wrap;

            gap: 10px;
        }


        .type-button {

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding:
                9px 15px;

            border-radius: 6px;

            border:
                1px solid var(--border);

            background: white;

            color: var(--text);

            font-size: 13px;

            font-weight: 600;
        }


        .type-button:hover {

            background:
                var(--lab-blue-light);

            color:
                var(--lab-blue-dark);
        }


        .type-button.active {

            background:
                var(--lab-blue);

            border-color:
                var(--lab-blue);

            color: white;
        }


        /* =====================================================
           FORM
        ===================================================== */

        .form-label {

            font-size: 12px;

            font-weight: 700;

            color: #4f5961;

            margin-bottom: 6px;
        }


        .form-select {

            border-color:
                var(--border);

            border-radius: 6px;

            font-size: 13px;

            padding:
                9px 12px;
        }


        .form-select:focus {

            border-color:
                var(--lab-blue);

            box-shadow:
                0 0 0
                0.15rem
                rgba(31, 95, 117, 0.15);
        }


        /* =====================================================
           TRIAL CARDS
        ===================================================== */

        .trial-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;
        }


        .trial-card {

            border:
                1px solid var(--border);

            border-radius: 8px;

            background:
                #fafbfc;

            overflow: hidden;
        }


        .trial-card-header {

            padding:
                13px 16px;

            background:
                var(--lab-blue-light);

            border-bottom:
                1px solid var(--border);

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .trial-card-title {

            font-size: 14px;

            font-weight: 700;

            color:
                var(--lab-blue-dark);
        }


        .trial-card-body {

            padding: 16px;
        }


        .trial-date {

            font-size: 11px;

            color: var(--muted);
        }


        /* =====================================================
           TABLE
        ===================================================== */

        .comparison-table {

            width: 100%;

            margin: 0;

            border-collapse: collapse;
        }


        .comparison-table th {

            background:
                #f7f9fa;

            color:
                #46515a;

            font-size: 12px;

            font-weight: 700;

            padding:
                13px 14px;

            border:
                1px solid var(--border);
        }


        .comparison-table td {

            padding:
                13px 14px;

            border:
                1px solid var(--border);

            font-size: 13px;

            vertical-align: middle;
        }


        .comparison-table td:first-child {

            font-weight: 600;

            color:
                var(--text);

            background:
                #fafbfc;
        }


        .value-a {

            font-weight: 700;

            color:
                var(--lab-blue-dark);
        }


        .value-b {

            font-weight: 700;

            color:
                #455a64;
        }


        .difference-value {

            font-weight: 700;
        }


        .positive {

            color:
                var(--success);
        }


        .negative {

            color:
                var(--danger);
        }


        .neutral {

            color:
                var(--muted);
        }


        /* =====================================================
           SECTION LABEL
        ===================================================== */

        .section-label {

            display: flex;

            align-items: center;

            gap: 9px;

            margin-bottom: 12px;

            font-size: 14px;

            font-weight: 700;

            color:
                var(--lab-blue-dark);
        }


        .section-label i {

            color:
                var(--lab-blue);
        }


        /* =====================================================
           INTERPRETATION
        ===================================================== */

        .interpretation {

            background:
                #f8fafb;

            border-left:
                4px solid var(--lab-blue);

            border-radius: 5px;

            padding:
                16px 18px;

            color:
                #4e5962;

            font-size: 13px;

            line-height: 1.65;
        }


        .interpretation strong {

            color:
                var(--lab-blue-dark);
        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .btn-lab {

            background:
                var(--lab-blue);

            border-color:
                var(--lab-blue);

            color: white;

            font-size: 13px;

            font-weight: 600;

            border-radius: 6px;

            padding:
                9px 15px;
        }


        .btn-lab:hover {

            background:
                var(--lab-blue-dark);

            border-color:
                var(--lab-blue-dark);

            color: white;
        }


        .btn-outline-lab {

            background: white;

            color:
                var(--lab-blue);

            border:
                1px solid var(--lab-blue);

            font-size: 13px;

            font-weight: 600;

            border-radius: 6px;

            padding:
                8px 14px;
        }


        .btn-outline-lab:hover {

            background:
                var(--lab-blue-light);

            color:
                var(--lab-blue-dark);
        }


        .actions {

            display: flex;

            flex-wrap: wrap;

            gap: 10px;

            margin-top: 22px;
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 850px) {

            .trial-grid {

                grid-template-columns:
                    1fr;
            }

        }


        @media (max-width: 650px) {

            .top-header {

                padding:
                    10px 15px;
            }


            .brand-subtitle {

                display: none;
            }


            .student-name {

                display: none;
            }


            .page-container {

                padding:
                    22px 14px 40px;
            }


            .heading-title {

                font-size: 23px;
            }


            .lab-card-body {

                padding: 15px;
            }


            .comparison-table {

                min-width: 650px;
            }


            .table-wrapper {

                overflow-x: auto;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="top-header">

    <div class="brand-area">

        <div class="brand-icon">

            <i class="bi bi-flask"></i>

        </div>


        <div>

            <div class="brand-title">

                Food Process Practical Learning System

            </div>


            <div class="brand-subtitle">

                Laboratory Practical Management

            </div>

        </div>

    </div>


    <div class="student-area">

        <div class="student-name">

            <?= htmlspecialchars($student_name) ?>

        </div>


        <div class="student-avatar">

            <?= strtoupper(
                substr(
                    $student_name,
                    0,
                    1
                )
            ) ?>

        </div>

    </div>

</header>



<!-- =========================================================
     MAIN
========================================================= -->

<main class="page-container">


    <div class="page-heading">

        <div class="heading-title">

            Practical 4 — Compare Simulation Trials

        </div>


        <p class="heading-description">

            Compare two saved drying trials and examine how
            operating conditions affect the calculated results.

        </p>

    </div>



    <!-- =====================================================
         COMPARISON TYPE
    ===================================================== -->

    <div class="lab-card">

        <div class="lab-card-header">

            <h2 class="lab-card-title">

                <i class="bi bi-arrow-left-right"></i>

                Comparison Type

            </h2>

        </div>


        <div class="lab-card-body">

            <div class="comparison-selector">

                <?php if (count($sprayResults) >= 2): ?>

                    <a
                        href="simulation4_compare.php?type=spray"
                        class="type-button
                        <?= $type === 'spray'
                            ? 'active'
                            : ''
                        ?>"
                    >

                        <i class="bi bi-wind"></i>

                        Spray Drying

                    </a>

                <?php endif; ?>


                <?php if (count($freezeResults) >= 2): ?>

                    <a
                        href="simulation4_compare.php?type=freeze"
                        class="type-button
                        <?= $type === 'freeze'
                            ? 'active'
                            : ''
                        ?>"
                    >

                        <i class="bi bi-snow2"></i>

                        Freeze Drying

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>



    <!-- =====================================================
         TRIAL SELECTION
    ===================================================== -->

    <div class="lab-card">

        <div class="lab-card-header">

            <h2 class="lab-card-title">

                <i class="bi bi-sliders"></i>

                Trial Selection

            </h2>

        </div>


        <div class="lab-card-body">

            <form
                method="GET"
                action="simulation4_compare.php"
            >

                <input
                    type="hidden"
                    name="type"
                    value="<?= htmlspecialchars($type) ?>"
                >


                <div class="row g-3">

                    <div class="col-md-5">

                        <label class="form-label">

                            Trial A

                        </label>


                        <select
                            name="trial_a"
                            class="form-select"
                            required
                        >

                            <?php foreach ($availableTrials as $trial): ?>

                                <option
                                    value="<?= (int)$trial['id'] ?>"
                                    <?= (
                                        (int)$trial['id']
                                        ===
                                        (int)$trialA['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    Trial #<?= (int)$trial['id'] ?>

                                    —
                                    <?= date(
                                        'd M Y, H:i',
                                        strtotime(
                                            $trial['created_at']
                                        )
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-md-5">

                        <label class="form-label">

                            Trial B

                        </label>


                        <select
                            name="trial_b"
                            class="form-select"
                            required
                        >

                            <?php foreach ($availableTrials as $trial): ?>

                                <option
                                    value="<?= (int)$trial['id'] ?>"
                                    <?= (
                                        (int)$trial['id']
                                        ===
                                        (int)$trialB['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    Trial #<?= (int)$trial['id'] ?>

                                    —
                                    <?= date(
                                        'd M Y, H:i',
                                        strtotime(
                                            $trial['created_at']
                                        )
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

                            <i class="bi bi-arrow-left-right me-1"></i>

                            Compare

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>



    <!-- =====================================================
         TRIAL INFORMATION
    ===================================================== -->

    <div class="trial-grid mb-4">


        <!-- TRIAL A -->

        <div class="trial-card">

            <div class="trial-card-header">

                <div class="trial-card-title">

                    <i class="bi bi-circle me-1"></i>

                    Trial A

                </div>


                <div class="trial-date">

                    <?= date(
                        'd M Y, H:i',
                        strtotime(
                            $trialA['created_at']
                        )
                    ) ?>

                </div>

            </div>


            <div class="trial-card-body">

                <strong>

                    <?= htmlspecialchars(
                        $trialA['simulation_type']
                    ) ?>

                </strong>

            </div>

        </div>



        <!-- TRIAL B -->

        <div class="trial-card">

            <div class="trial-card-header">

                <div class="trial-card-title">

                    <i class="bi bi-circle me-1"></i>

                    Trial B

                </div>


                <div class="trial-date">

                    <?= date(
                        'd M Y, H:i',
                        strtotime(
                            $trialB['created_at']
                        )
                    ) ?>

                </div>

            </div>


            <div class="trial-card-body">

                <strong>

                    <?= htmlspecialchars(
                        $trialB['simulation_type']
                    ) ?>

                </strong>

            </div>

        </div>

    </div>



    <?php if ($type === 'spray'): ?>


        <!-- =================================================
             SPRAY INPUT COMPARISON
        ================================================= -->

        <div class="lab-card">

            <div class="lab-card-header">

                <h2 class="lab-card-title">

                    <i class="bi bi-sliders2"></i>

                    Spray Drying Input Comparison

                </h2>

            </div>


            <div class="lab-card-body">

                <div class="table-wrapper">

                    <table class="comparison-table">

                        <thead>

                            <tr>

                                <th>
                                    Parameter
                                </th>

                                <th>
                                    Trial A
                                </th>

                                <th>
                                    Trial B
                                </th>

                                <th>
                                    Difference
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach (
                            $sprayData['inputs']
                            as $row
                        ): ?>

                            <?php

                            $diff =
                                difference(
                                    $row['a'],
                                    $row['b']
                                );

                            ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['name']
                                    ) ?>
                                </td>


                                <td class="value-a">

                                    <?= formatNumber(
                                        $row['a']
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $row['unit']
                                    ) ?>

                                </td>


                                <td class="value-b">

                                    <?= formatNumber(
                                        $row['b']
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $row['unit']
                                    ) ?>

                                </td>


                                <td class="difference-value
                                    <?= $diff > 0
                                        ? 'positive'
                                        : (
                                            $diff < 0
                                            ? 'negative'
                                            : 'neutral'
                                        )
                                    ?>"
                                >

                                    <?= $diff > 0
                                        ? '+'
                                        : ''
                                    ?>

                                    <?= formatNumber(
                                        $diff
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $row['unit']
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>



        <!-- =================================================
             SPRAY OUTPUT COMPARISON
        ================================================= -->

        <div class="lab-card">

            <div class="lab-card-header">

                <h2 class="lab-card-title">

                    <i class="bi bi-bar-chart-line"></i>

                    Spray Drying Result Comparison

                </h2>

            </div>


            <div class="lab-card-body">

                <div class="table-wrapper">

                    <table class="comparison-table">

                        <thead>

                            <tr>

                                <th>
                                    Result
                                </th>

                                <th>
                                    Trial A
                                </th>

                                <th>
                                    Trial B
                                </th>

                                <th>
                                    Difference
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach (
                            $sprayData['outputs']
                            as $row
                        ): ?>

                            <?php

                            $diff =
                                difference(
                                    $row['a'],
                                    $row['b']
                                );

                            ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['name']
                                    ) ?>
                                </td>


                                <td class="value-a">

                                    <?= formatNumber(
                                        $row['a']
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $row['unit']
                                    ) ?>

                                </td>


                                <td class="value-b">

                                    <?= formatNumber(
                                        $row['b']
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $row['unit']
                                    ) ?>

                                </td>


                                <td class="difference-value
                                    <?= $diff > 0
                                        ? 'positive'
                                        : (
                                            $diff < 0
                                            ? 'negative'
                                            : 'neutral'
                                        )
                                    ?>"
                                >

                                    <?= $diff > 0
                                        ? '+'
                                        : ''
                                    ?>

                                    <?= formatNumber(
                                        $diff
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $row['unit']
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>



        <!-- =================================================
             SPRAY INTERPRETATION
        ================================================= -->

        <div class="lab-card">

            <div class="lab-card-header">

                <h2 class="lab-card-title">

                    <i class="bi bi-lightbulb"></i>

                    Interpretation

                </h2>

            </div>


            <div class="lab-card-body">

                <div class="interpretation">

                    <strong>
                        Analysis:
                    </strong>

                    <?= htmlspecialchars(
                        $sprayInterpretation
                    ) ?>

                </div>

            </div>

        </div>


    <?php else: ?>


        <!-- =================================================
             FREEZE INPUT COMPARISON
        ================================================= -->

        <div class="lab-card">

            <div class="lab-card-header">

                <h2 class="lab-card-title">

                    <i class="bi bi-sliders2"></i>

                    Freeze Drying Input Comparison

                </h2>

            </div>


            <div class="lab-card-body">

                <div class="table-wrapper">

                    <table class="comparison-table">

                        <thead>

                            <tr>

                                <th>
                                    Parameter
                                </th>

                                <th>
                                    Trial A
                                </th>

                                <th>
                                    Trial B
                                </th>

                                <th>
                                    Difference
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach (
                            $freezeData['inputs']
                            as $row
                        ): ?>

                            <?php

                            $diff =
                                difference(
                                    $row['a'],
                                    $row['b']
                                );

                            ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['name']
                                    ) ?>
                                </td>


                                <td class="value-a">

                                    <?= formatNumber(
                                        $row['a']
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $row['unit']
                                    ) ?>

                                </td>


                                <td class="value-b">

                                    <?= formatNumber(
                                        $row['b']
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $row['unit']
                                    ) ?>

                                </td>


                                <td class="difference-value
                                    <?= $diff > 0
                                        ? 'positive'
                                        : (
                                            $diff < 0
                                            ? 'negative'
                                            : 'neutral'
                                        )
                                    ?>"
                                >

                                    <?= $diff > 0
                                        ? '+'
                                        : ''
                                    ?>

                                    <?= formatNumber(
                                        $diff
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $row['unit']
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>



        <!-- =================================================
             FREEZE OUTPUT COMPARISON
        ================================================= -->

        <div class="lab-card">

            <div class="lab-card-header">

                <h2 class="lab-card-title">

                    <i class="bi bi-bar-chart-line"></i>

                    Freeze Drying Result Comparison

                </h2>

            </div>


            <div class="lab-card-body">

                <div class="table-wrapper">

                    <table class="comparison-table">

                        <thead>

                            <tr>

                                <th>
                                    Result
                                </th>

                                <th>
                                    Trial A
                                </th>

                                <th>
                                    Trial B
                                </th>

                                <th>
                                    Difference
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach (
                            $freezeData['outputs']
                            as $row
                        ): ?>

                            <?php

                            $diff =
                                difference(
                                    $row['a'],
                                    $row['b']
                                );

                            ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        $row['name']
                                    ) ?>
                                </td>


                                <td class="value-a">

                                    <?= formatNumber(
                                        $row['a']
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $row['unit']
                                    ) ?>

                                </td>


                                <td class="value-b">

                                    <?= formatNumber(
                                        $row['b']
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $row['unit']
                                    ) ?>

                                </td>


                                <td class="difference-value
                                    <?= $diff > 0
                                        ? 'positive'
                                        : (
                                            $diff < 0
                                            ? 'negative'
                                            : 'neutral'
                                        )
                                    ?>"
                                >

                                    <?= $diff > 0
                                        ? '+'
                                        : ''
                                    ?>

                                    <?= formatNumber(
                                        $diff
                                    ) ?>

                                    <?= htmlspecialchars(
                                        $row['unit']
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>



        <!-- =================================================
             FREEZE INTERPRETATION
        ================================================= -->

        <div class="lab-card">

            <div class="lab-card-header">

                <h2 class="lab-card-title">

                    <i class="bi bi-lightbulb"></i>

                    Interpretation

                </h2>

            </div>


            <div class="lab-card-body">

                <div class="interpretation">

                    <strong>
                        Analysis:
                    </strong>

                    <?= htmlspecialchars(
                        $freezeInterpretation
                    ) ?>

                </div>

            </div>

        </div>


    <?php endif; ?>



    <!-- =====================================================
         ACTIONS
    ===================================================== -->

    <div class="actions">

        <a
            href="simulation4_history.php"
            class="btn btn-outline-lab"
        >

            <i class="bi bi-clock-history me-1"></i>

            Results History

        </a>


        <a
            href="simulation4.php"
            class="btn btn-lab"
        >

            <i class="bi bi-plus-lg me-1"></i>

            New Simulation

        </a>


        <button
            type="button"
            onclick="window.print()"
            class="btn btn-outline-lab"
        >

            <i class="bi bi-printer me-1"></i>

            Print Comparison

        </button>

    </div>

</main>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<style>

@media print {

    .top-header {

        position: static;

        box-shadow: none;
    }


    .actions,
    .type-button,
    form {

        display: none !important;
    }


    body {

        background: white;
    }


    .page-container {

        max-width: none;

        padding: 10px;
    }


    .lab-card {

        box-shadow: none;
    }

}

</style>


</body>

</html>