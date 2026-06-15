<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $id = $_POST['room_id'] ?? '';
  $current = $_POST['current_status'] ?? 'vacant';
  $new = $current === 'vacant' ? 'occupied' : 'vacant';

  $stmt = $conn->prepare("UPDATE emergency_rooms SET status = ? WHERE id = ?");
  $stmt->bind_param("si", $new, $id);
  $stmt->execute();
}

header("Location: hospital_admin_dashboard.php");
exit();
