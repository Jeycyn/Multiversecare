<?php
include 'config.php';

$hospital = $_GET['hospital'] ?? '';
$response = [];

$stmt = $conn->prepare("SELECT u.name, r.type FROM (
    SELECT patient_id, 'ambulance' as type FROM ambulance_requests WHERE seen = 0 AND hospital_name = ?
    UNION
    SELECT patient_id, 'room' as type FROM room_requests WHERE seen = 0 AND hospital_name = ?
) r JOIN users u ON r.patient_id = u.id");

$stmt->bind_param("ss", $hospital, $hospital);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $response[] = $row;
}

// Optionally mark as seen here
$conn->query("UPDATE ambulance_requests SET seen = 1 WHERE hospital_name = '$hospital'");
$conn->query("UPDATE room_requests SET seen = 1 WHERE hospital_name = '$hospital'");

echo json_encode($response);


