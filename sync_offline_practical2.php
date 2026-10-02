<?php

declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| GET
|--------------------------------------------------------------------------
| Used to confirm that the synchronization endpoint is available.
*/

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    echo json_encode([
        "success" => true,
        "message" => "Practical 2 synchronization endpoint is available."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| POST ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["user_id"]) ||
    !is_numeric($_SESSION["user_id"])
) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "You must be logged in to synchronize Practical 2."
    ]);

    exit;
}

$userId =
    (int) $_SESSION["user_id"];


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/config.php";

if (!isset($conn) || !$conn) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Database connection is not available."
    ]);

    exit;
}

mysqli_set_charset(
    $conn,
    "utf8mb4"
);


/*
|--------------------------------------------------------------------------
| READ JSON REQUEST
|--------------------------------------------------------------------------
*/

$rawInput =
    file_get_contents("php://input");

if (
    $rawInput === false ||
    trim($rawInput) === ""
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "No synchronization data was received."
    ]);

    exit;
}

$data =
    json_decode(
        $rawInput,
        true
    );

if (
    !is_array($data)
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON synchronization data."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| PRACTICAL NUMBER VALIDATION
|--------------------------------------------------------------------------
*/

$practicalNumber =
    isset($data["practical_number"])
        ? (int) $data["practical_number"]
        : 0;

if ($practicalNumber !== 2) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid practical number. Practical 2 was expected."
    ]);

    exit;
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
    "simulation_results",
    "practical_measurements"

];

foreach ($requiredTables as $table) {

    $tableEscaped =
        mysqli_real_escape_string(
            $conn,
            $table
        );

    $checkTable =
        mysqli_query(
            $conn,
            "SHOW TABLES LIKE '$tableEscaped'"
        );

    if (
        !$checkTable ||
        mysqli_num_rows($checkTable) === 0
    ) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" =>
                "Required database table '$table' does not exist."
        ]);

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| EXTRACT DATA
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
        : [];


/*
|--------------------------------------------------------------------------
| PREPARE CENTRIFUGATION DATA
|--------------------------------------------------------------------------
*/

$centrifugation =
    isset($simulation["centrifugation"]) &&
    is_array($simulation["centrifugation"])
        ? $simulation["centrifugation"]
        : [];


/*
|--------------------------------------------------------------------------
| PREPARE SIEVE DATA
|--------------------------------------------------------------------------
*/

$sieveAnalysis =
    isset($simulation["sieve_analysis"]) &&
    is_array($simulation["sieve_analysis"])
        ? $simulation["sieve_analysis"]
        : [];


