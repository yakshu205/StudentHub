<?php
// ==============================================================================
// Practical 14: Database Configuration & Auto-Schema Initialization
// StudentHub Portal: Admin Dashboard with RBAC, Dynamic Menu & Audit Log
// Manages: users, students, events, registrations, audit_logs tables
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

// 4. Ensure `students` table exists
$createStudentsTable = "CREATE TABLE IF NOT EXISTS `students` (
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
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `remember_token` VARCHAR(255) NULL,
    `reg_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createStudentsTable);

// Add missing columns to students if needed
$checkRole = mysqli_query($conn, "SHOW COLUMNS FROM `students` LIKE 'role'");
if (mysqli_num_rows($checkRole) == 0) {
    mysqli_query($conn, "ALTER TABLE `students` ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT 'student' AFTER `address`");
}

$checkStatus = mysqli_query($conn, "SHOW COLUMNS FROM `students` LIKE 'status'");
if (mysqli_num_rows($checkStatus) == 0) {
    mysqli_query($conn, "ALTER TABLE `students` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `role`");
}

$checkToken = mysqli_query($conn, "SHOW COLUMNS FROM `students` LIKE 'remember_token'");
if (mysqli_num_rows($checkToken) == 0) {
    mysqli_query($conn, "ALTER TABLE `students` ADD COLUMN `remember_token` VARCHAR(255) NULL AFTER `status`");
}

// 5. Ensure `users` table exists (RBAC authentication dataset)
$createUsersTable = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'student',
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createUsersTable);

// 6. Ensure `events` table exists
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

// 7. Ensure `registrations` table exists (Event & student registrations)
$createRegistrationsTable = "CREATE TABLE IF NOT EXISTS `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `student_name` VARCHAR(100) NOT NULL,
    `student_email` VARCHAR(100) NOT NULL,
    `event_title` VARCHAR(150) NOT NULL,
    `registration_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` VARCHAR(20) NOT NULL DEFAULT 'Confirmed',
    `attendance` VARCHAR(20) DEFAULT 'Present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createRegistrationsTable);

// 8. Ensure `audit_logs` table exists (Traceability & administrative audit logging)
$createAuditLogsTable = "CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `user_name` VARCHAR(100) NOT NULL,
    `user_role` VARCHAR(20) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `details` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($conn, $createAuditLogsTable);

