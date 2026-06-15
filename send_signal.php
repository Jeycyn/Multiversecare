<?php
session_start();
require 'config.php';

$call_id = (int)($_POST['call_id'] ?? 0);
$type = $_POST['type'] ?? '';
$payload = $_POST['payload'] ?? '';

header('Content-Type: application/json');

if ($call_id <= 0 || !in_array($type, ['offer','answer','candidate','end'])) {
    echo json_encode(['ok'=>false,'error'=>'Invalid parameters']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO video_signals (call_id, type, payload, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iss", $call_id, $type, $payload);
if ($stmt->execute()) {
    echo json_encode(['ok'=>true]);
} else {
    echo json_encode(['ok'=>false,'error'=>$conn->error]);
}
