<?php
include 'config.php';

$patient_id = $_GET['patient_id'] ?? 0;

$stmt = $conn->prepare("SELECT diagnosis, treatment, notes, created_at FROM medical_records WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
echo json_encode($result->fetch_assoc());
?>
