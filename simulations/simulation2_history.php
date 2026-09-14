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
   LOAD SAVED SIMULATION RESULTS
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
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$records = [];

while ($row = $result->fetch_assoc()) {

    $input = json_decode(
        $row['input_data'],
        true
    );

    $output = json_decode(
        $row['result_data'],
        true
    );

    $records[] = [
        "id" => $row['id'],
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
        Practical 2 Results History
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


    <!-- SYSTEM STYLE -->

    <style>

        :root {

            --lab-blue: #1f5f75;
            --lab-blue-dark: #17485a;
            --lab-bg: #f4f6f8;
            --lab-border: #d9dee3;
            --lab-text: #243746;
            --lab-muted: #667783;

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

            background:
                var(--lab-blue);

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
           PAGE HEADER
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

        .history-card {

            background: #ffffff;

            border:
                1px solid var(--lab-border);

            border-radius: 10px;

            box-shadow:
                0 2px 8px
                rgba(31, 55, 68, 0.04);

        }


        .history-card .card-body {

            padding: 24px;

        }


        /* =====================================================
           SUMMARY BOXES
        ===================================================== */

        .stat-box {

            background: #ffffff;

            border:
                1px solid var(--lab-border);

            border-left:
                4px solid var(--lab-blue);

            border-radius: 8px;

            padding: 17px;

            height: 100%;

        }


        .stat-box small {

            color: var(--lab-muted);

        }


        .stat-value {

            color: var(--lab-blue);

            font-size: 1.5rem;

            font-weight: 700;

            margin-top: 4px;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .table {

            margin-bottom: 0;

        }


        .table thead th {

            background: #eef3f5;

            color: var(--lab-text);

            border-bottom:
                2px solid var(--lab-border);

            white-space: nowrap;

            font-size: 0.88rem;

        }


        .table tbody td {

            vertical-align: middle;

            font-size: 0.9rem;

        }


        .table-hover tbody tr:hover {

            background: #f7fafb;

        }


        .trial-number {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 32px;

            height: 28px;

            padding: 0 8px;

            border-radius: 6px;

            background: #e8f0f3;

            color: var(--lab-blue);

            font-weight: 700;

        }


        /* =====================================================
           SECTION
        ===================================================== */

        .section-title {

            color: var(--lab-blue);

            font-weight: 700;

        }


        .info-box {

            background: #eef5f7;

            border-left:
                4px solid var(--lab-blue);

            border-radius: 8px;

            padding: 16px;

        }


        /* =====================================================
           EMPTY STATE
        ===================================================== */

        .empty-state {

            padding: 65px 20px;

            text-align: center;

        }


        .empty-icon {

            width: 72px;

            height: 72px;

            margin: auto;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #e8f0f3;

            color: var(--lab-blue);

            font-size: 32px;

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
           PRINT
        ===================================================== */

        @media print {

            .no-print {

                display: none !important;

            }


            body {

                background: #ffffff !important;

            }


            .history-card {

                box-shadow: none !important;

                border:
                    1px solid #cccccc;

            }


            .system-header {

                position: static;

                box-shadow: none;

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


            .history-card .card-body {

                padding: 18px;

            }


            .stat-box {

                padding: 14px;

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
                            class="bi bi-clock-history me-2"
                        ></i>

                        Simulation Results History

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
     MAIN
========================================================= -->

<main class="container py-4">


<!-- =========================================================
     SUMMARY
========================================================= -->

<div class="row g-3 mb-4">


    <div class="col-md-4">

        <div class="stat-box">

            <small>

                Total Saved Trials

            </small>


            <div class="stat-value">

                <?= count($records) ?>

            </div>

        </div>

    </div>


    <div class="col-md-4">

        <div class="stat-box">

            <small>

                Practical

            </small>


            <div class="fw-bold mt-1">

                Physical Separation

            </div>


            <div class="small text-muted">

                Centrifugation & Sieve Analysis

            </div>

        </div>

    </div>


    <div class="col-md-4">

        <div class="stat-box">

            <small>

                Student

            </small>


            <div
                class="fw-bold mt-1 text-truncate"
                title="<?= htmlspecialchars($user_name) ?>"
            >

                <?= htmlspecialchars($user_name) ?>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     HISTORY CARD
========================================================= -->

<div class="history-card">

    <div class="card-body">


        <div
            class="
                d-flex
                justify-content-between
                align-items-start
                flex-wrap
                gap-3
                mb-4
            "
        >

            <div>

                <h3 class="section-title mb-1">

                    <i class="bi bi-database me-1"></i>

                    Saved Simulation Trials

                </h3>


                <p class="text-muted mb-0">

                    Review the results of your previous
                    Practical 2 simulations.

                </p>

            </div>


            <div
                class="
                    d-flex
                    gap-2
                    flex-wrap
                    no-print
                "
            >

                <a
                    href="simulation2_compare.php"
                    class="btn btn-primary"
                >

                    <i class="bi bi-bar-chart-line me-1"></i>

                    Compare Trials

                </a>


                <button
                    onclick="window.print()"
                    class="btn btn-outline-secondary"
                >

                    <i class="bi bi-printer me-1"></i>

                    Print History

                </button>

            </div>

        </div>


        <?php if (count($records) === 0): ?>


            <!-- =================================================
                 EMPTY STATE
            ================================================= -->

            <div class="empty-state">


                <div class="empty-icon">

                    <i class="bi bi-clock-history"></i>

                </div>


                <h4 class="mt-4 mb-2">

                    No Saved Results Yet

                </h4>


                <p class="text-muted mb-4">

                    Run a Practical 2 simulation and save
                    the results to create your first trial.

                </p>


                <a
                    href="simulation2.php"
                    class="btn btn-primary"
                >

                    <i class="bi bi-play-circle me-1"></i>

                    Run Simulation

                </a>


            </div>


        <?php else: ?>


            <!-- =================================================
                 TABLE
            ================================================= -->

            <div class="table-responsive">

                <table
                    class="
                        table
                        table-bordered
                        table-hover
                        align-middle
                    "
                >

                    <thead>

                        <tr>

                            <th>
                                Trial
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                RPM
                            </th>

                            <th>
                                Radius
                            </th>

                            <th>
                                Time
                            </th>

                            <th>
                                RCF
                            </th>

                            <th>
                                Separation
                            </th>

                            <th>
                                Sample Mass
                            </th>

                            <th>
                                Mass Balance
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($records as $index => $record): ?>


                        <?php

                        $input =
                            $record['input'];

                        $output =
                            $record['output'];

                        ?>


                        <tr>


                            <td>

                                <span class="trial-number">

                                    <?= count($records) - $index ?>

                                </span>

                            </td>


                            <td>

                                <span class="small">

                                    <?= htmlspecialchars(
                                        $record['created_at']
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?= number_format(
                                    (float) (
                                        $input['rpm'] ?? 0
                                    ),
                                    0
                                ) ?>

                            </td>


                            <td>

                                <?= number_format(
                                    (float) (
                                        $input['radius'] ?? 0
                                    ),
                                    2
                                ) ?>

                                cm

                            </td>


                            <td>

                                <?= number_format(
                                    (float) (
                                        $input['time'] ?? 0
                                    ),
                                    0
                                ) ?>

                                min

                            </td>


                            <td class="fw-semibold">

                                <?= number_format(
                                    (float) (
                                        $output['rcf'] ?? 0
                                    ),
                                    2
                                ) ?>

                            </td>


                            <td class="fw-semibold">

                                <?= number_format(
                                    (float) (
                                        $output['separationIndex'] ?? 0
                                    ),
                                    2
                                ) ?>%

                            </td>


                            <td>

                                <?= number_format(
                                    (float) (
                                        $input['sampleMass'] ?? 0
                                    ),
                                    2
                                ) ?>

                                g

                            </td>


                            <td class="fw-semibold">

                                <?= number_format(
                                    (float) (
                                        $output['massBalance'] ?? 0
                                    ),
                                    2
                                ) ?>%

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


            <!-- =================================================
                 ANALYSIS
            ================================================= -->

            <div class="mt-5">


                <h4 class="section-title mb-2">

                    <i class="bi bi-graph-up-arrow me-1"></i>

                    Trial Analysis

                </h4>


                <p class="text-muted">

                    Use the saved trials above to examine how
                    different operating conditions affect the
                    simulation results.

                </p>


                <div class="info-box mt-3">


                    <div class="d-flex gap-3">

                        <i
                            class="
                                bi
                                bi-info-circle-fill
                                text-primary
                                fs-5
                            "
                        ></i>


                        <div>

                            <strong>

                                Interpretation

                            </strong>


                            <p class="mb-0 mt-1">

                                Higher RCF generally represents
                                greater centrifugal force.
                                The separation index indicates
                                the simulated separation tendency
                                under the selected conditions.
                                The mass balance shows how closely
                                the recovered sieve masses match
                                the original sample mass.

                            </p>

                        </div>

                    </div>

                </div>


            </div>


        <?php endif; ?>


    </div>

</div>


<!-- =========================================================
     NAVIGATION
========================================================= -->

<div
    class="
        d-flex
        gap-2
        flex-wrap
        mt-4
        no-print
    "
>


    <a
        href="../practical/practical2.php"
        class="btn btn-outline-primary"
    >

        <i class="bi bi-arrow-left me-1"></i>

        Back to Practical 2

    </a>


    <a
        href="simulation2.php"
        class="btn btn-primary"
    >

        <i class="bi bi-play-circle me-1"></i>

        New Simulation

    </a>


    <?php if (count($records) >= 2): ?>

        <a
            href="simulation2_compare.php"
            class="btn btn-outline-primary"
        >

            <i class="bi bi-bar-chart-line me-1"></i>

            Compare Trials

        </a>

    <?php endif; ?>


</div>


</main>


<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>