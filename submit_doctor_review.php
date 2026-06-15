<?php
session_start();
include 'config.php';

if (!isset($_SESSION['patient_id'])) {
  die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $doctor_id = intval($_POST['doctor_id']);
  $patient_id = $_SESSION['patient_id'];
  $rating = intval($_POST['rating']);
  $review = trim($_POST['review']);

  if ($rating < 1 || $rating > 5 || empty($review)) {
    die("Invalid input.");
  }

  // Check if the patient already reviewed this doctor
  $check_stmt = $conn->prepare("SELECT id FROM doctor_reviews WHERE doctor_id = ? AND patient_id = ?");
  $check_stmt->bind_param("ii", $doctor_id, $patient_id);
  $check_stmt->execute();
  $check_result = $check_stmt->get_result();

  if ($check_result->num_rows > 0) {
    die("You have already reviewed this doctor.");
  }

  // Insert the review
  $insert_stmt = $conn->prepare("INSERT INTO doctor_reviews (doctor_id, patient_id, rating, review, created_at) VALUES (?, ?, ?, ?, NOW())");
  $insert_stmt->bind_param("iiis", $doctor_id, $patient_id, $rating, $review);
  if ($insert_stmt->execute()) {
    header("Location: view_doctor.php?id=$doctor_id");
    exit;
  } else {
    echo "Failed to submit review. Please try again.";
  }
} else {
  echo "Invalid request.";
}
?>
