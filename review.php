
<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = (int)$_SESSION['user_id'];
$message = "";
$message_type = "";

// Submit Review
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {

    $skill_id = (int)$_POST['skill_id'];
    $reviewee_id = (int)$_POST['reviewee_id'];
    $rating = (int)$_POST['rating'];
    $review = trim($_POST['review']);

    if ($rating < 1 || $rating > 5 || $review == "") {
        $message = "Please select rating and write your review.";
        $message_type = "error";
    } elseif ($reviewee_id == $current_user) {
        $message = "You cannot review yourself.";
        $message_type = "error";
    } else {
        $check = $conn->prepare("
            SELECT sr.id
            FROM skill_requests sr
            JOIN skills s ON sr.skill_id = s.id
            WHERE sr.requester_id = ?
            AND s.user_id = ?
            AND sr.skill_id = ?
            AND sr.status = 'Accepted'
        ");

        $check->bind_param("iii", $current_user, $reviewee_id, $skill_id);
        $check->execute();
        $accepted = $check->get_result();

        $duplicate = $conn->prepare("
            SELECT id FROM reviews
            WHERE reviewer_id = ? AND skill_id = ?
        ");
        $duplicate->bind_param("ii", $current_user, $skill_id);
        $duplicate->execute();
        $already = $duplicate->get_result();

        if ($accepted->num_rows == 0) {
            $message = "You can review only accepted skill requests.";
            $message_type = "error";
        } elseif ($already->num_rows > 0) {
            $message = "You have already reviewed this skill.";
            $message_type = "error";
        } else {
            $insert = $conn->prepare("
                INSERT INTO reviews
                (reviewer_id, reviewee_id, skill_id, rating, review)
                VALUES (?, ?, ?, ?, ?)
            ");

            $insert->bind_param(
                "iiiis",
                $current_user,
                $reviewee_id,
                $skill_id,
                $rating,
                $review
            );

            if ($insert->execute()) {
                $message = "Your review has been submitted successfully!";
                $message_type = "success";
            } else {
                $message = "Something went wrong. Please try again.";
                $message_type = "error";
            }
        }
    }
}

// Accepted requests not yet reviewed
$sql = "
    SELECT sr.skill_id, s.skill_name,
           s.user_id AS owner_id, u.name AS owner_name
    FROM skill_requests sr
    JOIN skills s ON sr.skill_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE sr.requester_id = ?
    AND sr.status = 'Accepted'
    AND NOT EXISTS (
        SELECT 1 FROM reviews r
        WHERE r.reviewer_id = sr.requester_id
        AND r.skill_id = sr.skill_id
    )
    GROUP BY sr.skill_id
    ORDER BY sr.skill_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_user);
$stmt->execute();
$accepted_skills = $stmt->get_result();

// My submitted reviews
$review_sql = "
    SELECT r.rating, r.review, r.created_at,
           s.skill_name, u.name AS owner_name
    FROM reviews r
    JOIN skills s ON r.skill_id = s.id
    JOIN users u ON r.reviewee_id = u.id
    WHERE r.reviewer_id = ?
    ORDER BY r.id DESC
";

$review_stmt = $conn->prepare($review_sql);
$review_stmt->bind_param("i", $current_user);
$review_stmt->execute();
$my_reviews = $review_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rating & Reviews | Skill Swap</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: #f3f0ff;
    color: #24213b;
    min-height: 100vh;
}

.navbar {
    background: linear-gradient(110deg, #5425b8, #2875dc);
    padding: 19px 7%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    box-shadow: 0 5px 20px #4932a533;
}

.logo {
    color: white;
    font-size: 25px;
    font-weight: 800;
}

.nav-links {
    display: flex;
    gap: 25px;
    flex-wrap: wrap;
}

.nav-links a {
    color: white;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
}

.nav-links a:hover {
    color: #ffe18a;
}

.hero {
    text-align: center;
    padding: 48px 15px 30px;
}

.hero .tag {
    display: inline-block;
    background: #e4d9ff;
    color: #6637c6;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1px;
    margin-bottom: 15px;
}

.hero h1 {
    font-size: 36px;
    color: #30205f;
    margin-bottom: 12px;
}

.hero p {
    color: #77718d;
    font-size: 15px;
}

.container {
    width: 90%;
    max-width: 850px;
    margin: 0 auto 50px;
}

.notice {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 22px;
    font-weight: 600;
}

.success {
    background: #d9fbe8;
    color: #147344;
}

.error {
    background: #ffe0e4;
    color: #b42336;
}

.section-title {
    font-size: 23px;
    color: #30205f;
    margin: 25px 0 18px;
}

.review-card {
    background: white;
    border-radius: 22px;
    padding: 30px;
    margin-bottom: 25px;
    box-shadow: 0 10px 35px #4932a51a;
    border: 1px solid #ebe5ff;
}

.skill-label {
    color: #7852ce;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1px;
    margin-bottom: 10px;
}

.review-card h2 {
    font-size: 25px;
    color: #30205f;
    margin-bottom: 8px;
}

.provider {
    color: #77718d;
    margin-bottom: 25px;
    font-size: 14px;
}

.form-label {
    display: block;
    font-weight: 700;
    margin-bottom: 13px;
    color: #37304f;
}

.rating-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 25px;
}

.rating-options label {
    display: flex;
    align-items: center;
    gap: 13px;
    width: 100%;
    padding: 14px 18px;
    border: 1px solid #e4def4;
    border-radius: 12px;
    cursor: pointer;
    background: #fbfaff;
    transition: 0.2s;
}

.rating-options label:hover {
    background: #f0eaff;
    border-color: #8a62dc;
}

.rating-options input {
    width: 18px;
    height: 18px;
    accent-color: #7041ce;
}

.stars {
    color: #f3ad24;
    font-size: 20px;
    letter-spacing: 3px;
}

.rating-text {
    color: #4d4664;
    font-size: 14px;
    font-weight: 600;
}

textarea {
    width: 100%;
    min-height: 125px;
    border: 1px solid #ded7ef;
    border-radius: 13px;
    padding: 15px;
    font-family: inherit;
    font-size: 14px;
    resize: vertical;
    outline: none;
    margin-bottom: 20px;
    background: #fcfbff;
}

textarea:focus {
    border-color: #7950d2;
    box-shadow: 0 0 0 3px #7950d21a;
}

.submit-btn {
    width: 100%;
    border: none;
    padding: 15px;
    border-radius: 12px;
    color: white;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    background: linear-gradient(110deg, #6736c5, #2875dc);
    box-shadow: 0 7px 18px #6736c533;
}

.submit-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.empty {
    background: white;
    border-radius: 16px;
    padding: 25px;
    text-align: center;
    color: #77718d;
    border: 1px solid #ebe5ff;
}

.submitted-card {
    background: white;
    border-radius: 17px;
    padding: 22px;
    margin-bottom: 15px;
    border: 1px solid #ebe5ff;
    box-shadow: 0 6px 20px #4932a510;
}

.submitted-card h3 {
    color: #30205f;
    margin-bottom: 8px;
}

.submitted-card p {
    color: #69627d;
    margin-top: 8px;
    line-height: 1.6;
}

.submitted-stars {
    color: #f3ad24;
    font-size: 20px;
}

.date {
    font-size: 12px;
    color: #9991aa;
    margin-top: 10px;
}

.footer {
    text-align: center;
    color: #88809d;
    padding: 25px;
    font-size: 13px;
}

@media(max-width:600px) {
    .navbar {
        justify-content: center;
    }
    .hero h1 {
        font-size: 29px;
    }
    .review-card {
        padding: 22px;
    }
}
</style>
</head>

<body>

<header class="navbar">
    <div class="logo">🌟 Skill Swap</div>

    <nav class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="skills.php">Skills</a>
        <a href="messages.php">Messages</a>
        <a href="request.php">Requests</a>
    </nav>
</header>

<section class="hero">
    <div class="tag">🎯 SKILL EXCHANGE COMMUNITY</div>
    <h1>⭐ Rating & Reviews</h1>
    <p>Share your experience and appreciate the people who help you learn.</p>
</section>

<main class="container">

    <?php if ($message != ""): ?>
        <div class="notice <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <h2 class="section-title">✍️ Give Your Review</h2>

    <?php if ($accepted_skills->num_rows > 0): ?>

        <?php while ($skill = $accepted_skills->fetch_assoc()): ?>

        <div class="review-card">

            <div class="skill-label">YOUR LEARNING EXPERIENCE</div>

            <h2><?php echo htmlspecialchars($skill['skill_name']); ?></h2>

            <div class="provider">
                Skill Provider:
                <strong><?php echo htmlspecialchars($skill['owner_name']); ?></strong>
            </div>

            <form method="POST">

                <input type="hidden" name="skill_id"
                       value="<?php echo (int)$skill['skill_id']; ?>">

                <input type="hidden" name="reviewee_id"
                       value="<?php echo (int)$skill['owner_id']; ?>">

                <label class="form-label">Select Your Rating</label>

                <div class="rating-options">
                    <?php
                    $ratings = [
                        5 => "Excellent",
                        4 => "Very Good",
                        3 => "Good",
                        2 => "Average",
                        1 => "Poor"
                    ];
                    foreach ($ratings as $number => $word):
                    ?>
                    <label>
                        <input type="radio" name="rating"
                               value="<?php echo $number; ?>" required>
                        <span class="stars">
                            <?php echo str_repeat("★", $number); ?>
                        </span>
                        <span class="rating-text">
                            <?php echo $word; ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <label class="form-label">Write Your Review</label>

                <textarea name="review"
                    placeholder="Share your learning experience..."
                    required></textarea>

                <button type="submit" name="submit_review"
                        class="submit-btn">
                    Submit Review →
                </button>

            </form>
        </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">
            <h3>🎉 No Pending Reviews</h3>
            <p>You have reviewed all your accepted skill requests.</p>
        </div>

    <?php endif; ?>


    <h2 class="section-title">📋 My Submitted Reviews</h2>

    <?php if ($my_reviews->num_rows > 0): ?>

        <?php while ($row = $my_reviews->fetch_assoc()): ?>

        <div class="submitted-card">

            <h3><?php echo htmlspecialchars($row['skill_name']); ?></h3>

            <div class="provider">
                Reviewed Provider:
                <strong><?php echo htmlspecialchars($row['owner_name']); ?></strong>
            </div>

            <div class="submitted-stars">
                <?php echo str_repeat("★", (int)$row['rating']); ?>
                <span style="color:#aaa;">
                    <?php echo str_repeat("☆", 5 - (int)$row['rating']); ?>
                </span>
            </div>

            <p><?php echo nl2br(htmlspecialchars($row['review'])); ?></p>

            <div class="date">
                <?php echo htmlspecialchars($row['created_at']); ?>
            </div>

        </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="empty">
            You haven't submitted any reviews yet.
        </div>

    <?php endif; ?>

</main>

<footer class="footer">
    © 2026 Skill Swap Platform | Learn • Share • Grow
</footer>

</body>
</html>
