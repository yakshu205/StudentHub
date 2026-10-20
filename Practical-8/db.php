<?php
// ==============================================================================
// Database Configuration & Connection (XAMPP MySQL)
// Practical-8: StudentHub Registration Form with MySQL Database
// ==============================================================================

$host     = "localhost";
$username = "root";
$password = "";
$database = "studenthub_db";

// 1. Initial connection without database to ensure database exists
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
if (!mysqli_query($conn, $createDbQuery)) {
    die("Error creating database: " . mysqli_error($conn));
}

// 3. Select the database
mysqli_select_db($conn, $database);
mysqli_set_charset($conn, "utf8mb4");

// 4. Automatically create 'students' table if not exists
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
    `reg_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($conn, $createTableQuery)) {
    die("Error creating table: " . mysqli_error($conn));
}
?>
