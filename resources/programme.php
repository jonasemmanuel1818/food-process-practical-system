<?php

require_once "../config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$message = "";
$error = "";


/*
|--------------------------------------------------------------------------
| ADD PROGRAMME
|--------------------------------------------------------------------------
*/

if (isset($_POST["add_programme"])) {

    $date = $_POST["practical_date"] ?? "";
    $time = trim($_POST["practical_time"] ?? "");
    $content = trim($_POST["content"] ?? "");

    if ($date === "" || $time === "" || $content === "") {

        $error = "Please fill in all fields.";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO practical_programme
            (user_id, practical_date, practical_time, content)
            VALUES (?, ?, ?, ?)"
        );

        if ($stmt) {

            $stmt->bind_param(
                "isss",
                $user_id,
                $date,
                $time,
                $content
            );

            if ($stmt->execute()) {

                $message = "Practical programme added successfully.";

            } else {

                $error = "Unable to save the programme.";

            }

            $stmt->close();

        } else {

            $error = "Database error: " . $conn->error;

        }
    }
}


/*
|--------------------------------------------------------------------------
| UPDATE PROGRAMME
|--------------------------------------------------------------------------
*/

if (isset($_POST["update_programme"])) {

    $id = intval($_POST["id"] ?? 0);

    $date = $_POST["practical_date"] ?? "";
    $time = trim($_POST["practical_time"] ?? "");
    $content = trim($_POST["content"] ?? "");

    if ($id <= 0 || $date === "" || $time === "" || $content === "") {

        $error = "Please provide valid programme information.";

    } else {

        $stmt = $conn->prepare(
            "UPDATE practical_programme
             SET practical_date = ?,
                 practical_time = ?,
                 content = ?
             WHERE id = ?
             AND user_id = ?"
        );

        if ($stmt) {

            $stmt->bind_param(
                "sssii",
                $date,
                $time,
                $content,
                $id,
                $user_id
            );

            if ($stmt->execute()) {

                $message = "Programme updated successfully.";

            } else {

                $error = "Unable to update the programme.";

            }

            $stmt->close();

        } else {

            $error = "Database error: " . $conn->error;

        }
    }
}


/*
|--------------------------------------------------------------------------
| DELETE PROGRAMME
|--------------------------------------------------------------------------
*/

if (isset($_GET["delete"])) {

    $id = intval($_GET["delete"]);

    if ($id > 0) {

        $stmt = $conn->prepare(
            "DELETE FROM practical_programme
             WHERE id = ?
             AND user_id = ?"
        );

        if ($stmt) {

            $stmt->bind_param(
                "ii",
                $id,
                $user_id
            );

            if ($stmt->execute()) {

                $message = "Programme entry deleted.";

            } else {

                $error = "Unable to delete the programme.";

            }

            $stmt->close();

        }
    }
}


/*
|--------------------------------------------------------------------------
| EDIT MODE
|--------------------------------------------------------------------------
*/

$edit_programme = null;

