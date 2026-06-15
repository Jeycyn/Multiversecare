<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $id = $_POST['ambulance_id'] ?? '';
  $current = $_POST['current_status'] ?? 'available';
  $new = $current === 'available' ? 'unavailable' : 'available';

  $stmt = $conn->prepare("UPDATE ambulances SET status = ? WHERE id = ?");
  $stmt->bind_param("si", $new, $id);
  $stmt->execute();
}

header("Location: hospital_admin_dashboard.php");
exit();
