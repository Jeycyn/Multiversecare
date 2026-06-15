<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['request_id'], $_POST['driver_name'], $_POST['driver_phone'], $_POST['driver_vehicle'])) {
    $request_id = intval($_POST['request_id']);
    $driver_name = trim($_POST['driver_name']);
    $driver_phone = trim($_POST['driver_phone']);
    $driver_vehicle = trim($_POST['driver_vehicle']);
    $status = 'approved';

    // Make sure to add these columns to your ambulance_requests table:
    // driver_name, driver_phone, driver_vehicle
    $stmt = $conn->prepare("UPDATE ambulance_requests SET status = ?, driver_name = ?, driver_phone = ?, driver_vehicle = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $status, $driver_name, $driver_phone, $driver_vehicle, $request_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Driver assigned and request approved.";
    } else {
        $_SESSION['error'] = "Failed to assign driver.";
    }

    header("Location: hospital_dashboard.php");
    exit();
} else {
    header("Location: hospital_dashboard.php");
    exit();
}
