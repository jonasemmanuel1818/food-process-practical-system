<?php

/**
 * Food Process Learning & Simulation System
 * Offline Practical 5 Synchronization Endpoint
 *
 * File:
 * /food_process_system/sync_offline_practical5.php
 */

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

/**
 * Send JSON response
 */
function sendJson(
    bool $success,
    string $message,
    array $data = [],
    int $statusCode = 200
): void {
    http_response_code($statusCode);

    echo json_encode(
        [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/**
 * GET request
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(
        true,
        'Offline synchronization endpoint is available.',
        [
            'endpoint' => 'sync_offline_practical5.php',
            'method_required' => 'POST',
            'practical' => 5,
            'status' => 'ready'
        ]
    );
}

/**
 * Authentication
 */
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    sendJson(
        false,
        'You must be logged in before synchronizing offline data.',
        [],
        401
    );
}

$userId = (int) $_SESSION['user_id'];

/**
 * Database
 */
require_once __DIR__ . '/config.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    sendJson(
        false,
        'Database connection is not available.',
        [],
        500
    );
}

if ($conn->connect_errno) {
    sendJson(
        false,
        'Database connection failed: ' . $conn->connect_error,
        [],
        500
    );
}

$conn->set_charset('utf8mb4');

/**
 * Read JSON request
 */
$rawInput = file_get_contents('php://input');

if ($rawInput === false || trim($rawInput) === '') {
    sendJson(
        false,
        'No synchronization data was received.',
        [],
        400
    );
}

$payload = json_decode($rawInput, true);

if (!is_array($payload)) {
    sendJson(
        false,
        'Invalid synchronization data. Expected JSON object.',
        [],
        400
    );
}

/**
 * Validate practical number
 */
$practicalNumber = isset($payload['practical_number'])
    ? (int) $payload['practical_number']
    : 0;

if ($practicalNumber !== 5) {
    sendJson(
        false,
        'Invalid practical number. This endpoint accepts Practical 5 only.',
        [],
        400
    );
}

/**
 * Extract data
 */
$activityProgress = [];

if (
    isset($payload['activity_progress']) &&
    is_array($payload['activity_progress'])
) {
    $activityProgress = $payload['activity_progress'];
}

$studentRecord = [];

if (
    isset($payload['student_record']) &&
    is_array($payload['student_record'])
) {
    $studentRecord = $payload['student_record'];
}

$simulation = [];

if (
    isset($payload['simulation']) &&
    is_array($payload['simulation'])
) {
    $simulation = $payload['simulation'];
}

$measurements = [];

if (
    isset($payload['measurements']) &&
    is_array($payload['measurements'])
) {
    $measurements = $payload['measurements'];
}

/**
 * Start transaction
 */
$conn->begin_transaction();

