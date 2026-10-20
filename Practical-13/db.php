<?php
// ==============================================================================
// Database Configuration & Connection (XAMPP MySQL)
// Practical-10: StudentHub Portal Database
// ==============================================================================

$host     = "localhost";
$username = "root";
$password = "";
$database = "studenthub_db";

// 1. Initial connection
$conn = mysqli_connect($host, $username, $password);

if (!$conn) {
    die("<div style='color:red; font-family:Arial; padding:20px; text-align:center;'>
            <h2>Database Connection Failed!</h2>
            <p>Error: " . mysqli_connect_error() . "</p>
            <p>Please make sure MySQL is started in XAMPP Control Panel.</p>
         </div>");
}

// 2. Automatically create database if not exists
$createDbQuery = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
mysqli_query($conn, $createDbQuery);

// 3. Select database
mysqli_select_db($conn, $database);
mysqli_set_charset($conn, "utf8mb4");

// 4. Ensure students table exists
$createTableQuery = "CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `mobile` VARCHAR(15) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `dob` DATE NULL,
    `gender` VARCHAR(10) NULL,
    `course` VARCHAR(50) NULL,
    `skills` VARCHAR(150) NULL,
    `address` TEXT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'student',
    `remember_token` VARCHAR(255) NULL,
    `reg_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createTableQuery);

// 5. Add 'role' column if not present
$checkRole = mysqli_query($conn, "SHOW COLUMNS FROM `students` LIKE 'role'");
if (mysqli_num_rows($checkRole) == 0) {
    mysqli_query($conn, "ALTER TABLE `students` ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'student' AFTER `address`");
}

// 6. Add 'remember_token' column if not present
$checkToken = mysqli_query($conn, "SHOW COLUMNS FROM `students` LIKE 'remember_token'");
if (mysqli_num_rows($checkToken) == 0) {
    mysqli_query($conn, "ALTER TABLE `students` ADD COLUMN `remember_token` VARCHAR(255) NULL AFTER `role`");
}

// 7. Seed Demo Accounts if not already existing
$seedAccounts = [
    [
        'name'     => 'System Admin',
        'email'    => 'admin@studenthub.com',
        'mobile'   => '9876543210',
        'password' => 'thor%123',
        'course'   => 'System Administration',
        'role'     => 'admin'
    ],
    [
        'name'     => 'Yaksh Butani',
        'email'    => 'student@studenthub.com',
        'mobile'   => '9429800202',
        'password' => 'iron%123',
        'course'   => 'B.Tech Computer Science',
        'role'     => 'student'
    ]
];

foreach ($seedAccounts as $acc) {
    $email = $acc['email'];
    $check = mysqli_query($conn, "SELECT id FROM students WHERE email = '$email'");
    if (mysqli_num_rows($check) == 0) {
        $hashed = password_hash($acc['password'], PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO students (name, email, mobile, password, course, role) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssss", $acc['name'], $acc['email'], $acc['mobile'], $hashed, $acc['course'], $acc['role']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// 8. Ensure 'events' table exists
$createEventsTable = "CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `day` VARCHAR(10) NOT NULL,
    `month` VARCHAR(10) NOT NULL,
    `location` VARCHAR(150) NOT NULL,
    `time` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `button_text` VARCHAR(50) DEFAULT 'Register',
    `button_link` VARCHAR(255) DEFAULT '#',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createEventsTable);

// 9. Seed Original StudentHub Events if events table is empty
$checkEvents = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `events`");
$eventCount = mysqli_fetch_assoc($checkEvents)['cnt'] ?? 0;

if ($eventCount == 0) {
    $initialEvents = [
        [
            'title'       => 'Hackathon 2026',
            'day'         => '10',
            'month'       => 'AUG',
            'location'    => 'Computer Department',
            'time'        => '10:00 AM',
            'description' => '24-Hour Coding Competition with exciting prizes.',
            'button_text' => 'Register',
            'button_link' => '#'
        ],
        [
            'title'       => 'Independence Day Celebration',
            'day'         => '15',
            'month'       => 'AUG',
            'location'    => 'Main Ground',
            'time'        => '8:00 AM',
            'description' => 'Flag Hoisting, Cultural Program and Speech.',
            'button_text' => 'View Details',
            'button_link' => '#'
        ],
        [
            'title'       => 'Web Development Workshop',
            'day'         => '20',
            'month'       => 'AUG',
            'location'    => 'Seminar Hall',
            'time'        => '11:00 AM',
            'description' => 'Learn HTML, CSS and JavaScript from industry experts.',
            'button_text' => 'Register',
            'button_link' => '#'
        ],
        [
            'title'       => 'Sports Tournament',
            'day'         => '25',
            'month'       => 'AUG',
            'location'    => 'Sports Complex',
            'time'        => '9:00 AM',
            'description' => 'Cricket, Football, Volleyball and Indoor Games.',
            'button_text' => 'Participate',
            'button_link' => '#'
        ],
        [
            'title'       => 'Placement Training',
            'day'         => '30',
            'month'       => 'AUG',
            'location'    => 'Auditorium',
            'time'        => '2:00 PM',
            'description' => 'Aptitude, Resume Building and Interview Preparation.',
            'button_text' => 'Join Now',
            'button_link' => '#'
        ],
        [
            'title'       => "Teacher's Day Celebration",
            'day'         => '05',
            'month'       => 'SEP',
            'location'    => 'Auditorium',
            'time'        => '10:30 AM',
            'description' => 'Cultural performances and award ceremony.',
            'button_text' => 'View Details',
            'button_link' => '#'
        ]
    ];

    foreach ($initialEvents as $ev) {
        $ins = mysqli_prepare($conn, "INSERT INTO `events` (title, day, month, location, time, description, button_text, button_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($ins, "ssssssss", $ev['title'], $ev['day'], $ev['month'], $ev['location'], $ev['time'], $ev['description'], $ev['button_text'], $ev['button_link']);
        mysqli_stmt_execute($ins);
        mysqli_stmt_close($ins);
    }
}
?>
