<?php
session_start();
include 'config.php';

if (!isset($_SESSION['patient_id'])) {
  http_response_code(403);
  exit("Not logged in");
}

$patient_id = $_SESSION['patient_id'];

// Validate fields
$hospital_name = $_POST['hospital_name'] ?? '';
$condition = $_POST['condition'] ?? '';
$breathing = $_POST['breathing'] ?? '';
$bleeding = $_POST['bleeding'] ?? '';
$location = $_POST['location'] ?? '';
$notes = $_POST['notes'] ?? '';
$severity = $_POST['severity'] ?? '';
$status = 'pending';

if (empty($hospital_name)) {
  http_response_code(400);
  exit("Please select a hospital.");
}

// ✅ Insert ambulance request
$insert = $conn->prepare("INSERT INTO ambulance_requests 
  (patient_id, hospital_name, `condition`, breathing, bleeding, location, notes, severity, status) 
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$insert->bind_param("issssssss", $patient_id, $hospital_name, $condition, $breathing, $bleeding, $location, $notes, $severity, $status);
$insert->execute();

if ($insert->affected_rows > 0) {
  echo "Request submitted successfully";
} else {
  http_response_code(500);
  echo "Failed to submit request.";
}
?>
