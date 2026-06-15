<?php
include 'config.php';
$id = $_GET['id'];
$conn->query("DELETE FROM patient_reviews WHERE id = $id");
echo "Review deleted.";
?>
