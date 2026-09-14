<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Food Process Practical Learning & Simulation System</title>

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

    <style>

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #333;
        }

        /* NAVBAR */

        .navbar {
            background: #17495a;
            padding: 14px 0;
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
            font-size: 20px;
        }

        .navbar-brand i {
            margin-right: 8px;
        }

        .nav-link {
            color: #e8f2f5 !important;
            margin-left: 15px;
        }

        .nav-link:hover {
            color: white !important;
        }

        /* HERO */

        .hero {
            background: #1f5f75;
            color: white;
            padding: 90px 20px;
        }

        .hero h1 {
            font-size: 45px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 18px;
            max-width: 700px;
            line-height: 1.7;
            color: #e9f3f5;
        }

        .hero-buttons {
            margin-top: 30px;
        }

        .btn-primary-custom {
            background: white;
            color: #17495a;
            border: none;
            padding: 12px 25px;
            border-radius: 7px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary-custom:hover {
            background: #e8f2f5;
            color: #17495a;
        }

        .btn-outline-custom {
            border: 1px solid white;
            color: white;
            padding: 11px 25px;
            border-radius: 7px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }

        .btn-outline-custom:hover {
            background: white;
            color: #17495a;
        }

        /* SECTION */

        .section {
            padding: 70px 20px;
        }

        .section-title {
            text-align: center;
            color: #17495a;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .section-description {
            text-align: center;
            color: #666;
            max-width: 700px;
            margin: 0 auto 40px;
        }

        /* PRACTICAL CARDS */

        .practical-card {
            background: white;
            border: 1px solid #d9dee3;
            border-radius: 10px;
            padding: 25px;
            height: 100%;
            transition: 0.2s;
        }

        .practical-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 5px 18px rgba(0,0,0,0.08);
        }

        .practical-icon {
            width: 55px;
            height: 55px;
            border-radius: 8px;
            background: #e8f2f5;
            color: #1f5f75;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            margin-bottom: 18px;
        }

        .practical-card h5 {
            color: #17495a;
            font-weight: bold;
        }

        .practical-card p {
            color: #666;
            line-height: 1.6;
        }

        /* SIMULATION */

        .simulation-section {
            background: white;
            padding: 70px 20px;
            border-top: 1px solid #d9dee3;
            border-bottom: 1px solid #d9dee3;
        }

        .simulation-box {
            border: 1px solid #d9dee3;
            border-radius: 10px;
            padding: 30px;
            background: #f8fafb;
        }

        .simulation-box h3 {
            color: #17495a;
            font-weight: bold;
        }

        .simulation-list {
            list-style: none;
            padding: 0;
        }

        .simulation-list li {
            padding: 10px 0;
            color: #555;
        }

        .simulation-list i {
            color: #1f5f75;
            margin-right: 8px;
        }

        /* HOW IT WORKS */

        .step {
            text-align: center;
            padding: 20px;
        }

        .step-number {
            width: 45px;
            height: 45px;
            background: #1f5f75;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-weight: bold;
        }

        .step h5 {
            color: #17495a;
            font-weight: bold;
        }

        .step p {
            color: #666;
        }

        /* CTA */

        .cta {
            background: #17495a;
            color: white;
            text-align: center;
            padding: 70px 20px;
        }

        .cta h2 {
            font-weight: bold;
            margin-bottom: 15px;
        }

        .cta p {
            color: #dcebef;
            margin-bottom: 25px;
        }

        /* FOOTER */

        footer {
            background: #102f3a;
            color: #cbd9dd;
            padding: 25px 20px;
            text-align: center;
        }

        footer p {
            margin: 0;
        }

        /* RESPONSIVE */

        @media (max-width: 768px) {

            .hero {
                padding: 65px 20px;
            }

            .hero h1 {
                font-size: 34px;
            }

            .btn-outline-custom {
                margin-left: 0;
                margin-top: 10px;
            }

        }

    </style>

</head>

<body>


<!-- NAVBAR -->

<nav class="navbar navbar-expand-lg">

    <div class="container">

        <a class="navbar-brand" href="index.php">
            <i class="bi bi-beaker"></i>
            Food Process System
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarMenu"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">

            <ul class="navbar-nav ms-auto">

                <li class="nav-item">
                    <a class="nav-link" href="#practicals">
                        Practicals
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#simulations">
                        Simulations
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#how-it-works">
                        How It Works
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="login.php">
                        Login
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>


<!-- HERO -->

<section class="hero">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-lg-8">

                <h1>
                    Food Process Practical Learning & Simulation System
                </h1>

                <p>
                    An interactive learning platform designed to support
                    food processing practicals through guided activities,
                    experiment recording, and practical simulations.
                </p>

                <div class="hero-buttons">

                    <a href="login.php" class="btn-primary-custom">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Login to System
                    </a>

                    <a href="#practicals" class="btn-outline-custom">
                        Explore Practicals
                    </a>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- PRACTICALS -->

