<?php
// This file is INCLUDED by patient_dashboard.php.
// It relies on variables already defined there: $conn, $notifications, $results, etc.

?>

<!-- Doctor Modal -->
<div class="modal fade" id="doctorModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Available Doctors</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search by specialty or gender...">

        <div id="doctorList">
          <?php
            $sql = "SELECT id, name, email, profile_pic, specialty, gender, consultation_fee, is_available FROM users WHERE role = 'doctor'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0):
              while ($row = $result->fetch_assoc()):
          ?>
          <div class="doctor-card">
            <img src="<?= htmlspecialchars($row['profile_pic']) ?>" alt="doc">
            <div>
              <strong>
                <?= htmlspecialchars($row['name']) ?>
                <?= $row['is_available'] ? '🟢' : '🔴' ?>
              </strong><br>
              <?= htmlspecialchars($row['specialty']) ?> |
              <?= htmlspecialchars($row['gender']) ?> |
              KES <?= htmlspecialchars($row['consultation_fee']) ?><br>

              <?php if ($row['is_available']): ?>
                <button class="btn btn-sm btn-success mt-1"
                        onclick="openChatModal('<?= $row['name'] ?>', '<?= $row['email'] ?>')">
                  Chat
                </button>
                <button class="btn btn-sm btn-warning mt-1"
                        onclick="openAppointmentModal('<?= $row['name'] ?>', <?= $row['id'] ?>)">
                  Request Appointment
                </button>
                <a href="view_doctor.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm mt-1">View Profile</a>
              <?php else: ?>
                <button class="btn btn-sm btn-secondary mt-1" disabled>Offline</button>
              <?php endif; ?>
            </div>
          </div>
          <?php endwhile; else: ?>
            <p>No doctors found.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
// $results is prepared in the including file
?>

<!-- Medical Records Modal -->
<div class="modal fade" id="medicalRecordsModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">🩺 My Medical Records</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if ($results->num_rows > 0): ?>
          <?php while ($rec = $results->fetch_assoc()): ?>
            <?php
              $doc_stmt = $conn->prepare("SELECT name, hospital_name, specialty, email FROM users WHERE id = ?");
              $doc_stmt->bind_param("i", $rec['doctor_id']);
              $doc_stmt->execute();
              $doc_data = $doc_stmt->get_result()->fetch_assoc();

              $doctor_name = $doc_data['name'] ?? 'Unknown';
              $doctor_hospital = $doc_data['hospital_name'] ?? 'Unknown Hospital';
              $doctor_specialty = $doc_data['specialty'] ?? 'Not specified';
              $doctor_email = $doc_data['email'] ?? 'N/A';
            ?>
            <div class="mb-4 border-bottom pb-2">
              <p><strong>Diagnosis:</strong> <?= nl2br(htmlspecialchars($rec['diagnosis'])) ?></p>
              <p><strong>Treatment:</strong> <?= nl2br(htmlspecialchars($rec['treatment'])) ?></p>
              <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($rec['notes'])) ?></p>

              <div class="text-muted small mt-2">
                <strong>Doctor:</strong> Dr. <?= htmlspecialchars($doctor_name) ?><br>
                <strong>Hospital:</strong> <?= htmlspecialchars($doctor_hospital) ?><br>
                <strong>Specialty:</strong> <?= htmlspecialchars($doctor_specialty) ?><br>
                <strong>Email:</strong> <?= htmlspecialchars($doctor_email) ?><br>
                <strong>Date:</strong> <?= date('d M Y, H:i', strtotime($rec['created_at'])) ?>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-muted">No medical records available yet.</p>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- 💬 Chat Modal -->
<div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="chatModalLabel">💬 Chat with <span id="chatDoctorName"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="chatBox" style="max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
        <div class="text-center text-muted">Loading messages...</div>
      </div>
      <div class="modal-footer">
        <input type="text" id="chatMessage" class="form-control" placeholder="Type your message here...">
        <button class="btn btn-primary" onclick="sendChatMessage()">Send</button>
      </div>
    </div>
  </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Leave a Review</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="submit_review.php" method="POST">
        <div class="modal-body">
          <textarea class="form-control" name="review" rows="4" placeholder="Write your feedback..." required></textarea>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Submit Review</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Drug Diagnosis Modal -->
<div class="modal fade" id="drugDiagnosisModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Diagnose Based on Prescription</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="diagnosisForm">
        <div class="modal-body">
          <label for="drugSelect">Select prescribed drugs:</label>
          <select id="drugSelect" class="form-select" multiple required>
            <option value="paracetamol">Paracetamol</option>
            <option value="ibuprofen">Ibuprofen</option>
            <option value="amoxicillin">Amoxicillin</option>
            <!-- Add more -->
          </select>
          <div id="diagnosisResult" class="mt-3" style="display: none;"></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Diagnose</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Appointment Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="request_appointment.php" method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">📅 Request Appointment</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="doctor_id" id="appointmentDoctorId">
          <div class="mb-3">
            <label for="appointmentDate" class="form-label">Preferred Date</label>
            <input type="date" name="date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="reason" class="form-label">Reason for Appointment</label>
            <textarea name="reason" class="form-control" rows="3" placeholder="Describe your issue..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" type="submit">Submit Request</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Notifications Modal -->
<div class="modal fade" id="notifModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title">🔔 Notifications</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if ($notifications->num_rows > 0): ?>
          <ul class="list-group">
            <?php while ($n = $notifications->fetch_assoc()): ?>
              <li class="list-group-item <?= $n['is_read'] ? '' : 'list-group-item-warning' ?>">
                <?= htmlspecialchars($n['message']) ?><br>
                <small class="text-muted"><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></small>
              </li>
            <?php endwhile; ?>
          </ul>
        <?php else: ?>
          <p class="text-muted">No notifications yet.</p>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
