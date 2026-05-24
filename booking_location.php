<?php
session_start();
require_once "connection.php";

// --- 1. HANDLE SAVING LOCATION (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_location') {
    
    $booking_id = intval($_POST['booking_id']);
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    
    // Security: Verify this booking belongs to the logged-in customer
    $check = $conn->prepare("SELECT customer_id FROM bookings WHERE booking_id = ?");
    $check->bind_param("i", $booking_id);
    $check->execute();
    $res = $check->get_result();
    
    if ($row = $res->fetch_assoc()) {
        if ($row['customer_id'] == $_SESSION['user_id']) {
            // Save to Database
            $update = $conn->prepare("UPDATE bookings SET customer_latitude = ?, customer_longitude = ? WHERE booking_id = ?");
            $update->bind_param("ssi", $lat, $lng, $booking_id);
            
            if ($update->execute()) {
                // Redirect back to status page
                echo "<script>window.location.href='booking_pending.php?id=$booking_id';</script>";
                exit;
            }
        }
    }
}

// --- 2. AUTH CHECK & DATA FETCH (GET) ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Booking ID.");
}

 $booking_id = intval($_GET['id']);

// Verify ownership again
 $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ? AND customer_id = ?");
 $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
 $stmt->execute();
 $booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    die("Booking not found or access denied.");
}

// If location already exists, just send them back
if (!empty($booking['customer_latitude'])) {
    echo "<script>window.location.href='booking_pending.php?id=$booking_id';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Location</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .map-card { background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 600px; padding: 30px; text-align: center; }
        #map { height: 400px; width: 100%; border-radius: 12px; margin-bottom: 20px; z-index: 1; border: 2px solid #e9ecef; cursor: crosshair; }
        .location-btn { width: 100%; padding: 12px; border-radius: 10px; font-weight: 600; margin-bottom: 10px; }
        .coords-display { font-family: monospace; background: #f1f3f5; padding: 5px 10px; border-radius: 5px; font-size: 0.9rem; display: inline-block; margin-top: 5px; color: #495057; }
    </style>
</head>
<body>

    <div class="map-card">
        <h3 class="fw-bold mb-1">Set Your Location</h3>
        <p class="text-muted small mb-3">
            Booking for: <strong><?= date('d M, g:i A', strtotime($booking['start_time'])) ?></strong><br>
            Move the pin to your exact location.
        </p>

        <!-- The Map -->
        <div id="map"></div>

        <!-- Coordinates Display -->
        <div id="coordsDisplay" class="coords-display">Waiting for location...</div>

        <!-- Action Buttons -->
        <button id="btnGetLocation" class="btn btn-outline-primary location-btn mt-2">
            <i class="bi bi-crosshair"></i> Detect My Location (GPS)
        </button>
        
        <button id="btnConfirm" class="btn btn-success location-btn mt-2" disabled>
            <i class="bi bi-check-lg"></i> Confirm & Save Location
        </button>
        
        <div id="locError" class="text-danger small mt-2"></div>

        <!-- Hidden Form to submit data -->
        <form id="locForm" method="POST" style="display:none;">
            <input type="hidden" name="action" value="save_location">
            <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
            <input type="hidden" name="lat" id="inputLat">
            <input type="hidden" name="lng" id="inputLng">
        </form>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        let map, marker;
        let userLat, userLng;

        // 1. Initialize Map (Default view - Jhelum roughly)
        map = L.map('map').setView([33.9766, 73.7184], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Function to update coordinates display and hidden inputs
        function updatePosition(lat, lng) {
            userLat = lat;
            userLng = lng;
            
            // Update UI text
            document.getElementById('coordsDisplay').innerText = `Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)}`;
            
            // Enable Save button
            document.getElementById('btnConfirm').disabled = false;
            
            // Update hidden inputs
            document.getElementById('inputLat').value = lat;
            document.getElementById('inputLng').value = lng;
        }

        // 2. Function to place or move the marker
        function setMarker(lat, lng) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                // Create a DRAGGABLE marker
                marker = L.marker([lat, lng], {draggable: true}).addTo(map);
                
                // Add Drag Event Listener
                marker.on('dragend', function(event) {
                    var position = marker.getLatLng();
                    updatePosition(position.lat, position.lng);
                    map.panTo(position); // Center map on new position
                });
            }
            map.setView([lat, lng], 16); // Zoom in
            updatePosition(lat, lng);
        }

        // 3. Allow clicking on the map to set location
        map.on('click', function(e) {
            setMarker(e.latlng.lat, e.latlng.lng);
        });

        // 4. Handle "Detect My Location" Button
        document.getElementById('btnGetLocation').addEventListener('click', function() {
            if (navigator.geolocation) {
                const btn = this;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Locating...';
                btn.disabled = true;
                document.getElementById('locError').innerText = '';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        // Get GPS coords
                        const gpsLat = position.coords.latitude;
                        const gpsLng = position.coords.longitude;
                        
                        setMarker(gpsLat, gpsLng);
                        
                        btn.innerHTML = '<i class="bi bi-check-lg"></i> Location Found';
                        document.getElementById('locError').innerText = "You can drag the pin to adjust.";
                        document.getElementById('locError').className = "text-success small mt-2";
                    },
                    (error) => {
                        let msg = "Unable to retrieve location.";
                        if(error.code == 1) msg = "Permission denied. Please allow location access.";
                        document.getElementById('locError').innerText = msg;
                        btn.innerHTML = '<i class="bi bi-crosshair"></i> Try Again';
                        btn.disabled = false;
                    }
                );
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        });

        // 5. Handle Confirm Button
        document.getElementById('btnConfirm').addEventListener('click', function() {
            if(userLat && userLng) {
                // Double check values exist in hidden inputs
                document.getElementById('inputLat').value = userLat;
                document.getElementById('inputLng').value = userLng;
                document.getElementById('locForm').submit();
            }
        });
    </script>
</body>
</html>