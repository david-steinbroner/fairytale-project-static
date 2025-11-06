<?php
// Connect to database
$host = '127.0.0.1';
$port = '8889';
$dbname = 'fairytaleproject';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n\n";
    
    // Get all participants from main table
    $stmt = $pdo->query("SELECT participant_number FROM fairytale_main WHERE active = 1 ORDER BY participant_number");
    $participants = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $contentData = [];
    
    foreach ($participants as $participantNumber) {
        echo "Processing participant $participantNumber...\n";
        
        $content = [
            'id' => $participantNumber,
            'chinese' => [
                'bio' => '',
                'application' => '',
                'interview' => '',
                'followup' => ''
            ],
            'english' => [
                'bio' => '',
                'application' => '',
                'interview' => '',
                'followup' => ''
            ],
            'german' => [
                'bio' => '',
                'application' => '',
                'interview' => '',
                'followup' => ''
            ]
        ];
        
        // Get Chinese content
        $stmt = $pdo->prepare("SELECT bio, application, interview, interview_2 
                               FROM fairytale_chinese 
                               WHERE participant_number = ? AND active = 1");
        $stmt->execute([$participantNumber]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content['chinese']['bio'] = $row['bio'] ?? '';
            $content['chinese']['application'] = $row['application'] ?? '';
            $content['chinese']['interview'] = $row['interview'] ?? '';
            $content['chinese']['followup'] = $row['interview_2'] ?? '';
        }
        
        // Get English content
        $stmt = $pdo->prepare("SELECT bio, application, interview, interview_2 
                               FROM fairytale_english 
                               WHERE participant_number = ? AND active = 1");
        $stmt->execute([$participantNumber]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content['english']['bio'] = $row['bio'] ?? '';
            $content['english']['application'] = $row['application'] ?? '';
            $content['english']['interview'] = $row['interview'] ?? '';
            $content['english']['followup'] = $row['interview_2'] ?? '';
        }
        
        // Get German content
        $stmt = $pdo->prepare("SELECT bio, application, interview, interview_2 
                               FROM fairytale_german 
                               WHERE participant_number = ? AND active = 1");
        $stmt->execute([$participantNumber]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content['german']['bio'] = $row['bio'] ?? '';
            $content['german']['application'] = $row['application'] ?? '';
            $content['german']['interview'] = $row['interview'] ?? '';
            $content['german']['followup'] = $row['interview_2'] ?? '';
        }
        
        $contentData[] = $content;
    }
    
    // Save to JSON file
    $jsonOutput = json_encode($contentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents('fairytale-content.json', $jsonOutput);
    
    echo "\n✅ Successfully exported content for " . count($contentData) . " participants!\n";
    echo "📄 File saved: fairytale-content.json\n";
    echo "📊 File size: " . number_format(strlen($jsonOutput) / 1024, 2) . " KB\n";
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>