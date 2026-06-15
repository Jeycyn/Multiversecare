<?php
include 'config.php';
$id = $_GET['id'];
$conn->query("UPDATE patient_reviews SET is_approved = 1 WHERE id = $id");
echo "Review approved.";
?>
