<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Practical 3 Offline Synchronization Endpoint
|--------------------------------------------------------------------------
|
| Receives offline Practical 3 data and saves it to MySQL.
|
| Saves:
| 1. Activity progress
| 2. Overall practical progress
| 3. Student practical submission
| 4. Thermal processing simulation result
|
|--------------------------------------------------------------------------
*/

header("Content-Type: application/json; charset=UTF-8");


/*
|--------------------------------------------------------------------------
| Start Session
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Helper: JSON Response
|--------------------------------------------------------------------------
*/

function jsonResponse(
    bool $success,
    string $message,
    array $extra = [],
    int $statusCode = 200
): void {

    http_response_code($statusCode);

    echo json_encode(
        array_merge(
            [
                "success" => $success,
                "message" => $message
            ],
            $extra
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Endpoint Status
|--------------------------------------------------------------------------
|
| Opening the PHP file directly with GET can be used to verify
| that the synchronization endpoint is available.
|
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    jsonResponse(
        true,
        "Practical 3 synchronization endpoint is available."
    );

}


/*
|--------------------------------------------------------------------------
| Only POST is allowed for synchronization
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    jsonResponse(
        false,
        "Only POST requests are allowed.",
        [],
        405
    );

}


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["user_id"]) ||
    !is_numeric($_SESSION["user_id"])
) {

    jsonResponse(
        false,
        "You must be logged in to synchronize Practical 3.",
        [],
        401
    );

}


$userId = (int) $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| Load Database Configuration
|--------------------------------------------------------------------------
*/

$configFile = __DIR__ . "/config.php";


if (!file_exists($configFile)) {

    jsonResponse(
        false,
        "Database configuration file was not found.",
        [],
        500
    );

}


require_once $configFile;


/*
|--------------------------------------------------------------------------
| Check Database Connection
|--------------------------------------------------------------------------
*/

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    jsonResponse(
        false,
        "Database connection is not available.",
        [],
        500
    );

}


if ($conn->connect_errno) {

    jsonResponse(
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
| Practical Number
|--------------------------------------------------------------------------
*/

$practicalNumber = 3;


/*
|--------------------------------------------------------------------------
| Read Request Body
|--------------------------------------------------------------------------
*/

$rawInput = file_get_contents("php://input");


if ($rawInput === false || trim($rawInput) === "") {

    jsonResponse(
        false,
        "No synchronization data was received.",
        [],
        400
    );

}


$data = json_decode(
    $rawInput,
    true
);


if (
    json_last_error() !== JSON_ERROR_NONE ||
    !is_array($data)
) {

    jsonResponse(
        false,
        "The synchronization data is not valid JSON.",
        [],
        400
    );

}


/*
|--------------------------------------------------------------------------
| Verify Practical Number
|--------------------------------------------------------------------------
*/

$receivedPractical =
    isset($data["practical_number"])
        ? (int) $data["practical_number"]
        : 0;


if ($receivedPractical !== $practicalNumber) {

    jsonResponse(
        false,
        "Invalid practical number. Practical 3 was expected.",
        [],
        400
    );

}


/*
|--------------------------------------------------------------------------
| Check Required Tables
|--------------------------------------------------------------------------
*/

$requiredTables = [

    "practical_activity_progress",

    "practical_progress",

    "practical_submissions",

    "simulation_results"

];


foreach ($requiredTables as $table) {

    $tableEscaped =
        $conn->real_escape_string($table);


    $checkTable =
        $conn->query(
            "SHOW TABLES LIKE '$tableEscaped'"
        );


    if (
        !$checkTable ||
        $checkTable->num_rows === 0
    ) {

        jsonResponse(
            false,
            "Required database table '$table' was not found.",
            [],
            500
        );

    }

}


/*
|--------------------------------------------------------------------------
| Extract Data
|--------------------------------------------------------------------------
*/

$activities =
    isset($data["activities"]) &&
    is_array($data["activities"])
        ? $data["activities"]
        : [];


$record =
    isset($data["record"]) &&
    is_array($data["record"])
        ? $data["record"]
        : [];


$simulation =
    isset($data["simulation"]) &&
    is_array($data["simulation"])
        ? $data["simulation"]
        : null;


/*
|--------------------------------------------------------------------------
| Validate Activity Numbers
|--------------------------------------------------------------------------
*/

$validatedActivities = [];


foreach ($activities as $activity) {

    if (!is_array($activity)) {
        continue;
    }


    $activityNumber =
        isset($activity["activity_number"])
            ? (int) $activity["activity_number"]
            : 0;


    if (
        $activityNumber < 1 ||
        $activityNumber > 9
    ) {

        continue;

    }


    $completed =
        !empty($activity["completed"])
            ? 1
            : 0;


    $validatedActivities[] = [

        "activity_number" =>
            $activityNumber,

        "completed" =>
            $completed

    ];

}


/*
|--------------------------------------------------------------------------
| Remove Duplicate Activity Numbers
|--------------------------------------------------------------------------
*/

$uniqueActivities = [];


foreach ($validatedActivities as $activity) {

    $uniqueActivities[
        $activity["activity_number"]
    ] = $activity["completed"];

}


/*
|--------------------------------------------------------------------------
| Prepare Student Record
|--------------------------------------------------------------------------
*/

$results =
    isset($record["results"]) &&
    is_string($record["results"])
        ? trim($record["results"])
        : "";


$observations =
    isset($record["observations"]) &&
    is_string($record["observations"])
        ? trim($record["observations"])
        : "";


$conclusion =
    isset($record["conclusion"]) &&
    is_string($record["conclusion"])
        ? trim($record["conclusion"])
        : "";


/*
|--------------------------------------------------------------------------
| Prepare Simulation Data
|--------------------------------------------------------------------------
*/

$simulationInputData = null;

$simulationResultData = null;


if ($simulation !== null) {

    if (
        isset($simulation["inputs"]) &&
        is_array($simulation["inputs"])
    ) {

        $simulationInputData =
            json_encode(
                $simulation["inputs"],
                JSON_UNESCAPED_UNICODE
            );

    }


    /*
     * Save the calculated simulation output.
     *
     * The complete simulation result is retained so that
     * the stored result can be inspected later.
     */

    $simulationResultData =
        json_encode(
            $simulation,
            JSON_UNESCAPED_UNICODE
        );

}


/*
|--------------------------------------------------------------------------
| Start Transaction
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();


try {


    /*
    |--------------------------------------------------------------------------
    | 1. Save Activity Progress
    |--------------------------------------------------------------------------
    */

    $activitiesSaved = 0;


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
        (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            completed = VALUES(completed),
            updated_at = NOW()
    ";


    $activityStatement =
        $conn->prepare(
            $activitySql
        );


    if (!$activityStatement) {

        throw new Exception(
            "Unable to prepare activity progress statement: " .
            $conn->error
        );

    }


    foreach (
        $uniqueActivities as
        $activityNumber => $completed
    ) {

        $activityStatement->bind_param(
            "iiii",
            $userId,
            $practicalNumber,
            $activityNumber,
            $completed
        );


        if (
            !$activityStatement->execute()
        ) {

            throw new Exception(
                "Unable to save activity " .
                $activityNumber .
                ": " .
                $activityStatement->error
            );

        }


        $activitiesSaved++;

    }


    $activityStatement->close();


    /*
    |--------------------------------------------------------------------------
    | 2. Calculate Overall Practical Progress
    |--------------------------------------------------------------------------
    */

    $completedCount = 0;


    foreach (
        $uniqueActivities as
        $activityNumber => $completed
    ) {

        if ((int) $completed === 1) {

            $completedCount++;

        }

    }


    /*
     * Practical 3 has 9 activities.
     */

    $totalActivities = 9;


    $allActivitiesCompleted =
        $completedCount === $totalActivities;


    $overallStatus =
        $allActivitiesCompleted
            ? "completed"
            : (
                $completedCount > 0
                    ? "in_progress"
                    : "not_started"
            );


    /*
    |--------------------------------------------------------------------------
    | 3. Save Overall Practical Progress
    |--------------------------------------------------------------------------
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
                ?,
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

    } else {

        $progressSql = "
            INSERT INTO practical_progress
            (
                user_id,
                practical_number,
                status,
                started_at,
                updated_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                updated_at = NOW()
        ";

    }


    $progressStatement =
        $conn->prepare(
            $progressSql
        );


    if (!$progressStatement) {

        throw new Exception(
            "Unable to prepare practical progress statement: " .
            $conn->error
        );

    }


    $progressStatement->bind_param(
        "iis",
        $userId,
        $practicalNumber,
        $overallStatus
    );


    if (
        !$progressStatement->execute()
    ) {

        throw new Exception(
            "Unable to save overall practical progress: " .
            $progressStatement->error
        );

    }


    $progressStatement->close();


    /*
    |--------------------------------------------------------------------------
    | 4. Save Student Practical Submission
    |--------------------------------------------------------------------------
    */

    $recordSaved = false;


    /*
     * Save the record when at least one of the three
     * fields contains information.
     */

    if (
        $results !== "" ||
        $observations !== "" ||
        $conclusion !== ""
    ) {

        $submissionSql = "
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
                ?,
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
                updated_at = NOW()
        ";


        $submissionStatement =
            $conn->prepare(
                $submissionSql
            );


        if (!$submissionStatement) {

            throw new Exception(
                "Unable to prepare practical submission statement: " .
                $conn->error
            );

        }


        $submissionStatement->bind_param(
            "iisss",
            $userId,
            $practicalNumber,
            $results,
            $observations,
            $conclusion
        );


        if (
            !$submissionStatement->execute()
        ) {

            throw new Exception(
                "Unable to save student practical record: " .
                $submissionStatement->error
            );

        }


        $submissionStatement->close();


        $recordSaved = true;

    }


    /*
    |--------------------------------------------------------------------------
    | 5. Save Thermal Simulation
    |--------------------------------------------------------------------------
    */

    $simulationSaved = false;


    if (
        $simulation !== null &&
        $simulationResultData !== null
    ) {

        /*
         * simulation_type identifies the Practical 3
         * thermal-processing simulation.
         */

        $simulationType =
            "thermal_processing";


        /*
         * If input data is unavailable, store an empty
         * JSON object instead of NULL.
         */

        if (
            $simulationInputData === null
        ) {

            $simulationInputData = "{}";

        }


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
                ?,
                ?,
                ?,
                ?,
                NOW(),
                NOW()
            )
        ";


        $simulationStatement =
            $conn->prepare(
                $simulationSql
            );


        if (!$simulationStatement) {

            throw new Exception(
                "Unable to prepare simulation statement: " .
                $conn->error
            );

        }


        $simulationStatement->bind_param(
            "iisss",
            $userId,
            $practicalNumber,
            $simulationType,
            $simulationInputData,
            $simulationResultData
        );


        if (
            !$simulationStatement->execute()
        ) {

            throw new Exception(
                "Unable to save thermal simulation: " .
                $simulationStatement->error
            );

        }


        $simulationStatement->close();


        $simulationSaved = true;

    }


    /*
    |--------------------------------------------------------------------------
    | Commit Transaction
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | Successful Response
    |--------------------------------------------------------------------------
    */

    jsonResponse(
        true,
        "Practical 3 synchronized successfully.",
        [

            "practical_number" =>
                $practicalNumber,

            "activities_saved" =>
                $activitiesSaved,

            "activities_completed" =>
                $completedCount,

            "total_activities" =>
                $totalActivities,

            "practical_status" =>
                $overallStatus,

            "record_saved" =>
                $recordSaved,

            "simulation_saved" =>
                $simulationSaved

        ]
    );


} catch (Throwable $exception) {


    /*
    |--------------------------------------------------------------------------
    | Rollback
    |--------------------------------------------------------------------------
    */

    $conn->rollback();


    /*
    |--------------------------------------------------------------------------
    | Return Actual Error
    |--------------------------------------------------------------------------
    |
    | This is useful during local XAMPP testing because it tells us
    | exactly what failed instead of only returning "sync failed".
    |
    |--------------------------------------------------------------------------
    */

    jsonResponse(
        false,
        "Practical 3 synchronization failed: " .
        $exception->getMessage(),
        [],
        500
    );

}

?>