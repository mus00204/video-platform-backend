cat > admin.php << 'EOF'
<?php
// admin.php - Database Administration Panel
require_once 'database/config.php';

// Authentication
$admin_password = 'mustafa08'; // CHANGE THIS IN PRODUCTION!

if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['password']) && $_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
    } else {
        showLoginForm();
        exit;
    }
}

// Auto-logout after 1 hour
if (time() - $_SESSION['admin_login_time'] > 3600) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle actions
if (isset($_GET['action'])) {
    handleAdminAction($_GET['action'], $_GET['id'] ?? 0);
}

// Display admin panel
showAdminPanel();

/**
 * Show login form
 */
function showLoginForm() {
    $error = isset($_POST['password']) ? 'Invalid password!' : '';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                width: 100%;
                max-width: 400px;
            }
            h1 { 
                text-align: center;
                color: #333;
                margin-bottom: 30px;
                font-size: 24px;
            }
            .error {
                background: #fee;
                color: #c33;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 20px;
                text-align: center;
            }
            .form-group {
                margin-bottom: 20px;
            }
            label {
                display: block;
                margin-bottom: 5px;
                color: #666;
                font-weight: 500;
            }
            input[type="password"] {
                width: 100%;
                padding: 12px;
                border: 2px solid #ddd;
                border-radius: 5px;
                font-size: 16px;
                transition: border-color 0.3s;
            }
            input[type="password"]:focus {
                outline: none;
                border-color: #667eea;
            }
            button {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s;
            }
            button:hover {
                transform: translateY(-2px);
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                color: #666;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h1>🔐 Admin Login</h1>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">Admin Password</label>
                    <input type="password" id="password" name="password" required autofocus>
                </div>
                <button type="submit">Login</button>
            </form>
            <div class="footer">
                Database Administration Panel
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Handle admin actions
 */
function handleAdminAction($action, $id) {
    $id = intval($id);
    
    switch ($action) {
        case 'approve':
            db_query("UPDATE comments SET is_approved = TRUE WHERE id = $id");
            $_SESSION['message'] = 'Comment approved successfully';
            break;
            
        case 'unapprove':
            db_query("UPDATE comments SET is_approved = FALSE WHERE id = $id");
            $_SESSION['message'] = 'Comment unapproved';
            break;
            
        case 'delete':
            db_query("DELETE FROM comments WHERE id = $id");
            $_SESSION['message'] = 'Comment deleted successfully';
            break;
            
        case 'delete_video':
            db_query("DELETE FROM videos WHERE id = $id");
            $_SESSION['message'] = 'Video deleted successfully';
            break;
    }
    
    header('Location: admin.php');
    exit;
}

/**
 * Show admin panel
 */
function showAdminPanel() {
    // Get statistics
    $totalComments = db_count('comments');
    $approvedComments = db_count('comments', 'is_approved = TRUE');
    $pendingComments = db_count('comments', 'is_approved = FALSE');
    $totalVideos = db_count('videos');
    $totalViews = db_fetch_one("SELECT SUM(views) as total FROM videos")['total'] ?? 0;
    
    // Get recent comments
    $recentComments = db_fetch_all("SELECT * FROM comments ORDER BY created_at DESC LIMIT 20");
    
    // Get videos
    $videos = db_fetch_all("SELECT * FROM videos ORDER BY created_at DESC");
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Admin Panel</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f5f5f5;
                color: #333;
                line-height: 1.6;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px 0;
                margin-bottom: 30px;
                border-radius: 10px;
            }
            .header-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0 20px;
            }
            h1 {
                font-size: 24px;
                font-weight: 600;
            }
            .logout-btn {
                background: rgba(255,255,255,0.2);
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
            }
            .logout-btn:hover {
                background: rgba(255,255,255,0.3);
            }
            .message {
                background: #d4edda;
                color: #155724;
                padding: 12px;
                border-radius: 5px;
                margin-bottom: 20px;
                display: <?php echo isset($_SESSION['message']) ? 'block' : 'none'; ?>;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .stat-card {
                background: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .stat-card h3 {
                color: #666;
                font-size: 14px;
                text-transform: uppercase;
                margin-bottom: 10px;
            }
            .stat-value {
                font-size: 32px;
                font-weight: 700;
                color: #667eea;
            }
            .section {
                background: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                margin-bottom: 30px;
            }
            .section h2 {
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 2px solid #f0f0f0;
                color: #444;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th {
                background: #f8f9fa;
                padding: 12px;
                text-align: left;
                font-weight: 600;
                color: #555;
                border-bottom: 2px solid #dee2e6;
            }
            td {
                padding: 12px;
                border-bottom: 1px solid #eee;
            }
            tr:hover {
                background: #f8f9fa;
            }
            .actions {
                display: flex;
                gap: 10px;
            }
            .btn {
                padding: 6px 12px;
                border-radius: 4px;
                text-decoration: none;
                font-size: 12px;
                font-weight: 500;
                cursor: pointer;
                border: none;
            }
            .btn-approve {
                background: #28a745;
                color: white;
            }
            .btn-unapprove {
                background: #ffc107;
                color: #212529;
            }
            .btn-delete {
                background: #dc3545;
                color: white;
            }
            .btn:hover {
                opacity: 0.9;
            }
            .status-approved {
                color: #28a745;
                font-weight: 600;
            }
            .status-pending {
                color: #ffc107;
                font-weight: 600;
            }
            .video-thumb {
                width: 100px;
                height: 60px;
                object-fit: cover;
                border-radius: 4px;
            }
            .empty-state {
                text-align: center;
                padding: 40px;
                color: #666;
            }
            .empty-state i {
                font-size: 48px;
                margin-bottom: 10px;
                opacity: 0.5;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <header>
                <div class="header-content">
                    <h1>📊 Database Administration Panel</h1>
                    <a href="?logout=1" class="logout-btn">Logout</a>
                </div>
            </header>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="message" id="message">
                    <?php 
                    echo htmlspecialchars($_SESSION['message']); 
                    unset($_SESSION['message']);
                    ?>
                    <button onclick="document.getElementById('message').style.display='none'" 
                            style="float:right; background:none; border:none; color:#155724; cursor:pointer;">
                        ✕
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Comments</h3>
                    <div class="stat-value"><?php echo $totalComments; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Approved Comments</h3>
                    <div class="stat-value"><?php echo $approvedComments; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Pending Comments</h3>
                    <div class="stat-value"><?php echo $pendingComments; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Videos</h3>
                    <div class="stat-value"><?php echo $totalVideos; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Views</h3>
                    <div class="stat-value"><?php echo number_format($totalViews); ?></div>
                </div>
            </div>
            
            <div class="section">
                <h2>Recent Comments</h2>
                <?php if (empty($recentComments)): ?>
                    <div class="empty-state">
                        <div>📝</div>
                        <p>No comments yet</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Comment</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentComments as $comment): ?>
                            <tr>
                                <td><?php echo $comment['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($comment['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($comment['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(substr($comment['comment'], 0, 100)); ?>...</td>
                                <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                                <td>
                                    <?php if ($comment['is_approved']): ?>
                                        <span class="status-approved">Approved</span>
                                    <?php else: ?>
                                        <span class="status-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <?php if (!$comment['is_approved']): ?>
                                        <a href="?action=approve&id=<?php echo $comment['id']; ?>" 
                                           class="btn btn-approve">Approve</a>
                                    <?php else: ?>
                                        <a href="?action=unapprove&id=<?php echo $comment['id']; ?>" 
                                           class="btn btn-unapprove">Unapprove</a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?php echo $comment['id']; ?>" 
                                       class="btn btn-delete"
                                       onclick="return confirm('Delete this comment?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2>Videos</h2>
                <?php if (empty($videos)): ?>
                    <div class="empty-state">
                        <div>🎬</div>
                        <p>No videos yet</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Thumbnail</th>
                                <th>Title</th>
                                <th>Views</th>
                                <th>Likes</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($videos as $video): ?>
                            <tr>
                                <td><?php echo $video['id']; ?></td>
                                <td>
                                    <?php if ($video['thumbnail']): ?>
                                        <img src="<?php echo htmlspecialchars($video['thumbnail']); ?>" 
                                             alt="Thumbnail" class="video-thumb">
                                    <?php else: ?>
                                        <div class="video-thumb" style="background:#eee; display:flex; align-items:center; justify-content:center;">
                                            🎬
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($video['title']); ?></strong><br>
                                    <small><?php echo htmlspecialchars(substr($video['description'] ?? '', 0, 50)); ?>...</small>
                                </td>
                                <td><?php echo number_format($video['views']); ?></td>
                                <td><?php echo number_format($video['likes']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($video['created_at'])); ?></td>
                                <td class="actions">
                                    <a href="?action=delete_video&id=<?php echo $video['id']; ?>" 
                                       class="btn btn-delete"
                                       onclick="return confirm('Delete this video?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
            // Auto-hide message after 5 seconds
            setTimeout(function() {
                var message = document.getElementById('message');
                if (message) {
                    message.style.display = 'none';
                }
            }, 5000);
        </script>
    </body>
    </html>
    <?php
}
?>
EOF