<?php
// get-avatars.php
header('Content-Type: application/json');

$avatarFolder = './avatars/';
$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$avatars = [];

if (is_dir($avatarFolder)) {
    $files = scandir($avatarFolder);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        if (in_array($extension, $imageExtensions)) {
            $avatars[] = $avatarFolder . $file;
        }
    }
}

echo json_encode($avatars);
?>