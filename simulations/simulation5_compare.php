<?php

require_once "../config.php";

/* =========================================================
   ACCESS CONTROL
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   GET SAVED PRACTICAL 5 SIMULATIONS
========================================================= */

$stmt = mysqli_prepare($conn, "
    SELECT
        id,
        input_data,
        result_data,
        created_at
    FROM simulation_results
    WHERE user_id = ?
      AND practical_number = 5
    ORDER BY created_at DESC
");

if (!$stmt) {
    die("Could not prepare database request.");
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$records = [];


while ($row = mysqli_fetch_assoc($result)) {

    $inputData = json_decode(
        $row['input_data'],
        true
    );

    $resultData = json_decode(
        $row['result_data'],
        true
    );

    if (!is_array($inputData)) {
        $inputData = [];
    }

    if (!is_array($resultData)) {
        $resultData = [];
    }


    /* =====================================================
       FILTRATION RESULTS
    ===================================================== */

    $filtration =
        isset($resultData['filtration']) &&
        is_array($resultData['filtration'])
            ? $resultData['filtration']
            : [];


    $initialMass = isset($filtration['initialMass'])
        ? (float) $filtration['initialMass']
        : (float) ($inputData['initialMass'] ?? 0);


    $retainedMass = isset($filtration['retainedMass'])
        ? (float) $filtration['retainedMass']
        : (float) ($inputData['retainedMass'] ?? 0);


    $filtrateMass = isset($filtration['filtrateMass'])
        ? (float) $filtration['filtrateMass']
        : max(
            0,
            $initialMass - $retainedMass
        );


    $retentionPercentage =
        isset($filtration['retentionPercentage'])
            ? (float) $filtration['retentionPercentage']
            : (
                $initialMass > 0
                    ? ($retainedMass / $initialMass) * 100
                    : 0
            );


    $separationPercentage =
        isset($filtration['separationPercentage'])
            ? (float) $filtration['separationPercentage']
            : (
                $initialMass > 0
                    ? ($filtrateMass / $initialMass) * 100
                    : 0
            );


    $filtrationTime =
        isset($filtration['filtrationTime'])
            ? (float) $filtration['filtrationTime']
            : (float) ($inputData['filtrationTime'] ?? 0);


    /* =====================================================
       GRAPH DATA
    ===================================================== */

    $graphData = [];

    if (
        isset($resultData['graphData']) &&
        is_array($resultData['graphData'])
    ) {

        $graphData =
            $resultData['graphData'];

    } elseif (
        isset($resultData['filtrationGraph']) &&
        is_array($resultData['filtrationGraph'])
    ) {

        $graphData =
            $resultData['filtrationGraph'];

    } else {

        $graphData = [
            "labels" =>
                $resultData['times'] ?? [],

            "filtrateMass" =>
                $resultData['filtrate'] ?? [],

            "retainedMass" =>
                $resultData['retained'] ?? []
        ];
    }


    $labels =
        isset($graphData['labels']) &&
        is_array($graphData['labels'])
            ? array_values($graphData['labels'])
            : [];


    $filtrateValues =
        isset($graphData['filtrateMass']) &&
        is_array($graphData['filtrateMass'])
            ? array_values($graphData['filtrateMass'])
            : [];


    $retainedValues =
        isset($graphData['retainedMass']) &&
        is_array($graphData['retainedMass'])
            ? array_values($graphData['retainedMass'])
            : [];


    $records[] = [

        "id" =>
            (int) $row['id'],

        "created_at" =>
            $row['created_at'],

        "initialMass" =>
            $initialMass,

        "retainedMass" =>
            $retainedMass,

        "filtrateMass" =>
            $filtrateMass,

        "retentionPercentage" =>
            $retentionPercentage,

        "separationPercentage" =>
            $separationPercentage,

        "filtrationTime" =>
            $filtrationTime,

        "labels" =>
            $labels,

        "filtrateValues" =>
            $filtrateValues,

        "retainedValues" =>
            $retainedValues
    ];
}

mysqli_stmt_close($stmt);


/* =========================================================
   HELPER
========================================================= */

function h($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
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
        Compare Filtration Simulations
    </title>


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


    <!-- Chart.js -->

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

            opacity: .9;

        }


        .lab-card {

            background: white;

            border: 1px solid #d9dee3;

            border-radius: 12px;

            overflow: hidden;

            margin-bottom: 24px;

        }


        .card-header-lab {

            background: #f8fafb;

            border-bottom: 1px solid #d9dee3;

            padding: 17px 20px;

            color: #17495a;

            font-weight: 700;

        }


        .card-body-lab {

            padding: 20px;

        }


        .simulation-option {

            border: 1px solid #d9dee3;

            border-radius: 10px;

            padding: 14px;

            margin-bottom: 10px;

            transition: .2s;

            background: white;

        }


        .simulation-option:hover {

            border-color: #1f5f75;

            background: #f8fbfc;

        }


        .simulation-option input {

            margin-right: 10px;

        }


        .simulation-date {

            font-weight: 700;

            color: #17495a;

        }


        .simulation-info {

            font-size: 13px;

            color: #68757d;

            margin-top: 4px;

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


        .metric-card {

            border: 1px solid #d9dee3;

            border-radius: 10px;

            padding: 17px;

            background: white;

            height: 100%;

        }


        .metric-title {

            font-size: 13px;

            color: #68757d;

            margin-bottom: 6px;

        }


        .metric-value {

            font-size: 22px;

            font-weight: 700;

            color: #17495a;

        }


        .chart-container {

            position: relative;

            height: 390px;

        }


        .empty-state {

            text-align: center;

            padding: 60px 20px;

        }


        .empty-state i {

            font-size: 60px;

            color: #1f5f75;

        }


        .empty-state h4 {

            color: #17495a;

            margin-top: 15px;

        }


        .selection-count {

            color: #68757d;

            font-size: 14px;

        }


        .comparison-table th {

            background: #f4f6f8;

            color: #17495a;

            white-space: nowrap;

        }


        .comparison-table td {

            vertical-align: middle;

            white-space: nowrap;

        }


        .result-highlight {

            font-weight: 700;

            color: #17495a;

        }

    </style>

</head>


<body>


<!-- ======================================================
     HEADER
======================================================= -->

<header class="page-header">

    <div class="container">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

            <div>

                <h1>

                    <i class="bi bi-bar-chart-line me-2"></i>

                    Compare Simulations

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


<?php if (count($records) < 2): ?>


    <!-- ==================================================
         NOT ENOUGH RESULTS
    =================================================== -->

    <div class="lab-card">

        <div class="card-body-lab empty-state">

            <i class="bi bi-bar-chart-line"></i>

            <h4>
                Not Enough Simulations
            </h4>

            <p class="text-muted">

                You need at least two saved Practical 5
                simulations before you can compare results.

            </p>


            <div class="d-flex justify-content-center gap-2 mt-3">

                <a
                    href="simulation5.php"
                    class="btn btn-lab"
                >

                    <i class="bi bi-play-circle me-1"></i>

                    Run New Simulation

                </a>


                <a
                    href="simulation5_history.php"
                    class="btn btn-outline-secondary"
                >

                    <i class="bi bi-clock-history me-1"></i>

                    View History

                </a>

            </div>

        </div>

    </div>


<?php else: ?>


    <!-- ==================================================
         SELECT SIMULATIONS
    =================================================== -->

    <div class="lab-card">

        <div class="card-header-lab">

            <i class="bi bi-check2-square me-2"></i>

            Select Simulations to Compare

        </div>


        <div class="card-body-lab">

            <p class="text-muted mb-3">

                Select two or more saved simulations.
                The selected results will be displayed together
                for comparison.

            </p>


            <div class="row">

                <?php foreach ($records as $index => $record): ?>

                    <div class="col-md-6 col-lg-4">

                        <label
                            class="simulation-option d-block"
                        >

                            <div>

                                <input
                                    type="checkbox"
                                    class="simulation-checkbox"
                                    value="<?= h($record['id']) ?>"
                                >

                                <span class="simulation-date">

                                    Simulation <?= h($index + 1) ?>

                                </span>

                            </div>


                            <div class="simulation-info">

                                <?= h(
                                    date(
                                        "d M Y, H:i:s",
                                        strtotime(
                                            $record['created_at']
                                        )
                                    )
                                ) ?>

                            </div>


                            <div class="simulation-info">

                                Initial:
                                <?= number_format(
                                    $record['initialMass'],
                                    2
                                ) ?> g

                                &nbsp; | &nbsp;

                                Filtrate:
                                <?= number_format(
                                    $record['filtrateMass'],
                                    2
                                ) ?> g

                            </div>

                        </label>

                    </div>

                <?php endforeach; ?>

            </div>


            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">

                <span
                    class="selection-count"
                    id="selectionCount"
                >
                    0 simulations selected
                </span>


                <div class="d-flex gap-2">

                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        id="clearSelectionBtn"
                    >

                        <i class="bi bi-x-circle me-1"></i>

                        Clear

                    </button>


                    <button
                        type="button"
                        class="btn btn-lab"
                        id="compareBtn"
                    >

                        <i class="bi bi-bar-chart-line me-1"></i>

                        Compare Selected

                    </button>

                </div>

            </div>

        </div>

    </div>



    <!-- ==================================================
         COMPARISON RESULTS
    =================================================== -->

    <div
        id="comparisonSection"
        style="display:none;"
    >


        <!-- Summary -->

        <div class="lab-card">

            <div class="card-header-lab">

                <i class="bi bi-grid-3x3-gap me-2"></i>

                Comparison Summary

            </div>


            <div class="card-body-lab">

                <div
                    class="row g-3"
                    id="metricContainer"
                >
                </div>

            </div>

        </div>



        <!-- Table -->

        <div class="lab-card">

            <div class="card-header-lab">

                <i class="bi bi-table me-2"></i>

                Detailed Comparison

            </div>


            <div class="card-body-lab">

                <div class="table-responsive">

                    <table
                        class="table table-bordered comparison-table"
                    >

                        <thead>

                            <tr id="comparisonHeader">

                                <th>
                                    Parameter
                                </th>

                            </tr>

                        </thead>


                        <tbody id="comparisonBody">

                        </tbody>

                    </table>

                </div>

            </div>

        </div>



        <!-- Filtrate Graph -->

        <div class="lab-card">

            <div class="card-header-lab">

                <i class="bi bi-graph-up me-2"></i>

                Filtrate Mass Comparison

            </div>


            <div class="card-body-lab">

                <div class="chart-container">

                    <canvas
                        id="filtrateComparisonChart"
                    ></canvas>

                </div>

            </div>

        </div>



        <!-- Retained Mass Graph -->

        <div class="lab-card">

            <div class="card-header-lab">

                <i class="bi bi-graph-up-arrow me-2"></i>

                Retained Solid Mass Comparison

            </div>


            <div class="card-body-lab">

                <div class="chart-container">

                    <canvas
                        id="retainedComparisonChart"
                    ></canvas>

                </div>

            </div>

        </div>



        <!-- Percentage Comparison -->

        <div class="lab-card">

            <div class="card-header-lab">

                <i class="bi bi-percent me-2"></i>

                Separation & Retention Comparison

            </div>


            <div class="card-body-lab">

                <div class="chart-container">

                    <canvas
                        id="percentageComparisonChart"
                    ></canvas>

                </div>

            </div>

        </div>


    </div>

<?php endif; ?>


</main>



<script>

/* =========================================================
   DATA FROM PHP
========================================================= */

const simulationRecords =
    <?= json_encode(
        $records,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    ) ?>;


/* =========================================================
   CHART VARIABLES
========================================================= */

let filtrateComparisonChart = null;

let retainedComparisonChart = null;

let percentageComparisonChart = null;


/* =========================================================
   ELEMENTS
========================================================= */

const checkboxes =
    document.querySelectorAll(
        ".simulation-checkbox"
    );

const compareBtn =
    document.getElementById(
        "compareBtn"
    );

const clearSelectionBtn =
    document.getElementById(
        "clearSelectionBtn"
    );

const selectionCount =
    document.getElementById(
        "selectionCount"
    );

const comparisonSection =
    document.getElementById(
        "comparisonSection"
    );

const comparisonHeader =
    document.getElementById(
        "comparisonHeader"
    );

const comparisonBody =
    document.getElementById(
        "comparisonBody"
    );

const metricContainer =
    document.getElementById(
        "metricContainer"
    );


/* =========================================================
   UPDATE SELECTION COUNT
========================================================= */

function updateSelectionCount() {

    const selected =
        document.querySelectorAll(
            ".simulation-checkbox:checked"
        );

    selectionCount.textContent =
        selected.length +
        " simulation" +
        (
            selected.length === 1
                ? ""
                : "s"
        ) +
        " selected";
}


checkboxes.forEach(function (checkbox) {

    checkbox.addEventListener(
        "change",
        updateSelectionCount
    );

});


/* =========================================================
   GET SELECTED RECORDS
========================================================= */

function getSelectedRecords() {

    const selectedIds =
        Array.from(
            document.querySelectorAll(
                ".simulation-checkbox:checked"
            )
        ).map(function (checkbox) {

            return Number(
                checkbox.value
            );

        });


    return simulationRecords.filter(
        function (record) {

            return selectedIds.includes(
                Number(record.id)
            );

        }
    );
}


/* =========================================================
   CLEAR SELECTION
========================================================= */

clearSelectionBtn.addEventListener(
    "click",
    function () {

        checkboxes.forEach(
            function (checkbox) {

                checkbox.checked = false;

            }
        );


        updateSelectionCount();


        comparisonSection.style.display =
            "none";

    }
);


/* =========================================================
   COMPARE
========================================================= */

compareBtn.addEventListener(
    "click",
    function () {

        const selected =
            getSelectedRecords();


        if (selected.length < 2) {

            alert(
                "Please select at least two simulations to compare."
            );

            return;
        }


        buildComparison(
            selected
        );

    }
);


/* =========================================================
   BUILD COMPARISON
========================================================= */

function buildComparison(
    records
) {

    comparisonSection.style.display =
        "block";


    buildMetrics(records);

    buildTable(records);

    buildCharts(records);


    comparisonSection.scrollIntoView({
        behavior: "smooth",
        block: "start"
    });

}


/* =========================================================
   METRIC CARDS
========================================================= */

function buildMetrics(
    records
) {

    let html = "";


    records.forEach(
        function (record, index) {

            html += `

                <div class="col-md-6 col-lg-4">

                    <div class="metric-card">

                        <div class="metric-title">

                            Simulation ${index + 1}

                        </div>

                        <div class="metric-value">

                            ${Number(
                                record.separationPercentage
                            ).toFixed(2)}%

                        </div>

                        <small class="text-muted">

                            Separation efficiency

                        </small>

                        <hr>

                        <div>

                            <strong>

                                ${Number(
                                    record.filtrateMass
                                ).toFixed(2)} g

                            </strong>

                            filtrate

                        </div>

                        <div>

                            <strong>

                                ${Number(
                                    record.retainedMass
                                ).toFixed(2)} g

                            </strong>

                            retained

                        </div>

                        <div>

                            <strong>

                                ${Number(
                                    record.filtrationTime
                                ).toFixed(2)} s

                            </strong>

                            filtration time

                        </div>

                    </div>

                </div>

            `;

        }
    );


    metricContainer.innerHTML =
        html;
}


/* =========================================================
   COMPARISON TABLE
========================================================= */

function buildTable(
    records
) {

    let headerHtml =
        "<th>Parameter</th>";


    records.forEach(
        function (record, index) {

            headerHtml += `

                <th>
                    Simulation ${index + 1}
                    <br>

                    <small class="text-muted">

                        ${formatDate(
                            record.created_at
                        )}

                    </small>

                </th>

            `;

        }
    );


    comparisonHeader.innerHTML =
        headerHtml;


    const rows = [

        {
            label: "Initial Mass",
            key: "initialMass",
            unit: "g"
        },

        {
            label: "Retained Solid Mass",
            key: "retainedMass",
            unit: "g"
        },

        {
            label: "Filtrate Mass",
            key: "filtrateMass",
            unit: "g"
        },

        {
            label: "Retention Percentage",
            key: "retentionPercentage",
            unit: "%"
        },

        {
            label: "Separation Percentage",
            key: "separationPercentage",
            unit: "%"
        },

        {
            label: "Filtration Time",
            key: "filtrationTime",
            unit: "s"
        }

    ];


    let bodyHtml = "";


    rows.forEach(
        function (row) {

            bodyHtml += `

                <tr>

                    <th>
                        ${row.label}
                    </th>

            `;


            records.forEach(
                function (record) {

                    bodyHtml += `

                        <td>

                            <span class="result-highlight">

                                ${Number(
                                    record[row.key]
                                ).toFixed(2)}

                            </span>

                            ${row.unit}

                        </td>

                    `;

                }
            );


            bodyHtml += "</tr>";

        }
    );


    comparisonBody.innerHTML =
        bodyHtml;
}


/* =========================================================
   BUILD CHARTS
========================================================= */

function buildCharts(
    records
) {

    if (filtrateComparisonChart) {

        filtrateComparisonChart.destroy();

    }


    if (retainedComparisonChart) {

        retainedComparisonChart.destroy();

    }


    if (percentageComparisonChart) {

        percentageComparisonChart.destroy();

    }


    const colors = [
        "#1f5f75",
        "#d97706",
        "#198754",
        "#6f42c1",
        "#dc3545",
        "#0d6efd"
    ];


    /* =====================================================
       FILTRATE GRAPH
    ===================================================== */

    const filtrateDatasets =
        records.map(
            function (record, index) {

                return {

                    label:
                        "Simulation " +
                        (index + 1),

                    data:
                        record.filtrateValues,

                    borderColor:
                        colors[
                            index %
                            colors.length
                        ],

                    backgroundColor:
                        colors[
                            index %
                            colors.length
                        ],

                    borderWidth: 2,

                    tension: .25,

                    fill: false
                };

            }
        );


    filtrateComparisonChart =
        new Chart(
            document.getElementById(
                "filtrateComparisonChart"
            ),
            {

                type: "line",

                data: {

                    labels:
                        getCommonLabels(
                            records
                        ),

                    datasets:
                        filtrateDatasets

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

                        }

                    },

                    scales: {

                        x: {

                            title: {

                                display: true,

                                text:
                                    "Filtration Time (seconds)"

                            }

                        },

                        y: {

                            beginAtZero: true,

                            title: {

                                display: true,

                                text:
                                    "Filtrate Mass (g)"

                            }

                        }

                    }

                }

            }
        );


    /* =====================================================
       RETAINED GRAPH
    ===================================================== */

    const retainedDatasets =
        records.map(
            function (record, index) {

                return {

                    label:
                        "Simulation " +
                        (index + 1),

                    data:
                        record.retainedValues,

                    borderColor:
                        colors[
                            index %
                            colors.length
                        ],

                    backgroundColor:
                        colors[
                            index %
                            colors.length
                        ],

                    borderWidth: 2,

                    tension: .25,

                    fill: false

                };

            }
        );


    retainedComparisonChart =
        new Chart(
            document.getElementById(
                "retainedComparisonChart"
            ),
            {

                type: "line",

                data: {

                    labels:
                        getCommonLabels(
                            records
                        ),

                    datasets:
                        retainedDatasets

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

                        }

                    },

                    scales: {

                        x: {

                            title: {

                                display: true,

                                text:
                                    "Filtration Time (seconds)"

                            }

                        },

                        y: {

                            beginAtZero: true,

                            title: {

                                display: true,

                                text:
                                    "Retained Solid Mass (g)"

                            }

                        }

                    }

                }

            }
        );


    /* =====================================================
       PERCENTAGE GRAPH
    ===================================================== */

    percentageComparisonChart =
        new Chart(
            document.getElementById(
                "percentageComparisonChart"
            ),
            {

                type: "bar",

                data: {

                    labels: records.map(
                        function (
                            record,
                            index
                        ) {

                            return "Simulation " +
                                (index + 1);

                        }
                    ),

                    datasets: [

                        {

                            label:
                                "Separation Percentage",

                            data:
                                records.map(
                                    function (
                                        record
                                    ) {

                                        return Number(
                                            record.separationPercentage
                                        );

                                    }
                                ),

                            borderWidth: 1

                        },

                        {

                            label:
                                "Retention Percentage",

                            data:
                                records.map(
                                    function (
                                        record
                                    ) {

                                        return Number(
                                            record.retentionPercentage
                                        );

                                    }
                                ),

                            borderWidth: 1

                        }

                    ]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    plugins: {

                        legend: {

                            display: true

                        }

                    },

                    scales: {

                        y: {

                            beginAtZero: true,

                            max: 100,

                            title: {

                                display: true,

                                text:
                                    "Percentage (%)"

                            }

                        }

                    }

                }

            }
        );

}


/* =========================================================
   COMMON GRAPH LABELS
========================================================= */

function getCommonLabels(
    records
) {

    if (
        records.length === 0
    ) {

        return [];

    }


    return records[0].labels || [];
}


/* =========================================================
   DATE FORMAT
========================================================= */

function formatDate(
    dateString
) {

    const date =
        new Date(
            dateString.replace(
                " ",
                "T"
            )
        );


    if (
        Number.isNaN(
            date.getTime()
        )
    ) {

        return dateString;

    }


    return date.toLocaleString(
        "en-GB",
        {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        }
    );
}


/* =========================================================
   INITIALIZE
========================================================= */

updateSelectionCount();

</script>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>