<?php
// ==============================================================================
// Practical-10: StudentHub Events (Dynamic MySQL Driven)
// Exactly preserves original UI and dynamically loads events from database
// ==============================================================================

require_once "auth.php";

// Fetch all events from database
$eventsQuery = mysqli_query($conn, "SELECT * FROM `events` ORDER BY `id` ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub | Events</title>

    <link rel="stylesheet" href="events.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

<section class="events">

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
    <p>© 2026 StudentHub | All Rights Reserved</p>
</footer>

</body>
</html>
