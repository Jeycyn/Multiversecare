<?php
session_start();
include 'config.php';

// Super Admin Credentials
$allowed_name = 'Jeycyn Jeff';
$allowed_access_code = 'JEYCYN@JOYLIGHT';

// Attempt Tracking
if (!isset($_SESSION['attempts'])) $_SESSION['attempts'] = 0;

// Login Form
if (!isset($_SESSION['name']) || !isset($_SESSION['access_code'])) {
  $_SESSION['attempts']++;
  if ($_SESSION['attempts'] >= 3) {
    die("<h2 style='color:red;text-align:center;'>Access Blocked: Too many failed attempts.</h2>");
  }
  echo "<form method='post' style='text-align:center;margin-top:10%;'>
    <h2>Super Admin Login</h2>
    <input type='text' name='name' placeholder='Name' required><br><br>
    <input type='password' name='access_code' placeholder='Access Code' required><br><br>
    <button type='submit'>Login</button>
  </form>";
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['name'] = $_POST['name'];
    $_SESSION['access_code'] = $_POST['access_code'];
    header("Location: " . $_SERVER['PHP_SELF']);
  }
  exit;
}

if ($_SESSION['name'] !== $allowed_name || $_SESSION['access_code'] !== $allowed_access_code) {
  die("<h2 style='color:red;text-align:center;'>Access Denied: Unauthorized</h2>");
}

// Function to count
function getCount($query, $conn) {
  return $conn->query($query)->fetch_assoc()['total'];
}

// Fetch counts
$total_users = getCount("SELECT COUNT(*) AS total FROM users", $conn);
$total_doctors = getCount("SELECT COUNT(*) AS total FROM users WHERE role='doctor'", $conn);
$total_patients = getCount("SELECT COUNT(*) AS total FROM users WHERE role='patient'", $conn);
$total_nurses = getCount("SELECT COUNT(*) AS total FROM nurses", $conn); // ✅ Updated
$total_ambulances = getCount("SELECT COUNT(*) AS total FROM ambulances", $conn);
$total_rooms = getCount("SELECT COUNT(*) AS total FROM emergency_rooms", $conn);
$total_medical = getCount("SELECT COUNT(*) AS total FROM medical_records", $conn);
$total_reviews = getCount("SELECT COUNT(*) AS total FROM patient_reviews", $conn);
$total_drugs = getCount("SELECT COUNT(*) AS total FROM drugs", $conn);

