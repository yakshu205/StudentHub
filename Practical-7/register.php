<?php
// Start session for CSRF token & notification handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not already set (Advanced Requirement)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Storage file paths
$json_file = __DIR__ . "/students.json";
$csv_file  = __DIR__ . "/students.csv";

// Initialize files if not existing
if (!file_exists($json_file)) {
    file_put_contents($json_file, json_encode([], JSON_PRETTY_PRINT));
}

if (!file_exists($csv_file) || filesize($csv_file) === 0) {
    $fp = fopen($csv_file, "w");
    if ($fp) {
        fputcsv($fp, ["ID", "Full Name", "Email", "Mobile", "DOB", "Gender", "Course", "Skills", "Address", "Registered At"]);
        fclose($fp);
    }
}

$notification_msg = "";
$notification_type = "";
$errors = [];


$name    = "";
$email   = "";
$mobile  = "";
$dob     = "";
$gender  = "";
$course  = "";
$skills  = [];
$address = "";


if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        if (is_array($data)) {
            return array_map('sanitize_input', $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $posted_csrf = $_POST["csrf_token"] ?? "";
    if (empty($posted_csrf) || !hash_equals($_SESSION['csrf_token'], $posted_csrf)) {
        $errors[] = "Security check failed (CSRF token invalid). Please refresh and try again.";
    }


    $name     = sanitize_input($_POST["name"] ?? "");
    $email    = filter_var(trim($_POST["email"] ?? ""), FILTER_SANITIZE_EMAIL);
    $mobile   = sanitize_input($_POST["mobile"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm  = $_POST["confirm"] ?? "";
    $dob      = sanitize_input($_POST["dob"] ?? "");
    $gender   = sanitize_input($_POST["gender"] ?? "");
    $course   = sanitize_input($_POST["course"] ?? "");
    
    if (isset($_POST["skills"])) {
        $raw_skills = is_array($_POST["skills"]) ? $_POST["skills"] : [$_POST["skills"]];
        $skills = sanitize_input($raw_skills);
    } else {
        $skills = [];
    }

    $address = sanitize_input($_POST["address"] ?? "");
    $terms   = isset($_POST["terms"]);



    if (empty($name)) {
        $errors[] = "Full Name is required.";
    } elseif (!preg_match("/^[a-zA-Z\s]{2,50}$/", $name)) {
        $errors[] = "Full Name must contain only letters and spaces (2 to 50 characters).";
    }

 
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    // Mobile validation (10 digits)
    if (empty($mobile)) {
        $errors[] = "Mobile number is required.";
    } elseif (!preg_match("/^[0-9]{10}$/", $mobile)) {
        $errors[] = "Mobile number must be a valid 10-digit number.";
    }

    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    // Confirm password match
    if ($password !== $confirm) {
        $errors[] = "Password and Confirm Password do not match.";
    }

    // Date of Birth validation
    if (empty($dob)) {
        $errors[] = "Date of Birth is required.";
    } else {
        $dob_time = strtotime($dob);
        if ($dob_time === false || $dob_time > time()) {
            $errors[] = "Please enter a valid Date of Birth (cannot be in the future).";
        }
    }

    // Gender validation (Whitelist)
    $allowed_genders = ["Male", "Female", "Other"];
    if (empty($gender) || !in_array($gender, $allowed_genders, true)) {
        $errors[] = "Please select a valid Gender.";
    }

    // Course validation (Whitelist)
    $allowed_courses = ["B.Tech CSE", "B.Tech CE", "B.Tech IT"];
    if (empty($course) || !in_array($course, $allowed_courses, true)) {
        $errors[] = "Please select a valid Course.";
    }

    // Skills validation (Whitelist)
    $allowed_skills = ["C", "C++", "Java", "Python"];
    foreach ($skills as $skill_item) {
        if (!in_array($skill_item, $allowed_skills, true)) {
            $errors[] = "Invalid skill selected: " . htmlspecialchars($skill_item);
            break;
        }
    }

    // Terms validation
    if (!$terms) {
        $errors[] = "Please accept the Terms & Conditions.";
    }

    // 4. Duplicate Check & File Storage
    if (empty($errors)) {
        // Read existing JSON records safely
        $existing_students = [];
        if (file_exists($json_file)) {
            $json_raw = file_get_contents($json_file);
            $decoded = json_decode($json_raw, true);
            if (is_array($decoded)) {
                $existing_students = $decoded;
            }
        }

        // Duplicate email check
        $email_exists = false;
        foreach ($existing_students as $student) {
            if (isset($student['email']) && strtolower($student['email']) === strtolower($email)) {
                $email_exists = true;
                break;
            }
        }

        if ($email_exists) {
            $notification_msg = "Email address already registered. Please use another email.";
            $notification_type = "notification-error";
        } else {
            // Generate auto-increment ID
            $new_id = count($existing_students) > 0 ? (max(array_column($existing_students, 'id')) + 1) : 1;
            $registered_at = date('Y-m-d H:i:s');

       
            $new_record = [
                "id"            => $new_id,
                "name"          => $name,
                "email"         => $email,
                "mobile"        => $mobile,
                "password_hash" => password_hash($password, PASSWORD_DEFAULT),
                "dob"           => $dob,
                "gender"        => $gender,
                "course"        => $course,
                "skills"        => $skills,
                "address"       => $address,
                "registered_at" => $registered_at
            ];

            $existing_students[] = $new_record;

     
            $json_saved = false;
            $json_fp = fopen($json_file, "c+");
            if ($json_fp && flock($json_fp, LOCK_EX)) {
                ftruncate($json_fp, 0);
                rewind($json_fp);
                fwrite($json_fp, json_encode($existing_students, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                fflush($json_fp);
                flock($json_fp, LOCK_UN);
                fclose($json_fp);
                $json_saved = true;
            } elseif ($json_fp) {
                fclose($json_fp);
            }

        
            $csv_saved = false;
            $csv_fp = fopen($csv_file, "a");
            if ($csv_fp && flock($csv_fp, LOCK_EX)) {
                $skills_str = !empty($skills) ? implode(", ", $skills) : "None";
                fputcsv($csv_fp, [
                    $new_id,
                    $name,
                    $email,
                    $mobile,
                    $dob,
                    $gender,
                    $course,
                    $skills_str,
                    $address,
                    $registered_at
                ]);
                fflush($csv_fp);
                flock($csv_fp, LOCK_UN);
                fclose($csv_fp);
                $csv_saved = true;
            } elseif ($csv_fp) {
                fclose($csv_fp);
            }

            // Success or File Error handling (Question 4)
            if ($json_saved && $csv_saved) {
                $notification_msg = "Registration Successful! Data saved to JSON & CSV files.";
                $notification_type = "notification-success";

                // Reset form values on success
                $name = $email = $mobile = $dob = $gender = $course = $address = "";
                $skills = [];

                // Refresh CSRF token after successful submission
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $notification_msg = "Error: Failed to safely write data to storage files.";
                $notification_type = "notification-error";
            }
        }
    } else {
        // Validation errors encountered
        $notification_msg = implode(" ", $errors);
        $notification_type = "notification-error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Registration - Practical 7</title>
    <link rel="stylesheet" href="register.css">
</head>

<body>

<header>
<center>
<br>
<br>
<img src="https://admission.charusat.ac.in/View%20Assets/MainPage/assets/charusat%20images/logo.png" alt="StudentHub Logo" width="120">

    <h1>StudentHub Portal</h1>
    <h3>Create Your Account</h3>

    <p>
        Already have an account?
        <a href="../Practical-2/login.html">Login Here</a>
        &nbsp;|&nbsp;
        <a href="view_records.php">View Stored Records</a>
    </p>
<hr>
</center>
</header>

<!-- Notification popup (Question 4) -->
<div id="notification" class="<?php echo $notification_type; ?>" style="<?php echo !empty($notification_msg) ? 'display: block;' : ''; ?>">
    <?php echo htmlspecialchars($notification_msg); ?>
</div>

<!-- Registration Form submitted using POST (Question 1) -->
<form action="register.php" method="POST" id="register-form">

    <!-- CSRF Token for Security (Advanced Requirement) -->
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

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
        <input type="checkbox" name="skills[]" value="C" <?php echo in_array("C", $skills, true) ? "checked" : ""; ?>> C
        <input type="checkbox" name="skills[]" value="C++" <?php echo in_array("C++", $skills, true) ? "checked" : ""; ?>> C++
        <input type="checkbox" name="skills[]" value="Java" <?php echo in_array("Java", $skills, true) ? "checked" : ""; ?>> Java
        <input type="checkbox" name="skills[]" value="Python" <?php echo in_array("Python", $skills, true) ? "checked" : ""; ?>> Python
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

<center>
    <a href="view_records.php" class="view-btn">📄 View Stored Records (JSON / CSV)</a>
</center>

<hr>

<center>
    <small> <p>&copy; 2026 StudentHub. All Rights Reserved.</p></small>
</center>

<script>
    // Auto-hide notification after 5 seconds if displayed
    let notif = document.getElementById("notification");
    if (notif && notif.style.display === "block") {
        setTimeout(function() {
            notif.style.display = "none";
        }, 5000);
    }
</script>

</body>
</html>
