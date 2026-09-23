<?php
require_once "../config.php";

/* =========================================================
   ACCESS CONTROL
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$current_user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, full_name, username, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $current_user_id);
$stmt->execute();

$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (
    !$current_user ||
    !in_array($current_user['role'], ['admin', 'lecturer'], true)
) {
    http_response_code(403);
    exit("Access Denied");
}


/* =========================================================
   FILTER
========================================================= */

$filter = isset($_GET['practical'])
    ? (int) $_GET['practical']
    : 0;

if (!in_array($filter, [0, 2, 3, 4], true)) {
    $filter = 0;
}


/* =========================================================
   PRACTICAL INFORMATION
========================================================= */

$practical_names = [
    2 => "Physical Separation",
    3 => "Thermal Processing",
    4 => "Drying"
];

$practical_icons = [
    2 => "bi-filter-circle",
    3 => "bi-thermometer-half",
    4 => "bi-droplet-half"
];


/* =========================================================
   STATISTICS
========================================================= */

$total_simulations = 0;
$p2_count = 0;
$p3_count = 0;
$p4_count = 0;

$stats_query = mysqli_query(
    $conn,
    "
    SELECT
        COUNT(*) AS total,
        SUM(practical_number = 2) AS p2,
        SUM(practical_number = 3) AS p3,
        SUM(practical_number = 4) AS p4
    FROM simulation_results
    WHERE practical_number IN (2,3,4)
    "
);

if ($stats_query) {

    $stats = mysqli_fetch_assoc($stats_query);

    $total_simulations = (int) ($stats['total'] ?? 0);
    $p2_count = (int) ($stats['p2'] ?? 0);
    $p3_count = (int) ($stats['p3'] ?? 0);
    $p4_count = (int) ($stats['p4'] ?? 0);
}


/* =========================================================
   GET SIMULATION RESULTS
========================================================= */

$simulations = [];

