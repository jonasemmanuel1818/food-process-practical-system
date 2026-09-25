<?php
require_once "../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, input_data, result_data, created_at
     FROM simulation_results
     WHERE user_id = ? AND practical_number = 5
     ORDER BY created_at DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$records = [];

while ($row = mysqli_fetch_assoc($result)) {
    $inputData = json_decode($row['input_data'], true);
    $resultData = json_decode($row['result_data'], true);

    if (!is_array($inputData)) {
        $inputData = [];
    }

    if (!is_array($resultData)) {
        $resultData = [];
    }

    /*
     * Support the current Practical 5 structure:
     *
     * result_data
     * ├── filtration
     * ├── graphData
     * ├── filtrationGraph
     * ├── times
     * ├── filtrate
     * └── retained
     */

    $filtration = [];

    if (isset($resultData['filtration']) && is_array($resultData['filtration'])) {
        $filtration = $resultData['filtration'];
    }

    $initialMass = isset($filtration['initialMass'])
        ? (float) $filtration['initialMass']
        : (float) ($inputData['initialMass'] ?? 0);

    $retainedMass = isset($filtration['retainedMass'])
        ? (float) $filtration['retainedMass']
        : (float) ($inputData['retainedMass'] ?? 0);

    $filtrateMass = isset($filtration['filtrateMass'])
        ? (float) $filtration['filtrateMass']
        : max(0, $initialMass - $retainedMass);

    $retentionPercentage = isset($filtration['retentionPercentage'])
        ? (float) $filtration['retentionPercentage']
        : ($initialMass > 0 ? ($retainedMass / $initialMass) * 100 : 0);

    $separationPercentage = isset($filtration['separationPercentage'])
        ? (float) $filtration['separationPercentage']
        : ($initialMass > 0 ? ($filtrateMass / $initialMass) * 100 : 0);

    $filtrationTime = isset($filtration['filtrationTime'])
        ? (float) $filtration['filtrationTime']
        : (float) ($inputData['filtrationTime'] ?? 0);

    /*
     * Get graph data.
     */
    $graphData = [];

    if (
        isset($resultData['graphData']) &&
        is_array($resultData['graphData'])
    ) {
        $graphData = $resultData['graphData'];
    } elseif (
        isset($resultData['filtrationGraph']) &&
        is_array($resultData['filtrationGraph'])
    ) {
        $graphData = $resultData['filtrationGraph'];
    } else {
        $graphData = [
            "labels" => $resultData['times'] ?? [],
            "filtrateMass" => $resultData['filtrate'] ?? [],
            "retainedMass" => $resultData['retained'] ?? []
        ];
    }

    $labels = isset($graphData['labels']) && is_array($graphData['labels'])
        ? array_values($graphData['labels'])
        : [];

    $filtrateValues = isset($graphData['filtrateMass']) && is_array($graphData['filtrateMass'])
        ? array_values($graphData['filtrateMass'])
        : [];

    $retainedValues = isset($graphData['retainedMass']) && is_array($graphData['retainedMass'])
        ? array_values($graphData['retainedMass'])
        : [];

    $records[] = [
        "id" => (int) $row['id'],
        "created_at" => $row['created_at'],
        "initialMass" => $initialMass,
        "retainedMass" => $retainedMass,
        "filtrateMass" => $filtrateMass,
        "retentionPercentage" => $retentionPercentage,
        "separationPercentage" => $separationPercentage,
        "filtrationTime" => $filtrationTime,
        "labels" => $labels,
        "filtrateValues" => $filtrateValues,
        "retainedValues" => $retainedValues
    ];
}

mysqli_stmt_close($stmt);

$totalSimulations = count($records);

$totalInitialMass = 0;
$totalFiltrateMass = 0;
$totalRetainedMass = 0;

foreach ($records as $record) {
    $totalInitialMass += $record['initialMass'];
    $totalFiltrateMass += $record['filtrateMass'];
    $totalRetainedMass += $record['retainedMass'];
}

$averageSeparation = 0;
$averageTime = 0;

