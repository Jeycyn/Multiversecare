<?php
session_start();
include 'config.php';

if (!isset($_SESSION['patient_id'])) {
  http_response_code(403);
  exit("Unauthorized");
}

$required_fields = [
  'hospital_name', 'condition', 'breathing', 'consciousness',
  'temperature', 'pain_level', 'location', 'severity'
];

// 🛑 Check if any required field is missing
foreach ($required_fields as $field) {
  if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
    http_response_code(400);
    exit("Missing or empty field: $field");
  }
}

$patient_id = $_SESSION['patient_id'];
$hospital = $_POST['hospital_name'];
$condition = $_POST['condition'];
$breathing = $_POST['breathing'];
$consciousness = $_POST['consciousness'];
$temp = $_POST['temperature'];
$pain = $_POST['pain_level'];
$location = $_POST['location'];
$notes = $_POST['notes'] ?? '';
$severity = $_POST['severity'];

$stmt = $conn->prepare("
  INSERT INTO emergency_requests 
  (patient_id, hospital_name, condition, breathing, consciousness, temperature, pain_level, location, notes, severity) 
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
  http_response_code(500);
  exit("Prepare failed: " . $conn->error);
}

$stmt->bind_param("isssssssss", $patient_id, $hospital, $condition, $breathing, $consciousness, $temp, $pain, $location, $notes, $severity);

if ($stmt->execute()) {
  echo "✅ Emergency room request saved.";
} else {
  http_response_code(500);
  echo "❌ DB Error: " . $stmt->error;
}
?>
