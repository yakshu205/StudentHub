<?php
// ==============================================================================
// Practical 14: StudentHub Login with RBAC & Audit Logging
// Requirements:
// 1. PHP Sessions with Secure Lifetime
// 2. password_verify() with Hash Protection
// 3. session_regenerate_id(true) against Fixation
// 4. Role-based redirection (Admin -> admin.php, Student -> dashboard.php)
// 5. Active/Inactive Status verification (Intermediate Extension)
// 6. Security Audit logging for all login attempts
// ==============================================================================

require_once "auth.php";

// If already logged in, redirect based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$notification_msg = "";
$notification_type = "";

// Check query param messages
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $notification_msg = "Please login first to access the dashboard.";
        $notification_type = "notification-error";
    } elseif ($_GET['error'] === 'timeout') {
        $notification_msg = "Session expired due to 15 mins inactivity. Please login again.";
        $notification_type = "notification-error";
    } elseif ($_GET['error'] === 'forbidden') {
        $notification_msg = "Access Denied: Administrator role required to access that section.";
        $notification_type = "notification-error";
    } elseif ($_GET['error'] === 'inactive') {
        $notification_msg = "Your account has been deactivated by the Administrator. Please contact support.";
        $notification_type = "notification-error";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') {
    $notification_msg = "Successfully logged out!";
    $notification_type = "notification-success";
}

