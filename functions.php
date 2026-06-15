<?php
function sendNotification($conn, $patient_id, $message) {
  if (!$patient_id || !$message) return false;

  $stmt = $conn->prepare("INSERT INTO notifications (patient_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
  $stmt->bind_param("is", $patient_id, $message);
  return $stmt->execute();
}
?>
