<?php
// ==============================================================================
// Practical-10: Secure Logout
// Requirements implemented:
// 1. Clears remember-me token from MySQL
// 2. Deletes remember-me cookie & session cookie
// 3. Destroys active session
// 4. Redirects to login page with feedback message
// ==============================================================================

require_once "auth.php";

// 1. Clear database remember-token for the logged in user
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $clearStmt = mysqli_prepare($conn, "UPDATE students SET remember_token = NULL WHERE id = ?");
    if ($clearStmt) {
        mysqli_stmt_bind_param($clearStmt, "i", $uid);
        mysqli_stmt_execute($clearStmt);
        mysqli_stmt_close($clearStmt);
    }
}

// 2. Delete Remember-Me cookie
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
    unset($_COOKIE['remember_me']);
}

// 3. Clear all session variables
$_SESSION = [];

// 4. Invalidate session cookie in client browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 5. Completely destroy the PHP session
session_destroy();

// 6. Redirect to login page
header("Location: login.php?msg=logged_out");
exit();
?>
