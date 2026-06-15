<?php
include 'config.php';

// Get doctors and hospital admins
$result = $conn->query("SELECT * FROM users WHERE role IN ('doctor', 'hospital_admin')");

// Get HOD peer counselors
$hods = $conn->query("SELECT * FROM users WHERE role = 'peer_counselor' AND LOWER(specialty) LIKE '%hod%'");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Registered Users | DoctorsCare</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      padding: 30px;
    }
    h2 {
      color: #2c3e50;
      margin-top: 40px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }
    th {
      background-color: #3498db;
      color: white;
    }
    tr:hover {
      background-color: #f1f1f1;
    }
    img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
    }
  </style>
</head>
<body>

  <h2>👨‍⚕️ Registered Doctors & Hospital Admins</h2>
  <table>
    <tr>
      <th>Profile</th>
      <th>Name</th>
      <th>Email</th>
      <th>Role</th>
      <th>Hospital</th>
      <th>County</th>
      <th>Specialty</th>
      <th>Fee (KES)</th>
      <th>Access Code</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><img src="<?= htmlspecialchars($row['profile_pic']) ?>" alt="Profile"></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><?= ucfirst($row['role']) ?></td>
        <td><?= htmlspecialchars($row['hospital_name']) ?></td>
        <td><?= htmlspecialchars($row['county']) ?></td>
        <td><?= $row['specialty'] ?? '-' ?></td>
        <td><?= $row['consultation_fee'] ?? '-' ?></td>
        <td><code><?= $row['access_code'] ?></code></td>
      </tr>
    <?php endwhile; ?>
  </table>

 

</body>
</html>
