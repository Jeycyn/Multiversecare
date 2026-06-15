<?php
session_start();
include 'config.php';
date_default_timezone_set("Africa/Nairobi");

$secret_code = "DOCTORSSTWCARE";
$error = $success = "";

// Registration logic
if (isset($_POST['register'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $entered_code = $_POST['secret_code'] ?? '';
    $created_at = date('Y-m-d H:i:s');
    $is_active = 1;
    $profile_pic = '';

    if ($entered_code !== $secret_code) {
        $error = "❌ Invalid secret code.";
    } else {
        if (!empty($_FILES['profile_pic']['name'])) {
            $pic_name = time() . '_' . basename($_FILES['profile_pic']['name']);
            $target = "uploads/" . $pic_name;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target)) {
                $profile_pic = $target;
            }
        }
        $hashed_pass = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO prime_admins (name, email, password, profile_pic, created_at, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $name, $email, $hashed_pass, $profile_pic, $created_at, $is_active);
        if ($stmt->execute()) {
            $success = "✅ Registration successful. Please log in.";
        } else {
            $error = "❌ Error: " . $stmt->error;
        }
    }
}

// Login logic
if (isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM prime_admins WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['prime_admin_id'] = $admin['id'];
            header("Location: prime_dashboard.php");
            exit;
        } else {
            $error = "❌ Incorrect password.";
        }
    } else {
        $error = "❌ No active Prime Admin found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Prime Admin Auth | DoctorsCare</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #e8eff5;
      margin: 0;
      padding: 0;
    }
    .auth-container {
      width: 420px;
      margin: 70px auto;
      background: white;
      padding: 35px 30px;
      border-radius: 16px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #2c3e50;
      margin-bottom: 25px;
    }
    input, button, select {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border-radius: 8px;
      border: 1.5px solid #ccc;
      font-size: 16px;
    }
    button {
      background-color: #3498db;
      color: white;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    button:hover {
      background-color: #2980b9;
    }
    .toggle-link {
      text-align: center;
      margin-top: 20px;
      color: #2980b9;
      cursor: pointer;
      font-weight: 500;
    }
    .message {
      text-align: center;
      font-weight: bold;
      margin: 15px 0;
    }
    .message.success {
      color: green;
    }
    .message.error {
      color: red;
    }
    .hidden {
      display: none;
    }
    .form-section {
      transition: all 0.3s ease;
    }
  </style>
</head>
<body>

<div class="auth-container">
  <h2 id="form-title">Prime Admin - Register</h2>

  <!-- Success/Error Message -->
  <?php if (!empty($error)): ?>
    <div class="message error"><?= htmlspecialchars($error) ?></div>
  <?php elseif (!empty($success)): ?>
    <div class="message success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <!-- Register Form -->
  <form method="POST" enctype="multipart/form-data" id="register-form" class="form-section">
    <input type="text" name="secret_code" placeholder="Secret Code" required>
    <input type="text" name="name" placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="file" name="profile_pic" accept="image/*">
    <button type="submit" name="register">Register</button>
  </form>

  <!-- Login Form -->
  <form method="POST" id="login-form" class="form-section hidden">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="login">Login</button>
  </form>

  <div class="toggle-link" onclick="toggleForms()">Already have an account? Log in</div>
</div>

<script>
  const registerForm = document.getElementById('register-form');
  const loginForm = document.getElementById('login-form');
  const formTitle = document.getElementById('form-title');
  const toggleLink = document.querySelector('.toggle-link');

  function toggleForms() {
    registerForm.classList.toggle('hidden');
    loginForm.classList.toggle('hidden');

    const isLogin = !loginForm.classList.contains('hidden');
    formTitle.textContent = isLogin ? "Prime Admin - Login" : "Prime Admin - Register";
    toggleLink.textContent = isLogin ? "Don't have an account? Register" : "Already have an account? Log in";
  }
</script>

</body>
</html>
