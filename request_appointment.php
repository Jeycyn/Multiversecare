<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header("Location: patient_login.php");
    exit();
}

include 'config.php';

// OPTIONAL: include helper function to send notification
function sendNotification($conn, $patient_id, $message) {
    $stmt = $conn->prepare("INSERT INTO notifications (patient_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
    $stmt->bind_param("is", $patient_id, $message);
    $stmt->execute();
}

$patient_id = $_SESSION['patient_id'];
$doctor_id = $_POST['doctor_id'];
$date = $_POST['date'];
$reason = $_POST['reason'];

// Insert into appointments table
$stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_time, reason, status) VALUES (?, ?, ?, ?, 'pending')");
$stmt->bind_param("iiss", $patient_id, $doctor_id, $date, $reason);

if ($stmt->execute()) {
    // 🔔 Get doctor name
    $doctorStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $doctorStmt->bind_param("i", $doctor_id);
    $doctorStmt->execute();
    $doctorResult = $doctorStmt->get_result();
    $doctor = $doctorResult->fetch_assoc();
    $doctorName = $doctor['name'] ?? 'Doctor';

    // 🔔 Send notification to patient
    $message = "You requested an appointment with Dr. $doctorName on $date.";
    sendNotification($conn, $patient_id, $message);

    header("Location: patient_dashboard.php?success=1");
} else {
    echo "Error: " . $stmt->error;
}
?>
