<?php
session_start();
require_once "connection.php";

// This file ONLY runs when the form is submitted (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $user_type = $_POST['user_type'] ?? '';
    $user_id   = $_SESSION['user_id'] ?? 0;

    // Determine table and ID field
    if ($user_type == 'labour') {
        $table = 'labours';
        $id_field = 'labour_id';
    } elseif ($user_type == 'customer') {
        $table = 'customers';
        $id_field = 'customer_id';
    } else {
        header("Location: login.php");
        exit();
    }

    $name = $_POST['name'];
    $email = $_POST['email'];
    $location = $_POST['location'];
    $password = $_POST['password'];
    
    // Initialize image_name
    $image_name = ''; 

    // --- IMAGE UPLOAD LOGIC (ONLY FOR LABOURS) ---
    if ($user_type == 'labour') {
        // Fetch current user to get existing image if no new one is uploaded
        $stmt = $conn->prepare("SELECT image FROM $table WHERE $id_field = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentUser = $result->fetch_assoc();
        $image_name = $currentUser['image'] ?? '';

        // If a new file is uploaded
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            // FIX: Updated path to profilePictures (plural)
            $target_dir = "assets/images/profilePictures/"; 
            
            // Create directory if it does not exist
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_name = time() . "_" . basename($_FILES["profile_pic"]["name"]);
            $target_file = $target_dir . $file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            $allowed_types = ["jpg", "png", "jpeg", "webp"];
            $uploadOk = 1;

            // Check file size (5MB limit)
            if ($_FILES["profile_pic"]["size"] > 5000000) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'File is too large (Max 5MB).'];
                header("Location: edit.php");
                exit();
            }

            // Check format
            if (!in_array($imageFileType, $allowed_types)) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Invalid file format. Only JPG, JPEG, PNG & WEBP allowed.'];
                header("Location: edit.php");
                exit();
            }

            // Attempt to upload
            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                $image_name = $file_name;
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Error uploading file. Check folder permissions.'];
                header("Location: edit.php");
                exit();
            }
        }
    }

    // --- DATABASE UPDATE QUERY ---
    
    // Start with common fields
    $sql = "UPDATE $table SET name=?, email=?, location=?";
    $params = [$name, $email, $location];
    $types = "sss";

    // Add Labour specific fields (Image, Phone, Skill)
    if ($user_type == 'labour') {
        $sql .= ", image=?, phone=?, skill=?";
        $params[] = $image_name;
        $params[] = $_POST['phone'];
        $params[] = $_POST['skill'];
        $types .= "sss"; 
    }

    // Add Password if provided
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password=?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    // Add Where Clause
    $sql .= " WHERE $id_field=?";
    $params[] = $user_id;
    $types .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Profile updated successfully!'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Database error: ' . $conn->error];
    }

    header("Location: edit.php");
    exit();
}
?>