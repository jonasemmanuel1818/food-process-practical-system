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

$activity_number = isset($_POST['activity_number'])
    ? (int) $_POST['activity_number']
    : 0;

$completed = isset($_POST['completed'])
    ? (int) $_POST['completed']
    : 0;

/*
|--------------------------------------------------------------------------
| Practical 3 has 9 activities
|--------------------------------------------------------------------------
*/

if ($activity_number < 1 || $activity_number > 9) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid Practical 3 activity number."
    ]);
    exit();
}

$completed = ($completed === 1) ? 1 : 0;

$stmt = $conn->prepare("
    INSERT INTO practical_activity_progress
    (user_id, practical_number, activity_number, completed)
    VALUES (?, 3, ?, ?)
    ON DUPLICATE KEY UPDATE
        completed = VALUES(completed)
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database statement could not be prepared."
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
        "message" => "Practical 3 activity progress saved."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Could not save Practical 3 activity progress."
    ]);
}

$stmt->close();
?>