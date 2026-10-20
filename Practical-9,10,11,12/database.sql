-- ==============================================================================
-- Practical-10: Database Schema with Students, Roles, Remember-Token & Events
-- StudentHub Portal
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `studenthub_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `studenthub_db`;

-- 1. Students Table
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
    `role` VARCHAR(20) NOT NULL DEFAULT 'student',
    `remember_token` VARCHAR(255) NULL,
    `reg_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Sample Accounts (Passwords hashed using PHP password_hash)
-- Admin: admin@studenthub.com / thor%123
-- Student: student@studenthub.com / iron%123
INSERT INTO
    `students` (
        `name`,
        `email`,
        `mobile`,
        `password`,
        `course`,
        `role`
    )
VALUES (
        'System Admin',
        'admin@studenthub.com',
        '9876543210',
        '$2y$10$AwomCEPZySJPoWzLsIK2E.TZnbtgudTTP6t1i2xh1La/8iZQO0cCi',
        'System Administration',
        'admin'
    ),
    (
        'Yaksh Butani',
        'student@studenthub.com',
        '9429800202',
        '$2y$10$2YTb3/zHPnnJkSc4vI5dBeP.i3CJuk6vDUrArKH/fZbSBiRkEf4Um',
        'B.Tech Computer Science',
        'student'
    )
ON DUPLICATE KEY UPDATE
    `email` = `email`;

-- 2. Events Table (Managed by Admin, Dynamically Displayed on Student Events Page)
CREATE TABLE IF NOT EXISTS `events` (
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
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Initial Seed Events
INSERT INTO
    `events` (
        `title`,
        `day`,
        `month`,
        `location`,
        `time`,
        `description`,
        `button_text`,
        `button_link`
    )
VALUES (
        'Hackathon 2026',
        '10',
        'AUG',
        'Computer Department',
        '10:00 AM',
        '24-Hour Coding Competition with exciting prizes.',
        'Register',
        '#'
    ),
    (
        'Independence Day Celebration',
        '15',
        'AUG',
        'Main Ground',
        '8:00 AM',
        'Flag Hoisting, Cultural Program and Speech.',
        'View Details',
        '#'
    ),
    (
        'Web Development Workshop',
        '20',
        'AUG',
        'Seminar Hall',
        '11:00 AM',
        'Learn HTML, CSS and JavaScript from industry experts.',
        'Register',
        '#'
    ),
    (
        'Sports Tournament',
        '25',
        'AUG',
        'Sports Complex',
        '9:00 AM',
        'Cricket, Football, Volleyball and Indoor Games.',
        'Participate',
        '#'
    ),
    (
        'Placement Training',
        '30',
        'AUG',
        'Auditorium',
        '2:00 PM',
        'Aptitude, Resume Building and Interview Preparation.',
        'Join Now',
        '#'
    ),
    (
        "Teacher's Day Celebration",
        '05',
        'SEP',
        'Auditorium',
        '10:30 AM',
        'Cultural performances and award ceremony.',
        'View Details',
        '#'
    );