if ($filter === 0) {

    $stmt = $conn->prepare("
        SELECT
            sr.id,
            sr.user_id,
            sr.practical_number,
            sr.simulation_type,
            sr.input_data,
            sr.result_data,
            sr.created_at,
            u.full_name,
            u.username
        FROM simulation_results sr
        INNER JOIN users u
            ON sr.user_id = u.id
        WHERE sr.practical_number IN (2,3,4)
        ORDER BY sr.created_at DESC
    ");

} else {

    $stmt = $conn->prepare("
        SELECT
            sr.id,
            sr.user_id,
            sr.practical_number,
            sr.simulation_type,
            sr.input_data,
            sr.result_data,
            sr.created_at,
            u.full_name,
            u.username
        FROM simulation_results sr
        INNER JOIN users u
            ON sr.user_id = u.id
        WHERE sr.practical_number = ?
        ORDER BY sr.created_at DESC
    ");

    $stmt->bind_param("i", $filter);
}

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $input_array = json_decode(
        $row['input_data'] ?? '{}',
        true
    );

    $result_array = json_decode(
        $row['result_data'] ?? '{}',
        true
    );

    if (!is_array($input_array)) {
        $input_array = [];
    }

    if (!is_array($result_array)) {
        $result_array = [];
    }

    $row['input_array'] = $input_array;
    $row['result_array'] = $result_array;

    $simulations[] = $row;
}

$stmt->close();


/* =========================================================
   HELPER FUNCTIONS FOR PHP
========================================================= */

function json_for_html($data)
{
    return htmlspecialchars(
        json_encode(
            $data,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_AMP |
            JSON_HEX_QUOT
        ),
        ENT_QUOTES,
        'UTF-8'
    );
}

function student_initial($name)
{
    $name = trim((string)$name);

    if ($name === '') {
        return 'S';
    }

    return strtoupper(substr($name, 0, 1));
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
    Simulation Results | Food Process System
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet"
>

<script
    src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"
></script>


<style>

:root {
    --lab-blue: #1f5f75;
    --lab-dark: #17495a;
    --background: #f4f6f8;
    --border: #d9dee3;
    --text: #263238;
    --muted: #74818a;
    --white: #ffffff;
    --success: #2e7d32;
    --warning: #b7791f;
    --danger: #c62828;
    --shadow: 0 5px 18px rgba(23,73,90,0.08);
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: var(--background);
    color: var(--text);
    font-family:
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        Arial,
        sans-serif;
}

a {
    text-decoration: none;
}

.admin-wrapper {
    min-height: 100vh;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;

    width: 245px;
    min-width: 245px;

    background: var(--lab-dark);
    color: #fff;

    z-index: 1050;

    overflow-y: auto;

    transition: transform .25s ease;
}

.sidebar-brand {
    padding: 25px 20px 22px;
    border-bottom: 1px solid rgba(255,255,255,.1);
}

.brand-icon {
    width: 42px;
    height: 42px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    background: #fff;
    color: var(--lab-blue);

    border-radius: 10px;

    font-size: 21px;

    margin-right: 10px;

    vertical-align: middle;
}

.brand-title {
    display: inline-block;
    vertical-align: middle;

    font-size: 16px;
    font-weight: 700;

    line-height: 1.25;
}

.brand-subtitle {
    display: block;

    margin-left: 53px;
    margin-top: 5px;

    font-size: 11px;

    color: rgba(255,255,255,.65);
}

.sidebar-section {
    padding: 22px 15px 8px;
}

.sidebar-section-title {
    padding: 0 12px;
    margin-bottom: 8px;

    font-size: 10px;
    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: 1.2px;

    color: rgba(255,255,255,.45);
}

.sidebar-link {
    display: flex;
    align-items: center;

    gap: 12px;

    padding: 11px 12px;
    margin-bottom: 3px;

    border-radius: 8px;

    color: rgba(255,255,255,.78);

    font-size: 14px;
    font-weight: 500;

    transition: .2s ease;
}

.sidebar-link i {
    width: 20px;

    text-align: center;

    font-size: 17px;
}

.sidebar-link:hover {
    color: #fff;
    background: rgba(255,255,255,.09);
}

.sidebar-link.active {
    color: var(--lab-dark);
    background: #fff;
    font-weight: 600;
}

.sidebar-divider {
    height: 1px;

    margin: 14px 20px;

    background: rgba(255,255,255,.1);
}


/* =========================================================
   MAIN
========================================================= */

.main-content {
    margin-left: 245px;

    width: calc(100% - 245px);

    min-height: 100vh;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {
    height: 72px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 30px;

    background: #fff;

    border-bottom: 1px solid var(--border);

    position: sticky;
    top: 0;

    z-index: 1000;
}

.topbar-left {
    display: flex;
    align-items: center;

    gap: 14px;
}

.mobile-menu-btn {
    display: none;

    width: 40px;
    height: 40px;

    border: 1px solid var(--border);

    background: #fff;

    color: var(--lab-dark);

    border-radius: 8px;

    align-items: center;
    justify-content: center;

    font-size: 20px;
}

.page-title {
    margin: 0;

    font-size: 21px;
    font-weight: 700;

    color: var(--lab-dark);
}

.page-subtitle {
    margin-top: 2px;

    font-size: 12px;

    color: var(--muted);
}

.topbar-right {
    display: flex;
    align-items: center;

    gap: 15px;
}

.student-view-link {
    padding: 8px 12px;

    color: var(--lab-blue);

    background: #f8fbfc;

    border: 1px solid #c9dce3;

    border-radius: 7px;

    font-size: 13px;
    font-weight: 600;
}

.student-view-link:hover {
    color: var(--lab-dark);
    background: #edf6f8;
}

.user-mini {
    display: flex;
    align-items: center;

    gap: 9px;
}

.user-avatar {
    width: 38px;
    height: 38px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: var(--lab-blue);
    color: #fff;

    font-size: 14px;
    font-weight: 700;
}

.user-name {
    font-size: 13px;
    font-weight: 600;
}

.user-role {
    font-size: 11px;
    color: var(--muted);
}


/* =========================================================
   PAGE
========================================================= */

.page-body {
    padding: 30px;
}

.page-heading {
    margin-bottom: 25px;
}

.page-heading h1 {
    margin: 0 0 5px;

    font-size: 25px;
    font-weight: 700;

    color: var(--lab-dark);
}

.page-heading p {
    margin: 0;

    font-size: 14px;

    color: var(--muted);
}


/* =========================================================
   STATISTICS
========================================================= */

.stat-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 18px;

    margin-bottom: 25px;
}

.stat-card {
    padding: 20px;

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 10px;

    box-shadow: var(--shadow);
}

.stat-card-top {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;
}

.stat-icon {
    width: 44px;
    height: 44px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background: #edf5f7;

    color: var(--lab-blue);

    font-size: 20px;
}

.stat-label {
    font-size: 13px;
    color: var(--muted);
}

.stat-number {
    margin-top: 7px;

    font-size: 27px;
    font-weight: 700;

    color: var(--lab-dark);
}


/* =========================================================
   FILTER
========================================================= */

.filter-card {
    padding: 18px 20px;

    margin-bottom: 22px;

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 10px;

    box-shadow: var(--shadow);
}

.filter-row {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 20px;
}

.filter-title {
    margin-bottom: 3px;

    font-size: 14px;
    font-weight: 700;

    color: var(--lab-dark);
}

.filter-description {
    font-size: 12px;
    color: var(--muted);
}

.filter-form {
    display: flex;

    align-items: center;

    gap: 10px;
}

.filter-form select {
    min-width: 240px;

    height: 40px;

    padding: 0 12px;

    border: 1px solid var(--border);

    border-radius: 7px;

    background: #fff;

    color: var(--text);

    font-size: 13px;
}

.filter-form select:focus {
    outline: none;

    border-color: var(--lab-blue);

    box-shadow:
        0 0 0 3px
        rgba(31,95,117,.1);
}

.filter-btn {
    height: 40px;

    padding: 0 17px;

    border: none;

    border-radius: 7px;

    background: var(--lab-blue);

    color: #fff;

    font-size: 13px;
    font-weight: 600;
}

.filter-btn:hover {
    background: var(--lab-dark);
}


/* =========================================================
   CONTENT CARD
========================================================= */

.content-card {
    overflow: hidden;

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 10px;

    box-shadow: var(--shadow);
}

.content-card-header {
    display: flex;

    align-items: center;
    justify-content: space-between;

    padding: 18px 22px;

    border-bottom: 1px solid var(--border);
}

.content-card-title {
    margin: 0;

    font-size: 16px;
    font-weight: 700;

    color: var(--lab-dark);
}

.content-card-subtitle {
    margin: 4px 0 0;

    font-size: 12px;

    color: var(--muted);
}

.results-count {
    padding: 6px 11px;

    border-radius: 20px;

    background: #edf5f7;

    color: var(--lab-blue);

    font-size: 12px;
    font-weight: 600;
}


/* =========================================================
   TABLE
========================================================= */

.table-responsive {
    overflow-x: auto;
}

.results-table {
    width: 100%;

    margin: 0;

    border-collapse: collapse;
}

.results-table th {
    padding: 13px 16px;

    background: #f8fafb;

    border-bottom: 1px solid var(--border);

    color: #64727a;

    font-size: 11px;
    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: .45px;

    white-space: nowrap;
}

.results-table td {
    padding: 15px 16px;

    border-bottom: 1px solid #edf0f2;

    vertical-align: middle;

    font-size: 13px;
}

.results-table tbody tr:last-child td {
    border-bottom: none;
}

.results-table tbody tr:hover {
    background: #fbfcfc;
}

.student-cell {
    display: flex;

    align-items: center;

    gap: 10px;
}

.student-avatar {
    width: 35px;
    height: 35px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #e8f2f5;

    color: var(--lab-blue);

    font-size: 12px;
    font-weight: 700;
}

.student-name {
    font-weight: 600;
}

.student-username {
    margin-top: 2px;

    color: var(--muted);

    font-size: 11px;
}

.practical-badge {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 6px 9px;

    border-radius: 6px;

    background: #edf5f7;

    color: var(--lab-blue);

    font-size: 11px;
    font-weight: 600;
}

.practical-name {
    margin-top: 4px;

    color: var(--muted);

    font-size: 11px;
}

.simulation-type {
    font-size: 12px;
    font-weight: 500;
}

.date-text {
    color: var(--muted);

    font-size: 12px;

    white-space: nowrap;
}

.view-btn {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 7px 11px;

    border: 1px solid #c9dce3;

    border-radius: 6px;

    background: #f8fbfc;

    color: var(--lab-blue);

    font-size: 12px;
    font-weight: 600;
}

.view-btn:hover {
    background: #edf5f7;

    color: var(--lab-dark);
}


/* =========================================================
   EMPTY
========================================================= */

.empty-state {
    padding: 70px 25px;

    text-align: center;
}

.empty-icon {
    width: 70px;
    height: 70px;

    margin: 0 auto 18px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #edf5f7;

    color: var(--lab-blue);

    font-size: 30px;
}

.empty-state h4 {
    margin-bottom: 7px;

    font-size: 18px;

    color: var(--lab-dark);
}

.empty-state p {
    margin: 0;

    color: var(--muted);

    font-size: 13px;
}


/* =========================================================
   MODAL
========================================================= */

.modal-content {
    overflow: hidden;

    border: none;

    border-radius: 12px;

    box-shadow:
        0 18px 55px
        rgba(0,0,0,.18);
}

.modal-header {
    padding: 18px 22px;

    background: var(--lab-dark);

    color: #fff;

    border: none;
}

.modal-title {
    font-size: 17px;
    font-weight: 700;
}

.modal-header .btn-close {
    filter: brightness(0) invert(1);
}

.modal-body {
    padding: 25px;

    background: #fff;
}

.modal-footer {
    padding: 14px 20px;

    background: #fafbfc;

    border-top: 1px solid var(--border);
}


/* =========================================================
   META
========================================================= */

.modal-meta {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 12px;

    margin-bottom: 25px;
}

.meta-box {
    padding: 12px;

    background: #fafbfc;

    border: 1px solid var(--border);

    border-radius: 8px;
}

.meta-label {
    margin-bottom: 5px;

    color: var(--muted);

    font-size: 10px;
    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .5px;
}

.meta-value {
    color: var(--lab-dark);

    font-size: 13px;
    font-weight: 600;
}


/* =========================================================
   RESULT SECTIONS
========================================================= */

.result-section {
    margin-bottom: 25px;
}

.section-heading {
    display: flex;

    align-items: center;

    gap: 8px;

    padding-bottom: 10px;

    margin-bottom: 13px;

    border-bottom: 1px solid var(--border);
}

.section-heading i {
    color: var(--lab-blue);

    font-size: 17px;
}

.section-heading h6 {
    margin: 0;

    color: var(--lab-dark);

    font-size: 14px;
    font-weight: 700;
}

.value-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 10px;
}

.value-box {
    padding: 11px 12px;

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 7px;
}

.value-box-label {
    margin-bottom: 4px;

    color: var(--muted);

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .35px;
}

.value-box-value {
    color: var(--text);

    font-size: 13px;
    font-weight: 600;

    word-break: break-word;
}


/* =========================================================
   CHART
========================================================= */

.chart-block {
    padding: 18px;

    margin-top: 20px;

    background: #fff;

    border: 1px solid var(--border);

    border-radius: 9px;
}

.chart-block h6 {
    margin: 0 0 15px;

    color: var(--lab-dark);

    font-size: 13px;
    font-weight: 700;
}

.chart-container {
    position: relative;

    width: 100%;
    height: 330px;
}

.chart-message {
    display: none;

    padding: 25px 18px;

    border: 1px dashed var(--border);

    border-radius: 8px;

    background: #fafbfc;

    color: var(--muted);

    text-align: center;

    font-size: 13px;

    line-height: 1.6;
}

.chart-message i {
    display: block;

    margin-bottom: 8px;

    color: #9aa7ad;

    font-size: 25px;
}


/* =========================================================
   RAW DATA
========================================================= */

.raw-data {
    overflow-x: auto;

    padding: 15px;

    background: #f7f9fa;

    border: 1px solid var(--border);

    border-radius: 8px;
}

.raw-data pre {
    margin: 0;

    color: #39474e;

    font-family:
        Consolas,
        Monaco,
        monospace;

    font-size: 11px;

    line-height: 1.6;

    white-space: pre-wrap;

    word-break: break-word;
}


/* =========================================================
   BUTTONS
========================================================= */

.close-modal-btn {
    padding: 8px 15px;

    border: 1px solid var(--border);

    border-radius: 7px;

    background: #fff;

    color: var(--text);

    font-size: 12px;
    font-weight: 600;
}

.close-modal-btn:hover {
    background: #f4f6f8;
}


/* =========================================================
   SIDEBAR OVERLAY
========================================================= */

.sidebar-overlay {
    display: none;

    position: fixed;

    inset: 0;

    z-index: 1040;

    background: rgba(0,0,0,.35);
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1200px) {

    .stat-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .value-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }
}

