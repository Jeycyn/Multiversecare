<?php
session_start();
include 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$error = '';
$success = '';
$codeSent = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
  $name     = $_POST['name'];
  $email    = $_POST['email'];
  $county   = $_POST['county'];
  $password = $_POST['password'];
  $role     = 'patient';

  $targetDir = "uploads/";
  if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
  $filename = time() . '_' . basename($_FILES["profile_pic"]["name"]);
  $targetFile = $targetDir . $filename;

  if (!move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile)) {
    $error = "❌ Failed to upload profile picture.";
  } else {
    $code = rand(100000, 999999);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, county, password, role, profile_pic, verification_code)
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $email, $county, $hash, $role, $targetFile, $code);

    if ($stmt->execute()) {
      // Send email
      $mail = new PHPMailer(true);
      try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jeffjeycyn@gmail.com'; // replace
        $mail->Password = 'gawx krjo ohfm pcyb';    // replace
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('jeffjeycyn@gmail.com', 'Multiverse Care');
        $mail->addAddress($email, $name);
        $mail->Subject = 'Verify Your Email';
        $mail->Body    = "Hi $name,\n\nYour verification code is: $code";

        $mail->send();

        $_SESSION['verify_id'] = $conn->insert_id;
        $_SESSION['verify_name'] = $name;
        $codeSent = true;

      } catch (Exception $e) {
        $error = "❌ Failed to send email: " . $mail->ErrorInfo;
      }
    } else {
      $error = "❌ Registration failed.";
    }
  }
}

// Handle verification code submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verification_code'])) {
  $input_code = $_POST['verification_code'];
  $id = $_SESSION['verify_id'];

  $stmt = $conn->prepare("SELECT verification_code FROM users WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($user = $result->fetch_assoc()) {
    if ($input_code == $user['verification_code']) {
      $conn->query("UPDATE users SET verification_code = NULL WHERE id = $id");
      $_SESSION['patient_id'] = $id;
      $_SESSION['patient_name'] = $_SESSION['verify_name'];
      unset($_SESSION['verify_id'], $_SESSION['verify_name']);

      header("Location: patient_dashboard.php");
      exit();
    } else {
      $error = "❌ Incorrect verification code.";
      $codeSent = true;
    }
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Patient Register | DoctorsCare</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <style>
    body {
      background: #f0f4f8;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .form-box {
      background: white;
      padding: 30px;
      border-radius: 10px;
      width: 500px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<div class="form-box">
  <h3 class="text-center mb-4">📝 Register as Patient</h3>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label>Full Name</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>County</label>
      <input type="text" name="county" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Profile Picture</label>
      <input type="file" name="profile_pic" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Register</button>
    <p class="text-center mt-3">Already have an account? <a href="patient_login.php">Login</a></p>
  </form>
</div>

<!-- Verification Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">🔐 Email Verification</h5>
      </div>
      <div class="modal-body">
        <p>We’ve sent a code to your email. Enter it below to finish registration:</p>
        <input type="text" name="verification_code" maxlength="6" class="form-control" placeholder="Enter code" required />
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Verify</button>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($codeSent): ?>
<script>
  var modal = new bootstrap.Modal(document.getElementById('verifyModal'));
  modal.show();
</script>
<?php endif; ?>

</body>
</html>
