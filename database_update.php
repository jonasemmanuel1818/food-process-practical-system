<?php

require_once "config.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    // =========================================================
    // DATABASE
    // =========================================================

    mysqli_select_db($conn, "food_process_system");


    // =========================================================
    // 1. PRACTICAL PROGRAMME
    // =========================================================

    $sql = "CREATE TABLE IF NOT EXISTS practical_programme (

        id INT AUTO_INCREMENT PRIMARY KEY,

        user_id INT NOT NULL,

        practical_date DATE NOT NULL,

        practical_time VARCHAR(50) NOT NULL,

        content TEXT NOT NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE CASCADE

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($conn, $sql);


    // =========================================================
    // 2. EMERGENCY CONTACTS
    // =========================================================

    $sql = "CREATE TABLE IF NOT EXISTS emergency_contacts (

        id INT AUTO_INCREMENT PRIMARY KEY,

        institution VARCHAR(150) NOT NULL,

        telephone_numbers VARCHAR(255) NOT NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($conn, $sql);


    // =========================================================
    // 3. PRACTICAL PROGRESS
    // =========================================================

    $sql = "CREATE TABLE IF NOT EXISTS practical_progress (

        id INT AUTO_INCREMENT PRIMARY KEY,

        user_id INT NOT NULL,

        practical_number INT NOT NULL,

        status ENUM(
            'not_started',
            'in_progress',
            'completed'
        ) DEFAULT 'not_started',

        started_at TIMESTAMP NULL DEFAULT NULL,

        completed_at TIMESTAMP NULL DEFAULT NULL,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        UNIQUE KEY unique_user_practical
            (user_id, practical_number),

        FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE CASCADE

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($conn, $sql);


    // =========================================================
    // 4. PRACTICAL ACTIVITY PROGRESS
    // =========================================================

    $sql = "CREATE TABLE IF NOT EXISTS practical_activity_progress (

        id INT AUTO_INCREMENT PRIMARY KEY,

        user_id INT NOT NULL,

        practical_number INT NOT NULL,

        activity_number INT NOT NULL,

        completed TINYINT(1) NOT NULL DEFAULT 0,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        UNIQUE KEY unique_activity
            (user_id, practical_number, activity_number),

        FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE CASCADE

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($conn, $sql);


    // =========================================================
    // 5. PRACTICAL SUBMISSIONS
    // =========================================================

    $sql = "CREATE TABLE IF NOT EXISTS practical_submissions (

        id INT AUTO_INCREMENT PRIMARY KEY,

        user_id INT NOT NULL,

        practical_number INT NOT NULL,

        results TEXT NULL,

        observations TEXT NULL,

        conclusion TEXT NULL,

        submitted_at DATETIME NULL,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        UNIQUE KEY unique_student_practical
            (user_id, practical_number),

        FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE CASCADE

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($conn, $sql);


    // =========================================================
    // 6. SIMULATION RESULTS
    // =========================================================

    $sql = "CREATE TABLE IF NOT EXISTS simulation_results (

        id INT AUTO_INCREMENT PRIMARY KEY,

        user_id INT NOT NULL,

        practical_number INT NOT NULL,

        simulation_type VARCHAR(100) NOT NULL,

        input_data LONGTEXT NULL,

        result_data LONGTEXT NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE CASCADE

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($conn, $sql);


    // =========================================================
    // 7. PRACTICAL MEASUREMENTS
    // =========================================================

    $sql = "CREATE TABLE IF NOT EXISTS practical_measurements (

        id INT AUTO_INCREMENT PRIMARY KEY,

        user_id INT NOT NULL,

        practical_number INT NOT NULL,

        measurement_name VARCHAR(150) NOT NULL,

        measurement_value DECIMAL(12,4) NULL,

        unit VARCHAR(50) NULL,

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

        UNIQUE KEY unique_measurement
            (user_id, practical_number, measurement_name),

        FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE CASCADE

    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($conn, $sql);


    // =========================================================
    // 8. DEFAULT EMERGENCY CONTACT CATEGORIES
    // =========================================================

    $contacts = [

        "University Security Office",
        "Police (Emergency)",
        "Police Charge Office / Enquiries",
        "Fire Department",
        "Ambulance",
        "Student Health Services",
        "Hospital",
        "Useful Numbers",
        "Departmental Risk Committee",
        "Trained First Aid Staff"

    ];


    foreach ($contacts as $institution) {

        $check = $conn->prepare(
            "SELECT id
             FROM emergency_contacts
             WHERE institution = ?
             LIMIT 1"
        );

        $check->bind_param("s", $institution);

        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows === 0) {

            $insert = $conn->prepare(
                "INSERT INTO emergency_contacts
                (institution, telephone_numbers)
                VALUES (?, '')"
            );

            $insert->bind_param("s", $institution);

            $insert->execute();

            $insert->close();
        }

        $check->close();
    }


    // =========================================================
    // 9. CREATE PRACTICAL PROGRESS FOR EXISTING STUDENTS
    // =========================================================

    $students = mysqli_query(
        $conn,
        "SELECT id
         FROM users
         WHERE role = 'student'"
    );


    while ($student = mysqli_fetch_assoc($students)) {

        $user_id = (int) $student['id'];

        for ($practical = 1; $practical <= 4; $practical++) {

            $check = $conn->prepare(
                "SELECT id
                 FROM practical_progress
                 WHERE user_id = ?
                 AND practical_number = ?
                 LIMIT 1"
            );

            $check->bind_param(
                "ii",
                $user_id,
                $practical
            );

            $check->execute();

            $result = $check->get_result();

            if ($result->num_rows === 0) {

                $insert = $conn->prepare(
                    "INSERT INTO practical_progress
                    (user_id, practical_number, status)
                    VALUES (?, ?, 'not_started')"
                );

                $insert->bind_param(
                    "ii",
                    $user_id,
                    $practical
                );

                $insert->execute();

                $insert->close();
            }

            $check->close();
        }
    }


    // =========================================================
    // SUCCESS
    // =========================================================

    echo "<!DOCTYPE html>";

    echo "<html>";
    echo "<head>";
    echo "<title>Database Update</title>";

    echo "<style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 40px;
        }

        .box {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            border: 1px solid #d9dee3;
        }

        h2 {
            color: #1f5f75;
        }

        li {
            margin: 8px 0;
        }

        .success {
            color: #198754;
            font-weight: bold;
        }
    </style>";

    echo "</head>";

    echo "<body>";

    echo "<div class='box'>";

    echo "<h2>Database Update Completed Successfully</h2>";

    echo "<p class='success'>
            The Food Process System database is ready.
          </p>";

    echo "<p>The following tables are available:</p>";

    echo "<ul>";

    echo "<li>users</li>";
    echo "<li>experiments</li>";
    echo "<li>practical_programme</li>";
    echo "<li>emergency_contacts</li>";
    echo "<li>practical_progress</li>";
    echo "<li>practical_activity_progress</li>";
    echo "<li>practical_submissions</li>";
    echo "<li>simulation_results</li>";
    echo "<li>practical_measurements</li>";

    echo "</ul>";

    echo "<p>
        Practical 1, 2, 3 and 4 progress tracking,
        activities, submissions and simulations are ready.
    </p>";

    echo "</div>";

    echo "</body>";

    echo "</html>";


} catch (Exception $e) {

    echo "<h2>Database Update Failed</h2>";

    echo "<p>Error: " .
        htmlspecialchars($e->getMessage()) .
        "</p>";
}

?>