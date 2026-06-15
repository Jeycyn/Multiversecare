<?php
session_start();
include 'config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST['email'] ?? '');
  $access_code = trim($_POST['access_code'] ?? '');

  $stmt = $conn->prepare("SELECT * FROM nurses WHERE email = ? LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $nurse = $result->fetch_assoc();
    if ($nurse['access_code'] === $access_code) {
      $_SESSION['nurse_id'] = $nurse['id'];
      header("Location: nurse_dashboard.php");
      exit;
    } else {
      $error = "❌ Invalid email or access code.";
    }
  } else {
    $error = "❌ Invalid email or access code.";
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Nurse Login | DoctorsCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f4f8fb;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: 'Segoe UI', sans-serif;
    }
    .login-box {
      background: #fff;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 400px;
    }
    .btn-main {
      background-color: #3498db;
      color: white;
      width: 100%;
    }
    .btn-main:hover {
      background-color: #217dbb;
    }
    .form-control {
      padding: 10px;
    }
  </style>
</head>
<body>

<div class="login-box">
  <h4 class="mb-4 text-center">Nurse Login</h4>
  <form method="POST">
    <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
    <input type="text" name="access_code" class="form-control mb-3" placeholder="Access Code" required>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <button type="submit" class="btn btn-main">Login</button>
  </form>
</div>

</body>
</html>
