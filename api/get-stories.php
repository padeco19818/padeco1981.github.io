<?php
/**
 * get-stories.php
 * Retrieves all saved stories from the server
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$STORAGE_FILE = __DIR__ . '/../stories.json';

// Read stories from file
if (!file_exists($STORAGE_FILE)) {
    echo json_encode([
        'success' => true,
        'count' => 0,
        'stories' => []
    ]);
    exit();
}

$stories = json_decode(file_get_contents($STORAGE_FILE), true);

if (!is_array($stories)) {
    echo json_encode([
        'success' => true,
        'count' => 0,
        'stories' => []
    ]);
    exit();
}

// Return stories
echo json_encode([
    'success' => true,
    'count' => count($stories),
    'stories' => $stories
], JSON_PRETTY_PRINT);
?>
