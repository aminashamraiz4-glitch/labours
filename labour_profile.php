<?php
 $servername = "localhost";
 $username = "root";
 $password = "";
 $dbname = "labour_booking";

 $conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get labour ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request.");
}

 $labour_id = intval($_GET['id']);

// Fetch labour details
 $stmt = $conn->prepare("SELECT * FROM labours WHERE labour_id = ?");
 $stmt->bind_param("i", $labour_id);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Labour not found.");
}

 $labour = $result->fetch_assoc();

// Fetch Average Rating 
 $avg_sql = "SELECT AVG(r.rating) as avg_rating 
            FROM reviews r 
            JOIN bookings b ON r.booking_id = b.booking_id 
            WHERE b.labour_id = ?";
 $avg_stmt = $conn->prepare($avg_sql);
 $avg_stmt->bind_param("i", $labour_id);
 $avg_stmt->execute();
 $avg_result = $avg_stmt->get_result();
 $avg_row = $avg_result->fetch_assoc();
 $average_rating = $avg_row['avg_rating']; // Returns NULL if no reviews

// Fetch reviews (through bookings)
 $review_sql = "
    SELECT r.rating, r.comment, r.created_at, c.name AS customer_name
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.booking_id
    JOIN customers c ON b.customer_id = c.customer_id
    WHERE b.labour_id = ?
    ORDER BY r.created_at DESC
";

 $review_stmt = $conn->prepare($review_sql);
 $review_stmt->bind_param("i", $labour_id);
 $review_stmt->execute();
 $reviews = $review_stmt->get_result();

