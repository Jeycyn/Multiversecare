<?php
session_start();
include 'config.php';

// Optional: Redirect if not logged in
if (!isset($_SESSION['patient_id'])) {
  header("Location: patient_login.php");
  exit();
}

$symptomsList = ["fever", "cough", "headache", "chills", "nausea", "sneezing", "abdominal pain", "loss of appetite", "runny nose"];
?>

<!DOCTYPE html>
<html>
<head>
  <title>Self Diagnosis | DoctorsCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f4f9ff;
      font-family: 'Segoe UI', sans-serif;
    }
    .container {
      max-width: 700px;
      margin-top: 40px;
    }
    .diagnosis-box {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

<div class="container diagnosis-box">
  <h2 class="mb-4">🧠 Self Diagnosis Test</h2>
  <form method="POST">
    <h5>Select your symptoms:</h5>
    <div class="row">
      <?php foreach ($symptomsList as $sym): ?>
        <div class="col-md-6">
          <label><input type="checkbox" name="symptoms[]" value="<?= $sym ?>"> <?= ucfirst($sym) ?></label><br>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-primary mt-3">Check Diagnosis</button>
  </form>

  <?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['symptoms'])) {
    $userSymptoms = $_POST['symptoms'];
    $results = [];

    $query = $conn->query("SELECT * FROM conditions");
    while ($row = $query->fetch_assoc()) {
      $conditionSymptoms = explode(',', strtolower($row['symptoms']));
      $matchCount = count(array_intersect($userSymptoms, $conditionSymptoms));
      $percentage = ($matchCount / count($conditionSymptoms)) * 100;

      if ($percentage >= 40) {
        $results[] = [
          'name' => $row['condition_name'],
          'match' => round($percentage)
        ];
      }
    }

    echo "<hr><h4>🩺 Possible Conditions:</h4>";
    if ($results) {
      foreach ($results as $res) {
        echo "<div class='alert alert-info'><strong>{$res['name']}</strong> — Match Confidence: {$res['match']}%</div>";
      }
    } else {
      echo "<div class='alert alert-warning'>No strong match found. Please consult a doctor directly.</div>";
    }
  }
  ?>
</div>

</body>
</html>
