<?php
session_start();
require 'config.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'error'=>'Unauthorized']);
    exit;
}

$call_id = (int)($_POST['call_id'] ?? 0);

header('Content-Type: application/json');

if ($call_id <= 0) {
    echo json_encode(['success'=>false,'error'=>'Invalid call ID']);
    exit;
}

// Update status to accepted
$stmt = $conn->prepare("UPDATE video_calls SET status='accepted' WHERE id=?");
$stmt->bind_param("i",$call_id);
if($stmt->execute()){
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'error'=>$conn->error]);
}
