
<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$message = "";

// Get user details
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Update profile
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if ($name == "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid name and email.";
    } else {
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ? AND id != ?"
        );
        $check->bind_param("si", $email, $user_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $message = "This email is already registered.";
        } else {
            $update = $conn->prepare(
                "UPDATE users SET name = ?, email = ? WHERE id = ?"
            );
            $update->bind_param("ssi", $name, $email, $user_id);

            if ($update->execute()) {
                $message = "Profile updated successfully!";
                $user['name'] = $name;
                $user['email'] = $email;
                $_SESSION['name'] = $name;
            } else {
                $message = "Unable to update profile.";
            }
        }
    }
}

// Skills count
$skill_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM skills WHERE user_id = ?"
);
$skill_stmt->bind_param("i", $user_id);
$skill_stmt->execute();
$total_skills = $skill_stmt->get_result()->fetch_assoc()['total'];

// Reviews count and average
$review_stmt = $conn->prepare(
    "SELECT COUNT(*) AS total, AVG(rating) AS average
     FROM reviews WHERE reviewee_id = ?"
);
$review_stmt->bind_param("i", $user_id);
$review_stmt->execute();
$review_data = $review_stmt->get_result()->fetch_assoc();

$total_reviews = (int)$review_data['total'];
$average_rating = $review_data['average']
    ? number_format((float)$review_data['average'], 1)
    : "0.0";

// My skills
$my_skills_stmt = $conn->prepare(
    "SELECT skill_name, skill_type, description
     FROM skills WHERE user_id = ?
     ORDER BY id DESC"
);
$my_skills_stmt->bind_param("i", $user_id);
$my_skills_stmt->execute();
$my_skills = $my_skills_stmt->get_result();

// Reviews received
$received_stmt = $conn->prepare(
    "SELECT r.rating, r.review, r.created_at,
            s.skill_name, u.name AS reviewer_name
     FROM reviews r
     JOIN skills s ON r.skill_id = s.id
     JOIN users u ON r.reviewer_id = u.id
     WHERE r.reviewee_id = ?
     ORDER BY r.id DESC"
);
$received_stmt->bind_param("i", $user_id);
$received_stmt->execute();
$received_reviews = $received_stmt->get_result();

