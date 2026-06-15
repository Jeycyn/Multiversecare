<?php

session_start();
include 'config.php';

$approved_reviews = $conn->query("
  SELECT 
    patient_reviews.*, 
    users.profile_pic, 
    users.role 
  FROM patient_reviews 
  JOIN users ON patient_reviews.patient_id = users.id 
  WHERE is_approved = 1 
  ORDER BY review_date DESC 
  LIMIT 6
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hospital Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="style.css" >
   
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>
<body>
<header>

  <a href="#" class="logo"><span>M</span>ultiverse <span>C</span>are. </a>

  <div class="signin-dropdown">
    <a href="#" class="signin-toggle">|<span>S</span>ignin <span>A</span>s:|</a>
    <div class="signin-options">
      <a href="hospital_admin_dashboard.php">Hospital Admin</a>
      <a href="prime_register.php">Prime Admin</a>
      <a href="patient_register.php">Patient</a>
      <a href="doctor_dashboard.php">Doctor</a>
      <a href="nurse_login.php">Nurse</a>
    </div>
  </div>

  <a href="patient_login.php" class="login">|<span>L</span>og<span>i</span>n|</a>

  
  <div class="fas fa-bars menu-toggle"></div>

  <nav class="navbar">
    <ul class="nav-links">
      <li><a href="#home">Home</a></li>
      <li><a href="#about">About</a></li>
      <li><a href="#doctor">Doctor</a></li>
      <li><a href="#review">Review</a></li>
      <li><a href="#blog">Blog</a></li>
    </ul>
  </nav>
</header>


<!-- Home Section -->
<section id="home" class="home">


    <div class="row">
        <div class="images">
            <img src="images/home.png" alt="">
        </div>
        <div class="content">
            <h1><span>Stay</span> Safe, <span>Stay</span> Healthy.</h1>
            <p>the available hospitals around you</p>
            <button class="button">Read More</button>
        </div>
    </div>
</section>
<div id="homeModal" class="modal">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <h2>About Our Service</h2>
    <p>
      We help you find the best hospitals, doctors, and mental care facilities near you with just a few clicks. Stay updated on emergency services and make online bookings in seconds.
    </p>
  </div>
</div>

<section id="about" class="about">
  <h1 class="heading">About our Facilities</h1>
  <h1 class="title">Learn and explore our facilities</h1>

  <div class="box-container">

    <!-- Ambulance -->
    <div class="box">
      <div class="images"><img src="images/A modern, white and blue ambulance parked outside a hospital, with “Multiverse Care” written boldly on the side in clean, medical-style font. (1).jpg" alt="Ambulance"></div>
      <div class="content">
        <h3>Ambulance Services</h3>
        <p>Available ambulances around you. Do you require an ambulance service?</p>
        <button onclick="openModal('ambulanceModal')">Learn More</button>
      </div>
    </div>

   <!-- Emergency Beds -->
<div class="box">
  <div class="images">
    <img src="images/Create a realistic and professional image of a hospital emergency room interior for a healthcare platform called Multiverse Care. Show multiple emergency beds inside a clean, modern ER setup. Beds should be equi (1).jpg" alt="Emergency Beds">
  </div>
  <div class="content">
    <h3>Emergency Beds</h3>
    <p>Quick access to available emergency beds in critical care units and trauma sections of nearby hospitals.</p>
    <button onclick="openModal('emergencyModal')">Learn More</button>
  </div>
</div>


    <!-- Mental Health -->
    <div class="box">
      <div class="images"><img src="images/_Design a warm and peaceful scene of an African teenager or adult meditating or having a virtual therapy session on a laptop or tablet. Add comforting elements like plants or soft lights. Include the words 'Mult (1).jpg" alt="Mental Health"></div>
      <div class="content">
        <h3>Mental Health Support</h3>
        <p>Support for your emotional, mental and psychological wellbeing is here.</p>
        <button onclick="openModal('mentalHealthModal')">Learn More</button>
      </div>
    </div>

    <!-- Consultation -->
    <div class="box">
      <div class="images"><img src="images/_Create a realistic and professional illustration of an African patient consulting a doctor through a tablet or PC screen. The patient should be in a calm home setting—maybe seated at a desk or on a couch. The docto.jpg" alt="Consultation"></div>
      <div class="content">
        <h3>Doctor Consultations</h3>
        <p>Consult qualified doctors in your area, anytime from anywhere.</p>
        <button onclick="openModal('consultationModal')">Learn More</button>
      </div>
    </div>

    <!-- Why Us -->
    <div class="box">
      <div class="images"><img src="images/A group of professional African doctors standing confidently in a modern hospital environment, each wearing a lab coat labeled “Multiverse Care”..jpg" alt="Why Choose Us"></div>
      <div class="content">
        <h3>Why Multiverse Care?</h3>
        <p>Learn why thousands trust Multiverse Care for their health and emergencies.</p>
        <button onclick="openModal('whyUsModal')">Learn More</button>
      </div>
    </div>

  </div>


 


<!-- Doctors Section -->
<section id="doctor" class="card">
    <div class="container">
        <h1 class="heading">doctors</h1>
        <h3 class="title">our professional doctors</h3>

    <?php
$doctors = [];

// Ensure 1 male, 1 female, and 1 additional random doctor
$sql_male = "SELECT * FROM users WHERE role = 'doctor' AND gender = 'Male' ORDER BY RAND() LIMIT 1";
$sql_female = "SELECT * FROM users WHERE role = 'doctor' AND gender = 'Female' ORDER BY RAND() LIMIT 1";
$sql_random = "SELECT * FROM users WHERE role = 'doctor' ORDER BY RAND() LIMIT 1";

$ids = [];

// Fetch male doctor
$male = $conn->query($sql_male);
if ($male && $male->num_rows > 0) {
    $doc = $male->fetch_assoc();
    $doctors[] = $doc;
    $ids[] = $doc['id'];
}

// Fetch female doctor
$female = $conn->query($sql_female);
if ($female && $female->num_rows > 0) {
    $doc = $female->fetch_assoc();
    if (!in_array($doc['id'], $ids)) {
        $doctors[] = $doc;
        $ids[] = $doc['id'];
    }
}

// Fetch any extra doctor
$random = $conn->query($sql_random);
if ($random && $random->num_rows > 0) {
    while ($doc = $random->fetch_assoc()) {
        if (!in_array($doc['id'], $ids)) {
            $doctors[] = $doc;
            break;
        }
    }
}
?>

<div class="box-container">
    <?php foreach ($doctors as $doc): ?>
        <div class="box">
            <img src="<?= htmlspecialchars($doc['profile_pic'] ?: 'default.jpg') ?>" alt="Doctor Image">
            <div class="content">
                <h2><?= htmlspecialchars($doc['name']) ?></h2>
                <p><strong><?= htmlspecialchars(ucfirst($doc['specialty'])) ?></strong></p>
                <p><?= htmlspecialchars($doc['hospital_name']) ?> — <?= htmlspecialchars($doc['county']) ?></p>
                <p><strong>Fee:</strong> KES <?= htmlspecialchars($doc['consultation_fee']) ?></p>
                <div class="icons">
                    <a href="#" class="fab fa-facebook-f"></a>
                    <a href="#" class="fab fa-twitter"></a>
                    <a href="#" class="fab fa-instagram"></a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

</section>

<section id="review" class="review-section py-5">
  <div class="container">
    <div class="text-center mb-5">
      <h1 class="section-heading">🩺 What Our Patients Say</h1>
      <p class="section-subtitle">Real experiences from people we've cared for</p>
    </div>

    <div class="row g-4">
      <?php if ($approved_reviews->num_rows > 0): ?>
        <?php while ($r = $approved_reviews->fetch_assoc()): ?>
          <div class="col-md-6">
            <div class="card review-card shadow-sm h-100">
              <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                  <img src="<?= htmlspecialchars($r['profile_pic'] ?: 'images/default.jpg') ?>" alt="Profile" class="review-avatar rounded-circle me-3">
                  <div>
                    <h5 class="mb-0 fw-semibold"><?= htmlspecialchars($r['patient_name']) ?></h5>
                    <small class="text-muted"><?= date("F j, Y, g:i a", strtotime($r['review_date'])) ?></small>
                  </div>
                </div>
                <p class="review-text mb-0"><?= nl2br(htmlspecialchars($r['review_text'])) ?></p>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-info text-center">No approved reviews available yet.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

 

  


<!-- Blog Section -->
<section id="blog" class="blog">
    <h1 class="heading">blog</h1>
    <h3 class="title">Health is our priority</h3>
    <div class="box-container">

        <!-- Blog 1: Diabetes -->
        <div class="box">
            <img src="images/_Design a calm and empowering scene of an African man or woman checking their blood sugar using a glucometer. Include healthy food nearby. Make the environment clean and professional, with the text 'Multiverse C (1).jpg" alt="">
            <div class="content">
                <a href="#"><h2>Diabetes Awareness</h2></a>
                <p>Learn how to prevent and manage diabetes through lifestyle, nutrition, and awareness. Early detection and small daily habits can make a huge difference.</p>
               <button class="button" onclick="openModal('diabetesModal')">Learn More</button>
            </div>
        </div>

        <!-- Blog 2: Nutrition & Immunity -->
        <div class="box">
            <img src="images/_Create a bright and vibrant illustration of an African family surrounded by healthy foods like fruits, vegetables, and water bottles. Include immunity icons (e.g. shield, vitamins), and add the brand name 'Mult (3).jpg" alt="">
            <div class="content">
                <a href="#"><h2>Nutrition & Immunity</h2></a>
                <p>Your diet directly affects your immune system. Discover superfoods, hydration tips, and how vitamins help you fight off illnesses naturally.</p>
                <button class="button" onclick="openModal('diabetesModal')">Learn More</button>
            </div>
        </div>

        <!-- Blog 3: Tech & Health -->
        <div class="box">
            <img src="images/_Show a modern African doctor using technology—like a tablet, smart watch, or hospital dashboard—to monitor a patient's health. Add the text 'Multiverse Care' on the device screen or as a subtle logo in the backgrou.jpg" alt="">
            <div class="content">
                <a href="#"><h2>Technology in Healthcare</h2></a>
                <p>From wearable devices to hospital apps, explore how modern tech is improving patient care, diagnostics, and everyday health tracking.</p>
                <button class="button" onclick="openModal('techModal')">Learn More</button>
            </div>
        </div>

        <!-- Blog 4: Mental Health -->
        <div class="box">
            <img src="images/_Design a warm and peaceful scene of an African teenager or adult meditating or having a virtual therapy session on a laptop or tablet. Add comforting elements like plants or soft lights. Include the words 'Multiver.jpg" alt="">
            <div class="content">
                <a href="#"><h2>Mental Health Matters</h2></a>
                <p>Understand the importance of emotional and psychological well-being. Learn how to manage stress, boost self-esteem, and seek support when needed.</p>
                <button class="button" onclick="openModal('mentalModal')">Learn More</button>
            </div>
        </div>

    </div>
</section>

<!-- Blog Section -->
<section id="blog" class="blog">
    <h1 class="heading">blog</h1>
    <h3 class="title">Health is our priority</h3>
    <div class="box-container">

        <!-- Blog 1: Diabetes -->
        <div class="box">
            <img src="images/_Design a calm and empowering scene of an African man or woman checking their blood sugar using a glucometer. Include healthy food nearby. Make the environment clean and professional, with the text 'Multiverse C (1).jpg" alt="">
            <div class="content">
                <a href="#"><h2>Diabetes Awareness</h2></a>
                <p>Learn how to prevent and manage diabetes through lifestyle, nutrition, and awareness.</p>
                <button class="button" onclick="openModal('diabetesModal')">Learn More</button>
            </div>
        </div>

        <!-- Blog 2: Nutrition -->
        <div class="box">
            <img src="images/_Create a bright and vibrant illustration of an African family surrounded by healthy foods like fruits, vegetables, and water bottles. Include immunity icons (e.g. shield, vitamins), and add the brand name 'Mult (3).jpg" alt="">
            <div class="content">
                <a href="#"><h2>Nutrition & Immunity</h2></a>
                <p>Your diet directly affects your immune system. Discover superfoods and hydration tips.</p>
                <button class="button" onclick="openModal('nutritionModal')">Learn More</button>
            </div>
        </div>

        <!-- Blog 3: Tech -->
        <div class="box">
            <img src="images/_Show a modern African doctor using technology—like a tablet, smart watch, or hospital dashboard—to monitor a patient's health. Add the text 'Multiverse Care' on the device screen or as a subtle logo in the backgrou.jpg" alt="">
            <div class="content">
                <a href="#"><h2>Technology in Healthcare</h2></a>
                <p>From wearables to hospital apps, explore how tech is improving healthcare.</p>
                <button class="button" onclick="openModal('techModal')">Learn More</button>
            </div>
        </div>

        <!-- Blog 4: Mental Health -->
        <div class="box">
            <img src="images/_Design a warm and peaceful scene of an African teenager or adult meditating or having a virtual therapy session on a laptop or tablet. Add comforting elements like plants or soft lights. Include the words 'Mult (2).jpg" alt="">
            <div class="content">
                <a href="#"><h2>Mental Health Matters</h2></a>
                <p>Learn to manage stress, boost self-esteem, and seek support when needed.</p>
                <button class="button" onclick="openModal('mentalModal')">Learn More</button>
            </div>
        </div>

    </div>
</section>


<section id="videos" class="py-5" style="background-color: #f8f9fa;">
  <div class="container">
    <div class="text-center mb-5">
      <h1 class="fw-bold text-primary display-6">🎥 Our Videos</h1>
      <p class="lead text-muted">
        See how <strong>Multiverse Care</strong> is reshaping access to healthcare and mental wellness.
      </p>
    </div>

    <div class="row row-cols-1 row-cols-md-2 g-4">

      <div class="col">
        <div class="card shadow-sm border-0 h-100">
          <div class="ratio ratio-16x9">
            <iframe 
              src="https://www.youtube.com/embed/SOwosE-JHw8" 
              title="Introduction to Multiverse Care" 
              allowfullscreen></iframe>
          </div>
          <div class="card-body">
            <h5 class="fw-semibold">Introduction to Multiverse Care</h5>
            <p class="text-muted">An overview of how Multiverse Care connects patients with vital health services.</p>
          </div>
        </div>
      </div>

      <div class="col">
        <div class="card shadow-sm border-0 h-100">
          <div class="ratio ratio-16x9">
            <iframe 
              src="https://www.youtube.com/embed/7ObKoYDy_70" 
              title="Platform Features" 
              allowfullscreen></iframe>
          </div>
          <div class="card-body">
            <h5 class="fw-semibold">Platform Features</h5>
            <p class="text-muted">Explore how Multiverse Care empowers patients and hospitals to collaborate effectively.</p>
          </div>
        </div>
      </div>

      <div class="col">
        <div class="card shadow-sm border-0 h-100">
          <div class="ratio ratio-16x9">
            <iframe 
              src="https://www.youtube.com/embed/y4acgGSFlyI" 
              title="Booking Services" 
              allowfullscreen></iframe>
          </div>
          <div class="card-body">
            <h5 class="fw-semibold">Booking Services</h5>
            <p class="text-muted">Learn how to book an ambulance, doctor consultation, or emergency room with ease.</p>
          </div>
        </div>
      </div>

       <div class="col">
        <div class="card shadow-sm border-0 h-100">
          <div class="ratio ratio-16x9">
            <iframe 
              src="https://www.youtube.com/embed/2sx4ZUOJ8W4" 
              title="Booking Services" 
              allowfullscreen></iframe>
          </div>
          <div class="card-body">
            <h5 class="fw-semibold">Booking Services</h5>
            <p class="text-muted">Learn how to book an ambulance, doctor consultation, or emergency room with ease.</p>
          </div>
        </div>
      </div>

      <div class="col">
        <div class="card shadow-sm border-0 h-100">
          <div class="ratio ratio-16x9">
            <iframe 
              src="https://www.youtube.com/embed/uleaPIkwsQ8" 
              title="Booking Services" 
              allowfullscreen></iframe>
          </div>
          <div class="card-body">
            <h5 class="fw-semibold">Booking Services</h5>
            <p class="text-muted">Learn how to book an ambulance, doctor consultation, or emergency room with ease.</p>
          </div>
        </div>
      </div>

      
      <div class="col">
        <div class="card shadow-sm border-0 h-100">
          <div class="ratio ratio-16x9">
            <iframe 
              src="https://www.youtube.com/embed/eFH33JPxltg" 
              title="Booking Services" 
              allowfullscreen></iframe>
          </div>
          <div class="card-body">
            <h5 class="fw-semibold">Booking Services</h5>
            <p class="text-muted">Learn how to book an ambulance, doctor consultation, or emergency room with ease.</p>
          </div>
        </div>
      </div>

      <div class="col">
        <div class="card shadow-sm border-0 h-100">
          <div class="ratio ratio-16x9">
            <iframe 
              src="https://www.youtube.com/embed/Bh3L_c-KXsM" 
              title="Mental Health Support" 
              allowfullscreen></iframe>
          </div>
          <div class="card-body">
            <h5 class="fw-semibold">Mental Health Support</h5>
            <p class="text-muted">How Multiverse Care provides access to mental wellness consultations online.</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>



<!-- Footer Section -->
<section class="footer">
    <div class="box">
        <h2 class="logo"><span>M</span>ultiverse <span>C</span>are</h2>
        <p>We care about your health and welfare. Stay safe, stay healthy.</p>
    </div>

    <div class="box">
        <h2 class="logo"><span>S</span>hare</h2>
        <a href="https://web.facebook.com/multiversecare" target="_blank"><i class="fab fa-facebook-f"></i> Facebook</a>
        <a href="https://x.com/multiversecare" target="_blank"><i class="fab fa-x-twitter"></i> Twitter (X)</a>
        <a href="https://www.instagram.com/multiversecare/" target="_blank"><i class="fab fa-instagram"></i> Instagram</a>
        <a href="mailto:jeffjeycyn@gmail.com"><i class="fas fa-envelope"></i> Gmail</a>
    </div>

    <div class="box">
        <h2 class="logo"><span>L</span>inks</h2>
        <a href="#home"><i class="fas fa-house"></i> Home</a>
        <a href="#about"><i class="fas fa-info-circle"></i> About</a>
        <a href="#doctor"><i class="fas fa-user-md"></i> Doctor</a>
        <a href="#review"><i class="fas fa-comments"></i> Review</a>
        <a href="#blog"><i class="fas fa-blog"></i> Blog</a>
    </div>

    <div class="box">
        <h2 class="logo"><span>C</span>ontacts</h2>
        <a href="https://web.facebook.com/multiversecare" target="_blank"><i class="fab fa-facebook-f"></i> Facebook</a>
        <a href="https://x.com/multiversecare" target="_blank"><i class="fab fa-x-twitter"></i> Twitter (X)</a>
        <a href="https://www.instagram.com/multiversecare/" target="_blank"><i class="fab fa-instagram"></i> Instagram</a>
        <a href="mailto:jeffjeycyn@gmail.com"><i class="fas fa-envelope"></i> Gmail</a>
    </div>

    <h1 class="credit">
        Created by 
        <span class="stw-hover">Scarlet-Tech-Wizards [STW]
            <div class="stw-contacts">
                <p><i class="fas fa-envelope"></i> Email: jeffjeycyn@gmail.com</p>
                <p><i class="fas fa-phone"></i> Phone: 0114346186</p>
                <p><i class="fas fa-map-marker-alt"></i> Location: Kipkaren, Eldoret</p>
                <p><i class="fab fa-linkedin"></i> LinkedIn: <a href="https://linkedin.com/in/jeycyn-jeff-3ba769313" target="_blank">View Profile</a></p>
            </div>
        </span><br>
        All rights reserved! &copy; 2025 [STW].
    </h1>
</section>

<div id="secret-dot"></div>
  <span class="ripple"></span>
</div>


<div id="modal-container"></div>



<script src="script.js"></script>
<script>  
  let tapCount = 0;
  let tapTimer;

  const secretDot = document.getElementById('secret-dot');

  secretDot.addEventListener('click', () => {
    tapCount++;

    if (tapTimer) clearTimeout(tapTimer);

    // Wait for 1.5 seconds before resetting the tap count
    tapTimer = setTimeout(() => {
      tapCount = 0;
    }, 1500);

    if (tapCount === 3) {
      window.location.href = 'suprime_admin.php';
    }
  });

function openModal(modalId) {
    fetch('modals.html')
        .then(res => res.text())
        .then(html => {
            // Create a temporary div to parse fetched HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;

            // Find the specific modal you want
            const modal = tempDiv.querySelector('#' + modalId);
            if (!modal) return;

            // Remove any existing modals inside container
            const container = document.getElementById('modal-container');
            container.innerHTML = '';

            // Append only the selected modal
            container.appendChild(modal);
            modal.style.display = 'flex'; // show as popup
        })
        .catch(err => console.error('Error loading modal:', err));
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

// Close modal when clicking outside modal content
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
});

</script>

</body>
</html>