@media (max-width: 900px) {

    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .sidebar-overlay.show {
        display: block;
    }

    .main-content {
        margin-left: 0;

        width: 100%;
    }

    .mobile-menu-btn {
        display: inline-flex;
    }

    .topbar {
        padding: 0 20px;
    }

    .page-body {
        padding: 22px 20px;
    }

    .student-view-link {
        display: none;
    }
}

@media (max-width: 700px) {

    .filter-row {
        flex-direction: column;

        align-items: stretch;
    }

    .filter-form {
        width: 100%;
    }

    .filter-form select {
        flex: 1;

        min-width: 0;
    }

    .modal-meta {
        grid-template-columns: 1fr;
    }

    .value-grid {
        grid-template-columns: 1fr;
    }

    .chart-container {
        height: 280px;
    }

    .user-name,
    .user-role {
        display: none;
    }
}

@media (max-width: 576px) {

    .topbar {
        height: 65px;

        padding: 0 14px;
    }

    .page-title {
        font-size: 17px;
    }

    .page-subtitle {
        display: none;
    }

    .page-body {
        padding: 18px 14px;
    }

    .page-heading h1 {
        font-size: 21px;
    }

    .stat-grid {
        grid-template-columns: 1fr;

        gap: 12px;
    }

    .filter-card {
        padding: 15px;
    }

    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-form select,
    .filter-btn {
        width: 100%;
    }

    .content-card-header {
        padding: 15px;
    }

    .results-table th,
    .results-table td {
        padding: 11px 12px;
    }

    .modal-body {
        padding: 18px;
    }

    .chart-block {
        padding: 13px;
    }

    .chart-container {
        height: 240px;
    }
}

</style>

</head>


<body>


<div class="admin-wrapper">


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside
    class="sidebar"
    id="sidebar"
>

    <div class="sidebar-brand">

        <span class="brand-icon">
            <i class="bi bi-flask"></i>
        </span>

        <span class="brand-title">
            Food Process<br>
            Practical System
        </span>

        <span class="brand-subtitle">
            Administration
        </span>

    </div>


    <div class="sidebar-section">

        <div class="sidebar-section-title">
            Administration
        </div>


        <a
            href="dashboard.php"
            class="sidebar-link"
        >
            <i class="bi bi-grid-1x2"></i>
            <span>Dashboard</span>
        </a>


        <a
            href="students.php"
            class="sidebar-link"
        >
            <i class="bi bi-people"></i>
            <span>Students</span>
        </a>


        <a
            href="submissions.php"
            class="sidebar-link"
        >
            <i class="bi bi-journal-check"></i>
            <span>Submissions</span>
        </a>


        <a
            href="simulation_results.php"
            class="sidebar-link active"
        >
            <i class="bi bi-bar-chart-line"></i>
            <span>Simulation Results</span>
        </a>


        <a
            href="register.php"
            class="sidebar-link"
        >
            <i class="bi bi-person-plus"></i>
            <span>Register Admin</span>
        </a>

    </div>


    <div class="sidebar-divider"></div>


    <div class="sidebar-section">

        <div class="sidebar-section-title">
            Account
        </div>


        <a
            href="settings.php"
            class="sidebar-link"
        >
            <i class="bi bi-gear"></i>
            <span>Settings</span>
        </a>


        <a
            href="../logout.php"
            class="sidebar-link"
        >
            <i class="bi bi-box-arrow-right"></i>
            <span>Log Out</span>
        </a>

    </div>

</aside>


<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="main-content">


<header class="topbar">

    <div class="topbar-left">

        <button
            type="button"
            class="mobile-menu-btn"
            id="mobileMenuBtn"
        >
            <i class="bi bi-list"></i>
        </button>


        <div>

            <h1 class="page-title">
                Simulation Results
            </h1>

            <div class="page-subtitle">
                Review practical simulation records
            </div>

        </div>

    </div>


    <div class="topbar-right">

        <a
            href="../dashboard.php"
            class="student-view-link"
        >
            <i class="bi bi-mortarboard me-1"></i>
            Student View
        </a>


        <div class="user-mini">

            <div class="user-avatar">

                <?= htmlspecialchars(
                    student_initial(
                        $current_user['full_name'] ?? 'A'
                    )
                ) ?>

            </div>


            <div>

                <div class="user-name">

                    <?= htmlspecialchars(
                        $current_user['full_name']
                        ?? 'Administrator'
                    ) ?>

                </div>

                <div class="user-role">

                    <?= ucfirst(
                        htmlspecialchars(
                            $current_user['role']
                            ?? 'admin'
                        )
                    ) ?>

                </div>

            </div>

        </div>

    </div>

</header>


<section class="page-body">


<!-- =========================================================
     PAGE HEADING
========================================================= -->

<div class="page-heading">

    <h1>
        Simulation Results
    </h1>

    <p>
        View and analyze simulation results submitted by
        students across Practical 2, Practical 3 and Practical 4.
    </p>

</div>


<!-- =========================================================
     STATISTICS
========================================================= -->

<div class="stat-grid">


<div class="stat-card">

    <div class="stat-card-top">

        <div>

            <div class="stat-label">
                Total Simulations
            </div>

            <div class="stat-number">
                <?= number_format($total_simulations) ?>
            </div>

        </div>

        <div class="stat-icon">
            <i class="bi bi-bar-chart-line"></i>
        </div>

    </div>

</div>


<div class="stat-card">

    <div class="stat-card-top">

        <div>

            <div class="stat-label">
                Practical 2
            </div>

            <div class="stat-number">
                <?= number_format($p2_count) ?>
            </div>

        </div>

        <div class="stat-icon">
            <i class="bi bi-filter-circle"></i>
        </div>

    </div>

</div>


<div class="stat-card">

    <div class="stat-card-top">

        <div>

            <div class="stat-label">
                Practical 3
            </div>

            <div class="stat-number">
                <?= number_format($p3_count) ?>
            </div>

        </div>

        <div class="stat-icon">
            <i class="bi bi-thermometer-half"></i>
        </div>

    </div>

</div>


<div class="stat-card">

    <div class="stat-card-top">

        <div>

            <div class="stat-label">
                Practical 4
            </div>

            <div class="stat-number">
                <?= number_format($p4_count) ?>
            </div>

        </div>

        <div class="stat-icon">
            <i class="bi bi-droplet-half"></i>
        </div>

    </div>

</div>


</div>


<!-- =========================================================
     FILTER
========================================================= -->

<div class="filter-card">

    <div class="filter-row">

        <div>

            <div class="filter-title">
                Filter Simulation Results
            </div>

            <div class="filter-description">
                Select a practical to display its simulation records.
            </div>

        </div>


        <form
            method="GET"
            class="filter-form"
        >

            <select
                name="practical"
            >

                <option
                    value="0"
                    <?= $filter === 0 ? 'selected' : '' ?>
                >
                    All Simulations
                </option>


                <option
                    value="2"
                    <?= $filter === 2 ? 'selected' : '' ?>
                >
                    Practical 2 — Physical Separation
                </option>


                <option
                    value="3"
                    <?= $filter === 3 ? 'selected' : '' ?>
                >
                    Practical 3 — Thermal Processing
                </option>


                <option
                    value="4"
                    <?= $filter === 4 ? 'selected' : '' ?>
                >
                    Practical 4 — Drying
                </option>

            </select>


            <button
                type="submit"
                class="filter-btn"
            >

                <i class="bi bi-funnel me-1"></i>

                Apply Filter

            </button>

        </form>

    </div>

</div>


<!-- =========================================================
     RESULTS
========================================================= -->

<div class="content-card">


<div class="content-card-header">

    <div>

        <h2 class="content-card-title">
            Saved Simulation Records
        </h2>

        <p class="content-card-subtitle">
            Click View Result to inspect inputs,
            calculated values and graphs.
        </p>

    </div>


    <span class="results-count">
        <?= count($simulations) ?> record(s)
    </span>

</div>


<?php if (empty($simulations)): ?>


<div class="empty-state">

    <div class="empty-icon">
        <i class="bi bi-bar-chart"></i>
    </div>

    <h4>
        No Simulation Results
    </h4>

    <p>
        There are no saved simulation results matching
        the selected practical.
    </p>

</div>


<?php else: ?>


<div class="table-responsive">

