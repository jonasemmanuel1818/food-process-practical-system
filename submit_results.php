<?php

require_once "config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Get student information
|--------------------------------------------------------------------------
*/

$full_name = "Student";

$stmt = $conn->prepare("
    SELECT full_name
    FROM users
    WHERE id = ?
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $full_name = $row['full_name'];
    }

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| Practical information
|--------------------------------------------------------------------------
*/

$practicals = [
    1 => [
        "name" => "Laboratory Orientation",
        "short" => "Orientation",
        "icon" => "bi-shield-check",
        "description" => "Laboratory safety, equipment and procedures."
    ],
    2 => [
        "name" => "Physical Separation",
        "short" => "Physical Separation",
        "icon" => "bi-filter",
        "description" => "Centrifugation and sieve analysis."
    ],
    3 => [
        "name" => "Thermal Processing",
        "short" => "Thermal Processing",
        "icon" => "bi-thermometer-half",
        "description" => "Heat penetration and thermal processing."
    ],
    4 => [
        "name" => "Drying",
        "short" => "Drying",
        "icon" => "bi-wind",
        "description" => "Spray drying and freeze drying."
    ]
];

/*
|--------------------------------------------------------------------------
| Required activities
|--------------------------------------------------------------------------
|
| Practical 1 = orientation only
| Practical 2 = 5 activities
| Practical 3 = 9 activities
| Practical 4 = 12 activities
|
*/

$required_activities = [
    1 => 0,
    2 => 5,
    3 => 9,
    4 => 12
];

/*
|--------------------------------------------------------------------------
| Selected practical
|--------------------------------------------------------------------------
*/

$selected_practical = isset($_GET['practical'])
    ? (int) $_GET['practical']
    : 1;

if (!array_key_exists($selected_practical, $practicals)) {
    $selected_practical = 1;
}

/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";

/*
|--------------------------------------------------------------------------
| Check activity completion
|--------------------------------------------------------------------------
*/

$completed_activity_count = 0;

if ($selected_practical >= 2) {

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS completed_count
        FROM practical_activity_progress
        WHERE user_id = ?
          AND practical_number = ?
          AND completed = 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $user_id,
            $selected_practical
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $completed_activity_count = (int) $row['completed_count'];
        }

        $stmt->close();
    }
}

$required_count = $required_activities[$selected_practical] ?? 0;

$activities_complete =
    ($selected_practical === 1) ||
    ($completed_activity_count >= $required_count);

$remaining_activities = max(
    0,
    $required_count - $completed_activity_count
);

/*
|--------------------------------------------------------------------------
| Load existing submission
|--------------------------------------------------------------------------
*/

$existing_results = "";
$existing_observations = "";
$existing_conclusion = "";
$submitted_at = null;

