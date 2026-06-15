<?php
session_start();
include 'config.php';

if (isset($_SESSION['patient_id'])) {
  $id = $_SESSION['patient_id'];
  $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE patient_id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
}
?>
