<?php
// database/setup.php - Create MySQL tables
require_once 'config.php';

echo "<h2>Setting Up MySQL Database</h2>";

// Create videos table (matches your videos.json structure)
$sql = "CREATE TABLE IF NOT EXISTS videos (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    price VARCHAR(50) NOT NULL,
    views VARCHAR(50) NOT NULL,
    time_ago VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'online',
    description TEXT,
    video_src VARCHAR(500),
    author_img VARCHAR(500),
    cover_img VARCHAR(500),
    likes INT DEFAULT 0,
    created_at DATETIME,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (db_query($sql)) {
    echo "✅ Videos table created<br>";
} else {
    echo "❌ Failed to create videos table<br>";
}

// Migrate data from videos.json
$jsonFile = dirname(__DIR__) . '/videos.json';
if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $data = json_decode($jsonData, true);
    
    if ($data && isset($data['videos'])) {
        $migrated = 0;
        $errors = 0;
        
        foreach ($data['videos'] as $video) {
            $id = db_escape($video['id']);
            $title = db_escape($video['title']);
            $author = db_escape($video['author']);
            $price = db_escape($video['time']); // 'time' is actually price
            $views = db_escape($video['views']);
            $timeAgo = db_escape($video['timeAgo']);
            $status = db_escape($video['status']);
            $description = db_escape($video['description'] ?? '');
            $videoSrc = db_escape($video['videoSrc'] ?? '');
            $authorImg = db_escape($video['authorImg'] ?? '');
            $coverImg = db_escape($video['coverImg'] ?? $video['authorImg'] ?? '');
            $likes = intval($video['likes'] ?? 0);
            $createdAt = db_escape($video['createdAt'] ?? date('Y-m-d H:i:s'));
            $updatedAt = db_escape($video['updatedAt'] ?? date('Y-m-d H:i:s'));
            
            // Check if video already exists
            $exists = db_fetch_one("SELECT id FROM videos WHERE id = '$id'");
            
            if (!$exists) {
                $sql = "INSERT INTO products (id, title, author, price, views, time_ago, status, description, 
                        video_src, author_img, cover_img, likes, created_at) 
                        VALUES ('$id', '$title', '$author', '$price', '$views', '$timeAgo', '$status', 
                        '$description', '$videoSrc', '$authorImg', '$coverImg', $likes, '$createdAt', '$updatedAt')";
                
                if (db_query($sql)) {
                    $migrated++;
                } else {
                    $errors++;
                }
            }
        }
        
        echo "✅ Migrated $migrated videos from JSON to MySQL<br>";
        if ($errors > 0) echo "⚠️ $errors videos failed to migrate<br>";
    }
}

echo "<h3>✅ Setup Complete!</h3>";
echo "Total videos in database: " . db_count('videos') . "<br>";
echo "<a href='test_fixed.php'>Test Connection</a> | ";
echo "<a href='../admin.html'>Go to Admin Panel</a>";
?>