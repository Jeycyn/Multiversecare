<?php
header('Content-Type: application/json');
include 'config.php';

if (!isset($_GET['type'])) {
    echo json_encode(['error' => 'Missing type']);
    exit;
}

$type = $_GET['type'];
$validTypes = ['ambulance', 'bed', 'firestation'];

if (!in_array($type, $validTypes)) {
    echo json_encode(['error' => 'Invalid type']);
    exit;
}

switch ($type) {
    case 'ambulance':
        $query = "SELECT DISTINCT hospital_name FROM ambulances WHERE status = 'available'";
        break;
    case 'bed':
        $query = "SELECT DISTINCT hospital_name FROM emergency_beds WHERE status = 'vacant'";
        break;
    case 'firestation':
        $query = "SELECT DISTINCT station_name AS hospital_name FROM firestations WHERE status = 'active'";
        break;
}

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    exit;
}

$hospitals = [];
while ($row = mysqli_fetch_assoc($result)) {
    $hospitals[] = $row['hospital_name'];
}

echo json_encode($hospitals);
?>
