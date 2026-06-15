<?php
session_start();
include 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  die("Doctor ID is missing or invalid.");
}

$doctor_id = intval($_GET['id']);

// Fetch doctor details
$stmt = $conn->prepare("SELECT name, email, profile_pic, specialty, gender, bio, hospital_name, is_available FROM users WHERE id = ? AND role = 'doctor'");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
  die("Doctor not found.");
}

$doctor = $result->fetch_assoc();

// Fetch average rating
$rating_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM doctor_reviews WHERE doctor_id = ?");
$rating_stmt->bind_param("i", $doctor_id);
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result()->fetch_assoc();
$avg_rating = round($rating_result['avg_rating'] ?? 0, 1);

// Fetch reviews
$review_stmt = $conn->prepare("SELECT r.rating, r.review, r.created_at, u.name FROM doctor_reviews r JOIN users u ON r.patient_id = u.id WHERE r.doctor_id = ? ORDER BY r.created_at DESC");
$review_stmt->bind_param("i", $doctor_id);
$review_stmt->execute();
$reviews = $review_stmt->get_result();

// Fetch working hours
$sched_stmt = $conn->prepare("SELECT day, start_time, end_time FROM working_hours WHERE doctor_id = ?");
$sched_stmt->bind_param("i", $doctor_id);
$sched_stmt->execute();
$schedule = $sched_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Doctor Profile | DoctorsCare</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    body { background-color: #f4f6f9; }
    .card { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .profile-img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid #0d6efd; }
    .section-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; }
    .rating-star { color: #ffc107; }
  </style>
</head>
<body>
  <div class="container py-5">
    <a href="javascript:history.back()" class="btn btn-outline-secondary mb-4">&larr; Back</a>

    <!-- Profile Card -->
    <div class="card mb-4 p-4">
      <div class="d-flex align-items-center">
        <img src="<?= htmlspecialchars($doctor['profile_pic']) ?>" class="profile-img me-4" alt="Profile">
        <div>
          <h4 class="mb-1">Dr. <?= htmlspecialchars($doctor['name']) ?></h4>
          <div class="text-muted">Specialty: <?= htmlspecialchars($doctor['specialty']) ?></div>
          <div class="text-muted">Gender: <?= htmlspecialchars($doctor['gender']) ?></div>
          <div class="text-muted">Email: <?= htmlspecialchars($doctor['email']) ?></div>
          <div class="text-muted">Hospital: <?= htmlspecialchars($doctor['hospital_name']) ?></div>
          <div class="text-muted">
            Status: <?= $doctor['is_available'] ? '<span class="text-success">🟢 Available</span>' : '<span class="text-danger">🔴 Not Available</span>' ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Biography -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="section-title text-primary">🩺 Biography</div>
        <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($doctor['bio'] ?? 'No biography provided.')) ?></p>
      </div>
    </div>

    <!-- Ratings -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="section-title text-primary">⭐ Ratings</div>
        <h5 class="mb-1"><?= $avg_rating ?> <span class="rating-star">★</span> / 5.0</h5>
        <div class="mb-2">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="fas fa-star<?= $i <= round($avg_rating) ? ' text-warning' : ' text-muted' ?>"></i>
          <?php endfor; ?>
        </div>
        <small class="text-muted"><?= $rating_result['total'] ?> total reviews</small>
      </div>
    </div>

    <!-- Working Hours -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="section-title text-primary">📅 Working Hours</div>
        <?php if ($schedule->num_rows > 0): ?>
          <ul class="list-group list-group-flush">
            <?php while ($row = $schedule->fetch_assoc()): ?>
              <li class="list-group-item d-flex justify-content-between">
                <strong><?= htmlspecialchars($row['day']) ?>:</strong>
                <span><?= date('g:i A', strtotime($row['start_time'])) ?> - <?= date('g:i A', strtotime($row['end_time'])) ?></span>
              </li>
            <?php endwhile; ?>
          </ul>
        <?php else: ?>
          <p class="text-muted">No working hours defined.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Patient Reviews -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="section-title text-primary">💬 Patient Reviews</div>
        <?php if ($reviews->num_rows > 0): ?>
          <?php while ($rev = $reviews->fetch_assoc()): ?>
            <div class="mb-4">
              <h6 class="mb-1"><?= htmlspecialchars($rev['name']) ?> 
                <span class="badge bg-warning text-dark"><?= $rev['rating'] ?>/5</span>
              </h6>
              <p class="mb-1"><?= nl2br(htmlspecialchars($rev['review'])) ?></p>
              <small class="text-muted">Posted on <?= date('d M Y', strtotime($rev['created_at'])) ?></small>
              <hr>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-muted">No reviews yet.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Submit Review Form -->
    <?php if (isset($_SESSION['patient_id'])): ?>
      <div class="card mb-5">
        <div class="card-body">
          <div class="section-title text-primary">📝 Leave a Review</div>
          <form action="submit_doctor_review.php" method="POST">
            <input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">
            <div class="mb-3">
              <label class="form-label">Rating</label>
              <select name="rating" class="form-select" required>
                <option value="">Select...</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                  <option value="<?= $i ?>"><?= $i ?> ★</option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Review</label>
              <textarea name="review" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-success">Submit Review</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <p class="text-muted text-center">Login as a patient to leave a review.</p>
    <?php endif; ?>
  </div>
</body>
</html>
