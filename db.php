<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "skill_swap"
);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

?>