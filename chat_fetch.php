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

$patient_id = $_SESSION['patient_id'];
$doctor_email = $_GET['doctor_email'] ?? '';

$stmt = $conn->prepare("SELECT * FROM chat_messages WHERE patient_id = ? AND doctor_email = ? ORDER BY sent_at ASC");
$stmt->bind_param("is", $patient_id, $doctor_email);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
  $align = $row['sender'] === 'patient' ? 'text-end text-primary' : 'text-start text-success';
  echo "<div class='p-2 mb-1 border rounded $align'><small>{$row['message']}</small><br><small class='text-muted' style='font-size:10px'>" . date("H:i", strtotime($row['sent_at'])) . "</small></div>";
}
?>
