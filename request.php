
<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = (int)$_SESSION['user_id'];
$message = "";
$skill_id = (int)($_GET['skill_id'] ?? $_POST['skill_id'] ?? 0);

// SEND SKILL REQUEST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_request'])) {

    $skill_id = (int)$_POST['skill_id'];
    $request_message = trim($_POST['message']);

    $stmt = mysqli_prepare($conn, "SELECT id, user_id FROM skills WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $skill_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $skill = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$skill) {
        $message = "Skill not found!";
    } elseif ((int)$skill['user_id'] === $current_user) {
        $message = "You cannot request your own skill!";
    } elseif ($request_message == "") {
        $message = "Please write a message!";
    } else {

        $sql = "INSERT INTO skill_requests
                (requester_id, skill_id, message, status)
                VALUES (?, ?, ?, 'Pending')";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $current_user, $skill_id, $request_message);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: request.php?skill_id=" . $skill_id . "&status=sent");
            exit();
        } else {
            $message = "Error sending request!";
        }

        mysqli_stmt_close($stmt);
    }
}

// ACCEPT OR REJECT REQUEST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_request'])) {

    $request_id = (int)$_POST['request_id'];
    $new_status = $_POST['new_status'] ?? '';

    if (in_array($new_status, ['Accepted', 'Rejected'], true)) {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT sr.id
             FROM skill_requests sr
             JOIN skills s ON sr.skill_id = s.id
             WHERE sr.id = ? AND s.user_id = ? AND sr.status = 'Pending'"
        );

        mysqli_stmt_bind_param($stmt, "ii", $request_id, $current_user);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $valid_request = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($valid_request) {

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE skill_requests SET status=?
                 WHERE id=? AND status='Pending'"
            );

            mysqli_stmt_bind_param($stmt, "si", $new_status, $request_id);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: request.php?status=updated");
                exit();
            }

            mysqli_stmt_close($stmt);

        } else {
            $message = "Request not found or already updated!";
        }

    } else {
        $message = "Invalid request status!";
    }
}

// STATUS MESSAGE
if (isset($_GET['status'])) {
    if ($_GET['status'] == "sent") {
        $message = "Skill Request Sent Successfully! ✅";
    } elseif ($_GET['status'] == "updated") {
        $message = "Request Status Updated Successfully! ✅";
    }
}

// SELECTED SKILL DETAILS
$selected_skill = null;

if ($skill_id > 0) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT skills.*, users.name
         FROM skills
         JOIN users ON skills.user_id = users.id
         WHERE skills.id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $skill_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $selected_skill = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// INCOMING REQUESTS
$sql = "SELECT sr.id, sr.message, sr.status, sr.requester_id,
               sr.skill_id, s.skill_name, u.name AS requester_name
        FROM skill_requests sr
        JOIN skills s ON sr.skill_id = s.id
        JOIN users u ON sr.requester_id = u.id
        WHERE s.user_id = ?
        ORDER BY sr.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $current_user);
mysqli_stmt_execute($stmt);
$incoming_requests = mysqli_stmt_get_result($stmt);

// MY SENT REQUESTS
$sql = "SELECT sr.id, sr.message, sr.status,
               s.skill_name, u.name AS owner_name
        FROM skill_requests sr
        JOIN skills s ON sr.skill_id = s.id
        JOIN users u ON s.user_id = u.id
        WHERE sr.requester_id = ?
        ORDER BY sr.id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $current_user);
mysqli_stmt_execute($stmt);
$sent_requests = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Skill Requests - Skill Swap</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .request-container {
            max-width: 750px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(80,50,150,0.12);
        }

        .request-container h2,
        .section-title {
            text-align: center;
            color: #6035cf;
        }

        .request-container input,
        .request-container textarea {
            width: 100%;
            padding: 13px;
            margin: 12px 0 20px;
            border: 1px solid #d8ccff;
            border-radius: 9px;
            box-sizing: border-box;
        }

        .request-card {
            background: white;
            max-width: 750px;
            margin: 20px auto;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(80,50,150,0.12);
        }

        .request-card h3 {
            color: #6035cf;
        }

        .status {
            font-weight: bold;
            color: #6035cf;
        }

        .accept-btn {
            background: #16a36a;
        }

        .reject-btn {
            background: #e74c65;
        }

        .status-message {
            text-align: center;
            color: green;
            font-weight: bold;
            font-size: 18px;
        }

        .navigation {
            text-align: center;
            margin: 30px;
        }

        .request-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .empty-message {
            text-align: center;
            color: #666;
            margin: 25px;
        }
    </style>
