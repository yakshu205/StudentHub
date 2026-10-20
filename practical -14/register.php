<?php
// Include Auth & Database Connection
require_once "auth.php";

$notification_msg = "";
$notification_type = "";

// Form input variables (to keep values on validation error)
$name    = "";
$email   = "";
$mobile  = "";
$dob     = "";
$gender  = "";
$course  = "";
$skills  = [];
$address = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST["name"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $mobile   = trim($_POST["mobile"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $confirm  = trim($_POST["confirm"] ?? "");
    $dob      = trim($_POST["dob"] ?? "");
    $gender   = trim($_POST["gender"] ?? "");
    $course   = trim($_POST["course"] ?? "");
    
    if (isset($_POST["skills"])) {
        $skills = is_array($_POST["skills"]) ? $_POST["skills"] : [$_POST["skills"]];
    } else {
        $skills = [];
    }
    
    $address  = trim($_POST["address"] ?? "");
    $terms    = isset($_POST["terms"]);

    // Simple Validations
    if (empty($name) || empty($email) || empty($mobile) || empty($password) || empty($confirm) || empty($dob) || empty($gender) || empty($course)) {
        $notification_msg = "Please fill in all required fields.";
        $notification_type = "notification-error";
    } elseif ($password !== $confirm) {
        $notification_msg = "Password and Confirm Password do not match.";
        $notification_type = "notification-error";
    } elseif (!$terms) {
        $notification_msg = "Please accept the Terms & Conditions.";
        $notification_type = "notification-error";
    } else {
        // Check if email already exists
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM students WHERE email = ?");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $notification_msg = "Email already registered. Please use another email.";
            $notification_type = "notification-error";
            mysqli_stmt_close($checkStmt);
        } else {
            mysqli_stmt_close($checkStmt);

            // Hash password with bcrypt
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Join skills as comma-separated string
            $skills_str = !empty($skills) ? implode(", ", $skills) : "";

            // Insert into `students` table
            $insertSql = "INSERT INTO students (name, email, mobile, password, dob, gender, course, skills, address, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 'active')";
            $stmt = mysqli_prepare($conn, $insertSql);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sssssssss", $name, $email, $mobile, $hashedPassword, $dob, $gender, $course, $skills_str, $address);

                if (mysqli_stmt_execute($stmt)) {
                    $newId = mysqli_insert_id($conn);

                    // Also sync into `users` table
                    $uStmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'student', 'active')");
                    if ($uStmt) {
                        mysqli_stmt_bind_param($uStmt, "sss", $name, $email, $hashedPassword);
                        mysqli_stmt_execute($uStmt);
                        mysqli_stmt_close($uStmt);
                    }

                    // Audit Log
                    log_audit($conn, 'User Registered', 'Authentication', "New student account registered: '{$name}' ({$email}).", $newId, $name, 'student');

                    $notification_msg = "Registration Successful! You can now login with your credentials.";
                    $notification_type = "notification-success";

                    // Clear fields after success
                    $name = $email = $mobile = $dob = $gender = $course = $address = "";
                    $skills = [];
                } else {
                    $notification_msg = "Database Error: " . mysqli_error($conn);
                    $notification_type = "notification-error";
                }
                mysqli_stmt_close($stmt);
            } else {
                $notification_msg = "Query Error: " . mysqli_error($conn);
                $notification_type = "notification-error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Registration (Practical 14)</title>
    <link rel="stylesheet" href="register.css">
</head>

<body>

<header>
<center>
<br>
<img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS_msVTJ1UZqmtO-rMykxXBRws_5_EmYQxh7ZsTVTWeQoeh8BjRmlV7sLk&s=10" alt="StudentHub Logo" width="120">

    <h1>StudentHub Portal</h1>
    <h3>Create Your Account</h3>

    <p>
        Already have an account?
        <a href="login.php">Login Here</a>
    </p>
<hr>
</center>
</header>

<!-- Notification popup -->
<div id="notification" class="<?php echo $notification_type; ?>" style="<?php echo !empty($notification_msg) ? 'display: block;' : ''; ?>">
    <?php echo htmlspecialchars($notification_msg); ?>
</div>

<form action="register.php" method="POST" id="register-form">

<center>

<p> <b>Registration Form</b></p>   

<table>

<tr>
    <td><label for="name">Full Name</label></td>
    <td><input type="text" id="name" name="name" placeholder="Enter Full Name" value="<?php echo htmlspecialchars($name); ?>" required></td>
</tr>

<tr>
    <td><label for="email">Email</label></td>
    <td><input type="email" id="email" name="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($email); ?>" required></td>
</tr>

<tr>
    <td><label for="mobile">Mobile</label></td>
    <td><input type="tel" id="mobile" name="mobile" placeholder="Enter Mobile Number" value="<?php echo htmlspecialchars($mobile); ?>" required></td>
</tr>

<tr>
    <td><label for="password">Password</label></td>
    <td><input type="password" id="password" name="password" placeholder="Enter Password" required></td>
</tr>

<tr>
    <td><label for="confirm">Confirm Password</label></td>
    <td><input type="password" id="confirm" name="confirm" placeholder="Confirm Password" required></td>
</tr>

<tr>
    <td><label for="dob">Date of Birth</label></td>
    <td><input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($dob); ?>" required></td>
</tr>

<tr>
    <td>Gender</td>
    <td>
        <input type="radio" name="gender" id="male" value="Male" <?php echo ($gender === "Male") ? "checked" : ""; ?> required>
        <label for="male">Male</label>

        <input type="radio" name="gender" id="female" value="Female" <?php echo ($gender === "Female") ? "checked" : ""; ?>>
        <label for="female">Female</label>

        <input type="radio" name="gender" id="other" value="Other" <?php echo ($gender === "Other") ? "checked" : ""; ?>>
        <label for="other">Other</label>
    </td>
</tr>

<tr>
    <td><label for="course">Course</label></td>
    <td>
        <select id="course" name="course" required>
            <option value="">Select Course</option>
            <option value="B.Tech CSE" <?php echo ($course === "B.Tech CSE") ? "selected" : ""; ?>>B.Tech CSE</option>
            <option value="B.Tech CE" <?php echo ($course === "B.Tech CE") ? "selected" : ""; ?>>B.Tech CE</option>
            <option value="B.Tech IT" <?php echo ($course === "B.Tech IT") ? "selected" : ""; ?>>B.Tech IT</option>
        </select>
    </td>
</tr>

<tr>
    <td>Skills</td>
    <td>
        <input type="checkbox" name="skills[]" value="C" <?php echo in_array("C", $skills) ? "checked" : ""; ?>> C
        <input type="checkbox" name="skills[]" value="C++" <?php echo in_array("C++", $skills) ? "checked" : ""; ?>> C++
        <input type="checkbox" name="skills[]" value="Java" <?php echo in_array("Java", $skills) ? "checked" : ""; ?>> Java
        <input type="checkbox" name="skills[]" value="Python" <?php echo in_array("Python", $skills) ? "checked" : ""; ?>> Python
    </td>
</tr>

<tr>
    <td><label for="address">Address</label></td>
    <td>
        <textarea id="address" name="address" rows="3" cols="25" placeholder="Enter Your Address"><?php echo htmlspecialchars($address); ?></textarea>
    </td>
</tr>

<tr>
    <td colspan="2" align="center">
        <input type="checkbox" id="terms" name="terms" required>
        I agree to the Terms & Conditions
    </td>
</tr>

<tr>
    <td colspan="2" align="center">
        <button type="submit" id="submit">Register</button>
        <button type="reset" id="reset">Clear</button>
    </td>
</tr>

</table>

</center>

</form>

<hr>

<center>
    <small> <p>&copy; 2026 StudentHub. All Rights Reserved.</p></small>
</center>

<script>
    // Auto-hide notification after 4 seconds if displayed
    let notif = document.getElementById("notification");
    if (notif && notif.style.display === "block") {
        setTimeout(function() {
            notif.style.display = "none";
        }, 4000);
    }
</script>

</body>
</html>
