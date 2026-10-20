-- ==============================================================================
-- Practical 14: Admin Dashboard with Role-Based Access Control, Dynamic Menu, and Audit Log
-- Database Schema for StudentHub Portal
-- Tables: users, students, events, registrations, audit_logs
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `studenthub_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `studenthub_db`;

-- 1. Students Table (with status for Intermediate Extension)
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
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `remember_token` VARCHAR(255) NULL,
    `reg_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users Table (Core RBAC authentication table)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'student',
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Events Table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Registrations Table (Event registrations & status tracking)
CREATE TABLE IF NOT EXISTS `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `student_name` VARCHAR(100) NOT NULL,
    `student_email` VARCHAR(100) NOT NULL,
    `event_title` VARCHAR(150) NOT NULL,
    `registration_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` VARCHAR(20) NOT NULL DEFAULT 'Confirmed',
    `attendance` VARCHAR(20) DEFAULT 'Present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Audit Logs Table (Administrative audit trail & activity traceability)
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `user_name` VARCHAR(100) NOT NULL,
    `user_role` VARCHAR(20) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `details` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Accounts in `students`
INSERT INTO `students` (`name`, `email`, `mobile`, `password`, `course`, `role`, `status`) VALUES
('System Admin', 'admin@studenthub.com', '9876543210', '$2y$10$AwomCEPZySJPoWzLsIK2E.TZnbtgudTTP6t1i2xh1La/8iZQO0cCi', 'System Administration', 'admin', 'active'),
('Yaksh Butani', 'student@studenthub.com', '9429800202', '$2y$10$2YTb3/zHPnnJkSc4vI5dBeP.i3CJuk6vDUrArKH/fZbSBiRkEf4Um', 'B.Tech Computer Science', 'student', 'active'),
('Rohit Sharma', 'rohit@studenthub.com', '9822334455', '$2y$10$2YTb3/zHPnnJkSc4vI5dBeP.i3CJuk6vDUrArKH/fZbSBiRkEf4Um', 'Information Technology', 'student', 'active'),
('Priya Patel', 'priya@studenthub.com', '9811223344', '$2y$10$2YTb3/zHPnnJkSc4vI5dBeP.i3CJuk6vDUrArKH/fZbSBiRkEf4Um', 'Computer Science Engineering', 'student', 'active'),
('Aman Verma', 'aman@studenthub.com', '9733445566', '$2y$10$2YTb3/zHPnnJkSc4vI5dBeP.i3CJuk6vDUrArKH/fZbSBiRkEf4Um', 'Electronics & Communication', 'student', 'inactive')
ON DUPLICATE KEY UPDATE `email` = `email`;

-- Seed Accounts in `users`
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
('System Admin', 'admin@studenthub.com', '$2y$10$AwomCEPZySJPoWzLsIK2E.TZnbtgudTTP6t1i2xh1La/8iZQO0cCi', 'admin', 'active'),
('Yaksh Butani', 'student@studenthub.com', '$2y$10$2YTb3/zHPnnJkSc4vI5dBeP.i3CJuk6vDUrArKH/fZbSBiRkEf4Um', 'student', 'active'),
('Rohit Sharma', 'rohit@studenthub.com', '$2y$10$2YTb3/zHPnnJkSc4vI5dBeP.i3CJuk6vDUrArKH/fZbSBiRkEf4Um', 'student', 'active'),
('Priya Patel', 'priya@studenthub.com', '$2y$10$2YTb3/zHPnnJkSc4vI5dBeP.i3CJuk6vDUrArKH/fZbSBiRkEf4Um', 'student', 'active'),
('Aman Verma', 'aman@studenthub.com', '$2y$10$2YTb3/zHPnnJkSc4vI5dBeP.i3CJuk6vDUrArKH/fZbSBiRkEf4Um', 'student', 'inactive')
ON DUPLICATE KEY UPDATE `email` = `email`;

-- Seed Events
INSERT INTO `events` (`title`, `day`, `month`, `location`, `time`, `description`, `button_text`, `button_link`) VALUES
('Hackathon 2026', '10', 'AUG', 'Computer Department', '10:00 AM', '24-Hour Coding Competition with exciting prizes.', 'Register', '#'),
('Independence Day Celebration', '15', 'AUG', 'Main Ground', '8:00 AM', 'Flag Hoisting, Cultural Program and Speech.', 'View Details', '#'),
('Web Development Workshop', '20', 'AUG', 'Seminar Hall', '11:00 AM', 'Learn HTML, CSS and JavaScript from industry experts.', 'Register', '#'),
('Sports Tournament', '25', 'AUG', 'Sports Complex', '9:00 AM', 'Cricket, Football, Volleyball and Indoor Games.', 'Participate', '#'),
('Placement Training', '30', 'AUG', 'Auditorium', '2:00 PM', 'Aptitude, Resume Building and Interview Preparation.', 'Join Now', '#'),
("Teacher's Day Celebration", '05', 'SEP', 'Auditorium', '10:30 AM', 'Cultural performances and award ceremony.', 'View Details', '#');

-- Seed Registrations (for monthly analytics & latest registrations)
INSERT INTO `registrations` (`student_id`, `event_id`, `student_name`, `student_email`, `event_title`, `registration_date`, `status`) VALUES
(2, 1, 'Yaksh Butani', 'student@studenthub.com', 'Hackathon 2026', '2026-08-02 11:15:00', 'Confirmed'),
(3, 1, 'Rohit Sharma', 'rohit@studenthub.com', 'Hackathon 2026', '2026-08-04 14:20:00', 'Confirmed'),
(4, 3, 'Priya Patel', 'priya@studenthub.com', 'Web Development Workshop', '2026-08-10 16:45:00', 'Confirmed'),
(2, 4, 'Yaksh Butani', 'student@studenthub.com', 'Sports Tournament', '2026-08-15 09:30:00', 'Pending'),
(3, 5, 'Rohit Sharma', 'rohit@studenthub.com', 'Placement Training', '2026-08-22 10:10:00', 'Confirmed'),
(4, 6, 'Priya Patel', 'priya@studenthub.com', "Teacher's Day Celebration", '2026-09-01 13:00:00', 'Confirmed'),
(2, 3, 'Yaksh Butani', 'student@studenthub.com', 'Web Development Workshop', '2026-09-12 15:30:00', 'Confirmed'),
(3, 2, 'Rohit Sharma', 'rohit@studenthub.com', 'Independence Day Celebration', '2026-09-18 11:20:00', 'Confirmed'),
(4, 1, 'Priya Patel', 'priya@studenthub.com', 'Hackathon 2026', '2026-10-02 09:40:00', 'Confirmed'),
(2, 5, 'Yaksh Butani', 'student@studenthub.com', 'Placement Training', '2026-10-10 14:15:00', 'Confirmed'),
(3, 1, 'Rohit Sharma', 'rohit@studenthub.com', 'Coding Marathon', '2026-05-14 10:00:00', 'Confirmed'),
(4, 2, 'Priya Patel', 'priya@studenthub.com', 'Orientation Day', '2026-05-20 12:00:00', 'Confirmed'),
(2, 3, 'Yaksh Butani', 'student@studenthub.com', 'Tech Talks', '2026-06-08 14:00:00', 'Confirmed'),
(3, 4, 'Rohit Sharma', 'rohit@studenthub.com', 'Robotics Seminar', '2026-06-25 15:30:00', 'Confirmed'),
(4, 5, 'Priya Patel', 'priya@studenthub.com', 'Cloud Bootcamp', '2026-07-11 11:00:00', 'Confirmed'),
(2, 6, 'Yaksh Butani', 'student@studenthub.com', 'Design Sprint', '2026-07-28 16:00:00', 'Confirmed');

-- Seed Initial Audit Logs
INSERT INTO `audit_logs` (`user_id`, `user_name`, `user_role`, `action`, `module`, `details`, `ip_address`) VALUES
(1, 'System Admin', 'admin', 'System Initialization', 'System', 'Practical 14 database schema and role-based policies initialized.', '127.0.0.1'),
(1, 'System Admin', 'admin', 'User Created', 'User Management', 'Seeded demo administrator and student accounts.', '127.0.0.1'),
(1, 'System Admin', 'admin', 'User Status Changed', 'User Management', 'Set status of user Aman Verma (ID: 5) to Inactive for status testing.', '127.0.0.1'),
(1, 'System Admin', 'admin', 'Event Created', 'Event Management', 'Created event: Hackathon 2026.', '127.0.0.1'),
(2, 'Yaksh Butani', 'student', 'User Login', 'Authentication', 'Student logged in successfully from campus network.', '127.0.0.1'),
(2, 'Yaksh Butani', 'student', 'Event Registration', 'Registrations', 'Registered for Hackathon 2026.', '127.0.0.1'),
(1, 'System Admin', 'admin', 'User Login', 'Authentication', 'Administrator logged in to Admin Dashboard.', '127.0.0.1');
