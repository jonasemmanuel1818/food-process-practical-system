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


/* =========================================================
   LOAD PRACTICAL 2 SIMULATION TRIALS
========================================================= */

$stmt = $conn->prepare("
    SELECT
        id,
        input_data,
        result_data,
        created_at
    FROM simulation_results
    WHERE user_id = ?
      AND practical_number = 2
    ORDER BY created_at ASC
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

    <title>
        Practical 2 Trial Comparison
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

            --lab-bg: #f4f6f8;

            --lab-border: #d9dee3;

            --lab-text: #243746;

            --lab-muted: #667783;

            --lab-light: #eef5f7;

        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            background: var(--lab-bg);

            color: var(--lab-text);

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            line-height: 1.6;

        }


        /* =====================================================
           SYSTEM HEADER
        ===================================================== */

        .system-header {

            position: sticky;

            top: 0;

            z-index: 1030;

            background: #ffffff;

            border-bottom:
                1px solid var(--lab-border);

            box-shadow:
                0 2px 8px
                rgba(31, 95, 117, 0.08);

        }


        .system-header-inner {

            min-height: 72px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding: 12px 0;

        }


        .brand-area {

            display: flex;

            align-items: center;

            gap: 12px;

            min-width: 0;

        }


        .brand-icon {

            width: 42px;

            height: 42px;

            flex: 0 0 42px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 10px;

            background: var(--lab-blue);

            color: #ffffff;

            font-size: 21px;

        }


        .brand-title {

            margin: 0;

            color: var(--lab-blue);

            font-size: 1.05rem;

            font-weight: 700;

            line-height: 1.2;

        }


        .brand-subtitle {

            margin: 2px 0 0;

            color: var(--lab-muted);

            font-size: 0.78rem;

        }


        .student-area {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .student-avatar {

            width: 38px;

            height: 38px;

            border-radius: 50%;

            background: #e8f0f3;

            color: var(--lab-blue);

            display: inline-flex;

            align-items: center;

            justify-content: center;

            font-weight: 700;

        }


        .student-name {

            max-width: 220px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            font-size: 0.9rem;

            font-weight: 600;

        }


        /* =====================================================
           PAGE HEADING
        ===================================================== */

        .page-heading {

            background: #ffffff;

            border-bottom:
                1px solid var(--lab-border);

        }


        .page-heading-inner {

            padding: 24px 0;

        }


        .page-kicker {

            color: var(--lab-blue);

            font-size: 0.78rem;

            font-weight: 700;

            letter-spacing: 0.05em;

            text-transform: uppercase;

            margin-bottom: 5px;

        }


        .page-heading h1 {

            color: var(--lab-text);

            font-size:
                clamp(1.5rem, 3vw, 2rem);

            font-weight: 700;

            margin: 0;

        }


        .page-heading p {

            color: var(--lab-muted);

            margin: 5px 0 0;

        }


        /* =====================================================
           CARDS
        ===================================================== */

        .system-card {

            background: #ffffff;

            border:
                1px solid var(--lab-border);

            border-radius: 10px;

            box-shadow:
                0 2px 8px
                rgba(31, 55, 68, 0.04);

            margin-bottom: 20px;

        }


        .system-card-body {

            padding: 24px;

        }


        /* =====================================================
           SECTION TITLES
        ===================================================== */

        .section-title {

            color: var(--lab-blue);

            font-size: 1.2rem;

            font-weight: 700;

            margin-bottom: 8px;

        }


        .section-description {

            color: var(--lab-muted);

            margin-bottom: 20px;

        }


        /* =====================================================
           TRIAL SELECTION
        ===================================================== */

        .trial-box {

            border:
                1px solid var(--lab-border);

            border-radius: 8px;

            padding: 18px;

            background: #fafbfc;

        }


        .trial-label {

            color: var(--lab-blue);

            font-weight: 700;

            margin-bottom: 8px;

        }


        .form-select {

            border-color: var(--lab-border);

            min-height: 44px;

        }


        .form-select:focus {

            border-color: var(--lab-blue);

            box-shadow:
                0 0 0 0.2rem
                rgba(31, 95, 117, 0.12);

        }


        /* =====================================================
           BUTTONS
        ===================================================== */

        .btn-primary {

            background: var(--lab-blue);

            border-color: var(--lab-blue);

        }


        .btn-primary:hover,
        .btn-primary:focus {

            background: var(--lab-blue-dark);

            border-color: var(--lab-blue-dark);

        }


        .btn-outline-primary {

            color: var(--lab-blue);

            border-color: var(--lab-blue);

        }


        .btn-outline-primary:hover {

            background: var(--lab-blue);

            border-color: var(--lab-blue);

        }


        /* =====================================================
           COMPARISON TABLE
        ===================================================== */

        .comparison-table {

            margin-bottom: 0;

        }


        .comparison-table thead th {

            background: #eef3f5;

            color: var(--lab-text);

            border-bottom:
                2px solid var(--lab-border);

            white-space: nowrap;

            font-size: 0.9rem;

        }


        .comparison-table tbody td {

            vertical-align: middle;

            font-size: 0.9rem;

        }


        .comparison-table tbody tr:hover {

            background: #f8fafb;

        }


        .parameter-name {

            font-weight: 600;

            color: var(--lab-text);

        }


        .trial-heading {

            color: var(--lab-blue);

            font-weight: 700;

        }


        .difference-cell {

            font-weight: 600;

        }


        /* =====================================================
           ANALYSIS
        ===================================================== */

        .analysis-result {

            background: var(--lab-light);

            border-left:
                4px solid var(--lab-blue);

            border-radius: 8px;

            padding: 18px;

            color: var(--lab-text);

        }


        .metric-card {

            height: 100%;

            background: #ffffff;

            border:
                1px solid var(--lab-border);

            border-radius: 8px;

            padding: 18px;

        }


        .metric-icon {

            width: 38px;

            height: 38px;

            border-radius: 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            background: #e8f0f3;

            color: var(--lab-blue);

            margin-bottom: 12px;

        }


        .metric-label {

            color: var(--lab-muted);

            font-size: 0.85rem;

            margin-bottom: 4px;

        }


        .metric-value {

            color: var(--lab-blue);

            font-size: 1.2rem;

            font-weight: 700;

        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {

            text-align: center;

            padding: 65px 20px;

        }


        .empty-icon {

            width: 74px;

            height: 74px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            background: #e8f0f3;

            color: var(--lab-blue);

            border-radius: 50%;

            font-size: 32px;

        }


        /* =====================================================
           FOOTER NAVIGATION
        ===================================================== */

        .bottom-navigation {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 12px;

            flex-wrap: wrap;

            margin-top: 5px;

            margin-bottom: 30px;

        }


        /* =====================================================
           PRINT
        ===================================================== */

        @media print {

            .no-print {

                display: none !important;

            }


            body {

                background: #ffffff !important;

            }


            .system-header {

                position: static;

                box-shadow: none;

            }


            .system-card {

                box-shadow: none !important;

                border:
                    1px solid #cccccc;

                page-break-inside: avoid;

            }


            .page-heading {

                border-bottom:
                    1px solid #cccccc;

            }

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 767.98px) {

            .system-header-inner {

                min-height: 64px;

            }


            .brand-subtitle,
            .student-name {

                display: none;

            }


            .page-heading-inner {

                padding: 18px 0;

            }


            .system-card-body {

                padding: 18px;

            }


            .comparison-table {

                min-width: 650px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     SYSTEM HEADER
========================================================= -->

<header class="system-header no-print">

    <div class="container">

        <div class="system-header-inner">


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


                <div class="text-end d-none d-sm-block">

                    <div class="small text-muted">

                        Student

                    </div>


                    <div class="student-name">

                        <?= htmlspecialchars($user_name) ?>

                    </div>

                </div>


                <div class="student-avatar">

                    <?= strtoupper(
                        substr(
                            trim($user_name),
                            0,
                            1
                        )
                    ) ?>

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

        <div class="page-heading-inner">


            <div
                class="
                    d-flex
                    justify-content-between
                    align-items-center
                    flex-wrap
                    gap-3
                "
            >

                <div>

                    <div class="page-kicker">

                        Practical 2

                    </div>


                    <h1>

                        <i
                            class="bi bi-bar-chart-line me-2"
                        ></i>

                        Trial Comparison & Analysis

                    </h1>


                    <p>

                        Physical Separation —
                        Centrifugation and Sieve Analysis

                    </p>

                </div>


                <a
                    href="simulation2.php"
                    class="btn btn-outline-primary no-print"
                >

                    <i class="bi bi-arrow-left me-1"></i>

                    Back to Simulation

                </a>

            </div>

        </div>

    </div>

</section>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="container py-4">


<?php if (count($trials) < 2): ?>


    <!-- =====================================================
         NOT ENOUGH TRIALS
    ====================================================== -->

    <div class="system-card">

        <div class="system-card-body empty-state">


            <div class="empty-icon">

                <i class="bi bi-bar-chart-line"></i>

            </div>


            <h3 class="mt-4 mb-2">

                Not Enough Trials

            </h3>


            <p class="text-muted mb-4">

                You need at least two saved simulation
                trials before they can be compared.

            </p>


            <a
                href="simulation2.php"
                class="btn btn-primary"
            >

                <i class="bi bi-play-circle me-1"></i>

                Run Another Simulation

            </a>


        </div>

    </div>


<?php else: ?>


    <!-- =====================================================
         TRIAL SELECTION
    ====================================================== -->

    <div class="system-card">

        <div class="system-card-body">


            <h2 class="section-title">

                <i class="bi bi-check2-square me-1"></i>

                Select Trials

            </h2>


            <p class="section-description">

                Select two saved trials to compare their
                operating conditions and simulation results.

            </p>


            <div class="row g-3">


                <!-- Trial A -->

                <div class="col-lg-6">

                    <div class="trial-box">

                        <div class="trial-label">

                            <i class="bi bi-1-circle me-1"></i>

                            Trial A

                        </div>


                        <select
                            id="trialA"
                            class="form-select"
                        >

                            <?php foreach (
                                $trials
                                as $index => $trial
                            ): ?>

                                <option
                                    value="<?= $index ?>"
                                    <?= $index === max(
                                        0,
                                        count($trials) - 2
                                    )
                                        ? "selected"
                                        : "" ?>
                                >

                                    Trial <?= $index + 1 ?>

                                    —

                                    <?= htmlspecialchars(
                                        $trial['created_at']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <!-- Trial B -->

                <div class="col-lg-6">

                    <div class="trial-box">

                        <div class="trial-label">

                            <i class="bi bi-2-circle me-1"></i>

                            Trial B

                        </div>


                        <select
                            id="trialB"
                            class="form-select"
                        >

                            <?php foreach (
                                $trials
                                as $index => $trial
                            ): ?>

                                <option
                                    value="<?= $index ?>"
                                    <?= $index ===
                                        count($trials) - 1
                                        ? "selected"
                                        : "" ?>
                                >

                                    Trial <?= $index + 1 ?>

                                    —

                                    <?= htmlspecialchars(
                                        $trial['created_at']
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


            </div>


            <div class="mt-4 no-print">

                <button
                    type="button"
                    id="compareButton"
                    class="btn btn-primary"
                >

                    <i class="bi bi-bar-chart-line me-1"></i>

                    Compare Trials

                </button>

            </div>


        </div>

    </div>


    <!-- =====================================================
         COMPARISON TABLE
    ====================================================== -->

    <div
        class="system-card"
        id="comparisonCard"
    >

        <div class="system-card-body">


            <div
                class="
                    d-flex
                    justify-content-between
                    align-items-center
                    flex-wrap
                    gap-2
                    mb-3
                "
            >

                <div>

                    <h2 class="section-title mb-1">

                        <i class="bi bi-table me-1"></i>

                        Simulation Comparison

                    </h2>


                    <p class="text-muted mb-0">

                        Comparison of selected experimental
                        conditions and calculated results.

                    </p>

                </div>


                <span class="badge text-bg-light border">

                    Practical 2

                </span>

            </div>


            <div class="table-responsive">

                <table
                    class="
                        table
                        table-bordered
                        comparison-table
                        align-middle
                    "
                >

                    <thead>

                        <tr>

                            <th>
                                Parameter
                            </th>


                            <th
                                id="trialAHeading"
                                class="trial-heading"
                            >

                                Trial A

                            </th>


                            <th
                                id="trialBHeading"
                                class="trial-heading"
                            >

                                Trial B

                            </th>


                            <th>
                                Difference
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <!-- RPM -->

                        <tr>

                            <td class="parameter-name">

                                RPM

                            </td>

                            <td id="rpmA">
                                -
                            </td>

                            <td id="rpmB">
                                -
                            </td>

                            <td
                                id="rpmDiff"
                                class="difference-cell"
                            >
                                -
                            </td>

                        </tr>


                        <!-- Radius -->

                        <tr>

                            <td class="parameter-name">

                                Radius

                            </td>

                            <td id="radiusA">
                                -
                            </td>

                            <td id="radiusB">
                                -
                            </td>

                            <td
                                id="radiusDiff"
                                class="difference-cell"
                            >
                                -
                            </td>

                        </tr>


                        <!-- Time -->

                        <tr>

                            <td class="parameter-name">

                                Centrifugation Time

                            </td>

                            <td id="timeA">
                                -
                            </td>

                            <td id="timeB">
                                -
                            </td>

                            <td
                                id="timeDiff"
                                class="difference-cell"
                            >
                                -
                            </td>

                        </tr>


                        <!-- RCF -->

                        <tr>

                            <td class="parameter-name">

                                RCF

                            </td>

                            <td id="rcfA">
                                -
                            </td>

                            <td id="rcfB">
                                -
                            </td>

                            <td
                                id="rcfDiff"
                                class="difference-cell"
                            >
                                -
                            </td>

                        </tr>


                        <!-- Separation -->

                        <tr>

                            <td class="parameter-name">

                                Separation Index

                            </td>

                            <td id="separationA">
                                -
                            </td>

                            <td id="separationB">
                                -
                            </td>

                            <td
                                id="separationDiff"
                                class="difference-cell"
                            >
                                -
                            </td>

                        </tr>


                        <!-- Sample Mass -->

                        <tr>

                            <td class="parameter-name">

                                Sample Mass

                            </td>

                            <td id="sampleA">
                                -
                            </td>

                            <td id="sampleB">
                                -
                            </td>

                            <td
                                id="sampleDiff"
                                class="difference-cell"
                            >
                                -
                            </td>

                        </tr>


                        <!-- Total Retained -->

                        <tr>

                            <td class="parameter-name">

                                Total Retained

                            </td>

                            <td id="retainedA">
                                -
                            </td>

                            <td id="retainedB">
                                -
                            </td>

                            <td
                                id="retainedDiff"
                                class="difference-cell"
                            >
                                -
                            </td>

                        </tr>


                        <!-- Mass Difference -->

                        <tr>

                            <td class="parameter-name">

                                Mass Difference

                            </td>

                            <td id="massDifferenceA">
                                -
                            </td>

                            <td id="massDifferenceB">
                                -
                            </td>

                            <td
                                id="massDifferenceDiff"
                                class="difference-cell"
                            >
                                -
                            </td>

                        </tr>


                        <!-- Mass Balance -->

                        <tr>

                            <td class="parameter-name">

                                Mass Balance

                            </td>

                            <td id="balanceA">
                                -
                            </td>

                            <td id="balanceB">
                                -
                            </td>

                            <td
                                id="balanceDiff"
                                class="difference-cell"
                            >
                                -
                            </td>

                        </tr>


                    </tbody>

                </table>

            </div>


        </div>

    </div>


    <!-- =====================================================
         ANALYSIS
    ====================================================== -->

    <div class="system-card">

        <div class="system-card-body">


            <h2 class="section-title">

                <i class="bi bi-lightbulb me-1"></i>

                Analysis

            </h2>


            <div
                id="analysisResult"
                class="analysis-result"
            >

                Select two trials and click
                <strong>Compare Trials</strong>.

            </div>


            <div class="row g-3 mt-2">


                <!-- Better Separation -->

                <div class="col-md-4">

                    <div class="metric-card">

                        <div class="metric-icon">

                            <i class="bi bi-filter-circle"></i>

                        </div>


                        <div class="metric-label">

                            Better Separation

                        </div>


                        <div
                            id="betterSeparation"
                            class="metric-value"
                        >

                            -

                        </div>

                    </div>

                </div>


                <!-- Higher RCF -->

                <div class="col-md-4">

                    <div class="metric-card">

                        <div class="metric-icon">

                            <i class="bi bi-speedometer2"></i>

                        </div>


                        <div class="metric-label">

                            Higher RCF

                        </div>


                        <div
                            id="higherRCF"
                            class="metric-value"
                        >

                            -

                        </div>

                    </div>

                </div>


                <!-- Better Balance -->

                <div class="col-md-4">

                    <div class="metric-card">

                        <div class="metric-icon">

                            <i class="bi bi-check-circle"></i>

                        </div>


                        <div class="metric-label">

                            Better Mass Balance

                        </div>


                        <div
                            id="betterBalance"
                            class="metric-value"
                        >

                            -

                        </div>

                    </div>

                </div>


            </div>


            <div class="mt-4 no-print">

                <button
                    type="button"
                    onclick="window.print()"
                    class="btn btn-dark"
                >

                    <i class="bi bi-printer me-1"></i>

                    Print Comparison

                </button>

            </div>


        </div>

    </div>


<?php endif; ?>


<!-- =========================================================
     BOTTOM NAVIGATION
========================================================= -->

<div class="bottom-navigation no-print">


    <div class="d-flex gap-2 flex-wrap">

        <a
            href="simulation2_history.php"
            class="btn btn-outline-primary"
        >

            <i class="bi bi-clock-history me-1"></i>

            Results History

        </a>


        <a
            href="../practical/practical2.php"
            class="btn btn-outline-primary"
        >

            <i class="bi bi-arrow-left me-1"></i>

            Practical 2

        </a>

    </div>


    <a
        href="simulation2.php"
        class="btn btn-primary"
    >

        <i class="bi bi-play-circle me-1"></i>

        New Simulation

    </a>


</div>


</main>


<!-- =========================================================
     COMPARISON JAVASCRIPT
========================================================= -->

<?php if (count($trials) >= 2): ?>

<script>

const trials =
    <?= json_encode(
        $trials,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    ) ?>;


const trialASelect =
    document.getElementById("trialA");


const trialBSelect =
    document.getElementById("trialB");


const compareButton =
    document.getElementById("compareButton");


/* =========================================================
   NUMBER HELPER
========================================================= */

function number(value) {

    const n =
        parseFloat(value);

    return Number.isFinite(n)
        ? n
        : 0;

}


/* =========================================================
   FORMAT
========================================================= */

function format(
    value,
    decimals = 2
) {

    return number(value)
        .toFixed(decimals);

}


/* =========================================================
   DIFFERENCE
========================================================= */

function difference(a, b) {

    return b - a;

}


/* =========================================================
   COMPARE TRIALS
========================================================= */

function compareTrials() {


    let indexA =
        parseInt(
            trialASelect.value
        );


    let indexB =
        parseInt(
            trialBSelect.value
        );


    if (indexA === indexB) {

        alert(
            "Please select two different trials."
        );

        return;

    }


    const trialA =
        trials[indexA];


    const trialB =
        trials[indexB];


    const inputA =
        trialA.input || {};


    const inputB =
        trialB.input || {};


    const outputA =
        trialA.output || {};


    const outputB =
        trialB.output || {};


    /* =====================================================
       HEADINGS
    ===================================================== */

    document.getElementById(
        "trialAHeading"
    ).textContent =
        "Trial " + (indexA + 1);


    document.getElementById(
        "trialBHeading"
    ).textContent =
        "Trial " + (indexB + 1);


    /* =====================================================
       RPM
    ===================================================== */

    const rpmA =
        number(inputA.rpm);


    const rpmB =
        number(inputB.rpm);


    document.getElementById(
        "rpmA"
    ).textContent =
        format(rpmA, 0);


    document.getElementById(
        "rpmB"
    ).textContent =
        format(rpmB, 0);


    document.getElementById(
        "rpmDiff"
    ).textContent =
        format(
            difference(
                rpmA,
                rpmB
            ),
            0
        );


    /* =====================================================
       RADIUS
    ===================================================== */

    const radiusA =
        number(inputA.radius);


    const radiusB =
        number(inputB.radius);


    document.getElementById(
        "radiusA"
    ).textContent =
        format(radiusA) +
        " cm";


    document.getElementById(
        "radiusB"
    ).textContent =
        format(radiusB) +
        " cm";


    document.getElementById(
        "radiusDiff"
    ).textContent =
        format(
            difference(
                radiusA,
                radiusB
            )
        ) +
        " cm";


    /* =====================================================
       TIME
    ===================================================== */

    const timeA =
        number(inputA.time);


    const timeB =
        number(inputB.time);


    document.getElementById(
        "timeA"
    ).textContent =
        format(timeA, 0) +
        " min";


    document.getElementById(
        "timeB"
    ).textContent =
        format(timeB, 0) +
        " min";


    document.getElementById(
        "timeDiff"
    ).textContent =
        format(
            difference(
                timeA,
                timeB
            ),
            0
        ) +
        " min";


    /* =====================================================
       RCF
    ===================================================== */

    const rcfA =
        number(outputA.rcf);


    const rcfB =
        number(outputB.rcf);


    document.getElementById(
        "rcfA"
    ).textContent =
        format(rcfA);


    document.getElementById(
        "rcfB"
    ).textContent =
        format(rcfB);


    document.getElementById(
        "rcfDiff"
    ).textContent =
        format(
            difference(
                rcfA,
                rcfB
            )
        );


    /* =====================================================
       SEPARATION INDEX
    ===================================================== */

    const separationA =
        number(
            outputA.separationIndex
        );


    const separationB =
        number(
            outputB.separationIndex
        );


    document.getElementById(
        "separationA"
    ).textContent =
        format(separationA) +
        "%";


    document.getElementById(
        "separationB"
    ).textContent =
        format(separationB) +
        "%";


    document.getElementById(
        "separationDiff"
    ).textContent =
        format(
            difference(
                separationA,
                separationB
            )
        ) +
        "%";


    /* =====================================================
       SAMPLE MASS
    ===================================================== */

    const sampleA =
        number(
            inputA.sampleMass
        );


    const sampleB =
        number(
            inputB.sampleMass
        );


    document.getElementById(
        "sampleA"
    ).textContent =
        format(sampleA) +
        " g";


    document.getElementById(
        "sampleB"
    ).textContent =
        format(sampleB) +
        " g";


    document.getElementById(
        "sampleDiff"
    ).textContent =
        format(
            difference(
                sampleA,
                sampleB
            )
        ) +
        " g";


    /* =====================================================
       TOTAL RETAINED
    ===================================================== */

    const retainedA =
        number(
            outputA.totalRetained
        );


    const retainedB =
        number(
            outputB.totalRetained
        );


    document.getElementById(
        "retainedA"
    ).textContent =
        format(retainedA) +
        " g";


    document.getElementById(
        "retainedB"
    ).textContent =
        format(retainedB) +
        " g";


    document.getElementById(
        "retainedDiff"
    ).textContent =
        format(
            difference(
                retainedA,
                retainedB
            )
        ) +
        " g";


    /* =====================================================
       MASS DIFFERENCE
    ===================================================== */

    const massDifferenceA =
        number(
            outputA.massDifference
        );


    const massDifferenceB =
        number(
            outputB.massDifference
        );


    document.getElementById(
        "massDifferenceA"
    ).textContent =
        format(
            massDifferenceA
        ) +
        " g";


    document.getElementById(
        "massDifferenceB"
    ).textContent =
        format(
            massDifferenceB
        ) +
        " g";


    document.getElementById(
        "massDifferenceDiff"
    ).textContent =
        format(
            difference(
                massDifferenceA,
                massDifferenceB
            )
        ) +
        " g";


    /* =====================================================
       MASS BALANCE
    ===================================================== */

    const balanceA =
        number(
            outputA.massBalance
        );


    const balanceB =
        number(
            outputB.massBalance
        );


    document.getElementById(
        "balanceA"
    ).textContent =
        format(balanceA) +
        "%";


    document.getElementById(
        "balanceB"
    ).textContent =
        format(balanceB) +
        "%";


    document.getElementById(
        "balanceDiff"
    ).textContent =
        format(
            difference(
                balanceA,
                balanceB
            )
        ) +
        "%";


    /* =====================================================
       DETERMINE BETTER RESULTS
    ===================================================== */

    const betterSeparation =
        separationA > separationB
            ? "Trial " + (indexA + 1)
            : separationB > separationA
                ? "Trial " + (indexB + 1)
                : "Equal";


    const higherRCF =
        rcfA > rcfB
            ? "Trial " + (indexA + 1)
            : rcfB > rcfA
                ? "Trial " + (indexB + 1)
                : "Equal";


    /*
     * For mass balance,
     * closer to 100% is better.
     */

    const balanceErrorA =
        Math.abs(
            100 - balanceA
        );


    const balanceErrorB =
        Math.abs(
            100 - balanceB
        );


    const betterBalance =
        balanceErrorA < balanceErrorB
            ? "Trial " + (indexA + 1)
            : balanceErrorB < balanceErrorA
                ? "Trial " + (indexB + 1)
                : "Equal";


    document.getElementById(
        "betterSeparation"
    ).textContent =
        betterSeparation;


    document.getElementById(
        "higherRCF"
    ).textContent =
        higherRCF;


    document.getElementById(
        "betterBalance"
    ).textContent =
        betterBalance;


    /* =====================================================
       GENERATE INTERPRETATION
    ===================================================== */

    let analysis = "";


    if (separationA > separationB) {

        analysis +=

            "Trial " +
            (indexA + 1) +

            " produced the higher separation index (" +

            format(separationA) +

            "% compared with " +

            format(separationB) +

            "%). ";

    }

    else if (separationB > separationA) {

        analysis +=

            "Trial " +
            (indexB + 1) +

            " produced the higher separation index (" +

            format(separationB) +

            "% compared with " +

            format(separationA) +

            "%). ";

    }

    else {

        analysis +=

            "Both trials produced the same separation index. ";

    }


    if (rcfA > rcfB) {

        analysis +=

            "Trial " +
            (indexA + 1) +

            " had the higher relative centrifugal force. ";

    }

    else if (rcfB > rcfA) {

        analysis +=

            "Trial " +
            (indexB + 1) +

            " had the higher relative centrifugal force. ";

    }


    if (balanceErrorA < balanceErrorB) {

        analysis +=

            "Trial " +
            (indexA + 1) +

            " had the mass balance closer to 100%, indicating a closer agreement between the sample mass and retained mass. ";

    }

    else if (balanceErrorB < balanceErrorA) {

        analysis +=

            "Trial " +
            (indexB + 1) +

            " had the mass balance closer to 100%, indicating a closer agreement between the sample mass and retained mass. ";

    }

    else {

        analysis +=

            "Both trials had the same mass-balance accuracy. ";

    }


    document.getElementById(
        "analysisResult"
    ).innerHTML =

        "<strong>Interpretation:</strong> " +
        analysis;

}


/* =========================================================
   BUTTON
========================================================= */

compareButton.addEventListener(
    "click",
    compareTrials
);


/* =========================================================
   DEFAULT COMPARISON
========================================================= */

compareTrials();

</script>

<?php endif; ?>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>