<table class="results-table">

<thead>

<tr>

    <th>
        Student
    </th>

    <th>
        Practical
    </th>

    <th>
        Simulation Type
    </th>

    <th>
        Date
    </th>

    <th>
        Action
    </th>

</tr>

</thead>


<tbody>


<?php foreach ($simulations as $simulation): ?>

<?php

$id =
    (int) $simulation['id'];

$p =
    (int) $simulation['practical_number'];

$modal_id =
    "simulationModal_" . $id;

$full_name =
    $simulation['full_name']
    ?? "Unknown Student";

$username =
    $simulation['username']
    ?? "";

$simulation_type =
    $simulation['simulation_type']
    ?? "Simulation";

$created_at =
    $simulation['created_at']
    ?? "";

$initial =
    student_initial($full_name);

$practical_name =
    $practical_names[$p]
    ?? "Practical " . $p;

$practical_icon =
    $practical_icons[$p]
    ?? "bi-bar-chart";

$input_json =
    json_for_html(
        $simulation['input_array']
    );

$result_json =
    json_for_html(
        $simulation['result_array']
    );

?>


<tr>


<td>

<div class="student-cell">

    <div class="student-avatar">
        <?= htmlspecialchars($initial) ?>
    </div>

    <div>

        <div class="student-name">
            <?= htmlspecialchars($full_name) ?>
        </div>

        <div class="student-username">
            @<?= htmlspecialchars($username) ?>
        </div>

    </div>

</div>

</td>


<td>

<span class="practical-badge">

    <i
        class="bi <?= htmlspecialchars($practical_icon) ?>"
    ></i>

    Practical <?= $p ?>

</span>


<div class="practical-name">
    <?= htmlspecialchars($practical_name) ?>
</div>

</td>


<td>

<span class="simulation-type">

    <?= htmlspecialchars($simulation_type) ?>

</span>

</td>


<td>

<span class="date-text">

<?php

if ($created_at !== '') {

    echo htmlspecialchars(
        date(
            "d M Y, H:i",
            strtotime($created_at)
        )
    );

} else {

    echo "—";

}

?>

</span>

</td>


<td>

<button
    type="button"
    class="view-btn"
    data-bs-toggle="modal"
    data-bs-target="#<?= $modal_id ?>"
>

    <i class="bi bi-eye"></i>

    View Result

</button>

</td>


</tr>


<!-- =========================================================
     MODAL
========================================================= -->

<div
    class="modal fade simulation-modal"
    id="<?= $modal_id ?>"
    tabindex="-1"
    aria-hidden="true"
    data-simulation-id="<?= $id ?>"
    data-practical="<?= $p ?>"
    data-inputs="<?= $input_json ?>"
    data-results="<?= $result_json ?>"
>

<div
    class="modal-dialog modal-xl modal-dialog-scrollable"
>

<div class="modal-content">


<!-- MODAL HEADER -->

<div class="modal-header">

<h5 class="modal-title">

    <i
        class="bi <?= htmlspecialchars($practical_icon) ?>"
    ></i>

    Practical <?= $p ?>

    —

    <?= htmlspecialchars($practical_name) ?>

</h5>


<button
    type="button"
    class="btn-close"
    data-bs-dismiss="modal"
></button>

</div>


<!-- MODAL BODY -->

<div class="modal-body">


<!-- META -->

<div class="modal-meta">


<div class="meta-box">

    <div class="meta-label">
        Student
    </div>

    <div class="meta-value">
        <?= htmlspecialchars($full_name) ?>
    </div>

</div>


<div class="meta-box">

    <div class="meta-label">
        Simulation
    </div>

    <div class="meta-value">
        <?= htmlspecialchars($simulation_type) ?>
    </div>

</div>


<div class="meta-box">

    <div class="meta-label">
        Date
    </div>

    <div class="meta-value">

    <?php

    if ($created_at !== '') {

        echo htmlspecialchars(
            date(
                "d M Y, H:i",
                strtotime($created_at)
            )
        );

    } else {

        echo "—";

    }

    ?>

    </div>

</div>


</div>


<!-- INPUT VALUES -->

<div class="result-section">

<div class="section-heading">

    <i class="bi bi-sliders"></i>

    <h6>
        Input Values
    </h6>

</div>


<div
    class="value-grid input-values"
></div>

</div>


<!-- RESULT VALUES -->

<div class="result-section">

<div class="section-heading">

    <i class="bi bi-calculator"></i>

    <h6>
        Calculated Results
    </h6>

</div>


<div
    class="value-grid result-values"
></div>

</div>


<!-- =====================================================
     P2 CENTRIFUGE CHART
====================================================== -->

<div
    class="chart-block"
    id="centrifugeChartBlock_<?= $id ?>"
    style="display:none;"
>

<h6>

    <i class="bi bi-arrow-repeat me-2"></i>

    Centrifugation — RCF vs RPM

</h6>


<div class="chart-container">

    <canvas
        id="centrifugeChart_<?= $id ?>"
    ></canvas>

</div>

</div>


<!-- =====================================================
     P2 SIEVE CHART
====================================================== -->

<div
    class="chart-block"
    id="sieveChartBlock_<?= $id ?>"
    style="display:none;"
>

<h6>

    <i class="bi bi-bar-chart-line me-2"></i>

    Sieve Analysis — Percentage Retained

</h6>


<div class="chart-container">

    <canvas
        id="sieveChart_<?= $id ?>"
    ></canvas>

</div>

</div>


<!-- =====================================================
     P3 TEMPERATURE PROFILE
====================================================== -->

<div
    class="chart-block"
    id="p3TemperatureChartBlock_<?= $id ?>"
    style="display:none;"
>

<h6 id="p3TemperatureChartTitle_<?= $id ?>">
    <i class="bi bi-thermometer-half me-2"></i>
    Thermal Processing — Temperature Profile
</h6>

<div class="chart-container">
    <canvas id="p3TemperatureChart_<?= $id ?>"></canvas>
</div>

</div>


<!-- =====================================================
     GENERIC CHART
====================================================== -->

<div
    class="chart-block"
    id="singleChartBlock_<?= $id ?>"
    style="display:none;"
>

<h6
    id="singleChartTitle_<?= $id ?>"
>

    <i class="bi bi-graph-up me-2"></i>

    Simulation Graph

</h6>


<div class="chart-container">

    <canvas
        id="chart_<?= $id ?>"
    ></canvas>

</div>


<div
    class="chart-message"
    id="chartMessage_<?= $id ?>"
>

    <i class="bi bi-info-circle"></i>

    <span>
        The saved result contains calculated values,
        but no graph data was stored for this simulation.
    </span>

</div>

</div>

<!-- P3 REGRESSION CHARTS -->
<div class="chart-block" id="heatingRegressionChartBlock_<?= $id ?>" style="display:none;">
<h6 id="heatingRegressionChartTitle_<?= $id ?>"><i class="bi bi-graph-up-arrow me-2"></i>Heating Regression</h6>
<div class="chart-container"><canvas id="heatingRegressionChart_<?= $id ?>"></canvas></div>
</div>

<div class="chart-block" id="coolingRegressionChartBlock_<?= $id ?>" style="display:none;">
<h6 id="coolingRegressionChartTitle_<?= $id ?>"><i class="bi bi-graph-down-arrow me-2"></i>Cooling Regression</h6>
<div class="chart-container"><canvas id="coolingRegressionChart_<?= $id ?>"></canvas></div>
</div>


<!-- =====================================================
     RAW INPUT DATA
====================================================== -->

<div class="result-section">

<div class="section-heading">

    <i class="bi bi-code-square"></i>

    <h6>
        Saved Input Data
    </h6>

</div>


<div class="raw-data">

<pre
    class="raw-input-data"
></pre>

</div>

</div>


<!-- =====================================================
     RAW RESULT DATA
====================================================== -->

<div class="result-section">

<div class="section-heading">

    <i class="bi bi-file-earmark-code"></i>

    <h6>
        Saved Result Data
    </h6>

</div>


<div class="raw-data">

<pre
    class="raw-result-data"
></pre>

</div>

</div>


</div>


<!-- FOOTER -->

<div class="modal-footer">

<button
    type="button"
    class="close-modal-btn"
    data-bs-dismiss="modal"
>

    Close

</button>

</div>


</div>

</div>

</div>


<?php endforeach; ?>


</tbody>

</table>

</div>


<?php endif; ?>


</div>

</section>

</main>

</div>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

