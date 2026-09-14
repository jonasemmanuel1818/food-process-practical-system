<?php
session_start();
include("config.php");

$error = "";

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($username) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } else {

        $sql = "SELECT * FROM users WHERE username = '$username' AND role = '$role' LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) === 1) {

            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin' || $user['role'] === 'lecturer') {
                    header("Location: admin/dashboard.php");
                    exit();
                } else {
                    header("Location: dashboard.php");
                    exit();
                }

            } else {
                $error = "Incorrect password.";
            }

        } else {
            $error = "No account found with this username and selected role.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Food Process System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f6f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }

        .login-card {
            width: 420px;
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

        .role-title {
            text-align: center;
            font-weight: bold;
            color: #17495a;
            margin-bottom: 10px;
        }

        .role-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .role-buttons input {
            display: none;
        }

        .role-btn {
            flex: 1;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            font-weight: bold;
            color: #17495a;
            background: #fff;
        }

        .role-btn:hover {
            background: #f0f6f8;
        }

        .role-buttons input:checked + .role-btn {
            background: #1f5f75;
            color: white;
            border-color: #1f5f75;
        }

        .form-label {
            font-weight: 600;
            color: #333;
        }

        .btn-login {
            width: 100%;
            background: #1f5f75;
            border: none;
            padding: 12px;
            font-weight: bold;
            color: white;
            border-radius: 8px;
        }

        .btn-login:hover {
            background: #17495a;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
        }

        .register-link a {
            color: #1f5f75;
            font-weight: bold;
            text-decoration: none;
        }

        .alert {
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="login-card">

    <div class="logo">
        🧪
    </div>

    <h2>Food Process System</h2>

    <p class="subtitle">
        Practical Learning & Simulation System
    </p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="role-title">
            Login As
        </div>

        <div class="role-buttons">

            <input type="radio"
                   name="role"
                   id="student"
                   value="student"
                   checked>

            <label for="student" class="role-btn">
                👨‍🎓 Student
            </label>


            <input type="radio"
                   name="role"
                   id="admin"
                   value="admin">

            <label for="admin" class="role-btn">
                🛡️ Admin
            </label>

        </div>

        <div class="mb-3">

            <label class="form-label">
                Username
            </label>

            <input type="text"
                   name="username"
                   class="form-control"
                   placeholder="Enter username"
                   required>

        </div>


        <div class="mb-3">

            <label class="form-label">
                Password
            </label>

            <input type="password"
                   name="password"
                   class="form-control"
                   placeholder="Enter password"
                   required>

        </div>


        <button type="submit"
                name="login"
                class="btn btn-login">

            Login

        </button>

    </form>


    <div class="register-link">

        Don't have a student account?

        <a href="register.php">
            Register
        </a>

    </div>

</div>

</body>
</html>