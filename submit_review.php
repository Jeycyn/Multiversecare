<?php
session_start();
include 'config.php';

if (!isset($_SESSION['patient_id'])) {
  header("Location: patient_login.php");
  exit();
}

$patient_id = $_SESSION['patient_id'];
$review_text = trim($_POST['review_text'] ?? '');

if ($review_text === '') {
  $_SESSION['error'] = "Review cannot be empty.";
  header("Location: patient_dashboard.php");
  exit();
}

// Get patient details
$stmt = $conn->prepare("SELECT name, profile_pic FROM users WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
  $_SESSION['error'] = "User not found.";
  header("Location: patient_dashboard.php");
  exit();
}

$name = $user['name'];
$profile_img = $user['profile_pic'] ?? 'default.jpg';

// Insert the review
$insert = $conn->prepare("
  INSERT INTO patient_reviews (patient_id, patient_name, profile_img, review_text)
  VALUES (?, ?, ?, ?)
");
$insert->bind_param("isss", $patient_id, $name, $profile_img, $review_text);

if ($insert->execute()) {
  $_SESSION['success'] = "Review submitted successfully!";
} else {
  $_SESSION['error'] = "Something went wrong. Please try again.";
}

header("Location: patient_dashboard.php");
exit();