/* =========================================================
   MOBILE SIDEBAR
========================================================= */

const mobileMenuBtn =
    document.getElementById(
        "mobileMenuBtn"
    );

const sidebar =
    document.getElementById(
        "sidebar"
    );

const sidebarOverlay =
    document.getElementById(
        "sidebarOverlay"
    );


if (mobileMenuBtn) {

    mobileMenuBtn.addEventListener(
        "click",
        function () {

            sidebar.classList.toggle(
                "open"
            );

            sidebarOverlay.classList.toggle(
                "show"
            );

        }
    );

}


if (sidebarOverlay) {

    sidebarOverlay.addEventListener(
        "click",
        function () {

            sidebar.classList.remove(
                "open"
            );

            sidebarOverlay.classList.remove(
                "show"
            );

        }
    );

}


/* =========================================================
   CHART STORAGE
========================================================= */

const simulationCharts = {};


/* =========================================================
   HTML ESCAPE
========================================================= */

function escapeHtml(value) {

    if (
        value === null ||
        value === undefined
    ) {
        return "";
    }

    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}


/* =========================================================
   FORMAT LABEL
========================================================= */

function formatLabel(key) {

    let label =
        String(key);

    label =
        label.replace(
            /([a-z])([A-Z])/g,
            "$1 $2"
        );

    label =
        label.replace(
            /_/g,
            " "
        );

    label =
        label.replace(
            /\s+/g,
            " "
        )
        .trim();


    return label.replace(
        /\b\w/g,
        function (char) {

            return char.toUpperCase();

        }
    );

}


/* =========================================================
   FORMAT VALUE
========================================================= */

function formatValue(value) {

    if (
        value === null ||
        value === undefined
    ) {

        return "—";

    }


    if (
        typeof value === "number"
    ) {

        if (
            !Number.isFinite(value)
        ) {

            return "—";

        }


        return Number.isInteger(value)
            ? String(value)
            : value
                .toFixed(4)
                .replace(/0+$/, "")
                .replace(/\.$/, "");

    }


    if (
        typeof value === "boolean"
    ) {

        return value
            ? "Yes"
            : "No";

    }


    if (
        typeof value === "object"
    ) {

        try {

            return JSON.stringify(
                value
            );

        } catch (e) {

            return String(value);

        }

    }


    return String(value);

}


/* =========================================================
   BUILD VALUE BOXES
========================================================= */

function buildValueBoxes(
    container,
    data
) {

    if (!container) {
        return;
    }


    container.innerHTML = "";


    if (
        !data ||
        typeof data !== "object" ||
        Array.isArray(data)
    ) {

        return;

    }


    Object.keys(data)
        .forEach(function (key) {

            const value =
                data[key];


            /*
             * Arrays are displayed in raw JSON.
             */

            if (
                Array.isArray(value)
            ) {

                return;

            }


            /*
             * Nested objects are displayed in raw JSON.
             */

            if (
                value &&
                typeof value === "object"
            ) {

                return;

            }


            const box =
                document.createElement(
                    "div"
                );

            box.className =
                "value-box";


            box.innerHTML = `

                <div class="value-box-label">
                    ${escapeHtml(
                        formatLabel(key)
                    )}
                </div>

                <div class="value-box-value">
                    ${escapeHtml(
                        formatValue(value)
                    )}
                </div>

            `;


            container.appendChild(
                box
            );

        });

}


/* =========================================================
   FIND ARRAY
========================================================= */

function findArray(
    object,
    keys
) {

    if (
        !object ||
        typeof object !== "object"
    ) {

        return null;

    }


    for (
        const key of keys
    ) {

        if (
            Array.isArray(
                object[key]
            )
        ) {

            return object[key];

        }

    }


    return null;

}


/* =========================================================
   FIND VALUE
========================================================= */

function findValue(
    object,
    keys
) {

    if (
        !object ||
        typeof object !== "object"
    ) {

        return null;

    }


    for (
        const key of keys
    ) {

        if (
            object[key] !== undefined &&
            object[key] !== null
        ) {

            return object[key];

        }

    }


    return null;

}


/* =========================================================
   HIDE BLOCK
========================================================= */

function hideBlock(id) {

    const block =
        document.getElementById(id);

    if (block) {

        block.style.display =
            "none";

    }

}


/* =========================================================
   SHOW BLOCK
========================================================= */

function showBlock(id) {

    const block =
        document.getElementById(id);

    if (block) {

        block.style.display =
            "block";

    }

}


/* =========================================================
   DESTROY CHART
========================================================= */

function destroyChart(key) {

    if (
        simulationCharts[key]
    ) {

        try {

            simulationCharts[key].destroy();

        } catch (error) {}

        delete simulationCharts[key];

    }

}


/* =========================================================
   DESTROY MODAL CHARTS
========================================================= */

function destroyModalCharts(id) {

    Object.keys(
        simulationCharts
    )
    .forEach(function (key) {

        if (
            key.indexOf(
                id + "_"
            ) === 0
        ) {

            destroyChart(key);

        }

    });

}


/* =========================================================
   SHOW MESSAGE
========================================================= */

function showMessage(
    id,
    message
) {

    const block =
        document.getElementById(
            "singleChartBlock_" + id
        );

    const messageBox =
        document.getElementById(
            "chartMessage_" + id
        );


    if (block) {

        block.style.display =
            "block";

    }


    if (messageBox) {

        messageBox.style.display =
            "block";


        const text =
            messageBox.querySelector(
                "span"
            );


        if (text) {

            text.textContent =
                message;

        }

    }

}


/* =========================================================
   HIDE MESSAGE
========================================================= */

function hideMessage(id) {

    const messageBox =
        document.getElementById(
            "chartMessage_" + id
        );


    if (messageBox) {

        messageBox.style.display =
            "none";

    }

}


/* =========================================================
   PREPARE CHART DATA
========================================================= */

function prepareChartData(
    labels,
    values
) {

    const cleanLabels = [];
    const cleanValues = [];


    if (
        !Array.isArray(labels) ||
        !Array.isArray(values)
    ) {

        return {
            labels: [],
            values: []
        };

    }


    const count =
        Math.min(
            labels.length,
            values.length
        );


    for (
        let i = 0;
        i < count;
        i++
    ) {

        const value =
            parseFloat(
                values[i]
            );


        if (
            !Number.isFinite(value)
        ) {

            continue;

        }


        let label =
            labels[i];


        const numericLabel =
            parseFloat(label);


        if (
            Number.isFinite(
                numericLabel
            )
        ) {

            label =
                numericLabel;

        }


        cleanLabels.push(
            label
        );

        cleanValues.push(
            value
        );

    }


    return {
        labels: cleanLabels,
        values: cleanValues
    };

}


/* =========================================================
   DRAW PRACTICAL 2
========================================================= */

