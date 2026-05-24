<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Labour Categories - Labour Booking</title>
  <link rel="icon" href="assets/images/favicon.png">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    :root {
        --mustard: #D4A017;
        --mustard-dark: #B8860B;
        --mustard-light: #FFF8E1;
        --black: #121212;
        --black-light: #1E1E1E;
        --silver: #A8A8A8;
        --silver-light: #E8E8E8;
        --silver-bg: #F4F4F4;
        --white: #FFFFFF;
        --text-main: #1A1A1A;
        --text-muted: #6B6B6B;
        --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06);
        --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
        --shadow-lg: 0 12px 30px rgba(0, 0, 0, 0.12);
        --radius: 14px;
    }

    * {
        font-family: 'Inter', sans-serif;
    }

    body {
        background: var(--silver-bg);
        color: var(--text-main);
    }

    /* --- Header Section --- */
    .categories-container {
        padding-top: 60px;
        padding-bottom: 40px;
    }

    .categories-container h1 {
        text-align: center;
        font-size: 2.8rem;
        font-weight: 800;
        color: var(--black);
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: -1px;
    }

    .categories-container h1 span {
        color: var(--mustard-dark);
    }

    .category-description {
        max-width: 600px;
        margin: 0 auto 50px auto;
        text-align: center;
        font-size: 1.05rem;
        color: var(--silver);
        line-height: 1.7;
        font-weight: 500;
    }

    /* --- Card Design --- */
    .category-card {
        background: var(--white);
        border-radius: var(--radius);
        padding: 24px 28px;
        display: flex;
        align-items: center;
        gap: 24px;
        border: 1px solid var(--silver-light);
        border-left: 5px solid transparent; /* Hidden border for hover effect */
        box-shadow: var(--shadow-sm);
        transition: all .3s cubic-bezier(.4,0,.2,1);
        cursor: pointer;
        text-decoration: none;
        color: var(--text-main);
        position: relative;
        overflow: hidden;
    }

    .category-card:hover {
        transform: translateY(-6px);
        box-shadow: var(--shadow-lg);
        border-left-color: var(--mustard); /* Mustard slide-in effect */
        background: var(--mustard-light); /* Very subtle warm tint */
    }

    /* --- Icon Design --- */
    .category-icon {
        font-size: 1.8rem;
        width: 68px;
        height: 68px;
        min-width: 68px; /* Prevents shrinking */
        border-radius: 14px;
        background: var(--black);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all .3s ease;
        color: var(--mustard);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }

    .category-card:hover .category-icon {
        transform: scale(1.08) rotate(-5deg);
        background: var(--black-light);
        box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    }

    /* --- Text Content --- */
    .category-info {
        flex-grow: 1;
    }

    .category-info h4 {
        font-size: 1.25rem;
        margin: 0 0 4px;
        font-weight: 700;
        color: var(--black);
        transition: color .3s ease;
    }

    .category-info p {
        margin: 0;
        font-size: 0.9rem;
        color: var(--silver);
        font-weight: 500;
    }

    .category-card:hover .category-info h4 {
        color: var(--mustard-dark);
    }

    /* --- Arrow --- */
    .category-arrow {
        font-size: 1.5rem;
        color: var(--silver-light);
        transition: all .3s ease;
        font-weight: 300;
    }

    .category-card:hover .category-arrow {
        transform: translateX(6px);
        color: var(--mustard);
    }

    /* --- Premium Badge --- */
    .avail-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: var(--mustard);
        color: var(--black);
        font-size: 0.65rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 50px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0;
        transition: opacity .3s ease;
    }

    .category-card:hover .avail-badge {
        opacity: 1;
    }


    /* --- Footer Theme Override --- */
    .footer {
        background: var(--black);
        color: var(--silver);
        padding: 60px 0 0 0;
        margin-top: 80px;
        border-top: 1px solid var(--black-light);
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 40px;
    }

    .footer-col h4 {
        color: var(--white);
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 20px;
        position: relative;
        padding-bottom: 10px;
    }

    .footer-col h4::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 30px;
        height: 3px;
        background: var(--mustard);
        border-radius: 2px;
    }

    .footer-col p {
        font-size: 0.9rem;
        line-height: 1.7;
        color: var(--silver);
    }

    .footer-col ul {
        list-style: none;
        padding: 0;
    }

    .footer-col ul li a {
        color: var(--silver);
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: inline-block;
        margin-bottom: 10px;
    }

    .footer-col ul li a:hover {
        color: var(--mustard);
        transform: translateX(4px);
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px !important;
        font-size: 0.9rem !important;
    }

    .contact-item i {
        color: var(--mustard);
        width: 16px;
        text-align: center;
    }

    .footer-social {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }

    .footer-social a {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--black-light);
        color: var(--silver);
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid #333;
    }

    .footer-social a:hover {
        background: var(--mustard);
        color: var(--black);
        border-color: var(--mustard);
        transform: translateY(-3px);
    }

    .footer-bottom {
        text-align: center;
        padding: 20px 0;
        margin-top: 40px;
        border-top: 1px solid #333;
        font-size: 0.85rem;
        color: #666;
    }

    /* --- Responsive --- */
    @media (max-width: 768px) {
        .categories-container h1 { font-size: 2.2rem; }
        .category-card { padding: 20px; gap: 18px; }
        .category-icon { width: 60px; height: 60px; min-width: 60px; font-size: 1.5rem; }
    }

    @media (max-width: 576px) {
        .category-card {
            flex-direction: column;
            text-align: center;
            gap: 15px;
            border-left: 5px solid transparent;
            border-bottom: 5px solid transparent;
        }
        .category-card:hover {
            border-left-color: transparent;
            border-bottom-color: var(--mustard);
        }
        .category-arrow { display: none; }
        .avail-badge { top: 10px; right: 10px; opacity: 1; }
    }
  </style>
