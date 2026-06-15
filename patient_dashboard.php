<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
  header("Location: patient_login.php");
  exit();
}

include 'config.php';
$id = $_SESSION['patient_id'];

// Fetch patient data
$stmt = $conn->prepare("SELECT name, profile_pic, county FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

// Fetch patient appointments
$appt_stmt = $conn->prepare("
  SELECT a.*, d.name AS doctor_name 
  FROM appointments a 
  JOIN users d ON a.doctor_id = d.id 
  WHERE a.patient_id = ?
  ORDER BY a.appointment_time DESC
");
$appt_stmt->bind_param("i", $id);
$appt_stmt->execute();
$appt_result = $appt_stmt->get_result();

$notif_stmt = $conn->prepare("SELECT * FROM notifications WHERE patient_id = ? ORDER BY created_at DESC");
$notif_stmt->bind_param("i", $id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// Count unread notifications
$unread_stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE patient_id = ? AND is_read = 0");
$unread_stmt->bind_param("i", $id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_data = $unread_result->fetch_assoc();
$unread_count = $unread_data['unread_count'];

$notification_query = $conn->prepare("
  SELECT `condition`, hospital_name, status, admin_response, responded_at 
  FROM ambulance_requests 
  WHERE patient_id = ? 
    AND status != 'pending' 
    AND admin_response IS NOT NULL 
    AND admin_response != ''
  ORDER BY responded_at DESC
");
$notification_query->bind_param("i", $id);
$notification_query->execute();
$notif_result = $notification_query->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patient Dashboard | DoctorsCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>/* ---------------- General ---------------- */
body {
  font-family: 'Inter', sans-serif;
  margin:0; padding:0;
  background: linear-gradient(135deg, #0f2027, #203a43);
  color: #fff;
  overflow-x: hidden;
  transition: background 0.5s ease;
}

/* ---------------- Sidebar ---------------- */
.sidebar {
  position: fixed;
  top:0; left:0;
  width: 250px;
  height: 100vh;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(15px);
  padding: 30px 20px;
  display: flex; flex-direction: column; gap:15px;
  transition: transform 0.4s ease;
  z-index:1000;
}
.sidebar.active { transform: translateX(-100%); }

.sidebar h2 {
  font-size:1.8rem;
  color:#00f0ff;
  text-align:center;
  margin-bottom:30px;
}

.sidebar a {
  display:flex;
  align-items:center;
  gap:10px;
  color:#fff;
  text-decoration:none;
  padding:12px 15px;
  border-radius:12px;
  transition: all 0.3s ease;
  background: rgba(255,255,255,0.05);
}

.sidebar a:hover {
  background: linear-gradient(135deg,#00f0ff,#ff3cac);
  transform: translateX(5px);
  box-shadow:0 8px 25px rgba(0,240,255,0.3);
}

/* ---------------- Sidebar Toggle ---------------- */
.sidebar-toggle {
  position: fixed;
  top:20px; left:260px;
  z-index:2000;
  background:#00f0ff;
  color:#000;
  border:none;
  padding:10px 14px;
  border-radius:12px;
  cursor:pointer;
  transition: all 0.3s ease;
}
.sidebar-toggle:hover { background:#ff3cac; transform: scale(1.1); }

/* ---------------- Main Content ---------------- */
.main-content {
  margin-left:270px;
  padding:30px;
  transition: margin-left 0.4s ease;
}
.main-content.sidebar-hidden { margin-left:50px; }

/* ---------------- Cards ---------------- */
.card-container { display:flex; flex-wrap:wrap; gap:20px; }
.card {
  background: rgba(255,255,255,0.05);
  backdrop-filter: blur(12px);
  border-radius:20px;
  padding:20px;
  flex:1 1 250px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  cursor:pointer;
}
.card:hover {
  transform: translateY(-8px) scale(1.03);
  box-shadow: 0 20px 50px rgba(0,240,255,0.3);
}
.card h3 { color:#00f0ff; margin-bottom:10px; }
.card p { color:#cfd8dc; font-size:0.95rem; }

/* ---------------- Buttons ---------------- */
button, .btn {
  background: linear-gradient(135deg,#00f0ff,#ff3cac);
  color:#000;
  padding:12px 25px;
  border:none;
  border-radius:12px;
  font-weight:600;
  cursor:pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  position: relative;
  overflow:hidden;
}
button:hover, .btn:hover {
  transform: translateY(-3px) scale(1.05);
  box-shadow: 0 12px 30px rgba(255,60,172,0.4);
}
button::after, .btn::after {
  content:'';
  position:absolute;
  width:100%; height:100%;
  top:0; left:-100%;
  background: rgba(255,255,255,0.1);
  transition:0.5s;
  z-index:0;
}
button:hover::after, .btn:hover::after { left:0; }
button, .btn { z-index:1; }

/* ---------------- Profile ---------------- */
.profile-section {
  background: rgba(255,255,255,0.05);
  backdrop-filter: blur(12px);
  border-radius:15px;
  padding:20px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-bottom:30px;
  box-shadow:0 8px 30px rgba(0,0,0,0.05);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.profile-section:hover { transform: translateY(-5px); box-shadow:0 12px 40px rgba(0,0,0,0.1); }
.profile-section img { width:60px; height:60px; border-radius:50%; border:3px solid #00f0ff; transition: transform 0.3s ease;}
.profile-section img:hover { transform: scale(1.1); }

/* ---------------- Animations ---------------- */
@keyframes fadeInUp { 0% {opacity:0; transform:translateY(20px);} 100% {opacity:1; transform:translateY(0);} }
.card, .profile-section { animation: fadeInUp 0.5s ease forwards; }

/* ---------------- Responsive ---------------- */
@media screen and (max-width:768px){
  .sidebar { transform: translateX(-100%);}
  .main-content { margin-left:0;}
  .sidebar-toggle { left:20px;}
  .card-container { flex-direction: column; }
}
</style>
</head>
<body>
  <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>

  <div class="sidebar">
    <h4>MultiverseCare</h4>
    <a href="#" data-bs-toggle="modal" data-bs-target="#notifModal">
      🔔 Notifications
      <?php if ($unread_count > 0): ?>
        <span class="badge bg-danger"><?= $unread_count ?></span>
      <?php endif; ?>
    </a>

    <a href="#">Dashboard</a>
    <a href="#" data-bs-toggle="modal" data-bs-target="#medicalRecordsModal">Medical Records</a>
    <a href="#" data-bs-toggle="modal" data-bs-target="#doctorModal">Book Doctor</a>

    <a href="video_call.php?hospital=<?= urlencode($patient['hospital_name'] ?? 'Reale') ?>" target="_blank" class="btn btn-danger">
    📞 Start Emergency Video Call
    </a>


    <a href="support_center.php">Mental Support</a>
    <a href="diagnose.php">Self Diagnosis</a>
    <a href="logout.php">Logout</a>
  </div>

  <div class="main-content">
    <div class="profile-section d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center">
        <img src="<?= htmlspecialchars($patient['profile_pic']) ?>" alt="Profile">
        <div class="ms-3">
          <h5>Welcome, <?= htmlspecialchars($patient['name']) ?></h5>
          <small class="text-muted">County: <?= htmlspecialchars($patient['county']) ?></small>
        </div>
      </div>
      <a href="logout.php" class="btn btn-outline-danger">Logout</a>
    </div>

    
    <div id="hospitalListContainer"></div>

    <div class="section-box">
      <h5>📝 Share Your Experience</h5>
      <button class="btn-main" data-bs-toggle="modal" data-bs-target="#reviewModal">Leave a Review</button>
    </div>

    <div class="section-box">
      <h5>📅 My Appointments</h5>
      <?php if ($appt_result->num_rows > 0): ?>
        <ul class="list-group">
          <?php while ($appt = $appt_result->fetch_assoc()): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <strong>Doctor:</strong> Dr. <?= htmlspecialchars($appt['doctor_name']) ?><br>
                <strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($appt['appointment_time'])) ?><br>
                <strong>Reason:</strong> <?= htmlspecialchars($appt['reason']) ?><br>
              </div>
              <span class="badge 
                <?= $appt['status'] === 'approved' ? 'bg-success' : ($appt['status'] === 'denied' ? 'bg-danger' : 'bg-warning') ?>">
                <?= ucfirst($appt['status']) ?>
              </span>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <p class="text-muted">You haven't requested any appointments yet.</p>
      <?php endif; ?>
    </div>

    <a href="https://mydawa.com" target="_blank" rel="noopener noreferrer">
      Order Medicine via MyDawa
    </a>

    <div class="section-box">
      <h5>💊 Diagnose from Prescription</h5>
      <button class="btn-main" data-bs-toggle="modal" data-bs-target="#drugDiagnosisModal">Diagnose Now</button>
    </div>
  </div>

  <?php
    // prepare data needed by modals BEFORE including them
    $patient_id = $_SESSION['patient_id'];
    $records = $conn->prepare("SELECT * FROM medical_records WHERE patient_id = ? ORDER BY created_at DESC");
    $records->bind_param("i", $patient_id);
    $records->execute();
    $results = $records->get_result();

    // include ALL modals (moved out to keep this file clean)
    include 'patient_modals.php';
  ?>

  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script >
document.getElementById('submitBtn').innerHTML = 'Sending...';

document.getElementById('notifModal').addEventListener('shown.bs.modal', function () {
  fetch('mark_notifications_read.php')
    .then(() => {
      // Remove the badge since notifications are read
      const badge = document.querySelector('.sidebar .badge');
      if (badge) badge.remove();
    });
});



function openAppointmentModal(doctorName, doctorId) {
  document.getElementById('appointmentDoctorId').value = doctorId;
  new bootstrap.Modal(document.getElementById('appointmentModal')).show();
}



function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const mainContent = document.querySelector('.main-content');

  sidebar.classList.toggle('active');

  if (sidebar.classList.contains('active')) {
    // Sidebar is hidden
    mainContent.classList.add('sidebar-hidden');
  } else {
    // Sidebar visible
    mainContent.classList.remove('sidebar-hidden');
  }
}


  document.getElementById('searchInput').addEventListener('input', function () {
    const query = this.value.toLowerCase();
    document.querySelectorAll('.doctor-card').forEach(card => {
      const text = card.innerText.toLowerCase();
      card.style.display = text.includes(query) ? 'flex' : 'none';
    });
  });

  let chatWith = '';
  let chatInterval;

  function openChatModal(name, email) {
    chatWith = email;
    document.getElementById('chatDoctorName').innerText = name;
    document.getElementById('chatBox').innerHTML = 'Loading chat...';
    fetchChatMessages();

    const modal = new bootstrap.Modal(document.getElementById('chatModal'));
    modal.show();

    clearInterval(chatInterval);
    chatInterval = setInterval(fetchChatMessages, 4000);
  }

  function fetchChatMessages() {
    fetch('chat_fetch.php?doctor_email=' + encodeURIComponent(chatWith))
      .then(res => res.text())
      .then(data => {
        document.getElementById('chatBox').innerHTML = data;
        const chatBox = document.getElementById('chatBox');
        chatBox.scrollTop = chatBox.scrollHeight;
      });
  }

  function sendChatMessage() {
    const message = document.getElementById('chatMessage').value;
    if (!message.trim()) return;

    fetch('chat_send.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `doctor_email=${encodeURIComponent(chatWith)}&message=${encodeURIComponent(message)}`
    })
    .then(() => {
      document.getElementById('chatMessage').value = '';
      fetchChatMessages();
    });
  }



  function openModal(id) {
    document.getElementById(id).style.display = 'block';
  }

  function closeModal(id) {
    document.getElementById(id).style.display = 'none';
  }

  window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
      if (event.target === modal) {
        modal.style.display = "none";
      }
    });
  };

 
  document.getElementById('diagnosisForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = e.target;
    const selected = Array.from(form.querySelector('#drugSelect').selectedOptions).map(opt => opt.value);

    fetch('drug_diagnosis.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ 'drugs[]': selected })
    })

    .then(res => res.text())
    .then(data => {
      const resultDiv = document.getElementById('diagnosisResult');
      resultDiv.style.display = 'block';
      resultDiv.innerHTML = data;
    });
  });



function showHospitalList(type) {
  fetch(`get_available_hospitals.php?type=${type}`)
    .then(res => res.json())
    .then(data => {
      let listHtml = '<h4>Select a Hospital:</h4>';
      data.forEach(hospital => {
        listHtml += `
          <div style="margin-bottom:10px;">
            <button onclick="bookEmergency('${type}', '${hospital.id}', '${hospital.name}')">
              ${hospital.name} (${hospital.location})
            </button>
          </div>
        `;
      });
      document.getElementById('hospitalListContainer').innerHTML = listHtml;
    });
}

function bookEmergency(type, hospitalId, hospitalName) {
  fetch('send_emergency_request.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ type, hospitalId })
  })
  .then(res => res.text())
  .then(data => {
    alert(`Request sent to ${hospitalName}`);
    // Open video call link (e.g., Jitsi)
    window.location.href = `video_call.php?hospital_id=${hospitalId}`;

  });
}

function getHospitals() {
  const type = document.getElementById('requestType').value;
  const listDiv = document.getElementById('hospitalList');
  listDiv.innerHTML = 'Loading...';

  fetch(`get_available_hospitals.php?type=${type}`)
    .then(response => response.json())
    .then(data => {
      if (data.length === 0) {
        listDiv.innerHTML = 'No hospitals available at the moment.';
        return;
      }

      listDiv.innerHTML = '<h4>Select Hospital:</h4>';
      data.forEach(hospital => {
        const btn = document.createElement('button');
        btn.innerText = `${hospital.name} - ${hospital.location}`;
        btn.onclick = () => {
          // Trigger video call (Jitsi Meet or similar)
          const meetRoom = `multiversecare-${hospital.id}-${Date.now()}`;
          const link = `https://meet.jit.si/${meetRoom}`;
          window.open(link, '_blank');
        };
        btn.style.display = 'block';
        btn.style.margin = '10px 0';
        listDiv.appendChild(btn);
      });
    })
    .catch(err => {
      listDiv.innerHTML = 'Error fetching hospitals.';
      console.error(err);
    });
}

btn.onclick = () => {
  const meetRoom = `multiversecare-${hospital.id}-${Date.now()}`;
  const link = `https://meet.jit.si/${meetRoom}`;
  // Save the request + meet link to DB
  fetch('save_emergency_call.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      hospital_id: hospital.id,
      type: type,
      meet_link: link
    })
  });
  window.open(link, '_blank');
};


function callForHelp() {
  const data = new URLSearchParams();
  data.append('name', patientName);
  data.append('phone', patientPhone);
  data.append('hospital', selectedHospital);
  data.append('reason', 'Urgent emergency call');

  fetch('send_call.php', {
    method: 'POST',
    body: data
  }).then(res => res.text())
    .then(res => {
      if (res === 'success') {
        // Optionally launch Jitsi here
        alert("Call sent to hospital.");
        startJitsiCall(); 
      }
    });
}


function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    sidebar.classList.toggle('active');
    mainContent.classList.toggle('sidebar-hidden');
}

// Fade in all section boxes on load
document.querySelectorAll('.section-box, .profile-section').forEach(el => {
    el.style.opacity = 0;
    el.style.transform = 'translateY(20px)';
    setTimeout(() => {
        el.style.transition = 'all 0.6s ease';
        el.style.opacity = 1;
        el.style.transform = 'translateY(0)';
    }, 100);
});


</script>
</body>
</html>
