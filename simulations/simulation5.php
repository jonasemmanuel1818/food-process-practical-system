<?php

require_once "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? "Student";

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
        Practical 5 Simulation | Filtration and Separation
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
            --lab-dark: #17495a;
            --lab-light: #eef7fa;

            --page-bg: #f4f6f8;

            --border: #d9dee3;

            --text: #263238;

            --muted: #6b7280;

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
                Arial,
                sans-serif;

        }


        /* =========================================================
           TOP BAR
        ========================================================= */

        .topbar {

            background: #ffffff;

            border-bottom:
                1px solid var(--border);

            min-height: 70px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                0 28px;

        }


        .brand {

            display: flex;

            align-items: center;

            gap: 11px;

            color: var(--lab-dark);

            font-weight: 700;

        }


        .brand-icon {

            width: 38px;

            height: 38px;

            border-radius: 8px;

            background:
                var(--lab-light);

            color:
                var(--lab-blue);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;

        }


        .student-area {

            display: flex;

            align-items: center;

            gap: 10px;

            color: #52606d;

            font-size: 14px;

        }


        .student-avatar {

            width: 38px;

            height: 38px;

            border-radius: 50%;

            background:
                var(--lab-blue);

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: 600;

        }


        /* =========================================================
           PAGE
        ========================================================= */

        .page-wrapper {

            max-width: 1400px;

            margin: 0 auto;

            padding: 28px;

        }


        .back-link {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            color:
                var(--lab-blue);

            text-decoration: none;

            font-size: 14px;

            font-weight: 600;

            margin-bottom: 18px;

        }


        .back-link:hover {

            color:
                var(--lab-dark);

        }


        .page-title {

            font-size: 30px;

            font-weight: 700;

            color:
                var(--lab-dark);

            margin-bottom: 7px;

        }


        .page-subtitle {

            color:
                var(--muted);

            margin-bottom: 25px;

        }


        /* =========================================================
           CARDS
        ========================================================= */

        .lab-card {

            background: #ffffff;

            border:
                1px solid var(--border);

            border-radius: 12px;

            overflow: hidden;

        }


        .card-header-custom {

            padding:
                18px 20px;

            border-bottom:
                1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

        }


        .card-header-custom h5 {

            margin: 0;

            color:
                var(--lab-dark);

            font-size: 17px;

            font-weight: 700;

        }


        .card-body-custom {

            padding: 20px;

        }


        /* =========================================================
           INSTRUCTIONS
        ========================================================= */

        .instruction-box {

            background:
                var(--lab-light);

            border:
                1px solid #cfe4ea;

            border-radius: 9px;

            padding: 16px;

            margin-bottom: 22px;

        }


        .instruction-box strong {

            color:
                var(--lab-dark);

        }


        .instruction-list {

            margin:
                10px 0 0;

            padding-left: 20px;

            color:
                #52606d;

        }


        .instruction-list li {

            margin-bottom: 5px;

        }


        /* =========================================================
           LAB WORKSPACE
        ========================================================= */

        .lab-workspace {

            position: relative;

            min-height: 720px;

            background:
                #fbfcfd;

            border:
                1px solid var(--border);

            border-radius: 10px;

            overflow: hidden;

        }


        .workspace-title {

            position: absolute;

            top: 18px;

            left: 20px;

            color:
                var(--lab-dark);

            font-weight: 700;

            z-index: 10;

        }


        /* =========================================================
           SAMPLE BEAKER
        ========================================================= */

        .sample-area {

            position: absolute;

            left: 9%;

            top: 31%;

            width: 170px;

            text-align: center;

        }


        .sample-beaker {

            width: 90px;

            height: 120px;

            margin: 0 auto;

            border:
                3px solid #7d8b93;

            border-top: 0;

            border-radius:
                0 0 18px 18px;

            position: relative;

            background:
                rgba(255,255,255,0.6);

            cursor: grab;

            transition:
                transform .2s ease,
                box-shadow .2s ease;

        }


        .sample-beaker::before {

            content: "";

            position: absolute;

            top: -10px;

            left: -5px;

            width: 96px;

            height: 10px;

            border:
                3px solid #7d8b93;

            border-bottom: 0;

            border-radius:
                8px 8px 0 0;

            background:
                #ffffff;

        }


        .sample-liquid {

            position: absolute;

            left: 4px;

            right: 4px;

            bottom: 4px;

            height: 75px;

            border-radius:
                0 0 12px 12px;

            background:
                #d9c79b;

            overflow: hidden;

        }


        .sample-particle {

            position: absolute;

            width: 7px;

            height: 7px;

            border-radius: 50%;

            background:
                #806f45;

        }


        .particle-1 {
            left: 18px;
            top: 20px;
        }

        .particle-2 {
            left: 45px;
            top: 40px;
        }

        .particle-3 {
            left: 28px;
            top: 57px;
        }

        .particle-4 {
            left: 62px;
            top: 30px;
        }

        .particle-5 {
            left: 50px;
            top: 63px;
        }


        .sample-beaker.dragging {

            transform:
                scale(1.05);

            box-shadow:
                0 10px 25px
                rgba(31,95,117,0.18);

        }


        /* =========================================================
           FILTER FUNNEL
        ========================================================= */

        .filter-area {

            position: absolute;

            left: 50%;

            top: 17%;

            transform:
                translateX(-50%);

            width: 230px;

            height: 370px;

            text-align: center;

        }


        .funnel {

            position: relative;

            width: 160px;

            height: 230px;

            margin:
                25px auto 0;

        }


        .funnel-top {

            width: 160px;

            height: 95px;

            border-left:
                4px solid #75838b;

            border-right:
                4px solid #75838b;

            border-bottom:
                4px solid #75838b;

            border-radius:
                0 0 80px 80px;

            position: relative;

            background:
                rgba(255,255,255,0.7);

            overflow: hidden;

        }


        .filter-paper {

            position: absolute;

            width: 112px;

            height: 58px;

            left: 20px;

            top: 13px;

            background:
                #f2ead7;

            border-radius: 50%;

            border:
                2px solid #c9baa0;

        }


        .retained-solids {

            position: absolute;

            left: 39px;

            top: 29px;

            width: 70px;

            height: 28px;

            opacity: 0;

        }


        .retained-solids span {

            position: absolute;

            width: 6px;

            height: 6px;

            border-radius: 50%;

            background:
                #806f45;

        }


        .retained-solids span:nth-child(1) {

            left: 5px;

            top: 8px;

        }


        .retained-solids span:nth-child(2) {

            left: 20px;

            top: 4px;

        }


        .retained-solids span:nth-child(3) {

            left: 36px;

            top: 10px;

        }


        .retained-solids span:nth-child(4) {

            left: 51px;

            top: 5px;

        }


        .funnel-stem {

            width: 28px;

            height: 125px;

            border-left:
                4px solid #75838b;

            border-right:
                4px solid #75838b;

            margin:
                -2px auto 0;

            background:
                rgba(255,255,255,0.6);

        }


        .filter-area.ready
        .filter-paper {

            box-shadow:
                0 0 0 3px
                rgba(31,95,117,0.15);

        }


        /* =========================================================
           RECEIVING BEAKER
           DIRECTLY BELOW FUNNEL
        ========================================================= */

        .receiver-area {

            position: absolute;

            left: 50%;

            top: 63%;

            transform:
                translateX(-50%);

            width: 170px;

            text-align: center;

        }


        .receiver-beaker {

            width: 105px;

            height: 125px;

            margin: 0 auto;

            border:
                3px solid #7d8b93;

            border-top: 0;

            border-radius:
                0 0 18px 18px;

            position: relative;

            background:
                rgba(255,255,255,0.6);

            overflow: hidden;

        }


        .receiver-beaker::before {

            content: "";

            position: absolute;

            top: -10px;

            left: -5px;

            width: 111px;

            height: 10px;

            border:
                3px solid #7d8b93;

            border-bottom: 0;

            border-radius:
                8px 8px 0 0;

            background:
                #ffffff;

        }


        .receiver-liquid {

            position: absolute;

            left: 4px;

            right: 4px;

            bottom: 4px;

            height: 0;

            background:
                #d9c79b;

            border-radius:
                0 0 12px 12px;

            transition:
                height .3s linear;

        }


        /* =========================================================
           FILTRATE DROP
        ========================================================= */

        .filtrate-drop {

            position: absolute;

            width: 9px;

            height: 14px;

            border-radius:
                50% 50% 50% 0;

            background:
                #d9c79b;

            transform:
                rotate(-45deg);

            opacity: 0;

            left: 50%;

            top: 49%;

        }


        .filtrate-drop.animate {

            animation:
                filtrationDrop
                1.1s linear infinite;

        }


        @keyframes filtrationDrop {

            0% {

                opacity: 0;

                top: 49%;

            }

            20% {

                opacity: 1;

            }

            100% {

                opacity: 1;

                top: 68%;

            }

        }


        /* =========================================================
           EQUIPMENT LABELS
        ========================================================= */

        .equipment-label {

            font-size: 12px;

            font-weight: 600;

            color:
                #52606d;

            text-align: center;

            margin-top: 8px;

        }


        .equipment-label small {

            font-weight: 400;

            color:
                #7b8794;

        }


        /* =========================================================
           CONTROLS
        ========================================================= */

        .controls {

            margin-top: 18px;

            display: flex;

            gap: 10px;

            flex-wrap: wrap;

        }


        .btn-lab {

            background:
                var(--lab-blue);

            color:
                #ffffff;

            border:
                1px solid
                var(--lab-blue);

        }


        .btn-lab:hover {

            background:
                var(--lab-dark);

            border-color:
                var(--lab-dark);

            color:
                #ffffff;

        }


        .btn-outline-lab {

            color:
                var(--lab-blue);

            border:
                1px solid
                var(--lab-blue);

            background:
                #ffffff;

        }


        .btn-outline-lab:hover {

            background:
                var(--lab-light);

            color:
                var(--lab-dark);

        }


        /* =========================================================
           STATUS
        ========================================================= */

        .status-box {

            margin-top: 15px;

            padding:
                12px 14px;

            border-radius: 8px;

            background:
                #f4f6f8;

            border:
                1px solid var(--border);

            color:
                #52606d;

            font-size: 14px;

        }


        .status-box.success {

            background:
                #edf8f1;

            border-color:
                #b9dec5;

            color:
                #28633b;

        }


        .status-box.warning {

            background:
                #fff8e8;

            border-color:
                #ead8a7;

            color:
                #725d20;

        }


        /* =========================================================
           FORM
        ========================================================= */

        .form-label {

            font-size: 13px;

            font-weight: 600;

            color:
                #374151;

        }


        .form-control {

            border-color:
                var(--border);

            box-shadow:
                none;

        }


        .form-control:focus {

            border-color:
                var(--lab-blue);

            box-shadow:
                0 0 0 3px
                rgba(31,95,117,.10);

        }


        /* =========================================================
           RESULTS
        ========================================================= */

        .result-card {

            margin-top: 22px;

        }


        .result-value {

            font-size: 24px;

            font-weight: 700;

            color:
                var(--lab-dark);

        }


        .result-label {

            color:
                var(--muted);

            font-size: 13px;

        }


        .chart-container {

            position: relative;

            height: 330px;

        }


        .saved-message {

            display: none;

            margin-top: 15px;

            padding:
                12px 14px;

            border-radius: 8px;

            background:
                #edf8f1;

            border:
                1px solid #b9dec5;

            color:
                #28633b;

        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 900px) {

            .lab-workspace {

                min-height:
                    820px;

            }


            .sample-area {

                left: 7%;

                top: 34%;

            }


            .filter-area {

                top: 14%;

            }


            .receiver-area {

                top: 62%;

            }

        }


        @media (max-width: 650px) {

            .page-wrapper {

                padding: 18px;

            }


            .topbar {

                padding:
                    0 16px;

            }


            .student-name {

                display: none;

            }


            .lab-workspace {

                min-height:
                    1000px;

            }


            .sample-area {

                left: 50%;

                transform:
                    translateX(-50%);

                top: 9%;

            }


            .filter-area {

                left: 50%;

                top: 31%;

                transform:
                    translateX(-50%);

            }


            .receiver-area {

                left: 50%;

                top: 69%;

                transform:
                    translateX(-50%);

            }


            .page-title {

                font-size: 25px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     TOPBAR
========================================================= -->

<header class="topbar">


    <div class="brand">

        <div class="brand-icon">

            <i class="bi bi-funnel"></i>

        </div>


        Food Process Practical Learning & Simulation System

    </div>



    <div class="student-area">

        <span class="student-name">

            <?= htmlspecialchars($full_name) ?>

        </span>


        <div class="student-avatar">

            <?= strtoupper(
                substr($full_name, 0, 1)
            ) ?>

        </div>

    </div>


</header>



<main class="page-wrapper">


    <!-- =====================================================
         BACK
    ====================================================== -->

    <a
        href="../dashboard.php"
        class="back-link"
    >

        <i class="bi bi-arrow-left"></i>

        Back to Dashboard

    </a>



    <!-- =====================================================
         TITLE
    ====================================================== -->

    <h1 class="page-title">

        Practical 5 — Filtration and Separation

    </h1>


    <p class="page-subtitle">

        Interactive Virtual Laboratory

    </p>



    <!-- =====================================================
         INSTRUCTIONS
    ====================================================== -->

    <div class="lab-card mb-4">


        <div class="card-header-custom">

            <h5>

                <i class="bi bi-info-circle me-2"></i>

                Experiment Instructions

            </h5>

        </div>



        <div class="card-body-custom">


            <div class="instruction-box">


                <strong>
                    Objective:
                </strong>

                Learn how filtration separates insoluble
                particles from a liquid mixture.


                <ul class="instruction-list">

                    <li>
                        Drag the sample beaker toward the
                        filtration funnel.
                    </li>


                    <li>
                        Install the filter paper.
                    </li>


                    <li>
                        Start the filtration process.
                    </li>


                    <li>
                        Observe the solid particles being
                        retained on the filter paper.
                    </li>


                    <li>
                        Observe the filtrate falling vertically
                        into the receiving beaker.
                    </li>


                    <li>
                        Review and save the results.
                    </li>

                </ul>


            </div>


        </div>

    </div>



    <!-- =====================================================
         MAIN ROW
    ====================================================== -->

    <div class="row g-4">


        <!-- =================================================
             VIRTUAL LAB
        ================================================== -->

        <div class="col-lg-8">


            <div class="lab-card">


                <div class="card-header-custom">


                    <h5>

                        <i class="bi bi-beaker me-2"></i>

                        Virtual Filtration Laboratory

                    </h5>


                    <span
                        class="badge text-bg-light"
                        id="experimentStatus"
                    >

                        Ready

                    </span>


                </div>



                <div class="card-body-custom">


                    <div
                        class="lab-workspace"
                        id="labWorkspace"
                    >


                        <div class="workspace-title">

                            Laboratory Work Area

                        </div>



                        <!-- =================================
                             SAMPLE BEAKER
                        ================================== -->

                        <div
                            class="sample-area"
                            id="sampleArea"
                        >


                            <div
                                class="sample-beaker"
                                id="sampleBeaker"
                                draggable="true"
                            >


                                <div class="sample-liquid">


                                    <span
                                        class="sample-particle particle-1"
                                    ></span>


                                    <span
                                        class="sample-particle particle-2"
                                    ></span>


                                    <span
                                        class="sample-particle particle-3"
                                    ></span>


                                    <span
                                        class="sample-particle particle-4"
                                    ></span>


                                    <span
                                        class="sample-particle particle-5"
                                    ></span>


                                </div>


                            </div>



                            <div class="equipment-label">


                                Sample Mixture


                                <br>


                                <small>

                                    Drag me to the funnel

                                </small>


                            </div>


                        </div>



                        <!-- =================================
                             FILTRATION FUNNEL
                        ================================== -->

                        <div
                            class="filter-area"
                            id="filterArea"
                        >


                            <div class="funnel">


                                <div class="funnel-top">


                                    <div
                                        class="filter-paper"
                                    ></div>



                                    <div
                                        class="retained-solids"
                                        id="retainedSolids"
                                    >


                                        <span></span>

                                        <span></span>

                                        <span></span>

                                        <span></span>


                                    </div>


                                </div>



                                <div class="funnel-stem"></div>


                            </div>



                            <div class="equipment-label">


                                Filtration Funnel


                                <br>


                                <small>

                                    Filter paper

                                </small>


                            </div>


                        </div>



                        <!-- =================================
                             RECEIVING BEAKER
                             DIRECTLY BELOW FUNNEL
                        ================================== -->

                        <div
                            class="receiver-area"
                            id="receiverArea"
                        >


                            <div
                                class="receiver-beaker"
                            >


                                <div
                                    class="receiver-liquid"
                                    id="receiverLiquid"
                                ></div>


                            </div>



                            <div class="equipment-label">


                                Receiving Beaker


                                <br>


                                <small>

                                    Filtrate

                                </small>


                            </div>


                        </div>



                        <!-- =================================
                             FILTRATE DROP
                        ================================== -->

                        <div
                            class="filtrate-drop"
                            id="filtrateDrop"
                        ></div>


                    </div>



                    <!-- =====================================
                         CONTROLS
                    ====================================== -->

                    <div class="controls">


                        <button
                            type="button"
                            class="btn btn-lab"
                            id="installFilterBtn"
                        >

                            <i class="bi bi-funnel-fill me-1"></i>

                            Install Filter Paper

                        </button>



                        <button
                            type="button"
                            class="btn btn-lab"
                            id="startBtn"
                            disabled
                        >

                            <i class="bi bi-play-fill me-1"></i>

                            Start Filtration

                        </button>



                        <button
                            type="button"
                            class="btn btn-outline-lab"
                            id="resetBtn"
                        >

                            <i class="bi bi-arrow-clockwise me-1"></i>

                            Reset

                        </button>


                    </div>



                    <!-- =====================================
                         STATUS
                    ====================================== -->

                    <div
                        class="status-box"
                        id="statusBox"
                    >

                        Drag the sample beaker onto the
                        filtration funnel to begin setting
                        up the experiment.

                    </div>


                </div>

            </div>

        </div>



        <!-- =================================================
             PARAMETERS
        ================================================== -->

        <div class="col-lg-4">


            <div class="lab-card">


                <div class="card-header-custom">


                    <h5>

                        <i class="bi bi-sliders me-2"></i>

                        Experiment Parameters

                    </h5>


                </div>



                <div class="card-body-custom">


                    <div class="mb-3">


                        <label
                            class="form-label"
                            for="initialMass"
                        >

                            Initial Sample Mass (g)

                        </label>


                        <input
                            type="number"
                            id="initialMass"
                            class="form-control"
                            value="100"
                            min="1"
                            step="0.1"
                        >


                    </div>



                    <div class="mb-3">


                        <label
                            class="form-label"
                            for="retainedMass"
                        >

                            Retained Solid Mass (g)

                        </label>


                        <input
                            type="number"
                            id="retainedMass"
                            class="form-control"
                            value="12"
                            min="0"
                            step="0.1"
                        >


                    </div>



                    <div class="mb-3">


                        <label
                            class="form-label"
                            for="filtrationTime"
                        >

                            Filtration Time (seconds)

                        </label>


                        <input
                            type="number"
                            id="filtrationTime"
                            class="form-control"
                            value="10"
                            min="3"
                            max="60"
                            step="1"
                        >


                    </div>



                    <div class="status-box">


                        <strong>
                            Sample:
                        </strong>

                        Liquid + suspended insoluble particles


                        <br>
                        <br>


                        <strong>
                            Filter:
                        </strong>

                        Laboratory filter paper


                    </div>


                </div>

            </div>



            <!-- =============================================
                 RESULTS
            ============================================== -->

            <div
                class="lab-card result-card"
                id="resultCard"
                style="display:none;"
            >


                <div class="card-header-custom">


                    <h5>

                        <i class="bi bi-clipboard-data me-2"></i>

                        Results

                    </h5>


                </div>



                <div class="card-body-custom">


                    <div class="row g-3">


                        <div class="col-6">


                            <div
                                class="result-value"
                                id="filtrateMassResult"
                            >
                                —
                            </div>


                            <div class="result-label">

                                Filtrate Mass (g)

                            </div>


                        </div>



                        <div class="col-6">


                            <div
                                class="result-value"
                                id="retainedMassResult"
                            >
                                —
                            </div>


                            <div class="result-label">

                                Retained Mass (g)

                            </div>


                        </div>



                        <div class="col-6">


                            <div
                                class="result-value"
                                id="efficiencyResult"
                            >
                                —
                            </div>


                            <div class="result-label">

                                Retention (%)

                            </div>


                        </div>



                        <div class="col-6">


                            <div
                                class="result-value"
                                id="timeResult"
                            >
                                —
                            </div>


                            <div class="result-label">

                                Time (s)

                            </div>


                        </div>


                    </div>



                    <button
                        type="button"
                        class="btn btn-lab w-100 mt-4"
                        id="saveBtn"
                    >

                        <i class="bi bi-save me-1"></i>

                        Save Simulation Result

                    </button>



                    <div
                        class="saved-message"
                        id="savedMessage"
                    >

                        <i
                            class="bi bi-check-circle me-1"
                        ></i>

                        Simulation result saved successfully.

                    </div>


                </div>

            </div>


        </div>

    </div>



    <!-- =====================================================
         GRAPH
    ====================================================== -->

    <div
        class="lab-card mt-4"
        id="chartCard"
        style="display:none;"
    >


        <div class="card-header-custom">


            <h5>

                <i class="bi bi-graph-up me-2"></i>

                Filtration Progress

            </h5>


        </div>



        <div class="card-body-custom">


            <div class="chart-container">

                <canvas
                    id="filtrationChart"
                ></canvas>

            </div>


        </div>

    </div>


</main>



<!-- =========================================================
     CHART.JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"
></script>



<script>

    const USER_ID =
        <?= $user_id ?>;

</script>



<!-- =========================================================
     PRACTICAL 5 JAVASCRIPT
========================================================= -->

<script
    src="../assets/js/practical5_filtration.js"
></script>


</body>

</html>