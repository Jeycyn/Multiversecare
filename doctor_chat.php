<?php
session_start();
include 'config.php';

if (!isset($_SESSION['doctor_id'])) {
  header("Location: index.php");
  exit();
}

$doctor_id = $_SESSION['doctor_id'];
$stmt = $conn->prepare("SELECT email, name, hospital_name FROM users WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();
$doctor_email = $doctor['email'];
?>

<!DOCTYPE html>
<html>
<head>
  <title>Doctor Chat | DoctorsCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f0f4f8;
      padding: 20px;
      font-family: 'Segoe UI', sans-serif;
    }
    h4 {
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 25px;
    }
    .chat-box {
      height: 420px;
      overflow-y: auto;
      background: #ffffff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07);
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .msg {
      padding: 10px 16px;
      border-radius: 18px;
      font-size: 0.95rem;
      max-width: 70%;
      line-height: 1.4;
    }
    .patient {
      background-color: #e3f2fd;
      color: #333;
      align-self: flex-start;
      border: 1px solid #bbdefb;
    }
    .doctor {
      background-color: #d0f0c0;
      color: #2e7d32;
      align-self: flex-end;
      text-align: right;
      border: 1px solid #a5d6a7;
    }
    .form-select, .form-control, .btn {
      border-radius: 10px;
    }
    .input-group .form-control {
      border-top-left-radius: 10px;
      border-bottom-left-radius: 10px;
    }
    .input-group .btn {
      border-top-right-radius: 10px;
      border-bottom-right-radius: 10px;
    }
    .modal-content {
      border-radius: 14px;
      box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
    }
    .modal-header {
      border-bottom: none;
    }
    .modal-title {
      font-weight: 600;
      font-size: 1.2rem;
    }
    .modal-body label {
      font-weight: 500;
      margin-bottom: 5px;
    }
    .modal-body textarea {
      border-radius: 10px;
      resize: vertical;
    }
    .alert {
      border-radius: 8px;
    }
    .text-muted small {
      font-size: 0.75rem;
    }
  </style>
</head>
<body>

<div class="container">
  <h4>💬 Chat Dashboard for Dr. <?= htmlspecialchars($doctor['name']) ?></h4>

  <form method="GET" class="mb-3">
    <label>Select patient:</label>
    <select name="patient_id" class="form-select" onchange="this.form.submit()" required>
      <option value="">-- Choose Patient --</option>
      <?php
      $res = $conn->prepare("SELECT DISTINCT u.id, u.name 
        FROM chat_messages c 
        JOIN users u ON c.patient_id = u.id 
        WHERE c.doctor_email = ?");
      $res->bind_param("s", $doctor_email);
      $res->execute();
      $r = $res->get_result();
      while ($row = $r->fetch_assoc()):
      ?>
        <option value="<?= $row['id'] ?>" <?= isset($_GET['patient_id']) && $_GET['patient_id'] == $row['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($row['name']) ?>
        </option>
      <?php endwhile; ?>
    </select>
  </form>

  <?php if (isset($_GET['patient_id'])):
    $patient_id = intval($_GET['patient_id']);
    $msgs = $conn->prepare("SELECT * FROM chat_messages WHERE patient_id = ? AND doctor_email = ? ORDER BY sent_at ASC");
    $msgs->bind_param("is", $patient_id, $doctor_email);
    $msgs->execute();
    $chats = $msgs->get_result();
  ?>
  <div class="chat-box mb-3 chat-wrapper">
    <?php while ($chat = $chats->fetch_assoc()): ?>
      <div class="msg <?= $chat['sender'] === 'doctor' ? 'doctor' : 'patient' ?>">
        <?= htmlspecialchars($chat['message']) ?><br>
        <small class="text-muted"><?= date('H:i', strtotime($chat['sent_at'])) ?></small>
      </div>
    <?php endwhile; ?>
  </div>

  <div class="d-flex gap-2 mb-3">
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#writeRecordModal" data-patient-id="<?= $patient_id ?>">
      ✍️ Write Medical Record
    </button>
    <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#medicalRecordModal">
      📖 View Medical Records
    </button>
  </div>

  <form method="POST" action="doctor_send.php">
    <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
    <input type="hidden" name="doctor_email" value="<?= $doctor_email ?>">
    <div class="input-group">
      <input type="text" name="message" class="form-control" placeholder="Type your reply..." required>
      <button class="btn btn-primary">Send</button>
    </div>
  </form>
  <?php endif; ?>
</div>

<?php if (isset($_GET['patient_id'])):
  $records = $conn->prepare("SELECT * FROM medical_records WHERE patient_id = ? ORDER BY created_at DESC");
  $records->bind_param("i", $patient_id);
  $records->execute();
  $results = $records->get_result();
?>
<div class="modal fade" id="writeRecordModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="writeRecordForm" method="POST">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">📝 Write Medical Record</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="writeRecordMsg" class="alert d-none"></div>
          <input type="hidden" name="patient_id" id="recordPatientId" value="<?= $patient_id ?>">
          <input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">
          <input type="hidden" name="doctor_email" value="<?= htmlspecialchars($doctor['email']) ?>">
          <input type="hidden" name="hospital_name" value="<?= htmlspecialchars($doctor['hospital_name'] ?? 'Unknown') ?>">
          <div class="mb-3">
            <label>Diagnosis</label>
            <textarea name="diagnosis" class="form-control" required></textarea>
          </div>
          <div class="mb-3">
            <label>Treatment</label>
            <textarea name="treatment" class="form-control" required></textarea>
          </div>
          <div class="mb-3">
            <label>Notes</label>
            <textarea name="notes" class="form-control"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="medicalRecordModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">📖 Medical Records for Patient</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if ($results->num_rows > 0): ?>
          <?php while ($rec = $results->fetch_assoc()): ?>
            <?php
              $doc_stmt = $conn->prepare("SELECT name, email, hospital_name FROM users WHERE id = ?");
              $doc_stmt->bind_param("i", $rec['doctor_id']);
              $doc_stmt->execute();
              $doc_info = $doc_stmt->get_result()->fetch_assoc();
            ?>
            <div class="mb-4 border-bottom pb-3">
              <p><strong>Diagnosis:</strong> <?= nl2br(htmlspecialchars($rec['diagnosis'])) ?></p>
              <p><strong>Treatment:</strong> <?= nl2br(htmlspecialchars($rec['treatment'])) ?></p>
              <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($rec['notes'])) ?></p>
              <p class="text-muted small">
                <strong>Written by:</strong> <?= htmlspecialchars($doc_info['name'] ?? 'Unknown') ?>,
                <?= htmlspecialchars($doc_info['hospital_name'] ?? 'Unknown Hospital') ?><br>
                <strong>Email:</strong> <?= htmlspecialchars($doc_info['email'] ?? '-') ?><br>
                <strong>Date:</strong> <?= date('d M Y, H:i', strtotime($rec['created_at'])) ?>
              </p>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-muted">No medical records found for this patient.</p>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('writeRecordForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const msgBox = document.getElementById('writeRecordMsg');

  fetch('save_medical_record.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(response => {
    msgBox.className = 'alert alert-success';
    msgBox.textContent = '✅ Medical record saved successfully.';
    msgBox.classList.remove('d-none');
    setTimeout(() => {
      form.reset();
      bootstrap.Modal.getInstance(document.getElementById('writeRecordModal')).hide();
    }, 1000);
  })
  .catch(err => {
    msgBox.className = 'alert alert-danger';
    msgBox.textContent = '⚠️ Failed to save record.';
    msgBox.classList.remove('d-none');
    console.error(err);
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const chatBox = document.querySelector('.chat-box');
  if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
});
</script>

</body>
</html>
