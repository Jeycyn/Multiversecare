<?php
// view_emergency_requests.php
include 'config.php';

// Handle admin response
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['respond_id'], $_POST['response'])) {
    $id = intval($_POST['respond_id']);
    $response = $_POST['response'] === 'approve' ? 'Approved' : 'Denied';
    $status = $_POST['response'] === 'approve' ? 'approved' : 'denied';

    $stmt = $conn->prepare("UPDATE emergency_requests SET admin_response = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssi", $response, $status, $id);
    $stmt->execute();
    $stmt->close();
}

$sql = "SELECT * FROM emergency_requests ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Emergency Room Requests</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .table-responsive {
      overflow-x: auto;
    }
    th, td {
      white-space: nowrap;
    }
  </style>
</head>
<body class="py-4">
  <div class="container">
    <h2 class="mb-4 text-center text-primary">🆘 Emergency Room Requests</h2>

    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark text-center">
          <tr>
            <th>ID</th>
            <th>Patient</th>
            <th>Hospital</th>
            <th>Condition</th>
            <th>Breathing</th>
            <th>Conscious?</th>
            <th>Temp (°C)</th>
            <th>Pain</th>
            <th>Location</th>
            <th>Notes</th>
            <th>Severity</th>
            <th>Status</th>
            <th>Response</th>
            <th>Action</th>
            <th>Created</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr class="text-center">
                <td><?= $row['id'] ?></td>
                <td><?= $row['patient_id'] ?></td>
                <td><?= htmlspecialchars($row['hospital_name']) ?></td>
                <td><?= htmlspecialchars($row['condition']) ?></td>
                <td><?= htmlspecialchars($row['breathing']) ?></td>
                <td><?= htmlspecialchars($row['consciousness']) ?></td>
                <td><?= htmlspecialchars($row['temperature']) ?></td>
                <td><?= htmlspecialchars($row['pain_level']) ?></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
                <td><?= htmlspecialchars($row['notes']) ?></td>
                <td>
                  <span class="badge bg-<?= 
                    $row['severity'] === 'Critical' ? 'danger' : 
                    ($row['severity'] === 'Moderate' ? 'warning text-dark' : 'success') ?>">
                    <?= htmlspecialchars($row['severity']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge bg-<?= $row['status'] === 'pending' ? 'warning text-dark' : 
                                             ($row['status'] === 'approved' ? 'success' : 'danger') ?>">
                    <?= htmlspecialchars($row['status']) ?>
                  </span>
                </td>
                <td><?= $row['admin_response'] ?: '<span class="text-muted">Pending</span>' ?></td>
                <td>
                  <?php if ($row['status'] === 'pending'): ?>
                    <form method="post" class="d-flex gap-1">
                      <input type="hidden" name="respond_id" value="<?= $row['id'] ?>">
                      <button type="submit" name="response" value="approve" class="btn btn-success btn-sm">✅ Approve</button>
                      <button type="submit" name="response" value="deny" class="btn btn-danger btn-sm">❌ Deny</button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td><?= $row['created_at'] ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="15" class="text-center text-danger">No emergency requests found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>

<?php $conn->close(); ?>
