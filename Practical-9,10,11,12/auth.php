<?php
// ==============================================================================
// Authentication & Session Helper
// Practical-10: StudentHub Portal
// Handles: Session start, timeout (15 mins), remember-me cookies, access control
// ==============================================================================

// 1. Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,      // Prevent JavaScript XSS cookie theft
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . "/db.php";

// Session Timeout duration: 15 minutes (900 seconds) of inactivity
define('SESSION_TIMEOUT_DURATION', 900);

// 2. Check Session Inactivity Timeout
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_DURATION)) {
        // Clear session data
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        // Expire remember me cookie if active
        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', time() - 3600, '/');
        }

        header("Location: login.php?error=timeout");
        exit();
    }

    // Refresh last activity time
    $_SESSION['last_activity'] = time();
}

// 3. Optional Remember-Me Cookie Verification (Auto-Login)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $cookieValue = $_COOKIE['remember_me'];
    $parts = explode(':', $cookieValue, 2);

    if (count($parts) === 2) {
        $cookieUserId = (int)$parts[0];
        $cookieToken  = $parts[1];

        $stmt = mysqli_prepare($conn, "SELECT id, name, email, role, remember_token FROM students WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $cookieUserId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($res)) {
                // Cryptographically compare SHA-256 hash of token to prevent timing attacks
                if (!empty($user['remember_token']) && hash_equals($user['remember_token'], hash('sha256', $cookieToken))) {
                    // Regenerate Session ID to prevent session fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id']       = $user['id'];
                    $_SESSION['user_name']     = $user['name'];
                    $_SESSION['user_email']    = $user['email'];
                    $_SESSION['user_role']     = $user['role'];
                    $_SESSION['last_activity'] = time();
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// 4. Access Guard: Restrict access to logged-in users only
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?error=unauthorized");
        exit();
    }
}

// 5. Access Guard: Restrict access to Admin role only
function require_admin() {
    require_login();
    if ($_SESSION['user_role'] !== 'admin') {
        header("Location: dashboard.php?error=forbidden");
        exit();
    }
}
?>
