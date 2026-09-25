<?php
require_once "config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Protect dashboard */
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/* Get student's name */
$user_name = $_SESSION['name']
    ?? $_SESSION['full_name']
    ?? $_SESSION['username']
    ?? "Student";


/* =========================================================
   PRACTICAL INFORMATION
   ========================================================= */

$practicals = [

    1 => [
        "title" => "Laboratory Orientation",
        "description" => "Laboratory orientation, health and safety equipment, and laboratory rules.",
        "activities" => 0,
        "link" => "practical/practical1.php",
        "icon" => "bi-shield-check"
    ],

    2 => [
        "title" => "Physical Separation: Centrifugation and Sieve Analysis",
        "description" => "Study physical separation techniques through centrifugation and sieve analysis.",
        "activities" => 5,
        "link" => "practical/practical2.php",
        "icon" => "bi-funnel"
    ],

    3 => [
        "title" => "Thermal Processing in Foods: Heat Penetration",
        "description" => "Study heat penetration and thermal processing using practical activities and simulation.",
        "activities" => 9,
        "link" => "practical/practical3.php",
        "icon" => "bi-thermometer-half"
    ],

    4 => [
        "title" => "Drying: Spray Drying and Freeze Drying",
        "description" => "Study spray drying and freeze drying through practical activities and simulation.",
        "activities" => 12,
        "link" => "practical/practical4.php",
        "icon" => "bi-wind"
    ],

    5 => [
        "title" => "Filtration and Separation",
        "description" => "Study filtration and separation techniques through practical activities and interactive simulation.",
        "activities" => 8,
        "link" => "practical/practical5.php",
        "icon" => "bi-filter-circle"
    ]

];


/* =========================================================
   GET PRACTICAL STATUS, ACTIVITY PROGRESS
   AND SUBMISSION STATUS
   ========================================================= */

$practical_status = [];
$practical_progress = [];
$submission_status = [];


