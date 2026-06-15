<?php
session_start();
include 'config.php';

if (!isset($_SESSION['nurse_id'])) {
  header("Location: nurse_login.php");
  exit();
}

$nurse_id = $_SESSION['nurse_id'];
$record_id = $_GET['record_id'] ?? null;

// Fetch the record
$stmt = $conn->prepare("SELECT * FROM medical_records WHERE id = ? AND nurse_id = ?");
$stmt->bind_param("ii", $record_id, $nurse_id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();

if (!$record) {
  die("❌ Record not found or not yours.");
}

// Handle update
if (isset($_POST['update_record'])) {
  $diagnosis = trim($_POST['diagnosis']);
  $treatment = trim($_POST['treatment']);
  $notes = trim($_POST['notes']);

  $update = $conn->prepare("UPDATE medical_records SET diagnosis = ?, treatment = ?, notes = ? WHERE id = ? AND nurse_id = ?");
  $update->bind_param("sssii", $diagnosis, $treatment, $notes, $record_id, $nurse_id);
  $update->execute();

  $_SESSION['success'] = "✅ Record updated successfully!";
  header("Location: nurse_dashboard.php");
  exit();
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Medical Record</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="card shadow p-4">
    <h4>Edit Medical Record</h4>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Diagnosis</label>
        <textarea name="diagnosis" class="form-control" required><?= htmlspecialchars($record['diagnosis']) ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Treatment</label>
        <textarea name="treatment" class="form-control" required><?= htmlspecialchars($record['treatment']) ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control"><?= htmlspecialchars($record['notes']) ?></textarea>
      </div>
      <button type="submit" name="update_record" class="btn btn-success">Update Record</button>
      <a href="nurse_dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>
</body>
</html>
