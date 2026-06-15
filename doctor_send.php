<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = intval($_POST['patient_id']);
  $doctor_email = trim($_POST['doctor_email']);
  $message = trim($_POST['message']);
  $sender = 'doctor';

  if ($patient_id && $doctor_email && $message) {
    $stmt = $conn->prepare("INSERT INTO chat_messages (patient_id, doctor_email, message, sender) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $patient_id, $doctor_email, $message, $sender);
    if ($stmt->execute()) {
      echo "success";
    } else {
      echo "DB error";
    }
  } else {
    echo "Missing fields";
  }
} else {
  echo "Invalid request";
}
?>
