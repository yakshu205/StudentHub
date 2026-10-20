<?php
require_once "auth.php";

// Restrict access: Only logged in users
require_login();

// Fetch student details from database
$userId = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$userName = $student['name'] ?? $_SESSION['user_name'];
$userCourse = $student['course'] ?? 'B.Tech Computer Science';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Dashboard</title>

    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<header>
    <!-- complete -->
    <center>
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS_msVTJ1UZqmtO-rMykxXBRws_5_EmYQxh7ZsTVTWeQoeh8BjRmlV7sLk&s=10"
            alt="StudentHub Logo" width="120">

        <h1>StudentHub Portal</h1>

        <br>
        <p><b>Empowering Students Through Technology</b></p>

    </center>

    <nav class="navigation">

        <center>

            <a href="about.php">About</a> |
            <a href="dashboard.php">Dashboard</a> |
            <a href="events.php">Events</a> |
            <a href="profile.php">Profile</a> |
            <a href="contact.php">Contact</a> |
            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="admin.php">Admin</a> |
            <?php endif; ?>
            <a href="faq.php">FAQ</a> |
            <a href="feedback.php">Feedback</a> |
            <a href="logout.php">Logout</a>

        </center>
    </nav>

    <marquee style="background-color: rgb(251, 248, 148); color: #f37208;">
        Admission Open 2026 • AI Workshop Registration Started • National Hackathon Registration Open • Semester
        Examination Timetable Released
    </marquee>

</header>

<body>

    <div class="container">
        <div class="main">
            <div class="topbar">
                <div class="search">
                    <input type="text" placeholder="Search...">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>

                <div class="user">
                    <i class="fa-solid fa-bell"></i>
                    <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="User">
                    <span><?php echo htmlspecialchars($userName); ?></span>
                </div>
            </div>

            <div class="welcome">
                <h1>Welcome Back 👋 <?php echo htmlspecialchars($userName); ?></h1>
                <p><?php echo htmlspecialchars($userCourse); ?> (Role:
                    <?php echo htmlspecialchars(strtoupper($_SESSION['user_role'])); ?>)</p>
            </div>

            <div class="cards">
                <div class="card">
                    <i class="fa-solid fa-user-check"></i>
                    <h2>95%</h2>
                    <p>Attendance</p>
                </div>

                <div class="card">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <h2>7.93</h2>
                    <p>CGPA</p>
                </div>

                <div class="card">
                    <i class="fa-solid fa-book"></i>
                    <h2>7</h2>
                    <p>Assignments</p>
                </div>

                <div class="card">
                    <i class="fa-solid fa-diagram-project"></i>
                    <h2>2</h2>
                    <p>Projects</p>
                </div>
            </div>

            <div class="dashboard-content">
                <div class="left-panel">
                    <div class="box">
                        <h2>Quick Links</h2>
                        <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
                        <a href="events.php"><i class="fa-solid fa-calendar-days"></i> University Events</a>
                        <a href="edit.php"><i class="fa-solid fa-pen-to-square"></i> Edit Profile</a>
                        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>

                <div class="right-panel">
                    <div class="box">
                        <h2>Latest Notices</h2>
                        <ul>
                            <li>📢 Internal Exam starts from 10 August.</li>
                            <li>📢 Semester Project Submission: 20 August.</li>
                            <li>📢 Hackathon Registration is Open.</li>
                            <li>📢 Library will remain closed on Sunday.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="activity">
                <h2>Recent Activity</h2>
                <ul>
                    <li>✅ Logged in at: <?php echo htmlspecialchars($_SESSION['login_time'] ?? date('h:i A')); ?></li>
                    <li>✅ Session protection enabled with session_regenerate_id()</li>
                    <li>✅ Assignment 7 Submitted Successfully</li>
                    <li>✅ Attendance Updated</li>
                    <li>✅ Semester Fees Paid</li>
                </ul>
            </div>

            <footer>
                <p>© 2026 StudentHub | All Rights Reserved</p>
            </footer>
        </div>
    </div>

</body>

</html>