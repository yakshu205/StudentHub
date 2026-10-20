<?php
// ==============================================================================
// Practical-10: Student Profile (Original UI with dynamic student data)
// ==============================================================================

require_once "auth.php";
require_login();

$userId = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$studentName   = $student['name'] ?? $_SESSION['user_name'];
$studentEmail  = $student['email'] ?? $_SESSION['user_email'];
$studentCourse = $student['course'] ?? 'B.Tech Computer Engineering';
$studentMobile = $student['mobile'] ?? '+91 9876543210';
$studentGender = $student['gender'] ?? 'Male';
$studentDob    = $student['dob'] ?? '2005-01-01';
$studentSkills = !empty($student['skills']) ? explode(',', $student['skills']) : ['HTML', 'CSS', 'JavaScript', 'Problem Solving'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">

    <!-- Profile Header -->
    <div class="profile-header">
        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Student">
        <h1><?php echo htmlspecialchars($studentName); ?></h1>
        <p><?php echo htmlspecialchars($studentCourse); ?></p>
        <span class="badge">3rd Semester (<?php echo strtoupper(htmlspecialchars($_SESSION['user_role'])); ?>)</span>
    </div>

    <!-- Statistics -->
    <div class="stats">
        <div class="card">
            <i class="fa-solid fa-graduation-cap"></i>
            <h2>7.93</h2>
            <p>CGPA</p>
        </div>
        <div class="card">
            <i class="fa-solid fa-user-check"></i>
            <h2>95%</h2>
            <p>Attendance</p>
        </div>
        <div class="card">
            <i class="fa-solid fa-folder-open"></i>
            <h2>2</h2>
            <p>Projects</p>
        </div>
        <div class="card">
            <i class="fa-solid fa-book"></i>
            <h2>7</h2>
            <p>Assignments</p>
        </div>
    </div>

    <!-- Personal Information -->
    <div class="section">
        <h2>Personal Information</h2>
        <table>
            <tr>
                <td><b>Full Name:</b></td>
                <td><?php echo htmlspecialchars($studentName); ?></td>
            </tr>
            <tr>
                <td><b>Email:</b></td>
                <td><?php echo htmlspecialchars($studentEmail); ?></td>
            </tr>
            <tr>
                <td><b>Phone:</b></td>
                <td><?php echo htmlspecialchars($studentMobile); ?></td>
            </tr>
            <tr>
                <td><b>Date of Birth:</b></td>
                <td><?php echo htmlspecialchars($studentDob); ?></td>
            </tr>
            <tr>
                <td><b>Gender:</b></td>
                <td><?php echo htmlspecialchars($studentGender); ?></td>
            </tr>
        </table>
    </div>

    <!-- Academic Details -->
    <div class="section">
        <h2>Academic Details</h2>
        <table>
            <tr>
                <td><b>Course:</b></td>
                <td><?php echo htmlspecialchars($studentCourse); ?></td>
            </tr>
            <tr>
                <td><b>Branch:</b></td>
                <td>Computer Engineering</td>
            </tr>
            <tr>
                <td><b>Semester:</b></td>
                <td>3rd Semester</td>
            </tr>
            <tr>
                <td><b>University:</b></td>
                <td>CHARUSAT University</td>
            </tr>
        </table>
    </div>

    <!-- Skills -->
    <div class="section">
        <h2>Technical Skills</h2>
        <div class="skills">
            <?php foreach ($studentSkills as $skill): ?>
                <span><?php echo htmlspecialchars(trim($skill)); ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Achievements -->
    <div class="section">
        <h2>Achievements</h2>
        <ul>
            <li>🏆 Winner - Coding Competition</li>
            <li>📜 Web Development Workshop Completed</li>
            <li>🚀 National Hackathon Participant</li>
            <li>⭐ Java Programming Certificate</li>
        </ul>
    </div>

    <!-- Buttons -->
    <div class="buttons">
        <a href="edit.php">Edit Profile</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </div>

</div>
</body>
</html>
