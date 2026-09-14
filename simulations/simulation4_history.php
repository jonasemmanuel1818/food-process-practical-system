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

/* =========================================================
   STUDENT NAME
========================================================= */
$student_name = "Student";

if (isset($_SESSION['full_name']) && trim($_SESSION['full_name']) !== '') {
    $student_name = trim($_SESSION['full_name']);
} elseif (isset($_SESSION['name']) && trim($_SESSION['name']) !== '') {
    $student_name = trim($_SESSION['name']);
} elseif (isset($_SESSION['username']) && trim($_SESSION['username']) !== '') {
    $student_name = trim($_SESSION['username']);
} else {
    $stmtUser = $conn->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");

    if ($stmtUser) {
        $stmtUser->bind_param("i", $user_id);
        $stmtUser->execute();
        $resultUser = $stmtUser->get_result();

        if ($rowUser = $resultUser->fetch_assoc()) {
            if (!empty($rowUser['full_name'])) {
                $student_name = $rowUser['full_name'];
            }
        }

        $stmtUser->close();
    }
}

/* =========================================================
   LOAD SIMULATION RESULTS
========================================================= */
$results = [];

$stmt = $conn->prepare("
    SELECT id, simulation_type, input_data, result_data, created_at
    FROM simulation_results
    WHERE user_id = ?
      AND practical_number = 4
    ORDER BY created_at DESC
");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $queryResult = $stmt->get_result();

    while ($row = $queryResult->fetch_assoc()) {

        $row['input'] = [];
        $row['output'] = [];

        if (!empty($row['input_data'])) {
            $decodedInput = json_decode($row['input_data'], true);

            if (is_array($decodedInput)) {
                $row['input'] = $decodedInput;
            }
        }

        if (!empty($row['result_data'])) {
            $decodedOutput = json_decode($row['result_data'], true);

            if (is_array($decodedOutput)) {
                $row['output'] = $decodedOutput;
            }
        }

        $results[] = $row;
    }

    $stmt->close();
}

/* =========================================================
   SEPARATE SIMULATION TYPES
========================================================= */
$sprayResults = [];
$freezeResults = [];

foreach ($results as $result) {

    $type = strtolower($result['simulation_type'] ?? '');

    if (strpos($type, 'spray') !== false) {
        $sprayResults[] = $result;
    } elseif (strpos($type, 'freeze') !== false) {
        $freezeResults[] = $result;
    }
}

/* =========================================================
   HELPERS
========================================================= */
function getValue($array, $key, $default = '—')
{
    if (!is_array($array)) {
        return $default;
    }

    if (array_key_exists($key, $array)) {
        return $array[$key];
    }

    return $default;
}

function formatValue($value, $decimals = 2)
{
    if ($value === null || $value === '' || $value === '—') {
        return '—';
    }

    if (is_numeric($value)) {
        return number_format((float)$value, $decimals);
    }

    return htmlspecialchars((string)$value);
}

