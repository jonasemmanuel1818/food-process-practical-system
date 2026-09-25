<?php

require_once "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header(
    "Content-Type: application/json; charset=UTF-8"
);


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "You must be logged in."
    ]);

    exit();
}


$user_id =
    (int) $_SESSION['user_id'];


/* =========================================================
   GET DATA
========================================================= */

$activity_number =
    isset($_POST['activity_number'])
        ? (int) $_POST['activity_number']
        : 0;


$completed =
    isset($_POST['completed'])
        ? (int) $_POST['completed']
        : 0;


/* =========================================================
   VALIDATE
========================================================= */

if (
    $activity_number < 1
    ||
    $activity_number > 8
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid activity number."
    ]);

    exit();
}


if (
    $completed !== 0
    &&
    $completed !== 1
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid completion value."
    ]);

    exit();
}


/* =========================================================
   CHECK IF PRACTICAL PROGRESS EXISTS
========================================================= */

$stmt = $conn->prepare("
    SELECT id
    FROM practical_progress
    WHERE user_id = ?
      AND practical_number = 5
    LIMIT 1
");


if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    $exists =
        $result->fetch_assoc();

    $stmt->close();


    if (!$exists) {

        $insert =
            $conn->prepare("
                INSERT INTO practical_progress
                (
                    user_id,
                    practical_number,
                    status,
                    started_at
                )
                VALUES
                (
                    ?,
                    5,
                    'in_progress',
                    NOW()
                )
            ");


        if ($insert) {

            $insert->bind_param(
                "i",
                $user_id
            );

            $insert->execute();

            $insert->close();
        }

    } else {

        /*
           If the student is working on an activity,
           make sure the practical is marked in progress.
        */

        $update =
            $conn->prepare("
                UPDATE practical_progress
                SET status = 'in_progress'
                WHERE user_id = ?
                  AND practical_number = 5
                  AND status = 'not_started'
            ");


        if ($update) {

            $update->bind_param(
                "i",
                $user_id
            );

            $update->execute();

            $update->close();
        }
    }
}


/* =========================================================
   SAVE ACTIVITY PROGRESS
========================================================= */

$stmt = $conn->prepare("
    INSERT INTO practical_activity_progress
    (
        user_id,
        practical_number,
        activity_number,
        completed,
        updated_at
    )
    VALUES
    (
        ?,
        5,
        ?,
        ?,
        NOW()
    )
    ON DUPLICATE KEY UPDATE
        completed = VALUES(completed),
        updated_at = NOW()
");


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Could not prepare database request."
    ]);

    exit();
}


$stmt->bind_param(
    "iii",
    $user_id,
    $activity_number,
    $completed
);


if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" =>
            "Activity progress saved."
    ]);

} else {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Could not save activity progress."
    ]);
}


$stmt->close();

$conn->close();

?>