<section class="section" id="practicals">

    <div class="container">

        <h2 class="section-title">
            Practical Learning
        </h2>

        <p class="section-description">
            The system provides structured practical activities that guide
            students through important food processing laboratory concepts.
        </p>


        <div class="row g-4">

            <!-- PRACTICAL 1 -->

            <div class="col-md-6 col-lg-3">

                <div class="practical-card">

                    <div class="practical-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>

                    <h5>
                        Practical 1
                    </h5>

                    <p>
                        Laboratory Orientation
                    </p>

                    <small class="text-muted">
                        Health, safety equipment, laboratory rules,
                        and safe working practices.
                    </small>

                </div>

            </div>


            <!-- PRACTICAL 2 -->

            <div class="col-md-6 col-lg-3">

                <div class="practical-card">

                    <div class="practical-icon">
                        <i class="bi bi-funnel"></i>
                    </div>

                    <h5>
                        Practical 2
                    </h5>

                    <p>
                        Physical Separation
                    </p>

                    <small class="text-muted">
                        Centrifugation and sieve analysis for
                        physical separation of food materials.
                    </small>

                </div>

            </div>


            <!-- PRACTICAL 3 -->

            <div class="col-md-6 col-lg-3">

                <div class="practical-card">

                    <div class="practical-icon">
                        <i class="bi bi-thermometer-half"></i>
                    </div>

                    <h5>
                        Practical 3
                    </h5>

                    <p>
                        Thermal Processing
                    </p>

                    <small class="text-muted">
                        Heat penetration and thermal processing
                        analysis using simulation.
                    </small>

                </div>

            </div>


            <!-- PRACTICAL 4 -->

            <div class="col-md-6 col-lg-3">

                <div class="practical-card">

                    <div class="practical-icon">
                        <i class="bi bi-droplet"></i>
                    </div>

                    <h5>
                        Practical 4
                    </h5>

                    <p>
                        Drying
                    </p>

                    <small class="text-muted">
                        Introduction to food drying processes
                        including spray and freeze drying.
                    </small>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- SIMULATIONS -->

<section class="simulation-section" id="simulations">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-lg-6">

                <h2 class="section-title text-start">
                    Practical Simulations
                </h2>

                <p class="section-description text-start mx-0">
                    Interactive simulations allow students to enter
                    measurements, observe calculated results, and
                    understand food processing concepts.
                </p>

            </div>


            <div class="col-lg-6">

                <div class="simulation-box">

                    <h3>
                        <i class="bi bi-graph-up"></i>
                        Simulation Features
                    </h3>

                    <ul class="simulation-list mt-3">

                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Physical separation simulation
                        </li>

                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Thermal processing simulation
                        </li>

                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Drying simulation
                        </li>

                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Automatic calculations
                        </li>

                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Simulation history
                        </li>

                        <li>
                            <i class="bi bi-check-circle-fill"></i>
                            Results comparison
                        </li>

                    </ul>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- HOW IT WORKS -->

<section class="section" id="how-it-works">

    <div class="container">

        <h2 class="section-title">
            How It Works
        </h2>

        <p class="section-description">
            Students progress through each practical in a structured
            sequence.
        </p>


        <div class="row">

            <div class="col-md-3">

                <div class="step">

                    <div class="step-number">
                        1
                    </div>

                    <h5>
                        Login
                    </h5>

                    <p>
                        Access the system using your account.
                    </p>

                </div>

            </div>


            <div class="col-md-3">

                <div class="step">

                    <div class="step-number">
                        2
                    </div>

                    <h5>
                        Learn
                    </h5>

                    <p>
                        Read the practical instructions and complete activities.
                    </p>

                </div>

            </div>


            <div class="col-md-3">

                <div class="step">

                    <div class="step-number">
                        3
                    </div>

                    <h5>
                        Simulate
                    </h5>

                    <p>
                        Run simulations and analyze the generated results.
                    </p>

                </div>

            </div>


            <div class="col-md-3">

                <div class="step">

                    <div class="step-number">
                        4
                    </div>

                    <h5>
                        Submit
                    </h5>

                    <p>
                        Record observations and submit practical results.
                    </p>

                </div>

            </div>

        </div>

    </div>

</section>




<!-- FOOTER -->

<footer>

    <div class="container">

        <p>
            Food Process Practical Learning & Simulation System
        </p>

        <p class="mt-2">
            © <?= date("Y") ?> — Academic Practical Learning Platform
        </p>

    </div>

</footer>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>