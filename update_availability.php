<?php
session_start();
include 'config.php';

if (!isset($_SESSION['doctor_id'])) {
  echo 'unauthorized';
  exit;
}

$doctor_id = $_SESSION['doctor_id'];
$status = isset($_POST['status']) && $_POST['status'] === '1' ? 1 : 0;

$stmt = $conn->prepare("UPDATE users SET is_available = ? WHERE id = ?");
$stmt->bind_param("ii", $status, $doctor_id);
$stmt->execute();

echo 'success';

// After updating availability
if ($status === 1) {
  // Fetch the doctor's name
  $name_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
  $name_stmt->bind_param("i", $doctor_id);
  $name_stmt->execute();
  $name_stmt->bind_result($doctor_name);
  $name_stmt->fetch();
  $name_stmt->close();

  // Get recent patients who chatted or booked with this doctor
  $patients_stmt = $conn->prepare("
    SELECT DISTINCT patient_id 
    FROM appointments 
    WHERE doctor_id = ? AND status = 'pending'
  ");
  $patients_stmt->bind_param("i", $doctor_id);
  $patients_stmt->execute();
  $result = $patients_stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $patient_id = $row['patient_id'];
    $message = "👨‍⚕️ Dr. $doctor_name is now available. You may now chat or request an appointment.";
    $notif_stmt = $conn->prepare("INSERT INTO notifications (patient_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    $notif_stmt->bind_param("is", $patient_id, $message);
    $notif_stmt->execute();
    $notif_stmt->close();
  }

  $patients_stmt->close();
}

?>
