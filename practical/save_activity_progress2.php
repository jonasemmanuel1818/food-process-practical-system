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

if ($activity_number < 1 || $activity_number > 5) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid activity number."
    ]);
    exit();
}

$completed = ($completed === 1) ? 1 : 0;

$stmt = $conn->prepare("
    INSERT INTO practical_activity_progress
    (user_id, practical_number, activity_number, completed)
    VALUES (?, 2, ?, ?)
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
        "message" => "Activity progress saved."
    ]);

} else {

    echo json_encode([
        "success" => false,
        "message" => "Could not save activity progress."
    ]);
}

$stmt->close();
?>