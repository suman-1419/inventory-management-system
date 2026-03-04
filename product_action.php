<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$action = $_POST['action'];

if ($action == 'add') {
    $name          = mysqli_real_escape_string($conn, $_POST['name']);
    $category_id   = intval($_POST['category_id']);
    $quantity      = intval($_POST['quantity']);
    $price         = floatval($_POST['price']);
    $reorder_level = intval($_POST['reorder_level']);

    mysqli_query($conn, "INSERT INTO products (name, category_id, quantity, price, reorder_level)
                         VALUES ('$name', $category_id, $quantity, $price, $reorder_level)");
    header("Location: products.php?msg=added");
    exit();
}

if ($action == 'edit') {
    $id            = intval($_POST['id']);
    $name          = mysqli_real_escape_string($conn, $_POST['name']);
    $category_id   = intval($_POST['category_id']);
    $quantity      = intval($_POST['quantity']);
    $price         = floatval($_POST['price']);
    $reorder_level = intval($_POST['reorder_level']);

    mysqli_query($conn, "UPDATE products SET
                         name='$name', category_id=$category_id,
                         quantity=$quantity, price=$price,
                         reorder_level=$reorder_level
                         WHERE id=$id");
    header("Location: products.php?msg=updated");
    exit();
}
?>
```

---

## ✅ Save Both Files Then Open:
```
http://localhost/inventory/products.php