
CREATE DATABASE IF NOT EXISTS `studenthub_db`
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `studenthub_db`;

CREATE TABLE IF NOT EXISTS `students` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
