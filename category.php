<?php
require_once "connection.php"; 

 $skill = isset($_GET['skill']) ? $_GET['skill'] : '';

if (empty($skill)) {
    die("No category selected.");
}

 $stmt = $conn->prepare("SELECT * FROM labours WHERE skill = ?");
 $stmt->bind_param("s", $skill);
 $stmt->execute();
 $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($skill) ?>s - TrustedLabours</title>
    <link rel="icon" href="assets/images/favicon.png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

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
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.12);
            --radius: 14px;
        }

        * {
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }

        body {
            background-color: var(--silver-bg);
            color: var(--text-main);
            min-height: 100vh;
            padding-bottom: 40px;
        }

        .container {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- Top Action Bar --- */
        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            margin-bottom: 30px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1.5px solid var(--silver-light);
            background: var(--white);
            color: var(--black);
            box-shadow: var(--shadow-sm);
        }

        .action-btn:hover {
            border-color: var(--silver);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: var(--black);
        }

        /* Header Section */
        .category-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .category-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--black);
            text-transform: uppercase;
            letter-spacing: -0.5px;
            margin-bottom: 5px;
        }

        .category-header h1 span {
            color: var(--mustard-dark);
        }

        .category-header p {
            color: var(--silver);
            font-weight: 500;
            font-size: 1rem;
            margin: 0;
        }

        /* Labour Card */
        .labour-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px 25px;
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 20px;
            border: 1px solid var(--silver-light);
            border-left: 5px solid transparent;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s cubic-bezier(.4,0,.2,1);
            text-decoration: none;
            color: var(--text-main);
            margin-bottom: 20px;
        }

        .labour-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-left-color: var(--mustard);
            background: var(--mustard-light);
        }

        /* Labour Image */
        .lc-img-wrapper {
            flex-shrink: 0;
        }

        .lc-img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--mustard);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            background-color: var(--white);
        }

        .labour-card:hover .lc-img {
            transform: scale(1.05);
        }

        /* Labour Info */
        .lc-info {
            flex-grow: 1;
        }

        .lc-info h5 {
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--black);
            margin: 0 0 6px 0;
            transition: color 0.3s ease;
        }

        .labour-card:hover .lc-info h5 {
            color: var(--mustard-dark);
        }

        .skill-badge {
            display: inline-block;
            background: var(--black);
            color: var(--mustard);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .lc-location {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .lc-location i {
            color: var(--silver);
            font-size: 0.85rem;
        }

        /* Arrow Icon */
        .lc-arrow {
            font-size: 1.2rem;
            color: var(--silver-light);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .labour-card:hover .lc-arrow {
            color: var(--mustard);
            transform: translateX(5px);
        }

        /* Empty State Styling */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: var(--radius);
            border: 1px solid var(--silver-light);
            box-shadow: var(--shadow-sm);
            margin-top: 20px;
        }

        .empty-state i {
            font-size: 3.5rem;
            color: var(--silver-light);
            margin-bottom: 20px;
            display: block;
        }

        .empty-state h5 {
            color: var(--black);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--silver);
            margin-bottom: 25px;
        }

        .btn-mustard {
            background: var(--mustard);
            color: var(--black);
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 10px 24px;
            text-decoration: none;
            transition: background 0.2s ease;
        }

        .btn-mustard:hover {
            background: var(--mustard-dark);
            color: var(--black);
        }

        /* --- Mobile Responsiveness --- */
        @media (max-width: 768px) {
            .category-header h1 { font-size: 2rem; }
            .top-actions { margin-top: 25px; margin-bottom: 20px; }
            .action-btn { padding: 8px 16px; font-size: 0.85rem; }
        }

        @media (max-width: 576px) {
            /* Stack elements vertically on very small screens */
            .labour-card {
                flex-direction: column;
                text-align: center;
                padding: 25px 20px;
                gap: 15px;
            }

            /* Shift left border hover to bottom border on mobile */
            .labour-card {
                border-left: 5px solid transparent;
                border-bottom: 5px solid transparent;
            }

            .labour-card:hover {
                border-left-color: transparent;
                border-bottom-color: var(--mustard);
            }

            .lc-img {
                width: 100px;
                height: 100px;
            }

            /* Center the map pin icon on mobile */
            .lc-location {
                justify-content: center;
            }

            .lc-arrow {
                display: none; /* Hide arrow on mobile since it's stacked */
            }
        }
    </style>
</head>

<body>

    <div class="container" style="max-width: 850px;">
        
        <!-- Top Navigation Bar (Symmetrical) -->
        <div class="top-actions">
            <a href="allcategories.php" class="action-btn">
                <i class="bi bi-grid-fill"></i> Categories
            </a>
            <a href="home.php" class="action-btn">
                <i class="bi bi-house"></i> Home
            </a>
        </div>

        <!-- Page Title -->
        <div class="category-header">
            <h1><?= htmlspecialchars($skill) ?>s <span>Available</span></h1>
            <p>Choose from trusted and verified professionals</p>
        </div>

        <div class="row justify-content-center">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    
                    // Fallback image handling if labour doesn't have an image uploaded
                    if (!empty($row['image'])) {
                        $imgSrc = "assets/images/profilePictures/" . htmlspecialchars($row['image']);
                    } else {
                        // Generates a clean text avatar matching the theme
                        $imgSrc = "https://ui-avatars.com/api/?name=" . urlencode($row['name']) . "&background=121212&color=D4A017&size=150&bold=true";
                    }
                ?>
                    <div class="col-12">
                        <a href="labour_profile.php?id=<?= $row['labour_id'] ?>" class="labour-card text-decoration-none">
                            
                            <div class="lc-img-wrapper">
                                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="lc-img">
                            </div>
                            
                            <div class="lc-info">
                                <h5><?= htmlspecialchars($row['name']) ?></h5>
                                <span class="skill-badge"><?= htmlspecialchars($row['skill']) ?></span>
                                <p class="lc-location">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?= htmlspecialchars($row['location']) ?>
                                </p>
                            </div>

                            <div class="lc-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>

                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Modern Empty State Design -->
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <h5>No <?= htmlspecialchars($skill) ?>s Found</h5>
                        <p>We couldn't find any verified professionals for this category right now. Please check back later.</p>
                        <a href="allcategories.php" class="btn btn-mustard">
                            <i class="bi bi-grid-fill me-2"></i>Browse Categories
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>