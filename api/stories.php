<?php
// api/save-story.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$dataDir = __DIR__ . '/../stories/';

// Create stories directory if it doesn't exist
if (!file_exists($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Save or update story
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['title']) || empty($input['content'])) {
        echo json_encode(['success' => false, 'message' => 'Title and content are required']);
        exit;
    }
    
    $storyId = $input['id'] ?? uniqid();
    $story = [
        'id' => $storyId,
        'title' => $input['title'],
        'content' => $input['content'],
        'category' => $input['category'] ?? 'general',
        'tags' => $input['tags'] ?? '',
        'status' => $input['status'] ?? 'published',
        'createdAt' => $input['createdAt'] ?? date('Y-m-d H:i:s'),
        'updatedAt' => date('Y-m-d H:i:s'),
        'slug' => createSlug($input['title'])
    ];
    
    $filename = $dataDir . $storyId . '.json';
    file_put_contents($filename, json_encode($story, JSON_PRETTY_PRINT));
    
    // Update index file for quick listing
    updateStoryIndex($story);
    
    echo json_encode(['success' => true, 'story' => $story]);
    
} elseif ($method === 'GET') {
    // Get stories list
    $stories = getAllStories();
    echo json_encode(['success' => true, 'stories' => $stories]);
    
} elseif ($method === 'DELETE') {
    // Delete story
    $input = json_decode(file_get_contents('php://input'), true);
    $storyId = $input['id'] ?? null;
    
    if ($storyId) {
        $filename = $dataDir . $storyId . '.json';
        if (file_exists($filename)) {
            unlink($filename);
        }
        removeFromIndex($storyId);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Story ID required']);
    }
}

function createSlug($title) {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

function getAllStories() {
    $dataDir = __DIR__ . '/../stories/';
    $stories = [];
    
    if (file_exists($dataDir)) {
        $files = glob($dataDir . '*.json');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $story = json_decode($content, true);
            if ($story && $story['status'] === 'published') {
                $stories[] = $story;
            }
        }
        // Sort by date, newest first
        usort($stories, function($a, $b) {
            return strtotime($b['createdAt']) - strtotime($a['createdAt']);
        });
    }
    
    return $stories;
}

function updateStoryIndex($story) {
    $indexFile = __DIR__ . '/../stories/index.json';
    $index = [];
    
    if (file_exists($indexFile)) {
        $index = json_decode(file_get_contents($indexFile), true) ?: [];
    }
    
    $index[$story['id']] = [
        'id' => $story['id'],
        'title' => $story['title'],
        'slug' => $story['slug'],
        'category' => $story['category'],
        'createdAt' => $story['createdAt'],
        'updatedAt' => $story['updatedAt']
    ];
    
    file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT));
}

function removeFromIndex($storyId) {
    $indexFile = __DIR__ . '/../stories/index.json';
    if (file_exists($indexFile)) {
        $index = json_decode(file_get_contents($indexFile), true) ?: [];
        unset($index[$storyId]);
        file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT));
    }
}
?>