function drawPractical2(
    id,
    results,
    inputs
) {

    let chartCount = 0;


    /* -----------------------------------------------------
       RCF VS RPM
    ----------------------------------------------------- */

    const rpm =
        parseFloat(
            findValue(
                inputs,
                [
                    "rpm",
                    "RPM"
                ]
            )
        );


    const radius =
        parseFloat(
            findValue(
                inputs,
                [
                    "radius",
                    "Radius"
                ]
            )
        );


    if (
        Number.isFinite(rpm) &&
        Number.isFinite(radius) &&
        rpm > 0 &&
        radius > 0
    ) {

        const rpmValues = [];
        const rcfValues = [];


        /*
         * Same calculation used by Practical 2:
         *
         * RCF = 1.118 × 10^-5 × radius × RPM²
         */

        for (
            let i = 1;
            i <= 6;
            i++
        ) {

            const currentRPM =
                Math.round(
                    rpm * i / 6
                );


            const rcf =
                1.118e-5 *
                radius *
                Math.pow(
                    currentRPM,
                    2
                );


            rpmValues.push(
                currentRPM
            );

            rcfValues.push(
                Number(
                    rcf.toFixed(4)
                )
            );

        }


        const canvas =
            document.getElementById(
                "centrifugeChart_" + id
            );


        if (canvas) {

            showBlock(
                "centrifugeChartBlock_" + id
            );


            destroyChart(
                id + "_centrifuge"
            );


            simulationCharts[
                id + "_centrifuge"
            ] = new Chart(
                canvas,
                {

                    type: "line",

                    data: {

                        labels:
                            rpmValues,

                        datasets: [

                            {

                                label:
                                    "RCF",

                                data:
                                    rcfValues,

                                borderWidth:
                                    2,

                                pointRadius:
                                    4,

                                tension:
                                    .25

                            }

                        ]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio:
                            false,

                        scales: {

                            x: {

                                title: {

                                    display:
                                        true,

                                    text:
                                        "RPM"

                                }

                            },

                            y: {

                                beginAtZero:
                                    true,

                                title: {

                                    display:
                                        true,

                                    text:
                                        "Relative Centrifugal Force"

                                }

                            }

                        }

                    }

                }
            );


            chartCount++;

        }

    }


    /* -----------------------------------------------------
       SIEVE ANALYSIS
    ----------------------------------------------------- */

    let sieveResults =
        findArray(
            results,
            [
                "sieveResults"
            ]
        );


    if (
        !sieveResults &&
        results.sieveAnalysis &&
        typeof results.sieveAnalysis === "object"
    ) {

        sieveResults =
            findArray(
                results.sieveAnalysis,
                [
                    "results"
                ]
            );

    }


    if (
        Array.isArray(sieveResults) &&
        sieveResults.length > 0
    ) {

        const labels = [];
        const values = [];


        sieveResults.forEach(
            function (item) {

                if (
                    !item ||
                    typeof item !== "object"
                ) {

                    return;

                }


                const label =
                    findValue(
                        item,
                        [
                            "sieve",
                            "sieveSize",
                            "sieve_size",
                            "name"
                        ]
                    );


                const value =
                    findValue(
                        item,
                        [
                            "percentageRetained",
                            "percentRetained",
                            "percentage_retained"
                        ]
                    );


                const numericValue =
                    parseFloat(value);


                if (
                    label !== null &&
                    Number.isFinite(
                        numericValue
                    )
                ) {

                    labels.push(
                        String(label)
                    );

                    values.push(
                        numericValue
                    );

                }

            }
        );


        if (
            labels.length > 0 &&
            values.length > 0
        ) {

            const canvas =
                document.getElementById(
                    "sieveChart_" + id
                );


            if (canvas) {

                showBlock(
                    "sieveChartBlock_" + id
                );


                destroyChart(
                    id + "_sieve"
                );


                simulationCharts[
                    id + "_sieve"
                ] = new Chart(
                    canvas,
                    {

                        type: "bar",

                        data: {

                            labels:
                                labels,

                            datasets: [

                                {

                                    label:
                                        "Percentage Retained",

                                    data:
                                        values,

                                    borderWidth:
                                        1

                                }

                            ]

                        },

                        options: {

                            responsive: true,

                            maintainAspectRatio:
                                false,

                            scales: {

                                x: {

                                    title: {

                                        display:
                                            true,

                                        text:
                                            "Sieve Size"

                                    }

                                },

                                y: {

                                    beginAtZero:
                                        true,

                                    title: {

                                        display:
                                            true,

                                        text:
                                            "Percentage Retained (%)"

                                    }

                                }

                            }

                        }

                    }
                );


                chartCount++;

            }

        }

    }


    if (
        chartCount === 0
    ) {

        showMessage(
            id,
            "The saved result contains calculated values, but there is not enough saved Practical 2 data to draw the graphs."
        );

    }


    return chartCount;

}


/* =========================================================
   DRAW PRACTICAL 3
========================================================= */

function drawPractical3(
    id,
    results,
    inputs
) {

    let chartCount = 0;

    /*
     * Practical 3 temperature graph.
     *
     * New records may contain temperatureData.
     * Older records may not contain it, so when it is missing
     * we rebuild the exact temperature profile from the saved
     * Practical 3 input values.
     */

    let temperatureData = findArray(
        results,
        ["temperatureData", "temperature_data"]
    );

    /*
     * CURRENT PRACTICAL 3 FORMAT
     *
     * practical3_simulation.js stores the temperature graph as:
     *
     * result_data.chartData.temperature = {
     *     labels: [...],
     *     retortTemperature: [...],
     *     productTemperature: [...]
     * }
     *
     * Read this format first.
     */
    if (
        !temperatureData &&
        results.chartData &&
        typeof results.chartData === "object" &&
        results.chartData.temperature &&
        typeof results.chartData.temperature === "object"
    ) {

        const temperatureChartData =
            results.chartData.temperature;

        const labels =
            Array.isArray(temperatureChartData.labels)
                ? temperatureChartData.labels
                : [];

        const retortValues =
            Array.isArray(temperatureChartData.retortTemperature)
                ? temperatureChartData.retortTemperature
                : [];

        const productValues =
            Array.isArray(temperatureChartData.productTemperature)
                ? temperatureChartData.productTemperature
                : [];

        if (
            labels.length > 0 &&
            (retortValues.length > 0 || productValues.length > 0)
        ) {

            temperatureData = labels.map(function(label, index) {

                return {
                    time: Number(label),
                    retortTemp:
                        Number.isFinite(Number(retortValues[index]))
                            ? Number(retortValues[index])
                            : null,
                    productTemp:
                        Number.isFinite(Number(productValues[index]))
                            ? Number(productValues[index])
                            : null,
                    stage: ""
                };

            });
        }
    }

    /*
     * Older chart format.
     */
    if (
        !temperatureData &&
        results.chart &&
        typeof results.chart === "object"
    ) {
        temperatureData = findArray(
            results.chart,
            ["temperatureData", "temperature_data"]
        );
    }

    /*
     * ---------------------------------------------------------
     * FALLBACK: REBUILD TEMPERATURE DATA FROM SAVED INPUTS
     * ---------------------------------------------------------
     */

    if (
        !Array.isArray(temperatureData) ||
        temperatureData.length === 0
    ) {

        const initialTemp = Number(
            findValue(inputs, ["initialTemp", "initial_temperature", "initialTemperature"])
        );

        const retortTemp = Number(
            findValue(inputs, ["retortTemp", "retort_temperature", "retortTemperature"])
        );

        const heatingTime = Number(
            findValue(inputs, ["heatingTime", "heating_time"])
        );

        const processingTime = Number(
            findValue(inputs, ["processingTime", "processing_time"])
        );

        const coolingTime = Number(
            findValue(inputs, ["coolingTime", "cooling_time"])
        );

        if (
            Number.isFinite(initialTemp) &&
            Number.isFinite(retortTemp) &&
            Number.isFinite(heatingTime) &&
            Number.isFinite(processingTime) &&
            Number.isFinite(coolingTime) &&
            heatingTime > 0 &&
            processingTime > 0 &&
            coolingTime > 0 &&
            retortTemp > initialTemp
        ) {

            const rebuilt = [];
            const totalTime =
                heatingTime +
                processingTime +
                coolingTime;

            /*
             * These equations match the Practical 3 simulation:
             * heating F = 10
             * cooling F = 11
             * j = 1
             */

            const heatingEndProductTemp =
                initialTemp +
                (retortTemp - initialTemp) *
                (1 - Math.exp(-2));

            const processingEndProductTemp =
                retortTemp -
                (retortTemp - heatingEndProductTemp) *
                Math.pow(10, -processingTime / 10);

            for (
                let time = 0;
                time <= totalTime;
                time += 1
            ) {

                let stage = "";
                let retortTemperature = retortTemp;
                let productTemperature = initialTemp;

                if (time <= heatingTime) {

                    stage = "Heating";

                    const progress =
                        time / heatingTime;

                    retortTemperature =
                        initialTemp +
                        (retortTemp - initialTemp) *
                        progress;

                    productTemperature =
                        initialTemp +
                        (retortTemp - initialTemp) *
                        (1 - Math.exp(-2 * progress));

                } else if (
                    time <=
                    heatingTime + processingTime
                ) {

                    stage = "Processing";

                    const processingElapsed =
                        time - heatingTime;

                    retortTemperature =
                        retortTemp;

                    productTemperature =
                        retortTemp -
                        (retortTemp - heatingEndProductTemp) *
                        Math.pow(
                            10,
                            -processingElapsed / 10
                        );

                } else {

                    stage = "Cooling";

                    const coolingElapsed =
                        time -
                        heatingTime -
                        processingTime;

                    const coolingProgress =
                        coolingElapsed / coolingTime;

                    retortTemperature =
                        retortTemp -
                        (retortTemp - initialTemp) *
                        coolingProgress;

                    productTemperature =
                        initialTemp +
                        (processingEndProductTemp - initialTemp) *
                        Math.pow(
                            10,
                            -coolingElapsed / 11
                        );

                }

                rebuilt.push({
                    time: time,
                    retortTemp: retortTemperature,
                    productTemp: productTemperature,
                    stage: stage
                });
            }

            temperatureData = rebuilt;
        }
    }

    /*
     * ---------------------------------------------------------
     * DRAW TEMPERATURE PROFILE
     * ---------------------------------------------------------
     */

    if (
        Array.isArray(temperatureData) &&
        temperatureData.length > 0
    ) {

        const labels = [];
        const retort = [];
        const product = [];

        temperatureData.forEach(function(item) {

            if (
                !item ||
                typeof item !== "object"
            ) {
                return;
            }

            const time = Number(
                findValue(
                    item,
                    ["time", "simulationTime", "simulation_time"]
                )
            );

            const retortTemp = Number(
                findValue(
                    item,
                    [
                        "retortTemp",
                        "retortTemperature",
                        "retort_temperature"
                    ]
                )
            );

            const productTemp = Number(
                findValue(
                    item,
                    [
                        "productTemp",
                        "productTemperature",
                        "product_temperature"
                    ]
                )
            );

            if (!Number.isFinite(time)) {
                return;
            }

            labels.push(time);

            retort.push(
                Number.isFinite(retortTemp)
                    ? retortTemp
                    : null
            );

            product.push(
                Number.isFinite(productTemp)
                    ? productTemp
                    : null
            );
        });

        const datasets = [];

        if (
            retort.some(function(value) {
                return Number.isFinite(value);
            })
        ) {
            datasets.push({
                label: "Retort Temperature (°C)",
                data: retort,
                borderWidth: 2,
                pointRadius: 2,
                tension: 0.2,
                spanGaps: true
            });
        }

        if (
            product.some(function(value) {
                return Number.isFinite(value);
            })
        ) {
            datasets.push({
                label: "Product Temperature (°C)",
                data: product,
                borderWidth: 2,
                pointRadius: 2,
                tension: 0.2,
                spanGaps: true
            });
        }

        const canvas =
            document.getElementById(
                "p3TemperatureChart_" + id
            );

        if (
            canvas &&
            labels.length > 0 &&
            datasets.length > 0
        ) {

            showBlock(
                "p3TemperatureChartBlock_" + id
            );

            const title =
                document.getElementById(
                    "p3TemperatureChartTitle_" + id
                );

            if (title) {
                title.innerHTML =
                    '<i class="bi bi-thermometer-half me-2"></i>' +
                    'Thermal Processing — Temperature Profile';
            }

            destroyChart(id + "_main");

            simulationCharts[id + "_main"] =
                new Chart(
                    canvas,
                    {
                        type: "line",
                        data: {
                            labels: labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: "index",
                                intersect: false
                            },
                            scales: {
                                x: {
                                    title: {
                                        display: true,
                                        text: "Time (min)"
                                    }
                                },
                                y: {
                                    title: {
                                        display: true,
                                        text: "Temperature (°C)"
                                    }
                                }
                            }
                        }
                    }
                );

            chartCount++;
        }
    }

    /*
     * Final legacy fallback for records that contain simple
     * labels + temperature arrays.
     */

    if (chartCount === 0) {

        const labels =
            findArray(
                results,
                [
                    "labels",
                    "times",
                    "time",
                    "simulationTime",
                    "simulation_time",
                    "time_data"
                ]
            );

        const values =
            findArray(
                results,
                [
                    "values",
                    "temperature",
                    "temperatures",
                    "product_temperature",
                    "product_temperatures"
                ]
            );

        if (
            Array.isArray(labels) &&
            Array.isArray(values)
        ) {

            const data =
                prepareChartData(
                    labels,
                    values
                );

            if (
                data.labels.length &&
                data.values.length
            ) {

                const canvas =
                    document.getElementById(
                        "p3TemperatureChart_" + id
                    );

                if (canvas) {

                    showBlock(
                        "p3TemperatureChartBlock_" + id
                    );

                    const title =
                        document.getElementById(
                            "p3TemperatureChartTitle_" + id
                        );

                    if (title) {
                        title.innerHTML =
                            '<i class="bi bi-thermometer-half me-2"></i>' +
                            'Thermal Processing — Product Temperature';
                    }

                    destroyChart(id + "_main");

                    simulationCharts[id + "_main"] =
                        new Chart(
                            canvas,
                            {
                                type: "line",
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        label: "Product Temperature (°C)",
                                        data: data.values,
                                        borderWidth: 2,
                                        pointRadius: 2,
                                        tension: 0.2
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        x: {
                                            title: {
                                                display: true,
                                                text: "Time (min)"
                                            }
                                        },
                                        y: {
                                            title: {
                                                display: true,
                                                text: "Temperature (°C)"
                                            }
                                        }
                                    }
                                }
                            }
                        );

                    chartCount++;
                }
            }
        }
    }

    /*
     * Do not show the missing-temperature message here when
     * regression graphs exist. The three graph blocks are
     * handled independently.
     */

    return chartCount;
}


