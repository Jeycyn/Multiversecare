<?php
include 'config.php';

$hospital = $_GET['hospital'] ?? '';
$response = [
  'ambulance' => [],
  'rooms' => []
];

// Ambulance requests
$stmt = $conn->prepare("SELECT r.id, u.name AS patient_name, r.status 
                        FROM ambulance_requests r 
                        JOIN users u ON r.patient_id = u.id 
                        WHERE r.hospital_name = ?");
$stmt->bind_param("s", $hospital);
$stmt->execute();
$ambulance_result = $stmt->get_result();
while ($row = $ambulance_result->fetch_assoc()) {
  $response['ambulance'][] = $row;
}

// Emergency room requests
$stmt = $conn->prepare("SELECT r.id, u.name AS patient_name, r.status 
                        FROM room_requests r 
                        JOIN users u ON r.patient_id = u.id 
                        WHERE r.hospital_name = ?");
$stmt->bind_param("s", $hospital);
$stmt->execute();
$room_result = $stmt->get_result();
while ($row = $room_result->fetch_assoc()) {
  $response['rooms'][] = $row;
}

header('Content-Type: application/json');
echo json_encode($response);
?>
