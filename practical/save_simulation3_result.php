<?php

require_once "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "User is not logged in."
    ]);
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$input_data = $_POST['input_data'] ?? '';
$result_data = $_POST['result_data'] ?? '';

if ($input_data === '' || $result_data === '') {
    echo json_encode([
        "success" => false,
        "message" => "Simulation data is missing."
    ]);
    exit();
}

$simulation_type = "Thermal Processing";

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

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Could not prepare database statement."
    ]);
    exit();
}

$stmt->bind_param(
    "isss",
    $user_id,
    $simulation_type,
    $input_data,
    $result_data
);

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

$stmt->close();
$conn->close();

?>