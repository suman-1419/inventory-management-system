<?php
session_start();
include 'config/db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    $user   = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login — InvenTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    * { font-family: 'Inter', sans-serif; }

    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #1a1f36 0%, #2d3561 100%);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-card {
      background: #fff;
      border-radius: 20px;
      padding: 40px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }

    .brand-logo {
      width: 52px; height: 52px;
      background: linear-gradient(135deg, #7b93ff, #5563eb);
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 16px;
    }

    .brand-logo i { color: #fff; font-size: 1.5rem; }

    .brand-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1a1f36;
      text-align: center;
      margin-bottom: 4px;
    }

    .brand-title span { color: #4361ee; }

    .brand-subtitle {
      text-align: center;
      color: #8492a6;
      font-size: 0.85rem;
      margin-bottom: 28px;
    }

    .form-label {
      font-size: 0.82rem;
      font-weight: 600;
      color: #3d4557;
    }

    .form-control {
      border-radius: 10px;
      border: 1.5px solid #e2e8f0;
      padding: 10px 14px;
      font-size: 0.9rem;
      transition: all 0.2s;
    }

    .form-control:focus {
      border-color: #4361ee;
      box-shadow: 0 0 0 3px rgba(67,97,238,0.15);
    }

    .input-group .form-control { border-right: none; }

    .input-group .btn-outline-secondary {
      border: 1.5px solid #e2e8f0;
      border-left: none;
      border-radius: 0 10px 10px 0;
      background: #f8f9fa;
      color: #8492a6;
    }

    .btn-login {
      background: linear-gradient(135deg, #4361ee, #5563eb);
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 12px;
      font-weight: 600;
      font-size: 0.95rem;
      width: 100%;
      transition: all 0.2s;
    }

    .btn-login:hover {
      background: linear-gradient(135deg, #3451d1, #4452d8);
      transform: translateY(-1px);
      box-shadow: 0 4px 15px rgba(67,97,238,0.4);
      color: #fff;
    }

    .alert-danger {
      border-radius: 10px;
      font-size: 0.85rem;
      border: none;
      background: #ffeaec;
      color: #e63946;
    }

    .footer-text {
      text-align: center;
      color: #8492a6;
      font-size: 0.78rem;
      margin-top: 24px;
    }
  </style>
</head>
<body>

<div class="login-card">

  <!-- Brand -->
  <div class="brand-logo">
    <i class="bi bi-box-seam-fill"></i>
  </div>
  <div class="brand-title">Inven<span>Track</span></div>
  <div class="brand-subtitle">Sign in to your account</div>

  <!-- Error Message -->
  <?php if ($error): ?>
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
    </div>
  <?php endif; ?>

  <!-- Login Form -->
  <form method="POST" action="">

    <div class="mb-3">
      <label class="form-label">Username</label>
      <div class="input-group">
        <span class="input-group-text" style="border-radius:10px 0 0 10px;border:1.5px solid #e2e8f0;background:#f8f9fa;">
          <i class="bi bi-person text-muted"></i>
        </span>
        <input type="text" name="username" class="form-control"
               placeholder="Enter your username" required
               style="border-radius:0 10px 10px 0;border-left:none;"/>
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label">Password</label>
      <div class="input-group">
        <span class="input-group-text" style="border-radius:10px 0 0 10px;border:1.5px solid #e2e8f0;background:#f8f9fa;">
          <i class="bi bi-lock text-muted"></i>
        </span>
        <input type="password" name="password" id="passwordField" class="form-control"
               placeholder="Enter your password" required
               style="border-radius:0;border-left:none;border-right:none;"/>
               <button type="submit" class="btn-login">
  <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
</button>