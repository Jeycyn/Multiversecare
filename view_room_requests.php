<?php
session_start();
include 'config.php';

$patient_id = $_SESSION['patient_id'];

$res = $conn->query("SELECT * FROM room_requests WHERE patient_id = $patient_id ORDER BY requested_at DESC");

while ($r = $res->fetch_assoc()) {
  echo "<li>";
  echo "Hospital: " . htmlspecialchars($r['hospital_name']) . " | ";
  echo "Status: <strong>" . strtoupper($r['status']) . "</strong>";
  if ($r['status'] === 'approved') {
    echo " | Room: <strong>{$r['assigned_room_number']}</strong>";
  }
  echo "</li>";
}
?>
