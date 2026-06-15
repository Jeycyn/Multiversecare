<?php
session_start();
include 'config.php';

if (!isset($_SESSION['doctor_id'])) {
  header("Location: doctor_dashboard.php");
  exit;
}

$doctor_id = $_SESSION['doctor_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
  $file = $_FILES['profile_pic'];
  $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
  $maxSize = 2 * 1024 * 1024; // 2MB

  if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = 'doctor_' . $doctor_id . '_' . time() . '.' . $ext;
    $uploadPath = 'uploads/' . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
      // Save to DB
      $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
      $stmt->bind_param("si", $uploadPath, $doctor_id);
      $stmt->execute();
      header("Location: doctor_dashboard.php");
      exit;
    } else {
      echo "❌ Failed to upload image.";
    }
  } else {
    echo "❌ Invalid file type or size. Only JPG/PNG under 2MB allowed.";
  }
} else {
  echo "❌ No image received.";
}
?>
