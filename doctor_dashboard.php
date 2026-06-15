<?php
session_start();
include 'config.php';

$doctor = null;
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'], $_POST['access_code'])) {
  $email = trim($_POST['email']);
  $access_code = $_POST['access_code'];

  $stmt = $conn->prepare("SELECT * FROM users WHERE role = 'doctor' AND email = ? LIMIT 1");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    if ($access_code === $row['access_code'])
 {
      $doctor = $row;
      $_SESSION['doctor_id'] = $doctor['id'];
    } else {
      $error = "❌ Invalid access code.";
    }
  } else {
    $error = "❌ No doctor found with that email.";
  }
}

if (!$doctor && isset($_SESSION['doctor_id'])) {
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->bind_param("i", $_SESSION['doctor_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  $doctor = $result->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_bio'])) {
  $bio = trim($_POST['bio']);
  $stmt = $conn->prepare("UPDATE users SET bio = ? WHERE id = ?");
  $stmt->bind_param("si", $bio, $_SESSION['doctor_id']);
  $stmt->execute();
  $doctor['bio'] = $bio;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_schedule'])) {
  $stmt = $conn->prepare("DELETE FROM working_hours WHERE doctor_id = ?");
  $stmt->bind_param("i", $_SESSION['doctor_id']);
  $stmt->execute();
  
  $days = $_POST['day'];
  $start_times = $_POST['start_time'];
  $end_times = $_POST['end_time'];

  $stmt = $conn->prepare("INSERT INTO working_hours (doctor_id, day, start_time, end_time) VALUES (?, ?, ?, ?)");
  for ($i = 0; $i < count($days); $i++) {
    $stmt->bind_param("isss", $_SESSION['doctor_id'], $days[$i], $start_times[$i], $end_times[$i]);
    $stmt->execute();
  }
}
?>


<!DOCTYPE html>
<html>
<head>
  <title>Doctor Dashboard | DoctorsCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
 <style>