// 9. Audit Logging Function
if (!function_exists('log_audit')) {
    function log_audit($conn, $action, $module, $details, $userId = null, $userName = null, $userRole = null) {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
        }
        if ($userName === null && isset($_SESSION['user_name'])) {
            $userName = $_SESSION['user_name'];
        }
        if ($userRole === null && isset($_SESSION['user_role'])) {
            $userRole = $_SESSION['user_role'];
        }
        if (empty($userName)) {
            $userName = 'System / Guest';
        }
        if (empty($userRole)) {
            $userRole = 'guest';
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if ($ip === '::1') {
            $ip = '127.0.0.1';
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO `audit_logs` (`user_id`, `user_name`, `user_role`, `action`, `module`, `details`, `ip_address`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issssss", $userId, $userName, $userRole, $action, $module, $details, $ip);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

// 10. Seed Accounts in `students` and `users`
$seedAccounts = [
    [
        'name'     => 'System Admin',
        'email'    => 'admin@studenthub.com',
        'mobile'   => '9876543210',
        'password' => 'thor%123',
        'course'   => 'System Administration',
        'role'     => 'admin',
        'status'   => 'active'
    ],
    [
        'name'     => 'Yaksh Butani',
        'email'    => 'student@studenthub.com',
        'mobile'   => '9429800202',
        'password' => 'iron%123',
        'course'   => 'B.Tech Computer Science',
        'role'     => 'student',
        'status'   => 'active'
    ],
    [
        'name'     => 'Rohit Sharma',
        'email'    => 'rohit@studenthub.com',
        'mobile'   => '9822334455',
        'password' => 'rohit%123',
        'course'   => 'Information Technology',
        'role'     => 'student',
        'status'   => 'active'
    ],
    [
        'name'     => 'Priya Patel',
        'email'    => 'priya@studenthub.com',
        'mobile'   => '9811223344',
        'password' => 'priya%123',
        'course'   => 'Computer Science Engineering',
        'role'     => 'student',
        'status'   => 'active'
    ],
    [
        'name'     => 'Aman Verma',
        'email'    => 'aman@studenthub.com',
        'mobile'   => '9733445566',
        'password' => 'aman%123',
        'course'   => 'Electronics & Communication',
        'role'     => 'student',
        'status'   => 'inactive' // Seeded as inactive for immediate testing of status toggle!
    ]
];

foreach ($seedAccounts as $acc) {
    $email = $acc['email'];
    $checkSt = mysqli_query($conn, "SELECT id FROM `students` WHERE `email` = '$email'");
    $hashed = password_hash($acc['password'], PASSWORD_DEFAULT);

    if (mysqli_num_rows($checkSt) == 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO `students` (`name`, `email`, `mobile`, `password`, `course`, `role`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssssss", $acc['name'], $acc['email'], $acc['mobile'], $hashed, $acc['course'], $acc['role'], $acc['status']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    $checkUsr = mysqli_query($conn, "SELECT id FROM `users` WHERE `email` = '$email'");
    if (mysqli_num_rows($checkUsr) == 0) {
        $uStmt = mysqli_prepare($conn, "INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($uStmt, "sssss", $acc['name'], $acc['email'], $hashed, $acc['role'], $acc['status']);
        mysqli_stmt_execute($uStmt);
        mysqli_stmt_close($uStmt);
    }
}

// 11. Seed Events if empty
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
        $ins = mysqli_prepare($conn, "INSERT INTO `events` (`title`, `day`, `month`, `location`, `time`, `description`, `button_text`, `button_link`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($ins, "ssssssss", $ev['title'], $ev['day'], $ev['month'], $ev['location'], $ev['time'], $ev['description'], $ev['button_text'], $ev['button_link']);
        mysqli_stmt_execute($ins);
        mysqli_stmt_close($ins);
    }
}

// 12. Seed Registrations for Monthly Analytics & Latest Registrations
$checkReg = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `registrations`");
$regCount = mysqli_fetch_assoc($checkReg)['cnt'] ?? 0;

if ($regCount == 0) {
    $seedRegistrations = [
        ['student_id' => 2, 'event_id' => 1, 'student_name' => 'Yaksh Butani', 'student_email' => 'student@studenthub.com', 'event_title' => 'Hackathon 2026', 'registration_date' => '2026-08-02 11:15:00', 'status' => 'Confirmed'],
        ['student_id' => 3, 'event_id' => 1, 'student_name' => 'Rohit Sharma', 'student_email' => 'rohit@studenthub.com', 'event_title' => 'Hackathon 2026', 'registration_date' => '2026-08-04 14:20:00', 'status' => 'Confirmed'],
        ['student_id' => 4, 'event_id' => 3, 'student_name' => 'Priya Patel', 'student_email' => 'priya@studenthub.com', 'event_title' => 'Web Development Workshop', 'registration_date' => '2026-08-10 16:45:00', 'status' => 'Confirmed'],
        ['student_id' => 2, 'event_id' => 4, 'student_name' => 'Yaksh Butani', 'student_email' => 'student@studenthub.com', 'event_title' => 'Sports Tournament', 'registration_date' => '2026-08-15 09:30:00', 'status' => 'Pending'],
        ['student_id' => 3, 'event_id' => 5, 'student_name' => 'Rohit Sharma', 'student_email' => 'rohit@studenthub.com', 'event_title' => 'Placement Training', 'registration_date' => '2026-08-22 10:10:00', 'status' => 'Confirmed'],
        ['student_id' => 4, 'event_id' => 6, 'student_name' => 'Priya Patel', 'student_email' => 'priya@studenthub.com', 'event_title' => "Teacher's Day Celebration", 'registration_date' => '2026-09-01 13:00:00', 'status' => 'Confirmed'],
        ['student_id' => 2, 'event_id' => 3, 'student_name' => 'Yaksh Butani', 'student_email' => 'student@studenthub.com', 'event_title' => 'Web Development Workshop', 'registration_date' => '2026-09-12 15:30:00', 'status' => 'Confirmed'],
        ['student_id' => 3, 'event_id' => 2, 'student_name' => 'Rohit Sharma', 'student_email' => 'rohit@studenthub.com', 'event_title' => 'Independence Day Celebration', 'registration_date' => '2026-09-18 11:20:00', 'status' => 'Confirmed'],
        ['student_id' => 4, 'event_id' => 1, 'student_name' => 'Priya Patel', 'student_email' => 'priya@studenthub.com', 'event_title' => 'Hackathon 2026', 'registration_date' => '2026-10-02 09:40:00', 'status' => 'Confirmed'],
        ['student_id' => 2, 'event_id' => 5, 'student_name' => 'Yaksh Butani', 'student_email' => 'student@studenthub.com', 'event_title' => 'Placement Training', 'registration_date' => '2026-10-10 14:15:00', 'status' => 'Confirmed'],
        // Historical months for analytics chart
        ['student_id' => 3, 'event_id' => 1, 'student_name' => 'Rohit Sharma', 'student_email' => 'rohit@studenthub.com', 'event_title' => 'Coding Marathon', 'registration_date' => '2026-05-14 10:00:00', 'status' => 'Confirmed'],
        ['student_id' => 4, 'event_id' => 2, 'student_name' => 'Priya Patel', 'student_email' => 'priya@studenthub.com', 'event_title' => 'Orientation Day', 'registration_date' => '2026-05-20 12:00:00', 'status' => 'Confirmed'],
        ['student_id' => 2, 'event_id' => 3, 'student_name' => 'Yaksh Butani', 'student_email' => 'student@studenthub.com', 'event_title' => 'Tech Talks', 'registration_date' => '2026-06-08 14:00:00', 'status' => 'Confirmed'],
        ['student_id' => 3, 'event_id' => 4, 'student_name' => 'Rohit Sharma', 'student_email' => 'rohit@studenthub.com', 'event_title' => 'Robotics Seminar', 'registration_date' => '2026-06-25 15:30:00', 'status' => 'Confirmed'],
        ['student_id' => 4, 'event_id' => 5, 'student_name' => 'Priya Patel', 'student_email' => 'priya@studenthub.com', 'event_title' => 'Cloud Bootcamp', 'registration_date' => '2026-07-11 11:00:00', 'status' => 'Confirmed'],
        ['student_id' => 2, 'event_id' => 6, 'student_name' => 'Yaksh Butani', 'student_email' => 'student@studenthub.com', 'event_title' => 'Design Sprint', 'registration_date' => '2026-07-28 16:00:00', 'status' => 'Confirmed']
    ];

    foreach ($seedRegistrations as $reg) {
        $rIns = mysqli_prepare($conn, "INSERT INTO `registrations` (`student_id`, `event_id`, `student_name`, `student_email`, `event_title`, `registration_date`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($rIns, "iisssss", $reg['student_id'], $reg['event_id'], $reg['student_name'], $reg['student_email'], $reg['event_title'], $reg['registration_date'], $reg['status']);
        mysqli_stmt_execute($rIns);
        mysqli_stmt_close($rIns);
    }
}

// 13. Seed Initial Audit Logs if empty
$checkAudit = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `audit_logs`");
$auditCount = mysqli_fetch_assoc($checkAudit)['cnt'] ?? 0;

if ($auditCount == 0) {
    $seedAuditLogs = [
        ['user_id' => 1, 'user_name' => 'System Admin', 'user_role' => 'admin', 'action' => 'System Initialization', 'module' => 'System', 'details' => 'Practical 14 database schema and role-based policies initialized.', 'ip_address' => '127.0.0.1'],
        ['user_id' => 1, 'user_name' => 'System Admin', 'user_role' => 'admin', 'action' => 'User Created', 'module' => 'User Management', 'details' => 'Seeded demo administrator and student accounts.', 'ip_address' => '127.0.0.1'],
        ['user_id' => 1, 'user_name' => 'System Admin', 'user_role' => 'admin', 'action' => 'User Status Changed', 'module' => 'User Management', 'details' => 'Set status of user Aman Verma (ID: 5) to Inactive for status testing.', 'ip_address' => '127.0.0.1'],
        ['user_id' => 1, 'user_name' => 'System Admin', 'user_role' => 'admin', 'action' => 'Event Created', 'module' => 'Event Management', 'details' => 'Created event: Hackathon 2026.', 'ip_address' => '127.0.0.1'],
        ['user_id' => 2, 'user_name' => 'Yaksh Butani', 'user_role' => 'student', 'action' => 'User Login', 'module' => 'Authentication', 'details' => 'Student logged in successfully from campus network.', 'ip_address' => '127.0.0.1'],
        ['user_id' => 2, 'user_name' => 'Yaksh Butani', 'user_role' => 'student', 'action' => 'Event Registration', 'module' => 'Registrations', 'details' => 'Registered for Hackathon 2026.', 'ip_address' => '127.0.0.1'],
        ['user_id' => 1, 'user_name' => 'System Admin', 'user_role' => 'admin', 'action' => 'User Login', 'module' => 'Authentication', 'details' => 'Administrator logged in to Admin Dashboard.', 'ip_address' => '127.0.0.1']
    ];

    foreach ($seedAuditLogs as $log) {
        $aIns = mysqli_prepare($conn, "INSERT INTO `audit_logs` (`user_id`, `user_name`, `user_role`, `action`, `module`, `details`, `ip_address`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($aIns, "issssss", $log['user_id'], $log['user_name'], $log['user_role'], $log['action'], $log['module'], $log['details'], $log['ip_address']);
        mysqli_stmt_execute($aIns);
        mysqli_stmt_close($aIns);
    }
}
?>
