<?php
// ==============================================================================
// Practical 13: Admin Dashboard with AJAX / Fetch-Based CRUD without Page Reload
// Features Implemented:
// 1. Asynchronous List, Add, Update, and Delete via Fetch API
// 2. Debounced AJAX Search
// 3. Loading, Success, and Error state notifications
// 4. Centralized API response handling & proper HTTP status codes
// 5. Preserves 100% original StudentHub UI and admin.css styling
// ==============================================================================

require_once "auth.php";

// Restrict access: Only users with 'admin' role
require_admin();

// Fetch summary metrics
$totalStudentsQuery = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM students WHERE role = 'student'");
$totalStudents = mysqli_fetch_assoc($totalStudentsQuery)['cnt'] ?? 0;

$totalEventsQuery = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM events");
$totalEvents = mysqli_fetch_assoc($totalEventsQuery)['cnt'] ?? 0;

// Fetch students for student management section
$allStudents = mysqli_query($conn, "SELECT id, name, email, course, role FROM students WHERE role = 'student' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Admin Panel (Practical 13)</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Styles matching original admin layout with AJAX indicators */
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
            cursor: pointer;
            border: none;
        }
        .btn-edit {
            background: #0284c7;
            color: #fff;
        }
        .btn-edit:hover {
            background: #0369a1;
        }
        .btn-delete {
            background: #dc2626;
            color: #fff;
        }
        .btn-delete:hover {
            background: #b91c1c;
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
            transition: all 0.2s ease;
        }
        .btn-submit:hover {
            background: #3e90e3;
            color: #fff;
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .alert-box {
            padding: 12px 18px;
            margin: 15px 0;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            display: none;
            transition: all 0.3s ease;
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
        .loading-state {
            display: inline-block;
            margin-left: 10px;
            font-size: 14px;
            color: #0284c7;
            font-weight: bold;
        }
        .search-box {
            width: 100%;
            max-width: 450px;
            padding: 10px 14px;
            border: 2px solid #2faad6;
            border-radius: 6px;
            font-size: 16px;
            margin-bottom: 12px;
            box-sizing: border-box;
            outline: none;
        }
        .search-box:focus {
            border-color: #0d57ec;
            box-shadow: 0 0 5px rgba(13, 87, 236, 0.3);
        }
        .badge-live {
            background: #e0f2fe;
            color: #0369a1;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: bold;
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
Practical 13: PHP JSON API and AJAX/Fetch-Based CRUD without Page Reload • Live Debounced Search • Real-Time Asynchronous Updates
</marquee>

</header>
<body>

   <main>
    <section>
        <h1>🛠 Student Hub - Admin Panel</h1>
        <p>Welcome, Administrator (<b><?php echo htmlspecialchars($_SESSION['user_name']); ?></b>)! Manage students, events, courses, and announcements with <b>Fetch API & AJAX CRUD (No Page Reload)</b>.</p>
    </section>

    <!-- Overview Section -->
    <section>
        <h2>Dashboard Overview</h2>
        <p><strong>Total Students in Database:</strong> <?php echo $totalStudents; ?></p>
        <p><strong>Total Events Scheduled:</strong> <span id="eventCountBadge"><?php echo $totalEvents; ?></span></p>
        <p><strong>Faculty Members:</strong> 85</p>
        <p><strong>Courses Available:</strong> 24</p>
        
    </section>

    <hr>

    <!-- EVENT MANAGEMENT CRUD SECTION VIA FETCH API -->
    <section id="events-section">
        <h2>🎉 Event Management</h2>
        <p>
            Add, update, or delete events <b>asynchronously without page reload</b>. Changes are saved to MySQL via the PHP JSON API 
            and reflected immediately on the student's 
            <a href="events.php" target="_blank" style="color:#0284c7; font-weight:bold;">Events Page (events.php)</a>.
        </p>

        <!-- Notification Alert for AJAX operations -->
        <div id="ajaxAlert" class="alert-box"></div>

        <!-- ADD / EDIT EVENT FORM (FETCH API POWERED) -->
        <div style="width:100%; max-width:800px; margin:20px 0; padding:20px; background:#f9fbff; border-left:5px solid #2faad6; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 id="formTitle" style="margin-bottom:12px; color:#2f5f80e1;">➕ Add New Event (via Fetch API)</h3>
                <span id="formLoadingState" class="loading-state" style="display:none;">⏳ Processing...</span>
            </div>

            <form id="eventAjaxForm">
                <input type="hidden" id="formAction" name="action" value="create">
                <input type="hidden" id="eventId" name="id" value="">

                <table class="event-form-table">
                    <tr>
                        <td width="25%"><label><b>Event Title:</b></label></td>
                        <td><input type="text" id="eventTitle" name="title" required placeholder="e.g. AI & Robotics Workshop"></td>
                    </tr>
                    <tr>
                        <td><label><b>Day & Month:</b></label></td>
                        <td>
                            <input type="text" id="eventDay" name="day" style="width:30%; display:inline-block;" required placeholder="Day e.g. 15">
                            <input type="text" id="eventMonth" name="month" style="width:40%; display:inline-block;" required placeholder="Month e.g. AUG">
                            <small style="color:#666;">(e.g. 15 AUG)</small>
                        </td>
                    </tr>
                    <tr>
                        <td><label><b>Location:</b></label></td>
                        <td><input type="text" id="eventLocation" name="location" required placeholder="e.g. Main Auditorium"></td>
                    </tr>
                    <tr>
                        <td><label><b>Time:</b></label></td>
                        <td><input type="text" id="eventTime" name="time" required placeholder="e.g. 10:00 AM"></td>
                    </tr>
                    <tr>
                        <td><label><b>Description:</b></label></td>
                        <td><textarea id="eventDescription" name="description" rows="2" required placeholder="Short event description"></textarea></td>
                    </tr>
                    <tr>
                        <td><label><b>Button Text:</b></label></td>
                        <td><input type="text" id="eventButtonText" name="button_text" placeholder="e.g. Register / View Details" value="Register"></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <button type="submit" id="btnSubmitEvent" class="btn-submit">➕ Add Event</button>
                            <button type="button" id="btnCancelEdit" style="display:none; margin-left:15px; padding:9px 18px; border-radius:5px; background:#94a3b8; color:#fff; border:none; cursor:pointer; font-weight:bold;">Cancel Edit</button>
                        </td>
                    </tr>
                </table>
            </form>
        </div>

        <!-- LIVE SEARCH WITH DEBOUNCE (INTERMEDIATE EXTENSION) -->
        <div style="margin-top:25px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <h3>📋 Live Events in Database</h3>
            <div>
                <input type="text" id="eventSearchInput" class="search-box" placeholder="🔍 Search events (debounced 300ms)...">
                <span id="searchLoadingState" class="loading-state" style="display:none;">⏳ Searching...</span>
            </div>
        </div>

        <!-- TABLE POPULATED ASYNCHRONOUSLY WITHOUT PAGE RELOAD -->
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="8%">ID</th>
                        <th width="12%">Date</th>
                        <th width="25%">Title</th>
                        <th width="20%">Location & Time</th>
                        <th width="15%">Button</th>
                        <th width="20%">Actions (AJAX)</th>
                    </tr>
                </thead>
                <tbody id="eventsTableBody">
                    <tr>
                        <td colspan="6" style="text-align:center; padding:15px;">⏳ Loading events from JSON API...</td>
                    </tr>
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
        <p>&copy; 2026 Student Hub | Admin Dashboard • Practical 13</p>
    </footer>

    <!-- ======================================================================= -->
    <!-- JAVASCRIPT FETCH API CRUD & DEBOUNCED SEARCH (PRACTICAL 13 IMPLEMENTATION) -->
    <!-- ======================================================================= -->
    <script>
        const API_URL = 'api_events.php';
        let cachedEvents = [];
        let debounceTimer = null;

        const tableBody = document.getElementById('eventsTableBody');
        const alertBox = document.getElementById('ajaxAlert');
        const form = document.getElementById('eventAjaxForm');
        const formTitle = document.getElementById('formTitle');
        const formAction = document.getElementById('formAction');
        const eventIdInput = document.getElementById('eventId');
        const btnSubmit = document.getElementById('btnSubmitEvent');
        const btnCancel = document.getElementById('btnCancelEdit');
        const formLoading = document.getElementById('formLoadingState');
        const searchInput = document.getElementById('eventSearchInput');
        const searchLoading = document.getElementById('searchLoadingState');
        const countBadge = document.getElementById('eventCountBadge');

        // Helper: Display Alert notification with automatic fade out
        function showAlert(message, type = 'success', duration = 4000) {
            alertBox.className = 'alert-box ' + (type === 'error' ? 'alert-error' : 'alert-success');
            alertBox.innerHTML = (type === 'error' ? '⚠️ ' : '✅ ') + message;
            alertBox.style.display = 'block';

            if (duration > 0) {
                setTimeout(() => {
                    alertBox.style.display = 'none';
                }, duration);
            }
        }

        // Helper: Escape HTML string to prevent XSS
        function escapeHtml(str) {
            if (!str) return '';
            return str.toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // ---------------------------------------------------------------------
        // 1. FETCH EVENTS (LIST / READ) WITHOUT PAGE RELOAD
        // ---------------------------------------------------------------------
        async function loadEvents(search = '') {
            try {
                if (search) {
                    searchLoading.style.display = 'inline-block';
                }

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
                    tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:red; padding:15px;">Failed to load events: ${escapeHtml(result.message)}</td></tr>`;
                }
            } catch (error) {
                searchLoading.style.display = 'none';
                tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:red; padding:15px;">Network error loading events.</td></tr>`;
                showAlert('Network error: ' + error.message, 'error');
            }
        }

        // Render Table rows dynamically
        function renderEventsTable(events) {
            if (!events || events.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:15px;">No events found in database.</td></tr>`;
                return;
            }

            let html = '';
            events.forEach(ev => {
                html += `
                    <tr id="event-row-${ev.id}">
                        <td>#${escapeHtml(ev.id)}</td>
                        <td><b>${escapeHtml(ev.day)} ${escapeHtml(ev.month)}</b></td>
                        <td>${escapeHtml(ev.title)}</td>
                        <td>${escapeHtml(ev.location)} (${escapeHtml(ev.time)})</td>
                        <td><span class="badge-live">${escapeHtml(ev.button_text || 'Register')}</span></td>
                        <td>
                            <button type="button" class="btn-action btn-edit" onclick="onEditEvent(${ev.id})">✏️ Edit</button>
                            <button type="button" class="btn-action btn-delete" onclick="onDeleteEvent(${ev.id})">🗑️ Delete</button>
                        </td>
                    </tr>
                `;
            });
            tableBody.innerHTML = html;
        }

        // ---------------------------------------------------------------------
        // 2. CREATE & UPDATE EVENT (POST / PUT) WITHOUT PAGE RELOAD
        // ---------------------------------------------------------------------
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Input validation before sending
            const title = document.getElementById('eventTitle').value.trim();
            const day = document.getElementById('eventDay').value.trim();
            const month = document.getElementById('eventMonth').value.trim();
            const location = document.getElementById('eventLocation').value.trim();
            const time = document.getElementById('eventTime').value.trim();
            const description = document.getElementById('eventDescription').value.trim();
            const button_text = document.getElementById('eventButtonText').value.trim() || 'Register';
            const action = formAction.value;
            const id = eventIdInput.value;

            if (!title || !day || !month || !location || !time || !description) {
                showAlert('Please fill in all required fields.', 'error');
                return;
            }

            // Set loading state
            btnSubmit.disabled = true;
            formLoading.style.display = 'inline-block';

            const payload = {
                action: action,
                id: id,
                title: title,
                day: day,
                month: month,
                location: location,
                time: time,
                description: description,
                button_text: button_text,
                button_link: '#'
            };

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                btnSubmit.disabled = false;
                formLoading.style.display = 'none';

                if (response.ok && result.success) {
                    showAlert(result.message, 'success');
                    resetForm();
                    // Reload events asynchronously without page reload
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

        // ---------------------------------------------------------------------
        // 3. EDIT EVENT (Populate form asynchronously)
        // ---------------------------------------------------------------------
        window.onEditEvent = function(id) {
            const ev = cachedEvents.find(e => parseInt(e.id) === parseInt(id));
            if (!ev) {
                showAlert('Event data not found.', 'error');
                return;
            }

            // Fill form fields
            eventIdInput.value = ev.id;
            document.getElementById('eventTitle').value = ev.title;
            document.getElementById('eventDay').value = ev.day;
            document.getElementById('eventMonth').value = ev.month;
            document.getElementById('eventLocation').value = ev.location;
            document.getElementById('eventTime').value = ev.time;
            document.getElementById('eventDescription').value = ev.description;
            document.getElementById('eventButtonText').value = ev.button_text || 'Register';

            // Switch to edit mode
            formAction.value = 'update';
            formTitle.innerHTML = `✏️ Edit Event (#${ev.id}) (via Fetch API)`;
            btnSubmit.innerHTML = '💾 Update Event';
            btnCancel.style.display = 'inline-block';

            // Scroll to form smoothly
            document.getElementById('events-section').scrollIntoView({ behavior: 'smooth' });
        };

        // Cancel Edit button
        btnCancel.addEventListener('click', resetForm);

        function resetForm() {
            form.reset();
            formAction.value = 'create';
            eventIdInput.value = '';
            document.getElementById('eventButtonText').value = 'Register';
            formTitle.innerHTML = '➕ Add New Event (via Fetch API)';
            btnSubmit.innerHTML = '➕ Add Event';
            btnCancel.style.display = 'none';
        }

        // ---------------------------------------------------------------------
        // 4. DELETE EVENT (DELETE / AJAX) WITHOUT PAGE RELOAD
        // ---------------------------------------------------------------------
        window.onDeleteEvent = async function(id) {
            if (!confirm(`Are you sure you want to delete event #${id}? This will remove it immediately.`)) {
                return;
            }

            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        id: id
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showAlert(result.message, 'success');
                    // Remove row from table directly
                    const row = document.getElementById(`event-row-${id}`);
                    if (row) {
                        row.style.transition = 'all 0.4s ease';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            // Update count badge
                            if (countBadge) {
                                countBadge.innerText = Math.max(0, parseInt(countBadge.innerText || 1) - 1);
                            }
                        }, 400);
                    }
                } else {
                    showAlert(result.message || 'Failed to delete event.', 'error');
                }
            } catch (error) {
                showAlert('Network error: ' + error.message, 'error');
            }
        };

        // ---------------------------------------------------------------------
        // 5. INTERMEDIATE EXTENSION: AJAX SEARCH WITH DEBOUNCE (300ms)
        // ---------------------------------------------------------------------
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.trim();
            clearTimeout(debounceTimer);

            debounceTimer = setTimeout(() => {
                loadEvents(query);
            }, 300); // 300ms debounce
        });

        // Initial Load on page ready
        document.addEventListener('DOMContentLoaded', () => {
            loadEvents();
        });
    </script>
</body>
</html>