$stmt = $conn->prepare("
    SELECT
        results,
        observations,
        conclusion,
        submitted_at
    FROM practical_submissions
    WHERE user_id = ?
      AND practical_number = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "ii",
        $user_id,
        $selected_practical
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        $existing_results = $row['results'] ?? "";
        $existing_observations = $row['observations'] ?? "";
        $existing_conclusion = $row['conclusion'] ?? "";
        $submitted_at = $row['submitted_at'];
    }

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| Handle submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $posted_practical = isset($_POST['practical_number'])
        ? (int) $_POST['practical_number']
        : $selected_practical;

    if (!array_key_exists($posted_practical, $practicals)) {
        $message = "Invalid practical selected.";
        $message_type = "danger";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Re-check activity completion from database
        |--------------------------------------------------------------------------
        | This is intentionally done again during POST so the restriction
        | cannot be bypassed by changing the page or JavaScript.
        |--------------------------------------------------------------------------
        */

        $selected_practical = $posted_practical;

        $required_count = $required_activities[$selected_practical] ?? 0;

        $completed_activity_count = 0;

        if ($selected_practical >= 2) {

            $stmt = $conn->prepare("
                SELECT COUNT(*) AS completed_count
                FROM practical_activity_progress
                WHERE user_id = ?
                  AND practical_number = ?
                  AND completed = 1
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "ii",
                    $user_id,
                    $selected_practical
                );

                $stmt->execute();

                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $completed_activity_count = (int) $row['completed_count'];
                }

                $stmt->close();
            }
        }

        $activities_complete =
            ($selected_practical === 1) ||
            ($completed_activity_count >= $required_count);

        $remaining_activities = max(
            0,
            $required_count - $completed_activity_count
        );

        /*
        |--------------------------------------------------------------------------
        | Block Practical 2-4 if activities are incomplete
        |--------------------------------------------------------------------------
        */

        if (!$activities_complete) {

            $message =
                "You cannot submit this practical yet. " .
                $completed_activity_count . "/" .
                $required_count .
                " activities are completed. " .
                $remaining_activities .
                " more " .
                ($remaining_activities === 1 ? "activity" : "activities") .
                " must be completed first.";

            $message_type = "warning";

        } else {

            /*
            |--------------------------------------------------------------------------
            | Practical 1
            |--------------------------------------------------------------------------
            */

            if ($selected_practical === 1) {

                $results = "Laboratory orientation completed.";

                $observations =
                    "Health and safety equipment, laboratory procedures " .
                    "and laboratory rules were reviewed.";

                $conclusion =
                    "The laboratory orientation was completed successfully " .
                    "and the required laboratory safety procedures were reviewed.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Practical 2-4
                |--------------------------------------------------------------------------
                */

                $results = trim($_POST['results'] ?? "");
                $observations = trim($_POST['observations'] ?? "");
                $conclusion = trim($_POST['conclusion'] ?? "");

                if (
                    $results === "" ||
                    $observations === "" ||
                    $conclusion === ""
                ) {

                    $message =
                        "Please complete Results, Observations and Conclusion " .
                        "before submitting.";

                    $message_type = "danger";
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Save submission
            |--------------------------------------------------------------------------
            */

            if ($message === "") {

                /*
                |--------------------------------------------------------------------------
                | Check whether submission already exists
                |--------------------------------------------------------------------------
                */

                $submission_exists = false;

                $stmt = $conn->prepare("
                    SELECT id
                    FROM practical_submissions
                    WHERE user_id = ?
                      AND practical_number = ?
                    LIMIT 1
                ");

                if ($stmt) {

                    $stmt->bind_param(
                        "ii",
                        $user_id,
                        $selected_practical
                    );

                    $stmt->execute();

                    $result = $stmt->get_result();

                    $submission_exists = ($result->num_rows > 0);

                    $stmt->close();
                }

                /*
                |--------------------------------------------------------------------------
                | Update existing submission
                |--------------------------------------------------------------------------
                */

                if ($submission_exists) {

                    $stmt = $conn->prepare("
                        UPDATE practical_submissions
                        SET
                            results = ?,
                            observations = ?,
                            conclusion = ?,
                            submitted_at = NOW()
                        WHERE user_id = ?
                          AND practical_number = ?
                    ");

                    if ($stmt) {

                        $stmt->bind_param(
                            "sssii",
                            $results,
                            $observations,
                            $conclusion,
                            $user_id,
                            $selected_practical
                        );

                        if ($stmt->execute()) {

                            $message =
                                "Practical results updated successfully.";

                            $message_type = "success";

                        } else {

                            $message =
                                "Could not update the practical results.";

                            $message_type = "danger";
                        }

                        $stmt->close();

                    } else {

                        $message =
                            "Database statement could not be prepared.";

                        $message_type = "danger";
                    }

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Insert new submission
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $conn->prepare("
                        INSERT INTO practical_submissions
                        (
                            user_id,
                            practical_number,
                            results,
                            observations,
                            conclusion,
                            submitted_at
                        )
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");

                    if ($stmt) {

                        $stmt->bind_param(
                            "iisss",
                            $user_id,
                            $selected_practical,
                            $results,
                            $observations,
                            $conclusion
                        );

                        if ($stmt->execute()) {

                            $message =
                                "Practical results submitted successfully.";

                            $message_type = "success";

                        } else {

                            $message =
                                "Could not save the practical results.";

                            $message_type = "danger";
                        }

                        $stmt->close();

                    } else {

                        $message =
                            "Database statement could not be prepared.";

                        $message_type = "danger";
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Mark practical as completed
                |--------------------------------------------------------------------------
                |
                | Practical 1 is completed after orientation submission.
                | Practical 2-4 are completed only after activities and
                | written results have been submitted.
                |
                */

                if ($message_type === "success") {

                    $stmt = $conn->prepare("
                        SELECT id
                        FROM practical_progress
                        WHERE user_id = ?
                          AND practical_number = ?
                        LIMIT 1
                    ");

                    $progress_exists = false;

                    if ($stmt) {

                        $stmt->bind_param(
                            "ii",
                            $user_id,
                            $selected_practical
                        );

                        $stmt->execute();

                        $result = $stmt->get_result();

                        $progress_exists = ($result->num_rows > 0);

                        $stmt->close();
                    }

                    if ($progress_exists) {

                        $stmt = $conn->prepare("
                            UPDATE practical_progress
                            SET
                                status = 'completed',
                                completed_at = NOW()
                            WHERE user_id = ?
                              AND practical_number = ?
                        ");

                    } else {

                        $stmt = $conn->prepare("
                            INSERT INTO practical_progress
                            (
                                user_id,
                                practical_number,
                                status,
                                started_at,
                                completed_at
                            )
                            VALUES (?, ?, 'completed', NOW(), NOW())
                        ");
                    }

                    if ($stmt) {

                        $stmt->bind_param(
                            "ii",
                            $user_id,
                            $selected_practical
                        );

                        $stmt->execute();

                        $stmt->close();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Refresh values after successful submission
                    |--------------------------------------------------------------------------
                    */

                    $existing_results = $results;
                    $existing_observations = $observations;
                    $existing_conclusion = $conclusion;
                    $submitted_at = date("Y-m-d H:i:s");
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Reload activity count after POST
|--------------------------------------------------------------------------
*/

$completed_activity_count = 0;

if ($selected_practical >= 2) {

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS completed_count
        FROM practical_activity_progress
        WHERE user_id = ?
          AND practical_number = ?
          AND completed = 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $user_id,
            $selected_practical
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $completed_activity_count = (int) $row['completed_count'];
        }

        $stmt->close();
    }
}

$required_count = $required_activities[$selected_practical] ?? 0;

$activities_complete =
    ($selected_practical === 1) ||
    ($completed_activity_count >= $required_count);

$remaining_activities = max(
    0,
    $required_count - $completed_activity_count
);

$activity_percentage = $required_count > 0
    ? round(($completed_activity_count / $required_count) * 100)
    : 100;

/*
|--------------------------------------------------------------------------
| Simulation history links
|--------------------------------------------------------------------------
*/

$simulation_history = [
    2 => "simulations/simulation2_history.php",
    3 => "simulations/simulation3_history.php",
    4 => "simulations/simulation4_history.php"
];

$simulation_compare = [
    2 => "simulations/simulation2_compare.php",
    3 => "simulations/simulation3_compare.php",
    4 => "simulations/simulation4_compare.php"
];

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
        Submit Results | Food Process Practical Learning System
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f6f8;
            color: #24343b;
            font-family:
                Inter,
                "Segoe UI",
                Arial,
                sans-serif;
        }

        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        .top-header {
            height: 72px;
            background: #ffffff;
            border-bottom: 1px solid #d9dee3;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #17495a;
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: #1f5f75;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .brand-title {
            font-weight: 700;
            font-size: 17px;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 12px;
            color: #71808a;
            margin-top: 2px;
        }

        .student-area {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1f5f75;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .student-name {
            font-size: 14px;
            font-weight: 600;
            color: #34454d;
        }

        /*
        |--------------------------------------------------------------------------
        | Main
        |--------------------------------------------------------------------------
        */

        .main-container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 34px 24px 50px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            color: #17495a;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .page-heading p {
            color: #6c7b83;
            margin: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Practical selector
        |--------------------------------------------------------------------------
        */

        .practical-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .practical-card {
            display: block;
            text-decoration: none;
            background: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 9px;
            padding: 18px;
            color: #263940;
            transition: 0.2s ease;
        }

        .practical-card:hover {
            border-color: #1f5f75;
            transform: translateY(-2px);
        }

        .practical-card.active {
            border: 2px solid #1f5f75;
            padding: 17px;
            background: #fafdfe;
        }

        .practical-card .icon {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            background: #eaf2f5;
            color: #1f5f75;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
        }

        .practical-card h3 {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .practical-card p {
            font-size: 12px;
            color: #71808a;
            margin: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Status panel
        |--------------------------------------------------------------------------
        */

        .status-panel {
            background: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 9px;
            padding: 22px;
            margin-bottom: 22px;
        }

        .status-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .status-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-title-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: #eaf2f5;
            color: #1f5f75;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
        }

        .status-title h2 {
            font-size: 19px;
            font-weight: 700;
            color: #17495a;
            margin: 0;
        }

        .status-title p {
            font-size: 13px;
            color: #71808a;
            margin: 3px 0 0;
        }

        .status-badge {
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-ready {
            color: #166534;
            background: #dcfce7;
        }

        .status-locked {
            color: #92400e;
            background: #fef3c7;
        }

        .status-orientation {
            color: #155e75;
            background: #e0f2fe;
        }

        /*
        |--------------------------------------------------------------------------
        | Progress
        |--------------------------------------------------------------------------
        */

        .progress-area {
            margin-top: 20px;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .progress-info strong {
            color: #17495a;
        }

        .progress {
            height: 9px;
            background: #e9edf0;
            border-radius: 10px;
        }

        .progress-bar {
            background: #1f5f75;
            border-radius: 10px;
        }

        /*
        |--------------------------------------------------------------------------
        | Alert
        |--------------------------------------------------------------------------
        */

        .alert {
            border-radius: 8px;
            border: 1px solid transparent;
            font-size: 14px;
        }

        /*
        |--------------------------------------------------------------------------
        | Main form card
        |--------------------------------------------------------------------------
        */

        .content-card {
            background: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 9px;
            padding: 28px;
            margin-bottom: 22px;
        }

        .section-title {
            color: #17495a;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .section-description {
            color: #71808a;
            font-size: 13px;
            margin-bottom: 24px;
        }

        .form-label {
            color: #34454d;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid #ccd4d9;
            border-radius: 7px;
            padding: 11px 13px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #1f5f75;
            box-shadow: 0 0 0 3px rgba(31, 95, 117, 0.10);
        }

        textarea.form-control {
            min-height: 145px;
            resize: vertical;
        }

        /*
        |--------------------------------------------------------------------------
        | Locked message
        |--------------------------------------------------------------------------
        */

        .locked-box {
            background: #fffaf0;
            border: 1px solid #ead9a8;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 24px;
        }

        .locked-box .locked-title {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #8a5a00;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .locked-box p {
            color: #765f2e;
            font-size: 13px;
            margin: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Orientation box
        |--------------------------------------------------------------------------
        */

        .orientation-box {
            background: #f3f9fb;
            border: 1px solid #c9dfe7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .orientation-box h4 {
            color: #17495a;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .orientation-box p {
            color: #53666e;
            font-size: 13px;
            margin-bottom: 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Buttons
        |--------------------------------------------------------------------------
        */

        .btn-lab {
            background: #1f5f75;
            border: 1px solid #1f5f75;
            color: #ffffff;
            border-radius: 7px;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-lab:hover {
            background: #17495a;
            border-color: #17495a;
            color: #ffffff;
        }

        .btn-outline-lab {
            background: #ffffff;
            border: 1px solid #1f5f75;
            color: #1f5f75;
            border-radius: 7px;
            padding: 9px 16px;
            font-size: 13px;
            font-weight: 600;
        }

        .btn-outline-lab:hover {
            background: #1f5f75;
            color: #ffffff;
        }

        .btn-secondary-light {
            background: #ffffff;
            border: 1px solid #d0d7dc;
            color: #4d5d64;
            border-radius: 7px;
            padding: 10px 17px;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-secondary-light:hover {
            background: #f3f5f6;
        }

        /*
        |--------------------------------------------------------------------------
        | Simulation links
        |--------------------------------------------------------------------------
        */

        .simulation-panel {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 22px;
            padding-top: 20px;
            border-top: 1px solid #e2e6e9;
        }

        .simulation-label {
            width: 100%;
            color: #53666e;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        /*
        |--------------------------------------------------------------------------
        | Submitted information
        |--------------------------------------------------------------------------
        */

        .submitted-info {
            display: flex;
            align-items: center;
            gap: 9px;
            background: #eef8f1;
            border: 1px solid #cce5d2;
            color: #28633a;
            padding: 11px 14px;
            border-radius: 7px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        /*
        |--------------------------------------------------------------------------
        | Footer actions
        |--------------------------------------------------------------------------
        */

        .bottom-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-top: 25px;
        }

        .action-left,
        .action-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */

        @media (max-width: 900px) {

            .practical-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .top-header {
                padding: 0 20px;
            }

            .brand-subtitle {
                display: none;
            }
        }

        @media (max-width: 600px) {

            .top-header {
                height: auto;
                padding: 15px;
                gap: 12px;
            }

            .student-name {
                display: none;
            }

            .main-container {
                padding: 24px 15px 40px;
            }

            .practical-grid {
                grid-template-columns: 1fr;
            }

            .status-header {
                flex-direction: column;
            }

            .content-card {
                padding: 20px;
            }

            .bottom-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .action-left,
            .action-right {
                width: 100%;
            }

            .action-left a,
            .action-right button {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<!-- ==============================================================
     HEADER
================================================================ -->

<header class="top-header">

    <div class="brand">

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

        <div class="student-avatar">
            <?= htmlspecialchars(strtoupper(substr($full_name, 0, 1))) ?>
        </div>

        <div class="student-name">
            <?= htmlspecialchars($full_name) ?>
        </div>

    </div>

</header>


<!-- ==============================================================
     MAIN CONTENT
================================================================ -->

<main class="main-container">

    <div class="page-heading">

        <h1>
            <i class="bi bi-file-earmark-check me-2"></i>
            Submit Practical Results
        </h1>

        <p>
            Select a practical, review your completion status and submit
            your laboratory results.
        </p>

    </div>


    <!-- ==========================================================
         PRACTICAL SELECTOR
    =========================================================== -->

    <div class="practical-grid">

        <?php foreach ($practicals as $number => $practical): ?>

            <a
                href="submit_results.php?practical=<?= $number ?>"
                class="practical-card
                    <?= $selected_practical === $number ? 'active' : '' ?>"
            >

                <div class="icon">
                    <i class="bi <?= htmlspecialchars($practical['icon']) ?>"></i>
                </div>

                <h3>
                    Practical <?= $number ?>:
                    <?= htmlspecialchars($practical['short']) ?>
                </h3>

                <p>
                    <?= htmlspecialchars($practical['description']) ?>
                </p>

            </a>

        <?php endforeach; ?>

    </div>


    <!-- ==========================================================
         ALERT MESSAGE
    =========================================================== -->

    <?php if ($message !== ""): ?>

        <div
            class="alert alert-<?= htmlspecialchars($message_type) ?>"
            role="alert"
        >
            <i class="bi
                <?= $message_type === 'success'
                    ? 'bi-check-circle'
                    : ($message_type === 'warning'
                        ? 'bi-exclamation-triangle'
                        : 'bi-exclamation-circle')
                ?>
                me-2"
            ></i>

            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>


    <!-- ==========================================================
         STATUS PANEL
    =========================================================== -->

    <div class="status-panel">

        <div class="status-header">

            <div class="status-title">

                <div class="status-title-icon">
                    <i class="bi <?= htmlspecialchars(
                        $practicals[$selected_practical]['icon']
                    ) ?>"></i>
                </div>

                <div>

                    <h2>
                        Practical <?= $selected_practical ?>:
                        <?= htmlspecialchars(
                            $practicals[$selected_practical]['name']
                        ) ?>
                    </h2>

                    <p>
                        Submission status and completion requirements
                    </p>

                </div>

            </div>


            <?php if ($selected_practical === 1): ?>

                <span class="status-badge status-orientation">
                    <i class="bi bi-shield-check me-1"></i>
                    Orientation

                </span>

            <?php elseif ($activities_complete): ?>

                <span class="status-badge status-ready">
                    <i class="bi bi-check-circle me-1"></i>
                    Ready for Submission
                </span>

            <?php else: ?>

                <span class="status-badge status-locked">
                    <i class="bi bi-lock-fill me-1"></i>
                    Submission Locked
                </span>

            <?php endif; ?>

        </div>


        <?php if ($selected_practical === 1): ?>

            <div class="progress-area">

                <div class="progress-info">

                    <strong>
                        Laboratory Orientation
                    </strong>

                    <span>
                        Ready
                    </span>

                </div>

                <div class="progress">

                    <div
                        class="progress-bar"
                        style="width: 100%;"
                    ></div>

                </div>

            </div>

        <?php else: ?>

            <div class="progress-area">

                <div class="progress-info">

                    <strong>
                        Activity Progress
                    </strong>

                    <span>
                        <?= $completed_activity_count ?>
                        /
                        <?= $required_count ?>
                        completed
                    </span>

                </div>

                <div class="progress">

                    <div
                        class="progress-bar"
                        style="width: <?= $activity_percentage ?>%;"
                    ></div>

                </div>

            </div>

        <?php endif; ?>

    </div>


    <!-- ==========================================================
         LOCKED NOTICE
    =========================================================== -->

    <?php if (
        $selected_practical >= 2 &&
        !$activities_complete
    ): ?>

        <div class="locked-box">

            <div class="locked-title">

                <i class="bi bi-lock-fill"></i>

                Submission is locked

            </div>

            <p>

                You have completed
                <strong>
                    <?= $completed_activity_count ?>
                    of
                    <?= $required_count ?>
                </strong>
                activities.

                Please return to the practical and complete the remaining
                <strong>
                    <?= $remaining_activities ?>
                </strong>
                <?= $remaining_activities === 1
                    ? "activity"
                    : "activities" ?>
                before submitting your results.

            </p>

        </div>

    <?php endif; ?>


    <!-- ==========================================================
         ORIENTATION NOTICE
    =========================================================== -->

    <?php if ($selected_practical === 1): ?>

        <div class="orientation-box">

            <h4>
                <i class="bi bi-info-circle me-2"></i>
                Laboratory Orientation
            </h4>

            <p>
                Practical 1 is an orientation practical. There are no
                individual activities to complete. Once the laboratory
                orientation has been completed, you can confirm it below.
            </p>

        </div>

    <?php endif; ?>


    <!-- ==========================================================
         RESULTS FORM
    =========================================================== -->

    <div class="content-card">

        <div class="section-title">
            Practical Report
        </div>

        <div class="section-description">

            Enter the results, observations and conclusion for the selected
            practical. Your submission will be recorded under your student
            account.

        </div>


        <?php if ($submitted_at): ?>

            <div class="submitted-info">

                <i class="bi bi-check-circle-fill"></i>

                <span>
                    Results previously submitted on
                    <strong>
                        <?= htmlspecialchars($submitted_at) ?>
                    </strong>
                </span>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="submit_results.php?practical=<?= $selected_practical ?>"
        >

            <input
                type="hidden"
                name="practical_number"
                value="<?= $selected_practical ?>"
            >


            <!-- RESULTS -->

            <div class="mb-4">

                <label
                    for="results"
                    class="form-label"
                >
                    Results
                </label>

                <?php if ($selected_practical === 1): ?>

                    <textarea
                        id="results"
                        name="results"
                        class="form-control"
                        readonly
                    >Laboratory orientation completed.</textarea>

                <?php else: ?>

                    <textarea
                        id="results"
                        name="results"
                        class="form-control"
                        placeholder="Enter the results obtained from the practical..."
                        <?= !$activities_complete ? 'disabled' : '' ?>
                    ><?= htmlspecialchars($existing_results) ?></textarea>

                <?php endif; ?>

            </div>


            <!-- OBSERVATIONS -->

            <div class="mb-4">

                <label
                    for="observations"
                    class="form-label"
                >
                    Observations
                </label>

                <?php if ($selected_practical === 1): ?>

                    <textarea
                        id="observations"
                        name="observations"
                        class="form-control"
                        readonly
                    >Health and safety equipment, laboratory procedures and laboratory rules were reviewed.</textarea>

                <?php else: ?>

                    <textarea
                        id="observations"
                        name="observations"
                        class="form-control"
                        placeholder="Enter your observations during the practical..."
                        <?= !$activities_complete ? 'disabled' : '' ?>
                    ><?= htmlspecialchars($existing_observations) ?></textarea>

                <?php endif; ?>

            </div>


            <!-- CONCLUSION -->

            <div class="mb-4">

                <label
                    for="conclusion"
                    class="form-label"
                >
                    Conclusion
                </label>

                <?php if ($selected_practical === 1): ?>

                    <textarea
                        id="conclusion"
                        name="conclusion"
                        class="form-control"
                        readonly
                    >The laboratory orientation was completed successfully and the required laboratory safety procedures were reviewed.</textarea>

                <?php else: ?>

                    <textarea
                        id="conclusion"
                        name="conclusion"
                        class="form-control"
                        placeholder="Write your conclusion for the practical..."
                        <?= !$activities_complete ? 'disabled' : '' ?>
                    ><?= htmlspecialchars($existing_conclusion) ?></textarea>

                <?php endif; ?>

            </div>


            <!-- ==================================================
                 ACTIONS
            =================================================== -->

            <div class="bottom-actions">

                <div class="action-left">

                    <?php if ($selected_practical === 1): ?>

                        <a
                            href="practical/practical1.php"
                            class="btn-secondary-light text-decoration-none"
                        >
                            <i class="bi bi-arrow-left me-1"></i>
                            Back to Practical
                        </a>

                    <?php elseif (!$activities_complete): ?>

                        <a
                            href="practical/practical<?= $selected_practical ?>.php"
                            class="btn-lab text-decoration-none"
                        >
                            <i class="bi bi-arrow-left me-1"></i>
                            Complete Activities
                        </a>

                    <?php else: ?>

                        <a
                            href="practical/practical<?= $selected_practical ?>.php"
                            class="btn-secondary-light text-decoration-none"
                        >
                            <i class="bi bi-arrow-left me-1"></i>
                            Back to Practical
                        </a>

                    <?php endif; ?>

                </div>


                <div class="action-right">

                    <a
                        href="dashboard.php"
                        class="btn-secondary-light text-decoration-none"
                    >
                        <i class="bi bi-grid me-1"></i>
                        Dashboard
                    </a>


                    <?php if ($selected_practical === 1): ?>

                        <button
                            type="submit"
                            class="btn-lab"
                        >
                            <i class="bi bi-check-circle me-1"></i>
                            Confirm Orientation
                        </button>

                    <?php elseif ($activities_complete): ?>

                        <button
                            type="submit"
                            class="btn-lab"
                        >
                            <i class="bi bi-send me-1"></i>
                            Submit Results
                        </button>

                    <?php else: ?>

                        <button
                            type="button"
                            class="btn btn-secondary"
                            disabled
                        >
                            <i class="bi bi-lock-fill me-1"></i>
                            Complete Activities First
                        </button>

                    <?php endif; ?>

                </div>

            </div>

        </form>


        <!-- ======================================================
             SIMULATION LINKS
        ======================================================= -->

        <?php if ($selected_practical >= 2): ?>

            <div class="simulation-panel">

                <div class="simulation-label">
                    <i class="bi bi-bar-chart-line me-1"></i>
                    Simulation Records
                </div>


                <a
                    href="<?= htmlspecialchars(
                        $simulation_history[$selected_practical]
                    ) ?>"
                    class="btn-outline-lab text-decoration-none"
                >
                    <i class="bi bi-clock-history me-1"></i>
                    Results History
                </a>


                <a
                    href="<?= htmlspecialchars(
                        $simulation_compare[$selected_practical]
                    ) ?>"
                    class="btn-outline-lab text-decoration-none"
                >
                    <i class="bi bi-bar-chart-line me-1"></i>
                    Compare Results
                </a>

            </div>

        <?php endif; ?>

    </div>

</main>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>