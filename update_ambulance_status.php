<?php
session_start();
include 'config.php';

if (!isset($_POST['request_id'], $_POST['action'], $_POST['admin_response'])) {
  exit("Missing data");
}

$request_id = $_POST['request_id'];
$status = $_POST['action'];
$admin_response = trim($_POST['admin_response']);

if (!in_array($status, ['approved', 'denied'])) {
  exit("Invalid status");
}

// 1. Update ambulance request
$stmt = $conn->prepare("UPDATE ambulance_requests SET status = ?, admin_response = ?, responded_at = NOW() WHERE id = ?");
$stmt->bind_param("ssi", $status, $admin_response, $request_id);
$stmt->execute();
$stmt->close();

// 2. Fetch patient_id and hospital_name
$stmt = $conn->prepare("SELECT patient_id, hospital_name, condition FROM ambulance_requests WHERE id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->bind_result($patient_id, $hospital_name, $condition);
$stmt->fetch();
$stmt->close();

// 3. Create notification message
$message = "🚑 Your ambulance request for '$condition' at $hospital_name has been " . ucfirst($status) . ". Admin says: $admin_response";

// 4. Insert notification
$stmt = $conn->prepare("INSERT INTO notifications (patient_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
$stmt->bind_param("is", $patient_id, $message);
$stmt->execute();
$stmt->close();

header("Location: hospital_admin_dashboard.php?status_updated=1");
exit();
