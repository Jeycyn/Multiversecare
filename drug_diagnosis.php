<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drugs'])) {
  $drugs = $_POST['drugs'];

  $data = [
    'paracetamol' => 'Fever, Mild Pain, Headache',
    'ibuprofen' => 'Inflammation, Muscle Pain, Cramps',
    'amoxicillin' => 'Bacterial Infections, Dental Issues, Tonsillitis',
  ];

  $combo_diagnosis = '';
  if (in_array('paracetamol', $drugs) && in_array('amoxicillin', $drugs)) {
    $combo_diagnosis = 'Bacterial Infection with Fever';
  } elseif (in_array('ibuprofen', $drugs) && in_array('amoxicillin', $drugs)) {
    $combo_diagnosis = 'Dental or Throat Infection';
  }

  echo "<h6>💊 Based on Selected Drugs:</h6><ul>";
  foreach ($drugs as $drug) {
    $drugName = ucfirst($drug);
    $condition = $data[$drug] ?? 'Unknown';
    echo "<li><strong>{$drugName}</strong>: {$condition}</li>";
  }
  echo "</ul>";

  if ($combo_diagnosis) {
    echo "<hr><h6>🤝 Combined Drug Pattern:</h6>";
    echo "<p><strong>Diagnosis Suggestion:</strong> {$combo_diagnosis}</p>";
  }

  echo "<p class='text-warning mt-3'>⚠️ This tool gives basic insight based on prescriptions. For accurate diagnosis, consult a certified doctor.</p>";
}
?>
