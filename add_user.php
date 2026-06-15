<?php
include 'config.php';

function generateCode($length = 6) {
  return strtoupper(substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length));
}

// Detect profile picture field
$profileInputName = '';
foreach (['profile_pic', 'profile_pic_doc', 'profile_pic_admin'] as $field) {
  if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
    $profileInputName = $field;
    break;
  }
}

if (!$profileInputName) {
  die("❌ No profile picture uploaded.");
}

// Handle profile picture upload
$targetDir = "../uploads/";
if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

$originalFile = basename($_FILES[$profileInputName]["name"]);
$timestamp = time();
$profile_pic = $targetDir . $timestamp . "_" . $originalFile;

if (!move_uploaded_file($_FILES[$profileInputName]["tmp_name"], $profile_pic)) {
  die("❌ Failed to upload profile picture.");
}

// Collect form data
$name             = $_POST['name'];
$email            = $_POST['email'];
$role             = $_POST['role'];
$county           = $_POST['county'];
$hospital_name    = $_POST['hospital_name'] ?? null;
$specialty        = $_POST['specialty'] ?? null;
$consultation_fee = $_POST['consultation_fee'] ?? null;
$gender           = $_POST['gender'] ?? null;
$access_code      = generateCode();
$created_by       = 1; // Prime Admin ID

// Prepare query
$sql = "INSERT INTO users (
          name, email, role, gender, profile_pic,
          specialty, hospital_name, county,
          access_code, consultation_fee, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
  "ssssssssiii",
  $name,
  $email,
  $role,
  $gender,
  $profile_pic,
  $specialty,
  $hospital_name,
  $county,
  $access_code,
  $consultation_fee,
  $created_by
);

// Execute and respond
if ($stmt->execute()) {
  echo "<h2>User Registered Successfully 🎉</h2>";
  echo "<p><strong>Name:</strong> $name</p>";
  echo "<p><strong>Role:</strong> $role</p>";
  echo "<p><strong>Email:</strong> $email</p>";
  echo "<p><strong>Access Code:</strong> <code>$access_code</code></p>";
  echo "<a href='prime_dashboard.php'>← Back to Dashboard</a>";
} else {
  echo "❌ Error: " . $stmt->error;
}
?>
