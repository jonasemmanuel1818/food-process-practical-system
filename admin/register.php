<?php
session_start();
include("../config.php");

$error = "";
$success = "";

if (isset($_POST['register'])) {

    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check empty fields
    if (empty($full_name) || empty($username) || empty($password) || empty($confirm_password)) {

        $error = "Please fill in all fields.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } else {

        // Check if username already exists
        $check_sql = "SELECT id FROM users WHERE username = '$username' LIMIT 1";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {

            $error = "Username already exists. Please choose another username.";

        } else {

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Create admin account
            $sql = "INSERT INTO users 
                    (full_name, username, password, role)
                    VALUES 
                    ('$full_name', '$username', '$hashed_password', 'admin')";

            if (mysqli_query($conn, $sql)) {

                $success = "Admin account created successfully.";

                // Clear form values
                $full_name = "";
                $username = "";

            } else {

                $error = "Registration failed: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Registration - Food Process System</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f4f6f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }

        .register-card {
            width: 450px;
            max-width: 95%;
            background: white;
            border: 1px solid #d9dee3;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .logo {
            width: 65px;
            height: 65px;
            background: #1f5f75;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
        }

        h2 {
            color: #17495a;
            text-align: center;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
        }

        .admin-badge {
            background: #e8f2f5;
            color: #17495a;
            border: 1px solid #c8dfe6;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
        }

        .form-control {
            padding: 11px;
            border-radius: 8px;
        }

        .btn-register {
            width: 100%;
            background: #1f5f75;
            border: none;
            padding: 12px;
            font-weight: bold;
            color: white;
            border-radius: 8px;
        }

        .btn-register:hover {
            background: #17495a;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #1f5f75;
            font-weight: bold;
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="register-card">

    <div class="logo">
        🛡️
    </div>

    <h2>Admin Registration</h2>

    <p class="subtitle">
        Food Process Learning & Simulation System
    </p>

    <div class="admin-badge">
        🛡️ ADMIN ACCOUNT
    </div>

    <?php if (!empty($error)): ?>

        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <?php if (!empty($success)): ?>

        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <div class="mb-3">

            <label class="form-label">
                Full Name
            </label>

            <input
                type="text"
                name="full_name"
                class="form-control"
                placeholder="Enter admin full name"
                value="<?= isset($full_name) ? htmlspecialchars($full_name) : '' ?>"
                required
            >

        </div>


        <div class="mb-3">

            <label class="form-label">
                Username
            </label>

            <input
                type="text"
                name="username"
                class="form-control"
                placeholder="Enter admin username"
                value="<?= isset($username) ? htmlspecialchars($username) : '' ?>"
                required
            >

        </div>


        <div class="mb-3">

            <label class="form-label">
                Password
            </label>

            <input
                type="password"
                name="password"
                class="form-control"
                placeholder="Enter password"
                required
            >

        </div>


        <div class="mb-3">

            <label class="form-label">
                Confirm Password
            </label>

            <input
                type="password"
                name="confirm_password"
                class="form-control"
                placeholder="Confirm password"
                required
            >

        </div>


        <button
            type="submit"
            name="register"
            class="btn btn-register"
        >
            Create Admin Account
        </button>

    </form>


    <div class="login-link">

        Already have an account?

        <a href="../login.php">
            Login
        </a>

    </div>

</div>

</body>

</html>