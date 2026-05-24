<?php
// Show errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once "connection.php"; 

// Check if form submitted
if (isset($_POST['submit'])) {
    // Collect data from form
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $location = $_POST['location'];
    $timestamp = date('Y-m-d H:i:s'); // current time automatically

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert query
    $sql = "INSERT INTO customers (name, email, password, location, created_at)
            VALUES ('$name', '$email', '$hashed_password', '$location', '$timestamp')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>
                alert('✅ Account created successfully!');
                window.location.href = 'home.php';
              </script>";
    } else {
        echo "<script>
                alert('❌ Error: " . $conn->error . "');
                window.history.back();
              </script>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

      <link rel="icon" href="assets/images/favicon.png">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/forms.css">
</head>

<body>



    <div class="container py-5">
        <div class="row justify-content-end">
            <div class="col-md-6 bg-opacity-75 p-4 ">
                <h2 class="text-center mb-4 heading text-dark">Create an account</h2>
                <p class="text-center text-dark mb-4">
                    Create your account to find skilled and trusted labourers for any type of work - safely and conveniently
                </p>

                <p class="text-center urdu-text text-dark mb-4">
                    اپنا اکاؤنٹ بنائیں تاکہ آپ کسی بھی کام کے لیے قابلِ اعتماد اور ماہر مزدور آسانی اور حفاظت کے ساتھ حاصل کر سکیں

                </p>
                <form method="post" action="customer-signup.php">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Full name</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('name')">🔊</button>
                            </div>
                            <span class="text-dark urdu-text small">اپنا نام درج کریں</span>
                        </div>
                        <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Email</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('email')">🔊</button>
                            </div>
                            <span class="text-dark urdu-text small">اپنا ای میل درج کریں</span>
                        </div>
                        <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Password</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('password')">🔊</button>
                            </div>
                            <span class="text-dark urdu-text small">اپنا پاس ورڈ درج کریں</span>
                        </div>
                        <input type="password" name="password" class="form-control" placeholder="Create a password" required>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="label-left">
                                <label class="form-label text-dark mb-0">Location</label>
                                <button type="button" class="speaker-btn" onclick="playBoth('location')">🔊</button>
                            </div>
                            <span class="text-dark urdu-text small">اپنی لوکیشن شیئر کریں</span>
                        </div>
                        <input type="text" name="location" class="form-control" placeholder="Enter your city or area" required>
                    </div>


                    <div class="d-grid mt-4">
                        <button type="submit" name="submit" class="btn btn-warning btn-lg">Signup</button>
                    </div>

                    <p class="text-center text-dark mt-3">
                        Already have an account? <a href="login.php" class="text-warning text-decoration-none">Login
                            here</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <script>
        function playBoth(field) {
            const audio1 = new Audio(`audio/${field}-eng.wav`);
            const audio2 = new Audio(`audio/${field}-urdu.wav`);
            audio1.onended = () => audio2.play();
            audio1.play();
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>