for ($i = 1; $i <= 5; $i++) {

    /* Default status */
    $status = "not_started";


    /* -----------------------------------------------------
       GET LATEST PRACTICAL STATUS
       ----------------------------------------------------- */

    $stmt = $conn->prepare("
        SELECT status
        FROM practical_progress
        WHERE user_id = ?
          AND practical_number = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $user_id,
            $i
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        if ($row && !empty($row['status'])) {
            $status = $row['status'];
        }

        $stmt->close();
    }


    $practical_status[$i] = $status;


    /* -----------------------------------------------------
       CHECK SUBMISSION
       ----------------------------------------------------- */

    $has_submission = false;

    $stmt = $conn->prepare("
        SELECT id
        FROM practical_submissions
        WHERE user_id = ?
          AND practical_number = ?
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $user_id,
            $i
        );

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->fetch_assoc()) {
            $has_submission = true;
        }

        $stmt->close();
    }


    $submission_status[$i] = $has_submission;


    /* =====================================================
       PRACTICAL 1
       ===================================================== */

    if ($i === 1) {

        if ($status === "completed") {

            $practical_progress[$i] = 100;

        } elseif ($status === "in_progress") {

            $practical_progress[$i] = 50;

        } else {

            $practical_progress[$i] = 0;
        }

        continue;
    }


    /* =====================================================
       PRACTICALS 2, 3, 4 AND 5
       ===================================================== */

    $total_activities = $practicals[$i]["activities"];

    $completed_activities = 0;


    $stmt = $conn->prepare("
        SELECT COUNT(*) AS completed_count
        FROM practical_activity_progress
        WHERE user_id = ?
          AND practical_number = ?
          AND completed = 1
          AND activity_number BETWEEN 1 AND ?
    ");

    if ($stmt) {

        $stmt->bind_param(
            "iii",
            $user_id,
            $i,
            $total_activities
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $row = $result->fetch_assoc();

        if ($row) {
            $completed_activities = (int) $row['completed_count'];
        }

        $stmt->close();
    }


    if ($total_activities > 0) {

        $percentage = round(
            ($completed_activities / $total_activities) * 100
        );

    } else {

        $percentage = 0;
    }


    /* Official completion overrides activity percentage */

    if ($status === "completed") {
        $percentage = 100;
    }


    $practical_progress[$i] = $percentage;
}


/* =========================================================
   DASHBOARD STATISTICS
   ========================================================= */

$total_practicals = 5;

$completed_count = 0;

$in_progress_count = 0;

$not_started_count = 0;


for ($i = 1; $i <= 5; $i++) {

    if ($practical_status[$i] === "completed") {
        $completed_count++;
    }

    if ($practical_status[$i] === "in_progress") {
        $in_progress_count++;
    }

    if ($practical_status[$i] === "not_started") {
        $not_started_count++;
    }
}


/* =========================================================
   OVERALL PROGRESS
   ========================================================= */

$total_progress = 0;


for ($i = 1; $i <= 5; $i++) {

    $total_progress += $practical_progress[$i];
}


$overall_progress = round(
    $total_progress / $total_practicals
);


/* =========================================================
   STATUS FUNCTIONS
   ========================================================= */

function getStatusText($status)
{
    switch ($status) {

        case "completed":
            return "Completed";

        case "in_progress":
            return "In Progress";

        default:
            return "Not Started";
    }
}


function getStatusClass($status)
{
    switch ($status) {

        case "completed":
            return "status-completed";

        case "in_progress":
            return "status-progress";

        default:
            return "status-not-started";
    }
}


function getStatusIcon($status)
{
    switch ($status) {

        case "completed":
            return "bi-check-circle-fill";

        case "in_progress":
            return "bi-arrow-repeat";

        default:
            return "bi-circle";
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
    Student Dashboard | Food Process Practical Learning System
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


<style>

/* =========================================================
   GENERAL
   ========================================================= */

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f4f6f8;

    color: #263238;
}


a {
    text-decoration: none;
}


/* =========================================================
   TOP HEADER
   ========================================================= */

.top-header {

    height: 68px;

    background: #ffffff;

    border-bottom: 1px solid #d9dee3;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 28px;

    position: fixed;

    top: 0;

    left: 0;

    right: 0;

    z-index: 1000;
}


.brand {

    display: flex;

    align-items: center;

    gap: 12px;

    color: #263238;

    font-size: 18px;

    font-weight: 600;
}


.brand-icon {

    width: 40px;

    height: 40px;

    background: #1f5f75;

    color: #ffffff;

    border-radius: 6px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;
}


.brand-text small {

    display: block;

    color: #7b8790;

    font-size: 11px;

    font-weight: normal;

    margin-top: 2px;
}


.user-area {

    display: flex;

    align-items: center;

    gap: 11px;
}


.user-name {

    font-size: 14px;

    font-weight: 600;

    color: #455a64;
}


.user-role {

    display: block;

    font-size: 10px;

    color: #8a959d;

    font-weight: normal;

    text-align: right;

    margin-top: 2px;
}


.user-avatar {

    width: 38px;

    height: 38px;

    border-radius: 50%;

    background: #e8eef1;

    color: #1f5f75;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 18px;
}


/* =========================================================
   SIDEBAR
   ========================================================= */

.sidebar {

    width: 245px;

    position: fixed;

    top: 68px;

    left: 0;

    bottom: 0;

    background: #ffffff;

    border-right: 1px solid #d9dee3;

    padding: 22px 15px;

    overflow-y: auto;
}


.sidebar-heading {

    padding: 0 11px;

    margin-bottom: 12px;

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.8px;

    color: #8a959d;
}


.nav-link-custom {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 11px 12px;

    margin-bottom: 4px;

    border-radius: 5px;

    color: #53636c;

    font-size: 14px;

    transition:
        background 0.15s ease,
        color 0.15s ease;
}


.nav-link-custom i {

    width: 21px;

    font-size: 17px;
}


.nav-link-custom:hover {

    background: #eef3f5;

    color: #1f5f75;
}


.nav-link-custom.active {

    background: #e7f0f3;

    color: #1f5f75;

    font-weight: 600;

    border-left: 3px solid #1f5f75;

    padding-left: 9px;
}


.nav-section-divider {

    height: 1px;

    background: #edf0f2;

    margin: 20px 10px;
}


.logout-link {

    color: #9b4141;
}


.logout-link:hover {

    background: #faeeee;

    color: #8a3030;
}


/* =========================================================
   MAIN CONTENT
   ========================================================= */

.main-content {

    margin-left: 245px;

    padding: 94px 30px 90px;

    min-height: 100vh;
}


/* =========================================================
   WELCOME SECTION
   ========================================================= */

.welcome-section {

    background: #ffffff;

    border: 1px solid #d9dee3;

    border-radius: 8px;

    padding: 25px 27px;

    margin-bottom: 25px;

    position: relative;

    overflow: hidden;
}


.welcome-section::before {

    content: "";

    position: absolute;

    left: 0;

    top: 0;

    bottom: 0;

    width: 5px;

    background: #1f5f75;
}


.welcome-content {

    position: relative;

    z-index: 2;
}


.welcome-label {

    color: #1f5f75;

    font-size: 12px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.7px;

    margin-bottom: 7px;
}


.welcome-section h1 {

    margin: 0 0 7px;

    font-size: 27px;

    font-weight: 600;

    color: #263238;
}


.welcome-section p {

    margin: 0;

    color: #738089;

    font-size: 14px;

    line-height: 1.6;
}


/* =========================================================
   OVERALL PROGRESS
   ========================================================= */

.overall-card {

    background: #f8fafb;

    border: 1px solid #dfe5e8;

    border-radius: 7px;

    padding: 20px 21px;

    margin-top: 22px;
}


.overall-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 11px;
}


.overall-title {

    font-size: 13px;

    font-weight: 600;

    color: #455a64;
}


.overall-percentage {

    font-size: 20px;

    font-weight: 700;

    color: #1f5f75;
}


.overall-progress {

    height: 9px;

    background: #e2e8eb;

    border-radius: 20px;

    overflow: hidden;
}


.overall-progress-bar {

    height: 100%;

    background: #1f5f75;

    border-radius: 20px;

    transition: width 0.3s ease;
}


.overall-bottom {

    display: flex;

    justify-content: space-between;

    margin-top: 9px;

    font-size: 11px;

    color: #7d8990;
}


/* =========================================================
   PAGE HEADER
   ========================================================= */

.page-header {

    margin-bottom: 20px;

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 15px;
}


.page-header h2 {

    margin: 0 0 5px;

    font-size: 20px;

    font-weight: 600;

    color: #37474f;
}


.page-header p {

    margin: 0;

    color: #7b8790;

    font-size: 13px;
}


.practical-count {

    white-space: nowrap;

    padding: 7px 11px;

    background: #edf4f6;

    color: #1f5f75;

    border-radius: 5px;

    font-size: 11px;

    font-weight: 600;
}


/* =========================================================
   PRACTICAL SECTION
   ========================================================= */

.section-card {

    background: #ffffff;

    border: 1px solid #d9dee3;

    border-radius: 7px;

    margin-bottom: 25px;
}


.section-header {

    padding: 17px 20px;

    border-bottom: 1px solid #e6eaed;

    display: flex;

    align-items: center;

    justify-content: space-between;
}


.section-header h5 {

    margin: 0;

    font-size: 16px;

    font-weight: 600;

    color: #37474f;
}


.section-header small {

    color: #87939b;
}


.section-body {

    padding: 20px;
}


/* =========================================================
   PRACTICAL CARDS
   ========================================================= */

.practical-card {

    height: 100%;

    background: #ffffff;

    border: 1px solid #dce2e5;

    border-radius: 7px;

    padding: 20px;

    display: flex;

    flex-direction: column;

    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease,
        transform 0.15s ease;
}


.practical-card:hover {

    border-color: #b6c7ce;

    box-shadow:
        0 5px 16px rgba(25, 55, 65, 0.07);

    transform: translateY(-2px);
}


/* Completed card */

.practical-card.card-completed {

    border-top: 3px solid #28734a;
}


/* In progress card */

.practical-card.card-progress {

    border-top: 3px solid #b88a27;
}


/* Not started card */

.practical-card.card-not-started {

    border-top: 3px solid #d5dadd;
}


/* =========================================================
   PRACTICAL TOP
   ========================================================= */

.practical-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    margin-bottom: 15px;
}


