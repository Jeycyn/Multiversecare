<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($appointment_id && $status) {
        // 1. Update the appointment status
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $appointment_id);
        $update_success = $stmt->execute();
        $stmt->close();

        if ($update_success) {
            // 2. Get patient_id and doctor_id
            $stmt = $conn->prepare("SELECT patient_id, doctor_id, appointment_time FROM appointments WHERE id = ?");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $stmt->bind_result($patient_id, $doctor_id, $appt_time);
            $stmt->fetch();
            $stmt->close();

            // 3. Get doctor's name
            $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            $stmt->bind_result($doctor_name);
            $stmt->fetch();
            $stmt->close();

            // 4. Create message
            $message = "Your appointment with Dr. $doctor_name on " . date('d M Y, h:i A', strtotime($appt_time)) . " has been " . ucfirst($status) . ".";

            // 5. Insert into notifications table
            $stmt = $conn->prepare("INSERT INTO notifications (patient_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
            $stmt->bind_param("is", $patient_id, $message);
            $stmt->execute();
            $stmt->close();

            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "invalid";
    }
}
?>
