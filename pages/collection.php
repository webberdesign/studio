<?php
/*  PAGE NAME: pages/collection.php
    SECTION: Collection Tracklist and Player (Public)
------------------------------------------------------------*/

require_once __DIR__ . '/../config.php';

// Get collection ID from query string
$collectionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($collectionId <= 0) {
    echo '<section class="tb-section"><p class="tb-empty">Collection not found.</p></section>';
    return;
}
// Fetch collection details
$stmt = $pdo->prepare("SELECT * FROM tb_collections WHERE id = ?");
$stmt->execute([$collectionId]);
$collection = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$collection) {
    echo '<section class="tb-section"><p class="tb-empty">Collection not found.</p></section>';
    return;
}
// Determine theme for body class
$currentTheme = tb_get_theme();

// Fetch tracks for this collection (released only)
$stmt = $pdo->prepare("SELECT * FROM tb_songs WHERE collection_id = ? AND is_released = 1 ORDER BY position ASC, created_at DESC");
$stmt->execute([$collectionId]);
$collectionTracks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$placeholderCover = 'assets/icons/icon-192.png';
$trackItems = [];
foreach ($collectionTracks as $track) {
    if (!empty($track['mp3_path'])) {
        $trackItems[] = [
            'title' => $track['title'],
            'src' => $track['mp3_path'],
            'cover' => !empty($track['cover_path']) ? $track['cover_path'] : $placeholderCover,
        ];
    }
}
$trackCount = count($trackItems);
$coverImage = !empty($collection['cover_path']) ? $collection['cover_path'] : $placeholderCover;
$trackItemsJson = htmlspecialchars(json_encode($trackItems), ENT_QUOTES, 'UTF-8');
?>

<section class="tb-section">
    <div class="tb-tracklist" data-tracklist data-tracks="<?php echo $trackItemsJson; ?>">
        <div class="tb-tracklist-header">
            <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($collection['name']); ?>" class="tb-tracklist-cover">
            <div class="tb-tracklist-meta">
                <h1 class="tb-title"><?php echo htmlspecialchars($collection['name']); ?></h1>
                <p class="tb-tracklist-count"><?php echo $trackCount; ?> track<?php echo $trackCount === 1 ? '' : 's'; ?></p>
                <p class="tb-subtitle">Listen to the tracks from this collection.</p>
            </div>
        </div>

        <?php if (!empty($trackItems)): ?>
            <div class="tb-tracklist-rows">
                <?php foreach ($trackItems as $index => $track): ?>
                    <button type="button" class="tb-track-row" data-track-index="<?php echo $index; ?>">
                        <span class="tb-track-number"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                        <span class="tb-track-title"><?php echo htmlspecialchars($track['title']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="tb-track-player" data-track-player>
                <div class="tb-track-player-info">
                    <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="" class="tb-track-player-cover" data-track-cover>
                    <div>
                        <div class="tb-track-player-label">Now playing</div>
                        <div class="tb-track-player-title" data-track-current>Select a track</div>
                    </div>
                </div>
                <div class="tb-track-player-controls">
                    <button type="button" class="tb-track-control" data-track-prev aria-label="Previous track">
                        <svg viewBox="0 0 320 512" aria-hidden="true"><path d="M267.5 440.6c-9.5 7.9-22.8 9.7-34.1 4.4S215 428.4 215 416V96c0-12.4 7.2-23.7 18.4-29s24.5-3.6 34.1 4.4l-160 160v41.7l160 160z" fill="currentColor"></path></svg>
                    </button>
                    <button type="button" class="tb-track-control tb-track-play" data-track-play aria-label="Play">
                        <svg viewBox="0 0 384 512" aria-hidden="true"><path d="M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80v352c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z" fill="currentColor"></path></svg>
                    </button>
                    <button type="button" class="tb-track-control" data-track-next aria-label="Next track">
                        <svg viewBox="0 0 320 512" aria-hidden="true"><path d="M52.5 71.4c9.5-7.9 22.8-9.7 34.1-4.4S105 83.6 105 96v320c0 12.4-7.2 23.7-18.4 29s-24.5 3.6-34.1-4.4l160-160v-41.7L52.5 71.4z" fill="currentColor"></path></svg>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <p class="tb-empty">No tracks yet.</p>
        <?php endif; ?>
    </div>
</section>
