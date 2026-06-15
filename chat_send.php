<?php
session_start();
include 'config.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['patient_id'])) {
  http_response_code(403);
  echo "Unauthorized";
  exit();
}

$doctor_email = $_POST['doctor_email'] ?? '';
$message = trim($_POST['message']);
$patient_id = $_SESSION['patient_id'];

if ($doctor_email && $message) {
  $stmt = $conn->prepare("INSERT INTO chat_messages (patient_id, doctor_email, message, sender) VALUES (?, ?, ?, 'patient')");
  $stmt->bind_param("iss", $patient_id, $doctor_email, $message);
  $stmt->execute();
  echo "Message sent";
} else {
  echo "Missing data";
}
?>