</head>

<body>

  <div class="container categories-container">
    <h1>Find Your <span>Expert</span></h1>

    <p class="category-description">
        Choose from a wide range of skilled labour categories. Select the service you need and explore verified workers ready to assist.
    </p>

    <div class="row gy-4">

        <!-- Plumber -->
        <div class="col-lg-6">
            <a href="category.php?skill=Plumber" class="category-card text-decoration-none">
                <span class="avail-badge">Available</span>
                <i class="fa-solid fa-wrench category-icon"></i>
                <div class="category-info">
                    <h4>Plumbers</h4>
                    <p>Skilled professionals for plumbing and water system services.</p>
                </div>
                <span class="category-arrow">→</span>
            </a>
        </div>

        <!-- Electrician -->
        <div class="col-lg-6">
            <a href="category.php?skill=Electrician" class="category-card text-decoration-none">
                <span class="avail-badge">Available</span>
                <i class="fa-solid fa-bolt category-icon"></i>
                <div class="category-info">
                    <h4>Electricians</h4>
                    <p>Certified electricians for residential and commercial work.</p>
                </div>
                <span class="category-arrow">→</span>
            </a>
        </div>

        <!-- Carpenter -->
        <div class="col-lg-6">
            <a href="category.php?skill=Carpenter" class="category-card text-decoration-none">
                <span class="avail-badge">Available</span>
                <i class="fa-solid fa-hammer category-icon"></i>
                <div class="category-info">
                    <h4>Carpenters</h4>
                    <p>Expert carpenters for furniture, woodwork, and renovations.</p>
                </div>
                <span class="category-arrow">→</span>
            </a>
        </div>

        <!-- Cleaner -->
        <div class="col-lg-6">
            <a href="category.php?skill=Cleaner" class="category-card text-decoration-none">
                <span class="avail-badge">Available</span>
                <i class="fa-solid fa-broom category-icon"></i>
                <div class="category-info">
                    <h4>Cleaners</h4>
                    <p>Professional cleaning services for home and office spaces.</p>
                </div>
                <span class="category-arrow">→</span>
            </a>
        </div>

        <!-- Painter -->
        <div class="col-lg-6">
            <a href="category.php?skill=Painter" class="category-card text-decoration-none">
                <span class="avail-badge">Available</span>
                <i class="fa-solid fa-paint-roller category-icon"></i>
                <div class="category-info">
                    <h4>Painters</h4>
                    <p>Experienced painters for walls, buildings, and decorative work.</p>
                </div>
                <span class="category-arrow">→</span>
            </a>
        </div>

        <!-- Movers -->
        <div class="col-lg-6">
            <a href="category.php?skill=Mover" class="category-card text-decoration-none">
                <span class="avail-badge">Available</span>
                <i class="fa-solid fa-truck-moving category-icon"></i>
                <div class="category-info">
                    <h4>Movers</h4>
                    <p>Reliable labourers for packing, moving, and delivery services.</p>
                </div>
                <span class="category-arrow">→</span>
            </a>
        </div>

    </div>
</div>

  <!-- FOOTER (Themed to match) -->
  <footer class="footer">
    <div class="footer-container">

      <!-- Column 1 -->
      <div class="footer-col">
        <h4>TrustedLabours</h4>
        <p>Your trusted platform for hiring verified and skilled labourers across all categories.</p>

        <div class="footer-social">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
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
          <li><a href="faq.php">FAQs</a></li>
          <li><a href="terms.php">Terms & Conditions</a></li>
          <li><a href="privacy.php">Privacy Policy</a></li>
        </ul>
      </div>

      <!-- Column 4 -->
      <div class="footer-col">
        <h4>Contact Us</h4>
        <p class="contact-item"><i class="fas fa-phone"></i> +92 300 1234567</p>
        <p class="contact-item"><i class="fas fa-envelope"></i> support@trustedlabours.com</p>
        <p class="contact-item"><i class="fas fa-map-marker-alt"></i> Jhelum, Pakistan</p>
      </div>
    </div>

    <div class="footer-bottom">
      <p>© 2026 TrustedLabours — All Rights Reserved.</p>
    </div>
  </footer>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>