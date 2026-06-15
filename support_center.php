<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
  header("Location: patient_login.php");
  exit();
}
include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Support Center | DoctorsCare</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    body {
      background: linear-gradient(to right, #e0f7fa, #ffffff);
      font-family: 'Segoe UI', sans-serif;
    }
    .container {
      max-width: 1000px;
      margin: 50px auto;
      background: #ffffff;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    h2 {
      color: #2c3e50;
      font-weight: 700;
      text-align: center;
      margin-bottom: 30px;
    }
    .quote {
      font-style: italic;
      font-size: 1.1rem;
      border-left: 5px solid #28a745;
      background: #e9f7ef;
      padding: 15px 20px;
      border-radius: 8px;
    }
    .peer-card {
      padding: 20px;
      background: #f9f9f9;
      border: 1px solid #ccc;
      margin-bottom: 20px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      gap: 20px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      transition: transform 0.3s ease;
    }
    .peer-card:hover {
      transform: translateY(-3px);
    }
    .peer-card img {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #2ecc71;
    }
    .sos {
      background-color: #e74c3c;
      color: white;
      font-weight: bold;
      font-size: 18px;
      padding: 12px;
      border: none;
      border-radius: 8px;
      margin-top: 30px;
      transition: background 0.3s;
    }
    .sos:hover {
      background-color: #c0392b;
    }
    textarea.form-control {
      border-radius: 10px;
      resize: none;
    }
    button.btn {
      border-radius: 8px;
      font-size: 16px;
    }
    .modal-content {
      border-radius: 14px;
    }
    .btn-outline-success {
      border-radius: 6px;
      padding: 6px 14px;
      font-size: 14px;
    }
    #diagnosisResult {
      font-size: 15px;
      line-height: 1.6;
    }
    #diagnosisResult a {
      margin-top: 8px;
      display: inline-block;
    }
    .btn-info, .btn-warning, .btn-secondary {
      font-weight: 500;
    }
    .modal-title {
      font-weight: bold;
    }
    label {
      font-weight: 500;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>🧠 Mental & Peer Support Center</h2>

  <div class="quote mb-4 p-3 bg-success-subtle border-start border-5 border-success">
    "Even the darkest night will end and the sun will rise." — Victor Hugo
  </div>

  <h5>🤝 Peer Counselors</h5>
  <?php
    $sql = "SELECT name, email, county, profile_pic FROM users WHERE role = 'doctor' AND LOWER(specialty) LIKE '%peer counselor%'";
    $res = $conn->query($sql);
    if ($res->num_rows > 0):
      while ($peer = $res->fetch_assoc()):
  ?>
    <div class="peer-card">
      <img src="<?= htmlspecialchars($peer['profile_pic']) ?>" alt="Profile">
      <div>
        <strong><?= htmlspecialchars($peer['name']) ?></strong><br>
        <?= htmlspecialchars($peer['email']) ?><br>
        <span class="text-muted"><?= htmlspecialchars($peer['county']) ?></span>
      </div>
      <button class="btn btn-sm btn-outline-success mt-2" onclick="openChatModal('<?= $peer['name'] ?>', '<?= $peer['email'] ?>')">💬 Chat</button>

    </div>
  <?php endwhile; else: ?>
    <p class="text-muted">No peer counselors available currently.</p>
  <?php endif; ?>

  <!-- 📤 Anonymous Message Form -->
  <h5 class="mt-4">📤 Share Anonymously</h5>
  <form id="anonForm" method="POST" action="submit_anonymous.php" onsubmit="return openPopup('anonymous')">
    <textarea name="message" class="form-control" rows="4" placeholder="What's on your mind..." required></textarea>
    <input type="hidden" name="type" value="anonymous">
    <button type="submit" class="btn btn-primary mt-2">Send</button>
  </form>

  <!-- 🚨 SOS -->
  <form id="sosForm" method="POST" action="submit_anonymous.php" onsubmit="return openPopup('sos')">
    <input type="hidden" name="message" value="🚨 A patient urgently needs help (SOS triggered)">
    <input type="hidden" name="type" value="sos">
    <button class="sos w-100">🚨 SOS — I Need Urgent Help</button>
  </form>

  <!-- Extras -->
  <h5 class="mt-5">🧰 Helpful Tools</h5>
  <div class="d-grid gap-2">
    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#selfDiagnosisModal">🤖 Self Diagnosis</button>
    <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#infoModal">📚 Learn About Depression</button>
    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#firstAidModal">🩺 First Aid for Seizures</button>
  </div>
</div>

<!-- Modal: Contact Info -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="contactForm">
      <div class="modal-header">
        <h5 class="modal-title">🔒 Contact Info (Private)</h5>
      </div>
      <div class="modal-body">
        <input type="hidden" id="modalTargetForm" value="">
        <div class="mb-3">
          <label>Your Name</label>
          <input type="text" name="contact_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Phone Number</label>
          <input type="text" name="contact_phone" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Preferred Time to Reach You</label>
          <input type="text" name="contact_time" class="form-control" placeholder="e.g. Evenings">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" type="submit">Submit</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Self Diagnosis -->
<div class="modal fade" id="selfDiagnosisModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content p-3">
      <h5 class="modal-title">🤖 Self Diagnosis Assistant</h5>
      <div class="modal-body">
        <p>Select any symptoms you are experiencing:</p>
        <div class="row">
          <div class="col-md-6">
            <label><input type="checkbox" class="symptom" value="sadness"> Persistent sadness</label><br>
            <label><input type="checkbox" class="symptom" value="anxiety"> Anxiety or fear</label><br>
            <label><input type="checkbox" class="symptom" value="worthlessness"> Feeling worthless</label><br>
            <label><input type="checkbox" class="symptom" value="panic"> Panic attacks</label><br>
            <label><input type="checkbox" class="symptom" value="nightmares"> Nightmares or flashbacks</label><br>
          </div>
          <div class="col-md-6">
            <label><input type="checkbox" class="symptom" value="fatigue"> Fatigue</label><br>
            <label><input type="checkbox" class="symptom" value="isolation"> Social withdrawal</label><br>
            <label><input type="checkbox" class="symptom" value="sleep"> Trouble sleeping</label><br>
            <label><input type="checkbox" class="symptom" value="appetite"> Loss of appetite</label><br>
            <label><input type="checkbox" class="symptom" value="suicidal"> Suicidal thoughts</label><br>
          </div>
        </div>

        <hr>
        <p>Or describe how you're feeling:</p>
        <textarea id="diagnosisInput" class="form-control mb-3" rows="3" placeholder="Describe what you're going through..."></textarea>

        <button class="btn btn-primary" onclick="analyzeSymptoms()">🧠 Get Diagnosis</button>

        <div id="diagnosisResult" class="alert alert-info mt-4" style="display: none;"></div>
      </div>
    </div>
  </div>
</div>


<!-- Modal: Depression Info -->
<div class="modal fade" id="infoModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content p-3">
      <h5 class="modal-title">📚 What is Depression?</h5>
      <div class="modal-body">
        <p>Depression is a common mental disorder that negatively affects how you feel, think, and act. It causes feelings of sadness, hopelessness, and a loss of interest in daily activities. You are not alone — support is available.</p>
        <p><strong>How to Help:</strong></p>
        <ul>
          <li>Talk to someone you trust</li>
          <li>Join a support group</li>
          <li>Seek professional help if symptoms persist</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Modal: First Aid -->
<div class="modal fade" id="firstAidModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content p-3">
      <h5 class="modal-title">🩺 First Aid for Seizures</h5>
      <div class="modal-body">
        <ul>
          <li>Stay calm and stay with the person</li>
          <li>Time the seizure</li>
          <li>Clear the area of any harmful objects</li>
          <li>Do not hold them down or put anything in their mouth</li>
          <li>After seizure stops, help them recover gently</li>
          <li>Call for medical help if it lasts more than 5 minutes</li>
        </ul>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  let targetForm = '';

  function openPopup(type) {
    event.preventDefault();
    targetForm = type === 'sos' ? 'sosForm' : 'anonForm';
    const modal = new bootstrap.Modal(document.getElementById('contactModal'));
    modal.show();
    return false;
  }

  document.getElementById('contactForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const contactData = new FormData(this);
    const form = document.getElementById(targetForm);
    for (let [key, value] of contactData.entries()) {
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = key;
      input.value = value;
      form.appendChild(input);
    }
    bootstrap.Modal.getInstance(document.getElementById('contactModal')).hide();
    form.submit();
  });

 function analyzeSymptoms() {
  const selectedSymptoms = Array.from(document.querySelectorAll(".symptom:checked")).map(el => el.value);
  const description = document.getElementById("diagnosisInput").value.toLowerCase();
  const resultDiv = document.getElementById("diagnosisResult");

  let conditions = new Set();

  // Detect illness from symptoms
  if (selectedSymptoms.includes("sadness") || selectedSymptoms.includes("worthlessness") || selectedSymptoms.includes("fatigue") || selectedSymptoms.includes("appetite") || selectedSymptoms.includes("suicidal")) {
    conditions.add("Depression");
  }

  if (selectedSymptoms.includes("anxiety") || selectedSymptoms.includes("panic") || selectedSymptoms.includes("sleep")) {
    conditions.add("Anxiety Disorder");
  }

  if (selectedSymptoms.includes("nightmares")) {
    conditions.add("PTSD");
  }

  if (selectedSymptoms.includes("isolation") && selectedSymptoms.includes("sadness")) {
    conditions.add("Social Anxiety");
  }

  if (description.includes("empty") || description.includes("hopeless") || description.includes("worthless")) {
    conditions.add("Depression");
  }

  if (description.includes("panic") || description.includes("scared") || description.includes("fear")) {
    conditions.add("Anxiety Disorder");
  }

  if (description.includes("flashback") || description.includes("trauma")) {
    conditions.add("PTSD");
  }

  if (description.includes("suicide") || description.includes("kill myself")) {
    conditions.add("⚠️ Suicidal thoughts – Please seek urgent help now.");
  }

  // Output
  if (conditions.size === 0) {
    resultDiv.innerHTML = "✅ No strong indicators found based on your input. Still, it's okay to reach out for help.";
  } else {
    let links = {
      "Depression": {
        info: "https://www.who.int/news-room/fact-sheets/detail/depression",
        aid: "https://www.mayoclinic.org/diseases-conditions/depression/in-depth/first-aid/art-20045943",
        help: "https://www.google.com/search?q=mental+health+support+near+me"
      },
      "Anxiety Disorder": {
        info: "https://www.nimh.nih.gov/health/topics/anxiety-disorders",
        aid: "https://www.medicalnewstoday.com/articles/anxiety-attack-first-aid",
        help: "https://www.google.com/search?q=anxiety+therapist+near+me"
      },
      "PTSD": {
        info: "https://www.nhs.uk/mental-health/conditions/post-traumatic-stress-disorder-ptsd/overview/",
        aid: "https://www.ptsd.va.gov/family/how_help.asp",
        help: "https://www.google.com/search?q=ptsd+support+groups"
      },
      "Social Anxiety": {
        info: "https://www.verywellmind.com/social-anxiety-disorder-4157210",
        aid: "https://www.healthline.com/health/social-anxiety-disorder/first-aid",
        help: "https://www.google.com/search?q=social+anxiety+support+near+me"
      }
    };

    let output = "<strong>🧠 Based on your input, you may be experiencing:</strong><ul>";
    conditions.forEach(condition => {
      output += `<li><strong>${condition}</strong>`;

      if (links[condition]) {
        output += `<div class='mt-2'>
          <a href="${links[condition].info}" target="_blank" class="btn btn-sm btn-outline-primary me-1">📚 Learn More</a>
          <a href="${links[condition].aid}" target="_blank" class="btn btn-sm btn-outline-warning me-1">🩺 First Aid</a>
          <a href="${links[condition].help}" target="_blank" class="btn btn-sm btn-outline-success">🏥 Find Help</a>
        </div>`;
      }

      output += `</li>`;
    });
    output += "</ul><div class='mt-2'>📝 This is not a professional diagnosis. For accurate help, reach out to a doctor or peer counselor.</div>";

    resultDiv.innerHTML = output;
  }

  resultDiv.style.display = 'block';
}

let chatWith = '';
  let chatInterval;

  function openChatModal(name, email) {
    chatWith = email;
    document.getElementById('chatDoctorName').innerText = name;
    document.getElementById('chatBox').innerHTML = 'Loading chat...';
    fetchChatMessages();

    const modal = new bootstrap.Modal(document.getElementById('chatModal'));
    modal.show();

    // Refresh chat every 4 seconds
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
</script>



</body>
</html>
