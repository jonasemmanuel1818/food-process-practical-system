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

$user_name = $_SESSION['user_name']
    ?? $_SESSION['name']
    ?? $_SESSION['studentName']
    ?? $_SESSION['full_name']
    ?? "Student";


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

$queryResult = $stmt->get_result();

$trials = [];

while ($row = $queryResult->fetch_assoc()) {

    $input = json_decode(
        $row['input_data'],
        true
    );

    $output = json_decode(
        $row['result_data'],
        true
    );

    $trials[] = [
        "id" => (int) $row['id'],
        "created_at" => $row['created_at'],
        "input" => $input ?: [],
        "output" => $output ?: []
    ];
}

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

    <title>Practical 3 | Results History</title>

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
            --muted: #6c757d;
        }

        body {
            margin: 0;
            background: var(--page-bg);
            color: #263238;
            font-family: Arial, Helvetica, sans-serif;
        }

        .system-header {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 14px 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            background: var(--lab-blue);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .brand-title {
            margin: 0;
            font-weight: 700;
            color: var(--lab-blue-dark);
        }

        .brand-subtitle {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .page-heading {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 25px 0;
        }

        .breadcrumb-text {
            color: var(--muted);
            font-size: 13px;
        }

        .page-title {
            color: var(--lab-blue-dark);
            font-size: 27px;
            font-weight: 700;
            margin: 4px 0;
        }

        .card-lab {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .card-header-lab {
            padding: 15px 18px;
            border-bottom: 1px solid var(--border);
            background: #fafbfc;
            font-weight: 700;
            color: var(--lab-blue-dark);
        }

        .card-body-lab {
            padding: 20px;
        }

        .summary-box {
            background: #ffffff;
            border: 1px solid var(--border);
            border-left: 4px solid var(--lab-blue);
            border-radius: 7px;
            padding: 18px;
            height: 100%;
        }

        .summary-label {
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            font-weight: 700;
        }

        .summary-value {
            font-size: 23px;
            font-weight: 700;
            color: var(--lab-blue-dark);
            margin-top: 4px;
        }

        .table thead th {
            background: var(--lab-blue);
            color: #ffffff;
            white-space: nowrap;
        }

        .table-container {
            overflow-x: auto;
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }

        .empty-icon {
            font-size: 55px;
            color: var(--lab-blue);
        }

        .learning-note {
            background: var(--lab-blue-light);
            border-left: 4px solid var(--lab-blue);
            padding: 15px;
            border-radius: 5px;
        }

        .footer {
            background: #ffffff;
            border-top: 1px solid var(--border);
            padding: 20px 0;
            text-align: center;
            color: var(--muted);
        }

        @media print {

            .no-print {
                display: none !important;
            }

            body {
                background: #ffffff !important;
            }

            .card-lab {
                box-shadow: none !important;
                break-inside: avoid;
            }

        }

    </style>

</head>

<body>


<header class="system-header">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div class="brand">

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


            <div class="d-flex align-items-center gap-2">

                <i class="bi bi-person-circle text-secondary"></i>

                <span class="fw-semibold">
                    <?= htmlspecialchars($user_name) ?>
                </span>

            </div>

        </div>

    </div>

</header>


<section class="page-heading">

    <div class="container">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>

                <div class="breadcrumb-text">
                    Practical 3 / Simulation / History
                </div>

                <h1 class="page-title">
                    Simulation Results History
                </h1>

                <p class="text-muted mb-0">
                    Thermal Processing — Heat Penetration Analysis
                </p>

            </div>


            <div class="no-print">

                <a
                    href="simulation3.php"
                    class="btn btn-outline-primary"
                >
                    <i class="bi bi-arrow-left"></i>
                    Back to Simulation
                </a>

                <a
                    href="../practical/practical3.php"
                    class="btn btn-outline-secondary"
                >
                    <i class="bi bi-journal-text"></i>
                    Practical 3
                </a>

            </div>

        </div>

    </div>

</section>


<main class="container py-4">


    <!-- SUMMARY -->

    <div class="row g-3 mb-4">

        <div class="col-lg-4">

            <div class="summary-box">

                <div class="summary-label">
                    Saved Trials
                </div>

                <div class="summary-value">
                    <?= count($trials) ?>
                </div>

            </div>

        </div>


        <div class="col-lg-4">

            <div class="summary-box">

                <div class="summary-label">
                    Practical
                </div>

                <div class="summary-value fs-5">
                    Thermal Processing
                </div>

            </div>

        </div>


        <div class="col-lg-4">

            <div class="summary-box">

                <div class="summary-label">
                    Student
                </div>

                <div class="summary-value fs-5">
                    <?= htmlspecialchars($user_name) ?>
                </div>

            </div>

        </div>

    </div>


    <!-- HISTORY -->

    <div class="card-lab">

        <div class="card-header-lab">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                <div>

                    <i class="bi bi-clock-history me-2"></i>
                    Saved Thermal Processing Trials

                </div>


                <div class="d-flex gap-2 no-print">

                    <?php if (count($trials) >= 2): ?>

                        <a
                            href="simulation3_compare.php"
                            class="btn btn-sm btn-lab"
                        >
                            <i class="bi bi-bar-chart-line"></i>
                            Compare Trials
                        </a>

                    <?php endif; ?>


                    <button
                        onclick="window.print()"
                        class="btn btn-sm btn-outline-dark"
                    >
                        <i class="bi bi-printer"></i>
                        Print
                    </button>

                </div>

            </div>

        </div>


        <div class="card-body-lab">

            <?php if (count($trials) === 0): ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>

                    <h3 class="mt-3">
                        No Saved Results
                    </h3>

                    <p class="text-muted">
                        Complete a simulation and save the results
                        to create a trial record.
                    </p>

                    <a
                        href="simulation3.php"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-play-fill"></i>
                        Run Simulation
                    </a>

                </div>

            <?php else: ?>


                <div class="table-container">

                    <table class="table table-bordered table-hover align-middle">

                        <thead>

                            <tr>

                                <th>Trial</th>
                                <th>Date</th>
                                <th>Initial °C</th>
                                <th>Retort °C</th>
                                <th>Heating</th>
                                <th>Processing</th>
                                <th>Cooling</th>
                                <th>Max PT</th>
                                <th>Heating Rate</th>
                                <th>Cooling Rate</th>
                                <th>Thermal Lag</th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($trials as $index => $trial): ?>

                            <?php

                            $input = $trial['input'];
                            $output = $trial['output'];

                            ?>

                            <tr>

                                <td class="fw-semibold">

                                    Trial
                                    <?= count($trials) - $index ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $trial['created_at']
                                    ) ?>

                                </td>


                                <td>

                                    <?= number_format(
                                        (float)($input['initialTemp'] ?? 0),
                                        2
                                    ) ?>

                                </td>


                                <td>

                                    <?= number_format(
                                        (float)($input['retortTemp'] ?? 0),
                                        2
                                    ) ?>

                                </td>


                                <td>

                                    <?= number_format(
                                        (float)($input['heatingTime'] ?? 0),
                                        0
                                    ) ?>

                                    min

                                </td>


                                <td>

                                    <?= number_format(
                                        (float)($input['processingTime'] ?? 0),
                                        0
                                    ) ?>

                                    min

                                </td>


                                <td>

                                    <?= number_format(
                                        (float)($input['coolingTime'] ?? 0),
                                        0
                                    ) ?>

                                    min

                                </td>


                                <td class="fw-semibold">

                                    <?= number_format(
                                        (float)($output['maxTemperature'] ?? 0),
                                        2
                                    ) ?>

                                    °C

                                </td>


                                <td>

                                    <?= number_format(
                                        (float)($output['heatingRate'] ?? 0),
                                        4
                                    ) ?>

                                </td>


                                <td>

                                    <?= number_format(
                                        (float)($output['coolingRate'] ?? 0),
                                        4
                                    ) ?>

                                </td>


                                <td>

                                    <?= number_format(
                                        (float)($output['thermalLag'] ?? 0),
                                        4
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


                <div class="learning-note mt-4">

                    <i class="bi bi-info-circle me-2"></i>

                    <strong>Learning note:</strong>

                    Saving multiple trials allows you to investigate how
                    changing heating, processing and cooling conditions
                    affects the thermal response of the product.

                </div>


            <?php endif; ?>

        </div>

    </div>


    <div class="d-flex flex-wrap gap-2 no-print">

        <a
            href="simulation3.php"
            class="btn btn-primary"
        >
            <i class="bi bi-play-fill"></i>
            New Simulation
        </a>


        <a
            href="../practical/practical3.php"
            class="btn btn-outline-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Back to Practical 3
        </a>

    </div>

</main>


<footer class="footer">

    <div class="container">

        <div class="fw-semibold">
            Food Process Practical Learning & Simulation System
        </div>

        <small>
            Practical 3 — Thermal Processing
        </small>

    </div>

</footer>

</body>

</html>