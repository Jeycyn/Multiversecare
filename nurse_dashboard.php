<?php
session_start();
include 'config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['nurse_id'])) {
  header("Location: nurse_login.php");
  exit();
}

// Get nurse info
$nurse_id = $_SESSION['nurse_id'];
$stmt = $conn->prepare("SELECT * FROM nurses WHERE id = ?");
$stmt->bind_param("i", $nurse_id);
$stmt->execute();
$nurse = $stmt->get_result()->fetch_assoc();

// Handle availability toggle
if (isset($_POST['toggle_availability'])) {
  $is_active = $nurse['is_active'] ? 0 : 1;
  $update = $conn->prepare("UPDATE nurses SET is_active = ? WHERE id = ?");
  $update->bind_param("ii", $is_active, $nurse_id);
  $update->execute();
  $nurse['is_active'] = $is_active; // update local copy
}



if (isset($_POST['save_record'])) {
  $patient_id = $_POST['patient_id'];

  // ✅ Step 1: Check nurse is assigned to patient and patient is admitted
  $check = $conn->prepare("
    SELECT 1 FROM nurse_patient_assignments a
    JOIN admissions adm ON a.patient_id = adm.patient_id
    WHERE a.nurse_id = ? AND a.patient_id = ? AND adm.discharged_at IS NULL
  ");
  $check->bind_param("ii", $nurse_id, $patient_id);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows === 0) {
    die("❌ Unauthorized: You are not assigned to this patient or they are already discharged.");
  }

  // ✅ Step 2: Insert medical record
  $diagnosis = trim($_POST['diagnosis']);
  $treatment = trim($_POST['treatment']);
  $notes = trim($_POST['notes']);
  $nurse_email = $nurse['email'];
  $hospital_name = $nurse['hospital_name'];

  $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, nurse_id, nurse_email, hospital_name, diagnosis, treatment, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
  $stmt->bind_param("iisssss", $patient_id, $nurse_id, $nurse_email, $hospital_name, $diagnosis, $treatment, $notes);
  $stmt->execute();
}







?>
<!DOCTYPE html>
<html>
<head>
  <title>Nurse Dashboard | DoctorsCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f4f8fb;
      font-family: 'Segoe UI', sans-serif;
    }
    .dashboard {
      max-width: 960px;
      margin: 40px auto;
      background: #fff;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    .btn-main {
      background-color: #3498db;
      color: white;
      padding: 10px 20px;
      border-radius: 10px;
    }
    .btn-main:hover {
      background-color: #217dbb;
    }
  </style>
</head>
<body>

<div class="dashboard">
  <div class="text-center">
    <h3>👩‍⚕️ Welcome, <?= htmlspecialchars($nurse['name']) ?></h3>
    <p><?= htmlspecialchars($nurse['hospital_name']) ?> - <?= htmlspecialchars($nurse['county']) ?></p>
    <form method="POST" class="mt-2">
      <button name="toggle_availability" class="btn <?= $nurse['is_active'] ? 'btn-danger' : 'btn-success' ?>">
        <?= $nurse['is_active'] ? 'Go Offline' : 'Go Online' ?>
      </button>
    </form>
  </div>


  <a href="nurse_edit_profile.php" class="btn btn-warning mt-3">✏️ Edit Profile</a>

  <hr>

  
  <div>
    <h5>🧑‍🤝‍🧑 Assigned Patients</h5>
    <ul class="list-group">
      <?php
  $stmt = $conn->prepare("
    SELECT u.id, u.name
    FROM nurse_patient_assignments a
    JOIN users u ON a.patient_id = u.id
    JOIN admissions adm ON u.id = adm.patient_id
    WHERE a.nurse_id = ? AND adm.discharged_at IS NULL
  ");
  $stmt->bind_param("i", $nurse_id);
  $stmt->execute();
  $patients = $stmt->get_result();

  if ($patients->num_rows === 0) {
    echo "<li class='list-group-item text-muted'>No assigned admitted patients currently.</li>";
  } else {
    while ($p = $patients->fetch_assoc()):
?>
  <li class="list-group-item d-flex justify-content-between align-items-center">
    <?= htmlspecialchars($p['name']) ?>
    <form method="POST" class="d-inline">
      <input type="hidden" name="patient_id" value="<?= $p['id'] ?>">
      <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#recordModal<?= $p['id'] ?>">Add Record</button>
    </form>
  </li>

  <!-- Record Modal -->
  <div class="modal fade" id="recordModal<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form method="POST" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Medical Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="patient_id" value="<?= $p['id'] ?>">
          <textarea name="diagnosis" class="form-control mb-2" placeholder="Diagnosis" required></textarea>
          <textarea name="treatment" class="form-control mb-2" placeholder="Treatment" required></textarea>
          <textarea name="notes" class="form-control" placeholder="Additional Notes"></textarea>
        </div>
        <div class="modal-footer">
          <button name="save_record" type="submit" class="btn btn-success">Save Record</button>
        </div>
      </form>
    </div>
  </div>
<?php
    endwhile;
  }
?>

    </ul>
    <h5 class="mt-4">📋 Patient Records</h5>
<?php
  $record_stmt = $conn->prepare("
    SELECT mr.*, u.name AS patient_name
    FROM medical_records mr
    JOIN users u ON mr.patient_id = u.id
    WHERE mr.nurse_id = ?
    ORDER BY mr.created_at DESC
  ");
  $record_stmt->bind_param("i", $nurse_id);
  $record_stmt->execute();
  $records = $record_stmt->get_result();

  if ($records->num_rows === 0) {
    echo "<p class='text-muted'>No records submitted yet.</p>";
  } else {
    while ($r = $records->fetch_assoc()) {
  echo "<div class='border rounded p-3 mb-2'>
          <strong>" . htmlspecialchars($r['patient_name']) . "</strong><br>
          <small><em>" . $r['created_at'] . "</em></small>
          <p><strong>Diagnosis:</strong> " . htmlspecialchars($r['diagnosis']) . "</p>
          <p><strong>Treatment:</strong> " . htmlspecialchars($r['treatment']) . "</p>
          <p><strong>Notes:</strong> " . htmlspecialchars($r['notes']) . "</p>
          <a href='nurse_edit_record.php?record_id=" . $r['id'] . "' class='btn btn-sm btn-warning mt-2'>✏️ Edit</a>
        </div>";
}

  }
?>

  </div>

  
 
  <div class="text-center mt-4">
    <a href="logout.php" class="btn btn-danger">Logout</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
