<?php
session_start();
require 'config.php';

if (!isset($_SESSION['patient_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'error'=>'Unauthorized']);
    exit;
}

$caller_id = $_SESSION['patient_id'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);
$hospital_name = trim($_POST['hospital_name'] ?? '');

header('Content-Type: application/json');

if ($receiver_id <= 0 || $hospital_name === '') {
    echo json_encode(['success'=>false, 'error'=>'Receiver ID and hospital name are required']);
    exit;
}

// Insert new call
$stmt = $conn->prepare("INSERT INTO video_calls (caller_id, receiver_id, hospital_name, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
$stmt->bind_param("iis", $caller_id, $receiver_id, $hospital_name);

if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'call_id'=>$stmt->insert_id]);
} else {
    echo json_encode(['success'=>false, 'error'=>'Database error: '.$conn->error]);
}
