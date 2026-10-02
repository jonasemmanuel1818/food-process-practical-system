<?php

declare(strict_types=1);

session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

/*
|--------------------------------------------------------------------------
| Practical 4 Offline Synchronization Endpoint
|--------------------------------------------------------------------------
| File:
| /food_process_system/sync_offline_practical4.php
|--------------------------------------------------------------------------
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
            "success" => $success,
            "message" => $message,
            "data" => $data
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| GET REQUEST
|--------------------------------------------------------------------------
| This makes it possible to test the endpoint directly in the browser.
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    sendJson(
        true,
        "Practical 4 synchronization endpoint is available.",
        [
            "endpoint" => "sync_offline_practical4.php",
            "method_required" => "POST",
            "practical_number" => 4,
            "server_status" => "ready",
            "logged_in" => isset($_SESSION["user_id"])
        ]
    );
}

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["user_id"]) ||
    empty($_SESSION["user_id"])
) {

    sendJson(
        false,
        "You must be logged in before synchronizing Practical 4 data.",
        [],
        401
    );
}

$userId = (int) $_SESSION["user_id"];

if ($userId <= 0) {

    sendJson(
        false,
        "Invalid logged-in user.",
        [],
        401
    );
}

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/config.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    sendJson(
        false,
        "Database connection is not available.",
        [],
        500
    );
}

if ($conn->connect_errno) {

    sendJson(
        false,
        "Database connection failed: " .
        $conn->connect_error,
        [],
        500
    );
}

$conn->set_charset("utf8mb4");

/*
|--------------------------------------------------------------------------
| READ JSON DATA
|--------------------------------------------------------------------------
*/

$rawInput = file_get_contents("php://input");

if (
    $rawInput === false ||
    trim($rawInput) === ""
) {

    sendJson(
        false,
        "No synchronization data was received.",
        [],
        400
    );
}

$payload = json_decode(
    $rawInput,
    true
);

if (
    !is_array($payload) ||
    json_last_error() !== JSON_ERROR_NONE
) {

    sendJson(
        false,
        "Invalid JSON synchronization data.",
        [],
        400
    );
}

/*
|--------------------------------------------------------------------------
| VERIFY PRACTICAL NUMBER
|--------------------------------------------------------------------------
*/

$practicalNumber =
    isset($payload["practical_number"])
        ? (int) $payload["practical_number"]
        : 0;

if ($practicalNumber !== 4) {

    sendJson(
        false,
        "Invalid practical number. Practical 4 was expected.",
        [],
        400
    );
}

/*
|--------------------------------------------------------------------------
| CHECK REQUIRED TABLES
|--------------------------------------------------------------------------
*/

$requiredTables = [
    "practical_activity_progress",
    "practical_progress",
    "practical_submissions",
    "simulation_results"
];

foreach ($requiredTables as $table) {

    $checkTable = $conn->query(
        "SHOW TABLES LIKE '" .
        $conn->real_escape_string($table) .
        "'"
    );

    if (!$checkTable || $checkTable->num_rows === 0) {

        sendJson(
            false,
            "Required database table is missing: " . $table,
            [
                "missing_table" => $table
            ],
            500
        );
    }

    $checkTable->free();
}

/*
|--------------------------------------------------------------------------
| DATA PREPARATION
|--------------------------------------------------------------------------
*/

$progress =
    isset($payload["progress"]) &&
    is_array($payload["progress"])
        ? $payload["progress"]
        : [];

$record =
    isset($payload["record"]) &&
    is_array($payload["record"])
        ? $payload["record"]
        : [];

$simulations =
    isset($payload["simulations"]) &&
    is_array($payload["simulations"])
        ? $payload["simulations"]
        : [];

/*
|--------------------------------------------------------------------------
| CALCULATE ACTIVITY COMPLETION
|--------------------------------------------------------------------------
*/

$completedActivities = 0;

for ($activity = 1; $activity <= 12; $activity++) {

    if (
        isset($progress[$activity]) &&
        $progress[$activity] === true
    ) {
        $completedActivities++;
    }
}

/*
|--------------------------------------------------------------------------
| OVERALL PRACTICAL STATUS
|--------------------------------------------------------------------------
*/

if ($completedActivities === 0) {

    $overallStatus = "not_started";

} elseif ($completedActivities === 12) {

    $overallStatus = "completed";

} else {

    $overallStatus = "in_progress";
}

/*
|--------------------------------------------------------------------------
| STUDENT RECORD
|--------------------------------------------------------------------------
*/

$results =
    isset($record["results"])
        ? trim((string) $record["results"])
        : "";

$observations =
    isset($record["observations"])
        ? trim((string) $record["observations"])
        : "";

$conclusion =
    isset($record["conclusion"])
        ? trim((string) $record["conclusion"])
        : "";

$recordHasData =
    (
        $results !== "" ||
        $observations !== "" ||
        $conclusion !== ""
    );

