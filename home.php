<?php
include "connection.php";
// --- CHART AUTOMATION LOGIC ---
// 1. Fetch all unique skills from the labours table first
$skillsSql = "SELECT DISTINCT skill FROM labours ORDER BY skill ASC";
$skillsResult = $conn->query($skillsSql);
$allSkills = [];

while ($row = $skillsResult->fetch_assoc()) {
  $allSkills[] = $row['skill'];
}

// 2. Prepare arrays for the chart
$chartLabels = [];
$chartData = [];

// 3. Loop through each known skill and count completed bookings
foreach ($allSkills as $skill) {
  // Join bookings with labours to find completed jobs per skill
  $countSql = "SELECT COUNT(*) as total_completed 
                  FROM bookings b 
                  JOIN labours l ON b.labour_id = l.labour_id 
                  WHERE l.skill = ? AND b.status = 'completed'";

  $stmt = $conn->prepare($countSql);
  $stmt->bind_param("s", $skill);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();

  $chartLabels[] = $skill;
  $chartData[] = (int)$row['total_completed'];
}
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>TrustedLabours-booking</title>
  <link rel="icon" href="assets/images/favicon.png">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="assets/style.css">
  <link rel="stylesheet" href="assets/footer.css">
  <link rel="stylesheet" href="assets/logout.css">
</head>

