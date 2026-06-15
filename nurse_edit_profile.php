<?php
session_start();
include 'config.php';

if (!isset($_SESSION['nurse_id'])) {
  header("Location: nurse_login.php");
  exit();
}

$nurse_id = $_SESSION['nurse_id'];

// Get current info
$stmt = $conn->prepare("SELECT * FROM nurses WHERE id = ?");
$stmt->bind_param("i", $nurse_id);
$stmt->execute();
$nurse = $stmt->get_result()->fetch_assoc();

// Handle update
if (isset($_POST['update'])) {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $gender = $_POST['gender'];
  $county = $_POST['county'];

  $update = $conn->prepare("UPDATE nurses SET name = ?, email = ?, gender = ?, county = ? WHERE id = ?");
  $update->bind_param("ssssi", $name, $email, $gender, $county, $nurse_id);
  $update->execute();

  $_SESSION['success'] = "✅ Profile updated successfully!";
  header("Location: nurse_dashboard.php");
  exit();
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Profile | Nurse</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5">
    <div class="card shadow-lg p-4">
      <h4 class="mb-3">✏️ Edit Profile</h4>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($nurse['name']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($nurse['email']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-control" required>
            <option value="Male" <?= $nurse['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $nurse['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= $nurse['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">County</label>
          <input type="text" name="county" class="form-control" value="<?= htmlspecialchars($nurse['county']) ?>" required>
        </div>
        <button name="update" class="btn btn-success">Update Profile</button>
        <a href="nurse_dashboard.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div>
  </div>
</body>
</html>