.practical-icon {

    width: 45px;

    height: 45px;

    background: #edf4f6;

    color: #1f5f75;

    border-radius: 6px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;
}


.practical-index {

    font-size: 11px;

    font-weight: 700;

    color: #89959c;

    text-transform: uppercase;

    letter-spacing: 0.5px;
}


/* =========================================================
   STATUS
   ========================================================= */

.status {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    width: fit-content;

    padding: 5px 9px;

    border-radius: 4px;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.3px;

    margin-bottom: 12px;
}


.status-not-started {

    background: #eef0f2;

    color: #65727a;
}


.status-progress {

    background: #fff3d6;

    color: #866b21;
}


.status-completed {

    background: #e3f1e9;

    color: #28734a;
}


/* =========================================================
   PRACTICAL TITLE
   ========================================================= */

.practical-card h6 {

    margin: 0 0 8px;

    font-size: 15px;

    line-height: 1.45;

    font-weight: 600;

    color: #37474f;
}


.practical-card p {

    margin: 0 0 15px;

    color: #7a858c;

    font-size: 13px;

    line-height: 1.55;

    flex-grow: 1;
}


/* =========================================================
   SUBMISSION STATUS
   ========================================================= */

.submission-area {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 9px 10px;

    margin-bottom: 15px;

    border: 1px solid #e3e7e9;

    background: #fafbfc;

    border-radius: 4px;

    font-size: 11px;
}


