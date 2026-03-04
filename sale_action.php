<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_POST['action'] == 'add') {
    $product_id    = intval($_POST['product_id']);
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    $quantity      = intval($_POST['quantity']);
    $total_price   = floatval($_POST['total_price']);
    $sale_date     = mysqli_real_escape_string($conn, $_POST['sale_date']);

    // Check stock availability
    $product = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM products WHERE id=$product_id"));

    if ($product['quantity'] < $quantity) {
        header("Location: sales.php?msg=nostock");
        exit();
    }

    // Insert sale
    mysqli_query($conn, "INSERT INTO sales
        (product_id, customer_name, quantity, total_price, sale_date)
        VALUES ($product_id, '$customer_name', $quantity, $total_price, '$sale_date')");

    // Deduct stock
    mysqli_query($conn, "UPDATE products SET
        quantity = quantity - $quantity WHERE id = $product_id");

    header("Location: sales.php?msg=added");
    exit();
}
?>
