
<?php
session_start();
include __DIR__ . "/../config/db.php";

$message = "";

if (isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ss", $email, $password);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        header("Location: ../pages/dashboard.php");
        exit();

    } else {
        $message = "Invalid Email or Password!";
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Skill Swap Platform</title>

    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>
<body class="login-page">

    <nav class="login-navbar">

        <a href="../index.php" class="login-logo">
            💡 Skill Swap Platform
        </a>

        <a href="../index.php" class="home-link">
            Home
        </a>

    </nav>

    <main class="login-container">

        <div class="login-card">

            <div class="login-icon">👩‍💻</div>

            <h2>Welcome Back!</h2>

            <p class="login-subtitle">
                Login to continue your learning journey.
            </p>

            <?php if ($message != "") { ?>

                <div class="login-error">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php } ?>

            <form method="POST" action="">

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
                    placeholder="Enter your password"
                    required
                >

                <button type="submit" name="login"
                    class="login-submit">
                    Login Now →
                </button>

            </form>

            <p class="register-text">
                Don't have an account?
                <a href="register.php">Create Account</a>
            </p>

            <a href="../index.php" class="back-home">
                ← Back to Home
            </a>

        </div>

    </main>

</body>
</html>
