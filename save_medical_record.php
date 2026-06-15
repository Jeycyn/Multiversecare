<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['patient_id', 'doctor_id', 'doctor_email', 'hospital_name', 'diagnosis', 'treatment', 'notes'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode([
                "status" => "error",
                "message" => "Missing field: $field"
            ]);
            exit;
        }
    }

    // Sanitize inputs
    $patient_id = intval($_POST['patient_id']);
    $doctor_id = intval($_POST['doctor_id']);
    $doctor_email = filter_var($_POST['doctor_email'], FILTER_VALIDATE_EMAIL);
    $hospital_name = trim($_POST['hospital_name']);
    $diagnosis = trim($_POST['diagnosis']);
    $treatment = trim($_POST['treatment']);
    $notes = trim($_POST['notes']);

    if (!$doctor_email) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid doctor email."
        ]);
        exit;
    }

    // Insert into database
    $stmt = $conn->prepare("
        INSERT INTO medical_records 
        (patient_id, doctor_id, doctor_email, hospital_name, diagnosis, treatment, notes, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => "SQL Error: " . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("iisssss", $patient_id, $doctor_id, $doctor_email, $hospital_name, $diagnosis, $treatment, $notes);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Medical record saved successfully."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Database error: " . $stmt->error
        ]);
    }

    $stmt->close();
    $conn->close();

} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method. Use POST."
    ]);
}
?>
