<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_POST['action'] == 'add') {
    $product_id     = intval($_POST['product_id']);
    $supplier_id    = intval($_POST['supplier_id']);
    $quantity       = intval($_POST['quantity']);
    $total_cost     = floatval($_POST['total_cost']);
    $purchase_date  = mysqli_real_escape_string($conn, $_POST['purchase_date']);

    // Insert purchase record
    mysqli_query($conn, "INSERT INTO purchases
        (product_id, supplier_id, quantity, total_cost, purchase_date)
        VALUES ($product_id, $supplier_id, $quantity, $total_cost, '$purchase_date')");

    // Add stock to product
    mysqli_query($conn, "UPDATE products SET
        quantity = quantity + $quantity WHERE id = $product_id");

    header("Location: purchases.php?msg=added");
    exit();
}
?>