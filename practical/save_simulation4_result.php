<?php

require_once "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");


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
   GET JSON DATA
========================================================= */

$raw_data = file_get_contents("php://input");

if (!$raw_data) {

    echo json_encode([
        "success" => false,
        "message" => "No data was received."
    ]);

    exit();
}


$data = json_decode($raw_data, true);


if (!is_array($data)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON data received."
    ]);

    exit();
}


/* =========================================================
   GET VALUES
========================================================= */

$simulation_type = trim(
    $data['simulation_type'] ?? ""
);

$input_data = $data['input_data'] ?? [];

$result_data = $data['result_data'] ?? [];


/* =========================================================
   VALIDATE SIMULATION TYPE
========================================================= */

$allowed_types = [
    "Spray Drying",
    "Freeze Drying"
];


if (!in_array(
    $simulation_type,
    $allowed_types,
    true
)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid simulation type."
    ]);

    exit();
}


/* =========================================================
   VALIDATE DATA
========================================================= */

if (!is_array($input_data)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid input data."
    ]);

    exit();
}


if (!is_array($result_data)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid result data."
    ]);

    exit();
}


/* =========================================================
   ENCODE DATA
========================================================= */

$input_json = json_encode(
    $input_data,
    JSON_UNESCAPED_UNICODE
);

$result_json = json_encode(
    $result_data,
    JSON_UNESCAPED_UNICODE
);


if ($input_json === false) {

    echo json_encode([
        "success" => false,
        "message" => "Could not encode input data."
    ]);

    exit();
}


if ($result_json === false) {

    echo json_encode([
        "success" => false,
        "message" => "Could not encode result data."
    ]);

    exit();
}


/* =========================================================
   SAVE RESULT
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
    VALUES
    (
        ?,
        4,
        ?,
        ?,
        ?
    )
");


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Database statement could not be prepared."
    ]);

    exit();
}


/* =========================================================
   BIND PARAMETERS
========================================================= */

$stmt->bind_param(
    "isss",
    $user_id,
    $simulation_type,
    $input_json,
    $result_json
);


/* =========================================================
   EXECUTE
========================================================= */

if ($stmt->execute()) {

    $result_id = $stmt->insert_id;

    echo json_encode([
        "success" => true,
        "message" => "Simulation result saved successfully.",
        "result_id" => $result_id
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Could not save simulation result.",
        "error" => $stmt->error
    ]);

}


$stmt->close();

?>