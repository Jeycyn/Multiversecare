<?php
include 'config.php';

$id = $_POST['id'];
$status = $_POST['status'];

if (!in_array($status, ['approved', 'denied'])) {
  http_response_code(400);
  exit("Invalid status");
}

$stmt = $conn->prepare("UPDATE emergency_requests SET status = ?, responded_at = NOW() WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();

echo "Status updated to $status";
?>
