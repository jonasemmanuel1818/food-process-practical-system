<?php

// =========================================================
// FOOD PROCESS SYSTEM - DATABASE CONFIGURATION
// =========================================================

// Start session only if one is not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database settings
$host = "localhost";
$username = "root";
$password = "";
$database = "food_process_system";

// Create database connection
$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set character encoding
mysqli_set_charset($conn, "utf8mb4");

?>