<?php
// ==============================================================================
// Practical 13: StudentHub Events (Fetch API & Debounced Search)
// Features:
// 1. Consumes PHP JSON API (api_events.php) using JavaScript Fetch API
// 2. Debounced live search without page reload
// 3. Loading, Success, and Error states
// 4. Exact original UI & events.css styling preserved
// ==============================================================================

require_once "auth.php";

// Fetch initial events from database
$eventsQuery = mysqli_query($conn, "SELECT * FROM `events` ORDER BY `id` ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Events (Practical 13)</title>

    <link rel="stylesheet" href="events.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .search-container {
            width: 90%;
            max-width: 600px;
            margin: 30px auto 10px;
            text-align: center;
        }
        .search-input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #7fb1c8;
            border-radius: 30px;
            font-size: 16px;
            outline: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .search-input:focus {
            border-color: #5265f9;
            box-shadow: 0 4px 12px rgba(82, 101, 249, 0.2);
        }
        .search-status {
            margin-top: 8px;
            font-size: 14px;
            font-weight: bold;
            color: #5265f9;
            display: none;
        }
        .api-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            margin-top: 6px;
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
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <a href="admin.php">Admin</a> |
<?php endif; ?>
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

<!-- Debounced Live Search Bar (Intermediate Extension) -->
<div class="search-container">
    <input type="text" id="liveEventSearch" class="search-input" placeholder="🔍 Live search events (title, location, description)...">
    <div id="searchStatus" class="search-status">⏳ Searching events via JSON API...</div>
</div>

<!-- Dynamic Events Grid -->
<section class="events" id="eventsContainer">

    <?php if (mysqli_num_rows($eventsQuery) > 0): ?>
        <?php while ($ev = mysqli_fetch_assoc($eventsQuery)): ?>
            <div class="event-card">
                <div class="date">
                    <h2><?php echo htmlspecialchars($ev['day']); ?></h2>
                    <span><?php echo htmlspecialchars($ev['month']); ?></span>
                </div>

                <div class="details">
                    <h3><?php echo htmlspecialchars($ev['title']); ?></h3>

                    <p>
                        <i class="fa-solid fa-location-dot"></i>
                        <?php echo htmlspecialchars($ev['location']); ?>
                    </p>

                    <p>
                        <i class="fa-solid fa-clock"></i>
                        <?php echo htmlspecialchars($ev['time']); ?>
                    </p>

                    <p>
                        <?php echo htmlspecialchars($ev['description']); ?>
                    </p>

                    <a href="<?php echo htmlspecialchars($ev['button_link'] ?: '#'); ?>">
                        <?php echo htmlspecialchars($ev['button_text'] ?: 'Register'); ?>
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="grid-column: 1 / -1; text-align:center; padding: 40px; background:white; border-radius:10px;">
            <h3>No events scheduled right now. Check back soon!</h3>
        </div>
    <?php endif; ?>

</section>

<footer>
    <p>© 2026 StudentHub | All Rights Reserved • Practical 13</p>
</footer>

<!-- Fetch API & Debounced Search Integration Script -->
<script>
    const searchInput = document.getElementById('liveEventSearch');
    const searchStatus = document.getElementById('searchStatus');
    const container = document.getElementById('eventsContainer');
    let debounceTimeout = null;

    function escapeHtml(str) {
        if (!str) return '';
        return str.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async function searchEvents(keyword = '') {
        searchStatus.style.display = 'block';
        searchStatus.innerText = '⏳ Searching events via JSON API...';

        try {
            const url = keyword ? `api_events.php?search=${encodeURIComponent(keyword)}` : 'api_events.php';
            const response = await fetch(url);
            const result = await response.json();

            searchStatus.style.display = 'none';

            if (response.ok && result.success) {
                const events = result.data.events || [];
                renderEvents(events);
            } else {
                container.innerHTML = `<div style="grid-column: 1 / -1; text-align:center; padding: 30px; background:white; border-radius:10px; color:red;">
                    Error loading events: ${escapeHtml(result.message)}
                </div>`;
            }
        } catch (error) {
            searchStatus.style.display = 'none';
            container.innerHTML = `<div style="grid-column: 1 / -1; text-align:center; padding: 30px; background:white; border-radius:10px; color:red;">
                Network error: ${escapeHtml(error.message)}
            </div>`;
        }
    }

    function renderEvents(events) {
        if (!events || events.length === 0) {
            container.innerHTML = `<div style="grid-column: 1 / -1; text-align:center; padding: 40px; background:white; border-radius:10px;">
                <h3>🔍 No matching events found.</h3>
                <p style="color:#666; margin-top:6px;">Try searching for another keyword or clear the search input.</p>
            </div>`;
            return;
        }

        let html = '';
        events.forEach(ev => {
            html += `
                <div class="event-card">
                    <div class="date">
                        <h2>${escapeHtml(ev.day)}</h2>
                        <span>${escapeHtml(ev.month)}</span>
                    </div>

                    <div class="details">
                        <h3>${escapeHtml(ev.title)}</h3>

                        <p>
                            <i class="fa-solid fa-location-dot"></i>
                            ${escapeHtml(ev.location)}
                        </p>

                        <p>
                            <i class="fa-solid fa-clock"></i>
                            ${escapeHtml(ev.time)}
                        </p>

                        <p>
                            ${escapeHtml(ev.description)}
                        </p>

                        <a href="${escapeHtml(ev.button_link || '#')}">
                            ${escapeHtml(ev.button_text || 'Register')}
                        </a>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    // Debounce Listener (300ms)
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        clearTimeout(debounceTimeout);

        debounceTimeout = setTimeout(() => {
            searchEvents(query);
        }, 300);
    });
</script>

</body>
</html>
