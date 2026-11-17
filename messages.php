
<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['user_id'];
$status = "";
$receiver_id = isset($_POST['receiver_id'])
    ? (int)$_POST['receiver_id']
    : (isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0);

// Get other users
$users = mysqli_query(
    $conn,
    "SELECT id, name FROM users WHERE id != $current_user"
);

$user_list = [];
while ($u = mysqli_fetch_assoc($users)) {
    $user_list[] = $u;
}

// Select first user if no receiver selected
if ($receiver_id == 0 && count($user_list) > 0) {
    $receiver_id = (int)$user_list[0]['id'];
}

// Send message
if (isset($_POST['send_message'])) {

    $msg = trim($_POST['message']);

    if ($msg != "" && $receiver_id != $current_user) {

        $sql = "INSERT INTO messages (sender_id, receiver_id, message)
                VALUES (?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stmt, "iis", $current_user, $receiver_id, $msg
        );

        if (mysqli_stmt_execute($stmt)) {
            $status = "Message Sent Successfully! ✅";
        } else {
            $status = "Message could not be sent.";
        }

        mysqli_stmt_close($stmt);
    }
}

// Get conversation messages
$chat_messages = [];

if ($receiver_id > 0) {

    $sql = "SELECT m.message, m.sender_id, u.name AS sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.id ASC";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt, "iiii",
        $current_user, $receiver_id,
        $receiver_id, $current_user
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $chat_messages[] = $row;
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Skill Swap</title>
    <link rel="stylesheet" href="style.css?v=4">
</head>

<body>

<h1 style="text-align:center;">🌟 Skill Swap Platform</h1>
<h2>💬 Messages</h2>

<div class="message-container">

    <?php if ($status != "") { ?>
        <p class="message-status">
            <?php echo htmlspecialchars($status); ?>
        </p>
    <?php } ?>

    <?php if (count($user_list) > 0) { ?>

        <form method="POST" class="message-form">

            <label>Select Student:</label>

            <select name="receiver_id" onchange="this.form.submit()">
                <?php foreach ($user_list as $u) { ?>
                    <option value="<?php echo $u['id']; ?>"
                        <?php if ($receiver_id == $u['id']) echo "selected"; ?>>
                        <?php echo htmlspecialchars($u['name']); ?>
                    </option>
                <?php } ?>
            </select>

            <label>Previous Messages:</label>

            <div class="chat-box">

                <?php if (count($chat_messages) > 0) { ?>

                    <?php foreach ($chat_messages as $chat) { ?>

                        <div class="<?php echo ($chat['sender_id'] == $current_user) ? 'my-message' : 'other-message'; ?>">

                            <b><?php echo htmlspecialchars($chat['sender_name']); ?></b>

                            <p><?php echo nl2br(htmlspecialchars($chat['message'])); ?></p>

                        </div>

                    <?php } ?>

                <?php } else { ?>

                    <p>No messages yet. Start a conversation!</p>

                <?php } ?>

            </div>

            <label>Write your message:</label>

            <textarea name="message" rows="4"
                placeholder="Write your message here..."
                required></textarea>

            <button type="submit" name="send_message">
                📩 Send Message
            </button>

        </form>

    <?php } else { ?>

        <p>No other students registered yet.</p>

    <?php } ?>

    <div style="text-align:center;">
        <a href="dashboard.php"><button>📊 Dashboard</button></a>
        <a href="skills.php"><button>🎯 Skills</button></a>
    </div>

</div>

</body>
</html>