.submission-label {

    color: #7b8790;

    font-weight: 600;
}


.submission-status {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    font-weight: 700;
}


.submission-pending {

    color: #8a6d1d;
}


.submission-complete {

    color: #28734a;
}


/* =========================================================
   PROGRESS
   ========================================================= */

.progress-area {

    margin-bottom: 17px;
}


.progress-text {

    display: flex;

    justify-content: space-between;

    margin-bottom: 7px;

    font-size: 11px;

    color: #7d8990;
}


.progress {

    height: 7px;

    background: #e9edef;

    border-radius: 10px;
}


.progress-bar {

    background: #1f5f75;

    border-radius: 10px;
}


/* =========================================================
   PRACTICAL BUTTON
   ========================================================= */

.btn-practical {

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    padding: 10px 12px;

    border: 1px solid #1f5f75;

    border-radius: 5px;

    background: #1f5f75;

    color: #ffffff;

    font-size: 13px;

    font-weight: 600;

    transition:
        background 0.15s ease,
        border-color 0.15s ease;
}


.btn-practical:hover {

    background: #17495a;

    border-color: #17495a;

    color: #ffffff;
}


/* =========================================================
   SUMMARY SECTION
   ========================================================= */

.summary-section {

    margin-top: 25px;
}


.summary-card {

    background: #ffffff;

    border: 1px solid #d9dee3;

    border-radius: 7px;

    min-height: 105px;

    padding: 18px 20px;

    display: flex;

    align-items: center;

    gap: 15px;

    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}


.summary-card:hover {

    border-color: #bdcbd0;

    box-shadow:
        0 3px 12px rgba(25, 55, 65, 0.05);
}


.summary-icon {

    width: 44px;

    height: 44px;

    border-radius: 6px;

    background: #edf4f6;

    color: #1f5f75;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;

    flex-shrink: 0;
}


.summary-content small {

    display: block;

    color: #7d8990;

    font-size: 11px;

    margin-bottom: 5px;
}


.summary-number {

    font-size: 24px;

    font-weight: 600;

    color: #37474f;
}


.summary-description {

    color: #9aa3a8;

    font-size: 10px;

    margin-top: 2px;
}


/* =========================================================
   INFORMATION PANELS
   ========================================================= */

.info-icon {

    width: 45px;

    height: 45px;

    background: #edf4f6;

    color: #1f5f75;

    border-radius: 5px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;
}


.info-list {

    padding-left: 19px;

    margin-bottom: 0;
}


.info-list li {

    margin-bottom: 8px;

    color: #596870;

    font-size: 13px;
}


/* =========================================================
   FOOTER
   ========================================================= */

.footer {

    position: fixed;

    bottom: 0;

    left: 245px;

    right: 0;

    height: 43px;

    background: #ffffff;

    border-top: 1px solid #d9dee3;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #89949b;

    font-size: 11px;

    z-index: 900;
}


/* =========================================================
   MODAL IMPROVEMENTS
   ========================================================= */

.modal-content {

    border: 1px solid #d9dee3;

    border-radius: 7px;

    overflow: hidden;
}


.modal-header {

    border-bottom: 1px solid #e5e9eb;

    background: #fafbfc;
}


.modal-title {

    color: #37474f;

    font-size: 16px;

    font-weight: 600;
}


.modal-title i {

    color: #1f5f75;
}


/* =========================================================
   RESPONSIVE - TABLET
   ========================================================= */

