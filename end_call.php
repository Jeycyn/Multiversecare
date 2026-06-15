<?php
session_start();
require 'config.php';

$call_id = (int)($_POST['call_id'] ?? 0);

header('Content-Type: application/json');

if ($call_id <= 0) {
    echo json_encode(['success'=>false,'error'=>'Invalid call ID']);
    exit;
}

// Update status
$stmt = $conn->prepare("UPDATE video_calls SET status='ended' WHERE id=?");
$stmt->bind_param("i",$call_id);
$stmt->execute();

// Add end signal
$stmt2 = $conn->prepare("INSERT INTO video_signals (call_id,type,payload,created_at) VALUES (?, 'end', '{}', NOW())");
$stmt2->bind_param("i",$call_id);
$stmt2->execute();

echo json_encode(['success'=>true]);