// --- Helper Function for Better Star Display ---
function getStarRating($rating) {
    $html = '<div class="stars-wrapper">';
    if ($rating) {
        $fullStars = floor($rating);
        $hasHalfStar = ($rating - $fullStars) >= 0.5;
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $fullStars) {
                $html .= '<i class="bi bi-star-fill"></i>';
            } elseif ($i == $fullStars + 1 && $hasHalfStar) {
                $html .= '<i class="bi bi-star-half"></i>';
            } else {
                $html .= '<i class="bi bi-star"></i>';
            }
        }
    } else {
        $html .= '<span style="font-size:0.9rem; color:#888;">No rating yet</span>';
    }
    $html .= '</div>';
    return $html;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($labour['name']) ?> - Profile</title>
    <link rel="icon" href="assets/images/favicon.png">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --mustard: #FFC107;
            --mustard-dark: #e0a800;
            --silver: #C0C0C0;
            --text-dark: #333;
            --white: #fff;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #f5f5f5, #dcdcdc);
            margin: 0;
            padding: 0;
            animation: fadeInBody 1s ease forwards;
        }

        @keyframes fadeInBody {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .profile-container {
            max-width: 900px;
            margin: 50px auto;
            background: var(--white);
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            padding: 40px;
            transform: translateY(20px);
            opacity: 0;
            animation: fadeUp 0.9s ease forwards;
        }

        @keyframes fadeUp {
            to { transform: translateY(0); opacity: 1; }
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 25px;
            border-bottom: 2px solid var(--silver);
            padding-bottom: 20px;
            transition: all 0.3s ease;
        }

        .profile-header:hover {
            transform: scale(1.005); /* Subtle scale */
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.1);
        }

        .profile-header img {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--mustard);
            box-shadow: 0 0 15px rgba(255, 193, 7, 0.4);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }

        .profile-header img:hover {
            transform: scale(1.08) rotate(3deg);
            box-shadow: 0 0 25px rgba(255, 193, 7, 0.6);
        }

        .profile-header h2 {
            margin: 0 0 5px 0;
            color: var(--text-dark);
            font-size: 1.9rem;
            letter-spacing: 0.5px;
            animation: fadeSlide 0.8s ease forwards;
        }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* --- Improved Rating Styles --- */
        .avg-rating-container {
            margin-bottom: 15px;
        }
        
        .stars-wrapper i {
            color: var(--mustard);
            font-size: 1.4rem; /* Bigger stars */
            margin-right: 2px;
        }

        .rating-number {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-left: 8px;
        }

        .profile-header p {
            margin: 6px 0;
            color: #555;
            transition: color 0.3s ease;
            font-size: 1rem;
        }

        .profile-header p strong {
            color: #333;
            margin-right: 5px;
        }

        .bookbtn {
            display: inline-block;
            background: var(--mustard);
            color: var(--text-dark);
            padding: 10px 22px;
            text-decoration: none;
            border-radius: 10px;
            margin-top: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(255, 193, 7, 0.3);
        }

        .bookbtn:hover {
            background: var(--mustard-dark);
            color: var(--white);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 193, 7, 0.5);
        }

        /* ---------- REVIEWS ---------- */
        .reviews-section {
            margin-top: 40px;
            animation: fadeUp 1s ease 0.3s forwards;
        }

        .reviews-section h3 {
            color: var(--text-dark);
            border-left: 5px solid var(--mustard);
            padding-left: 10px;
            margin-bottom: 15px;
            transition: color 0.3s ease;
        }

        .reviews-section h3:hover {
            color: var(--mustard);
        }

        .review {
            background: #f7f7f7;
            border-left: 4px solid var(--mustard);
            border-radius: 10px;
            padding: 18px 20px;
            margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transform: translateY(10px);
            opacity: 0;
            animation: fadeReview 0.6s ease forwards;
        }

        @keyframes fadeReview {
            to { opacity: 1; transform: translateY(0); }
        }

        .review:nth-child(1) { animation-delay: 0.2s; }
        .review:nth-child(2) { animation-delay: 0.4s; }
        .review:nth-child(3) { animation-delay: 0.6s; }

        .review p { margin: 5px 0; color: #444; }
        small { color: #777; }

        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .review-btn i { margin-right: 5px; }
    </style>
</head>

<body>

    <div class="profile-container">
        <div class="profile-header">
            <!-- Added onerror just in case image is missing -->
            <img src="assets/images/profilePictures/<?= htmlspecialchars($labour['image']) ?>" 
                 alt="Profile Image" 
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($labour['name']) ?>&background=random'">
            
            <div>
                <h2><?= htmlspecialchars($labour['name']) ?></h2>
                
                <!-- Improved Average Rating Display -->
                <?php if ($average_rating && $average_rating > 0): ?>
                    <div class="avg-rating-container">
                        <?= getStarRating($average_rating) ?>
                        <span class="rating-number"><?= number_format($average_rating, 1) ?></span>
                    </div>
                <?php else: ?>
                    <div class="avg-rating-container">
                        <span style="color: #999; font-style: italic;">No ratings yet</span>
                    </div>
                <?php endif; ?>

                <!-- REMOVED: Availability, Status, Phone as requested -->
                
                <p><strong>Skill:</strong> <?= htmlspecialchars($labour['skill']) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($labour['location']) ?></p>
                
                <a href="booking_form.php?labour_id=<?= $labour_id ?>" class="bookbtn">Book Now</a>
            </div>
        </div>

        <div class="reviews-header d-flex justify-content-between align-items-center mb-3">
            <h3 class="m-0">Ratings & Reviews</h3>
            <a href="add_review.php?labour_id=<?= $labour_id ?>" class="btn btn-warning btn-sm review-btn">
                <i class="bi bi-pencil-square"></i> Add Your Review
            </a>
        </div>




        <?php if ($reviews->num_rows > 0): ?>

            <?php while ($review = $reviews->fetch_assoc()): ?>
                <div class="review">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                        <p style="margin:0; font-weight:700;"><?= htmlspecialchars($review['customer_name']) ?></p>
                        <!-- Little stars for individual review -->
                        <span class="text-warning" style="font-size:0.9rem;">
                            <?php 
                            for($i=1; $i<=5; $i++) {
                                if($i <= $review['rating']) echo '<i class="bi bi-star-fill"></i>';
                                else echo '<i class="bi bi-star"></i>';
                            }
                            ?>
                        </span>
                    </div>
                    
                    <p><?= htmlspecialchars($review['comment']) ?></p>
                    <small>Posted on <?= htmlspecialchars(date("d M Y", strtotime($review['created_at']))) ?></small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="color:#666;">No reviews yet.</p>
        <?php endif; ?>
    </div>
    </div>

</body>
</html>