@media (max-width: 992px) {

    .sidebar {

        width: 220px;
    }


    .main-content {

        margin-left: 220px;
    }


    .footer {

        left: 220px;
    }
}


/* =========================================================
   RESPONSIVE - MOBILE/TABLET
   ========================================================= */

@media (max-width: 768px) {

    .top-header {

        padding: 0 18px;
    }


    .brand-text {

        display: none;
    }


    .sidebar {

        width: 68px;

        padding: 18px 8px;
    }


    .sidebar-heading {

        display: none;
    }


    .nav-link-custom {

        justify-content: center;

        padding: 12px 5px;
    }


    .nav-link-custom span {

        display: none;
    }


    .nav-link-custom i {

        width: auto;
    }


    .nav-section-divider {

        margin: 15px 5px;
    }


    .main-content {

        margin-left: 68px;

        padding: 88px 18px 75px;
    }


    .footer {

        left: 68px;
    }


    .user-name {

        display: none;
    }


    .user-role {

        display: none;
    }


    .welcome-section {

        padding: 22px 21px;
    }


    .welcome-section h1 {

        font-size: 23px;
    }


    .page-header {

        align-items: flex-start;

        flex-direction: column;
    }


    .practical-count {

        align-self: flex-start;
    }
}


/* =========================================================
   RESPONSIVE - SMALL MOBILE
   ========================================================= */

@media (max-width: 576px) {

    .main-content {

        padding-left: 12px;

        padding-right: 12px;
    }


    .welcome-section {

        padding: 20px 18px;
    }


    .welcome-section h1 {

        font-size: 21px;
    }


    .welcome-section p {

        font-size: 12px;
    }


    .overall-card {

        padding: 17px;
    }


    .section-body {

        padding: 14px;
    }


    .section-header {

        padding: 15px;
    }


    .section-header h5 {

        font-size: 14px;
    }


    .section-header small {

        display: none;
    }


    .practical-card {

        padding: 17px;
    }


    .summary-card {

        min-height: 92px;

        padding: 15px;
    }


    .summary-icon {

        width: 39px;

        height: 39px;

        font-size: 17px;
    }


    .summary-number {

        font-size: 21px;
    }


    .submission-area {

        flex-direction: column;

        align-items: flex-start;

        gap: 4px;
    }


    .footer {

        font-size: 9px;

        padding: 0 8px;

        text-align: center;
    }
}


/* =========================================================
   VERY SMALL SCREENS
   ========================================================= */

@media (max-width: 400px) {

    .sidebar {

        width: 58px;
    }


    .main-content {

        margin-left: 58px;
    }


    .footer {

        left: 58px;
    }


    .top-header {

        height: 62px;
    }


    .sidebar {

        top: 62px;
    }


    .main-content {

        padding-top: 82px;
    }


    .brand-icon {

        width: 36px;

        height: 36px;

        font-size: 18px;
    }


    .user-avatar {

        width: 35px;

        height: 35px;
    }
}

</style>

</head>


<body>


<!-- =========================================================
     HEADER
     ========================================================= -->

<header class="top-header">

    <div class="brand">

        <div class="brand-icon">

            <i class="bi bi-flask"></i>

        </div>


        <div class="brand-text">

            Food Process Practical Learning System

            <small>
                Laboratory Practical Management
            </small>

        </div>

    </div>


    <div class="user-area">

        <div>

            <div class="user-name">

                <?php
                echo htmlspecialchars($user_name);
                ?>

            </div>

            <span class="user-role">
                Student
            </span>

        </div>


        <div class="user-avatar">

            <i class="bi bi-person"></i>

        </div>

    </div>

</header>



<!-- =========================================================
     LEFT MAIN MENU
     ========================================================= -->

