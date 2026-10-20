<?php
// ==============================================================================
// Authentication & Session Helper (Practical 14)
// StudentHub Portal: RBAC Access Control, Audit Logging & Dynamic Menu Loading
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
        log_audit($conn, 'Session Timeout', 'Authentication', "Session expired for user '{$_SESSION['user_name']}' after 15 minutes of inactivity.");
        
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', time() - 3600, '/');
        }

        header("Location: login.php?error=timeout");
        exit();
    }

    // Refresh last activity time
    $_SESSION['last_activity'] = time();

    // 3. Status Check: Check if user has been deactivated while logged in
    $checkStatusQuery = mysqli_prepare($conn, "SELECT status, role FROM students WHERE id = ?");
    if ($checkStatusQuery) {
        mysqli_stmt_bind_param($checkStatusQuery, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($checkStatusQuery);
        $statusRes = mysqli_stmt_get_result($checkStatusQuery);
        if ($statusRow = mysqli_fetch_assoc($statusRes)) {
            if ($statusRow['status'] === 'inactive') {
                log_audit($conn, 'Deactivated Session Terminated', 'RBAC', "Session terminated because user account is set to Inactive.");
                $_SESSION = [];
                session_destroy();
                header("Location: login.php?error=inactive");
                exit();
            }
            // Update role if changed
            $_SESSION['user_role'] = $statusRow['role'];
        }
        mysqli_stmt_close($checkStatusQuery);
    }
}

// 4. Optional Remember-Me Cookie Verification (Auto-Login)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $cookieValue = $_COOKIE['remember_me'];
    $parts = explode(':', $cookieValue, 2);

    if (count($parts) === 2) {
        $cookieUserId = (int)$parts[0];
        $cookieToken  = $parts[1];

        $stmt = mysqli_prepare($conn, "SELECT id, name, email, role, status, remember_token FROM students WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $cookieUserId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($res)) {
                if ($user['status'] !== 'inactive' && !empty($user['remember_token']) && hash_equals($user['remember_token'], hash('sha256', $cookieToken))) {
                    session_regenerate_id(true);

                    $_SESSION['user_id']       = $user['id'];
                    $_SESSION['user_name']     = $user['name'];
                    $_SESSION['user_email']    = $user['email'];
                    $_SESSION['user_role']     = $user['role'];
                    $_SESSION['user_status']   = $user['status'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['login_time']    = date("d M Y, h:i A");

                    log_audit($conn, 'Auto-Login (Remember Me)', 'Authentication', "User '{$user['name']}' logged in via persistent cookie token.");
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// 5. Access Guard: Restrict access to logged-in users only
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?error=unauthorized");
        exit();
    }
}

// 6. Access Guard: Restrict access to Admin role only (RBAC Enforcement)
function require_admin() {
    global $conn;
    require_login();
    if ($_SESSION['user_role'] !== 'admin') {
        $userName = $_SESSION['user_name'] ?? 'Unknown';
        $userRole = $_SESSION['user_role'] ?? 'guest';
        log_audit($conn, 'Access Denied', 'RBAC', "Unauthorized admin access attempted by user '{$userName}' (Role: {$userRole})");
        header("Location: dashboard.php?error=forbidden");
        exit();
    }
}

// 7. Dynamic Menu Generator (Role-Based Access Control Navigation)
function render_navigation($activePage = '') {
    $role = $_SESSION['user_role'] ?? null;
    $menuItems = [];

    if ($role === 'admin') {
        $menuItems = [
            ['title' => 'Admin Dashboard', 'url' => 'admin.php'],
            ['title' => 'Users', 'url' => 'admin.php#users-section'],
            ['title' => 'Events', 'url' => 'admin.php#events-section'],
            ['title' => 'Registrations', 'url' => 'admin.php#registrations-section'],
            ['title' => 'Audit Logs', 'url' => 'admin.php#audit-section'],
            ['title' => 'Student View', 'url' => 'dashboard.php'],
            ['title' => 'Logout', 'url' => 'logout.php']
        ];
    } elseif ($role === 'student') {
        $menuItems = [
            ['title' => 'Dashboard', 'url' => 'dashboard.php'],
            ['title' => 'Events', 'url' => 'events.php'],
            ['title' => 'Profile', 'url' => 'profile.php'],
            ['title' => 'About', 'url' => 'about.php'],
            ['title' => 'Contact', 'url' => 'contact.php'],
            ['title' => 'FAQ', 'url' => 'faq.php'],
            ['title' => 'Feedback', 'url' => 'feedback.php'],
            ['title' => 'Logout', 'url' => 'logout.php']
        ];
    } else {
        $menuItems = [
            ['title' => 'Home', 'url' => 'home.php'],
            ['title' => 'About', 'url' => 'about.php'],
            ['title' => 'Events', 'url' => 'events.php'],
            ['title' => 'Contact', 'url' => 'contact.php'],
            ['title' => 'FAQ', 'url' => 'faq.php'],
            ['title' => 'Register', 'url' => 'register.php'],
            ['title' => 'Login', 'url' => 'login.php']
        ];
    }

    $links = [];
    foreach ($menuItems as $item) {
        $isSelected = ($activePage === $item['url'] || ($activePage && strpos($item['url'], $activePage) === 0));
        $style = $isSelected ? 'style="color:#dfc059; text-decoration:underline;"' : '';
        $links[] = '<a href="' . htmlspecialchars($item['url']) . '" ' . $style . '>' . htmlspecialchars($item['title']) . '</a>';
    }

    $roleBadge = '';
    if ($role === 'admin') {
        $roleBadge = ' <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:12px; font-size:13px; font-weight:bold; margin-left:8px;">🛡️ ADMIN</span>';
    } elseif ($role === 'student') {
        $roleBadge = ' <span style="background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:12px; font-size:13px; font-weight:bold; margin-left:8px;">🎓 STUDENT</span>';
    }

    echo '<nav class="navigation"><center>' . implode(' | ', $links) . $roleBadge . '</center></nav>';
}
?>
