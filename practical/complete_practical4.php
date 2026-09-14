<?php

require_once "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "You are not logged in."
    ]);
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/* Check all 12 activities */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS completed_count
    FROM practical_activity_progress
    WHERE user_id = ?
      AND practical_number = 4
      AND activity_number BETWEEN 1 AND 12
      AND completed = 1
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database error."
    ]);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

$completed_count = (int) $row['completed_count'];

$stmt->close();

if ($completed_count < 12) {
    echo json_encode([
        "success" => false,
        "message" => "Please complete all 12 activities first."
    ]);
    exit();
}

/* Get student's report */
$stmt = $conn->prepare("
    SELECT results, observations, conclusion
    FROM practical_submissions
    WHERE user_id = ?
      AND practical_number = 4
    ORDER BY id DESC
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Could not check practical report."
    ]);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$submission = $result->fetch_assoc();

$stmt->close();

$results = $submission['results'] ?? "";
$observations = $submission['observations'] ?? "";
$conclusion = $submission['conclusion'] ?? "";

if (
    trim($results) === "" ||
    trim($observations) === "" ||
    trim($conclusion) === ""
) {
    echo json_encode([
        "success" => false,
        "message" => "Please complete your Results, Observations and Conclusion first."
    ]);
    exit();
}

/* Check practical_progress record */
$stmt = $conn->prepare("
    SELECT id
    FROM practical_progress
    WHERE user_id = ?
      AND practical_number = 4
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$progress = $result->fetch_assoc();

$stmt->close();

if ($progress) {

    $stmt = $conn->prepare("
        UPDATE practical_progress
        SET status = 'completed',
            completed_at = NOW()
        WHERE user_id = ?
          AND practical_number = 4
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

} else {

    $stmt = $conn->prepare("
        INSERT INTO practical_progress
        (
            user_id,
            practical_number,
            status,
            started_at,
            completed_at
        )
        VALUES (?, 4, 'completed', NOW(), NOW())
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

echo json_encode([
    "success" => true,
    "message" => "Practical 4 completed successfully."
]);