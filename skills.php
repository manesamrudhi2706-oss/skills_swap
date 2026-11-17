
<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = (int)$_SESSION['user_id'];
$status = "";

// ADD, EDIT AND DELETE SKILLS
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ADD SKILL
    if (isset($_POST['add_skill'])) {

        $skill_name = trim($_POST['skill_name']);
        $skill_type = trim($_POST['skill_type']);
        $description = trim($_POST['description']);

        if ($skill_name != "" && $description != "") {

            $sql = "INSERT INTO skills
                    (user_id, skill_name, skill_type, description)
                    VALUES (?, ?, ?, ?)";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "isss",
                $current_user,
                $skill_name,
                $skill_type,
                $description
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: skills.php?status=added");
                exit();
            }

            mysqli_stmt_close($stmt);
        }
    }

    // EDIT SKILL
    if (isset($_POST['edit_skill'])) {

        $id = (int)$_POST['skill_id'];
        $skill_name = trim($_POST['skill_name']);
        $skill_type = trim($_POST['skill_type']);
        $description = trim($_POST['description']);

        $sql = "UPDATE skills
                SET skill_name=?, skill_type=?, description=?
                WHERE id=? AND user_id=?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "sssii",
            $skill_name,
            $skill_type,
            $description,
            $id,
            $current_user
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: skills.php?status=updated");
            exit();
        }

        mysqli_stmt_close($stmt);
    }

    // DELETE SKILL
    if (isset($_POST['delete_skill'])) {

        $id = (int)$_POST['skill_id'];

        $sql = "DELETE FROM skills
                WHERE id=? AND user_id=?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $id,
            $current_user
        );

        if (mysqli_stmt_execute($stmt)) {
            header("Location: skills.php?status=deleted");
            exit();
        }

        mysqli_stmt_close($stmt);
    }
}

// STATUS MESSAGE
if (isset($_GET['status'])) {

    if ($_GET['status'] == "added") {
        $status = "Skill Added Successfully!";
    } elseif ($_GET['status'] == "updated") {
        $status = "Skill Updated Successfully!";
    } elseif ($_GET['status'] == "deleted") {
        $status = "Skill Deleted Successfully!";
    }
}

// EDIT FORM DATA
$edit_skill = null;

if (isset($_GET['edit'])) {

    $id = (int)$_GET['edit'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM skills WHERE id=? AND user_id=?"
    );

    mysqli_stmt_bind_param($stmt, "ii", $id, $current_user);
    mysqli_stmt_execute($stmt);

    $result_edit = mysqli_stmt_get_result($stmt);
    $edit_skill = mysqli_fetch_assoc($result_edit);

    mysqli_stmt_close($stmt);
}

// SEARCH SKILLS
$search = trim($_GET['search'] ?? '');

$sql = "SELECT skills.*, users.name
        FROM skills
        JOIN users ON skills.user_id = users.id
        WHERE skills.skill_name LIKE ?
        OR skills.skill_type LIKE ?
        OR skills.description LIKE ?
        OR users.name LIKE ?
        ORDER BY skills.id DESC";

$stmt = mysqli_prepare($conn, $sql);

$search_value = "%" . $search . "%";

