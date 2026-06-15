<?php
include 'config.php';

$patient_id = intval($_GET['patient_id'] ?? 0);
$doctor_email = $_GET['doctor_email'] ?? '';

$messages = [];
if ($patient_id && $doctor_email) {
  $stmt = $conn->prepare("SELECT message, sender, DATE_FORMAT(sent_at, '%Y-%m-%d %H:%i:%s') AS sent_at FROM chat_messages WHERE patient_id = ? AND doctor_email = ? ORDER BY sent_at ASC");
  $stmt->bind_param("is", $patient_id, $doctor_email);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $messages[] = $row;
  }
}

header('Content-Type: application/json');
echo json_encode($messages);
?>
