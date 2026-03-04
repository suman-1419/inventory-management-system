<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$action = $_POST['action'];

if ($action == 'add') {
    $name    = mysqli_real_escape_string($conn, $_POST['name']);
    $email   = mysqli_real_escape_string($conn, $_POST['email']);
    $phone   = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    mysqli_query($conn, "INSERT INTO suppliers (name, email, phone, address)
                         VALUES ('$name', '$email', '$phone', '$address')");
    header("Location: suppliers.php?msg=added");
    exit();
}

if ($action == 'edit') {
    $id      = intval($_POST['id']);
    $name    = mysqli_real_escape_string($conn, $_POST['name']);
    $email   = mysqli_real_escape_string($conn, $_POST['email']);
    $phone   = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    mysqli_query($conn, "UPDATE suppliers SET
                         name='$name', email='$email',
                         phone='$phone', address='$address'
                         WHERE id=$id");
    header("Location: suppliers.php?msg=updated");
    exit();
}
?>
