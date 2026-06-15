<?php
session_start();
include 'config.php';

// Assuming the hospital name is stored in session as 'hospital_name'
$hospitalName = $_SESSION['hospital_name'] ?? '';

$sql = "SELECT * FROM emergency_calls WHERE hospital_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hospitalName);
$stmt->execute();
$result = $stmt->get_result();
?>

<h3>Emergency Video Calls</h3>
<?php if ($result->num_rows > 0): ?>
    <table border="1" cellpadding="10">
        <tr>
            <th>Patient</th>
            <th>Meeting Link</th>
            <th>Status</th>
            <th>Time</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['patient_name']) ?></td>
                <td><a href="<?= htmlspecialchars($row['meet_link']) ?>" target="_blank">Join Call</a></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>No incoming emergency calls.</p>
<?php endif; ?>

<div id="emergency-calls-container">
  <!-- Emergency calls will be loaded here -->
</div>
