<?php
include 'config.php';
session_start();

$result = mysqli_query($conn, "SELECT DISTINCT hospital_name, county FROM users WHERE role = 'hospital_admin' AND hospital_name IS NOT NULL");

?>

<!DOCTYPE html>
<html>
<head>
  <title>Book Emergency</title>
</head>
<body>
  <h2>Emergency Booking Page</h2>

  <form action="submit_emergency.php" method="POST">
    <label>Select Hospital:</label>
    <select name="hospital">
      <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <option value="<?= $row['hospital_name'] ?>"><?= $row['hospital_name'] ?> - <?= $row['county'] ?></option>
      <?php endwhile; ?>
    </select>
    <br><br>

    <label>Emergency Type:</label>
    <select name="type">
      <option value="ambulance">Ambulance</option>
      <option value="bed">Emergency Room</option>
      <option value="fire">Fire/Rescue</option>
    </select>
    <br><br>

    <label>Details:</label><br>
    <textarea name="details" required></textarea><br><br>

    <button type="submit">Send Request</button>
  </form>
</body>
</html>