/*
|--------------------------------------------------------------------------
| START TRANSACTION
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | 1. ACTIVITY PROGRESS
    |--------------------------------------------------------------------------
    */

    $activitySql = "
        INSERT INTO practical_activity_progress
        (
            user_id,
            practical_number,
            activity_number,
            completed,
            updated_at
        )
        VALUES
        (?, 4, ?, ?, NOW())

        ON DUPLICATE KEY UPDATE
            completed = VALUES(completed),
            updated_at = NOW()
    ";

    $activityStmt =
        $conn->prepare($activitySql);

    if (!$activityStmt) {

        throw new Exception(
            "Unable to prepare activity progress query: " .
            $conn->error
        );
    }

    for ($activity = 1; $activity <= 12; $activity++) {

        $completed =
            (
                isset($progress[$activity]) &&
                $progress[$activity] === true
            )
                ? 1
                : 0;

        $activityStmt->bind_param(
            "iii",
            $userId,
            $activity,
            $completed
        );

        if (!$activityStmt->execute()) {

            throw new Exception(
                "Unable to save activity " .
                $activity .
                ": " .
                $activityStmt->error
            );
        }
    }

    $activityStmt->close();

    /*
    |--------------------------------------------------------------------------
    | 2. OVERALL PRACTICAL PROGRESS
    |--------------------------------------------------------------------------
    */

    /*
     * We use separate queries for completed/incomplete so that
     * completed_at is handled safely.
     */

    if ($overallStatus === "completed") {

        $progressSql = "
            INSERT INTO practical_progress
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
                4,
                ?,
                NOW(),
                NOW(),
                NOW()
            )

            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                completed_at = NOW(),
                updated_at = NOW()
        ";

        $progressStmt =
            $conn->prepare($progressSql);

        if (!$progressStmt) {

            throw new Exception(
                "Unable to prepare completed progress query: " .
                $conn->error
            );
        }

        $progressStmt->bind_param(
            "is",
            $userId,
            $overallStatus
        );

    } else {

        $progressSql = "
            INSERT INTO practical_progress
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
                4,
                ?,
                NOW(),
                NULL,
                NOW()
            )

            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                updated_at = NOW()
        ";

        $progressStmt =
            $conn->prepare($progressSql);

        if (!$progressStmt) {

            throw new Exception(
                "Unable to prepare progress query: " .
                $conn->error
            );
        }

        $progressStmt->bind_param(
            "is",
            $userId,
            $overallStatus
        );
    }

    if (!$progressStmt->execute()) {

        throw new Exception(
            "Unable to save overall Practical 4 progress: " .
            $progressStmt->error
        );
    }

    $progressStmt->close();

    /*
    |--------------------------------------------------------------------------
    | 3. STUDENT RECORD
    |--------------------------------------------------------------------------
    */

    if ($recordHasData) {

        $recordSql = "
            INSERT INTO practical_submissions
            (
                user_id,
                practical_number,
                results,
                observations,
                conclusion,
                submitted_at,
                updated_at
            )
            VALUES
            (
                ?,
                4,
                ?,
                ?,
                ?,
                NOW(),
                NOW()
            )

            ON DUPLICATE KEY UPDATE
                results = VALUES(results),
                observations = VALUES(observations),
                conclusion = VALUES(conclusion),
                submitted_at = NOW(),
                updated_at = NOW()
        ";

        $recordStmt =
            $conn->prepare($recordSql);

        if (!$recordStmt) {

            throw new Exception(
                "Unable to prepare student record query: " .
                $conn->error
            );
        }

        $recordStmt->bind_param(
            "isss",
            $userId,
            $results,
            $observations,
            $conclusion
        );

        if (!$recordStmt->execute()) {

            throw new Exception(
                "Unable to save student record: " .
                $recordStmt->error
            );
        }

        $recordStmt->close();
    }

    /*
    |--------------------------------------------------------------------------
    | 4. SIMULATION RESULTS
    |--------------------------------------------------------------------------
    */

    $savedSimulations = 0;

    if (count($simulations) > 0) {

        $simulationSql = "
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
                4,
                ?,
                ?,
                ?,
                NOW(),
                NOW()
            )
        ";

        $simulationStmt =
            $conn->prepare(
                $simulationSql
            );

        if (!$simulationStmt) {

            throw new Exception(
                "Unable to prepare simulation query: " .
                $conn->error
            );
        }

        foreach ($simulations as $simulation) {

            if (!is_array($simulation)) {
                continue;
            }

            $simulationType =
                isset($simulation["simulation_type"])
                    ? trim(
                        (string)
                        $simulation["simulation_type"]
                    )
                    : "";

            if ($simulationType === "") {
                continue;
            }

            $inputData =
                isset($simulation["input_data"])
                    ? $simulation["input_data"]
                    : [];

            $resultData =
                isset($simulation["result_data"])
                    ? $simulation["result_data"]
                    : [];

            $inputJson =
                json_encode(
                    $inputData,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                );

            $resultJson =
                json_encode(
                    $resultData,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                );

            if ($inputJson === false) {
                $inputJson = "{}";
            }

            if ($resultJson === false) {
                $resultJson = "{}";
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
                    "Unable to save " .
                    $simulationType .
                    " simulation: " .
                    $simulationStmt->error
                );
            }

            $savedSimulations++;
        }

        $simulationStmt->close();
    }

    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $conn->commit();

    /*
    |--------------------------------------------------------------------------
    | SUCCESS RESPONSE
    |--------------------------------------------------------------------------
    */

    sendJson(
        true,
        "Practical 4 synchronized successfully.",
        [
            "user_id" =>
                $userId,

            "practical_number" =>
                4,

            "completed_activities" =>
                $completedActivities,

            "total_activities" =>
                12,

            "status" =>
                $overallStatus,

            "record_saved" =>
                $recordHasData,

            "simulations_saved" =>
                $savedSimulations
        ]
    );

} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    $conn->rollback();

    error_log(
        "Practical 4 synchronization error: " .
        $e->getMessage()
    );

    sendJson(
        false,
        "Synchronization failed: " .
        $e->getMessage(),
        [
            "practical_number" => 4
        ],
        500
    );
}
?>