$first_letter = strtoupper(substr($user['name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Profile | Skill Swap</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f5f1ff;
    color: #211b43;
}

/* NAVBAR */
.navbar {
    background: linear-gradient(110deg, #5716c5, #1677ed);
    padding: 17px 4%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    box-shadow: 0 4px 18px #39207830;
}

.logo {
    color: white;
    font-size: 25px;
    font-weight: 800;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 28px;
    flex-wrap: wrap;
}

.nav-links a {
    color: white;
    text-decoration: none;
    font-size: 14px;
    font-weight: 650;
}

.nav-links a:hover {
    color: #ffe18a;
}

.nav-avatar {
    width: 38px;
    height: 38px;
    background: white;
    color: #6330c9;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: 800;
}

/* HERO */
.hero {
    background: linear-gradient(110deg, #7622dc, #286cf0);
    padding: 35px 7%;
    display: flex;
    align-items: center;
    gap: 28px;
    color: white;
    min-height: 170px;
}

.avatar {
    width: 105px;
    height: 105px;
    border: 4px solid white;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 47px;
    font-weight: 800;
    flex-shrink: 0;
    background: #ffffff12;
}

.hero h1 {
    font-size: 35px;
    margin-bottom: 7px;
}

.hero p {
    font-size: 17px;
    opacity: .92;
}

/* MAIN */
.container {
    width: 94%;
    max-width: 1150px;
    margin: 25px auto 45px;
}

/* STAT CARDS */
.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border: 1px solid #e6dcff;
    border-radius: 21px;
    padding: 23px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 5px 17px #5632a514;
}

.stat-icon {
    width: 78px;
    height: 78px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
    background: #eee5ff;
    flex-shrink: 0;
}

.stat-card:nth-child(2) .stat-icon {
    background: #fff0dd;
}

.stat-card h2 {
    font-size: 31px;
    color: #251b50;
    margin-bottom: 4px;
}

.stat-card p {
    font-size: 15px;
    font-weight: 600;
}

/* SECTIONS */
.section-card {
    background: white;
    border: 1px solid #e6dcff;
    border-radius: 21px;
    padding: 27px;
    margin-bottom: 22px;
    box-shadow: 0 5px 17px #5632a510;
}

.section-heading {
    display: flex;
    align-items: center;
    gap: 13px;
    font-size: 23px;
    color: #271b53;
    margin-bottom: 22px;
}

.section-heading span {
    font-size: 27px;
}

/* FORM */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.form-group label {
    display: block;
    font-weight: 700;
    margin-bottom: 9px;
}

.form-group input {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #d8cff2;
    border-radius: 11px;
    font-size: 15px;
    background: #faf9ff;
    outline: none;
}

.form-group input:focus {
    border-color: #7645dc;
    box-shadow: 0 0 0 3px #7645dc19;
}

.save-btn {
    width: 100%;
    margin-top: 22px;
    padding: 15px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(100deg, #6817c9, #2379f3);
    color: white;
    font-size: 16px;
    font-weight: 750;
    cursor: pointer;
}

.save-btn:hover {
    opacity: .91;
}

/* SKILL CARDS */
.skills-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.skill-item {
    background: linear-gradient(120deg, #f7f2ff, #f1f5ff);
    border: 1px solid #ded0ff;
    border-radius: 15px;
    padding: 20px;
    display: flex;
    gap: 17px;
    align-items: center;
}

.skill-icon {
    width: 85px;
    height: 85px;
    background: #e7ddff;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 39px;
    flex-shrink: 0;
}

.skill-item h3 {
    color: #211b43;
    font-size: 19px;
    margin-bottom: 8px;
}

.skill-type {
    display: inline-block;
    background: #e4d5ff;
    color: #5923b5;
    padding: 6px 15px;
    border-radius: 25px;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 8px;
}

.skill-item p {
    font-size: 14px;
    line-height: 1.5;
    color: #55506c;
}

/* REVIEWS */
.review-item {
    background: #faf8ff;
    border: 1px solid #e7dfff;
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 14px;
}

.review-item h3 {
    color: #33205e;
    margin-bottom: 8px;
}

.review-item p {
    margin-top: 9px;
    line-height: 1.6;
    color: #625b76;
}

.stars {
    color: #f5ae20;
    font-size: 22px;
    letter-spacing: 2px;
}

.empty {
    text-align: center;
    padding: 30px 15px;
    color: #716986;
    background: #faf8ff;
    border-radius: 14px;
}

.empty-icon {
    font-size: 45px;
    margin-bottom: 10px;
}

/* MESSAGE */
.message {
    padding: 14px 18px;
    background: #d9fbe8;
    color: #147344;
    border-radius: 12px;
    margin-bottom: 20px;
    font-weight: 650;
}

.footer {
    background: linear-gradient(100deg, #6417c8, #286cf0);
    color: white;
    text-align: center;
    padding: 20px;
    font-size: 14px;
}

/* RESPONSIVE */
@media(max-width:750px) {
    .stats {
        grid-template-columns: 1fr;
    }

    .skills-grid,
    .form-grid {
        grid-template-columns: 1fr;
    }

    .hero {
        padding: 30px 5%;
    }

    .hero h1 {
        font-size: 28px;
    }

    .avatar {
        width: 80px;
        height: 80px;
        font-size: 35px;
    }
}

@media(max-width:500px) {
    .hero {
        flex-direction: column;
        text-align: center;
    }

    .nav-links {
        gap: 13px;
        justify-content: center;
    }

    .section-card {
        padding: 19px;
    }

    .skill-item {
        align-items: flex-start;
    }
}
</style>
</head>

<body>

<header class="navbar">
    <div class="logo">⭐ Skill Swap</div>

    <nav class="nav-links">
        <a href="dashboard.php">⌂ Dashboard</a>
        <a href="skills.php">✿ Skills</a>
        <a href="messages.php">● Messages</a>
        <a href="review.php">✦ Reviews</a>
    </nav>

    <div class="nav-avatar">
        <?php echo htmlspecialchars($first_letter); ?>
    </div>
</header>

<section class="hero">
    <div class="avatar">
        <?php echo htmlspecialchars($first_letter); ?>
    </div>

    <div>
        <h1>My Profile</h1>
        <p>Welcome to your Skill Swap profile!</p>
    </div>
</section>

<main class="container">

    <?php if ($message != ""): ?>
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- STATISTICS -->
    <section class="stats">

        <div class="stat-card">
            <div class="stat-icon">🎓</div>
            <div>
                <h2><?php echo (int)$total_skills; ?></h2>
                <p>My Skills</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div>
                <h2><?php echo $total_reviews; ?></h2>
                <p>Reviews Received</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🌟</div>
            <div>
                <h2><?php echo $average_rating; ?></h2>
                <p>Average Rating</p>
            </div>
        </div>

    </section>

    <!-- PERSONAL INFORMATION -->
    <section class="section-card">

        <h2 class="section-heading">
            <span>👤</span> Personal Information
        </h2>

        <form method="POST">

            <div class="form-grid">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name"
                        value="<?php echo htmlspecialchars($user['name']); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email"
                        value="<?php echo htmlspecialchars($user['email']); ?>"
                        required>
                </div>

            </div>

            <button type="submit" name="update_profile" class="save-btn">
                Save Changes →
            </button>

        </form>

    </section>

    <!-- MY SKILLS -->
    <section class="section-card">

        <h2 class="section-heading">
            <span>💡</span> My Skills
        </h2>

        <?php if ($my_skills->num_rows > 0): ?>

            <div class="skills-grid">

            <?php while ($skill = $my_skills->fetch_assoc()): ?>

                <?php
                $skill_name = strtolower($skill['skill_name']);
                $skill_icon = "💡";

                if (strpos($skill_name, "excel") !== false) {
                    $skill_icon = "📊";
                } elseif (strpos($skill_name, "graphic") !== false) {
                    $skill_icon = "🎨";
                } elseif (strpos($skill_name, "python") !== false) {
                    $skill_icon = "🐍";
                } elseif (strpos($skill_name, "web") !== false) {
                    $skill_icon = "💻";
                }
                ?>

                <div class="skill-item">

                    <div class="skill-icon">
                        <?php echo $skill_icon; ?>
                    </div>

                    <div>
                        <h3>
                            <?php echo htmlspecialchars($skill['skill_name']); ?>
                        </h3>

                        <span class="skill-type">
                            <?php echo htmlspecialchars($skill['skill_type']); ?>
                        </span>

                        <p>
                            <?php echo htmlspecialchars($skill['description']); ?>
                        </p>
                    </div>

                </div>

            <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="empty">
                <div class="empty-icon">💡</div>
                You haven't added any skills yet.
            </div>

        <?php endif; ?>

    </section>

    <!-- REVIEWS RECEIVED -->
    <section class="section-card">

        <h2 class="section-heading">
            <span>⭐</span> Reviews Received
        </h2>

        <?php if ($received_reviews->num_rows > 0): ?>

            <?php while ($review = $received_reviews->fetch_assoc()): ?>

                <div class="review-item">

                    <h3>
                        <?php echo htmlspecialchars($review['skill_name']); ?>
                    </h3>

                    <p>
                        From:
                        <strong>
                            <?php echo htmlspecialchars($review['reviewer_name']); ?>
                        </strong>
                    </p>

                    <div class="stars">
                        <?php echo str_repeat("★", (int)$review['rating']); ?>
                        <?php echo str_repeat("☆", 5 - (int)$review['rating']); ?>
                    </div>

                    <p>
                        <?php echo nl2br(htmlspecialchars($review['review'])); ?>
                    </p>

                    <small>
                        <?php echo htmlspecialchars($review['created_at']); ?>
                    </small>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">
                <div class="empty-icon">⭐</div>
                No reviews received yet.
            </div>

        <?php endif; ?>

    </section>

</main>

<footer class="footer">
    © 2026 Skill Swap Platform | Learn • Share • Grow
</footer>

</body>
</html>
