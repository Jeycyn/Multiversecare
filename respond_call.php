<?php
include 'config.php';
$id = $_POST['id'];
$status = $_POST['status'];

$stmt = $conn->prepare("UPDATE emergency_calls SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();
echo "updated";
?>
