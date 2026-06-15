<?php
include 'config.php';

$hospital = $_POST['hospital_name'];
$type = $_POST['call_type'];
$url = $_POST['room_url'];

$stmt = $conn->prepare("INSERT INTO calls (hospital_name, call_type, room_url) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $hospital, $type, $url);
$stmt->execute();

echo json_encode(["status" => "success"]);
?>