/* ---------------- General ---------------- */
body {
  font-family: 'Inter', sans-serif;
  margin: 0;
  padding: 0;
  background: linear-gradient(135deg,#0f2027,#203a43);
  color: #fff;
  overflow-x: hidden;
}

/* ---------------- Profile Picture ---------------- */
.profile-pic {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #00f0ff;
  box-shadow: 0 0 15px rgba(0,240,255,0.4);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.profile-pic:hover {
  transform: scale(1.1);
  box-shadow: 0 0 25px rgba(255,60,172,0.5);
}

/* ---------------- Sections ---------------- */
.section {
  background: rgba(255,255,255,0.05);
  backdrop-filter: blur(12px);
  border-radius: 15px;
  padding: 18px;
  margin: 18px 0;
  box-shadow: 0 8px 30px rgba(0,240,255,0.2);
  animation: fadeInUp 0.5s ease forwards;
}

/* ---------------- Buttons ---------------- */
button, .btn, .btn-main {
  background: linear-gradient(135deg,#00f0ff,#ff3cac);
  color: #000;
  border: none;
  padding: 10px 22px;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
button:hover, .btn:hover, .btn-main:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 12px 30px rgba(255,60,172,0.4);
}

/* ---------------- Availability Toggle ---------------- */
#availabilityToggle {
  width: 40px;
  height: 20px;
  cursor: pointer;
  accent-color: #00f0ff;
}

/* ---------------- Cards & Tables ---------------- */
table {
  background: rgba(255,255,255,0.05);
  backdrop-filter: blur(12px);
  border-radius: 12px;
  overflow: hidden;
}
table th, table td {
  vertical-align: middle;
  color: #fff;
}
.status-badge {
  padding: 0.25em 0.5em;
  border-radius: 10px;
  font-size: 0.8rem;
}

/* ---------------- Chat Modal ---------------- */
#chatBox {
  background: #f0f2f5;
  border-radius: 12px;
}
#chatBox div {
  padding: 6px 10px;
  border-radius: 12px;
  margin-bottom: 6px;
}
.bg-primary { background: #00f0ff !important; color: #000 !important; }
.bg-light { background: #fff !important; color: #000 !important; }

/* ---------------- Update Profile Modal ---------------- */
.modal-content {
  border-radius: 15px;
  backdrop-filter: blur(15px);
  background: rgba(0,0,0,0.75);
}

/* ---------------- Animations ---------------- */
@keyframes fadeInUp {
  0% { opacity: 0; transform: translateY(20px); }
  100% { opacity: 1; transform: translateY(0); }
}

/* ---------------- Responsive ---------------- */
@media screen and (max-width: 768px) {
  .section { padding: 15px; margin: 12px 0; }
  .profile-pic { width: 100px; height: 100px; }
  table th, table td { font-size: 0.85rem; }
  .btn, .btn-main, button { width: 100%; text-align: center; margin-bottom: 8px; }
  #chatModal .modal-dialog { max-width: 95%; }
}

</style>

</head>
<body>

<?php if (!$doctor && !isset($_SESSION['doctor_id'])): ?>
<!-- Access Code Modal -->
<div class="modal show d-block" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title">Doctor Login</h5>
        </div>
        <div class="modal-body">
          <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
          <input type="text" name="access_code" class="form-control" placeholder="Access Code" required>
          <?php if ($error): ?>
            <div class="text-danger mt-2"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Enter Dashboard</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php else: 
  if (!$doctor && isset($_SESSION['doctor_id'])) {
    // Fetch doctor data from session ID
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['doctor_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();
  }
?>

<!-- Dashboard -->
<div class="dashboard">
  <div class="text-center">
   <img src="<?= htmlspecialchars($doctor['profile_pic'] ?? 'default.jpg') ?>" class="profile-pic" alt="Doctor Profile">

<h3 class="mt-3"><?= htmlspecialchars($doctor['name'] ?? 'Dr. Unknown') ?></h3>

<p class="text-muted">
  <?= htmlspecialchars($doctor['specialty'] ?? 'Specialty not set') ?> |
  <?= htmlspecialchars($doctor['hospital_name'] ?? 'Hospital not set') ?>
</p>

<p><strong>County:</strong> <?= htmlspecialchars($doctor['county'] ?? 'Not set') ?></p>

<input class="form-check-input" type="checkbox" id="availabilityToggle"
  <?= isset($doctor['is_available']) && $doctor['is_available'] ? 'checked' : '' ?>>

<label class="form-check-label" for="availabilityToggle" id="availabilityLabel">
  <?= isset($doctor['is_available']) && $doctor['is_available'] ? 'Available for Consultations' : 'Currently Unavailable' ?>
</label>


</div>

</div>

<div class="section text-end">
  <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#chatModal">
    <i class="fas fa-comments"></i> Open Chat
  </button>
</div>

<div class="section">
  <h5>📝 Update Biography</h5>
  <form method="POST">
    <textarea name="bio" class="form-control mb-2" rows="4" placeholder="Write something about yourself..."><?= htmlspecialchars($doctor['bio'] ?? '') ?></textarea>
    <button class="btn btn-main" type="submit" name="update_bio">Save Bio</button>
  </form>
</div>

<div class="section">
  <h5>⏰ Update Working Hours</h5>
  <form method="POST">
    <div id="scheduleFields">
      <?php
      $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
      foreach ($days as $day): ?>
        <div class="row mb-2">
          <div class="col-md-3"><strong><?= $day ?></strong></div>
          <div class="col-md-4">
            <input type="hidden" name="day[]" value="<?= $day ?>">
            <input type="time" name="start_time[]" class="form-control" required>
          </div>
          <div class="col-md-4">
            <input type="time" name="end_time[]" class="form-control" required>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-main mt-2" type="submit" name="update_schedule">Save Schedule</button>
  </form>
</div>

<div class="section">
  <h5>📅 My Appointments</h5>
  <div class="table-responsive">
    <table class="table table-bordered align-middle table-striped table-sm">
      <thead class="table-light">
        <tr>
          <th>Patient</th>
          <th>Time</th>
          <th>Reason</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="appointmentTable">
        <?php
          $stmt = $conn->prepare("
            SELECT a.*, u.name AS patient_name 
            FROM appointments a 
            JOIN users u ON a.patient_id = u.id 
            WHERE a.doctor_id = ?
            ORDER BY a.appointment_time ASC
          ");
          $stmt->bind_param("i", $doctor['id']);
          $stmt->execute();
          $result = $stmt->get_result();
          if ($result->num_rows === 0) {
            echo '<tr><td colspan="4" class="text-muted text-center">No appointments yet.</td></tr>';
          } else {
            while ($row = $result->fetch_assoc()):
        ?>
        <tr data-id="<?= $row['id'] ?>">
          <td><?= htmlspecialchars($row['patient_name']) ?></td>
          <td><?= date('M d, Y H:i', strtotime($row['appointment_time'])) ?></td>
          <td><?= htmlspecialchars($row['reason']) ?></td>
          <td>
            <select class="form-select form-select-sm status-dropdown" data-id="<?= $row['id'] ?>">
              <option value="pending" <?= $row['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="confirmed" <?= $row['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
              <option value="cancelled" <?= $row['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <span class="badge mt-2 status-badge bg-<?= $row['status'] === 'confirmed' ? 'success' : ($row['status'] === 'cancelled' ? 'danger' : 'secondary') ?>">
              <?= ucfirst($row['status']) ?>
            </span>
          </td>
        </tr>
        <?php endwhile; } ?>
      </tbody>
    </table>
  </div>
</div>



  <div class="section">
    <h5>📸 Update Profile Picture</h5>
  
<button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#updatePicModal">Update</button>

  </div>

  <div class="section">
    <a href="logout.php" class="btn btn-danger">Logout</a>
  </div>
</div>
<?php endif; ?>



<!-- Modern Chat Modal -->
<div class="modal fade" id="chatModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="height: 80vh;">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">💬 Doctor Chat</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0 d-flex">

        <!-- Sidebar: Patient List -->
        <div class="border-end bg-light" style="width: 280px; overflow-y: auto;">
          <div class="list-group list-group-flush" id="patientList">
            <?php
              $stmt = $conn->prepare("SELECT DISTINCT u.id, u.name, u.profile_pic FROM chat_messages c JOIN users u ON c.patient_id = u.id WHERE c.doctor_email = ?");
              $stmt->bind_param("s", $doctor['email']);
              $stmt->execute();
              $res = $stmt->get_result();
              while ($row = $res->fetch_assoc()):
            ?>
              <button class="list-group-item list-group-item-action d-flex align-items-center" onclick="loadChat(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>', '<?= $row['profile_pic'] ?>')">
                <img src="<?= htmlspecialchars($row['profile_pic']) ?>" class="rounded-circle me-2" style="width: 36px; height: 36px; object-fit: cover;">
                <span><?= htmlspecialchars($row['name']) ?></span>
              </button>
            <?php endwhile; ?>
          </div>
        </div>

        <!-- Chat Area -->
        <div class="flex-fill d-flex flex-column">
          <!-- Patient Info -->
          <div class="border-bottom p-3 d-flex align-items-center" id="chatHeader" style="min-height: 70px;">
            <span class="text-muted">Select a patient to start chatting.</span>
          </div>

          <!-- Messages -->
          <div id="chatBox" class="flex-fill p-3 overflow-auto" style="background: #f0f2f5;"></div>

          <!-- Send Form -->
          <form id="sendMessageForm" class="p-3 border-top bg-white d-flex">
            <input type="hidden" name="doctor_email" value="<?= htmlspecialchars($doctor['email']) ?>">
            <input type="hidden" name="patient_id" id="chatPatientId">
            <input type="text" name="message" class="form-control me-2" placeholder="Type a message..." required>
            <button class="btn btn-primary" type="submit">Send</button>
          </form>
        </div>

      </div>
    </div>
  </div>
</div>



<!-- 📸 Update Profile Picture Modal -->
<div class="modal fade" id="updatePicModal" tabindex="-1">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <form action="update_profile_pic.php" method="POST" enctype="multipart/form-data">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Update Profile Picture</h5>
          <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="file" name="profile_pic" class="form-control" accept="image/*" required>
          <small class="text-muted">Only JPG, PNG, or JPEG under 2MB allowed</small>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="submit">Upload</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>




function loadChat(patientId, name, pic) {
  // Set current patient ID
  document.getElementById('chatPatientId').value = patientId;

  // Update header with profile
  document.getElementById('chatHeader').innerHTML = `
    <img src="${pic}" class="rounded-circle me-2" style="width:40px; height:40px; object-fit:cover;">
    <strong>${name}</strong>
  `;

  // Load messages
  fetch(`get_chat_messages.php?patient_id=${patientId}&doctor_email=<?= urlencode($doctor['email']) ?>`)
    .then(res => res.json())
    .then(messages => {
      const chatBox = document.getElementById('chatBox');
      chatBox.innerHTML = '';
      if (messages.length === 0) {
        chatBox.innerHTML = '<p class="text-muted">No messages yet.</p>';
        return;
      }

      messages.forEach(msg => {
        const div = document.createElement('div');
        div.className = 'mb-2 d-flex';
        div.style.justifyContent = msg.sender === 'doctor' ? 'flex-end' : 'flex-start';

        div.innerHTML = `
          <div class="p-2 px-3 rounded-3 ${msg.sender === 'doctor' ? 'bg-primary text-white' : 'bg-light text-dark'}" style="max-width: 70%;">
            ${msg.message}<br>
            <small class="text-white-50">${msg.sent_at}</small>
          </div>
        `;
        chatBox.appendChild(div);
      });

      chatBox.scrollTop = chatBox.scrollHeight;
    });
}

document.getElementById('sendMessageForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const patientId = document.getElementById('chatPatientId').value;

  fetch('doctor_send.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(response => {
    if (response.trim() === 'success') {
      form.reset();
      loadChat(patientId); // reload chat
    } else {
      alert('❌ Message not sent.');
    }
  });
});

document.getElementById('availabilityToggle').addEventListener('change', function () {
  const isChecked = this.checked ? 1 : 0;
  fetch('update_availability.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'status=' + isChecked
  })
  .then(res => res.text())
  .then(response => {
    const label = document.getElementById('availabilityLabel');
    if (isChecked) {
      label.textContent = 'Available for Consultations';
    } else {
      label.textContent = 'Currently Unavailable';
    }
  });
});


document.querySelectorAll('.status-dropdown').forEach(dropdown => {
  dropdown.addEventListener('change', function () {
    const appointmentId = this.dataset.id;
    const newStatus = this.value;
    const badge = this.nextElementSibling;

    fetch('update_appointment_status.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: `appointment_id=${appointmentId}&status=${encodeURIComponent(newStatus)}`
    })
    .then(res => res.text())
    .then(response => {
      if (response.trim() === 'success') {
        // Update badge color and text
        badge.className = 'badge mt-2 status-badge bg-' + 
          (newStatus === 'confirmed' ? 'success' : (newStatus === 'cancelled' ? 'danger' : 'secondary'));
        badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
      } else {
        alert('❌ Failed to update appointment status.');
      }
    });
  });
});



</script>


</body>
</html>