<aside class="sidebar">

    <div class="sidebar-heading">

        Main Menu

    </div>


    <a
        href="dashboard.php"
        class="nav-link-custom active"
    >

        <i class="bi bi-grid-1x2"></i>

        <span>
            Dashboard
        </span>

    </a>


    <a
        href="#profileModal"
        class="nav-link-custom"
        data-bs-toggle="modal"
    >

        <i class="bi bi-person"></i>

        <span>
            My Profile
        </span>

    </a>


    <a
        href="#emergencyModal"
        class="nav-link-custom"
        data-bs-toggle="modal"
    >

        <i class="bi bi-telephone"></i>

        <span>
            Emergency Contacts
        </span>

    </a>


    <a
        href="#safetyModal"
        class="nav-link-custom"
        data-bs-toggle="modal"
    >

        <i class="bi bi-shield-check"></i>

        <span>
            Safety Instructions
        </span>

    </a>


    <a
        href="#programmeModal"
        class="nav-link-custom"
        data-bs-toggle="modal"
    >

        <i class="bi bi-book"></i>

        <span>
            Programme
        </span>

    </a>


    <a
        href="submit_results.php"
        class="nav-link-custom"
    >

        <i class="bi bi-send"></i>

        <span>
            Submit Results
        </span>

    </a>


    <div class="nav-section-divider"></div>


    <div class="sidebar-heading">

        Account

    </div>


    <a
        href="settings.php"
        class="nav-link-custom"
    >

        <i class="bi bi-gear"></i>

        <span>
            Settings
        </span>

    </a>


    <a
        href="logout.php"
        class="nav-link-custom logout-link"
    >

        <i class="bi bi-box-arrow-right"></i>

        <span>
            Log Out
        </span>

    </a>

</aside>



<!-- =========================================================
     MAIN CONTENT
     ========================================================= -->

