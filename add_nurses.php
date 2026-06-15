<?php
// You must have session and DB already included
include 'config.php';
session_start();

// Example mock: pull admin from session
$admin_id = $_SESSION['admin_id'] ?? null;
$admin = null;

if ($admin_id) {
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->bind_param("i", $admin_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $admin = $result->fetch_assoc();
}

// ADD NURSE LOGIC
if (isset($_POST['add_nurse']) && $admin) {
  $name = trim($_POST['nurse_name']);
  $email = trim($_POST['nurse_email']);
  $gender = $_POST['nurse_gender'];
  $hospital = $admin['hospital_name'];
  $county = $admin['county'];
  $created_by = $admin['id'];
  $access_code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));

  $stmt = $conn->prepare("INSERT INTO nurses (name, email, gender, access_code, hospital_name, county, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssssi", $name, $email, $gender, $access_code, $hospital, $county, $created_by);
  $stmt->execute();
}
?>
