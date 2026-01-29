cat > index.php << 'EOF'
<?php
// index.php - Test page
require_once 'database/config.php';

$conn = getDBConnection();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Test Page</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e8; border-radius: 5px; }
        .error { color: red; padding: 10px; background: #ffe8e8; border-radius: 5px; }
        .box { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
        ul { list-style: none; padding: 0; }
        li { padding: 5px 0; }
        .btn { display: inline-block; padding: 10px 15px; background: #0066cc; color: white; 
               border-radius: 5px; text-decoration: none; margin: 5px; }
        .btn:hover { background: #0055aa; }
    </style>
</head>
<body>
    <h1>Database Integration Test</h1>
    
    <div class="success">✅ Database connection successful!</div>
    
    <div class="box">
        <h2>Quick Links</h2>
        <p>
            <a class="btn" href="database/test_fixed.php">Test Connection</a>
            <a class="btn" href="database/setup.php">Setup Database</a>
            <a class="btn" href="admin.php">Admin Panel</a>
            <a class="btn" href="api.php?endpoint=stats">API Stats</a>
        </p>
    </div>
    
    <div class="box">
        <h2>Database Information</h2>
        <p><strong>Host:</strong> <?php echo DB_HOST; ?></p>
        <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
        <p><strong>User:</strong> <?php echo DB_USER; ?></p>
        <p><strong>Server Info:</strong> <?php echo $conn->server_info; ?></p>
    </div>
    
    <div class="box">
        <h2>API Endpoints</h2>
        <ul>
            <li><a href="api.php?endpoint=comments">GET /api.php?endpoint=comments</a> - Get comments</li>
            <li><a href="api.php?endpoint=videos">GET /api.php?endpoint=videos</a> - Get videos</li>
            <li><a href="api.php?endpoint=stats">GET /api.php?endpoint=stats</a> - Get statistics</li>
            <li><a href="comments.php?action=get">GET /comments.php?action=get</a> - Legacy comments</li>
        </ul>
    </div>
    
    <div class="box">
        <h2>Test Forms</h2>
        <h3>Add Test Comment</h3>
        <form action="comments.php" method="POST" target="_blank">
            <input type="hidden" name="action" value="add">
            <p>Name: <input type="text" name="name" value="Test User" required></p>
            <p>Email: <input type="email" name="email" value="test@example.com"></p>
            <p>Comment: <textarea name="comment" required>This is a test comment from the test page!</textarea></p>
            <p><button type="submit">Submit Comment</button></p>
        </form>
    </div>
    
    <div class="box">
        <h2>Current Database Content</h2>
        <?php
        // Show comment count
        $commentCount = db_count('comments');
        $videoCount = db_count('videos');
        $userCount = db_count('users');
        
        echo "<p>Comments: $commentCount</p>";
        echo "<p>Videos: $videoCount</p>";
        echo "<p>Users: $userCount</p>";
        
        // Show recent comments
        $recent = db_fetch_all("SELECT * FROM comments ORDER BY created_at DESC LIMIT 3");
        if ($recent) {
            echo "<h3>Recent Comments:</h3>";
            echo "<ul>";
            foreach ($recent as $comment) {
                echo "<li><strong>" . htmlspecialchars($comment['name']) . ":</strong> " . 
                     htmlspecialchars(substr($comment['comment'], 0, 50)) . "...</li>";
            }
            echo "</ul>";
        }
        ?>
    </div>
</body>
</html>
EOF