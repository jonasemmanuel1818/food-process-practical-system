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

/*
|--------------------------------------------------------------------------
| Practical 1 - Laboratory Orientation
|--------------------------------------------------------------------------
| Practical 1 has no individual activities.
| This endpoint simply confirms that the orientation was completed.
*/

$stmt = $conn->prepare("
    SELECT id
    FROM practical_progress
    WHERE user_id = ?
      AND practical_number = 1
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database statement could not be prepared."
    ]);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$exists = $result->num_rows > 0;

$stmt->close();

if ($exists) {

    $stmt = $conn->prepare("
        UPDATE practical_progress
        SET status = 'completed',
            completed_at = NOW()
        WHERE user_id = ?
          AND practical_number = 1
    ");

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Could not prepare update statement."
        ]);
        exit();
    }

    $stmt->bind_param("i", $user_id);

} else {

    $stmt = $conn->prepare("
        INSERT INTO practical_progress
        (user_id, practical_number, status, started_at, completed_at)
        VALUES (?, 1, 'completed', NOW(), NOW())
    ");

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Could not prepare insert statement."
        ]);
        exit();
    }

    $stmt->bind_param("i", $user_id);
}

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "message" => "Laboratory orientation completed successfully."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Could not save orientation progress."
    ]);
}

$stmt->close();
?>