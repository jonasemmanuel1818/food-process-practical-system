-- =========================================================
-- FOOD PROCESS ENGINEERING PRACTICAL LEARNING SYSTEM
-- COMPLETE DATABASE SETUP
-- =========================================================

CREATE DATABASE IF NOT EXISTS food_process_system;

USE food_process_system;


-- =========================================================
-- 1. USERS
-- =========================================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'lecturer', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================
-- 2. EXPERIMENTS
-- =========================================================

CREATE TABLE IF NOT EXISTS experiments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    practical_number INT NOT NULL,
    experiment_name VARCHAR(150) NOT NULL,
    result TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================
-- 3. PRACTICAL PROGRAMME
-- =========================================================

CREATE TABLE IF NOT EXISTS practical_programme (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================
-- 4. EMERGENCY CONTACTS
-- =========================================================

CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution VARCHAR(150) NOT NULL,
    telephone_numbers VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================
-- 5. PRACTICAL PROGRESS
-- =========================================================
-- Tracks overall progress for Practical 1–4.
-- =========================================================

CREATE TABLE IF NOT EXISTS practical_progress (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================
-- 6. PRACTICAL ACTIVITY PROGRESS
-- =========================================================
-- Practical 2 = 5 activities
-- Practical 3 = 9 activities
-- Practical 4 = 12 activities
-- =========================================================

CREATE TABLE IF NOT EXISTS practical_activity_progress (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================
-- 7. PRACTICAL SUBMISSIONS
-- =========================================================
-- Stores results, observations and conclusions.
-- One submission per student per practical.
-- =========================================================

CREATE TABLE IF NOT EXISTS practical_submissions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================
-- 8. SIMULATION RESULTS
-- =========================================================
-- Stores simulation inputs and calculated results.
-- =========================================================

CREATE TABLE IF NOT EXISTS simulation_results (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================
-- 9. PRACTICAL MEASUREMENTS
-- =========================================================
-- Stores individual measurements used by practicals.
-- =========================================================

CREATE TABLE IF NOT EXISTS practical_measurements (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =========================================================
-- 10. DEFAULT EMERGENCY CONTACT CATEGORIES
-- =========================================================

INSERT INTO emergency_contacts
    (institution, telephone_numbers)
SELECT
    'University Security Office', ''
WHERE NOT EXISTS (
    SELECT 1 FROM emergency_contacts
    WHERE institution = 'University Security Office'
);

INSERT INTO emergency_contacts
    (institution, telephone_numbers)
SELECT
    'Police (Emergency)', ''
WHERE NOT EXISTS (
    SELECT 1 FROM emergency_contacts
    WHERE institution = 'Police (Emergency)'
);

INSERT INTO emergency_contacts
    (institution, telephone_numbers)
SELECT
    'Police Charge Office / Enquiries', ''
WHERE NOT EXISTS (
    SELECT 1 FROM emergency_contacts
    WHERE institution = 'Police Charge Office / Enquiries'
);

INSERT INTO emergency_contacts
    (institution, telephone_numbers)
SELECT
    'Fire Department', ''
WHERE NOT EXISTS (
    SELECT 1 FROM emergency_contacts
    WHERE institution = 'Fire Department'
);

INSERT INTO emergency_contacts
    (institution, telephone_numbers)
SELECT
    'Ambulance', ''
WHERE NOT EXISTS (
    SELECT 1 FROM emergency_contacts
    WHERE institution = 'Ambulance'
);

INSERT INTO emergency_contacts
    (institution, telephone_numbers)
SELECT
    'Student Health Services', ''
WHERE NOT EXISTS (
    SELECT 1 FROM emergency_contacts
    WHERE institution = 'Student Health Services'
);

INSERT INTO emergency_contacts
    (institution, telephone_numbers)
SELECT
    'Hospital', ''
WHERE NOT EXISTS (
    SELECT 1 FROM emergency_contacts
    WHERE institution = 'Hospital'
);

INSERT INTO emergency_contacts
    (institution, telephone_numbers)
SELECT
    'Useful Numbers', ''
WHERE NOT EXISTS (
    SELECT 1 FROM emergency_contacts
    WHERE institution = 'Useful Numbers'
);

INSERT INTO emergency_contacts
    (institution, telephone_numbers)
SELECT
    'Departmental Risk Committee', ''
WHERE NOT EXISTS (
    SELECT 1 FROM emergency_contacts
    WHERE institution = 'Departmental Risk Committee'
);

INSERT INTO emergency_contacts
    (institution, telephone_numbers)
SELECT
    'Trained First Aid Staff', ''
WHERE NOT EXISTS (
    SELECT 1 FROM emergency_contacts
    WHERE institution = 'Trained First Aid Staff'
);


-- =========================================================
-- END OF DATABASE SETUP
-- =========================================================