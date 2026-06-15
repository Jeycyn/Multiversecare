<?php
session_start();
include 'config.php';

if (!isset($_SESSION['admin_id'])) {
  die("Access denied.");
}

$admin_id = $_SESSION['admin_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $room_number = $_POST['room_number'] ?? '';
  $status = 'vacant';

  // Fetch hospital name
  $stmt = $conn->prepare("SELECT hospital_name FROM users WHERE id = ?");
  $stmt->bind_param("i", $admin_id);
  $stmt->execute();
  $hospital = $stmt->get_result()->fetch_assoc();
  $hospital_name = $hospital['hospital_name'];

  // Insert emergency room
  $insert = $conn->prepare("INSERT INTO emergency_rooms (room_number, hospital_name, status) VALUES (?, ?, ?)");
  $insert->bind_param("sss", $room_number, $hospital_name, $status);

  if ($insert->execute()) {
    echo "success";
  } else {
    echo "error";
  }
}
?>
