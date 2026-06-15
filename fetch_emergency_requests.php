<?php
session_start();
include 'config.php';

$hospital_name = $_SESSION['hospital_name'];

$sql = "SELECT * FROM emergency_requests WHERE hospital_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hospital_name);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

echo json_encode($requests);
?>
