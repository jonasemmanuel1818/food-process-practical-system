<?php

require_once "../config.php";
require_once "../rate_limit.php";

/* =========================================================
   ACCESS CONTROL
========================================================= */

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);

    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode([
        "success" => false,
        "message" => "You must be logged in to save simulation results."
    ]);

    exit();
}

$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   RATE LIMIT
========================================================= */

$rate_limit_key =
    "simulation_p5_user_" . $user_id;

if (!check_rate_limit($rate_limit_key, 5, 60)) {

    http_response_code(429);

    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode([
        "success" => false,
        "message" => "Too many save attempts. Please wait a moment and try again."
    ]);

    exit();
}


/* =========================================================
   RESPONSE TYPE
========================================================= */

header(
    "Content-Type: application/json; charset=UTF-8"
);


/* =========================================================
   READ REQUEST
========================================================= */

$input_data_raw =
    $_POST['input_data'] ?? '';

$result_data_raw =
    $_POST['result_data'] ?? '';


if (
    trim($input_data_raw) === ''
    ||
    trim($result_data_raw) === ''
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Simulation input and result data are required."
    ]);

    exit();
}


/* =========================================================
   DECODE JSON
========================================================= */

$input_data =
    json_decode(
        $input_data_raw,
        true
    );

$result_data =
    json_decode(
        $result_data_raw,
        true
    );


if (
    !is_array($input_data)
    ||
    !is_array($result_data)
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid simulation data."
    ]);

    exit();
}


/* =========================================================
   VALIDATE BASIC INPUTS
========================================================= */

$initial_mass =
    isset($input_data['initialMass'])
        ? (float) $input_data['initialMass']
        : 0;

$retained_mass =
    isset($input_data['retainedMass'])
        ? (float) $input_data['retainedMass']
        : -1;

$filtration_time =
    isset($input_data['filtrationTime'])
        ? (int) $input_data['filtrationTime']
        : 0;


if ($initial_mass <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Initial sample mass must be greater than zero."
    ]);

    exit();
}


if (
    $retained_mass < 0
    ||
    $retained_mass > $initial_mass
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Retained solid mass must be between zero and the initial sample mass."
    ]);

    exit();
}


if (
    $filtration_time < 3
    ||
    $filtration_time > 60
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Filtration time must be between 3 and 60 seconds."
    ]);

    exit();
}


/* =========================================================
   VALIDATE GRAPH DATA
========================================================= */

$graph_data =
    $result_data['graphData']
    ?? null;


/*
 * The graph is required to be stored with the
 * calculated simulation result.
 */

if (!is_array($graph_data)) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Filtration graph data is missing from the simulation result."
    ]);

    exit();
}


$labels =
    $graph_data['labels']
    ?? [];

$filtrate_values =
    $graph_data['filtrateMass']
    ?? [];

$retained_values =
    $graph_data['retainedMass']
    ?? [];


if (
    !is_array($labels)
    ||
    !is_array($filtrate_values)
    ||
    !is_array($retained_values)
    ||
    count($labels) === 0
    ||
    count($labels) !== count($filtrate_values)
    ||
    count($labels) !== count($retained_values)
) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "The filtration graph data is incomplete or invalid."
    ]);

    exit();
}


/* =========================================================
   STORE A STANDARDIZED FILTRATION GRAPH
========================================================= */

$result_data['graphData'] = [
    "labels" => array_values($labels),

    "filtrateMass" =>
        array_map(
            "floatval",
            array_values($filtrate_values)
        ),

    "retainedMass" =>
        array_map(
            "floatval",
            array_values($retained_values)
        )
];


/*
 * Keep the additional compatibility structure too.
 */

$result_data['filtrationGraph'] =
    $result_data['graphData'];

$result_data['times'] =
    $result_data['graphData']['labels'];

$result_data['filtrate'] =
    $result_data['graphData']['filtrateMass'];

$result_data['retained'] =
    $result_data['graphData']['retainedMass'];


/* =========================================================
   STANDARDIZE CALCULATED VALUES
========================================================= */

$filtrate_mass =
    $initial_mass -
    $retained_mass;

$retention_percentage =
    ($retained_mass / $initial_mass) * 100;

$separation_percentage =
    ($filtrate_mass / $initial_mass) * 100;


$result_data['filtration'] = [
    "initialMass" =>
        round($initial_mass, 3),

    "retainedMass" =>
        round($retained_mass, 3),

    "filtrateMass" =>
        round($filtrate_mass, 3),

    "retentionPercentage" =>
        round($retention_percentage, 3),

    "separationPercentage" =>
        round($separation_percentage, 3),

    "filtrationTime" =>
        $filtration_time
];


/* =========================================================
   JSON ENCODE
========================================================= */

$input_json =
    json_encode(
        $input_data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

$result_json =
    json_encode(
        $result_data,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


if (
    $input_json === false
    ||
    $result_json === false
) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Could not prepare simulation data for storage."
    ]);

    exit();
}


/* =========================================================
   SAVE TO DATABASE
========================================================= */

$simulation_type =
    "Filtration and Separation";

$practical_number = 5;


$stmt = $conn->prepare("
    INSERT INTO simulation_results
    (
        user_id,
        practical_number,
        simulation_type,
        input_data,
        result_data,
        created_at,
        updated_at
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        NOW(),
        NOW()
    )
");


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Could not prepare the database request."
    ]);

    exit();
}


$stmt->bind_param(
    "iisss",
    $user_id,
    $practical_number,
    $simulation_type,
    $input_json,
    $result_json
);


if (!$stmt->execute()) {

    $error =
        $stmt->error;

    $stmt->close();

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Could not save the simulation result.",
        "error" =>
            $error
    ]);

    exit();
}


$inserted_id =
    $stmt->insert_id;

$stmt->close();


/* =========================================================
   SUCCESS
========================================================= */

echo json_encode([
    "success" => true,
    "message" =>
        "Practical 5 simulation result and graph data saved successfully.",
    "id" =>
        $inserted_id
]);

exit();
?>
