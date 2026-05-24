<?php
include 'connection.php';

// Initialize variables
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$skillFilter = isset($_GET['skill']) ? trim($_GET['skill']) : '';

// Prepare the base SQL query with a JOIN to get average ratings
// We use LEFT JOIN to ensure labours with no reviews still appear
$sql = "SELECT 
            l.*, 
            COALESCE(AVG(r.rating), 0) as average_rating,
            COUNT(r.review_id) as review_count
        FROM labours l
        LEFT JOIN bookings b ON l.labour_id = b.labour_id
        LEFT JOIN reviews r ON b.booking_id = r.booking_id
        WHERE 1=1 ";

$params = [];
$types = "";

// Add Search Logic (Name or Skill)
if (!empty($query)) {
  $sql .= " AND (l.name LIKE ? OR l.skill LIKE ? OR l.location LIKE ?) ";
  $searchTerm = "%" . $query . "%";
  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $params[] = $searchTerm;
  $types .= "sss";
}

// Add Skill Filter Logic
if (!empty($skillFilter)) {
  $sql .= " AND l.skill = ? ";
  $params[] = $skillFilter;
  $types .= "s";
}

$sql .= " GROUP BY l.labour_id ORDER BY l.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// --- SIDEBAR QUERY ---
// Fetches all distinct skills available for filtering
$skillOptions = [];
$skillSql = "SELECT DISTINCT skill FROM labours ORDER BY skill ASC";
$skillRes = $conn->query($skillSql);
while ($row = $skillRes->fetch_assoc()) {
  $skillOptions[] = $row['skill'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Search Results - TrustedLabours</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --primary: #f59e0b;
      --secondary: #f97316;
      --dark: #1e293b;
      --light: #f8f9fa;
      --gray: #64748b;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: #fefce8;
      /* Matches your home page bg */
      color: var(--dark);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* === Navbar === */
    .navbar-custom {
      background: #ffffff;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      padding: 1rem 0;
    }

    .navbar-brand img {
      height: 40px;
      margin-right: 10px;
    }

    .nav-link {
      color: var(--dark);
      font-weight: 500;
      transition: color 0.3s;
    }

    .nav-link:hover {
      color: var(--secondary);
    }

    /* === Search Header Section === */
    .search-header {
      background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
      padding: 60px 0 40px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      margin-bottom: 40px;
    }

    .main-search-box {
      position: relative;
      max-width: 700px;
      margin: 0 auto;
    }

    .main-search-input {
      width: 100%;
      padding: 18px 60px 18px 25px;
      border-radius: 50px;
      border: 2px solid #e2e8f0;
      font-size: 1.1rem;
      box-shadow: 0 10px 25px rgba(245, 158, 11, 0.1);
      transition: all 0.3s ease;
    }

    .main-search-input:focus {
      border-color: var(--primary);
      box-shadow: 0 10px 30px rgba(245, 158, 11, 0.25);
      outline: none;
    }

    .search-icon-btn {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      background: var(--primary);
      color: white;
      border: none;
      width: 45px;
      height: 45px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background 0.3s;
    }

    .search-icon-btn:hover {
      background: var(--secondary);
    }

    /* === Filters === */
    .filter-sidebar {
      background: white;
      padding: 25px;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
      position: sticky;
      top: 100px;
    }

    .filter-title {
      font-weight: 700;
      margin-bottom: 20px;
      color: var(--dark);
      border-bottom: 2px solid #f1f5f9;
      padding-bottom: 10px;
    }

    .form-check-input:checked {
      background-color: var(--primary);
      border-color: var(--primary);
    }

    /* === Result Cards === */
    .labour-card {
      background: white;
      border-radius: 16px;
      border: 1px solid rgba(0, 0, 0, 0.05);
      padding: 25px;
      height: 100%;
      transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    .labour-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 15px 30px rgba(245, 158, 11, 0.15);
      border-color: rgba(245, 158, 11, 0.3);
    }

    /* Top accent line */
    .labour-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      opacity: 0;
      transition: opacity 0.3s;
    }

    .labour-card:hover::before {
      opacity: 1;
    }

    .card-header-row {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }

    .labour-avatar {
      width: 70px;
      height: 70px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #fff;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      margin-right: 15px;
      background-color: #f0f0f0;
      /* Fallback bg */
    }

    .labour-info h5 {
      font-weight: 700;
      margin-bottom: 2px;
      color: var(--dark);
    }

    .labour-skill {
      display: inline-block;
      background: #fff7ed;
      color: var(--secondary);
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .labour-meta {
      color: var(--gray);
      font-size: 0.9rem;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .labour-meta i {
      color: var(--primary);
      margin-right: 5px;
    }

    .rating-stars {
      color: #fbbf24;
      font-size: 0.9rem;
    }

    .review-count {
      color: var(--gray);
      font-size: 0.8rem;
      margin-left: 5px;
    }

    .card-btn {
      margin-top: auto;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      text-align: center;
      text-decoration: none;
      transition: transform 0.2s;
    }

    .card-btn:hover {
      transform: scale(1.02);
      color: white;
      box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);
    }

    /* === Empty State === */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 16px;
    }

    .empty-icon {
      font-size: 4rem;
      color: #cbd5e1;
      margin-bottom: 20px;
    }

    /* Footer */
    footer {
      margin-top: auto;
      background: var(--dark);
      color: white;
      padding: 40px 0;
      text-align: center;
    }

    /* === Status Badge Styles === */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.75rem;
      padding: 4px 12px;
      border-radius: 20px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-available {
      background-color: #dcfce7;
      /* Light Green */
      color: #166534;
      /* Dark Green */
    }

    .status-busy {
      background-color: #fee2e2;
      /* Light Red */
      color: #991b1b;
      /* Dark Red */
    }
  </style>
</head>

<body>

  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center fw-bold" href="home.php">
        <i class="text-warning fa-2x me-2"></i>
        TrustedLabours
      </a>
      <div class="d-flex">
        <a href="home.php" class="btn btn-outline-secondary rounded-pill px-4">
          <i class="fas fa-home me-2"></i> Back Home
        </a>
      </div>
    </div>
  </nav>

  <!-- Header & Search -->
  <section class="search-header">
    <div class="container">
      <div class="text-center mb-4">
        <h1 class="fw-bold mb-2">Find the Perfect Professional</h1>
        <p class="text-muted">Search by name, skill (e.g., Plumber), or location.</p>
      </div>

      <form action="search.php" method="GET" class="main-search-box">
        <input type="text" name="query" class="main-search-input"
          placeholder="What service do you need?"
          value="<?= htmlspecialchars($query) ?>" autofocus>
        <button type="submit" class="search-icon-btn">
          <i class="fas fa-search"></i>
        </button>
      </form>
    </div>
  </section>

  <!-- Main Content -->
  <div class="container mb-5">
    <div class="row">

      <!-- Sidebar Filters -->
      <div class="col-lg-3 mb-4">
        <div class="filter-sidebar">
          <h5 class="filter-title"><i class="fas fa-filter me-2 text-warning"></i>Filter By</h5>

          <h6 class="fw-bold mb-3 text-dark">Skill Category</h6>
          <div class="list-group list-group-flush mb-4">

            <!-- Link for All Skills -->
            <a href="?query=<?= urlencode($query) ?>"
              class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= empty($skillFilter) ? 'active' : '' ?>">
              All Skills
              <!-- Show total count if no filter is active -->
              <?php if (empty($skillFilter)): ?>
                <span class="badge bg-light text-dark rounded-pill"><?= $result->num_rows ?></span>
              <?php endif; ?>
            </a>

            <?php foreach ($skillOptions as $skill): ?>

              <?php
              // Calculate count for this specific skill from the current result set
              $currentSkillCount = 0;
              $result->data_seek(0); // Reset pointer to count
              while ($row = $result->fetch_assoc()) {
                if ($row['skill'] == $skill) $currentSkillCount++;
              }
              $result->data_seek(0); // Reset pointer again for the main loop below
              ?>

              <!-- Use urlencode() for query and skill to ensure safe URLs -->
              <a href="?query=<?= urlencode($query) ?>&skill=<?= urlencode($skill) ?>"
                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $skillFilter === $skill ? 'active' : '' ?>">
                <?= htmlspecialchars($skill) ?>

                <!-- Show count badge -->
                <span class="badge bg-light text-dark rounded-pill">
                  <?= $currentSkillCount ?>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Results Grid -->
      <div class="col-lg-9">

        <?php if ($result->num_rows > 0): ?>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="text-muted mb-0">Found <span class="fw-bold text-dark"><?= $result->num_rows ?></span> professional(s)</h5>
            <small class="text-muted">Sorted by: Newest</small>
          </div>

                    <div class="row g-4">
            <?php 
            // Define base URL for images
            $base_url = "/"; 
            
            while ($row = $result->fetch_assoc()): 
                
                // --- IMAGE LOGIC ---
                if (!empty($row['image'])) {
                    $imageName = ltrim($row['image'], '/');
                    $imgPath = $base_url . 'assets/images/profilePicture/' . $imageName;
                } else {
                    $imgPath = 'https://ui-avatars.com/api/?name=' . urlencode($row['name']) . '&background=0D8ABC&color=fff';
                }
                
                // --- RATING LOGIC ---
                $rating = round($row['average_rating'], 1);
                $stars = '';
                for($i=1; $i<=5; $i++) {
                    if($i <= floor($rating)) $stars .= '<i class="fas fa-star"></i>';
                    elseif($i == ceil($rating) && !is_int($rating)) $stars .= '<i class="fas fa-star-half-alt"></i>';
                    else $stars .= '<i class="far fa-star"></i>';
                }

                // --- STATUS LOGIC (New) ---
                $status = $row['live_status'];
                if($status === 'available') {
                    $statusClass = 'status-available';
                    $statusIcon = 'fa-check-circle';
                } else {
                    $statusClass = 'status-busy';
                    $statusIcon = 'fa-clock';
                }
            ?>
              <div class="col-md-6 col-xl-4">
                <div class="labour-card">
                  <div class="card-header-row">
                    <img src="<?= $imgPath ?>" alt="Labour" class="labour-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($row['name']) ?>&background=random'">
                    <div class="labour-info">
                      <h5><?= htmlspecialchars($row['name']) ?></h5>
                      
                      <!-- Skill & Status Wrapper -->
                      <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="labour-skill"><?= htmlspecialchars($row['skill']) ?></span>
                        
                        <!-- Status Badge (New) -->
                        <span class="status-badge <?= $statusClass ?>">
                            <i class="fas <?= $statusIcon ?>"></i> 
                            <?= ucfirst($status) ?>
                        </span>
                      </div>

                    </div>
                  </div>
                  
                  <div class="labour-meta">
                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['location']) ?></span>
                  </div>

                  <div class="mb-3">
                    <span class="rating-stars"><?= $stars ?></span>
                    <span class="review-count">(<?= $row['review_count'] ?> Reviews)</span>
                  </div>

                  <a href="labour_profile.php?id=<?= $row['labour_id'] ?>" class="card-btn">
                    View Profile
                  </a>
                </div>
              </div>
            <?php endwhile; ?>
          </div>

        <?php else: ?>
          <div class="empty-state shadow-sm">
            <div class="empty-icon">
              <i class="fas fa-search"></i>
            </div>
            <h3 class="fw-bold">No Results Found</h3>
            <p class="text-muted mb-4">We couldn't find any labours matching "<strong><?= htmlspecialchars($query) ?></strong>".</p>
            <div class="d-flex justify-content-center gap-2">
              <a href="search.php" class="btn btn-outline-warning rounded-pill px-4">Clear Search</a>
              <a href="home.php" class="btn btn-warning text-white rounded-pill px-4">Browse Categories</a>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <footer>
    <div class="container">
      <p class="mb-0">&copy; <?= date('Y') ?> TrustedLabours. All Rights Reserved.</p>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>