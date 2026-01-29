<?php
// comments.php - FIXED VERSION
error_reporting(0); // Turn off error reporting for production
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$commentsFile = dirname(__FILE__) . '/comments.json'; // Use absolute path

// Function to read comments
function readComments() {
    global $commentsFile;
    
    // Create file if it doesn't exist
    if (!file_exists($commentsFile)) {
        $initialData = ['comments' => []];
        file_put_contents($commentsFile, json_encode($initialData, JSON_PRETTY_PRINT));
        return $initialData;
    }
    
    $content = @file_get_contents($commentsFile); // Suppress warnings
    if ($content === false || empty($content)) {
        return ['comments' => []];
    }
    
    $data = json_decode($content, true);
    if ($data === null || !isset($data['comments'])) {
        return ['comments' => []];
    }
    
    return $data;
}

// Function to save comments
function saveComments($data) {
    global $commentsFile;
    
    if (!isset($data['comments'])) {
        $data['comments'] = [];
    }
    
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return @file_put_contents($commentsFile, $json) !== false; // Suppress warnings
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $videoId = $_GET['video'] ?? '';
    
    $data = readComments();
    
    if (empty($videoId)) {
        echo json_encode([]);
        exit;
    }
    
// Filter comments for this video
$videoComments = [];
foreach ($data['comments'] as $comment) {
    if (isset($comment['videoId']) && $comment['videoId'] === $videoId) {
        $videoComments[] = $comment;
    }
}

// Sort by timestamp (newest first)
usort($videoComments, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});
    
    echo json_encode($videoComments);
    exit;
}

// Handle POST request (add comment)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        exit;
    }
    
    $required = ['videoId', 'text', 'userId', 'userName', 'userAvatar'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'error' => "Missing $field"]);
            exit;
        }
    }
    
    $data = readComments();
    
    $newComment = [
        'id' => 'c_' . time() . '_' . uniqid(),
        'videoId' => $input['videoId'],
        'userId' => $input['userId'],
        'userName' => $input['userName'],
        'userAvatar' => $input['userAvatar'],
        'text' => $input['text'],
        'parentId' => $input['parentId'] ?? null,
        'likes' => 0,
        'likedBy' => [],
        'timestamp' => date('c')
    ];
    
    $data['comments'][] = $newComment;
    
    if (saveComments($data)) {
        echo json_encode(['success' => true, 'comment' => $newComment]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Save failed']);
    }
    exit;
}

// Handle PUT request (like/unlike)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['commentId']) || !isset($input['userId'])) {
        echo json_encode(['success' => false, 'error' => 'Missing data']);
        exit;
    }
    
    $data = readComments();
    $updated = false;
    
    foreach ($data['comments'] as &$comment) {
        if ($comment['id'] === $input['commentId']) {
            if (!isset($comment['likedBy'])) {
                $comment['likedBy'] = [];
            }
            if (!isset($comment['likes'])) {
                $comment['likes'] = 0;
            }
            
            $key = array_search($input['userId'], $comment['likedBy']);
            
            if ($key !== false) {
                // Unlike
                array_splice($comment['likedBy'], $key, 1);
                $comment['likes'] = max(0, $comment['likes'] - 1);
            } else {
                // Like
                $comment['likedBy'][] = $input['userId'];
                $comment['likes']++;
            }
            
            $updated = true;
            break;
        }
    }
    
    if ($updated && saveComments($data)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
    exit;
}

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['commentId']) || !isset($input['userId'])) {
        echo json_encode(['success' => false, 'error' => 'Missing data']);
        exit;
    }
    
    $data = readComments();
    $newComments = [];
    $deleted = false;
    
    foreach ($data['comments'] as $comment) {
        if ($comment['id'] === $input['commentId']) {
            if ($comment['userId'] === $input['userId']) {
                $deleted = true;
                continue;
            } else {
                echo json_encode(['success' => false, 'error' => 'Not authorized']);
                exit;
            }
        }
        $newComments[] = $comment;
    }
    
    if ($deleted) {
        $data['comments'] = $newComments;
        if (saveComments($data)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Save failed']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Comment not found']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
?>