/*
|--------------------------------------------------------------------------
| START TRANSACTION
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction($conn);

try {

    /*
    |--------------------------------------------------------------------------
    | 1. SAVE ACTIVITY PROGRESS
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
        (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            completed = VALUES(completed),
            updated_at = NOW()
    ";

    $activityStmt =
        mysqli_prepare(
            $conn,
            $activitySql
        );

    if (!$activityStmt) {

        throw new Exception(
            "Unable to prepare activity progress statement."
        );
    }

    $activitiesSaved = 0;
    $activitiesCompleted = 0;

    foreach (
        $activities as $activity
    ) {

        if (
            !is_array($activity)
        ) {
            continue;
        }

        $activityNumber =
            isset($activity["activity_number"])
                ? (int) $activity["activity_number"]
                : 0;

        $completed =
            !empty(
                $activity["completed"]
            )
                ? 1
                : 0;

        if (
            $activityNumber < 1 ||
            $activityNumber > 9
        ) {
            continue;
        }

        mysqli_stmt_bind_param(
            $activityStmt,
            "iiii",
            $userId,
            $practicalNumber,
            $activityNumber,
            $completed
        );

        if (
            !mysqli_stmt_execute(
                $activityStmt
            )
        ) {

            throw new Exception(
                "Unable to save activity $activityNumber."
            );
        }

        $activitiesSaved++;

        if ($completed === 1) {
            $activitiesCompleted++;
        }
    }

    mysqli_stmt_close(
        $activityStmt
    );


    /*
    |--------------------------------------------------------------------------
    | 2. DETERMINE PRACTICAL STATUS
    |--------------------------------------------------------------------------
    */

    if (
        $activitiesCompleted <= 0
    ) {

        $practicalStatus =
            "not_started";

    } elseif (
        $activitiesCompleted >= 9
    ) {

        $practicalStatus =
            "completed";

    } else {

        $practicalStatus =
            "in_progress";
    }


    /*
    |--------------------------------------------------------------------------
    | 3. SAVE OVERALL PRACTICAL PROGRESS
    |--------------------------------------------------------------------------
    */

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
            updated_at = NOW()
    ";

    $progressStmt =
        mysqli_prepare(
            $conn,
            $progressSql
        );

    if (!$progressStmt) {

        throw new Exception(
            "Unable to prepare practical progress statement."
        );
    }

    mysqli_stmt_bind_param(
        $progressStmt,
        "iiss",
        $userId,
        $practicalNumber,
        $practicalStatus,
        $practicalStatus
    );

    if (
        !mysqli_stmt_execute(
            $progressStmt
        )
    ) {

        throw new Exception(
            "Unable to save overall practical progress."
        );
    }

    mysqli_stmt_close(
        $progressStmt
    );


    /*
    |--------------------------------------------------------------------------
    | 4. SAVE STUDENT PRACTICAL RECORD
    |--------------------------------------------------------------------------
    */

    $studentResults =
        isset($record["results"])
            ? trim(
                (string) $record["results"]
            )
            : "";

    $studentObservations =
        isset($record["observations"])
            ? trim(
                (string) $record["observations"]
            )
            : "";

    $studentConclusion =
        isset($record["conclusion"])
            ? trim(
                (string) $record["conclusion"]
            )
            : "";

    $recordSaved =
        false;

    if (
        $studentResults !== "" ||
        $studentObservations !== "" ||
        $studentConclusion !== ""
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
            (?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                results = VALUES(results),
                observations = VALUES(observations),
                conclusion = VALUES(conclusion),
                updated_at = NOW()
        ";

        $submissionStmt =
            mysqli_prepare(
                $conn,
                $submissionSql
            );

        if (!$submissionStmt) {

            throw new Exception(
                "Unable to prepare practical submission statement."
            );
        }

        mysqli_stmt_bind_param(
            $submissionStmt,
            "iisss",
            $userId,
            $practicalNumber,
            $studentResults,
            $studentObservations,
            $studentConclusion
        );

        if (
            !mysqli_stmt_execute(
                $submissionStmt
            )
        ) {

            throw new Exception(
                "Unable to save the Practical 2 student record."
            );
        }

        mysqli_stmt_close(
            $submissionStmt
        );

        $recordSaved =
            true;
    }


    /*
    |--------------------------------------------------------------------------
    | 5. SAVE CENTRIFUGATION SIMULATION
    |--------------------------------------------------------------------------
    */

    $centrifugationSaved =
        false;

    if (
        !empty($centrifugation)
    ) {

        $radius =
            isset(
                $centrifugation["radius"]
            )
                ? (float)
                    $centrifugation["radius"]
                : 0;

        $rpm =
            isset(
                $centrifugation["rpm"]
            )
                ? (float)
                    $centrifugation["rpm"]
                : 0;

        $rcf =
            isset(
                $centrifugation["rcf"]
            )
                ? (float)
                    $centrifugation["rcf"]
                : 0;

        if (
            $radius > 0 &&
            $rpm > 0 &&
            $rcf >= 0
        ) {

            $inputData = json_encode(
                [
                    "radius" =>
                        $radius,

                    "rpm" =>
                        $rpm
                ],
                JSON_UNESCAPED_UNICODE
            );

            $resultData = json_encode(
                [
                    "rcf" =>
                        $rcf,

                    "formula" =>
                        "RCF = 1.118 × 10^-5 × r × RPM^2"
                ],
                JSON_UNESCAPED_UNICODE
            );

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

            $simulationStmt =
                mysqli_prepare(
                    $conn,
                    $simulationSql
                );

            if (!$simulationStmt) {

                throw new Exception(
                    "Unable to prepare centrifugation simulation statement."
                );
            }

            $simulationType =
                "centrifugation";

            mysqli_stmt_bind_param(
                $simulationStmt,
                "iisss",
                $userId,
                $practicalNumber,
                $simulationType,
                $inputData,
                $resultData
            );

            if (
                !mysqli_stmt_execute(
                    $simulationStmt
                )
            ) {

                throw new Exception(
                    "Unable to save the centrifugation simulation."
                );
            }

            mysqli_stmt_close(
                $simulationStmt
            );

            $centrifugationSaved =
                true;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | 6. SAVE SIEVE MEASUREMENTS
    |--------------------------------------------------------------------------
    */

    $sieveMeasurementsSaved =
        0;

    $sieveRows =
        isset(
            $sieveAnalysis["rows"]
        ) &&
        is_array(
            $sieveAnalysis["rows"]
        )
            ? $sieveAnalysis["rows"]
            : [];

    if (
        !empty($sieveRows)
    ) {

        $measurementSql = "
            INSERT INTO practical_measurements
            (
                user_id,
                practical_number,
                measurement_name,
                measurement_value,
                unit,
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
            ON DUPLICATE KEY UPDATE
                measurement_value =
                    VALUES(measurement_value),
                unit =
                    VALUES(unit),
                updated_at =
                    NOW()
        ";

        $measurementStmt =
            mysqli_prepare(
                $conn,
                $measurementSql
            );

        if (!$measurementStmt) {

            throw new Exception(
                "Unable to prepare sieve measurement statement."
            );
        }

        foreach (
            $sieveRows as $index => $row
        ) {

            if (
                !is_array($row)
            ) {
                continue;
            }

            $size =
                isset(
                    $row["size"]
                )
                    ? trim(
                        (string)
                        $row["size"]
                    )
                    : "";

            $mass =
                isset(
                    $row["mass"]
                )
                    ? (float)
                        $row["mass"]
                    : 0;

            if (
                $size === "" ||
                $mass < 0
            ) {
                continue;
            }

            $measurementName =
                "Sieve " .
                ((int) $index + 1) .
                " - " .
                $size;

            $unit =
                "g";

            mysqli_stmt_bind_param(
                $measurementStmt,
                "iisds",
                $userId,
                $practicalNumber,
                $measurementName,
                $mass,
                $unit
            );

            if (
                !mysqli_stmt_execute(
                    $measurementStmt
                )
            ) {

                throw new Exception(
                    "Unable to save sieve measurement."
                );
            }

            $sieveMeasurementsSaved++;
        }

        mysqli_stmt_close(
            $measurementStmt
        );
    }


    /*
    |--------------------------------------------------------------------------
    | 7. SAVE COMPLETE SIEVE SIMULATION
    |--------------------------------------------------------------------------
    */

    $sieveSimulationSaved =
        false;

    if (
        !empty($sieveAnalysis)
    ) {

        $totalSampleMass =
            isset(
                $sieveAnalysis["total_sample_mass"]
            )
                ? (float)
                    $sieveAnalysis[
                        "total_sample_mass"
                    ]
                : 0;

        $totalRetained =
            isset(
                $sieveAnalysis["total_retained"]
            )
                ? (float)
                    $sieveAnalysis[
                        "total_retained"
                    ]
                : 0;

        $unaccountedMass =
            isset(
                $sieveAnalysis["unaccounted_mass"]
            )
                ? (float)
                    $sieveAnalysis[
                        "unaccounted_mass"
                    ]
                : 0;

        $inputData = json_encode(
            [
                "total_sample_mass" =>
                    $totalSampleMass,

                "rows" =>
                    $sieveRows
            ],
            JSON_UNESCAPED_UNICODE
        );

        $resultData = json_encode(
            [
                "total_retained" =>
                    $totalRetained,

                "unaccounted_mass" =>
                    $unaccountedMass,

                "rows" =>
                    $sieveRows
            ],
            JSON_UNESCAPED_UNICODE
        );

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

        $sieveSimulationStmt =
            mysqli_prepare(
                $conn,
                $simulationSql
            );

        if (!$sieveSimulationStmt) {

            throw new Exception(
                "Unable to prepare sieve simulation statement."
            );
        }

        $simulationType =
            "sieve_analysis";

        mysqli_stmt_bind_param(
            $sieveSimulationStmt,
            "iisss",
            $userId,
            $practicalNumber,
            $simulationType,
            $inputData,
            $resultData
        );

        if (
            !mysqli_stmt_execute(
                $sieveSimulationStmt
            )
        ) {

            throw new Exception(
                "Unable to save the sieve analysis simulation."
            );
        }

        mysqli_stmt_close(
            $sieveSimulationStmt
        );

        $sieveSimulationSaved =
            true;
    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    mysqli_commit(
        $conn
    );


    /*
    |--------------------------------------------------------------------------
    | SUCCESS RESPONSE
    |--------------------------------------------------------------------------
    */

    echo json_encode(
        [
            "success" =>
                true,

            "message" =>
                "Practical 2 synchronized successfully.",

            "practical_number" =>
                $practicalNumber,

            "activities_saved" =>
                $activitiesSaved,

            "activities_completed" =>
                $activitiesCompleted,

            "total_activities" =>
                9,

            "practical_status" =>
                $practicalStatus,

            "record_saved" =>
                $recordSaved,

            "centrifugation_saved" =>
                $centrifugationSaved,

            "sieve_measurements_saved" =>
                $sieveMeasurementsSaved,

            "sieve_simulation_saved" =>
                $sieveSimulationSaved
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;


} catch (
    Throwable $exception
) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    mysqli_rollback(
        $conn
    );

    http_response_code(500);

    echo json_encode(
        [
            "success" =>
                false,

            "message" =>
                "Practical 2 synchronization failed.",

            "error" =>
                $exception->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}
?>