if ($totalSimulations > 0) {
    $separationTotal = 0;
    $timeTotal = 0;

    foreach ($records as $record) {
        $separationTotal += $record['separationPercentage'];
        $timeTotal += $record['filtrationTime'];
    }

    $averageSeparation = $separationTotal / $totalSimulations;
    $averageTime = $timeTotal / $totalSimulations;
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Practical 5 Simulation History</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            background: #f4f6f8;
            color: #263238;
            font-family: Arial, sans-serif;
        }

        .page-header {
            background: #1f5f75;
            color: white;
            padding: 28px 0;
        }

        .page-header h1 {
            margin: 0;
            font-weight: 700;
        }

        .page-header p {
            margin: 6px 0 0;
            opacity: 0.9;
        }

        .stat-card {
            background: white;
            border: 1px solid #d9dee3;
            border-radius: 12px;
            padding: 20px;
            height: 100%;
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            background: #e8f2f5;
            color: #1f5f75;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 25px;
            font-weight: 700;
            color: #17495a;
        }

        .stat-label {
            color: #68757d;
            font-size: 14px;
        }

        .history-card {
            background: white;
            border: 1px solid #d9dee3;
            border-radius: 12px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .history-header {
            padding: 16px 20px;
            background: #f8fafb;
            border-bottom: 1px solid #d9dee3;
        }

        .history-header h5 {
            margin: 0;
            color: #17495a;
            font-weight: 700;
        }

        .history-body {
            padding: 20px;
        }

        .result-box {
            border: 1px solid #d9dee3;
            border-radius: 10px;
            padding: 14px;
            background: #fff;
            height: 100%;
        }

        .result-label {
            color: #68757d;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .result-value {
            color: #17495a;
            font-size: 19px;
            font-weight: 700;
        }

        .chart-container {
            position: relative;
            height: 330px;
            margin-top: 20px;
        }

        .empty-state {
            background: white;
            border: 1px solid #d9dee3;
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
        }

        .empty-state i {
            font-size: 55px;
            color: #1f5f75;
        }

        .empty-state h4 {
            margin-top: 18px;
            color: #17495a;
        }

        .btn-lab {
            background: #1f5f75;
            border-color: #1f5f75;
            color: white;
        }

        .btn-lab:hover {
            background: #17495a;
            border-color: #17495a;
            color: white;
        }

        .table th {
            background: #f4f6f8;
            color: #17495a;
            font-size: 13px;
        }

        .table td {
            vertical-align: middle;
            font-size: 14px;
        }

        .badge-result {
            background: #e8f2f5;
            color: #17495a;
            padding: 7px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
    </style>
</head>

<body>

<header class="page-header">
    <div class="container">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

            <div>
                <h1>
                    <i class="bi bi-clock-history me-2"></i>
                    Simulation History
                </h1>

                <p>
                    Practical 5 — Filtration and Separation
                </p>
            </div>

            <div class="d-flex gap-2">

                <a
                    href="../dashboard.php"
                    class="btn btn-light"
                >
                    <i class="bi bi-speedometer2 me-1"></i>
                    Dashboard
                </a>

                <a
                    href="simulation5.php"
                    class="btn btn-outline-light"
                >
                    <i class="bi bi-play-circle me-1"></i>
                    New Simulation
                </a>

            </div>

        </div>

    </div>
</header>


<main class="container py-4">

    <!-- Statistics -->

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="stat-card">

                <div class="stat-icon">
                    <i class="bi bi-bar-chart-line"></i>
                </div>

                <div class="stat-value">
                    <?= h($totalSimulations) ?>
                </div>

                <div class="stat-label">
                    Saved Simulations
                </div>

            </div>
        </div>


        <div class="col-md-3">
            <div class="stat-card">

                <div class="stat-icon">
                    <i class="bi bi-droplet"></i>
                </div>

                <div class="stat-value">
                    <?= number_format($totalFiltrateMass, 2) ?> g
                </div>

                <div class="stat-label">
                    Total Filtrate
                </div>

            </div>
        </div>


        <div class="col-md-3">
            <div class="stat-card">

                <div class="stat-icon">
                    <i class="bi bi-filter"></i>
                </div>

                <div class="stat-value">
                    <?= number_format($averageSeparation, 2) ?>%
                </div>

                <div class="stat-label">
                    Average Separation
                </div>

            </div>
        </div>


        <div class="col-md-3">
            <div class="stat-card">

                <div class="stat-icon">
                    <i class="bi bi-stopwatch"></i>
                </div>

                <div class="stat-value">
                    <?= number_format($averageTime, 2) ?> s
                </div>

                <div class="stat-label">
                    Average Filtration Time
                </div>

            </div>
        </div>

    </div>


    <?php if ($totalSimulations === 0): ?>

        <div class="empty-state">

            <i class="bi bi-filter-circle"></i>

            <h4>
                No Simulation Results Yet
            </h4>

            <p class="text-muted">
                You have not saved any Practical 5 filtration simulations.
            </p>

            <a
                href="simulation5.php"
                class="btn btn-lab mt-2"
            >
                <i class="bi bi-play-circle me-1"></i>
                Run Simulation
            </a>

        </div>

    <?php else: ?>


        <!-- History Table -->

        <div class="history-card">

            <div class="history-header">

                <div class="d-flex justify-content-between align-items-center">

                    <h5>
                        <i class="bi bi-table me-2"></i>
                        Saved Simulation Results
                    </h5>

                    <span class="badge-result">
                        <?= h($totalSimulations) ?> result(s)
                    </span>

                </div>

            </div>

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Date & Time</th>
                            <th>Initial Mass</th>
                            <th>Retained Solid</th>
                            <th>Filtrate</th>
                            <th>Separation</th>
                            <th>Time</th>
                            <th>Graph</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($records as $index => $record): ?>

                            <tr>

                                <td>
                                    <?= h($index + 1) ?>
                                </td>

                                <td>
                                    <?= h(date("d M Y, H:i", strtotime($record['created_at']))) ?>
                                </td>

                                <td>
                                    <?= number_format($record['initialMass'], 2) ?> g
                                </td>

                                <td>
                                    <?= number_format($record['retainedMass'], 2) ?> g
                                </td>

                                <td>
                                    <?= number_format($record['filtrateMass'], 2) ?> g
                                </td>

                                <td>
                                    <span class="badge-result">
                                        <?= number_format($record['separationPercentage'], 2) ?>%
                                    </span>
                                </td>

                                <td>
                                    <?= number_format($record['filtrationTime'], 2) ?> s
                                </td>

                                <td>

                                    <?php if (count($record['labels']) > 0): ?>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-lab"
                                            onclick="showGraph(<?= h($record['id']) ?>)"
                                        >
                                            <i class="bi bi-graph-up"></i>
                                            View
                                        </button>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            No graph
                                        </span>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- Detailed Results -->

        <?php foreach ($records as $index => $record): ?>

            <div class="history-card">

                <div class="history-header">

                    <div class="d-flex justify-content-between align-items-center">

                        <h5>
                            <i class="bi bi-funnel me-2"></i>
                            Simulation <?= h($index + 1) ?>
                        </h5>

                        <small class="text-muted">
                            <?= h(date("d M Y, H:i:s", strtotime($record['created_at']))) ?>
                        </small>

                    </div>

                </div>


                <div class="history-body">

                    <div class="row g-3">

                        <div class="col-md-3">
                            <div class="result-box">
                                <div class="result-label">
                                    Initial Mass
                                </div>

                                <div class="result-value">
                                    <?= number_format($record['initialMass'], 2) ?> g
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="result-box">
                                <div class="result-label">
                                    Retained Solid
                                </div>

                                <div class="result-value">
                                    <?= number_format($record['retainedMass'], 2) ?> g
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="result-box">
                                <div class="result-label">
                                    Filtrate Mass
                                </div>

                                <div class="result-value">
                                    <?= number_format($record['filtrateMass'], 2) ?> g
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="result-box">
                                <div class="result-label">
                                    Filtration Time
                                </div>

                                <div class="result-value">
                                    <?= number_format($record['filtrationTime'], 2) ?> s
                                </div>
                            </div>
                        </div>


                        <div class="col-md-6">
                            <div class="result-box">
                                <div class="result-label">
                                    Retention Percentage
                                </div>

                                <div class="result-value">
                                    <?= number_format($record['retentionPercentage'], 2) ?>%
                                </div>
                            </div>
                        </div>


                        <div class="col-md-6">
                            <div class="result-box">
                                <div class="result-label">
                                    Separation Percentage
                                </div>

                                <div class="result-value">
                                    <?= number_format($record['separationPercentage'], 2) ?>%
                                </div>
                            </div>
                        </div>

                    </div>


                    <?php if (count($record['labels']) > 0): ?>

                        <div class="mt-4">

                            <h6 class="fw-bold" style="color:#17495a;">
                                <i class="bi bi-graph-up me-2"></i>
                                Filtration Graph
                            </h6>

                            <div class="chart-container">

                                <canvas
                                    id="historyChart<?= h($record['id']) ?>"
                                ></canvas>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</main>


<script>
const historyRecords = <?= json_encode(
    $records,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
) ?>;

const historyCharts = {};


function showGraph(recordId) {

    const record = historyRecords.find(
        item => Number(item.id) === Number(recordId)
    );

    if (!record) {
        return;
    }

    const canvas = document.getElementById(
        "historyChart" + recordId
    );

    if (!canvas) {
        return;
    }

    if (historyCharts[recordId]) {
        historyCharts[recordId].destroy();
    }

    historyCharts[recordId] = new Chart(canvas, {
        type: "line",

        data: {
            labels: record.labels,

            datasets: [
                {
                    label: "Filtrate Mass (g)",
                    data: record.filtrateValues,
                    borderWidth: 2,
                    tension: 0.25,
                    fill: false
                },

                {
                    label: "Retained Solid Mass (g)",
                    data: record.retainedValues,
                    borderWidth: 2,
                    tension: 0.25,
                    fill: false
                }
            ]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            interaction: {
                intersect: false,
                mode: "index"
            },

            plugins: {
                legend: {
                    display: true
                },

                tooltip: {
                    enabled: true
                }
            },

            scales: {
                x: {
                    title: {
                        display: true,
                        text: "Filtration Time"
                    }
                },

                y: {
                    beginAtZero: true,

                    title: {
                        display: true,
                        text: "Mass (g)"
                    }
                }
            }
        }
    });
}


// Draw all available graphs when the page loads.
document.addEventListener("DOMContentLoaded", function () {

    historyRecords.forEach(function (record) {

        if (
            record.labels &&
            record.labels.length > 0
        ) {
            showGraph(record.id);
        }

    });

});
</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>