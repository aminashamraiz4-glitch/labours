<?php
session_start();

// 1. SECURITY: Check CSRF Token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    // Redirect if token is missing or invalid
    header("Location: login.php?error=csrf_error");
    exit();
}

// Database connection
require_once "connection.php";

// Check if already logged in (Optional safety check)
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    header("Location: profile.php");
    exit();
}

// Handle login submission
if (isset($_POST['submit'])) {
    // 2. INPUT HANDLING: Sanitize email
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Basic validation
    if (empty($email) || empty($password)) {
        header("Location: login.php?error=invalid_fields");
        exit();
    }

    $user = null;
    $user_type = null;

    // 3. SQL INJECTION PREVENTION: Check in 'labours' table first
    // Using prepared statements ($stmt->bind_param)
    $stmt = $conn->prepare("SELECT * FROM labours WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_type = 'labour';
    } else {
        // If not found in labours, check in 'customers' table
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_type = 'customer';
        }
    }

    // 4. PASSWORD VERIFICATION & SESSION CREATION
    if ($user) {
        if (password_verify($password, $user['password'])) {
            // Successful login

            // Set unified session variables
            // We handle different ID column names (labour_id vs customer_id)
            $_SESSION['user_id'] = ($user_type == 'labour') ? $user['labour_id'] : $user['customer_id'];
            $_SESSION['user_type'] = $user_type;
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            // 5. SECURITY: Prevent Session Fixation
            session_regenerate_id(true);

            // Popup trigger session
            $_SESSION['login_success'] = true;

            // Redirect to profile page
            header("Location: profile.php");
            exit();
        } else {
            // Password mismatch
            header("Location: login.php?error=invalid_password");
            exit();
        }
    } else {
        // User not found in either table
        header("Location: login.php?error=user_not_found");
        exit();
    }
}

$conn->close();