<main class="main-content">


    <!-- =====================================================
         WELCOME + OVERALL PROGRESS
         ===================================================== -->

    <section class="welcome-section">

        <div class="welcome-content">

            <div class="welcome-label">

                Student Learning Dashboard

            </div>


            <h1>

                Welcome,
                <?php
                echo htmlspecialchars($user_name);
                ?>.

            </h1>


            <p>

                Continue your laboratory practical activities,
                complete the required tasks, run simulations,
                and track your learning progress.

            </p>


            <div class="overall-card">

                <div class="overall-top">

                    <span class="overall-title">

                        Overall Practical Progress

                    </span>


                    <span class="overall-percentage">

                        <?php
                        echo $overall_progress;
                        ?>%

                    </span>

                </div>


                <div class="overall-progress">

                    <div
                        class="overall-progress-bar"
                        style="width: <?php echo $overall_progress; ?>%;"
                    ></div>

                </div>


                <div class="overall-bottom">

                    <span>

                        <?php
                        echo $completed_count;
                        ?>
                        of
                        <?php
                        echo $total_practicals;
                        ?>
                        practicals completed

                    </span>


                    <span>

                        <?php
                        echo $in_progress_count;
                        ?>
                        in progress

                    </span>

                </div>

            </div>

        </div>

    </section>



    <!-- =====================================================
         PAGE HEADER
         ===================================================== -->

    <div class="page-header">

        <div>

            <h2>
                Laboratory Practicals
            </h2>


            <p>
                Select a practical to start, continue, or review your work.
            </p>

        </div>


        <div class="practical-count">

            <i class="bi bi-journal-check me-1"></i>

            <?php
            echo $total_practicals;
            ?>
            Practicals

        </div>

    </div>



    <!-- =====================================================
         PRACTICAL ACTIVITIES
         ===================================================== -->

    <div class="section-card">

        <div class="section-header">

            <h5>

                <i class="bi bi-journal-text me-2"></i>

                Practical Activities

            </h5>


            <small>

                Laboratory learning programme

            </small>

        </div>


        <div class="section-body">

            <div class="row g-3">


                <?php for ($i = 1; $i <= 5; $i++): ?>


                    <?php

                    $card_class = "";

                    if ($practical_status[$i] === "completed") {

                        $card_class = "card-completed";

                    } elseif ($practical_status[$i] === "in_progress") {

                        $card_class = "card-progress";

                    } else {

                        $card_class = "card-not-started";
                    }

                    ?>


                    <div class="col-md-6">


                        <div
                            class="practical-card <?php echo $card_class; ?>"
                        >


                            <!-- PRACTICAL TOP -->

                            <div class="practical-top">


                                <div class="practical-icon">

                                    <i
                                        class="bi <?php echo $practicals[$i]['icon']; ?>"
                                    ></i>

                                </div>


                                <div class="practical-index">

                                    Practical
                                    <?php
                                    echo str_pad($i, 2, "0", STR_PAD_LEFT);
                                    ?>

                                </div>

                            </div>



                            <!-- STATUS -->

                            <span
                                class="status <?php echo getStatusClass($practical_status[$i]); ?>"
                            >

                                <i
                                    class="bi <?php echo getStatusIcon($practical_status[$i]); ?>"
                                ></i>

                                <?php

                                echo getStatusText(
                                    $practical_status[$i]
                                );

                                ?>

                            </span>



                            <!-- TITLE -->

                            <h6>

                                <?php

                                echo htmlspecialchars(
                                    $practicals[$i]["title"]
                                );

                                ?>

                            </h6>



                            <!-- DESCRIPTION -->

                            <p>

                                <?php

                                echo htmlspecialchars(
                                    $practicals[$i]["description"]
                                );

                                ?>

                            </p>



                            <!-- SUBMISSION STATUS -->

                            <div class="submission-area">


                                <span class="submission-label">

                                    Submission Status

                                </span>


                                <?php if ($submission_status[$i]): ?>

                                    <span
                                        class="submission-status submission-complete"
                                    >

                                        <i class="bi bi-check-circle-fill"></i>

                                        <?php

                                        if ($i === 1) {

                                            echo "Orientation Confirmed";

                                        } else {

                                            echo "Submitted";

                                        }

                                        ?>

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="submission-status submission-pending"
                                    >

                                        <i class="bi bi-clock"></i>

                                        Not Submitted

                                    </span>

                                <?php endif; ?>


                            </div>



                            <!-- ACTIVITY PROGRESS -->

                            <div class="progress-area">


                                <div class="progress-text">

                                    <span>

                                        Activity Progress

                                    </span>


                                    <span>

                                        <?php
                                        echo $practical_progress[$i];
                                        ?>%

                                    </span>

                                </div>


                                <div class="progress">

                                    <div
                                        class="progress-bar"
                                        style="
                                            width:
                                            <?php
                                            echo $practical_progress[$i];
                                            ?>%
                                        "
                                    ></div>

                                </div>

                            </div>



                            <!-- PRACTICAL BUTTON -->

                            <a
                                href="<?php
                                echo $practicals[$i]["link"];
                                ?>"
                                class="btn-practical"
                            >

                                <?php

                                if (
                                    $practical_status[$i]
                                    === "completed"
                                ) {

                                    echo '
                                        <i class="bi bi-eye"></i>
                                        Review Practical
                                    ';

                                } elseif (
                                    $practical_status[$i]
                                    === "in_progress"
                                ) {

                                    echo '
                                        <i class="bi bi-arrow-right"></i>
                                        Continue Practical
                                    ';

                                } else {

                                    echo '
                                        <i class="bi bi-play-fill"></i>
                                        Start Practical
                                    ';
                                }

                                ?>

                            </a>


                        </div>

                    </div>


                <?php endfor; ?>


            </div>

        </div>

    </div>



    <!-- =====================================================
         DASHBOARD SUMMARY
         ===================================================== -->

    <div class="summary-section">


        <div class="row g-3">


            <!-- TOTAL PRACTICALS -->

            <div class="col-6 col-lg-3">

                <div class="summary-card">

                    <div class="summary-icon">

                        <i class="bi bi-journal-check"></i>

                    </div>


                    <div class="summary-content">

                        <small>
                            Total Practicals
                        </small>


                        <div class="summary-number">

                            <?php
                            echo $total_practicals;
                            ?>

                        </div>


                        <div class="summary-description">

                            Available

                        </div>

                    </div>

                </div>

            </div>



            <!-- COMPLETED -->

            <div class="col-6 col-lg-3">

                <div class="summary-card">

                    <div class="summary-icon">

                        <i class="bi bi-check-circle"></i>

                    </div>


                    <div class="summary-content">

                        <small>
                            Completed
                        </small>


                        <div class="summary-number">

                            <?php
                            echo $completed_count;
                            ?>

                        </div>


                        <div class="summary-description">

                            Practicals finished

                        </div>

                    </div>

                </div>

            </div>



            <!-- IN PROGRESS -->

            <div class="col-6 col-lg-3">

                <div class="summary-card">

                    <div class="summary-icon">

                        <i class="bi bi-clock-history"></i>

                    </div>


                    <div class="summary-content">

                        <small>
                            In Progress
                        </small>


                        <div class="summary-number">

                            <?php
                            echo $in_progress_count;
                            ?>

                        </div>


                        <div class="summary-description">

                            Currently working

                        </div>

                    </div>

                </div>

            </div>



            <!-- OVERALL PROGRESS -->

            <div class="col-6 col-lg-3">

                <div class="summary-card">

                    <div class="summary-icon">

                        <i class="bi bi-bar-chart"></i>

                    </div>


                    <div class="summary-content">

                        <small>
                            Overall Progress
                        </small>


                        <div class="summary-number">

                            <?php
                            echo $overall_progress;
                            ?>%

                        </div>


                        <div class="summary-description">

                            Learning progress

                        </div>

                    </div>

                </div>

            </div>


        </div>

    </div>