if (isset($_GET["edit"])) {

    $edit_id = intval($_GET["edit"]);

    if ($edit_id > 0) {

        $stmt = $conn->prepare(
            "SELECT id,
                    practical_date,
                    practical_time,
                    content
             FROM practical_programme
             WHERE id = ?
             AND user_id = ?"
        );

        if ($stmt) {

            $stmt->bind_param(
                "ii",
                $edit_id,
                $user_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $edit_programme = $result->fetch_assoc();

            $stmt->close();

        }
    }
}


/*
|--------------------------------------------------------------------------
| GET USER PROGRAMME
|--------------------------------------------------------------------------
*/

$programmes = [];

$stmt = $conn->prepare(
    "SELECT id,
            practical_date,
            practical_time,
            content,
            created_at
     FROM practical_programme
     WHERE user_id = ?
     ORDER BY practical_date ASC, practical_time ASC"
);

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $programmes[] = $row;

    }

    $stmt->close();
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Practical Programme
    </title>

    <link rel="stylesheet"
          href="../assets/style.css">


    <style>

        .programme-form {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            margin-bottom: 30px;
        }


        .programme-form h2 {
            margin-bottom: 20px;
        }


        .form-row {
            display: grid;
            grid-template-columns:
                1fr 1fr;
            gap: 20px;
        }


        .form-group {
            margin-bottom: 18px;
        }


        .form-group label {
            margin-top: 0;
        }


        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }


        .btn-secondary {
            display: inline-block;
            background: #6b7280;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
        }


        .btn-secondary:hover {
            background: #4b5563;
        }


        .btn-edit {
            display: inline-block;
            background: #f59e0b;
            color: white;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 14px;
        }


        .btn-delete {
            display: inline-block;
            background: #ef4444;
            color: white;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 14px;
        }


        .btn-edit:hover {
            background: #d97706;
        }


        .btn-delete:hover {
            background: #dc2626;
        }


        .success-message {
            background: #dcfce7;
            color: #166534;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }


        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }


        .empty-message {
            text-align: center;
            padding: 30px;
            color: #6b7280;
        }


        @media (max-width: 700px) {

            .form-row {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="topbar">

    <div>

        <h2>
            Food Process Engineering
        </h2>

        <span>
            Practical Programme
        </span>

    </div>


    <div class="user-area">

        <a href="../dashboard.php"
           class="logout-btn">

            Dashboard

        </a>

    </div>

</header>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="content">


    <div class="info-section">

        <h1>
            PRACTICAL PROGRAMME
        </h1>

        <p>

            Use this page to organize your practical
            activities. Enter the date, time and content
            of each practical session.

        </p>

    </div>



    <!-- =================================================
         MESSAGES
    ================================================== -->

    <?php if ($message !== ""): ?>

        <div class="success-message">

            <?= htmlspecialchars($message) ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error-message">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>



    <!-- =================================================
         ADD / EDIT FORM
    ================================================== -->

    <div class="programme-form">


        <?php if ($edit_programme): ?>

            <h2>
                Edit Programme
            </h2>

        <?php else: ?>

            <h2>
                Add Practical Programme
            </h2>

        <?php endif; ?>


        <form method="POST">


            <?php if ($edit_programme): ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?= htmlspecialchars(
                        $edit_programme["id"]
                    ) ?>"
                >

            <?php endif; ?>


            <div class="form-row">


                <!-- DATE -->

                <div class="form-group">

                    <label for="practical_date">
                        DATE
                    </label>

                    <input
                        type="date"
                        id="practical_date"
                        name="practical_date"
                        required
                        value="<?= htmlspecialchars(
                            $edit_programme[
                                "practical_date"
                            ] ?? ""
                        ) ?>"
                    >

                </div>



                <!-- TIME -->

                <div class="form-group">

                    <label for="practical_time">
                        TIME
                    </label>

                    <input
                        type="text"
                        id="practical_time"
                        name="practical_time"
                        placeholder="Example: 08:00 - 10:00"
                        required
                        value="<?= htmlspecialchars(
                            $edit_programme[
                                "practical_time"
                            ] ?? ""
                        ) ?>"
                    >

                </div>


            </div>



            <!-- CONTENT -->

            <div class="form-group">

                <label for="content">
                    CONTENT
                </label>

                <textarea
                    id="content"
                    name="content"
                    rows="4"
                    placeholder="Enter the practical content..."
                    required
                ><?= htmlspecialchars(
                    $edit_programme[
                        "content"
                    ] ?? ""
                ) ?></textarea>

            </div>



            <div class="form-actions">


                <?php if ($edit_programme): ?>

                    <button
                        type="submit"
                        name="update_programme"
                        class="btn-primary"
                    >

                        Update Programme

                    </button>


                    <a
                        href="programme.php"
                        class="btn-secondary"
                    >

                        Cancel

                    </a>


                <?php else: ?>

                    <button
                        type="submit"
                        name="add_programme"
                        class="btn-primary"
                    >

                        Save Programme

                    </button>

                <?php endif; ?>


            </div>


        </form>

    </div>



    <!-- =================================================
         SAVED PROGRAMME
    ================================================== -->

    <div class="history">

        <h2>
            My Practical Programme
        </h2>


        <?php if (count($programmes) > 0): ?>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>
                                DATE
                            </th>

                            <th>
                                TIME
                            </th>

                            <th>
                                CONTENT
                            </th>

                            <th>
                                ACTIONS
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $programmes
                        as $programme
                    ): ?>


                        <tr>

                            <td>

                                <?= htmlspecialchars(
                                    $programme[
                                        "practical_date"
                                    ]
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $programme[
                                        "practical_time"
                                    ]
                                ) ?>

                            </td>


                            <td>

                                <?= nl2br(
                                    htmlspecialchars(
                                        $programme[
                                            "content"
                                        ]
                                    )
                                ) ?>

                            </td>


                            <td>

                                <a
                                    href="?edit=<?= $programme["id"] ?>"
                                    class="btn-edit"
                                >

                                    Edit

                                </a>


                                <a
                                    href="?delete=<?= $programme["id"] ?>"
                                    class="btn-delete"
                                    onclick="return confirm(
                                        'Are you sure you want to delete this programme entry?'
                                    );"
                                >

                                    Delete

                                </a>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty-message">

                <p>
                    You have not added any practical
                    programme entries yet.
                </p>

            </div>


        <?php endif; ?>


    </div>



    <!-- =================================================
         BACK BUTTON
    ================================================== -->

    <div style="margin-top: 25px;">

        <a
            href="../dashboard.php"
            class="btn-secondary"
        >

            ← Back to Dashboard

        </a>

    </div>


</main>


</body>

</html>
