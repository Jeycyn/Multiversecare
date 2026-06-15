<?php
session_start();
include 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  echo "<p class='text-danger'>Invalid doctor ID.</p>";
  exit;
}

$doctor_id = intval($_GET['id']);

// Get doctor details
$stmt = $conn->prepare("SELECT id, name, email, profile_pic, specialty, gender, consultation_fee, hospital_name, is_available FROM users WHERE id = ? AND role = 'doctor'");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  echo "<p class='text-muted'>Doctor not found.</p>";
  exit;
}

$doctor = $result->fetch_assoc();

// Get average rating
$rating_stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM reviews WHERE doctor_id = ?");
$rating_stmt->bind_param("i", $doctor_id);
$rating_stmt->execute();
$rating_result = $rating_stmt->get_result();
$avg_rating = round($rating_result->fetch_assoc()['avg_rating'] ?? 0, 1);

// Get all reviews
$review_stmt = $conn->prepare("SELECT review, rating, created_at FROM reviews WHERE doctor_id = ? ORDER BY created_at DESC");
$review_stmt->bind_param("i", $doctor_id);
$review_stmt->execute();
$reviews = $review_stmt->get_result();
?>

<div class="text-center mb-4">
  <img src="<?= htmlspecialchars($doctor['profile_pic']) ?>" alt="Doctor" class="rounded-circle" style="width: 100px; height: 100px;">
  <h5 class="mt-2"><?= htmlspecialchars($doctor['name']) ?> <?= $doctor['is_available'] ? '🟢' : '🔴' ?></h5>
  <p class="text-muted"><?= htmlspecialchars($doctor['specialty']) ?> | <?= htmlspecialchars($doctor['gender']) ?> | KES <?= htmlspecialchars($doctor['consultation_fee']) ?></p>
  <p><strong>Hospital:</strong> <?= htmlspecialchars($doctor['hospital_name']) ?></p>
  <p><strong>Email:</strong> <?= htmlspecialchars($doctor['email']) ?></p>
</div>

<div class="mb-3">
  <strong>⭐ Average Rating:</strong> <?= $avg_rating > 0 ? $avg_rating . "/5" : "Not rated yet" ?>
  <div>
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <i class="fas fa-star<?= $i <= $avg_rating ? ' text-warning' : ' text-muted' ?>"></i>
    <?php endfor; ?>
  </div>
</div>

<?php if ($reviews->num_rows > 0): ?>
  <h6 class="mt-4">🗣️ Patient Reviews</h6>
  <?php while ($rev = $reviews->fetch_assoc()): ?>
    <div class="border rounded p-2 mb-2 bg-light">
      <div>
        <strong><?= $rev['rating'] ?>/5</strong> ⭐
        <span class="text-muted small float-end"><?= date('d M Y', strtotime($rev['created_at'])) ?></span>
      </div>
      <p class="mb-0"><?= nl2br(htmlspecialchars(mb_strimwidth($rev['review'], 0, 300, '...'))) ?></p>
    </div>
  <?php endwhile; ?>
<?php else: ?>
  <p class="text-muted">No reviews yet for this doctor.</p>
<?php endif; ?>

<?php if (isset($_SESSION['patient_id'])): ?>
  <form action="submit_review.php" method="POST" class="mt-4">
    <input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">
    <div class="mb-2">
      <label for="rating">Your Rating</label>
      <select name="rating" class="form-select" required>
        <option value="">Select...</option>
        <?php for ($i = 5; $i >= 1; $i--): ?>
          <option value="<?= $i ?>"><?= $i ?> ⭐</option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="mb-2">
      <textarea name="review" class="form-control" rows="3" placeholder="Write your review..." required></textarea>
    </div>
    <button type="submit" class="btn btn-success">Submit Review</button>
  </form>
<?php endif; ?>
