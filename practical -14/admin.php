<?php

require_once "auth.php";

// 1. RBAC Access Control: Only admin users allowed
require_admin();

$notification_msg = "";
$notification_type = "";

// -----------------------------------------------------------------------------
// POST / AJAX Action Handlers (Status Toggle & Registration Status)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Intermediate Extension: Toggle Active / Inactive User Status
    if ($action === 'toggle_user_status') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);

        if ($targetUserId > 0) {
            $uStmt = mysqli_prepare($conn, "SELECT id, name, email, role, status FROM students WHERE id = ?");
            mysqli_stmt_bind_param($uStmt, "i", $targetUserId);
            mysqli_stmt_execute($uStmt);
            $uRes = mysqli_stmt_get_result($uStmt);
            $targetUser = mysqli_fetch_assoc($uRes);
            mysqli_stmt_close($uStmt);

            if ($targetUser) {
                // Prevent admin from deactivating self
                if ($targetUser['id'] == $_SESSION['user_id']) {
                    $msg = "Action Denied: You cannot deactivate your own administrator account.";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $msg]);
                        exit();
                    }
                    $notification_msg = $msg;
                    $notification_type = "alert-banner-error";
                } else {
                    $newStatus = ($targetUser['status'] === 'active') ? 'inactive' : 'active';

                    // Update students
                    $upSt = mysqli_prepare($conn, "UPDATE students SET status = ? WHERE id = ?");
                    mysqli_stmt_bind_param($upSt, "si", $newStatus, $targetUserId);
                    mysqli_stmt_execute($upSt);
                    mysqli_stmt_close($upSt);

                    // Update users
                    $upUsr = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE email = ?");
                    mysqli_stmt_bind_param($upUsr, "ss", $newStatus, $targetUser['email']);
                    mysqli_stmt_execute($upUsr);
                    mysqli_stmt_close($upUsr);

                    // Audit Log
                    $adminName = $_SESSION['user_name'];
                    log_audit(
                        $conn,
                        'User Status Changed',
                        'User Management',
                        "Admin '{$adminName}' changed status of user '{$targetUser['name']}' (ID: {$targetUserId}) from '{$targetUser['status']}' to '{$newStatus}'."
                    );

                    $msg = "User '{$targetUser['name']}' is now " . strtoupper($newStatus) . "!";
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => $msg, 'new_status' => $newStatus, 'user_id' => $targetUserId]);
                        exit();
                    }
                    $notification_msg = $msg;
                    $notification_type = "alert-banner-success";
                }
            }
        }
    }

    // Toggle Registration Status
    if ($action === 'toggle_reg_status') {
        $regId = (int)($_POST['reg_id'] ?? 0);
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['ajax']);

        if ($regId > 0) {
            $rStmt = mysqli_prepare($conn, "SELECT id, student_name, event_title, status FROM registrations WHERE id = ?");
            mysqli_stmt_bind_param($rStmt, "i", $regId);
            mysqli_stmt_execute($rStmt);
            $rRes = mysqli_stmt_get_result($rStmt);
            $regData = mysqli_fetch_assoc($rRes);
            mysqli_stmt_close($rStmt);

            if ($regData) {
                $newRegStatus = ($regData['status'] === 'Confirmed') ? 'Cancelled' : 'Confirmed';

                $upReg = mysqli_prepare($conn, "UPDATE registrations SET status = ? WHERE id = ?");
                mysqli_stmt_bind_param($upReg, "si", $newRegStatus, $regId);
                mysqli_stmt_execute($upReg);
                mysqli_stmt_close($upReg);

                log_audit(
                    $conn,
                    'Registration Status Changed',
                    'Registrations',
                    "Admin '{$_SESSION['user_name']}' updated registration #{$regId} status to '{$newRegStatus}'."
                );

                $msg = "Registration #{$regId} status changed to {$newRegStatus}!";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $msg, 'new_status' => $newRegStatus, 'reg_id' => $regId]);
                    exit();
                }
                $notification_msg = $msg;
                $notification_type = "alert-banner-success";
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 2. SQL Aggregations for Dynamic Dashboard Metrics
// -----------------------------------------------------------------------------
$userStatsQuery = mysqli_query($conn, "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as student_count,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count
FROM students");
$userStats = mysqli_fetch_assoc($userStatsQuery);

$totalUsers    = (int)($userStats['total_users'] ?? 0);
$totalStudents = (int)($userStats['student_count'] ?? 0);
$activeUsers   = (int)($userStats['active_count'] ?? 0);
$inactiveUsers = (int)($userStats['inactive_count'] ?? 0);

// Total Events
$totalEventsQuery = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM events");
$totalEvents = (int)(mysqli_fetch_assoc($totalEventsQuery)['cnt'] ?? 0);

// Total Registrations
$regStatsQuery = mysqli_query($conn, "SELECT 
    COUNT(*) as total_reg,
    SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_count
FROM registrations");
$regStats = mysqli_fetch_assoc($regStatsQuery);
$totalRegistrations = (int)($regStats['total_reg'] ?? 0);
$confirmedRegistrations = (int)($regStats['confirmed_count'] ?? 0);

// Fetch All Users for User Management (Intermediate Extension)
$allUsersList = mysqli_query($conn, "SELECT id, name, email, course, role, status, reg_date FROM students ORDER BY id ASC");

// Fetch Latest Registrations
$latestRegistrations = mysqli_query($conn, "SELECT id, student_id, student_name, student_email, event_title, registration_date, status FROM registrations ORDER BY id DESC LIMIT 8");

// Fetch Monthly Registration Counts for Advanced Analytics Chart
$monthlyQuery = mysqli_query($conn, "SELECT 
    MONTH(registration_date) as m_num,
    DATE_FORMAT(registration_date, '%b') as m_name,
    COUNT(*) as reg_count 
FROM registrations 
GROUP BY MONTH(registration_date), DATE_FORMAT(registration_date, '%b') 
ORDER BY m_num ASC");

$chartMonths = [];
$chartCounts = [];
while ($mRow = mysqli_fetch_assoc($monthlyQuery)) {
    $chartMonths[] = $mRow['m_name'];
    $chartCounts[] = (int)$mRow['reg_count'];
}

// Fetch Audit Logs
$auditLogsQuery = mysqli_query($conn, "SELECT * FROM audit_logs ORDER BY id DESC LIMIT 30");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub - Admin Dashboard (Practical 14)</title>
    
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js for Simple Analytics Chart (Advanced Extension) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<div class="admin-layout">

    <!-- ======================================================================= -->
    <!-- SIDEBAR NAVIGATION (White & Blue, Simple, Clean)                        -->
    <!-- ======================================================================= -->
    <aside class="admin-sidebar" id="sidebar">
        <!-- Brand Header -->
        <div class="sidebar-header">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS_msVTJ1UZqmtO-rMykxXBRws_5_EmYQxh7ZsTVTWeQoeh8BjRmlV7sLk&s=10" alt="Logo" class="sidebar-logo">
            <div class="sidebar-brand">
                StudentHub
                <span>Admin Portal</span>
            </div>
        </div>

        <!-- Sidebar Navigation Menu -->
        <nav class="sidebar-nav">
            <div class="nav-heading">Main Dashboard</div>
            <a href="#stats-overview" class="nav-link active">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Overview</span>
            </a>
            <a href="#users-section" class="nav-link">
                <i class="fa-solid fa-users"></i>
                <span>User Management</span>
            </a>
            <a href="#events-section" class="nav-link">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Event Management</span>
            </a>
            <a href="#registrations-section" class="nav-link">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Registrations</span>
            </a>
            <a href="#analytics-section" class="nav-link">
                <i class="fa-solid fa-chart-column"></i>
                <span>Monthly Analytics</span>
            </a>
            <a href="#audit-section" class="nav-link">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Audit Logs</span>
            </a>

            <div class="nav-heading">Shortcuts</div>
            <a href="dashboard.php" class="nav-link" target="_blank">
                <i class="fa-solid fa-graduation-cap"></i>
                <span>Student View <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px; margin-left:auto;"></i></span>
            </a>
            <a href="events.php" class="nav-link" target="_blank">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Events Portal <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:10px; margin-left:auto;"></i></span>
            </a>
            <a href="logout.php" class="nav-link" style="color:var(--danger);">
                <i class="fa-solid fa-right-from-bracket" style="color:var(--danger);"></i>
                <span>Logout</span>
            </a>
        </nav>

        <!-- Sidebar User Footer -->
        <div class="sidebar-footer">
            <div class="user-mini-profile">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Admin" class="user-avatar">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
            <a href="logout.php" class="btn-sidebar-logout" title="Logout">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
        </div>
    </aside>

    <!-- ======================================================================= -->
    <!-- MAIN CONTENT AREA                                                       -->
    <!-- ======================================================================= -->
    <div class="admin-main">

        <!-- Top Header Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="btn-mobile-toggle" id="btnSidebarToggle" aria-label="Toggle menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="page-title">Admin Dashboard</div>
            </div>

            <div class="topbar-right">
                <div class="badge-session">
                    <i class="fa-regular fa-clock"></i>
                    <span><?php echo htmlspecialchars($_SESSION['login_time'] ?? date('d M Y, h:i A')); ?></span>
                </div>
                <a href="#events-section" class="btn-topbar btn-topbar-primary">
                    <i class="fa-solid fa-plus"></i> Add Event
                </a>
                <a href="logout.php" class="btn-topbar btn-topbar-outline">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </header>

        <!-- Main Body Content -->
        <div class="content-wrapper">

            <!-- Practical 14 Topic Strip -->
            <div class="marquee-strip">
                <i class="fa-solid fa-circle-info"></i> <b>Practical 14:</b> Admin Dashboard with Role-Based Access Control (RBAC), Dynamic Role Menu, Active/Inactive User Toggle & Audit Logging
            </div>

            <!-- Notification Alerts -->
            <?php if (!empty($notification_msg)): ?>
                <div class="alert-banner <?php echo $notification_type; ?>" id="serverAlert">
                    <i class="fa-solid <?php echo ($notification_type === 'alert-banner-success') ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                    <span><?php echo htmlspecialchars($notification_msg); ?></span>
                </div>
            <?php endif; ?>

            <div id="ajaxAlert" class="alert-banner alert-banner-success" style="display:none;"></div>

            <!-- =============================================================== -->
            <!-- 1. DYNAMIC STATS CARDS (SQL AGGREGATIONS)                       -->
            <!-- =============================================================== -->
            <section id="stats-overview" class="stats-grid">
                <!-- Total Users -->
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value"><?php echo $totalUsers; ?></div>
                        <div class="stat-subtext">
                            <span style="color:var(--success); font-weight:600;">● <?php echo $activeUsers; ?> Active</span> &nbsp;
                            <span style="color:var(--danger); font-weight:600;">● <?php echo $inactiveUsers; ?> Inactive</span>
                        </div>
                    </div>
                    <div class="stat-icon-wrapper icon-blue">
                        <i class="fa-solid fa-users"></i>
                    </div>
                </div>

                <!-- Students Enrolled -->
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-label">Students Count</div>
                        <div class="stat-value"><?php echo $totalStudents; ?></div>
                        <div class="stat-subtext">Enrolled in university</div>
                    </div>
                    <div class="stat-icon-wrapper icon-indigo">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                </div>

                <!-- Events Scheduled -->
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-label">Events Count</div>
                        <div class="stat-value" id="eventCountBadge"><?php echo $totalEvents; ?></div>
                        <div class="stat-subtext">Scheduled & active</div>
                    </div>
                    <div class="stat-icon-wrapper icon-cyan">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                </div>

                <!-- Total Registrations -->
                <div class="stat-card">
                    <div class="stat-content">
                        <div class="stat-label">Registrations</div>
                        <div class="stat-value"><?php echo $totalRegistrations; ?></div>
                        <div class="stat-subtext">
                            <span style="color:var(--success); font-weight:600;">● <?php echo $confirmedRegistrations; ?> Confirmed</span>
                        </div>
                    </div>
                    <div class="stat-icon-wrapper icon-green">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                </div>
            </section>

            <!-- =============================================================== -->
            <!-- 2. MONTHLY ANALYTICS CHART (ADVANCED EXTENSION)                 -->
            <!-- =============================================================== -->
            <section id="analytics-section" class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">
                            <i class="fa-solid fa-chart-column"></i>
                            Event Registrations by Month
                        </div>
                        <div class="panel-subtitle">Advanced Extension • SQL Aggregated registration trends (<code>GROUP BY MONTH</code>)</div>
                    </div>
                    <span class="badge badge-admin"><i class="fa-solid fa-chart-line"></i> Monthly Analytics</span>
                </div>
                <div class="panel-body">
                    <div class="chart-card-wrapper">
                        <canvas id="monthlyRegistrationsChart"></canvas>
                    </div>
                </div>
            </section>

            <!-- =============================================================== -->
            <!-- 3. USER MANAGEMENT & STATUS TOGGLE (INTERMEDIATE EXTENSION)     -->
            <!-- =============================================================== -->
            <section id="users-section" class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">
                            <i class="fa-solid fa-users-gear"></i>
                            User & Student Management
                        </div>
                        <div class="panel-subtitle">Intermediate Extension • Active/Inactive status toggle with real-time audit logging</div>
                    </div>
                    <div>
                        <input type="text" id="userFilterInput" class="search-control" placeholder="🔍 Search user by name or email...">
                    </div>
                </div>
                <div class="panel-body" style="padding:0;">
                    <div class="table-responsive">
                        <table class="clean-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th width="7%">ID</th>
                                    <th width="22%">Name</th>
                                    <th width="23%">Email</th>
                                    <th width="18%">Course / Department</th>
                                    <th width="10%">Role</th>
                                    <th width="10%">Status</th>
                                    <th width="10%" style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($allUsersList) > 0): ?>
                                    <?php while ($u = mysqli_fetch_assoc($allUsersList)): ?>
                                        <tr id="user-row-<?php echo $u['id']; ?>">
                                            <td>#<?php echo htmlspecialchars($u['id']); ?></td>
                                            <td><b><?php echo htmlspecialchars($u['name']); ?></b></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td><?php echo htmlspecialchars($u['course'] ?: 'General'); ?></td>
                                            <td>
                                                <?php if ($u['role'] === 'admin'): ?>
                                                    <span class="badge badge-admin">Admin</span>
                                                <?php else: ?>
                                                    <span class="badge badge-student">Student</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($u['status'] === 'active'): ?>
                                                    <span class="badge badge-active" id="user-badge-<?php echo $u['id']; ?>">● Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-inactive" id="user-badge-<?php echo $u['id']; ?>">● Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align:right;">
                                                <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                                    <span style="font-size:12px; color:var(--text-subtle); font-weight:600;">(Current Admin)</span>
                                                <?php else: ?>
                                                    <form method="POST" action="admin.php#users-section" style="display:inline;" onsubmit="return handleStatusToggle(event, <?php echo $u['id']; ?>)">
                                                        <input type="hidden" name="action" value="toggle_user_status">
                                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                        <?php if ($u['status'] === 'active'): ?>
                                                            <button type="submit" class="btn-action btn-status-deactivate" id="btn-toggle-<?php echo $u['id']; ?>" title="Deactivate user account">
                                                                <i class="fa-solid fa-ban"></i> Deactivate
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit" class="btn-action btn-status-activate" id="btn-toggle-<?php echo $u['id']; ?>" title="Activate user account">
                                                                <i class="fa-solid fa-check"></i> Activate
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" style="text-align:center; padding:18px;">No users found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- =============================================================== -->
            <!-- 4. LATEST REGISTRATIONS (PROBLEM DEFINITION)                    -->
            <!-- =============================================================== -->
            <section id="registrations-section" class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">
                            <i class="fa-solid fa-clipboard-check"></i>
                            Latest Event Registrations
                        </div>
                        <div class="panel-subtitle">Recent student event enrollments loaded dynamically from MySQL</div>
                    </div>
                    <span class="badge badge-student">Live Database Records</span>
                </div>
                <div class="panel-body" style="padding:0;">
                    <div class="table-responsive">
                        <table class="clean-table">
                            <thead>
                                <tr>
                                    <th width="8%">Reg #</th>
                                    <th width="22%">Student Name</th>
                                    <th width="22%">Email</th>
                                    <th width="24%">Event Title</th>
                                    <th width="14%">Date & Time</th>
                                    <th width="10%" style="text-align:right;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($latestRegistrations) > 0): ?>
                                    <?php while ($reg = mysqli_fetch_assoc($latestRegistrations)): ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($reg['id']); ?></td>
                                            <td><b><?php echo htmlspecialchars($reg['student_name']); ?></b></td>
                                            <td><?php echo htmlspecialchars($reg['student_email']); ?></td>
                                            <td><?php echo htmlspecialchars($reg['event_title']); ?></td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($reg['registration_date'])); ?></td>
                                            <td style="text-align:right;">
                                                <form method="POST" action="admin.php#registrations-section" style="display:inline;">
                                                    <input type="hidden" name="action" value="toggle_reg_status">
                                                    <input type="hidden" name="reg_id" value="<?php echo $reg['id']; ?>">
                                                    <?php if ($reg['status'] === 'Confirmed'): ?>
                                                        <button type="submit" class="badge badge-confirmed" style="border:none; cursor:pointer;" title="Click to toggle status">
                                                            ✓ Confirmed
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="badge badge-pending" style="border:none; cursor:pointer;" title="Click to toggle status">
                                                            ⏳ <?php echo htmlspecialchars($reg['status']); ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center; padding:18px;">No event registrations yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- =============================================================== -->
            <!-- 5. EVENT MANAGEMENT (CRUD + AUDIT TRACKING)                     -->
            <!-- =============================================================== -->
            <section id="events-section" class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">
                            <i class="fa-solid fa-calendar-plus"></i>
                            Event Management
                        </div>
                        <div class="panel-subtitle">Create, update, or remove campus events (All actions are logged in Audit Logs)</div>
                    </div>
                    <div>
                        <input type="text" id="eventSearchInput" class="search-control" placeholder="🔍 Live search events (debounced 300ms)...">
                        <span id="searchLoadingState" style="display:none; font-size:12px; color:var(--primary); margin-left:8px; font-weight:600;">Searching...</span>
                    </div>
                </div>

                <div class="panel-body">
                    <!-- ADD / EDIT EVENT FORM -->
                    <div style="background:#f8fafc; border:1px solid var(--border-color); border-radius:8px; padding:18px; margin-bottom:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                            <h4 id="formTitle" style="font-size:14px; font-weight:700; color:var(--navy-dark);">
                                <i class="fa-solid fa-plus-circle" style="color:var(--primary);"></i> Add New Event
                            </h4>
                            <span id="formLoadingState" style="display:none; font-size:12px; color:var(--primary); font-weight:600;">Processing...</span>
                        </div>

                        <form id="eventAjaxForm">
                            <input type="hidden" id="formAction" name="action" value="create">
                            <input type="hidden" id="eventId" name="id" value="">

                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label class="form-label">Event Title</label>
                                    <input type="text" id="eventTitle" name="title" required placeholder="e.g. AI & Robotics Workshop" class="form-input">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Day & Month</label>
                                    <div style="display:flex; gap:8px;">
                                        <input type="text" id="eventDay" name="day" required placeholder="Day e.g. 15" class="form-input" style="width:40%;">
                                        <input type="text" id="eventMonth" name="month" required placeholder="Month e.g. AUG" class="form-input" style="width:60%;">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Location</label>
                                    <input type="text" id="eventLocation" name="location" required placeholder="e.g. Seminar Hall" class="form-input">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Time</label>
                                    <input type="text" id="eventTime" name="time" required placeholder="e.g. 10:00 AM" class="form-input">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Button Text</label>
                                    <input type="text" id="eventButtonText" name="button_text" value="Register" placeholder="e.g. Register" class="form-input">
                                </div>

                                <div class="form-group full-width">
                                    <label class="form-label">Description</label>
                                    <textarea id="eventDescription" name="description" rows="2" required placeholder="Short event description" class="form-textarea"></textarea>
                                </div>

                                <div class="form-group full-width" style="display:flex; gap:10px; margin-top:4px;">
                                    <button type="submit" id="btnSubmitEvent" class="btn-topbar btn-topbar-primary">
                                        <i class="fa-solid fa-save"></i> Save Event
                                    </button>
                                    <button type="button" id="btnCancelEdit" class="btn-topbar btn-topbar-outline" style="display:none;">
                                        Cancel Edit
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- EVENTS TABLE -->
                    <div class="table-responsive">
                        <table class="clean-table">
                            <thead>
                                <tr>
                                    <th width="7%">ID</th>
                                    <th width="12%">Date</th>
                                    <th width="28%">Event Title</th>
                                    <th width="23%">Location & Time</th>
                                    <th width="12%">Button</th>
                                    <th width="18%" style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="eventsTableBody">
                                <tr><td colspan="6" style="text-align:center; padding:18px;">⏳ Loading events from API...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- =============================================================== -->
            <!-- 6. AUDIT LOGS (KEY QUESTION #4 & TRACEABILITY)                  -->
            <!-- =============================================================== -->
            <section id="audit-section" class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">
                            <i class="fa-solid fa-shield-halved"></i>
                            Administrative Audit Logs
                        </div>
                        <div class="panel-subtitle">Key Question #4 • Traceable chronological record of logins, status changes, and CRUD operations</div>
                    </div>
                    <div>
                        <input type="text" id="auditSearchInput" class="search-control" placeholder="🔍 Filter logs by action or user...">
                    </div>
                </div>

                <div class="panel-body" style="padding:0;">
                    <div class="table-responsive">
                        <table class="clean-table" id="auditLogsTable">
                            <thead>
                                <tr>
                                    <th width="7%">Log #</th>
                                    <th width="16%">Timestamp</th>
                                    <th width="16%">User (Role)</th>
                                    <th width="18%">Action</th>
                                    <th width="14%">Module</th>
                                    <th width="20%">Details</th>
                                    <th width="9%" style="text-align:right;">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($auditLogsQuery) > 0): ?>
                                    <?php while ($log = mysqli_fetch_assoc($auditLogsQuery)): ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($log['id']); ?></td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?></td>
                                            <td>
                                                <b><?php echo htmlspecialchars($log['user_name']); ?></b><br>
                                                <small style="color:var(--text-muted); font-weight:600;"><?php echo strtoupper(htmlspecialchars($log['user_role'])); ?></small>
                                            </td>
                                            <td>
                                                <b><?php echo htmlspecialchars($log['action']); ?></b>
                                            </td>
                                            <td>
                                                <span class="badge badge-student"><?php echo htmlspecialchars($log['module']); ?></span>
                                            </td>
                                            <td style="font-size:13px; color:var(--navy-text);">
                                                <?php echo htmlspecialchars($log['details']); ?>
                                            </td>
                                            <td style="text-align:right;">
                                                <code style="font-size:12px;"><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align:center; padding:18px;">No audit logs recorded yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer style="text-align:center; padding:20px 0 10px; color:var(--text-muted); font-size:13px;">
                © 2026 StudentHub Portal • Admin Dashboard (Practical 14) • Built for Academic Evaluation
            </footer>

        </div> <!-- /.content-wrapper -->
    </div> <!-- /.admin-main -->

</div> <!-- /.admin-layout -->

<!-- ======================================================================= -->
<!-- JAVASCRIPT: SIDEBAR, FETCH API CRUD, AUDIT SEARCH & MONTHLY CHART       -->
<!-- ======================================================================= -->
<script>
    const API_URL = 'api_events.php';
    let cachedEvents = [];
    let debounceTimer = null;

    // Mobile Sidebar Toggle
    const btnSidebarToggle = document.getElementById('btnSidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (btnSidebarToggle && sidebar) {
        btnSidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    // Active link highlighting on scroll/click
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            if (window.innerWidth <= 768 && sidebar) {
                sidebar.classList.remove('open');
            }
        });
    });

    const tableBody     = document.getElementById('eventsTableBody');
    const alertBox      = document.getElementById('ajaxAlert');
    const form          = document.getElementById('eventAjaxForm');
    const formTitle     = document.getElementById('formTitle');
    const formAction    = document.getElementById('formAction');
    const eventIdInput  = document.getElementById('eventId');
    const btnSubmit     = document.getElementById('btnSubmitEvent');
    const btnCancel     = document.getElementById('btnCancelEdit');
    const formLoading   = document.getElementById('formLoadingState');
    const searchInput   = document.getElementById('eventSearchInput');
    const searchLoading = document.getElementById('searchLoadingState');
    const countBadge    = document.getElementById('eventCountBadge');

    function showAlert(message, type = 'success', duration = 4000) {
        alertBox.className = 'alert-banner ' + (type === 'error' ? 'alert-banner-error' : 'alert-banner-success');
        alertBox.innerHTML = (type === 'error' ? '<i class="fa-solid fa-triangle-exclamation"></i> ' : '<i class="fa-solid fa-circle-check"></i> ') + message;
        alertBox.style.display = 'flex';

        if (duration > 0) {
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, duration);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // -------------------------------------------------------------------------
    // 1. AJAX FETCH EVENTS
    // -------------------------------------------------------------------------
    async function loadEvents(search = '') {
        try {
            if (search) searchLoading.style.display = 'inline-block';

            const url = search ? `${API_URL}?search=${encodeURIComponent(search)}` : API_URL;
            const response = await fetch(url);
            const result = await response.json();

            searchLoading.style.display = 'none';

            if (response.ok && result.success) {
                cachedEvents = result.data.events || [];
                renderEventsTable(cachedEvents);
                if (!search && countBadge) {
                    countBadge.innerText = cachedEvents.length;
                }
            } else {
                tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--danger); padding:16px;">Failed to load events: ${escapeHtml(result.message)}</td></tr>`;
            }
        } catch (error) {
            searchLoading.style.display = 'none';
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--danger); padding:16px;">Network error loading events.</td></tr>`;
        }
    }

    function renderEventsTable(events) {
        if (!events || events.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:18px; color:var(--text-muted);">No events found in database.</td></tr>`;
            return;
        }

        let html = '';
        events.forEach(ev => {
            html += `
                <tr id="event-row-${ev.id}">
                    <td>#${escapeHtml(ev.id)}</td>
                    <td><b>${escapeHtml(ev.day)} ${escapeHtml(ev.month)}</b></td>
                    <td>${escapeHtml(ev.title)}</td>
                    <td>${escapeHtml(ev.location)} <small style="color:var(--text-muted);">(${escapeHtml(ev.time)})</small></td>
                    <td><span class="badge badge-admin">${escapeHtml(ev.button_text || 'Register')}</span></td>
                    <td style="text-align:right;">
                        <button type="button" class="btn-action btn-edit-action" onclick="onEditEvent(${ev.id})"><i class="fa-solid fa-pen"></i> Edit</button>
                        <button type="button" class="btn-action btn-delete-action" onclick="onDeleteEvent(${ev.id})"><i class="fa-solid fa-trash"></i> Delete</button>
                    </td>
                </tr>
            `;
        });
        tableBody.innerHTML = html;
    }

    // Submit Event Form
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const title       = document.getElementById('eventTitle').value.trim();
        const day         = document.getElementById('eventDay').value.trim();
        const month       = document.getElementById('eventMonth').value.trim();
        const location    = document.getElementById('eventLocation').value.trim();
        const time        = document.getElementById('eventTime').value.trim();
        const description = document.getElementById('eventDescription').value.trim();
        const button_text = document.getElementById('eventButtonText').value.trim() || 'Register';
        const action      = formAction.value;
        const id          = eventIdInput.value;

        if (!title || !day || !month || !location || !time || !description) {
            showAlert('Please fill in all required fields.', 'error');
            return;
        }

        btnSubmit.disabled = true;
        formLoading.style.display = 'inline-block';

        const payload = { action, id, title, day, month, location, time, description, button_text, button_link: '#' };

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();

            btnSubmit.disabled = false;
            formLoading.style.display = 'none';

            if (response.ok && result.success) {
                showAlert(result.message + ' (Recorded in Audit Logs)', 'success');
                resetEventForm();
                loadEvents();
            } else {
                showAlert(result.message || 'Error processing request.', 'error');
            }
        } catch (error) {
            btnSubmit.disabled = false;
            formLoading.style.display = 'none';
            showAlert('Network error: ' + error.message, 'error');
        }
    });

    window.onEditEvent = function(id) {
        const ev = cachedEvents.find(e => parseInt(e.id) === parseInt(id));
        if (!ev) return;
        eventIdInput.value = ev.id;
        document.getElementById('eventTitle').value = ev.title;
        document.getElementById('eventDay').value = ev.day;
        document.getElementById('eventMonth').value = ev.month;
        document.getElementById('eventLocation').value = ev.location;
        document.getElementById('eventTime').value = ev.time;
        document.getElementById('eventDescription').value = ev.description;
        document.getElementById('eventButtonText').value = ev.button_text || 'Register';

        formAction.value = 'update';
        formTitle.innerHTML = `<i class="fa-solid fa-pen" style="color:var(--primary);"></i> Edit Event (#${ev.id})`;
        btnSubmit.innerHTML = '<i class="fa-solid fa-save"></i> Update Event';
        btnCancel.style.display = 'inline-flex';
        document.getElementById('events-section').scrollIntoView({ behavior: 'smooth' });
    };

    btnCancel.addEventListener('click', resetEventForm);

    function resetEventForm() {
        form.reset();
        formAction.value = 'create';
        eventIdInput.value = '';
        formTitle.innerHTML = '<i class="fa-solid fa-plus-circle" style="color:var(--primary);"></i> Add New Event';
        btnSubmit.innerHTML = '<i class="fa-solid fa-save"></i> Save Event';
        btnCancel.style.display = 'none';
    }

    window.onDeleteEvent = async function(id) {
        if (!confirm(`Are you sure you want to delete event #${id}? This will be recorded in audit logs.`)) return;

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            });
            const result = await response.json();

            if (response.ok && result.success) {
                showAlert(result.message + ' (Recorded in Audit Logs)', 'success');
                const row = document.getElementById(`event-row-${id}`);
                if (row) row.remove();
                if (countBadge) countBadge.innerText = Math.max(0, parseInt(countBadge.innerText || 1) - 1);
            } else {
                showAlert(result.message || 'Failed to delete event.', 'error');
            }
        } catch (error) {
            showAlert('Network error: ' + error.message, 'error');
        }
    };

    searchInput.addEventListener('input', function(e) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            loadEvents(e.target.value.trim());
        }, 300);
    });

    // -------------------------------------------------------------------------
    // 2. USER STATUS TOGGLE (INTERMEDIATE EXTENSION)
    // -------------------------------------------------------------------------
    async function handleStatusToggle(e, userId) {
        e.preventDefault();
        const formEl = e.target;
        const formData = new FormData(formEl);
        formData.append('ajax', '1');

        try {
            const response = await fetch('admin.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const res = await response.json();

            if (res.success) {
                showAlert(res.message + ' (Recorded in Audit Logs)', 'success');
                const badge = document.getElementById(`user-badge-${userId}`);
                const btn   = document.getElementById(`btn-toggle-${userId}`);

                if (res.new_status === 'active') {
                    badge.className = 'badge badge-active';
                    badge.innerText = '● Active';
                    btn.className = 'btn-action btn-status-deactivate';
                    btn.innerHTML = '<i class="fa-solid fa-ban"></i> Deactivate';
                } else {
                    badge.className = 'badge badge-inactive';
                    badge.innerText = '● Inactive';
                    btn.className = 'btn-action btn-status-activate';
                    btn.innerHTML = '<i class="fa-solid fa-check"></i> Activate';
                }
            } else {
                showAlert(res.message, 'error');
            }
        } catch (err) {
            formEl.submit();
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // 3. SEARCH FILTERS FOR USERS & AUDIT LOGS
    // -------------------------------------------------------------------------
    const userFilterInput = document.getElementById('userFilterInput');
    if (userFilterInput) {
        userFilterInput.addEventListener('input', function(e) {
            const filter = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            rows.forEach(r => {
                const text = r.innerText.toLowerCase();
                r.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    const auditSearchInput = document.getElementById('auditSearchInput');
    if (auditSearchInput) {
        auditSearchInput.addEventListener('input', function(e) {
            const filter = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#auditLogsTable tbody tr');
            rows.forEach(r => {
                const text = r.innerText.toLowerCase();
                r.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    // -------------------------------------------------------------------------
    // 4. MONTHLY ANALYTICS CHART (ADVANCED EXTENSION)
    // -------------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        loadEvents();

        const ctx = document.getElementById('monthlyRegistrationsChart');
        if (ctx) {
            const labels = <?php echo json_encode($chartMonths); ?>;
            const dataCounts = <?php echo json_encode($chartCounts); ?>;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels.length ? labels : ['May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                    datasets: [{
                        label: 'Registrations',
                        data: dataCounts.length ? dataCounts : [12, 19, 24, 42, 31, 20],
                        backgroundColor: '#3b82f6',
                        hoverBackgroundColor: '#1d4ed8',
                        borderRadius: 4,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { size: 13, family: 'Inter' },
                            bodyFont: { size: 13, family: 'Inter' },
                            padding: 10,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    return ` ${context.parsed.y} Students Registered`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 5, color: '#64748b', font: { family: 'Inter', size: 12 } },
                            grid: { color: '#f1f5f9', drawBorder: false }
                        },
                        x: {
                            ticks: { color: '#475569', font: { family: 'Inter', size: 12, weight: '600' } },
                            grid: { display: false, drawBorder: false }
                        }
                    }
                }
            });
        }
    });
</script>

</body>
</html>