mysqli_stmt_bind_param(
    $stmt,
    "ssss",
    $search_value,
    $search_value,
    $search_value,
    $search_value
);

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Skills - Skill Swap Platform</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .skills-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 22px;
            margin: 30px auto;
            max-width: 1400px;
        }

        .skill-card {
            background: white;
            width: 280px;
            padding: 22px;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(80,50,150,0.12);
            text-align: center;
        }

        .skill-card h3 {
            color: #6035cf;
        }

        .skill-card p {
            text-align: left;
            color: #444;
            font-size: 14px;
        }

        .skill-form {
            background: white;
            max-width: 650px;
            margin: 30px auto;
            padding: 28px;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(80,50,150,0.12);
        }

        .skill-form input,
        .skill-form textarea,
        .skill-form select {
            width: 100%;
            padding: 13px;
            margin: 10px 0 18px;
            border: 1px solid #d8ccff;
            border-radius: 9px;
            box-sizing: border-box;
        }

        .skill-form label {
            font-weight: bold;
            color: #40306f;
        }

        .edit-btn {
            background: #ed9c26;
        }

        .delete-btn {
            background: #e74c65;
        }

        .status-message {
            text-align: center;
            color: green;
            font-weight: bold;
            font-size: 18px;
        }

        .owner-label {
            color: #15945b;
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* SEARCH BOX */

        .search-box {
            max-width: 650px;
            margin: 25px auto;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(80,50,150,0.12);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-box input {
            flex: 1;
            min-width: 180px;
            padding: 13px;
            border: 1px solid #d8ccff;
            border-radius: 9px;
            font-size: 15px;
        }

        .search-box button {
            padding: 12px 22px;
            border: none;
            border-radius: 9px;
            background: linear-gradient(135deg, #7654e8, #3678eb);
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .clear-search {
            display: flex;
            align-items: center;
            color: #6035cf;
            font-weight: bold;
            text-decoration: none;
            padding: 10px;
        }

        .no-result {
            text-align: center;
            color: #6035cf;
            font-size: 18px;
            font-weight: bold;
            width: 100%;
        }

        /* DELETE FORM DESIGN */

        .skill-card form {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
            width: auto !important;
        }

    </style>

</head>

<body>

<h1 style="text-align:center;">🌟 Skill Swap Platform</h1>

<h2 style="text-align:center;">🎯 Available Skills</h2>

<?php if ($status != "") { ?>

    <p class="status-message">
        <?php echo htmlspecialchars($status); ?>
    </p>

<?php } ?>


<!-- ADD / EDIT SKILL FORM -->

<div class="skill-form">

    <h2>
        <?php echo $edit_skill ? "✏️ Edit Your Skill" : "➕ Add New Skill"; ?>
    </h2>

    <form method="POST">

        <?php if ($edit_skill) { ?>

            <input type="hidden" name="skill_id"
                value="<?php echo $edit_skill['id']; ?>">

        <?php } ?>

        <label>Skill Name:</label>

        <input type="text"
            name="skill_name"
            placeholder="Example: Python"
            value="<?php echo htmlspecialchars($edit_skill['skill_name'] ?? ''); ?>"
            required>

        <label>Skill Type:</label>

        <select name="skill_type" required>

            <option value="Technical"
                <?php if (($edit_skill['skill_type'] ?? '') == 'Technical') echo 'selected'; ?>>
                Technical
            </option>

            <option value="Creative"
                <?php if (($edit_skill['skill_type'] ?? '') == 'Creative') echo 'selected'; ?>>
                Creative
            </option>

        </select>

        <label>Description:</label>

        <textarea name="description"
            rows="4"
            placeholder="Describe your skill..."
            required><?php echo htmlspecialchars($edit_skill['description'] ?? ''); ?></textarea>

        <?php if ($edit_skill) { ?>

            <button type="submit" name="edit_skill">
                💾 Update Skill
            </button>

            <a href="skills.php">
                <button type="button">Cancel</button>
            </a>

        <?php } else { ?>

            <button type="submit" name="add_skill">
                ➕ Add Skill
            </button>

        <?php } ?>

    </form>

</div>


<!-- SEARCH SKILLS -->

<form method="GET" action="skills.php" class="search-box">

    <input type="text"
        name="search"
        placeholder="🔍 Search Python, Excel, Java..."
        value="<?php echo htmlspecialchars($search); ?>">

    <button type="submit">
        Search
    </button>

    <a href="skills.php" class="clear-search">
        Clear
    </a>

</form>


<!-- DISPLAY ALL SKILLS -->

<div class="skills-container">

<?php if (mysqli_num_rows($result) > 0) { ?>

    <?php while ($row = mysqli_fetch_assoc($result)) { ?>

        <div class="skill-card">

            <h3>
                <?php echo htmlspecialchars($row['skill_name']); ?>
            </h3>

            <p>
                <b>Type:</b>
                <?php echo htmlspecialchars($row['skill_type']); ?>
            </p>

            <p>
                <b>Description:</b>
                <?php echo htmlspecialchars($row['description']); ?>
            </p>

            <p>
                <b>Offered by:</b>
                <?php echo htmlspecialchars($row['name']); ?>
            </p>

            <?php if ($row['user_id'] == $current_user) { ?>

                <p class="owner-label">⭐ Your Skill</p>

                <div class="action-buttons">

                    <a href="skills.php?edit=<?php echo $row['id']; ?>">
                        <button type="button" class="edit-btn">
                            ✏️ Edit
                        </button>
                    </a>

                    <form method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this skill?');">

                        <input type="hidden"
                            name="skill_id"
                            value="<?php echo $row['id']; ?>">

                        <button type="submit"
                            name="delete_skill"
                            class="delete-btn">
                            🗑️ Delete
                        </button>

                    </form>

                </div>

            <?php } else { ?>

                <a href="request.php?skill_id=<?php echo $row['id']; ?>">
                    <button type="button">
                        🤝 Request Skill
                    </button>
                </a>

            <?php } ?>

        </div>

    <?php } ?>

<?php } else { ?>

    <p class="no-result">
        😔 No Skills Found! Try another search.
    </p>

<?php } ?>

</div>


<!-- NAVIGATION BUTTONS -->

<div style="text-align:center; margin:30px;">

    <a href="dashboard.php">
        <button>📊 Dashboard</button>
    </a>

    <a href="messages.php">
        <button>💬 Messages</button>
    </a>

</div>

</body>
</html>
