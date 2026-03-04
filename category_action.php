<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$action = $_POST['action'];

if ($action == 'add') {
    $name        = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    mysqli_query($conn, "INSERT INTO categories (name, description) VALUES ('$name', '$description')");
    header("Location: categories.php?msg=added");
    exit();
}

if ($action == 'edit') {
    $id          = intval($_POST['id']);
    $name        = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    mysqli_query($conn, "UPDATE categories SET name='$name', description='$description' WHERE id=$id");
    header("Location: categories.php?msg=updated");
    exit();
}
?>