/* =========================================================
   DRAW PRACTICAL 4
========================================================= */

function drawPractical4(
    id,
    results
) {

    let labels =
        findArray(
            results,
            [
                "times",
                "time",
                "simulation_time",
                "labels"
            ]
        );


    let values =
        findArray(
            results,
            [
                "moisture",
                "moisture_content",
                "moistureContent",
                "drying_rate",
                "values"
            ]
        );


    /*
     * Fallback to nested chart object.
     */

    if (
        (
            !Array.isArray(labels) ||
            !Array.isArray(values)
        ) &&
        results.chart &&
        typeof results.chart === "object"
    ) {

        labels =
            findArray(
                results.chart,
                [
                    "times",
                    "time",
                    "labels"
                ]
            );


        values =
            findArray(
                results.chart,
                [
                    "moisture",
                    "moisture_content",
                    "moistureContent",
                    "drying_rate",
                    "values"
                ]
            );

    }


    if (
        !Array.isArray(labels) ||
        !Array.isArray(values)
    ) {

        showMessage(
            id,
            "The saved result contains calculated values, but no drying graph data was stored for this Practical 4 simulation."
        );

        return false;

    }


    const data =
        prepareChartData(
            labels,
            values
        );


    if (
        data.labels.length === 0 ||
        data.values.length === 0
    ) {

        showMessage(
            id,
            "The saved result contains calculated values, but no usable drying graph data was found."
        );

        return false;

    }


    const canvas =
        document.getElementById(
            "chart_" + id
        );


    if (!canvas) {

        return false;

    }


    showBlock(
        "singleChartBlock_" + id
    );

    hideMessage(id);


    const title =
        document.getElementById(
            "singleChartTitle_" + id
        );


    if (title) {

        title.innerHTML = `

            <i class="bi bi-droplet-half me-2"></i>

            Drying Simulation —
            Moisture Content

        `;

    }


    destroyChart(
        id + "_main"
    );


    simulationCharts[
        id + "_main"
    ] = new Chart(
        canvas,
        {

            type: "line",

            data: {

                labels:
                    data.labels,

                datasets: [

                    {

                        label:
                            "Moisture Content",

                        data:
                            data.values,

                        borderWidth:
                            2,

                        pointRadius:
                            2,

                        tension:
                            .25

                    }

                ]

            },

            options: {

                responsive:
                    true,

                maintainAspectRatio:
                    false,

                interaction: {

                    mode:
                        "index",

                    intersect:
                        false

                },

                scales: {

                    x: {

                        title: {

                            display:
                                true,

                            text:
                                "Time"

                        }

                    },

                    y: {

                        beginAtZero:
                            true,

                        title: {

                            display:
                                true,

                            text:
                                "Moisture Content"

                        }

                    }

                }

            }

        }
    );


    return true;

}


