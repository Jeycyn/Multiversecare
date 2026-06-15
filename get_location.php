<?php
include 'config.php';
$request_id = intval($_GET['request_id']);
$stmt = $conn->prepare("SELECT driver_lat, driver_lng FROM ambulance_requests WHERE id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
echo json_encode([
  'lat' => $res['driver_lat'],
  'lng' => $res['driver_lng']
]);
?>