</main>



<!-- =========================================================
     PROFILE MODAL
     ========================================================= -->

<div
    class="modal fade"
    id="profileModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-person me-2"></i>

                    My Profile

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">


                <div
                    class="d-flex align-items-center gap-3 mb-4"
                >

                    <div class="info-icon">

                        <i class="bi bi-person"></i>

                    </div>


                    <div>

                        <h6 class="mb-1">

                            <?php
                            echo htmlspecialchars($user_name);
                            ?>

                        </h6>


                        <small class="text-muted">

                            Laboratory Practical Student

                        </small>

                    </div>

                </div>


                <div class="border-top pt-3">


                    <p class="mb-2">

                        <strong>
                            Student ID:
                        </strong>

                        <?php
                        echo $user_id;
                        ?>

                    </p>


                    <p class="mb-0">

                        <strong>
                            Programme:
                        </strong>

                        Food Processing Practical Programme

                    </p>


                </div>


            </div>

        </div>

    </div>

</div>



<!-- =========================================================
     EMERGENCY CONTACTS MODAL
     ========================================================= -->

<div
    class="modal fade"
    id="emergencyModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-telephone me-2"></i>

                    Emergency Contacts

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">


                <div class="alert alert-warning small">

                    <i class="bi bi-exclamation-triangle me-2"></i>

                    In an emergency, immediately inform the
                    laboratory technician, lecturer, or responsible
                    laboratory staff.

                </div>


                <ul class="info-list">

                    <li>

                        Laboratory Technician —
                        Contact through the laboratory office.

                    </li>


                    <li>

                        Responsible Lecturer —
                        Contact through the department office.

                    </li>


                    <li>

                        Emergency Medical Services —
                        Use the official emergency number provided
                        by your institution.

                    </li>

                </ul>


            </div>

        </div>

    </div>

</div>



<!-- =========================================================
     SAFETY INSTRUCTIONS MODAL
     ========================================================= -->

<div
    class="modal fade"
    id="safetyModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-shield-check me-2"></i>

                    Laboratory Safety Instructions

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">


                <ul class="info-list">

                    <li>

                        Follow laboratory rules and instructions
                        from the responsible staff.

                    </li>


                    <li>

                        Wear the required personal protective
                        equipment.

                    </li>


                    <li>

                        Keep the working area clean and organized.

                    </li>


                    <li>

                        Handle laboratory equipment carefully and
                        only as instructed.

                    </li>


                    <li>

                        Report accidents, damaged equipment, or
                        unsafe conditions immediately.

                    </li>


                    <li>

                        Do not operate laboratory equipment without
                        proper authorization.

                    </li>


                    <li>

                        Know the location of laboratory emergency
                        and safety equipment.

                    </li>

                </ul>


            </div>

        </div>

    </div>

</div>



<!-- =========================================================
     PROGRAMME MODAL
     ========================================================= -->

<div
    class="modal fade"
    id="programmeModal"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="bi bi-book me-2"></i>

                    Practical Programme

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <div class="modal-body">


                <p class="text-muted small">

                    Laboratory practical activities included
                    in this learning system:

                </p>


                <ol class="info-list">

                    <li>

                        Laboratory Orientation

                    </li>


                    <li>

                        Physical Separation:
                        Centrifugation and Sieve Analysis

                    </li>


                    <li>

                        Thermal Processing in Foods:
                        Heat Penetration

                    </li>


                    <li>

                        Drying:
                        Spray Drying and Freeze Drying

                    </li>

                    <li>

                        Filtration and Separation

                    </li>

                </ol>


            </div>

        </div>

    </div>

</div>



<!-- =========================================================
     FOOTER
     ========================================================= -->

<footer class="footer">

    Food Process Practical Learning System

    &nbsp; | &nbsp;

    Laboratory Practical Management

</footer>



<!-- Bootstrap JavaScript -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>