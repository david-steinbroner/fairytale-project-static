<?php
/**
 * Export Blog Posts to JSON WITH IMAGES
 * Run this script with MAMP PHP to export all blog posts with embedded images
 * 
 * Usage:
 * /Applications/MAMP/bin/php/php8.4.1/bin/php exportBlogToJson-with-images.php
 */

// Database connection settings for MAMP
$host = '127.0.0.1';
$port = '8889';
$database = 'fairytaleproject';
$username = 'root';
$password = 'root';

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Query to get all active blog posts
    $query = "
        SELECT 
            id,
            active,
            title,
            post,
            media,
            author,
            date_posted,
            tags
        FROM blog_posts
        WHERE active = 1
        ORDER BY date_posted DESC
    ";
    
    $stmt = $pdo->query($query);
    $posts = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Clean up tags - remove leading comma and split into array
        $tagsString = ltrim($row['tags'], ',');
        $tagsArray = array_filter(array_map('trim', explode(',', $tagsString)));
        
        // Format date nicely
        $dateObj = new DateTime($row['date_posted']);
        $formattedDate = $dateObj->format('F j, Y');
        
        // Get media/image if exists
        $postContent = $row['post'];
        $mediaIds = array_filter(array_map('trim', explode(',', trim($row['media'], ','))));
        
        // If there are media IDs, fetch the image info and insert into post
        if (!empty($mediaIds)) {
            foreach ($mediaIds as $mediaId) {
                if (empty($mediaId)) continue;
                
                // Get media info from directus_media table - INCLUDING extension and caption
                $mediaQuery = "SELECT file_name, extension, title, caption FROM directus_media WHERE id = ?";
                $mediaStmt = $pdo->prepare($mediaQuery);
                $mediaStmt->execute([$mediaId]);
                $mediaRow = $mediaStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($mediaRow) {
                    $fileName = $mediaRow['file_name'];
                    $extension = $mediaRow['extension'];
                    $imageTitle = $mediaRow['title'] ?: 'Image';
                    $caption = $mediaRow['caption'];
                    
                    // Add extension to filename
                    $fullFileName = $fileName . '.' . $extension;
                    
                    // Create image tag - using blog-images folder for local copies
                    $imgTag = '<img src="../blog-images/' . htmlspecialchars($fullFileName) . '" alt="' . htmlspecialchars($imageTitle) . '" style="max-width: 100%; height: auto; margin: 15px 0;">';
                    
                    // Add caption if exists
                    if (!empty($caption)) {
                        $imgTag .= '<p style="font-size: 11px; margin-top: 5px; margin-bottom: 15px; line-height: 1.4;">' . nl2br(htmlspecialchars($caption)) . '</p>';
                    }
                    
                    // Insert image at the beginning of post content
                    $postContent = $imgTag . "\n\n" . $postContent;
                    
                    echo "  - Added image: $fullFileName" . ($caption ? " (with caption)" : "") . "\n";
                }
            }
        }
        
        // Create snippet (first 800 characters like original)
        // Extract the image tag first (if exists)
        $imageTag = '';
        if (preg_match('/<img[^>]+>/', $postContent, $matches)) {
            $imageTag = $matches[0];
        }
        
        // Extract caption if exists (right after image)
        $captionTag = '';
        if (preg_match('/<img[^>]+>\s*<p[^>]*>.*?<\/p>/s', $postContent, $matches)) {
            // Get just the caption paragraph
            if (preg_match('/<p[^>]*>.*?<\/p>/s', $matches[0], $captionMatch)) {
                $captionTag = $captionMatch[0];
            }
        }
        
        // Strip tags but KEEP links and br tags for text snippet
        $textSnippet = strip_tags($postContent, '<a><br>');

        // Remove the caption text from the snippet if it exists
        if (!empty($caption)) {
            $textSnippet = str_replace($caption, '', $textSnippet);
        }

        $textSnippet = substr($textSnippet, 0, 800);
        if (strlen(strip_tags($postContent)) > 800) {
            $textSnippet .= '...';
        }

        // Combine image + caption + text for final snippet
        $snippet = $imageTag . "\n" . $captionTag . "\n\n" . $textSnippet;

$post = [
    'id' => (int)$row['id'],
    'title' => mb_convert_encoding($row['title'], 'UTF-8', 'UTF-8'),
    'post' => mb_convert_encoding($postContent, 'UTF-8', 'UTF-8'),
    'snippet' => mb_convert_encoding($snippet, 'UTF-8', 'UTF-8'),
    'media' => $row['media'],
    'author' => mb_convert_encoding($row['author'], 'UTF-8', 'UTF-8'),
    'date_posted' => $row['date_posted'],
    'date_formatted' => $formattedDate,
    'tags' => $tagsArray
];
        
        $posts[] = $post;
        echo "Processed: {$row['title']}\n";
    }
    
echo "\nFound " . count($posts) . " blog posts\n";

// Convert to JSON and save
$json = json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Check for JSON encoding errors
if ($json === false) {
    echo "JSON encoding failed! Error: " . json_last_error_msg() . "\n";
    exit(1);
}

file_put_contents('blog-data.json', $json);
    
    echo "Successfully exported to blog-data.json!\n";
    echo "File size: " . number_format(strlen($json)) . " bytes\n";

    
    // List all unique image filenames
    $imageFiles = [];
    foreach ($posts as $post) {
        if (preg_match_all('/src="blog-images\/([^"]+)"/', $post['post'], $matches)) {
            foreach ($matches[1] as $filename) {
                $imageFiles[] = $filename;
            }
        }
    }
    $imageFiles = array_unique($imageFiles);
    
    echo "\n   Images to download:\n";
    foreach ($imageFiles as $filename) {
        echo "   - http://data.fairytaleproject.net/mediafiles/files/$filename\n";
    }
    
    echo "\n3. You can download them with curl:\n";
    foreach ($imageFiles as $filename) {
        echo "   curl -o ~/Desktop/fairytale-static/blog-images/$filename http://data.fairytaleproject.net/mediafiles/files/$filename\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
?>