/* =========================================================
   PRACTICAL 3 REGRESSION CHARTS
========================================================= */
function drawPractical3RegressionCharts(id, results, inputs) {
    const hr = results.heatingRegression || {};
    const cr = results.coolingRegression || {};
    const hd = results.heatingRegressionData || {};
    const cd = results.coolingRegressionData || {};

    let hx = Array.isArray(hd.x) ? hd.x : [];
    let hy = Array.isArray(hd.y) ? hd.y : [];
    let cx = Array.isArray(cd.x) ? cd.x : [];
    let cy = Array.isArray(cd.y) ? cd.y : [];

    /* Also accept x/y nested directly in the regression objects. */
    if (!hx.length && Array.isArray(hr.x)) hx = hr.x;
    if (!hy.length && Array.isArray(hr.y)) hy = hr.y;
    if (!cx.length && Array.isArray(cr.x)) cx = cr.x;
    if (!cy.length && Array.isArray(cr.y)) cy = cr.y;

    function makeChart(canvasId, blockId, titleId, xs, ys, slope, intercept, label, xTitle) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return false;

        const points = [];
        for (let i = 0; i < Math.min(xs.length, ys.length); i++) {
            const x = Number(xs[i]);
            const y = Number(ys[i]);
            if (Number.isFinite(x) && Number.isFinite(y)) points.push({x:x,y:y});
        }

        const m = Number(slope);
        const b = Number(intercept);
        let line = [];

        if (Number.isFinite(m) && Number.isFinite(b)) {
            let minX = points.length ? Math.min(...points.map(p => p.x)) : 0;
            let maxX = points.length ? Math.max(...points.map(p => p.x)) : 1;
            if (minX === maxX) maxX = minX + 1;
            line = [{x:minX,y:m*minX+b},{x:maxX,y:m*maxX+b}];
        }

        if (!points.length && !line.length) return false;

        showBlock(blockId);
        const title = document.getElementById(titleId);
        if (title) title.innerHTML = '<i class="bi bi-graph-up-arrow me-2"></i>' + escapeHtml(label);

        const key = id + '_' + (label === 'Heating Regression' ? 'heatingRegression' : 'coolingRegression');
        destroyChart(key);

        const datasets = [];
        if (points.length) {
            datasets.push({label: label + ' Data', data:points, showLine:false, pointRadius:4, borderWidth:1});
        }
        if (line.length) {
            datasets.push({type:'line', label:'Regression Line', data:line, pointRadius:0, borderWidth:2, tension:0});
        }

        simulationCharts[key] = new Chart(canvas, {
            type:'scatter',
            data:{datasets:datasets},
            options:{
                responsive:true,
                maintainAspectRatio:false,
                interaction:{mode:'nearest',intersect:false},
                scales:{
                    x:{type:'linear',title:{display:true,text:xTitle}},
                    y:{title:{display:true,text:'log₁₀ Temperature Ratio'}}
                }
            }
        });
        return true;
    }

    let count = 0;
    if (makeChart('heatingRegressionChart_'+id,'heatingRegressionChartBlock_'+id,'heatingRegressionChartTitle_'+id,hx,hy,hr.slope,hr.intercept,'Heating Regression','Time During Processing (min)')) count++;
    if (makeChart('coolingRegressionChart_'+id,'coolingRegressionChartBlock_'+id,'coolingRegressionChartTitle_'+id,cx,cy,cr.slope,cr.intercept,'Cooling Regression','Cooling Time (min)')) count++;

    /* Old records: draw the saved regression equation when x/y arrays were not saved. */
    function legacy(canvasId,blockId,titleId,slope,intercept,xMax,label,xTitle,key) {
        const canvas=document.getElementById(canvasId);
        const m=Number(slope), b=Number(intercept);
        if (!canvas || !Number.isFinite(m) || !Number.isFinite(b)) return false;
        const end=Number(xMax)>0 ? Number(xMax) : 1;
        showBlock(blockId);
        const title=document.getElementById(titleId);
        if(title) title.innerHTML='<i class="bi bi-graph-up-arrow me-2"></i>'+escapeHtml(label)+' <span style="font-size:10px;color:#74818a;">(saved regression)</span>';
        destroyChart(key);
        simulationCharts[key]=new Chart(canvas,{type:'line',data:{datasets:[{label:'Regression Line',data:[{x:0,y:b},{x:end,y:m*end+b}],pointRadius:0,borderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,scales:{x:{type:'linear',title:{display:true,text:xTitle}},y:{title:{display:true,text:'log₁₀ Temperature Ratio'}}}}});
        return true;
    }

    if (!hx.length || !hy.length) {
        const t=Number(findValue(inputs,['processingTime','processing_time']));
        if (legacy('heatingRegressionChart_'+id,'heatingRegressionChartBlock_'+id,'heatingRegressionChartTitle_'+id,hr.slope,hr.intercept,t,'Heating Regression','Time During Processing (min)',id+'_heatingRegression')) count++;
    }
    if (!cx.length || !cy.length) {
        const t=Number(findValue(inputs,['coolingTime','cooling_time']));
        if (legacy('coolingRegressionChart_'+id,'coolingRegressionChartBlock_'+id,'coolingRegressionChartTitle_'+id,cr.slope,cr.intercept,t,'Cooling Regression','Cooling Time (min)',id+'_coolingRegression')) count++;
    }
    return count;
}

/* =========================================================
   DRAW CHARTS
========================================================= */

function drawSimulationCharts(
    modal,
    results,
    inputs
) {

    const id =
        modal.dataset.simulationId;


    /*
     * THIS IS IMPORTANT.
     *
     * We use the actual practical number belonging to
     * the selected simulation record.
     *
     * We DO NOT use the page filter.
     */

    const practical =
        parseInt(
            modal.dataset.practical || "0",
            10
        );


    /*
     * Hide all chart blocks first.
     */

    hideBlock(
        "centrifugeChartBlock_" + id
    );

    hideBlock(
        "sieveChartBlock_" + id
    );

    hideBlock(
        "singleChartBlock_" + id
    );

    hideBlock(
        "p3TemperatureChartBlock_" + id
    );

    hideBlock(
        "heatingRegressionChartBlock_" + id
    );

    hideBlock(
        "coolingRegressionChartBlock_" + id
    );


    hideMessage(id);


    /*
     * Remove charts from previous modal opening.
     */

    destroyModalCharts(id);


    /*
     * Draw according to actual practical.
     */

    if (practical === 2) {

        drawPractical2(
            id,
            results,
            inputs
        );

        return;

    }


    if (practical === 3) {

        drawPractical3(
            id,
            results
        );

        drawPractical3RegressionCharts(
            id,
            results,
            inputs
        );

        return;

    }


    if (practical === 4) {

        drawPractical4(
            id,
            results
        );

        return;

    }


    showMessage(
        id,
        "This simulation does not have a supported graph format."
    );

}


/* =========================================================
   MODAL EVENTS
========================================================= */

document
    .querySelectorAll(
        ".simulation-modal"
    )
    .forEach(function (modal) {


        modal.addEventListener(
            "show.bs.modal",
            function () {

                const id =
                    modal.dataset.simulationId;


                let inputs = {};

                let results = {};


                /*
                 * Read JSON stored directly on the modal.
                 */

                try {

                    inputs =
                        JSON.parse(
                            modal.dataset.inputs ||
                            "{}"
                        );

                } catch (error) {

                    inputs = {};

                }


                try {

                    results =
                        JSON.parse(
                            modal.dataset.results ||
                            "{}"
                        );

                } catch (error) {

                    results = {};

                }


                /*
                 * Build input cards.
                 */

                buildValueBoxes(
                    modal.querySelector(
                        ".input-values"
                    ),
                    inputs
                );


                /*
                 * Build result cards.
                 */

                buildValueBoxes(
                    modal.querySelector(
                        ".result-values"
                    ),
                    results
                );


                /*
                 * Raw input data.
                 */

                const rawInput =
                    modal.querySelector(
                        ".raw-input-data"
                    );


                if (rawInput) {

                    rawInput.textContent =
                        JSON.stringify(
                            inputs,
                            null,
                            2
                        );

                }


                /*
                 * Raw result data.
                 */

                const rawResult =
                    modal.querySelector(
                        ".raw-result-data"
                    );


                if (rawResult) {

                    rawResult.textContent =
                        JSON.stringify(
                            results,
                            null,
                            2
                        );

                }


                /*
                 * Draw the graph.
                 */

                drawSimulationCharts(
                    modal,
                    results,
                    inputs
                );

            }
        );


        modal.addEventListener(
            "hidden.bs.modal",
            function () {

                const id =
                    modal.dataset.simulationId;


                destroyModalCharts(id);


                hideBlock(
                    "centrifugeChartBlock_" + id
                );

                hideBlock(
                    "sieveChartBlock_" + id
                );

                hideBlock(
                    "singleChartBlock_" + id
                );

                hideBlock(
                    "p3TemperatureChartBlock_" + id
                );

                hideBlock(
                    "heatingRegressionChartBlock_" + id
                );

                hideBlock(
                    "coolingRegressionChartBlock_" + id
                );


                hideMessage(id);

            }
        );

    });

</script>


</body>

</html>