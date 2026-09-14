<?php

require_once "../config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$user_role = $_SESSION["role"] ?? "student";

$message = "";
$error = "";


/*
|--------------------------------------------------------------------------
| UPDATE EMERGENCY CONTACT
|--------------------------------------------------------------------------
|
| Only lecturer and admin accounts can update numbers.
|
*/

if (isset($_POST["update_contact"])) {

    if ($user_role !== "admin" && $user_role !== "lecturer") {

        $error = "You do not have permission to update emergency numbers.";

    } else {

        $id = intval($_POST["id"] ?? 0);

        $telephone_numbers =
            trim($_POST["telephone_numbers"] ?? "");

        if ($id <= 0) {

            $error = "Invalid contact selected.";

        } else {

            $stmt = $conn->prepare(
                "UPDATE emergency_contacts
                 SET telephone_numbers = ?
                 WHERE id = ?"
            );

            if ($stmt) {

                $stmt->bind_param(
                    "si",
                    $telephone_numbers,
                    $id
                );

                if ($stmt->execute()) {

                    $message =
                        "Emergency telephone number updated successfully.";

                } else {

                    $error =
                        "Unable to update the emergency number.";

                }

                $stmt->close();

            } else {

                $error =
                    "Database error: " . $conn->error;

            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET EMERGENCY CONTACTS
|--------------------------------------------------------------------------
*/

$contacts = [];

$result = $conn->query(
    "SELECT id,
            institution,
            telephone_numbers
     FROM emergency_contacts
     ORDER BY id ASC"
);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $contacts[] = $row;

    }

} else {

    $error =
        "Unable to load emergency contacts.";

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Emergency Telephone Numbers
    </title>

    <link rel="stylesheet"
          href="../assets/style.css">


    <style>

        .emergency-header {

            background: #fff7ed;

            border-left: 5px solid #f97316;

            padding: 20px;

            border-radius: 10px;

            margin-bottom: 25px;

        }


        .emergency-header h2 {

            margin-top: 0;

            color: #9a3412;

        }


        .emergency-table {

            width: 100%;

            border-collapse: collapse;

            background: white;

            border-radius: 12px;

            overflow: hidden;

            box-shadow:
                0 5px 20px rgba(0,0,0,0.06);

        }


        .emergency-table th {

            background: #f3f4f6;

            padding: 15px;

            text-align: left;

            font-size: 14px;

        }


        .emergency-table td {

            padding: 15px;

            border-top: 1px solid #e5e7eb;

            vertical-align: middle;

        }


        .emergency-table tr:hover {

            background: #fafafa;

        }


        .phone-number {

            font-weight: 600;

            color: #1f2937;

        }


        .not-available {

            color: #9ca3af;

            font-style: italic;

        }


        .contact-form {

            display: flex;

            gap: 10px;

            align-items: center;

        }


        .contact-form input {

            width: 100%;

            min-width: 180px;

        }


        .update-btn {

            border: none;

            cursor: pointer;

            background: #2563eb;

            color: white;

            padding: 9px 14px;

            border-radius: 7px;

            font-weight: 600;

        }


        .update-btn:hover {

            background: #1d4ed8;

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


        .admin-note {

            background: #eff6ff;

            color: #1e40af;

            padding: 14px;

            border-radius: 8px;

            margin-top: 20px;

        }


        .back-button {

            display: inline-block;

            margin-top: 25px;

            background: #6b7280;

            color: white;

            padding: 12px 20px;

            border-radius: 8px;

            text-decoration: none;

            font-weight: 600;

        }


        .back-button:hover {

            background: #4b5563;

        }


        @media (max-width: 700px) {

            .emergency-table {

                font-size: 14px;

            }


            .emergency-table th,
            .emergency-table td {

                padding: 10px;

            }


            .contact-form {

                flex-direction: column;

                align-items: stretch;

            }


            .contact-form input {

                min-width: 0;

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
            Emergency Information
        </span>

    </div>


    <div class="user-area">

        <a
            href="../dashboard.php"
            class="logout-btn"
        >

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
            EMERGENCY TELEPHONE NUMBERS
        </h1>

        <p>

            Important emergency and laboratory contact
            information for students and other laboratory
            users.

        </p>

    </div>



    <!-- =================================================
         WARNING / INFORMATION
    ================================================== -->

    <div class="emergency-header">

        <h2>
            Emergency Information
        </h2>

        <p>

            In an emergency, remain calm and contact the
            appropriate responsible person or emergency
            service using the numbers provided below.

        </p>

        <p>

            Familiarize yourself with the location of the
            nearest emergency telephone, emergency exit,
            fire equipment and first aid facilities.

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
         CONTACT TABLE
    ================================================== -->

    <?php if (count($contacts) > 0): ?>


        <div class="table-container">

            <table class="emergency-table">

                <thead>

                    <tr>

                        <th>
                            Institution
                        </th>

                        <th>
                            Telephone Numbers
                        </th>

                        <?php if (
                            $user_role === "admin"
                            ||
                            $user_role === "lecturer"
                        ): ?>

                            <th>
                                Action
                            </th>

                        <?php endif; ?>

                    </tr>

                </thead>


                <tbody>


                <?php foreach (
                    $contacts as $contact
                ): ?>


                    <tr>


                        <!-- INSTITUTION -->

                        <td>

                            <strong>

                                <?= htmlspecialchars(
                                    $contact[
                                        "institution"
                                    ]
                                ) ?>

                            </strong>

                        </td>



                        <!-- TELEPHONE NUMBER -->

                        <td>

                            <?php

                            $number =
                                trim(
                                    $contact[
                                        "telephone_numbers"
                                    ]
                                );

                            ?>

                            <?php if ($number !== ""): ?>

                                <span class="phone-number">

                                    <?= nl2br(
                                        htmlspecialchars(
                                            $number
                                        )
                                    ) ?>

                                </span>

                            <?php else: ?>

                                <span
                                    class="not-available"
                                >

                                    Not yet provided

                                </span>

                            <?php endif; ?>

                        </td>



                        <!-- ADMIN / LECTURER ACTION -->

                        <?php if (
                            $user_role === "admin"
                            ||
                            $user_role === "lecturer"
                        ): ?>


                            <td>

                                <form
                                    method="POST"
                                    class="contact-form"
                                >


                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $contact["id"] ?>"
                                    >


                                    <input
                                        type="text"
                                        name="telephone_numbers"
                                        placeholder="Enter telephone number"
                                        value="<?= htmlspecialchars(
                                            $contact[
                                                "telephone_numbers"
                                            ]
                                        ) ?>"
                                    >


                                    <button
                                        type="submit"
                                        name="update_contact"
                                        class="update-btn"
                                    >

                                        Update

                                    </button>


                                </form>

                            </td>


                        <?php endif; ?>


                    </tr>


                <?php endforeach; ?>


                </tbody>

            </table>

        </div>


    <?php else: ?>


        <div class="info-section">

            <p>

                No emergency contact information has
                been added yet.

            </p>

        </div>


    <?php endif; ?>



    <!-- =================================================
         ADMIN NOTE
    ================================================== -->

    <?php if (
        $user_role === "admin"
        ||
        $user_role === "lecturer"
    ): ?>

        <div class="admin-note">

            <strong>
                Administrator / Lecturer:
            </strong>

            You can update the telephone numbers
            directly from this page.

        </div>

    <?php else: ?>

        <div class="admin-note">

            <strong>
                Student:
            </strong>

            This page is available as a reference.
            Emergency contact numbers can only be
            maintained by authorized users.

        </div>

    <?php endif; ?>



    <!-- =================================================
         BACK TO DASHBOARD
    ================================================== -->

    <a
        href="../dashboard.php"
        class="back-button"
    >

        ← Back to Dashboard

    </a>


</main>


</body>

</html>
