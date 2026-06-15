<?php
session_start();
require 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$callId = (int)($data['id'] ?? 0);

if($callId){
  $stmt = $conn->prepare("UPDATE calls SET status='rejected' WHERE id=?");
  $stmt->bind_param("i", $callId);
  $stmt->execute();
}

echo json_encode(['success'=>true]);
?>
