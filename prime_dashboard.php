<?php
session_start();
include 'config.php'; 


if (!isset($_SESSION['prime_admin_id'])) {
    header("Location: prime_login.php");
    exit;
}


$admin_id = $_SESSION['prime_admin_id'];
$stmt = $conn->prepare("SELECT name, email, profile_pic FROM prime_admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Prime Admin Dashboard | DoctorsCare</title>
  <style>
    /* Base & reset */
    * {
      box-sizing: border-box;
    }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
      background: #f4f6f9;
      color: #2c3e50;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      line-height: 1.6;
    }

    .container {
      max-width: 1100px;
      margin: 40px auto 60px;
      padding: 20px 30px 50px;
    }

    h1 {
      text-align: center;
      color: #34495e;
      margin-bottom: 40px;
      font-weight: 700;
      font-size: 2.8rem;
      user-select: none;
    }

    .btn-view {
      text-align: center;
      margin-bottom: 50px;
    }

    .btn-view a {
      display: inline-block;
      background-color: #27ae60;
      color: #fff;
      text-decoration: none;
      padding: 14px 50px;
      font-size: 20px;
      font-weight: 600;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      user-select: none;
    }

    .btn-view a:hover,
    .btn-view a:focus {
      background-color: #219150;
      box-shadow: 0 8px 20px rgba(33, 145, 80, 0.6);
      outline: none;
    }

    /* Layout wrapper for forms */
    .forms-wrapper {
      display: flex;
      gap: 40px;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
    }

    /* Each form container styled as a card */
    .form-card {
      background: #ffffff;
      flex: 1 1 45%;
      border-radius: 12px;
      padding: 30px 25px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
      min-width: 320px;
    }

    h2 {
      color: #2c3e50;
      margin-bottom: 30px;
      font-weight: 700;
      font-size: 1.8rem;
      border-bottom: 3px solid #3498db;
      padding-bottom: 8px;
      user-select: none;
    }

    form {
      margin-top: 0;
    }

    label {
      display: block;
      margin-top: 18px;
      font-weight: 600;
      color: #34495e;
      user-select: none;
    }

    input[type="text"],
    input[type="email"],
    input[type="number"],
    select,
    input[type="file"] {
      width: 100%;
      padding: 12px 15px;
      font-size: 16px;
      margin-top: 6px;
      border: 1.8px solid #bdc3c7;
      border-radius: 8px;
      transition: border-color 0.3s ease;
      font-family: inherit;
      user-select: text;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="number"]:focus,
    select:focus,
    input[type="file"]:focus {
      border-color: #3498db;
      outline: none;
      box-shadow: 0 0 8px rgba(52, 152, 219, 0.3);
    }

    button[type="submit"] {
      margin-top: 30px;
      padding: 14px;
      background-color: #2980b9;
      color: white;
      border: none;
      font-size: 18px;
      font-weight: 700;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
      width: 100%;
      user-select: none;
    }

    button[type="submit"]:hover,
    button[type="submit"]:focus {
      background-color: #1c5980;
      box-shadow: 0 5px 15px rgba(28, 89, 128, 0.5);
      outline: none;
    }

    /* Responsive for smaller devices */
    @media (max-width: 900px) {
      .forms-wrapper {
        flex-direction: column;
      }

      .form-card {
        flex: 1 1 100%;
        margin-bottom: 40px;
        min-width: auto;
      }
    }
  </style>
</head>
<body>

<div class="container">
  
  <h1>👑 Prime Admin Dashboard</h1>
<div style="display: flex; align-items: center; justify-content: flex-start; gap: 20px; margin-bottom: 30px;">
  <img src="<?= htmlspecialchars($admin['profile_pic']) ?>" alt="Admin Profile" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 3px solid #2980b9;">
  <div>
    <h2 style="margin: 0; font-size: 1.5rem;"><?= htmlspecialchars($admin['name']) ?></h2>
    <p style="margin: 4px 0; color: #666;"><?= htmlspecialchars($admin['email']) ?></p>
  </div>  

  <a href="logout.php" style="margin-left:auto; background:#c0392b; color:white; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:bold;">
  Logout
</a>

</div>

  <div class="btn-view">
    <a href="view_users.php" aria-label="View Registered Users">👁️ View Registered Users</a>
  </div>

  <div class="forms-wrapper">

    <!-- 👨‍⚕️ Doctor Registration -->
    <div class="form-card">
      <h2>Register a Doctor</h2>
      <form method="POST" action="add_user.php" enctype="multipart/form-data" novalidate>
        <label for="name">Full Name</label>
        <input type="text" name="name" id="name" placeholder="Dr. Jeycyn Jeff" required>

        <label for="email">Email Address</label>
        <input type="email" name="email" id="email" placeholder="doctor@example.com" required>

        <label for="gender">Gender</label>
        <select name="gender" id="gender" required>
          <option value="" disabled selected>Select Gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
          <option value="Other">Other</option>
        </select>

        <label for="specialty">Specialty</label>
        <input type="text" name="specialty" id="specialty" placeholder="e.g. Neurologist" required>

        <label for="hospital_name">Hospital Name</label>
        <input type="text" name="hospital_name" id="hospital_name" placeholder="Hospital Affiliation" required>

        <label for="county">County</label>
        <input type="text" name="county" id="county" placeholder="e.g. Nairobi" required>

        <label for="consultation_fee">Consultation Fee (KES)</label>
        <input type="number" name="consultation_fee" id="consultation_fee" placeholder="e.g. 1000" min="0" required>

        <label for="profile_pic">Profile Picture</label>
        <input type="file" name="profile_pic" id="profile_pic" accept="image/*" required>

        <input type="hidden" name="role" value="doctor">
        <button type="submit">➕ Register Doctor</button>
      </form>
    </div>

    <!-- 🏥 Hospital Admin Registration -->
    <div class="form-card">
      <h2>Register a Hospital Admin</h2>
      <form method="POST" action="add_user.php" enctype="multipart/form-data" novalidate>
        <label for="admin_name">Full Name</label>
        <input type="text" name="name" id="admin_name" placeholder="Admin Full Name" required>

        <label for="admin_email">Email Address</label>
        <input type="email" name="email" id="admin_email" placeholder="admin@hospital.org" required>

        <label for="admin_hospital_name">Hospital Name</label>
        <input type="text" name="hospital_name" id="admin_hospital_name" placeholder="Hospital Affiliation" required>

        <label for="admin_county">County</label>
        <input type="text" name="county" id="admin_county" placeholder="e.g. Kiambu" required>

        <label for="admin_profile_pic">Profile Picture</label>
        <input type="file" name="profile_pic" id="admin_profile_pic" accept="image/*" required>

        <input type="hidden" name="role" value="hospital_admin">
        <button type="submit">➕ Register Hospital Admin</button>
      </form>
    </div>

  </div>
</div>

</body>
</html>
