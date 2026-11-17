
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Skill Swap Platform</title>
    <link rel="stylesheet" href="style.css?v=4">
</head>

<body class="dashboard-page">

    <header class="dashboard-header">
        <h1>🌟 Skill Swap Platform</h1>
        <p>Learn • Share • Grow Together</p>
    </header>

    <main class="dashboard-main">

        <section class="dashboard-welcome">
            <h2>
                Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! 👋
            </h2>
            <p>What would you like to do today?</p>
        </section>

        <section class="dashboard-cards">

            <a href="skills.php" class="dashboard-card">
                <div class="dashboard-icon">🎯</div>
                <h3>Available Skills</h3>
                <p>Explore skills shared by students.</p>
                <span>Explore Now →</span>
            </a>

            <a href="request.php" class="dashboard-card">
                <div class="dashboard-icon">🤝</div>
                <h3>Request a Skill</h3>
                <p>Learn something new from others.</p>
                <span>Request Now →</span>
            </a>

            <a href="messages.php" class="dashboard-card">
                <div class="dashboard-icon">💬</div>
                <h3>Messages</h3>
                <p>Connect and communicate with students.</p>
                <span>Open Messages →</span>
            </a>

            <a href="profile.php" class="dashboard-card">
                <div class="dashboard-icon">👤</div>
                <h3>My Profile</h3>
                <p>View and update your personal information.</p>
                <span>View Profile →</span>
            </a>

            <a href="index.php" class="dashboard-card">
                <div class="dashboard-icon">🏠</div>
                <h3>Home</h3>
                <p>Visit the Skill Swap home page.</p>
                <span>Go Home →</span>
            </a>

        </section>

        <div class="dashboard-logout">
            <a href="logout.php">🚪 Logout</a>
        </div>

    </main>

    <footer class="dashboard-footer">
        <p>© 2026 Skill Swap Platform | Learn. Share. Grow.</p>
    </footer>

</body>
</html>