</head>

<body>

<h1 style="text-align:center;">🌟 Skill Swap Platform</h1>

<?php if ($message != "") { ?>
    <p class="status-message">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php } ?>


<!-- SEND REQUEST FORM -->

<?php if ($selected_skill && (int)$selected_skill['user_id'] !== $current_user) { ?>

    <div class="request-container">

        <h2>🤝 Request a Skill</h2>

        <h3>
            Skill: <?php echo htmlspecialchars($selected_skill['skill_name']); ?>
        </h3>

        <p>
            Offered by:
            <?php echo htmlspecialchars($selected_skill['name']); ?>
        </p>

        <form method="POST">

            <input type="hidden" name="skill_id"
                   value="<?php echo $skill_id; ?>">

            <label><b>Write your message:</b></label>

            <textarea name="message" rows="5"
                placeholder="Example: I want to learn this skill from you..."
                required></textarea>

            <button type="submit" name="send_request">
                📩 Send Request
            </button>

        </form>

    </div>

<?php } elseif ($skill_id > 0 && !$selected_skill) { ?>

    <p class="status-message">Skill not found!</p>

<?php } ?>


<!-- MY SENT REQUESTS -->

<h2 class="section-title">📤 My Sent Requests</h2>

<?php if (mysqli_num_rows($sent_requests) > 0) { ?>

    <?php while ($row = mysqli_fetch_assoc($sent_requests)) { ?>

        <div class="request-card">

            <h3>🎯 <?php echo htmlspecialchars($row['skill_name']); ?></h3>

            <p>
                <b>Offered by:</b>
                <?php echo htmlspecialchars($row['owner_name']); ?>
            </p>

            <p>
                <b>Your Message:</b>
                <?php echo htmlspecialchars($row['message']); ?>
            </p>

            <p class="status">
                Status: <?php echo htmlspecialchars($row['status']); ?>
            </p>

        </div>

    <?php } ?>

<?php } else { ?>

    <p class="empty-message">You have not sent any requests yet.</p>

<?php } ?>


<!-- RECEIVED REQUESTS -->

<h2 class="section-title">📩 Requests Received for Your Skills</h2>

<?php if (mysqli_num_rows($incoming_requests) > 0) { ?>

    <?php while ($row = mysqli_fetch_assoc($incoming_requests)) { ?>

        <div class="request-card">

            <h3>🎯 <?php echo htmlspecialchars($row['skill_name']); ?></h3>

            <p>
                <b>Requested by:</b>
                <?php echo htmlspecialchars($row['requester_name']); ?>
            </p>

            <p>
                <b>Message:</b>
                <?php echo htmlspecialchars($row['message']); ?>
            </p>

            <p class="status">
                Status: <?php echo htmlspecialchars($row['status']); ?>
            </p>

            <?php if ($row['status'] == "Pending") { ?>

                <form method="POST" class="request-actions">

                    <input type="hidden" name="request_id"
                           value="<?php echo $row['id']; ?>">

                    <input type="hidden" name="new_status" value="">

                    <button type="submit" name="update_request"
                        value="1" class="accept-btn"
                        onclick="this.form.new_status.value='Accepted'">
                        ✅ Accept
                    </button>

                    <button type="submit" name="update_request"
                        value="1" class="reject-btn"
                        onclick="this.form.new_status.value='Rejected'">
                        ❌ Reject
                    </button>

                </form>

            <?php } ?>

        </div>

    <?php } ?>

<?php } else { ?>

    <p class="empty-message">No requests received yet.</p>

<?php } ?>


<!-- NAVIGATION -->

<div class="navigation">

    <a href="skills.php">
        <button>🎯 Back to Skills</button>
    </a>

    <a href="dashboard.php">
        <button>📊 Dashboard</button>
    </a>

    <a href="messages.php">
        <button>💬 Messages</button>
    </a>

</div>

</body>
</html>
