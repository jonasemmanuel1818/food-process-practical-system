<?php

require_once "../config.php";
require_once "../rate_limit.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {

    echo json_encode([
        "success" => false,
        "message" => "User is not logged in."
    ]);

    exit();
}

$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   RATE LIMIT — PRACTICAL 3 SIMULATION
   Maximum 5 saves per minute per logged-in user
========================================================= */

$rate_limit = check_rate_limit(
    "simulation_p3_user_" . $user_id,
    5,
    60
);

if (!$rate_limit['allowed']) {

    http_response_code(429);

    echo json_encode([
        "success" => false,
        "message" => "Too many simulation saves. Please wait a moment and try again.",
        "retry_after" => $rate_limit['retry_after']
    ]);

    exit();
}


/* =========================================================
   GET SIMULATION DATA
========================================================= */

$input_data = $_POST['input_data'] ?? '';
$result_data = $_POST['result_data'] ?? '';


/* =========================================================
   VALIDATE DATA
========================================================= */

if ($input_data === '' || $result_data === '') {

    echo json_encode([
        "success" => false,
        "message" => "Simulation data is missing."
    ]);

    exit();
}


/* =========================================================
   SIMULATION TYPE
========================================================= */

$simulation_type = "Thermal Processing";


/* =========================================================
   PREPARE DATABASE STATEMENT
========================================================= */

$stmt = $conn->prepare("
    INSERT INTO simulation_results
    (
        user_id,
        practical_number,
        simulation_type,
        input_data,
        result_data
    )
    VALUES (?, 3, ?, ?, ?)
");


/* =========================================================
   CHECK STATEMENT
========================================================= */

if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Could not prepare database statement."
    ]);

    exit();
}


/* =========================================================
   BIND DATA
========================================================= */

$stmt->bind_param(
    "isss",
    $user_id,
    $simulation_type,
    $input_data,
    $result_data
);


/* =========================================================
   EXECUTE
========================================================= */

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Simulation results saved successfully.",
        "result_id" => $stmt->insert_id
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Could not save simulation results."
    ]);
}


/* =========================================================
   CLOSE
========================================================= */

$stmt->close();
$conn->close();

?>