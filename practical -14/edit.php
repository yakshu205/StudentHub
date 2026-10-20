<?php
// ==============================================================================
// Practical-10: Edit Profile (Original UI with MySQL Update)
// ==============================================================================

require_once "auth.php";
require_login();

$userId = $_SESSION['user_id'];
$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name    = trim($_POST['name'] ?? '');
    $mobile  = trim($_POST['mobile'] ?? '');
    $course  = trim($_POST['course'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $upStmt = mysqli_prepare($conn, "UPDATE students SET name = ?, mobile = ?, course = ?, address = ? WHERE id = ?");
    mysqli_stmt_bind_param($upStmt, "ssssi", $name, $mobile, $course, $address, $userId);
    if (mysqli_stmt_execute($upStmt)) {
        $_SESSION['user_name'] = $name;
        log_audit($conn, 'Profile Updated', 'User Profile', "User '{$name}' updated their profile information.");
        $msg = "Profile updated successfully!";
    }
    mysqli_stmt_close($upStmt);
}

// Fetch current details
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$studentName   = $student['name'] ?? $_SESSION['user_name'];
$studentEmail  = $student['email'] ?? $_SESSION['user_email'];
$studentMobile = $student['mobile'] ?? '+91 9726724445';
$studentCourse = $student['course'] ?? 'Computer Engineering';
$studentAddress = $student['address'] ?? 'Anand, Gujarat';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="edit.css">
</head>
<header>
  <!-- complete -->
<center>
<img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS_msVTJ1UZqmtO-rMykxXBRws_5_EmYQxh7ZsTVTWeQoeh8BjRmlV7sLk&s=10" alt="StudentHub Logo" width="120">

<h1>StudentHub Portal</h1>

<br>
<p><b>Empowering Students Through Technology</b></p>

</center>

<!-- DYNAMIC ROLE-BASED NAVIGATION MENU -->
<?php render_navigation('profile.php'); ?>

<marquee style="background-color: rgb(251, 248, 148); color: #f37208;">
Admission Open 2026 • AI Workshop Registration Started • National Hackathon Registration Open • Semester Examination Timetable Released
</marquee>

</header>
<body>

    <h1 align="center" style="color:rgb(9, 80, 186)"> Edit Profile</h1>

    <?php if (!empty($msg)): ?>
        <p align="center" style="color:green; font-weight:bold;"><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <form action="edit.php" method="POST">

        <table cellpadding="8">

            <tr>
                <td>Full Name : </td>
                <td>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($studentName); ?>" required>
                </td>
            </tr>

            <tr>
                <td>Student ID : </td>
                <td>
                    <input type="text" value="#<?php echo htmlspecialchars($student['id']); ?>" readonly>
                </td>
            </tr>

            <tr>
                <td>Email : </td>
                <td>
                    <input type="email" value="<?php echo htmlspecialchars($studentEmail); ?>" readonly>
                </td>
            </tr>

            <tr>
                <td>Phone Number :</td>
                <td>
                    <input type="tel" name="mobile" value="<?php echo htmlspecialchars($studentMobile); ?>">
                </td>
            </tr>

            <tr>
                <td>Department : </td>
                <td>
                    <select name="course">
                        <option value="Computer Engineering" <?php echo ($studentCourse === 'Computer Engineering') ? 'selected' : ''; ?>>Computer Engineering</option>
                        <option value="Computer Science" <?php echo ($studentCourse === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                        <option value="Information Technology" <?php echo ($studentCourse === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                        <option value="Electronics" <?php echo ($studentCourse === 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                        <option value="Mechanical" <?php echo ($studentCourse === 'Mechanical') ? 'selected' : ''; ?>>Mechanical</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Semester : </td>
                <td>
                    <select>
                        <option selected>3</option>
                        <option>1</option>
                        <option>2</option>
                        <option>4</option>
                        <option>5</option>
                        <option>6</option>
                        <option>7</option>
                        <option>8</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td>Address :</td>
                <td>
                    <textarea name="address" rows="4" cols="30"><?php echo htmlspecialchars($studentAddress); ?></textarea>
                </td>
            </tr>

            <tr>
                <td colspan="2" align="center"><br>
                    <input type="submit" value="Save Changes">
                    <input type="reset" value="Reset">
                </td>
            </tr>

        </table>

    </form>
    <br>

    <center>
        <a href="profile.php"> Back to Profile</a>
    </center>

</body>
</html>
