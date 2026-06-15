<?php
session_start();
require 'config.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$admin_id = (int)$_SESSION['admin_id'];

// get admin info (hospital)
$stmt = $conn->prepare("SELECT hospital_name FROM users WHERE id = ? AND role = 'hospital_admin' LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$hospital = $admin['hospital_name'] ?? '';

header('Content-Type: application/json');

if ($hospital === '') {
    echo json_encode([]);
    exit;
}

// fetch pending or accepted calls for this hospital
$q = $conn->prepare("
    SELECT id, caller_id, receiver_id, status, call_link, created_at
    FROM video_calls
    WHERE hospital_name = ?
      AND status IN ('pending','accepted')
    ORDER BY created_at DESC
");
$q->bind_param("s", $hospital);
$q->execute();
$res = $q->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
    $out[] = [
        'id' => (int)$row['id'],
        'caller_id' => (int)$row['caller_id'],
        'receiver_id' => (int)$row['receiver_id'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'room_url' => $row['call_link'] ?? 'join_call.php?room=' . urlencode($row['id']) // fallback
    ];
}

echo json_encode($out);