$totalTrials = count($results);
$totalSpray = count($sprayResults);
$totalFreeze = count($freezeResults);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Practical 4 Simulation History</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

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
           TOP HEADER
        ===================================================== */

        .top-header {
            position: sticky;
            top: 0;
            z-index: 1000;

            height: 68px;

            background: #ffffff;
            border-bottom: 1px solid var(--border);

            display: flex;
            align-items: center;

            padding: 0 28px;

            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .brand-area {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;

            background: var(--lab-blue);
            color: white;

            border-radius: 8px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20px;
        }

        .brand-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--lab-blue-dark);
            margin: 0;
        }

        .brand-subtitle {
            font-size: 12px;
            color: var(--muted);
            margin-top: 1px;
        }

        .student-area {
            margin-left: auto;

            display: flex;
            align-items: center;
            gap: 10px;
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
            font-size: 14px;
            font-weight: 600;
        }

        /* =====================================================
           PAGE
        ===================================================== */

        .page-container {
            max-width: 1250px;

            margin: 0 auto;

            padding: 32px 24px 50px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;

            gap: 20px;

            margin-bottom: 25px;
        }

        .heading-title {
            font-size: 27px;
            font-weight: 700;

            color: var(--lab-blue-dark);

            margin-bottom: 5px;
        }

        .heading-description {
            color: var(--muted);
            font-size: 14px;
            margin: 0;
        }

        /* =====================================================
           CARDS
        ===================================================== */

        .lab-card {
            background: var(--card-bg);

            border: 1px solid var(--border);
            border-radius: 10px;

            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.035);

            margin-bottom: 22px;
        }

        .lab-card-header {
            padding: 17px 20px;

            border-bottom: 1px solid var(--border);

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;
        }

        .lab-card-title {
            display: flex;
            align-items: center;

            gap: 10px;

            font-size: 16px;
            font-weight: 700;

            color: var(--lab-blue-dark);

            margin: 0;
        }

        .lab-card-title i {
            color: var(--lab-blue);
        }

        .lab-card-body {
            padding: 20px;
        }

        /* =====================================================
           SUMMARY CARDS
        ===================================================== */

        .summary-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 18px;

            margin-bottom: 25px;
        }

        .summary-card {
            background: #ffffff;

            border: 1px solid var(--border);
            border-radius: 9px;

            padding: 19px;

            display: flex;
            align-items: center;

            gap: 15px;
        }

        .summary-icon {
            width: 46px;
            height: 46px;

            flex-shrink: 0;

            border-radius: 8px;

            background: var(--lab-blue-light);
            color: var(--lab-blue);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 20px;
        }

        .summary-number {
            font-size: 24px;
            font-weight: 700;

            color: var(--lab-blue-dark);

            line-height: 1.1;
        }

        .summary-label {
            font-size: 12px;
            color: var(--muted);

            margin-top: 4px;
        }

        /* =====================================================
           TRIAL TABLE
        ===================================================== */

        .table-responsive {
            border: 1px solid var(--border);
            border-radius: 8px;

            overflow-x: auto;
        }

        .trial-table {
            width: 100%;

            margin: 0;

            border-collapse: collapse;
        }

        .trial-table th {
            background: #f7f9fa;

            color: #46515a;

            font-size: 12px;
            font-weight: 700;

            padding: 13px 14px;

            border-bottom: 1px solid var(--border);

            white-space: nowrap;
        }

        .trial-table td {
            padding: 14px;

            border-bottom: 1px solid #edf0f2;

            font-size: 13px;

            vertical-align: middle;
        }

        .trial-table tr:last-child td {
            border-bottom: 0;
        }

        .trial-table tbody tr:hover {
            background: #fafcfd;
        }

        .trial-number {
            width: 32px;
            height: 32px;

            border-radius: 6px;

            background: var(--lab-blue-light);
            color: var(--lab-blue);

            display: inline-flex;

            align-items: center;
            justify-content: center;

            font-weight: 700;
            font-size: 12px;
        }

        /* =====================================================
           BADGES
        ===================================================== */

        .type-badge {
            display: inline-flex;

            align-items: center;

            padding: 5px 9px;

            border-radius: 5px;

            background: var(--lab-blue-light);
            color: var(--lab-blue-dark);

            font-size: 11px;
            font-weight: 700;
        }

        .date-text {
            color: var(--muted);
            font-size: 12px;
        }

        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {
            text-align: center;

            padding: 45px 20px;
        }

        .empty-icon {
            width: 60px;
            height: 60px;

            margin: 0 auto 15px;

            border-radius: 50%;

            background: var(--lab-blue-light);
            color: var(--lab-blue);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
        }

        .empty-state h5 {
            color: var(--lab-blue-dark);

            font-size: 17px;
            font-weight: 700;

            margin-bottom: 7px;
        }

        .empty-state p {
            color: var(--muted);

            font-size: 13px;

            max-width: 500px;

            margin: 0 auto 18px;
        }

        /* =====================================================
           TRIAL DETAILS
        ===================================================== */

        .detail-section {
            margin-top: 15px;
        }

        .detail-title {
            font-size: 13px;

            font-weight: 700;

            color: var(--lab-blue-dark);

            margin-bottom: 10px;
        }

        .detail-grid {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 10px;
        }

        .detail-item {
            padding: 11px;

            border: 1px solid var(--border);
            border-radius: 6px;

            background: #fafbfc;
        }

        .detail-label {
            font-size: 10px;

            text-transform: uppercase;

            color: var(--muted);

            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 13px;

            font-weight: 600;

            color: var(--text);
        }

        /* =====================================================
           BUTTONS
        ===================================================== */

        .btn-lab {
            background: var(--lab-blue);
            border-color: var(--lab-blue);

            color: white;

            font-size: 13px;
            font-weight: 600;

            border-radius: 6px;

            padding: 9px 15px;
        }

        .btn-lab:hover {
            background: var(--lab-blue-dark);
            border-color: var(--lab-blue-dark);

            color: white;
        }

        .btn-outline-lab {
            color: var(--lab-blue);

            border: 1px solid var(--lab-blue);

            background: white;

            font-size: 13px;
            font-weight: 600;

            border-radius: 6px;

            padding: 8px 14px;
        }

        .btn-outline-lab:hover {
            background: var(--lab-blue-light);
            color: var(--lab-blue-dark);
        }

        .actions {
            display: flex;

            flex-wrap: wrap;

            gap: 10px;

            margin-top: 20px;
        }

        /* =====================================================
           LEARNING NOTE
        ===================================================== */

        .learning-note {
            background: #f8fafb;

            border-left: 4px solid var(--lab-blue);

            padding: 15px 17px;

            border-radius: 5px;

            color: #4e5962;

            font-size: 13px;

            line-height: 1.6;
        }

        .learning-note strong {
            color: var(--lab-blue-dark);
        }

        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 900px) {

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .detail-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }

        @media (max-width: 650px) {

            .top-header {
                height: auto;

                min-height: 65px;

                padding: 10px 15px;
            }

            .brand-subtitle {
                display: none;
            }

            .student-name {
                display: none;
            }

            .page-container {
                padding: 22px 14px 40px;
            }

            .page-heading {
                flex-direction: column;
            }

            .heading-title {
                font-size: 23px;
            }

            .lab-card-body {
                padding: 15px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
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
            <?= strtoupper(substr($student_name, 0, 1)) ?>
        </div>

    </div>

</header>


<!-- =========================================================
     PAGE
========================================================= -->

<main class="page-container">

    <div class="page-heading">

        <div>

            <div class="heading-title">
                Practical 4 Simulation History
            </div>

            <p class="heading-description">
                Review your previous Spray Drying and Freeze Drying simulation trials.
            </p>

        </div>

        <div>

            <a href="simulation4.php"
               class="btn btn-lab">

                <i class="bi bi-plus-lg me-1"></i>
                New Simulation

            </a>

        </div>

    </div>


    <!-- =====================================================
         SUMMARY
    ===================================================== -->

    <div class="summary-grid">

        <div class="summary-card">

            <div class="summary-icon">
                <i class="bi bi-bar-chart-line"></i>
            </div>

            <div>

                <div class="summary-number">
                    <?= $totalTrials ?>
                </div>

                <div class="summary-label">
                    Total Simulation Trials
                </div>

            </div>

        </div>


        <div class="summary-card">

            <div class="summary-icon">
                <i class="bi bi-wind"></i>
            </div>

            <div>

                <div class="summary-number">
                    <?= $totalSpray ?>
                </div>

                <div class="summary-label">
                    Spray Drying Trials
                </div>

            </div>

        </div>


        <div class="summary-card">

            <div class="summary-icon">
                <i class="bi bi-snow2"></i>
            </div>

            <div>

                <div class="summary-number">
                    <?= $totalFreeze ?>
                </div>

                <div class="summary-label">
                    Freeze Drying Trials
                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         SAVED TRIALS
    ===================================================== -->

    <div class="lab-card">

        <div class="lab-card-header">

            <h2 class="lab-card-title">

                <i class="bi bi-clock-history"></i>

                Saved Simulation Trials

            </h2>

            <?php if ($totalTrials > 0): ?>

                <span class="type-badge">
                    <?= $totalTrials ?> Saved
                </span>

            <?php endif; ?>

        </div>


        <div class="lab-card-body">

            <?php if (empty($results)): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        <i class="bi bi-clipboard-x"></i>
                    </div>

                    <h5>
                        No Simulation Results Yet
                    </h5>

                    <p>
                        You have not saved any Practical 4 simulation results.
                        Run a Spray Drying or Freeze Drying simulation and save
                        the results to see them here.
                    </p>

                    <a href="simulation4.php"
                       class="btn btn-lab">

                        <i class="bi bi-play-fill me-1"></i>
                        Start Simulation

                    </a>

                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="trial-table">

                        <thead>

                            <tr>

                                <th>#</th>

                                <th>Simulation Type</th>

                                <th>Date & Time</th>

                                <th>Key Result</th>

                                <th class="text-end">
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($results as $index => $trial): ?>

                            <?php

                            $type = strtolower(
                                $trial['simulation_type'] ?? ''
                            );

                            $isSpray =
                                strpos($type, 'spray') !== false;

                            $output = $trial['output'];

                            $keyResult = '—';

                            if ($isSpray) {

                                $dryingTime = getValue(
                                    $output,
                                    'dryingTime'
                                );

                                if ($dryingTime !== '—') {

                                    $keyResult =
                                        formatValue(
                                            $dryingTime
                                        ) . " min";
                                }

                            } else {

                                $experimentalTime = getValue(
                                    $output,
                                    'experimentalTime'
                                );

                                if ($experimentalTime !== '—') {

                                    $keyResult =
                                        formatValue(
                                            $experimentalTime
                                        ) . " min";
                                }
                            }

                            ?>

                            <tr>

                                <td>

                                    <span class="trial-number">
                                        <?= $index + 1 ?>
                                    </span>

                                </td>

                                <td>

                                    <span class="type-badge">

                                        <i class="bi
                                            <?= $isSpray
                                                ? 'bi-wind'
                                                : 'bi-snow2'
                                            ?>
                                            me-1">
                                        </i>

                                        <?= htmlspecialchars(
                                            $trial['simulation_type']
                                        ) ?>

                                    </span>

                                </td>

                                <td>

                                    <span class="date-text">

                                        <i class="bi bi-calendar3 me-1"></i>

                                        <?= htmlspecialchars(
                                            date(
                                                'd M Y, H:i',
                                                strtotime(
                                                    $trial['created_at']
                                                )
                                            )
                                        ) ?>

                                    </span>

                                </td>

                                <td>

                                    <strong>
                                        <?= $keyResult ?>
                                    </strong>

                                </td>

                                <td class="text-end">

                                    <button
                                        type="button"
                                        class="btn btn-outline-lab btn-sm"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#trialDetails<?= $trial['id'] ?>"
                                        aria-expanded="false">

                                        <i class="bi bi-eye me-1"></i>
                                        Details

                                    </button>

                                </td>

                            </tr>


                            <!-- =================================================
                                 TRIAL DETAILS
                            ================================================= -->

                            <tr class="collapse"
                                id="trialDetails<?= $trial['id'] ?>">

                                <td colspan="5">

                                    <div class="detail-section">

                                        <?php if ($isSpray): ?>

                                            <div class="detail-title">

                                                <i class="bi bi-wind me-1"></i>
                                                Spray Drying Results

                                            </div>

                                            <div class="detail-grid">

                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Inlet Temperature
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $trial['input'],
                                                                'inletTemp'
                                                            )
                                                        ) ?>
                                                        °C
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Feed Flow
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $trial['input'],
                                                                'feedFlow'
                                                            )
                                                        ) ?>
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Initial Moisture
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $trial['input'],
                                                                'initialMoisture'
                                                            )
                                                        ) ?>
                                                        %
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Final Moisture
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $trial['input'],
                                                                'finalMoisture'
                                                            )
                                                        ) ?>
                                                        %
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Drying Time
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $output,
                                                                'dryingTime'
                                                            )
                                                        ) ?>
                                                        min
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Water Removed
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $output,
                                                                'waterRemoved'
                                                            )
                                                        ) ?>
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Dry Product
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $output,
                                                                'dryProduct'
                                                            )
                                                        ) ?>
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Efficiency
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $output,
                                                                'efficiency'
                                                            )
                                                        ) ?>
                                                        %
                                                    </div>

                                                </div>

                                            </div>

                                        <?php else: ?>

                                            <div class="detail-title">

                                                <i class="bi bi-snow2 me-1"></i>
                                                Freeze Drying Results

                                            </div>

                                            <div class="detail-grid">

                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Product Thickness
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $trial['input'],
                                                                'thickness'
                                                            )
                                                        ) ?>
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Plate Temperature
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $trial['input'],
                                                                'plateTemp'
                                                            )
                                                        ) ?>
                                                        °C
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Chamber Pressure
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $trial['input'],
                                                                'pressure'
                                                            )
                                                        ) ?>
                                                        mbar
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Initial Moisture
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $trial['input'],
                                                                'moisture'
                                                            )
                                                        ) ?>
                                                        %
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Theoretical Time
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $output,
                                                                'theoreticalTime'
                                                            )
                                                        ) ?>
                                                        min
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Experimental Time
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $output,
                                                                'experimentalTime'
                                                            )
                                                        ) ?>
                                                        min
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Final Moisture
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $output,
                                                                'finalMoisture'
                                                            )
                                                        ) ?>
                                                        %
                                                    </div>

                                                </div>


                                                <div class="detail-item">

                                                    <div class="detail-label">
                                                        Time Difference
                                                    </div>

                                                    <div class="detail-value">
                                                        <?= formatValue(
                                                            getValue(
                                                                $output,
                                                                'difference'
                                                            )
                                                        ) ?>
                                                        min
                                                    </div>

                                                </div>

                                            </div>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     COMPARE TRIALS
                ================================================= -->

                <div class="lab-card mt-4 mb-0">

                    <div class="lab-card-header">

                        <h3 class="lab-card-title">

                            <i class="bi bi-arrow-left-right"></i>

                            Compare Simulation Trials

                        </h3>

                    </div>

                    <div class="lab-card-body">

                        <p class="mb-3"
                           style="font-size:13px;color:var(--muted);">

                            Compare two saved trials to examine how
                            different operating conditions affect the
                            simulation results.

                        </p>

                        <?php if ($totalSpray >= 2 || $totalFreeze >= 2): ?>

                            <div class="actions mt-0">

                                <?php if ($totalSpray >= 2): ?>

                                    <a
                                        href="simulation4_compare.php?type=spray"
                                        class="btn btn-lab">

                                        <i class="bi bi-wind me-1"></i>
                                        Compare Spray Drying

                                    </a>

                                <?php endif; ?>


                                <?php if ($totalFreeze >= 2): ?>

                                    <a
                                        href="simulation4_compare.php?type=freeze"
                                        class="btn btn-outline-lab">

                                        <i class="bi bi-snow2 me-1"></i>
                                        Compare Freeze Drying

                                    </a>

                                <?php endif; ?>

                            </div>

                        <?php else: ?>

                            <div class="learning-note">

                                <strong>
                                    Comparison unavailable:
                                </strong>

                                At least two saved trials of the same
                                simulation type are required before a
                                comparison can be performed.

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =====================================================
         LEARNING NOTE
    ===================================================== -->

    <div class="lab-card">

        <div class="lab-card-header">

            <h2 class="lab-card-title">

                <i class="bi bi-mortarboard"></i>

                Learning Note

            </h2>

        </div>

        <div class="lab-card-body">

            <div class="learning-note">

                <strong>Why save simulation results?</strong>

                Saving each trial allows you to review previous
                experimental conditions and calculated results.
                You can also compare trials to understand how changes
                in temperature, moisture, pressure, thickness, and
                other operating conditions influence drying performance.

            </div>

        </div>

    </div>


    <!-- =====================================================
         ACTIONS
    ===================================================== -->

    <div class="actions">

        <a
            href="../practical/practical4.php"
            class="btn btn-outline-lab">

            <i class="bi bi-arrow-left me-1"></i>
            Back to Practical 4

        </a>


        <a
            href="simulation4.php"
            class="btn btn-lab">

            <i class="bi bi-play-fill me-1"></i>
            New Simulation

        </a>


        <?php if (!empty($results)): ?>

            <button
                type="button"
                onclick="window.print()"
                class="btn btn-outline-lab">

                <i class="bi bi-printer me-1"></i>
                Print History

            </button>

        <?php endif; ?>

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
    .btn,
    button {
        display: none !important;
    }

    body {
        background: white;
    }

    .page-container {
        max-width: none;
        padding: 10px;
    }

    .lab-card,
    .summary-card {
        box-shadow: none;
    }

}

</style>

</body>
</html>