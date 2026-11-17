
<?php
include "db.php";

$message = "";
$message_type = "";

if (isset($_POST['register'])) {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $college = trim($_POST['college']);

    $sql = "INSERT INTO users (name, email, password, college)
            VALUES (?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {

        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $password, $college);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Account created successfully! You can now login.";
            $message_type = "success";
        } else {
            if (mysqli_stmt_errno($stmt) == 1062) {
                $message = "This email is already registered. Please login.";
            } else {
                $message = "Something went wrong. Please try again.";
            }
            $message_type = "error";
        }

        mysqli_stmt_close($stmt);

    } else {
        $message = "Database error. Please try again.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Skill Swap Platform</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="register-page">

    <div class="register-wrapper">

        <section class="register-welcome">

            <div class="register-brand">🌟 Skill Swap</div>

            <div class="register-illustration">👩‍🎓</div>

            <h1>Learn. Share. Grow.</h1>

            <p>
                Join a community where students teach,
                learn and grow together by sharing their skills.
            </p>

            <div class="register-points">
                <div>✨ Share your knowledge</div>
                <div>🤝 Connect with students</div>
                <div>🚀 Learn new skills</div>
            </div>

            <div class="register-bottom">
                Your skills can make a difference!
            </div>

        </section>

        <section class="register-form-section">

            <div class="register-card">

                <div class="register-icon">🎓</div>

                <h2>Create Your Account</h2>

                <p class="register-description">
                    Start your skill-sharing journey today!
                </p>

                <?php if ($message != "") { ?>
                    <div class="register-message <?php echo $message_type; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php } ?>

                <form method="POST" action="">

                    <label>Full Name</label>
                    <input
                        type="text"
                        name="name"
                        placeholder="Enter your full name"
                        required
                    >

                    <label>Email Address</label>
                    <input
                        type="email"
                        name="email"
                        placeholder="Enter your email"
                        required
                    >

                    <label>Password</label>
                    <input
                        type="password"
                        name="password"
                        placeholder="Create a password"
                        required
                    >

                    <label>College Name</label>
                    <input
                        type="text"
                        name="college"
                        placeholder="Enter your college name"
                        required
                    >

                    <button type="submit" name="register" class="register-submit">
                        Create Account →
                    </button>

                </form>

                <p class="register-login">
                    Already have an account?
                    <a href="login.php">Login Here</a>
                </p>

                <a href="index.php" class="register-home">← Back to Home</a>

            </div>

        </section>

    </div>

</body>
</html>
