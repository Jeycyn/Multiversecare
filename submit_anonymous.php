<?php
session_start();
include 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php'; // PHPMailer (via Composer)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $message = trim($_POST['message']);
  $type = $_POST['type'] ?? 'anonymous';

  $contact_name = trim($_POST['contact_name'] ?? '');
  $contact_phone = trim($_POST['contact_phone'] ?? '');
  $contact_time = trim($_POST['contact_time'] ?? '');

  if (empty($message)) {
    echo "❌ Message cannot be empty.";
    exit;
  }

  if (empty($contact_name) || empty($contact_phone)) {
    echo "❌ Please provide your name and phone number.";
    exit;
  }

  // Save message to DB (you can expand the table to include name, phone, etc. later)
  $stmt = $conn->prepare("INSERT INTO anonymous_messages (message, submitted_at) VALUES (?, NOW())");
  $stmt->bind_param("s", $message);
  $stmt->execute();

  // Fetch doctors with specialty including 'peer counselor'
  $recipients = $conn->query("
    SELECT email, name 
    FROM users 
    WHERE role = 'doctor' AND LOWER(specialty) LIKE '%peer counselor%'
  ");

  if ($recipients->num_rows === 0) {
    echo "⚠️ Message saved, but no peer counselor doctors found.";
    exit;
  }

  // Prepare email
  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'jeffjeycyn@gmail.com';        // Replace with your Gmail
    $mail->Password   = 'gawx krjo ohfm pcyb';         // Use Gmail App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('jeffjeycyn@gmail.com', 'DoctorsCare - Anonymous Support');
    $mail->Subject = $type === 'sos' ? '🚨 SOS Alert from Patient' : '🧠 New Anonymous Message from a Patient';

    $mail->Body = "Hello Doctor,\n\nYou have received a new message from a patient.\n\n".
                  "📨 Message:\n\"$message\"\n\n".
                  "👤 Contact Details:\n".
                  "• Name: $contact_name\n".
                  "• Phone: $contact_phone\n".
                  ($contact_time ? "• Preferred Time: $contact_time\n" : "").
                  "\nPlease reach out to them with care and discretion.";

    while ($row = $recipients->fetch_assoc()) {
      $mail->addBCC($row['email'], $row['name']);
    }

    $mail->send();
    echo "✅ Your message was sent successfully. A peer counselor will contact you soon.";
  } catch (Exception $e) {
    echo "❌ Message saved, but email failed to send: {$mail->ErrorInfo}";
  }

} else {
  echo "❌ Invalid request method.";
}
?>
