<?php
// modals.php
?>

<!-- Add Ambulance Modal -->
<div class="modal fade" id="addAmbulanceModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Ambulance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="text" name="plate_number" class="form-control mb-2" placeholder="Plate Number" required>
          <input type="text" name="driver_name" class="form-control mb-2" placeholder="Driver Name" required>
        </div>
        <div class="modal-footer">
          <button name="add_ambulance" class="btn btn-success">Add</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Add Bed Modal -->
<div class="modal fade" id="addBedModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Emergency Bed</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="text" name="bed_number" class="form-control mb-2" placeholder="Bed Number (e.g. 5)" required>
        </div>
        <div class="modal-footer">
          <button name="add_bed" class="btn btn-success">Add Bed</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Add Nurse Modal -->
<div class="modal fade" id="nurseModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Nurse</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="text" name="nurse_name" class="form-control mb-2" placeholder="Full Name" required>
          <input type="email" name="nurse_email" class="form-control mb-2" placeholder="Email" required>
          <select name="nurse_gender" class="form-control mb-2" required>
            <option value="">Select Gender</option>
            <option value="Female">Female</option>
            <option value="Male">Male</option>
          </select>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_nurse" class="btn btn-success">Save Nurse</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Admit Patient Modal -->
<div class="modal fade" id="admitModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Admit New Patient</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="text" name="patient_name" class="form-control mb-2" placeholder="Patient Full Name" required>
          <input type="email" name="patient_email" class="form-control mb-2" placeholder="Patient Email" required>
          <select name="patient_gender" class="form-control mb-2" required>
            <option value="">Select Gender</option>
            <option value="Female">Female</option>
            <option value="Male">Male</option>
          </select>
          <select name="bed_id" class="form-control mb-2" required>
            <option value="">Select bed (Vacant)</option>
            <?php
              $stmt = $conn->prepare("SELECT id, bed_number FROM emergency_beds WHERE hospital_name = ? AND status = 'vacant'");
              $stmt->bind_param("s", $admin['hospital_name']);
              $stmt->execute();
              $beds = $stmt->get_result();
              while ($r = $beds->fetch_assoc()):
            ?>
              <option value="<?= $r['id'] ?>">bed <?= htmlspecialchars($r['bed_number']) ?></option>
            <?php endwhile; ?>
          </select>
          <textarea name="admission_reason" class="form-control mb-2" placeholder="Reason for Admission" rows="2" required></textarea>
        </div>
        <div class="modal-footer">
          <button type="submit" name="admit_patient" class="btn btn-success">Admit</button>
        </div>
      </div>
    </form>
  </div>
</div>