// Process Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $remember = isset($_POST["remember_me"]);

    if (empty($email) || empty($password)) {
        $notification_msg = "Please enter both Email and Password.";
        $notification_type = "notification-error";
    } else {
        // Fetch user from MySQL
        $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role, status FROM students WHERE email = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                // 1. Verify Password with password_verify()
                $isPasswordCorrect = password_verify($password, $user['password']);

                // Fallback for earlier plain-text passwords
                if (!$isPasswordCorrect && $password === $user['password']) {
                    $isPasswordCorrect = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upStmt = mysqli_prepare($conn, "UPDATE students SET password = ? WHERE id = ?");
                    mysqli_stmt_bind_param($upStmt, "si", $newHash, $user['id']);
                    mysqli_stmt_execute($upStmt);
                    mysqli_stmt_close($upStmt);
                }

                if ($isPasswordCorrect) {
                    // 2. Active/Inactive Status Check (Intermediate Extension)
                    if (isset($user['status']) && $user['status'] === 'inactive') {
                        log_audit($conn, 'Login Blocked (Inactive)', 'Authentication', "Login blocked for deactivated account: '{$email}'.", $user['id'], $user['name'], $user['role']);
                        $notification_msg = "Your account is currently Inactive/Suspended. Please contact the administrator.";
                        $notification_type = "notification-error";
                    } else {
                        // 3. Regenerate session ID after login
                        session_regenerate_id(true);

                        // 4. Set Session variables
                        $_SESSION['user_id']       = $user['id'];
                        $_SESSION['user_name']     = $user['name'];
                        $_SESSION['user_email']    = $user['email'];
                        $_SESSION['user_role']     = $user['role'];
                        $_SESSION['user_status']   = $user['status'] ?? 'active';
                        $_SESSION['last_activity'] = time();
                        $_SESSION['login_time']    = date("d M Y, h:i A");

                        // Update last_login in users table
                        mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE email = '{$email}'");

                        // Record Audit Log for successful login
                        log_audit($conn, 'User Login', 'Authentication', "User '{$user['name']}' ({$user['role']}) logged in successfully.", $user['id'], $user['name'], $user['role']);

                        // 5. Secure Remember-Me Token
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $hashedToken = hash('sha256', $token);

                            $tokenStmt = mysqli_prepare($conn, "UPDATE students SET remember_token = ? WHERE id = ?");
                            mysqli_stmt_bind_param($tokenStmt, "si", $hashedToken, $user['id']);
                            mysqli_stmt_execute($tokenStmt);
                            mysqli_stmt_close($tokenStmt);

                            setcookie(
                                'remember_me',
                                $user['id'] . ':' . $token,
                                [
                                    'expires'  => time() + (30 * 24 * 60 * 60),
                                    'path'     => '/',
                                    'httponly' => true,
                                    'samesite' => 'Lax'
                                ]
                            );
                        }

                        mysqli_stmt_close($stmt);

                        // 6. Role-based Redirection
                        if ($user['role'] === 'admin') {
                            header("Location: admin.php");
                        } else {
                            header("Location: dashboard.php");
                        }
                        exit();
                    }

                } else {
                    log_audit($conn, 'Failed Login Attempt', 'Authentication', "Invalid password entered for email '{$email}'.", $user['id'], $user['name'], $user['role']);
                    $notification_msg = "Invalid Password. Please try again.";
                    $notification_type = "notification-error";
                }
            } else {
                log_audit($conn, 'Failed Login Attempt', 'Authentication', "Non-existent email '{$email}' attempted to login.");
                $notification_msg = "No account found with this email.";
                $notification_type = "notification-error";
            }
            mysqli_stmt_close($stmt);
        } else {
            $notification_msg = "Database Error: " . mysqli_error($conn);
            $notification_type = "notification-error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Login (Practical 14)</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
 <!-- complete -->
<center>

    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS_msVTJ1UZqmtO-rMykxXBRws_5_EmYQxh7ZsTVTWeQoeh8BjRmlV7sLk&s=10"
        alt="StudentHub Logo" width="150">

    <h1>StudentHub Portal</h1>
    <h3>Welcome Back</h3>

    <p>
        Don't have an account?
        <a href="register.php" id="register-link">Register Here</a>
    </p>

</center>

<?php if (!empty($notification_msg)): ?>
    <div id="notification" class="<?php echo $notification_type; ?>" style="display:block;">
        <?php echo htmlspecialchars($notification_msg); ?>
    </div>
<?php endif; ?>

<form action="login.php" method="POST" id="login-form">

<center>

   <center>
    <legend><b>Login Form</b></legend>
    </center>

    <table cellpadding="10">

        <tr>
            <td><label for="email">Email</label></td>
            <td>
                <input type="email" id="email" name="email"
                    placeholder="Enter Your Email" required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </td>
        </tr>

        <tr>
            <td><label for="password">Password</label></td>
            <td>
                <input type="password" id="password" name="password"
                    placeholder="Enter Your Password" required>
            </td>
        </tr>

        <tr>
            <td colspan="2" align="left">
                <label style="font-weight:normal; font-size:14px; cursor:pointer;">
                    <input type="checkbox" name="remember_me" value="1" style="vertical-align:middle; width:auto;">
                    Remember Me
                </label>
            </td>
        </tr>

        <tr>
            <td colspan="2" align="right">
                <a href="#" id="forgot-password-link">Forgot Password?</a>
            </td>
        </tr>

        <tr>
            <td colspan="2" align="center">
                <button type="submit" id="login-btn">Login</button>
                <button type="reset" id="clear-btn">Clear</button>
            </td>
        </tr>

    </table>

</center>

</form>

<center>
    <div style="max-width:440px; text-align:left; font-size:13px; color:#555; background:#fff; padding:12px 16px; border-radius:8px; margin: 10px auto; border-left:4px solid #0d57ec; box-shadow:0 2px 6px rgba(0,0,0,0.08);">
        <b>🔑 Demo Accounts (Practical 14 RBAC):</b><br>
        • <b>Admin:</b> <code>admin@studenthub.com</code> / <code>thor%123</code><br>
        • <b>Active Student:</b> <code>student@studenthub.com</code> / <code>iron%123</code><br>
        • <b>Inactive Student (Test Status Toggle):</b> <code>aman@studenthub.com</code> / <code>aman%123</code>
    </div>

    <p>
        New User?
        <a href="register.php" id="create-account-link">Create an Account</a>
    </p>

    <small>
        © 2026 StudentHub Portal | All Rights Reserved
    </small>
</center>

</body>
</html>
