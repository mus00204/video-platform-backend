<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = 'mustafa08';
$dbname = 'pazzle_store';

// Connect to database
$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');

// Helper function to escape input
function escape($value) {
    global $conn;
    return mysqli_real_escape_string($conn, $value);
}

// Helper function to clean views
function cleanViews($views) {
    if (is_numeric($views)) {
        return intval($views);
    }
    $cleaned = preg_replace('/[^0-9]/', '', $views);
    return intval($cleaned) ?: 0;
}

// Route 1: Admin - Get all videos
if (isset($_GET['action']) && $_GET['action'] === 'get_videos_admin') {
    // FIXED LINE: Added price and time_ago to SELECT
    $sql = "SELECT id, title, description, thumbnail, views, likes, status, price, time_ago FROM products ORDER BY id DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database query failed']);
        exit;
    }
    
    $videos = [];
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['views'] = cleanViews($row['views']);
            $row['likes'] = intval($row['likes']);
            $videos[] = $row;
        }
    }
    
    echo json_encode($videos);
    exit;
}

// Route 2: Admin - Add/Update video
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_video_admin') {
    // Get form data
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $views = $_POST['views'] ?? 0;
    $likes = $_POST['likes'] ?? 0;
    $price = $_POST['price'] ?? '';
    $time_ago = $_POST['time_ago'] ?? '';
    $status = $_POST['status'] ?? 'online';
    
    // Check if editing
    $isEditing = isset($_POST['id']) && !empty($_POST['id']);
    $id = $isEditing ? escape($_POST['id']) : 'vid_' . uniqid();
    
    // Clean views
    $views = cleanViews($views);
    
    // Escape data
    $title = escape($title);
    $description = escape($description);
    $price = escape($price);
    $time_ago = escape($time_ago);
    $status = escape($status);
    $views = intval($views);
    $likes = intval($likes);
    
    // Handle file upload
    $thumbnail = '';
    if (!empty($_FILES['authorImageFile']['name'])) {
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        
        $imageId = 'img_' . uniqid();
        $imageExt = strtolower(pathinfo($_FILES['authorImageFile']['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($imageExt, $allowedExt)) {
            $imageFilename = $imageId . '.' . $imageExt;
            $targetPath = $uploadDir . $imageFilename;
            
            if (move_uploaded_file($_FILES['authorImageFile']['tmp_name'], $targetPath)) {
                $thumbnail = 'api.php?file=' . $imageFilename;
            }
        }
    }
    
    $thumbnail = escape($thumbnail);
    
    if ($isEditing) {
        // Update existing video
        if ($thumbnail) {
            // Update with new thumbnail
            $sql = "UPDATE products SET 
                    title = '$title',
                    description = '$description',
                    views = $views,
                    likes = $likes,
                    price = '$price',
                    time_ago = '$time_ago',
                    status = '$status',
                    thumbnail = '$thumbnail'
                    WHERE id = '$id'";
        } else {
            // Update without changing thumbnail
            $sql = "UPDATE products SET 
                    title = '$title',
                    description = '$description',
                    views = $views,
                    likes = $likes,
                    price = '$price',
                    time_ago = '$time_ago',
                    status = '$status'
                    WHERE id = '$id'";
        }
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode([
                'success' => true,
                'message' => 'Video updated successfully',
                'video_id' => $id,
                'status_saved' => $status
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Update failed: ' . mysqli_error($conn)]);
        }
    } else {
        // Insert new video
        $sql = "INSERT INTO products (id, title, description, thumbnail, views, likes, price, time_ago, status) 
                VALUES ('$id', '$title', '$description', '$thumbnail', $views, $likes, '$price', '$time_ago', '$status')";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode([
                'success' => true,
                'message' => 'Video added successfully',
                'video_id' => $id,
                'status_saved' => $status
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Insert failed: ' . mysqli_error($conn)]);
        }
    }
    exit;
}

// Route 3: Admin - Delete video
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_video') {
    $videoId = escape($_POST['video_id'] ?? '');
    
    if (empty($videoId)) {
        echo json_encode(['success' => false, 'error' => 'Video ID required']);
        exit;
    }
    
    $sql = "DELETE FROM products WHERE id = '$videoId'";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Video deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete: ' . mysqli_error($conn)]);
    }
    exit;
}

// Route 4: Admin - Update video status (for button)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_video_status') {
    $videoId = escape($_POST['video_id'] ?? '');
    $status = $_POST['status'] ?? 'online';
    
    if (empty($videoId)) {
        echo json_encode(['success' => false, 'error' => 'Video ID required']);
        exit;
    }
    
    // Update status column only
    $sql = "UPDATE products SET status = '$status' WHERE id = '$videoId'";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode([
            'success' => true,
            'message' => 'Video status updated to ' . $status,
            'video_id' => $videoId,
            'status' => $status
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update status: ' . mysqli_error($conn)]);
    }
    exit;
}

// Route 5: Serve uploaded files
if (isset($_GET['file']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $filename = basename($_GET['file']);
    $filepath = 'uploads/' . $filename;
    
    if (!file_exists($filepath)) {
        http_response_code(404);
        die('File not found');
    }
    
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $contentTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ];
    
    if (isset($contentTypes[$extension])) {
        header('Content-Type: ' . $contentTypes[$extension]);
    }
    
    readfile($filepath);
    exit;
}

// Route 6: Main site (index.html) - Get videos
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['action'])) {
    $sql = "SELECT id, title, description, thumbnail, views, likes, price, time_ago, status 
            FROM products 
            WHERE status = 'online'
            ORDER BY id DESC 
            LIMIT 50";
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database query failed']);
        exit;
    }
    
    $videos = [];
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Extract author from description
            $description = $row['description'] ?? '';
            $author = 'Unknown';
            $pureDescription = $description;
            
            if (strpos($description, ' - ') !== false) {
                $parts = explode(' - ', $description, 2);
                $author = trim($parts[0]);
                $pureDescription = trim($parts[1] ?? '');
            }
            
            $videos[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'author' => $author,
                'price' => $row['price'] ? $row['price'] . ' SAR' : '0 SAR',
                'views' => cleanViews($row['views']) . ' views',
                'timeAgo' => $row['time_ago'] ?: 'Recently',
                'status' => $row['status'] ?: 'online',
                'description' => $pureDescription,
                'videoSrc' => '',
                'authorImg' => $row['thumbnail'] ?: '',
                'coverImg' => $row['thumbnail'] ?: '',
                'likes' => intval($row['likes']),
                'createdAt' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    echo json_encode($videos);
    exit;
}
// Route: Update video likes in database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_video_likes') {
    $videoId = escape($_POST['video_id'] ?? '');
    $likes = intval($_POST['likes'] ?? 0);
    
    if (empty($videoId)) {
        echo json_encode(['success' => false, 'error' => 'Video ID required']);
        exit;
    }
    
    // Update likes in database
    $sql = "UPDATE products SET likes = $likes WHERE id = '$videoId'";
    
    if (mysqli_query($conn, $sql)) {
        echo json_encode([
            'success' => true,
            'likes' => $likes,
            'message' => 'Likes updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update: ' . mysqli_error($conn)]);
    }
    exit;
}
// Default route - return empty array if no matching route
echo json_encode([]);

// Close connection
mysqli_close($conn);