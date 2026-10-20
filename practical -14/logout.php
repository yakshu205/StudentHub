<?php
// ==============================================================================
// Practical 14: Secure Logout with Audit Logging
// ==============================================================================

require_once "auth.php";

// Record audit log before session destruction
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $uName = $_SESSION['user_name'] ?? 'User';
    $uRole = $_SESSION['user_role'] ?? 'user';
    log_audit($conn, 'User Logout', 'Authentication', "User '{$uName}' ({$uRole}) logged out successfully.", $uid, $uName, $uRole);

    // 1. Clear database remember-token for the logged in user
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
