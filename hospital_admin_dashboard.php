<?php
session_start();
include 'config.php';

$admin = null;
$error = '';
$maxAttempts = 5;

if (!isset($_SESSION['attempts'])) {
  $_SESSION['attempts'] = 0;
}

// Handle login
if (
  $_SERVER["REQUEST_METHOD"] === "POST" &&
  !isset($_POST['add_nurse']) &&
  !isset($_POST['admit_patient']) &&
  !isset($_POST['assign_patient']) &&
  !isset($_POST['discharge']) &&
  !isset($_POST['respond_ambulance'])
) {
  if ($_SESSION['attempts'] >= $maxAttempts) {
    $error = "❌ Too many attempts. Try again later.";
  } else {
    $email = trim($_POST['email'] ?? '');
    $access_code = trim($_POST['access_code'] ?? '');

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'hospital_admin' LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
      $admin = $result->fetch_assoc();
      if ($admin['access_code'] === $access_code) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['attempts'] = 0;
      } else {
        $_SESSION['attempts']++;
        $error = "❌ Invalid email or access code.";
        $admin = null;
      }
    } else {
      $_SESSION['attempts']++;
      $error = "❌ Invalid email or access code.";
    }
  }
}

// Fetch admin from session if not set
if (!$admin && isset($_SESSION['admin_id'])) {
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->bind_param("i", $_SESSION['admin_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $admin = $result->fetch_assoc();
}

// Add nurse
if (isset($_POST['add_nurse']) && $admin) {
  $nurse_name = trim($_POST['nurse_name']);
  $nurse_email = trim($_POST['nurse_email']);
  $nurse_gender = $_POST['nurse_gender'];
  $hospital = $admin['hospital_name'];
  $county = $admin['county'];
  $created_by = $admin['id'];
  $access_code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ123456789'), 0, 6));

  $check = $conn->prepare("SELECT id FROM nurses WHERE email = ?");
  $check->bind_param("s", $nurse_email);
  $check->execute();
  $check_result = $check->get_result();

  if ($check_result->num_rows > 0) {
    $error = "❌ Nurse with that email already exists.";
  } else {
    $stmt = $conn->prepare("INSERT INTO nurses (name, email, gender, hospital_name, county, access_code, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $nurse_name, $nurse_email, $nurse_gender, $hospital, $county, $access_code, $created_by);
    $stmt->execute();
  }
}

// Assign patient to nurse
if (isset($_POST['assign_patient']) && $admin) {
  $nurse_id = intval($_POST['assign_nurse_id']);
  $patient_id = intval($_POST['assign_patient_id']);
  $assigned_by = $admin['id'];

  $check = $conn->prepare("SELECT id FROM nurse_patient_assignments WHERE nurse_id = ? AND patient_id = ?");
  $check->bind_param("ii", $nurse_id, $patient_id);
  $check->execute();
  $result = $check->get_result();

  if ($result->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO nurse_patient_assignments (nurse_id, patient_id, assigned_by) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $nurse_id, $patient_id, $assigned_by);
    $stmt->execute();
  } else {
    $error = "❌ This patient is already assigned to the nurse.";
  }
}

// Admit patient
if (isset($_POST['admit_patient']) && $admin) {
  $name = trim($_POST['patient_name']);
  $email = trim($_POST['patient_email']);
  $gender = $_POST['patient_gender'];
  $bed_id = intval($_POST['bed_id']);
  $reason = trim($_POST['admission_reason']);
  $hospital = $admin['hospital_name'];
  $county = $admin['county'];

  if (!$name || !$email) {
    $error = "❌ Patient name and email cannot be empty.";
  } else {
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'patient'");
    $check->bind_param("s", $email);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
      $patient = $res->fetch_assoc();
      $patient_id = $patient['id'];
    } else {
      $insert_patient = $conn->prepare("INSERT INTO users (name, email, gender, hospital_name, county, role) VALUES (?, ?, ?, ?, ?, 'patient')");
      $insert_patient->bind_param("sssss", $name, $email, $gender, $hospital, $county);
      $insert_patient->execute();
      $patient_id = $insert_patient->insert_id;
    }

   $stmt = $conn->prepare("INSERT INTO admissions (patient_id, hospital_name, bed_id, notes) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isis", $patient_id, $hospital, $bed_id, $reason);

    
    $stmt->execute();

    $stmt2 = $conn->prepare("UPDATE emergency_beds SET status = 'occupied' WHERE id = ?");
    $stmt2->bind_param("i", $bed_id);
    $stmt2->execute();
  }
}

// Discharge patient
if (isset($_POST['discharge']) && $admin) {
  $admission_id = intval($_POST['admission_id']);

  $get_bed = $conn->prepare("SELECT bed_id FROM admissions WHERE id = ?");
  $get_bed->bind_param("i", $admission_id);
  $get_bed->execute();
  $bed_res = $get_bed->get_result();

  if ($bed = $bed_res->fetch_assoc()) {
    $bed_id = $bed['bed_id'];

    $stmt = $conn->prepare("UPDATE admissions SET discharged_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $admission_id);
    $stmt->execute();

    $update_bed = $conn->prepare("UPDATE emergency_beds SET status = 'vacant' WHERE id = ?");
    $update_bed->bind_param("i", $bed_id);
    $update_bed->execute();
  }
}



// Add ambulance
if (isset($_POST['add_ambulance']) && $admin) {
  $plate = trim($_POST['plate_number']);
  $driver = trim($_POST['driver_name']);
  $hospital = $admin['hospital_name'];

  $stmt = $conn->prepare("INSERT INTO ambulances (plate_number, driver_name, hospital_name, status) VALUES (?, ?, ?, 'available')");
  $stmt->bind_param("sss", $plate, $driver, $hospital);
  $stmt->execute();
}

// Toggle status
if (isset($_POST['toggle_ambulance_status']) && isset($_POST['ambulance_id'])) {
  $id = intval($_POST['ambulance_id']);
  $stmt = $conn->prepare("SELECT status FROM ambulances WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $new_status = $row['status'] === 'available' ? 'dispatched' : 'available';
    $update = $conn->prepare("UPDATE ambulances SET status = ? WHERE id = ?");
    $update->bind_param("si", $new_status, $id);
    $update->execute();
  }
}

// Delete ambulance
if (isset($_POST['delete_ambulance']) && isset($_POST['delete_ambulance_id'])) {
  $id = intval($_POST['delete_ambulance_id']);
  $stmt = $conn->prepare("DELETE FROM ambulances WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
}

// Add bed
if (isset($_POST['add_bed']) && $admin) {
  $bed_number = trim($_POST['bed_number']);
  $hospital = $admin['hospital_name'];
  $stmt = $conn->prepare("INSERT INTO emergency_beds (bed_number, hospital_name, status) VALUES (?, ?, 'vacant')");
  $stmt->bind_param("ss", $bed_number, $hospital);
  $stmt->execute();
}

// Delete bed
if (isset($_POST['delete_bed']) && isset($_POST['delete_bed_id'])) {
  $id = intval($_POST['delete_bed_id']);
  $stmt = $conn->prepare("DELETE FROM emergency_beds WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
}



?>
<!DOCTYPE html>
<html>
<head>
  <title>Hospital Admin Dashboard | DoctorsCare</title>
  <meta charset="UTF-8">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
   /* ---------------- General ---------------- */
body {
  margin: 0;
  font-family: 'Segoe UI', sans-serif;
  background: linear-gradient(145deg, #e8f0f8, #f9f6f2);
  color: #222;
  overflow-x: hidden;
}

/* ---------------- Dashboard Wrapper ---------------- */
.dashboard {
  max-width: 1200px;
  margin: 40px auto;
  padding: 40px;
  display: grid;
  grid-template-columns: 1fr;
  gap: 40px;
}

/* ---------------- Profile Card ---------------- */
.text-center {
  background: linear-gradient(145deg, #ffffff80, #e8f0f880);
  backdrop-filter: blur(15px);
  border-radius: 20px;
  padding: 30px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  animation: fadeInUp 0.6s ease forwards;
}

.text-center:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 50px rgba(255,215,0,0.4);
}

.profile-pic {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  border: 4px solid #FFD700;
  object-fit: cover;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.profile-pic:hover {
  transform: scale(1.1);
  box-shadow: 0 15px 40px rgba(255,215,0,0.6);
}

.text-center h3 {
  color: #1a2f6f;
  font-weight: 700;
  margin-top: 15px;
}

.text-center p {
  color: #555;
  margin: 5px 0;
}

/* ---------------- Section Cards ---------------- */
.section {
  background: rgba(255,255,255,0.05);
  backdrop-filter: blur(15px);
  border-radius: 20px;
  padding: 30px;
  box-shadow: 0 12px 35px rgba(0,0,0,0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  animation: fadeInUp 0.7s ease forwards;
}

.section:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 50px rgba(255,215,0,0.3);
}

.section h5 {
  color: #FFD700;
  font-weight: 700;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* ---------------- Buttons ---------------- */
.btn-main {
  background: linear-gradient(135deg, #FFD700, #1a2f6f);
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 12px 25px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 8px 25px rgba(255,215,0,0.3);
  transition: all 0.3s ease;
}

.btn-main:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 12px 40px rgba(255,215,0,0.5);
}

/* ---------------- Cards for Patients/Nurses/Ambulances ---------------- */
.card-item {
  background: rgba(255,255,255,0.05);
  backdrop-filter: blur(12px);
  border-radius: 20px;
  padding: 20px;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card-item:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 40px rgba(255,215,0,0.3);
}

/* ---------------- Badges ---------------- */
.badge {
  font-weight: 600;
  padding: 6px 12px;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.badge.bg-success {
  background: #1a2f6f;
  color: #FFD700;
}

.badge.bg-danger {
  background: #b22222;
  color: #fff;
}

.badge.bg-secondary {
  background: #555;
  color: #fff;
}

/* ---------------- Modals ---------------- */
.modal-content {
  border-radius: 20px;
  border: none;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}

.modal-header, .modal-footer {
  border: none;
}

.modal-title {
  font-weight: 700;
  color: #FFD700;
}

/* ---------------- Animations ---------------- */
@keyframes fadeInUp {
  0% { opacity: 0; transform: translateY(30px); }
  100% { opacity: 1; transform: translateY(0); }
}

/* ---------------- Tables (Optional) ---------------- */
.table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 10px;
}

.table th {
  background: linear-gradient(135deg, #1a2f6f, #4682c2);
  color: #FFD700;
  font-weight: 600;
  border: none;
  text-align: center;
  padding: 12px;
}

.table td {
  background: rgba(255,255,255,0.05);
  backdrop-filter: blur(12px);
  border-radius: 12px;
  text-align: center;
  padding: 12px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.table td:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 25px rgba(255,215,0,0.3);
}

/* ---------------- Responsive ---------------- */
@media screen and (max-width: 1024px) {
  .dashboard { padding: 30px; }
  .section { padding: 25px; }
  .text-center img.profile-pic { width: 120px; height: 120px; }
}

@media screen and (max-width: 768px) {
  .dashboard { padding: 20px; margin: 20px; }
  .text-center img.profile-pic { width: 100px; height: 100px; }
  .section { padding: 20px; }
}

  </style>
</head>
<body>

<?php if (!$admin && !isset($_SESSION['admin_id'])): ?>
<div class="modal show d-block" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title">Enter Access Code</h5>
        </div>
        <div class="modal-body">
          <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
          <input type="text" name="access_code" class="form-control" placeholder="Access Code" required>
          <?php if ($error): ?>
            <div class="text-danger mt-3"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Enter Dashboard</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php else: ?>

<div class="dashboard">
  <div class="text-center mb-4">
    <img src="<?= htmlspecialchars($admin['profile_pic']) ?>" class="profile-pic" alt="Profile Picture">
    <h3 class="mt-3"><?= htmlspecialchars($admin['name']) ?></h3>
    <p class="text-muted"><?= htmlspecialchars($admin['hospital_name']) ?></p>
    <p><strong>County:</strong> <?= htmlspecialchars($admin['county']) ?></p>
    <div id="alert-area" class="mt-3"></div>
  </div>


  <div class="section" id="videoCallsSection">
  <h5>📞 Incoming Video Calls</h5>
  <ul class="list-group" id="incomingCallsList"></ul>
</div>

  <div id="incomingCallModals"></div>


  <!-- 🏥 Admit Patient Section -->
  <div class="section">
    <h5>🏥 Admit Patient</h5>
    <button class="btn-main" data-bs-toggle="modal" data-bs-target="#admitModal">Admit New Patient</button>
  </div>

  <!-- 📋 Admission Records Table -->
  <div class="section mt-5">
    <h5>📋 Admission Records</h5>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Patient</th>
          <th>Bed</th>
          <th>Admitted At</th>
          <th>Discharged At</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $stmt = $conn->prepare("
            SELECT a.id, u.name AS patient_name, e.bed_number, a.created_at, a.discharged_at
            FROM admissions a
            JOIN users u ON a.patient_id = u.id
            JOIN emergency_beds e ON a.bed_id = e.id
            WHERE a.hospital_name = ?
            ORDER BY a.created_at DESC
          ");
          $stmt->bind_param("s", $admin['hospital_name']);
          $stmt->execute();
          $admissions = $stmt->get_result();
          while ($admission = $admissions->fetch_assoc()):
        ?>
        <tr>
          <td><?= htmlspecialchars($admission['patient_name']) ?></td>
          <td>bed <?= htmlspecialchars($admission['bed_number']) ?></td>
          <td><?= htmlspecialchars($admission['created_at']) ?></td>
          <td><?= $admission['discharged_at'] ? htmlspecialchars($admission['discharged_at']) : '—' ?></td>
          <td>
            <?php if (!$admission['discharged_at']): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="admission_id" value="<?= $admission['id'] ?>">
                <button name="discharge" class="btn btn-danger btn-sm">Discharge</button>
              </form>
            <?php else: ?>
              <span class="badge bg-secondary">Discharged</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- 🧑‍⚕️ Manage Nurses Section -->
  <div class="section">
    <h5>🧑‍⚕️ Manage Nurses</h5>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <button class="btn-main" data-bs-toggle="modal" data-bs-target="#nurseModal">Add Nurse</button>

    <ul class="list-group mt-3">
      <?php
        $stmt = $conn->prepare("SELECT * FROM nurses WHERE hospital_name = ?");
        $stmt->bind_param("s", $admin['hospital_name']);
        $stmt->execute();
        $nurses = $stmt->get_result();
        while ($n = $nurses->fetch_assoc()):
      ?>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <?= htmlspecialchars($n['name']) ?> (<?= htmlspecialchars($n['email']) ?>)
        <span>
          <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignModal<?= $n['id'] ?>">Assign Patients</button>
          <span class="badge bg-primary">Access Code: <?= htmlspecialchars($n['access_code']) ?></span>
        </span>
      </li>

      <!-- Assign Patients Modal per nurse (remains here) -->
      <div class="modal fade" id="assignModal<?= $n['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
          <form method="POST">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Assign Patients to <?= htmlspecialchars($n['name']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <input type="hidden" name="assign_nurse_id" value="<?= $n['id'] ?>">
                <select name="assign_patient_id" class="form-control mb-3" required>
                  <option value="">Select Patient</option>
                  <?php
                    $stmt2 = $conn->prepare("SELECT id, name FROM users WHERE role = 'patient' AND hospital_name = ?");
                    $stmt2->bind_param("s", $admin['hospital_name']);
                    $stmt2->execute();
                    $patients = $stmt2->get_result();
                    while ($p = $patients->fetch_assoc()):
                  ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="modal-footer">
                <button type="submit" name="assign_patient" class="btn btn-success">Assign</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <?php endwhile; ?>
    </ul>
  </div>

  <!-- 🚑 Manage Ambulances Section -->
  <div class="section">
    <h5>🚑 Manage Ambulances</h5>
    <button class="btn-main mb-2" data-bs-toggle="modal" data-bs-target="#addAmbulanceModal">Add Ambulance</button>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Plate</th>
          <th>Driver</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $stmt = $conn->prepare("SELECT * FROM ambulances WHERE hospital_name = ?");
          $stmt->bind_param("s", $admin['hospital_name']);
          $stmt->execute();
          $ambulances = $stmt->get_result();
          while ($a = $ambulances->fetch_assoc()):
        ?>
        <tr>
          <td><?= htmlspecialchars($a['plate_number']) ?></td>
          <td><?= htmlspecialchars($a['driver_name']) ?></td>
          <td><span class="badge bg-<?= $a['status'] === 'dispatched' ? 'danger' : 'success' ?>">
            <?= ucfirst($a['status']) ?></span></td>
          <td>
            <form method="POST" class="d-inline">
              <input type="hidden" name="ambulance_id" value="<?= $a['id'] ?>">
              <button name="toggle_ambulance_status" class="btn btn-sm btn-outline-warning">
                <?= $a['status'] === 'available' ? 'Mark Dispatched' : 'Mark Available' ?>
              </button>
            </form>
            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
              <input type="hidden" name="delete_ambulance_id" value="<?= $a['id'] ?>">
              <button name="delete_ambulance" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- 🛏️ Manage Beds Section -->
  <div class="section mt-4">
    <h5>🛏️ Manage Emergency Beds</h5>
    <button class="btn-main mb-2" data-bs-toggle="modal" data-bs-target="#addBedModal">Add Bed</button>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Bed No</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $stmt = $conn->prepare("SELECT * FROM emergency_beds WHERE hospital_name = ?");
          $stmt->bind_param("s", $admin['hospital_name']);
          $stmt->execute();
          $beds = $stmt->get_result();
          while ($b = $beds->fetch_assoc()):
        ?>
        <tr>
          <td><?= htmlspecialchars($b['bed_number']) ?></td>
          <td><span class="badge bg-<?= $b['status'] === 'occupied' ? 'danger' : 'success' ?>"><?= ucfirst($b['status']) ?></span></td>
          <td>
            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this bed?')">
              <input type="hidden" name="delete_bed_id" value="<?= $b['id'] ?>">
              <button name="delete_bed" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Logout -->
  <div class="section text-center mt-5">
    <a href="logout.php" class="btn btn-danger">Logout</a>
  </div>

  <!-- Include all modals -->
  <?php include 'hospital_modals.php'; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let pendingRequests = [];

function speak(text) {
  const msg = new SpeechSynthesisUtterance(text);
  msg.lang = 'en-US';
  window.speechSynthesis.speak(msg);
}

function addAlertUI(request) {
  const alertId = `alert-${request.type}-${request.name}`;
  if (document.getElementById(alertId)) return;
  const alertDiv = document.createElement("div");
  alertDiv.id = alertId;
  alertDiv.className = "alert alert-danger blink mt-3";
  alertDiv.innerText = `🚨 Pending ${request.type} request from ${request.name}`;
  document.getElementById("alert-area").appendChild(alertDiv);
}

function updateRequests() {
  fetch(`check_requests.php?hospital=<?= urlencode($admin['hospital_name']) ?>`)
    .then(res => res.json())
    .then(data => {
      data.forEach(req => {
        const id = `${req.type}-${req.name}`;
        if (!pendingRequests.includes(id)) {
          pendingRequests.push(id);
          speak(`New ${req.type} request from ${req.name}`);
          addAlertUI(req);
        }
      });
    });
}

document.addEventListener("mousemove", () => {
  pendingRequests.forEach(id => {
    const [type, name] = id.split("-");
    speak(`Reminder: ${type} request from ${name}`);
  });
});

setInterval(updateRequests, 5000);

setInterval(() => {
  fetch('fetch_calls.php')
    .then(r => r.json())
    .then(calls => {
      if (!Array.isArray(calls)) return;
      let html = '';
      if (calls.length) {
        html += '<h5>Incoming Calls</h5><ul class="list-group">';
        calls.forEach(c => {
          html += `<li class="list-group-item d-flex justify-content-between align-items-center">
            <span>Emergency call (${c.status}) • ${new Date(c.created_at).toLocaleString()}</span>
            <a class="btn btn-sm btn-primary" href="${c.room_url}" target="_blank">Join</a>
          </li>`;
        });
        html += '</ul>';
      } else {
        html = '<div class="text-muted">No incoming calls.</div>';
      }
      document.getElementById('incomingCalls').innerHTML = html;
    });
}, 2000);


let activeCalls = new Set();

function speak(text) {
  const msg = new SpeechSynthesisUtterance(text);
  msg.lang = 'en-US';
  window.speechSynthesis.speak(msg);
}

function showCallModal(call) {
  // Skip if modal already exists
  if (document.getElementById('callModal-' + call.id)) return;

  // Create modal HTML
  const modalDiv = document.createElement('div');
  modalDiv.className = 'modal fade';
  modalDiv.id = 'callModal-' + call.id;
  modalDiv.tabIndex = -1;
  modalDiv.innerHTML = `
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Incoming Emergency Call</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Hospital emergency call received at ${new Date(call.created_at).toLocaleString()}</p>
        </div>
        <div class="modal-footer">
          <a href="${call.room_url}" class="btn btn-success">Accept</a>
          <button class="btn btn-danger reject-call-btn" data-call-id="${call.id}">Reject</button>
        </div>
      </div>
    </div>
  `;
  
  document.getElementById('incomingCallModals').appendChild(modalDiv);

  // Show the modal
  const bootstrapModal = new bootstrap.Modal(modalDiv);
  bootstrapModal.show();
  
  // speak alert
  speak('New emergency call received');
}

function fetchCalls() {
  fetch('fetch_calls.php')
    .then(res => res.json())
    .then(calls => {
      if (!Array.isArray(calls)) return;

      calls.forEach(call => {
        if (!activeCalls.has(call.id)) {
          activeCalls.add(call.id);
          showCallModal(call);
        }
      });

      // Remove ended calls
      const endedCalls = Array.from(activeCalls).filter(id => !calls.find(c => c.id === id));
      endedCalls.forEach(id => activeCalls.delete(id));
    });
}

// Reject button handler
document.addEventListener('click', function(e){
  if(e.target && e.target.classList.contains('reject-call-btn')){
    const callId = e.target.getAttribute('data-call-id');
    fetch('reject_call.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({id: callId})
    }).then(() => {
      // Close the modal
      const modalEl = document.getElementById('callModal-' + callId);
      const modal = bootstrap.Modal.getInstance(modalEl);
      if(modal) modal.hide();
      modalEl.remove();
      activeCalls.delete(parseInt(callId));
    });
  }
});

setInterval(fetchCalls, 2000);
fetchCalls();

</script>

<?php endif; ?>
</body>
</html>
