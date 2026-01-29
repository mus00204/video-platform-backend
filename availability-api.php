<?php
// availability-api.php - EXACT WORKING VERSION
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database - USE YOUR EXACT CREDENTIALS
$host = 'localhost';
$user = 'root';
$password = 'mustafa08';
$database = 'pazzle_store';

// Connect
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]));
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    
    if ($_GET['action'] === 'get_all') {
        // Ensure availability column exists
        $check = $conn->query("SHOW COLUMNS FROM products LIKE 'availability'");
        if ($check->num_rows === 0) {
            $conn->query("ALTER TABLE products ADD COLUMN availability VARCHAR(20) DEFAULT 'available'");
        }
        
        // Get all videos with availability
        $result = $conn->query("SELECT id, availability FROM products");
        $availabilities = [];
        
        while ($row = $result->fetch_assoc()) {
            $availabilities[$row['id']] = $row['availability'] ?? 'available';
        }
        
        echo json_encode([
            'success' => true,
            'availabilities' => $availabilities,
            'count' => count($availabilities)
        ]);
        
    } elseif ($_GET['action'] === 'check') {
        echo json_encode(['success' => true, 'message' => 'API is working']);
    }
    
    $conn->close();
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'update') {
        $video_id = $_POST['video_id'] ?? '';
        $availability = $_POST['availability'] ?? 'available';
        
        if (empty($video_id)) {
            echo json_encode(['success' => false, 'error' => 'No video ID']);
            exit;
        }
        
        // Ensure column exists
        $check = $conn->query("SHOW COLUMNS FROM products LIKE 'availability'");
        if ($check->num_rows === 0) {
            $conn->query("ALTER TABLE products ADD COLUMN availability VARCHAR(20) DEFAULT 'available'");
        }
        
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("UPDATE products SET availability = ? WHERE id = ?");
        $stmt->bind_param("ss", $availability, $video_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Updated successfully',
                'video_id' => $video_id,
                'availability' => $availability,
                'affected_rows' => $stmt->affected_rows
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        
        $stmt->close();
    }
    
    $conn->close();
    exit;
}

// Default response
echo json_encode(['success' => false, 'error' => 'Invalid request']);