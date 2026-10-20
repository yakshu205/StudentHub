<?php
// ==============================================================================
// Practical-10: Simple Admin Dashboard & Event Management
// Requirements:
// 1. Role-based Access Control (Only 'admin')
// 2. Add / Edit / Delete Events dynamically reflected in Student Events page
// 3. Original StudentHub UI and admin.css styling
// ==============================================================================

require_once "auth.php";

// Restrict access: Only users with 'admin' role
require_admin();

$notification_msg = "";
$notification_type = "";

// 1. Handle Delete Event
if (isset($_GET['action']) && $_GET['action'] === 'delete_event' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM `events` WHERE `id` = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        header("Location: admin.php?msg=deleted#events-section");
        exit();
    } else {
        $notification_msg = "Error deleting event: " . mysqli_error($conn);
        $notification_type = "notification-error";
    }
    mysqli_stmt_close($stmt);
}

// 2. Handle Add or Edit Event Form Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $form_action  = $_POST['form_action'] ?? '';
    $title        = trim($_POST['title'] ?? '');
    $day          = trim($_POST['day'] ?? '');
    $month        = strtoupper(trim($_POST['month'] ?? ''));
    $location     = trim($_POST['location'] ?? '');
    $time         = trim($_POST['time'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $button_text  = trim($_POST['button_text'] ?? 'Register');
    $button_link  = trim($_POST['button_link'] ?? '#');

    if (empty($title) || empty($day) || empty($month) || empty($location) || empty($time) || empty($description)) {
        $notification_msg = "Please fill in all required event details.";
        $notification_type = "notification-error";
    } else {
        if ($form_action === 'edit_event') {
            $event_id = (int)$_POST['event_id'];
            $stmt = mysqli_prepare($conn, "UPDATE `events` SET `title`=?, `day`=?, `month`=?, `location`=?, `time`=?, `description`=?, `button_text`=?, `button_link`=? WHERE `id`=?");
            mysqli_stmt_bind_param($stmt, "ssssssssi", $title, $day, $month, $location, $time, $description, $button_text, $button_link, $event_id);
            if (mysqli_stmt_execute($stmt)) {
                header("Location: admin.php?msg=updated#events-section");
                exit();
            } else {
                $notification_msg = "Error updating event: " . mysqli_error($conn);
                $notification_type = "notification-error";
            }
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO `events` (`title`, `day`, `month`, `location`, `time`, `description`, `button_text`, `button_link`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssssss", $title, $day, $month, $location, $time, $description, $button_text, $button_link);
            if (mysqli_stmt_execute($stmt)) {
                header("Location: admin.php?msg=added#events-section");
                exit();
            } else {
                $notification_msg = "Error adding event: " . mysqli_error($conn);
                $notification_type = "notification-error";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Check feedback message from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $notification_msg = "Event Added Successfully! It is now live on the Student Events page.";
        $notification_type = "notification-success";
    } elseif ($_GET['msg'] === 'updated') {
        $notification_msg = "Event Updated Successfully! Changes reflected in Student Events page.";
        $notification_type = "notification-success";
    } elseif ($_GET['msg'] === 'deleted') {
        $notification_msg = "Event Deleted Successfully! Removed from Student Events page.";
        $notification_type = "notification-success";
    }
}

// Fetch event data if in Edit Mode
$editMode = false;
$editEvent = [
    'id'          => '',
    'title'       => '',
    'day'         => '',
    'month'       => '',
    'location'    => '',
    'time'        => '',
    'description' => '',
    'button_text' => 'Register',
    'button_link' => '#'
];

if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $fetchEdit = mysqli_query($conn, "SELECT * FROM `events` WHERE `id` = $edit_id");
    if ($row = mysqli_fetch_assoc($fetchEdit)) {
        $editMode = true;
        $editEvent = $row;
    }
}

// Fetch statistics
$totalStudentsQuery = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM students WHERE role = 'student'");
$totalStudents = mysqli_fetch_assoc($totalStudentsQuery)['cnt'] ?? 0;

$totalEventsQuery = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM events");
$totalEvents = mysqli_fetch_assoc($totalEventsQuery)['cnt'] ?? 0;

// Fetch all events
$allEvents = mysqli_query($conn, "SELECT * FROM `events` ORDER BY `id` DESC");

// Fetch all students
$allStudents = mysqli_query($conn, "SELECT id, name, email, course, role FROM students WHERE role = 'student' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Admin Panel</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Simple table & form styles matching user's admin layout */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #fff;
        }
        .admin-table th, .admin-table td {
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 16px;
            text-align: left;
        }
        .admin-table th {
            background-color: #2f5f80e1;
            color: #fff;
        }
        .admin-table tr:hover {
            background-color: #f1f5f9;
        }
        .btn-action {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            margin-right: 5px;
        }
        .btn-edit {
            background: #0284c7;
            color: #fff;
        }
        .btn-delete {
            background: #dc2626;
            color: #fff;
        }
        .event-form-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .event-form-table td {
            padding: 8px;
            font-size: 16px;
        }
        .event-form-table input, .event-form-table textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 15px;
            box-sizing: border-box;
        }
        .btn-submit {
            background: #2faad6;
            color: #222;
            border: none;
            padding: 10px 22px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
        }
        .btn-submit:hover {
            background: #3e90e3;
            color: #fff;
        }
        .alert-box {
            padding: 12px 18px;
            margin: 15px 0;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
        }
        .alert-success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<header>
  <!-- complete -->
<center>
<img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS_msVTJ1UZqmtO-rMykxXBRws_5_EmYQxh7ZsTVTWeQoeh8BjRmlV7sLk&s=10" alt="StudentHub Logo" width="120">

<h1>StudentHub Portal</h1>

<br>
<p><b>Empowering Students Through Technology</b></p>

</center>

<nav class="navigation">

<center>

<a href="about.php">About</a> |
<a href="dashboard.php">Dashboard</a> |
<a href="events.php">Events</a> |
<a href="profile.php">Profile</a> |
<a href="contact.php">Contact</a> |
<a href="admin.php">Admin</a> |
<a href="faq.php">FAQ</a> |
<a href="feedback.php">Feedback</a> |
<a href="logout.php">Logout</a>

</center> 
</nav>

<marquee style="background-color: rgb(251, 248, 148); color: #f37208;">
Admission Open 2026 • AI Workshop Registration Started • National Hackathon Registration Open • Semester Examination Timetable Released
</marquee>

</header>
<body>

   <main>
    <section>
        <h1>🛠 Student Hub - Admin Panel</h1>
        <p>Welcome, Administrator (<b><?php echo htmlspecialchars($_SESSION['user_name']); ?></b>)! Manage students, events, courses, and announcements from one place.</p>
    </section>

    <!-- Feedback Notification -->
    <?php if (!empty($notification_msg)): ?>
        <div class="alert-box <?php echo ($notification_type === 'notification-error') ? 'alert-error' : 'alert-success'; ?>">
            <?php echo htmlspecialchars($notification_msg); ?>
        </div>
    <?php endif; ?>

    <!-- Overview Section -->
    <section>
        <h2>Dashboard Overview</h2>
        <p><strong>Total Students in Database:</strong> <?php echo $totalStudents; ?></p>
        <p><strong>Total Events Scheduled:</strong> <?php echo $totalEvents; ?></p>
        <p><strong>Faculty Members:</strong> 85</p>
        <p><strong>Courses Available:</strong> 24</p>
        <p><strong>Feedback Received:</strong> 340</p>
    </section>

    <hr>

    <!-- EVENT MANAGEMENT CRUD SECTION -->
    <section id="events-section">
        <h2>🎉 Event Management (Dynamic for Students)</h2>
        <p>Any event added, edited, or deleted here updates <b>instantly</b> on the student's 
           <a href="events.php" target="_blank" style="color:#0284c7; font-weight:bold;">Events Page (events.php)</a>.
        </p>

        <!-- ADD / EDIT EVENT FORM -->
        <div style="width:100%; max-width:800px; margin:20px 0; padding:20px; background:#f9fbff; border-left:5px solid #2faad6; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom:12px; color:#2f5f80e1;">
                <?php echo $editMode ? "✏️ Edit Event (#" . htmlspecialchars($editEvent['id']) . ")" : "➕ Add New Event"; ?>
            </h3>

            <form action="admin.php" method="POST">
                <input type="hidden" name="form_action" value="<?php echo $editMode ? 'edit_event' : 'add_event'; ?>">
                <?php if ($editMode): ?>
                    <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($editEvent['id']); ?>">
                <?php endif; ?>

                <table class="event-form-table">
                    <tr>
                        <td width="25%"><label><b>Event Title:</b></label></td>
                        <td><input type="text" name="title" required placeholder="e.g. AI & Robotics Workshop" value="<?php echo htmlspecialchars($editEvent['title']); ?>"></td>
                    </tr>
                    <tr>
                        <td><label><b>Day & Month:</b></label></td>
                        <td>
                            <input type="text" name="day" style="width:30%; display:inline-block;" required placeholder="Day e.g. 15" value="<?php echo htmlspecialchars($editEvent['day']); ?>">
                            <input type="text" name="month" style="width:40%; display:inline-block;" required placeholder="Month e.g. AUG" value="<?php echo htmlspecialchars($editEvent['month']); ?>">
                            <small style="color:#666;">(e.g. 15 AUG)</small>
                        </td>
                    </tr>
                    <tr>
                        <td><label><b>Location:</b></label></td>
                        <td><input type="text" name="location" required placeholder="e.g. Main Auditorium" value="<?php echo htmlspecialchars($editEvent['location']); ?>"></td>
                    </tr>
                    <tr>
                        <td><label><b>Time:</b></label></td>
                        <td><input type="text" name="time" required placeholder="e.g. 10:00 AM" value="<?php echo htmlspecialchars($editEvent['time']); ?>"></td>
                    </tr>
                    <tr>
                        <td><label><b>Description:</b></label></td>
                        <td><textarea name="description" rows="2" required placeholder="Short event description"><?php echo htmlspecialchars($editEvent['description']); ?></textarea></td>
                    </tr>
                    <tr>
                        <td><label><b>Button Text:</b></label></td>
                        <td><input type="text" name="button_text" placeholder="e.g. Register / View Details" value="<?php echo htmlspecialchars($editEvent['button_text']); ?>"></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <button type="submit" class="btn-submit">
                                <?php echo $editMode ? "💾 Update Event" : "➕ Add Event"; ?>
                            </button>
                            <?php if ($editMode): ?>
                                <a href="admin.php#events-section" style="margin-left:15px; color:#666; font-size:15px;">Cancel</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </form>
        </div>

        <!-- LIST OF CURRENT EVENTS -->
        <h3 style="margin-top:25px; margin-bottom:10px;">📋 Live Events in Database</h3>
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="8%">ID</th>
                        <th width="12%">Date</th>
                        <th width="25%">Title</th>
                        <th width="20%">Location & Time</th>
                        <th width="20%">Button</th>
                        <th width="15%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($allEvents) > 0): ?>
                        <?php while ($ev = mysqli_fetch_assoc($allEvents)): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($ev['id']); ?></td>
                                <td><b><?php echo htmlspecialchars($ev['day'] . ' ' . $ev['month']); ?></b></td>
                                <td><?php echo htmlspecialchars($ev['title']); ?></td>
                                <td><?php echo htmlspecialchars($ev['location'] . ' (' . $ev['time'] . ')'); ?></td>
                                <td><span style="background:#e0f2fe; padding:2px 8px; border-radius:4px; font-size:13px;"><?php echo htmlspecialchars($ev['button_text']); ?></span></td>
                                <td>
                                    <a href="admin.php?edit_id=<?php echo $ev['id']; ?>#events-section" class="btn-action btn-edit">Edit</a>
                                    <a href="admin.php?action=delete_event&id=<?php echo $ev['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:15px;">No events found in database.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <hr>

    <!-- Student Management Section -->
    <section>
        <h2>Student Management</h2>
        <p>Registered Students from MySQL database:</p>
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($allStudents) > 0): ?>
                        <?php while ($st = mysqli_fetch_assoc($allStudents)): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($st['id']); ?></td>
                                <td><b><?php echo htmlspecialchars($st['name']); ?></b></td>
                                <td><?php echo htmlspecialchars($st['email']); ?></td>
                                <td><?php echo htmlspecialchars($st['course'] ?: 'General'); ?></td>
                                <td><span style="background:#e0f2fe; color:#0284c7; padding:2px 8px; border-radius:4px; font-weight:bold; font-size:13px;">STUDENT</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:15px;">No students registered yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <hr>

    <!-- Course Management Section -->
    <section>
        <h2>Course Management</h2>
        <ul>
            <li>Add New Course</li>
            <li>Edit Course Details</li>
            <li>Assign Faculty</li>
            <li>Upload Study Materials</li>
            <li>Remove Old Courses</li>
        </ul>
    </section>

    <hr>

    <!-- Announcements Section -->
    <section>
        <h2>Announcements</h2>
        <article>
            <h3>Placement Drive</h3>
            <p>Campus placement registrations are open until 30 August 2026.</p>
        </article>
        <article>
            <h3>Semester Examination</h3>
            <p>Mid-semester examinations will begin from 10 September 2026.</p>
        </article>
        <article>
            <h3>Holiday Notice</h3>
            <p>The college will remain closed on 15 August due to Independence Day.</p>
        </article>
    </section>

    <hr>

    <!-- Recent Feedback Section -->
    <section>
        <h2>Recent Feedback</h2>
        <ul>
            <li>⭐⭐⭐⭐⭐ Excellent Student Portal</li>
            <li>⭐⭐⭐⭐ Need more study materials.</li>
            <li>⭐⭐⭐⭐⭐ Coding competition was well organized.</li>
            <li>⭐⭐⭐⭐ Improve mobile responsiveness.</li>
        </ul>
    </section>

    <hr>

    </main>

    <footer>
        <p>&copy; 2026 Student Hub | Admin Dashboard</p>
    </footer>

</body>
</html>