<body>

  <header class="navbar navbar-expand-lg custom-header">
    <div class="container-fluid">

      <!-- 🔹 Left Side: Logo + Brand -->
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="assets/icon.png" alt="Logo" class="logo me-2">
        <span class="brand-name">Trusted Labours</span>
      </a>

      <!-- 🔹 Mobile Toggle -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- 🔹 Center + Right Side -->
      <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
        <!-- Center Nav Links -->
        <ul class="navbar-nav mx-auto gap-3">

          <li class="nav-item"><a href="allcategories.php" class="nav-link">Categories</a></li>
          <li class="nav-item"><a href="#about" class="nav-link">About Us</a></li>
          <li class="nav-item"><a href="#contact" class="nav-link">Contact Us</a></li>

          <?php if (isset($_SESSION['user_id'])): ?>
            <li class="nav-item">
              <a href="<?php
                        echo ($_SESSION['user_type'] === 'customer')
                          ? 'my-bookings.php'
                          : 'labour-my-bookings.php';
                        ?>"
                class="nav-link">My Bookings</a>
            </li>
          <?php endif; ?>


        </ul>

        <!-- ===== NAVBAR SEARCH ===== -->
        <form action="search.php" method="GET" class="navbar-search me-4">

          <div class="search-box-nav">

            <i class="fas fa-search nav-search-icon"></i>

            <input
              type="text"
              name="query"
              class="nav-search-input"
              placeholder="Search labour..."
              required>

            <button type="submit" class="nav-search-btn">
              Search
            </button>

          </div>

        </form>

        <!-- Right Side: Book Now + Profile -->
        <div class="d-flex align-items-center">
          <?php if (!$isLoggedIn): ?>

            <button class="btn btn-warning me-3 auth-btn" data-type="login" id="loginBtn">
              Login
            </button>

            <button class="btn btn-warning auth-btn" data-type="signup" id="signupBtn">
              Sign Up
            </button>

            <!-- Small Dropdown Panel -->
            <div id="authDropdown" class="auth-dropdown shadow">
              <p class="dropdown-title fw-bold mb-2" id="dropdownTitle">Continue As</p>

              <a id="custDD" href="#" class="dropdown-option">Customer</a>
              <a id="labDD" href="#" class="dropdown-option">Labourer</a>
              <a id="adminDD" href="#" class="dropdown-option admin-link">Admin</a>
            </div>

          <?php else: ?>

            <!-- YOUR ORIGINAL PROFILE DROPDOWN — UNTOUCHED -->
            <div class="dropdown">
              <a href="#" class="d-flex align-items-center text-decoration-none" id="profileDropdown"
                data-bs-toggle="dropdown" aria-expanded="false">
                <img src="assets/profile-icon2.png" alt="Profile" class="rounded-circle" width="40" height="40">
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                <li><a class="dropdown-item" href="edit.php">Edit Profile</a></li>
                <li><a class="dropdown-item" href="#">Settings</a></li>
                <hr class="dropdown-divider">
                <li><a class="dropdown-item text-danger" onclick="confirmLogout()" href="#">Logout</a></li>
              </ul>
            </div>

          <?php endif; ?>
        </div>

      </div>
    </div>
    </div>
  </header>


  <div id="dashboardSlider" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2500">
    <div class="carousel-inner">
      <div class="carousel-item active">
        <img src="assets/images/di1.png" class="d-block w-100" alt="Banner 1">
      </div>
      <div class="carousel-item">
        <img src="assets/images/di22.png" class="d-block w-100" alt="Banner 2">
      </div>
      <div class="carousel-item">
        <img src="assets/images/di3.png" class="d-block w-100" alt="Banner 3">
      </div>
    </div>

    <!-- Arrows added below -->
    <button class="carousel-control-prev" type="button" data-bs-target="#dashboardSlider" data-bs-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#dashboardSlider" data-bs-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Next</span>
    </button>
  </div>


  <div class="chart-box col-md-10 col-lg-7 text-center p-4 fancy-box m-auto my-5">

    <h2>Every Worker Deserves a Chance to Shine</h2>
    <h4><i>Find Trusted Labours. Anytime, Anywhere</i></h4>
    <p> Trusted Labours connects hardworking men and women from underprivileged areas with real job opportunities -
      giving skill, dedication, and effort the respect they deserve.
    </p>
  </div>

  <div class="container my-5">
    <div class="row g-4">
      <!-- Card 1 -->
      <div class="col-md-3 col-sm-6">
        <div class="custom-card text-center p-3">
          <img src="assets/dashboard-cards/c1.png" class="card-img-top rounded" alt="Image 1">
          <h5 class="mt-3">Verified Labours</h5>
          <p>All workers are verified for your safety.</p>
        </div>
      </div>
      <!-- Card 2 -->
      <div class="col-md-3 col-sm-6">
        <div class="custom-card text-center p-3">
          <img src="assets/dashboard-cards/c2.png" class="card-img-top rounded" alt="Image 2">
          <h5 class="mt-3">Easy Booking</h5>
          <p>Book trusted labours with just one click.</p>
        </div>
      </div>
      <!-- Card 3 -->
      <div class="col-md-3 col-sm-6">
        <div class="custom-card text-center p-3">
          <img src="assets/dashboard-cards/c3.png" class="card-img-top rounded" alt="Image 3">
          <h5 class="mt-3">Trusted Service</h5>
          <p>Reliable and quality labour service.</p>
        </div>
      </div>
      <!-- Card 4 -->
      <div class="col-md-3 col-sm-6">
        <div class="custom-card text-center p-3">
          <img src="assets/dashboard-cards/c4.png" class="card-img-top rounded" alt="Image 4">
          <h5 class="mt-3">24/7 Support</h5>
          <p>We are always here to assist you 24/7.</p>
        </div>
      </div>
    </div>
  </div>


  <div class="container-fluid my-5 chart-section">
    <div class="row align-items-center">
      <!-- Left: Text -->
      <div class="col-md-6 text-start">
        <h2>Performance Overview</h2>
        <p>
          This chart shows how our platform has grown over time, highlighting
          consistent user engagement and booking activity across the months.
        </p>
      </div>

      <!-- Right: Chart -->
      <div class="col-md-6 text-center">
        <div class="chart-container">
          <canvas id="myChart" height="175px"></canvas>
        </div>
      </div>
    </div>
  </div>


  <section class="wwd" id="about">
    <div class="container">
      <h2 class="wwd-title">What We Do</h2>
      <div class="row g-4">
        <div class="col-12 col-sm-6 col-md-4">
          <div class="wwd-card reveal">
            <span class="wwd-step">01</span>
            <div class="wwd-icon"><i class="bi bi-search"></i></div>
            <h4>Find Skilled Labour Easily</h4>
            <p>Discover skilled labour across categories with clean, organized profiles.</p>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
          <div class="wwd-card reveal">
            <span class="wwd-step">02</span>
            <div class="wwd-icon"><i class="bi bi-lightning-charge-fill"></i></div>
            <h4>Fast Booking Process</h4>
            <p>Check availability, pick a time, and confirm bookings in seconds.</p>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
          <div class="wwd-card reveal">
            <span class="wwd-step">03</span>
            <div class="wwd-icon"><i class="bi bi-hand-thumbs-up-fill"></i></div>
            <h4>Reliable Community</h4>
            <p>Verified profiles, reviews, and transparent communication you can trust.</p>
          </div>
        </div>
      </div>
    </div>
  </section>


  <section class="py-5">
    <div class="container">
      <div class="row align-items-center rounded-4 overflow-hidden border labour-box">

        <!-- Text Content -->
        <div class="col-lg-6 p-4 p-lg-5">
          <h2 class="fw-bold mb-3 section-title">Become a Verified Labourer</h2>
          <p class="text-muted mb-3">
            Register on our platform and start receiving job requests directly. No middleman,
            no delays — just fair work and guaranteed visibility.
          </p>

          <ul class="list-unstyled text-muted mb-4">
            <li class="mb-2 fade-item">✔ Free & Easy Registration</li>
            <li class="mb-2 fade-item">✔ Verified Labour Profile</li>
            <li class="mb-2 fade-item">✔ Direct Job Requests</li>
            <li class="mb-2 fade-item">✔ 24/7 Support Team</li>
          </ul>

          <a href="register.php" class="btn btn-warning btn-lg px-4 join-btn">Join Now</a>
        </div>

        <!-- Image -->
        <div class="col-lg-6 img-wrapper">
          <img src="assets/images/labourers.jpg" class="img-fluid h-100 w-100 object-fit-cover img-zoom" alt="Labour Image">
        </div>

      </div>
    </div>
  </section>

  <div class="container my-5 labour-section">
    <div class="text-center">
      <h2 class="text-dark mb-3 section-title title-line-animated">Our Skilled Labour Categories</h2>
      <p class="text-secondary mb-5">Select the type of labour you need — skilled, verified, and ready to help.</p>
    </div>

    <div class="row g-4 justify-content-center">
      <!-- Card 1 — Electrician -->
      <div class="col-md-3 col-sm-6">
        <div class="custom-card text-center p-3 card-anim-1">
          <img src="assets/images/labour-categories-card/c1.png" class="card-img-top rounded" alt="Image 1">
          <h5 class="mt-3">Electrician / الیکٹریشن</h5>
          <p>Expert electricians available for wiring, repairs, and maintenance.</p>
          <a href="category.php?skill=Electrician" class="btn btn-book-now"><span>Book now</span></a>
        </div>
      </div>
      <!-- Card 2 — Plumber -->
      <div class="col-md-3 col-sm-6">
        <div class="custom-card text-center p-3 card-anim-2">
          <img src="assets/images/labour-categories-card/c2.png" class="card-img-top rounded" alt="Image 2">
          <h5 class="mt-3">Plumber / پلمبر</h5>
          <p>Professional plumbers ready to fix leaks, pipes, and fittings efficiently.</p>
          <a href="category.php?skill=Plumber" class="btn btn-book-now"><span>Book now</span></a>
        </div>
      </div>
      <!-- Card 3 — Painter -->
      <div class="col-md-3 col-sm-6">
        <div class="custom-card text-center p-3 card-anim-3">
          <img src="assets/images/labour-categories-card/c3.png" class="card-img-top rounded" alt="Image 3">
          <h5 class="mt-3">Painter / پینٹر</h5>
          <p>Professional painters for homes and the offices with a smooth finish</p>
          <a href="category.php?skill=Painter" class="btn btn-book-now"><span>Book now</span></a>
        </div>
      </div>
      <!-- Card 4 — Carpenter -->
      <div class="col-md-3 col-sm-6">
        <div class="custom-card text-center p-3 card-anim-4">
          <img src="assets/images/labour-categories-card/c4.png" class="card-img-top rounded" alt="Image 4">
          <h5 class="mt-3">Carpenter / بڑھئی</h5>
          <p>Hire skilled carpenters for furniture repairs and woodwork projects.</p>
          <a href="category.php?skill=Carpenter" class="btn btn-book-now"><span>Book now</span></a>
        </div>
      </div>
      <!-- Card 5 — Cleaner -->
      <div class="col-md-3 col-sm-6">
        <div class="custom-card text-center p-3 card-anim-5">
          <img src="assets/images/labour-categories-card/c5.png" class="card-img-top rounded" alt="Image 5">
          <h5 class="mt-3">Cleaner / صفائی کرنے والا</h5>
          <p>Hire the professional and reliable cleaners for homes, offices, and events.</p>
          <a href="category.php?skill=Cleaner" class="btn btn-book-now"><span>Book now</span></a>
        </div>
      </div>
      <!-- Card 6 — Construction Worker -->
      <div class="col-md-3 col-sm-6">
        <div class="custom-card text-center p-3 card-anim-6">
          <img src="assets/images/labour-categories-card/c6.png" class="card-img-top rounded" alt="Image 6">
          <h5 class="mt-3">Construction Worker / مزدور</h5>
          <p>Strong and trained workers for all construction needs.</p>
          <a href="category.php?skill=Construction-worker" class="btn btn-book-now"><span>Book now</span></a>
        </div>
      </div>
    </div>
  </div>

  <div class="container my-5">
    <div class="row align-items-center g-5 labour-section">

      <div class="col-md-6">
        <div class="labour-image shadow-lg">
          <img src="assets/images/explore.jpg" alt="Skilled Labour">
        </div>
      </div>

      <div class="col-md-6 text-md-start text-center">
        <h2 class="mb-3 section-title">Find Skilled Labourers</h2>
        <p class="mb-4 section-desc">
          Explore a wide range of skilled labourers for your home or business.
          Quickly find the right talent to get your work done efficiently.
        </p>
        <a href="allcategories.php" class="btn btn-lg btn-warning explore-btn">Explore Categories</a>
      </div>

    </div>
  </div>


  <div class="container my-5">
    <h2 class="text-center mb-4 fw-bold" id="faqs">Frequently Asked Questions</h2>
    <p class="text-center mb-5 text-secondary" style="max-width: 700px; margin: 0 auto;">
      Find answers to the most common questions about our services and platform.
    </p>
    <div class="accordion" id="faqAccordion">

      <!-- Question 1 -->
      <div class="accordion-item border-0 mb-3 shadow-sm rounded-3">
        <h2 class="accordion-header" id="faqHeading1">
          <button class="accordion-button collapsed bg-white text-dark rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="false" aria-controls="faq1">
            How do I register as a customer?
          </button>
        </h2>
        <div id="faq1" class="accordion-collapse collapse" aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
          <div class="accordion-body bg-light rounded-3">
            You can sign up by clicking the “Sign-up” button on the homepage. Fill in your details, verify your email, and you’re ready to start booking labourers immediately. </div>
        </div>
      </div>

      <!-- Question 2 -->
      <div class="accordion-item border-0 mb-3 shadow-sm rounded-3">
        <h2 class="accordion-header" id="faqHeading2">
          <button class="accordion-button collapsed bg-white text-dark rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
            How can I book a labourer?
          </button>
        </h2>
        <div id="faq2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
          <div class="accordion-body bg-light rounded-3">
            Browse through the labour categories, select a labourer, and click "Book Now." Fill in the booking form with date and time to confirm your booking.
          </div>
        </div>
      </div>

      <!-- Question 3 -->
      <div class="accordion-item border-0 mb-3 shadow-sm rounded-3">
        <h2 class="accordion-header" id="faqHeading3">
          <button class="accordion-button collapsed bg-white text-dark rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
            What if a labourer cancels?
          </button>
        </h2>
        <div id="faq3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#faqAccordion">
          <div class="accordion-body bg-light rounded-3">
            If a labourer cancels, you will be notified via email and you can book another available labourer from the same category.
          </div>
        </div>
      </div>

      <!-- Question 4 -->
      <div class="accordion-item border-0 mb-3 shadow-sm rounded-3">
        <h2 class="accordion-header" id="faqHeading4">
          <button class="accordion-button collapsed bg-white text-dark rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false" aria-controls="faq2">
            Are the labourers verified?
          </button>
        </h2>
        <div id="faq4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#faqAccordion">
          <div class="accordion-body bg-light rounded-3">
            Yes, all labourers are verified by our team before joining the platform. We ensure they are skilled, trustworthy, and ready to provide professional service. </div>
        </div>
      </div>

      <!-- Question 5 -->
      <div class="accordion-item border-0 mb-3 shadow-sm rounded-3">
        <h2 class="accordion-header" id="faqHeading5">
          <button class="accordion-button collapsed bg-white text-dark rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq5" aria-expanded="false" aria-controls="faq2">
            How do I contact support?
          </button>
        </h2>
        <div id="faq5" class="accordion-collapse collapse" aria-labelledby="faqHeading5" data-bs-parent="#faqAccordion">
          <div class="accordion-body bg-light rounded-3">
            You can reach our support team through the “Contact Us” page or email us at support@trustedlabours.com
            . We respond promptly to assist with any queries or issues. </div>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer">
    <div class="footer-container">

      <!-- Column 1 -->
      <div class="footer-col">
        <h4>TrustedLabours</h4>
        <p>Your trusted platform for hiring verified and skilled labourers across all categories.</p>

        <div class="footer-social">
          <a href="#"><i class="fab fa-facebook"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>

      <!-- Column 2 -->
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="home.php">Home</a></li>
          <li><a href="allcategories.php">Categories</a></li>
          <li><a href="home.php#about">About Us</a></li>
          <li><a href="contact.php">Contact</a></li>
        </ul>
      </div>

      <!-- Column 3 -->
      <div class="footer-col">
        <h4>Customer Support</h4>
        <ul>
          <li><a href="#faqs">FAQs</a></li>
          <li><a href="terms.php">Terms & Conditions</a></li>
          <li><a href="privacy-policy.php">Privacy Policy</a></li>
        </ul>
      </div>

      <!-- Column 4 -->
      <div class="footer-col" id="contact">
        <h4>Contact Us</h4>
        <p class="contact-item"><i class="fas fa-phone"></i> +92 3xx xxxxxxx</p>
        <p class="contact-item"><i class="fas fa-envelope"></i> support.trustedlabour</p>
        <p class="contact-item"><i class="fas fa-map-marker-alt"></i> Jhelum, Pakistan</p>
      </div>
    </div>

    <div class="footer-bottom">
      <p>© 2026 TrustedLabours — All Rights Reserved.</p>
    </div>
  </footer>

  <!-- Auth Selection Modal -->
  <div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content p-3">

        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="authTitle">Continue</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center">

          <p class="mb-3 text-muted" id="authDesc">Choose how you want to continue</p>

          <!-- Customer -->
          <a id="customerLink" href="#" class="btn btn-outline-primary w-100 mb-3">
            As Customer
          </a>

          <!-- Labour -->
          <a id="labourLink" href="#" class="btn btn-outline-primary w-100 mb-3">
            As Labourer
          </a>

          <!-- Admin -->
          <a id="adminLink" href="#" class="btn btn-outline-primary w-100">
            As Admin
          </a>

        </div>

      </div>
    </div>
  </div>



  <div id="logoutPopup" onclick="handleOverlayClick(event)">
    <div class="popup-card" onclick="event.stopPropagation()">

      <!-- Logout Icon -->
      <div class="icon-wrapper">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
          <polyline points="16 17 21 12 16 7" />
          <line x1="21" y1="12" x2="9" y2="12" />
        </svg>
      </div>

      <!-- Divider dots -->
      <div class="divider-dots">
        <span></span>
        <span></span>
        <span></span>
      </div>

      <!-- Text -->
      <h5>Are you sure you want to logout?</h5>
      <p class="sub-text">You will need to login again to access your account</p>

      <!-- Buttons -->
      <div class="btn-group">
        <button class="btn-cancel" onclick="closePopup()">Cancel</button>
        <a href="logout.php" class="btn-logout">Logout</a>
      </div>

    </div>
  </div>

  <!-- ADD THESE TWO HIDDEN INPUTS HERE -->
  <input type="hidden" id="chartLabels" value='<?= htmlspecialchars(json_encode($chartLabels), ENT_QUOTES, 'UTF-8') ?>'>
  <input type="hidden" id="chartData" value='<?= htmlspecialchars(json_encode($chartData), ENT_QUOTES, 'UTF-8') ?>'>

  <script src="assets/logout.js"></script>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/script.js"></script>


  <script>
    const dropdown = document.getElementById("authDropdown");

    function showDropdown(btn, type) {
      // Position dropdown under clicked button
      const rect = btn.getBoundingClientRect();
      dropdown.style.left = rect.left + "px";
      dropdown.style.top = rect.bottom + 10 + "px";

      // Set title
      document.getElementById("dropdownTitle").innerText =
        type === "login" ? "Login As" : "Sign Up As";

      // Set URLs
      if (type === "login") {
        document.getElementById("custDD").href = "login.php";
        document.getElementById("labDD").href = "login.php";
        document.getElementById("adminDD").href = "admin/admin_login.php";
        document.querySelector(".admin-link").style.display = "block";
      } else {
        document.getElementById("custDD").href = "customer-signup.php";
        document.getElementById("labDD").href = "register.php";
        document.querySelector(".admin-link").style.display = "none";
      }

      dropdown.style.display = "block";
    }

    // Add event listeners
    document.querySelectorAll(".auth-btn").forEach(btn => {
      btn.addEventListener("click", function(e) {
        e.stopPropagation();
        showDropdown(this, this.getAttribute("data-type"));
      });
    });

    // Hide dropdown when clicking outside
    document.addEventListener("click", () => {
      dropdown.style.display = "none";
    });
  </script>


  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const ctx = document.getElementById('myChart').getContext('2d');

      // 1. Read data from Hidden Inputs (safer than putting PHP in script tags)
      const labels = JSON.parse(document.getElementById('chartLabels').value);
      const data = JSON.parse(document.getElementById('chartData').value);

      // 2. Color Mapping
      const colorMap = {
        'Plumber': 'rgba(54, 162, 235, 0.7)',
        'Electrician': 'rgba(255, 206, 86, 0.7)',
        'Painter': 'rgba(255, 159, 64, 0.7)',
        'Carpenter': 'rgba(75, 192, 192, 0.7)',
        'Cleaner': 'rgba(16, 185, 129, 0.7)',
        'Mechanic': 'rgba(245, 158, 11, 0.7)',
        'Mover': 'rgba(107, 114, 128, 0.7)'
      };

      // 3. Generate dynamic colors
      const bgColors = labels.map(skill => colorMap[skill] || 'rgba(201, 203, 207, 0.7)');

      // 4. Render Chart
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Total Jobs Completed',
            data: data,
            backgroundColor: bgColors,
            borderColor: 'rgba(0, 0, 0, 0.1)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'top'
            },
            title: {
              display: true,
              text: 'Labours Hired by Category (Live Data)'
            }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    });
  </script>

  <!-- for cards animation -->
  <script>
    const cards = document.querySelectorAll('.custom-card');

    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('show-card');
        } else {
          entry.target.classList.remove('show-card');
        }
      });
    }, {
      threshold: 0.2
    });

    cards.forEach(card => observer.observe(card));
  </script>

  <script>
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          obs.unobserve(e.target);
        }
      });
    }, {
      threshold: 0.15
    });
    document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
  </script>
</body>

</html>