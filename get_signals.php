<?php
session_start();
require 'config.php';

$call_id = (int)($_GET['call_id'] ?? 0);
$last_id = (int)($_GET['last_id'] ?? 0);

header('Content-Type: application/json');

if ($call_id <= 0) { echo json_encode(['ok'=>false]); exit; }

$res = $conn->query("SELECT id,type,payload,created_at FROM video_signals WHERE call_id=$call_id AND id>$last_id ORDER BY id ASC");
$signals = [];
while($row = $res->fetch_assoc()){
    $signals[] = [
        'id' => (int)$row['id'],
        'type' => $row['type'],
        'payload' => $row['payload'],
        'created_at' => $row['created_at']
    ];
}

echo json_encode(['ok'=>true,'signals'=>$signals]);