try {

    /* ============================================================
       1. SAVE ACTIVITY PROGRESS
       ============================================================ */

    $activityStmt = $conn->prepare(
        "INSERT INTO practical_activity_progress
        (
            user_id,
            practical_number,
            activity_number,
            completed,
            updated_at
        )
        VALUES (?, 5, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            completed = VALUES(completed),
            updated_at = NOW()"
    );

    if (!$activityStmt) {
        throw new Exception(
            'Could not prepare activity progress statement: ' .
            $conn->error
        );
    }

    for ($activityNumber = 1; $activityNumber <= 8; $activityNumber++) {

        $completed = 0;

        $valueFound = false;

        if (
            array_key_exists(
                (string) $activityNumber,
                $activityProgress
            )
        ) {
            $value = $activityProgress[(string) $activityNumber];
            $valueFound = true;
        } elseif (
            array_key_exists(
                $activityNumber,
                $activityProgress
            )
        ) {
            $value = $activityProgress[$activityNumber];
            $valueFound = true;
        }

        if ($valueFound) {

            if (
                $value === true ||
                $value === 1 ||
                $value === '1' ||
                $value === 'true'
            ) {
                $completed = 1;
            }
        }

        $activityStmt->bind_param(
            "iii",
            $userId,
            $activityNumber,
            $completed
        );

        if (!$activityStmt->execute()) {
            throw new Exception(
                'Failed to save activity ' .
                $activityNumber .
                ': ' .
                $activityStmt->error
            );
        }
    }

    $activityStmt->close();


    /* ============================================================
       2. SAVE STUDENT RECORD
       ============================================================ */

    $results = '';
    $observations = '';
    $conclusion = '';

    if (isset($studentRecord['results'])) {
        $results = trim((string) $studentRecord['results']);
    }

    if (isset($studentRecord['observations'])) {
        $observations = trim((string) $studentRecord['observations']);
    }

    if (isset($studentRecord['conclusion'])) {
        $conclusion = trim((string) $studentRecord['conclusion']);
    }

    $submissionStmt = $conn->prepare(
        "INSERT INTO practical_submissions
        (
            user_id,
            practical_number,
            results,
            observations,
            conclusion,
            submitted_at,
            updated_at
        )
        VALUES (?, 5, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            results = VALUES(results),
            observations = VALUES(observations),
            conclusion = VALUES(conclusion),
            updated_at = NOW()"
    );

    if (!$submissionStmt) {
        throw new Exception(
            'Could not prepare practical submission statement: ' .
            $conn->error
        );
    }

    $submissionStmt->bind_param(
        "isss",
        $userId,
        $results,
        $observations,
        $conclusion
    );

    if (!$submissionStmt->execute()) {
        throw new Exception(
            'Failed to save practical record: ' .
            $submissionStmt->error
        );
    }

    $submissionStmt->close();


    /* ============================================================
       3. PROCESS SIMULATION
       ============================================================ */

    $simulationSaved = false;

    $simulationInput = [];
    $simulationResult = [];

    if (!empty($simulation)) {

        /*
         * Read input_data
         */
        if (isset($simulation['input_data'])) {

            if (is_array($simulation['input_data'])) {

                $simulationInput = $simulation['input_data'];

            } else {

                $decodedInput = json_decode(
                    (string) $simulation['input_data'],
                    true
                );

                if (is_array($decodedInput)) {
                    $simulationInput = $decodedInput;
                }
            }
        }

        /*
         * Read result_data
         */
        if (isset($simulation['result_data'])) {

            if (is_array($simulation['result_data'])) {

                $simulationResult = $simulation['result_data'];

            } else {

                $decodedResult = json_decode(
                    (string) $simulation['result_data'],
                    true
                );

                if (is_array($decodedResult)) {
                    $simulationResult = $decodedResult;
                }
            }
        }

        /*
         * Some offline packages may place values directly
         * inside the simulation object.
         */
        if (empty($simulationInput)) {

            $simulationInput = [
                'initialMass' =>
                    $simulation['initialMass'] ?? null,

                'retainedMass' =>
                    $simulation['retainedMass'] ?? null,

                'filtrationTime' =>
                    $simulation['filtrationTime'] ?? null
            ];
        }

        /*
         * Get input values
         */
        $initialMass = isset($simulationInput['initialMass'])
            ? (float) $simulationInput['initialMass']
            : 0.0;

        $retainedMass = isset($simulationInput['retainedMass'])
            ? (float) $simulationInput['retainedMass']
            : 0.0;

        $filtrationTime = isset($simulationInput['filtrationTime'])
            ? (float) $simulationInput['filtrationTime']
            : 0.0;

        /*
         * Support alternative naming
         */
        if (
            $initialMass <= 0 &&
            isset($simulationInput['initial_mass'])
        ) {
            $initialMass =
                (float) $simulationInput['initial_mass'];
        }

        if (
            isset($simulationInput['retained_mass'])
        ) {
            $retainedMass =
                (float) $simulationInput['retained_mass'];
        }

        if (
            $filtrationTime <= 0 &&
            isset($simulationInput['filtration_time'])
        ) {
            $filtrationTime =
                (float) $simulationInput['filtration_time'];
        }

        /*
         * Validate masses
         */
        if ($initialMass < 0) {
            $initialMass = 0;
        }

        if ($retainedMass < 0) {
            $retainedMass = 0;
        }

        if (
            $initialMass > 0 &&
            $retainedMass > $initialMass
        ) {
            $retainedMass = $initialMass;
        }

        if ($filtrationTime < 0) {
            $filtrationTime = 0;
        }

        /*
         * Calculate filtration results
         */
        if ($initialMass > 0) {

            $filtrateMass =
                $initialMass - $retainedMass;

            $retentionPercentage =
                ($retainedMass / $initialMass) * 100;

            $separationPercentage =
                ($filtrateMass / $initialMass) * 100;

        } else {

            $filtrateMass = 0;
            $retentionPercentage = 0;
            $separationPercentage = 0;
        }

        /*
         * Preserve graph information
         */
        $graphData = [];

        if (isset($simulation['graphData'])) {
            $graphData = $simulation['graphData'];
        }

        $filtrationGraph = [];

        if (isset($simulation['filtrationGraph'])) {
            $filtrationGraph =
                $simulation['filtrationGraph'];
        }

        $times = [];

        if (isset($simulation['times'])) {
            $times = $simulation['times'];
        }

        $filtrate = [];

        if (isset($simulation['filtrate'])) {
            $filtrate = $simulation['filtrate'];
        }

        $retained = [];

        if (isset($simulation['retained'])) {
            $retained = $simulation['retained'];
        }

        /*
         * Standard simulation data
         */
        $standardInput = [
            'initialMass' => $initialMass,
            'retainedMass' => $retainedMass,
            'filtrationTime' => $filtrationTime
        ];

        $standardResult = [
            'filtration' => [
                'initialMass' => $initialMass,
                'retainedMass' => $retainedMass,
                'filtrateMass' => $filtrateMass,
                'retentionPercentage' =>
                    $retentionPercentage,
                'separationPercentage' =>
                    $separationPercentage,
                'filtrationTime' =>
                    $filtrationTime
            ],

            'graphData' => $graphData,
            'filtrationGraph' => $filtrationGraph,
            'times' => $times,
            'filtrate' => $filtrate,
            'retained' => $retained
        ];

        $inputJson = json_encode(
            $standardInput,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        $resultJson = json_encode(
            $standardResult,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        if (
            $inputJson === false ||
            $resultJson === false
        ) {
            throw new Exception(
                'Could not encode simulation data.'
            );
        }

        $simulationType =
            'Filtration and Separation';

        $simulationStmt = $conn->prepare(
            "INSERT INTO simulation_results
            (
                user_id,
                practical_number,
                simulation_type,
                input_data,
                result_data,
                created_at,
                updated_at
            )
            VALUES (?, 5, ?, ?, ?, NOW(), NOW())"
        );

        if (!$simulationStmt) {
            throw new Exception(
                'Could not prepare simulation statement: ' .
                $conn->error
            );
        }

        $simulationStmt->bind_param(
            "isss",
            $userId,
            $simulationType,
            $inputJson,
            $resultJson
        );

        if (!$simulationStmt->execute()) {
            throw new Exception(
                'Failed to save simulation result: ' .
                $simulationStmt->error
            );
        }

        $simulationStmt->close();

        $simulationSaved = true;
    }


    /* ============================================================
       4. SAVE PRACTICAL MEASUREMENTS
       ============================================================ */

    /*
     * IMPORTANT:
     *
     * First use measurements sent by offline-sync.js.
     *
     * If measurements are empty, use the simulation data
     * that was just processed above.
     *
     * This prevents measurement records from being lost.
     */

    $measurementData = [];

    if (!empty($measurements)) {

        $measurementData = $measurements;

    } elseif (!empty($simulationResult)) {

        /*
         * Fallback source:
         *
         * simulation.result_data.filtration
         */
        if (
            isset($simulationResult['filtration']) &&
            is_array($simulationResult['filtration'])
        ) {
            $measurementData =
                $simulationResult['filtration'];
        }

    }

    /*
     * If simulationResult was unavailable, use the values
     * calculated above.
     */
    if (empty($measurementData) && !empty($simulation)) {

        $measurementData = [
            'initialMass' =>
                $initialMass ?? 0,

            'retainedMass' =>
                $retainedMass ?? 0,

            'filtrateMass' =>
                $filtrateMass ?? 0,

            'filtrationTime' =>
                $filtrationTime ?? 0,

            'retentionPercentage' =>
                $retentionPercentage ?? 0,

            'separationPercentage' =>
                $separationPercentage ?? 0
        ];
    }

    /*
     * Measurement definitions
     */
    $measurementMap = [

        'initialMass' => [
            'label' => 'Initial Sample Mass',
            'unit' => 'g'
        ],

        'retainedMass' => [
            'label' => 'Retained Solid Mass',
            'unit' => 'g'
        ],

        'filtrateMass' => [
            'label' => 'Filtrate Mass',
            'unit' => 'g'
        ],

        'filtrationTime' => [
            'label' => 'Filtration Time',
            'unit' => 'seconds'
        ],

        'retentionPercentage' => [
            'label' => 'Retention Percentage',
            'unit' => '%'
        ],

        'separationPercentage' => [
            'label' => 'Separation Percentage',
            'unit' => '%'
        ]
    ];

    $measurementsSaved = 0;

    /*
     * Only prepare the statement if we actually have
     * measurement data.
     */
    if (!empty($measurementData)) {

        $measurementStmt = $conn->prepare(
            "INSERT INTO practical_measurements
            (
                user_id,
                practical_number,
                measurement_name,
                measurement_value,
                unit,
                created_at,
                updated_at
            )
            VALUES (?, 5, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                measurement_value = VALUES(measurement_value),
                unit = VALUES(unit),
                updated_at = NOW()"
        );

        if (!$measurementStmt) {
            throw new Exception(
                'Could not prepare measurement statement: ' .
                $conn->error
            );
        }

        foreach ($measurementMap as $key => $info) {

            if (
                !array_key_exists(
                    $key,
                    $measurementData
                )
            ) {
                continue;
            }

            $value = $measurementData[$key];

            /*
             * Support:
             *
             * {
             *     "value": 100,
             *     "unit": "g"
             * }
             */
            if (is_array($value)) {

                if (isset($value['value'])) {
                    $value = $value['value'];
                } else {
                    continue;
                }
            }

            if (
                $value === null ||
                $value === ''
            ) {
                continue;
            }

            if (!is_numeric($value)) {
                continue;
            }

            $measurementName =
                $info['label'];

            $measurementValue =
                (float) $value;

            $unit =
                $info['unit'];

            $measurementStmt->bind_param(
                "isds",
                $userId,
                $measurementName,
                $measurementValue,
                $unit
            );

            if (!$measurementStmt->execute()) {
                throw new Exception(
                    'Failed to save measurement "' .
                    $measurementName .
                    '": ' .
                    $measurementStmt->error
                );
            }

            $measurementsSaved++;
        }

        $measurementStmt->close();
    }


    /* ============================================================
       5. UPDATE PRACTICAL PROGRESS
       ============================================================ */

    $completedActivities = 0;

    for ($i = 1; $i <= 8; $i++) {

        $value = false;

        if (
            array_key_exists(
                (string) $i,
                $activityProgress
            )
        ) {
            $value =
                $activityProgress[(string) $i];

        } elseif (
            array_key_exists(
                $i,
                $activityProgress
            )
        ) {
            $value =
                $activityProgress[$i];
        }

        if (
            $value === true ||
            $value === 1 ||
            $value === '1' ||
            $value === 'true'
        ) {
            $completedActivities++;
        }
    }

    $practicalStatus = 'in_progress';

    if (
        $completedActivities >= 8 &&
        trim($results) !== '' &&
        trim($observations) !== '' &&
        trim($conclusion) !== ''
    ) {
        $practicalStatus = 'completed';
    }

    $progressStmt = $conn->prepare(
        "INSERT INTO practical_progress
        (
            user_id,
            practical_number,
            status,
            started_at,
            completed_at,
            updated_at
        )
        VALUES
        (
            ?,
            5,
            ?,
            NOW(),
            CASE
                WHEN ? = 'completed'
                THEN NOW()
                ELSE NULL
            END,
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            completed_at =
                CASE
                    WHEN VALUES(status) = 'completed'
                    THEN NOW()
                    ELSE completed_at
                END,
            updated_at = NOW()"
    );

    if (!$progressStmt) {
        throw new Exception(
            'Could not prepare progress statement: ' .
            $conn->error
        );
    }

    $progressStmt->bind_param(
        "iss",
        $userId,
        $practicalStatus,
        $practicalStatus
    );

    if (!$progressStmt->execute()) {
        throw new Exception(
            'Failed to update practical progress: ' .
            $progressStmt->error
        );
    }

    $progressStmt->close();


    /* ============================================================
       6. COMMIT
       ============================================================ */

    $conn->commit();


    /* ============================================================
       7. SUCCESS RESPONSE
       ============================================================ */

    sendJson(
        true,
        'Offline Practical 5 data synchronized successfully.',
        [
            'practical_number' => 5,
            'practical_name' =>
                'Filtration and Separation',

            'activities_saved' =>
                $completedActivities,

            'status' =>
                $practicalStatus,

            'simulation_saved' =>
                $simulationSaved,

            'measurements_received' =>
                count($measurements),

            'measurements_saved' =>
                $measurementsSaved,

            'measurement_source' =>
                !empty($measurements)
                    ? 'offline_measurements'
                    : (
                        !empty($measurementData)
                            ? 'simulation_fallback'
                            : 'none'
                    ),

            'student_record_saved' => (
                $results !== '' ||
                $observations !== '' ||
                $conclusion !== ''
            )
        ]
    );

} catch (Throwable $e) {

    /*
     * Roll back everything if any part fails.
     */
    $conn->rollback();

    error_log(
        'Offline Practical 5 synchronization error: ' .
        $e->getMessage()
    );

    sendJson(
        false,
        'Synchronization failed: ' .
        $e->getMessage(),
        [],
        500
    );
}
?>