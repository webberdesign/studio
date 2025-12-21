<?php
/*
 * vibeplayer.php
 *
 * This PHP endpoint returns a simple JSON playlist for the client‑side
 * audio player.  Provide a `collectionId` query parameter to load
 * tracks for a specific collection.  Each track is represented by
 * an associative array with `title`, `src`, and `cover` keys.  If no
 * collection is specified or no tracks exist, an empty array is returned.
 */

header('Content-Type: application/json');

// Include database config to obtain $pdo
require_once __DIR__ . '/config.php';

// Get the collection ID; default to 0 (invalid) if absent
$collectionId = isset($_GET['collectionId']) ? (int)$_GET['collectionId'] : 0;

// If collection ID is not provided or <= 0, return empty array
if ($collectionId <= 0) {
    echo json_encode([]);
    exit;
}

// Fetch songs belonging to this collection and marked as released
$stmt = $pdo->prepare("SELECT * FROM tb_songs WHERE collection_id = ? AND is_released = 1 ORDER BY position ASC, created_at DESC");
$stmt->execute([$collectionId]);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items = [];
foreach ($songs as $s) {
    // Use mp3_path as source; if null, skip track
    if (!empty($s['mp3_path'])) {
        $items[] = [
            'title' => $s['title'],
            'src'   => $s['mp3_path'],
            'cover' => !empty($s['cover_path']) ? $s['cover_path'] : '',
        ];
    }
}

echo json_encode($items);