$latest_users = $conn->query("SELECT * FROM users ORDER BY id DESC LIMIT 5");
$pending_reviews = $conn->query("SELECT * FROM patient_reviews WHERE is_approved = 0 ORDER BY review_date DESC LIMIT 5");
$ambulance_requests = $conn->query("SELECT * FROM ambulance_requests ORDER BY id DESC LIMIT 5");
$room_requests = $conn->query("SELECT * FROM room_requests ORDER BY id DESC LIMIT 5");
$chat_messages = $conn->query("SELECT * FROM chat_messages ORDER BY id DESC LIMIT 5");
$anonymous_msgs = $conn->query("SELECT * FROM anonymous_messages ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Super Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
  <div class="container py-4">
    <h2 class="text-center mb-4">Welcome, Jeycyn 👑</h2>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <?php
      $cards = [
        ['title' => 'Users', 'count' => $total_users],
        ['title' => 'Doctors', 'count' => $total_doctors],
        ['title' => 'Nurses', 'count' => $total_nurses],
        ['title' => 'Patients', 'count' => $total_patients],
        ['title' => 'Ambulances', 'count' => $total_ambulances],
        ['title' => 'Emergency Rooms', 'count' => $total_rooms],
        ['title' => 'Medical Records', 'count' => $total_medical],
        ['title' => 'Reviews', 'count' => $total_reviews],
        ['title' => 'Drugs', 'count' => $total_drugs]
      ];
      foreach ($cards as $card) {
        echo "
        <div class='col-md-4'>
          <div class='card text-center shadow-sm card-click' data-target='{$card['title']}'>
            <div class='card-body'>
              <h6>{$card['title']}</h6>
              <h3>{$card['count']}</h3>
            </div>
          </div>
        </div>";
      }
      ?>
    </div>

    <!-- Pending Reviews -->
    <hr>
    <h4 class="mb-3">Pending Reviews</h4>
    <ul class="list-group">
      <?php while ($r = $pending_reviews->fetch_assoc()): ?>
        <li class="list-group-item d-flex justify-content-between">
          <?= $r['patient_name'] ?>: "<?= $r['review_text'] ?>"
          <span>
            <button class="btn btn-success btn-sm" onclick="moderateReview(<?= $r['id'] ?>, 'approve')">Approve</button>
            <button class="btn btn-danger btn-sm" onclick="moderateReview(<?= $r['id'] ?>, 'delete')">Delete</button>
          </span>
        </li>
      <?php endwhile; ?>
    </ul>

    <!-- Latest Users -->
    <hr>
    <h4 class="mt-4">Recent User Registrations</h4>
    <ul class="list-group">
      <?php while ($u = $latest_users->fetch_assoc()):
        $is_new = (strtotime($u['created_at']) >= strtotime('-1 minutes')) ? '🆕 ' : '';
      ?>
        <li class="list-group-item"><?= $is_new ?>👤 <?= $u['name'] ?> (<?= $u['role'] ?>) - <?= $u['created_at'] ?></li>
      <?php endwhile; ?>
    </ul>

    <!-- Ambulance & Room Requests -->
    <hr>
    <h4 class="mt-4">Ambulance & Room Requests</h4>
    <div class="row">
      <div class="col-md-6">
        <h6>🚑 Ambulance Requests</h6>
        <ul class="list-group">
          <?php while ($a = $ambulance_requests->fetch_assoc()): ?>
            <li class="list-group-item">Patient <?= $a['patient_id'] ?> - <?= $a['hospital_name'] ?> [<?= $a['status'] ?>]</li>
          <?php endwhile; ?>
        </ul>
      </div>
      <div class="col-md-6">
        <h6>🛌 Room Requests</h6>
        <ul class="list-group">
          <?php while ($r = $room_requests->fetch_assoc()): ?>
            <li class="list-group-item">Patient <?= $r['patient_id'] ?> - <?= $r['hospital_name'] ?> [<?= $r['status'] ?>]</li>
          <?php endwhile; ?>
        </ul>
      </div>
    </div>

    <!-- Chat Messages -->
    <hr>
    <h4 class="mt-4">💬 Chat Messages</h4>
    <ul class="list-group">
      <?php while ($m = $chat_messages->fetch_assoc()): ?>
        <li class="list-group-item">[<?= $m['sender'] ?>] <?= $m['message'] ?></li>
      <?php endwhile; ?>
    </ul>

    <!-- Anonymous Messages -->
    <hr>
    <h4 class="mt-4">😶 Anonymous Messages</h4>
    <ul class="list-group">
      <?php while ($a = $anonymous_msgs->fetch_assoc()): ?>
        <li class="list-group-item">"<?= $a['message'] ?>"</li>
      <?php endwhile; ?>
    </ul>

    <hr class="mt-5">
    <footer class="text-center text-muted">DoctorsCare Super Admin Panel | Developed by Jeycyn Jeff</footer>

    <!-- Modals for Card Details -->
    <?php
    $tables = [
      'Users' => "SELECT name, role, email, created_at FROM users ORDER BY id DESC",
      'Doctors' => "SELECT name, specialty, email, created_at FROM users WHERE role='doctor'",
      'Nurses' => "SELECT name, email, gender, hospital_name, county, created_at FROM nurses ORDER BY id DESC", // ✅ Updated for nurses
      'Patients' => "SELECT name, email, created_at FROM users WHERE role='patient'",
      'Ambulances' => "SELECT * FROM ambulances",
      'Emergency Rooms' => "SELECT * FROM emergency_rooms",
      'Medical Records' => "SELECT * FROM medical_records ORDER BY id DESC",
      'Reviews' => "SELECT * FROM patient_reviews ORDER BY review_date DESC",
      'Drugs' => "SELECT * FROM drugs"
    ];

    foreach ($tables as $label => $query) {
      $res = $conn->query($query);
      echo "<div class='modal fade' id='modal-$label' tabindex='-1'><div class='modal-dialog modal-lg'><div class='modal-content'>";
      echo "<div class='modal-header'><h5 class='modal-title'>$label Details</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div><div class='modal-body'>";
      echo "<div style='max-height: 400px; overflow-y:auto'><table class='table table-bordered table-sm'><thead><tr>";

      $firstRow = $res->fetch_assoc();
      if ($firstRow) {
        foreach ($firstRow as $col => $val) echo "<th>$col</th>";
        echo "</tr></thead><tbody><tr>";
        foreach ($firstRow as $val) echo "<td>$val</td>";
        echo "</tr>";

        while ($row = $res->fetch_assoc()) {
          echo "<tr>";
          foreach ($row as $val) echo "<td>$val</td>";
          echo "</tr>";
        }
      } else {
        echo "<tr><td colspan='10'>No data</td></tr>";
      }

      echo "</tbody></table></div></div></div></div></div>";
    }
    ?>

    <script>
      document.querySelectorAll('.card-click').forEach(card => {
        card.addEventListener('click', () => {
          const target = card.getAttribute('data-target');
          const modalId = 'modal-' + target;
          const modal = new bootstrap.Modal(document.getElementById(modalId));
          modal.show();
        });
      });

      function moderateReview(id, action) {
        fetch(`${action}_review.php?id=${id}`)
          .then(res => res.text())
          .then(msg => alert(msg))
          .then(() => location.reload());
      }
    </script>
  </div>
</body>
</html>
