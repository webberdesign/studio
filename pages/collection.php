<?php
/*  PAGE NAME: pages/collection.php
    SECTION: Collection Tracklist and Player (Public)
------------------------------------------------------------*/

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../user_helpers.php';
require_once __DIR__ . '/../onesignal_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_collection_comment']) && !empty($_POST['collection_id'])) {
        $collectionIdPost = (int) $_POST['collection_id'];
        $comment = trim($_POST['comment_body'] ?? '');
        $authorName = tb_get_comment_author($pdo);
        $authorUserId = tb_get_comment_author_id($pdo);
        if ($comment !== '' && $authorName) {
            $stmt = $pdo->prepare("INSERT INTO tb_collection_comments (collection_id, author_name, author_user_id, body) VALUES (?, ?, ?, ?)");
            $stmt->execute([$collectionIdPost, $authorName, $authorUserId, $comment]);
            $titleStmt = $pdo->prepare("SELECT name FROM tb_collections WHERE id = ? LIMIT 1");
            $titleStmt->execute([$collectionIdPost]);
            $collectionRow = $titleStmt->fetch(PDO::FETCH_ASSOC);
            $context = $collectionRow && !empty($collectionRow['name']) ? $collectionRow['name'] : 'a collection';
            tb_notify_comment($pdo, $context, $authorName, $authorUserId, '/?page=collection&id=' . $collectionIdPost);
        }
    }
}

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
$currentUser = tb_get_current_user($pdo);
$settings = tb_get_effective_settings($pdo, $currentUser);
$currentTheme = $settings['theme'];

// Fetch tracks for this collection
$stmt = $pdo->prepare("SELECT * FROM tb_songs WHERE collection_id = ? ORDER BY position ASC, created_at DESC");
$stmt->execute([$collectionId]);
$collectionTracks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$placeholderCover = 'assets/icons/icon-192.png';
$trackItems = [];
foreach ($collectionTracks as $track) {
    $audioPath = $track['mp3_path'] ?? '';
    if ($audioPath === '' && !empty($track['m4a_path'])) {
        $audioPath = $track['m4a_path'];
    }
    $trackItems[] = [
        'title' => $track['title'],
        'src' => $audioPath,
        'mp3' => $track['mp3_path'] ?? '',
        'm4a' => $track['m4a_path'] ?? '',
        'cover' => !empty($track['cover_path']) ? $track['cover_path'] : $placeholderCover,
    ];
}
$trackCount = count($collectionTracks);
$coverImage = !empty($collection['cover_path']) ? $collection['cover_path'] : $placeholderCover;
$trackItemsJson = htmlspecialchars(json_encode($trackItems), ENT_QUOTES, 'UTF-8');

$collectionComments = [];
$commentsStmt = $pdo->prepare("SELECT * FROM tb_collection_comments WHERE collection_id = ? ORDER BY created_at ASC");
$commentsStmt->execute([$collectionId]);
$collectionComments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="tb-section">
    <div class="tb-tracklist" data-tracklist data-tracklist-id="collection-<?php echo $collectionId; ?>" data-tracks="<?php echo $trackItemsJson; ?>">
        <div class="tb-tracklist-header">
            <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="<?php echo htmlspecialchars($collection['name']); ?>" class="tb-tracklist-cover">
            <div class="tb-tracklist-meta">
                <h1 class="tb-title"><?php echo htmlspecialchars($collection['name']); ?></h1>
                <p class="tb-tracklist-count"><?php echo $trackCount; ?> track<?php echo $trackCount === 1 ? '' : 's'; ?></p>
                <p class="tb-subtitle">Listen to the tracks from this collection.</p>
            </div>
        </div>

        <?php if (!empty($collectionTracks)): ?>
            <div class="tb-tracklist-rows">
                <?php foreach ($collectionTracks as $index => $track): ?>
                    <button type="button" class="tb-track-row" data-track-index="<?php echo $index; ?>">
                        <span class="tb-track-number"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                        <span class="tb-track-cover-wrap">
                            <img src="<?php echo htmlspecialchars(!empty($track['cover_path']) ? $track['cover_path'] : $placeholderCover); ?>" alt="" class="tb-track-cover<?php echo empty($track['cover_path']) ? ' is-placeholder' : ''; ?>">
                        </span>
                        <span class="tb-track-main">
                            <span class="tb-track-title"><?php echo htmlspecialchars($track['title']); ?></span>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="tb-track-player is-hidden" data-track-player>
                <div class="tb-track-player-info">
                    <img src="<?php echo htmlspecialchars($coverImage); ?>" alt="" class="tb-track-player-cover" data-track-cover>
                    <div>
                        <div class="tb-track-player-label">Now playing</div>
                        <div class="tb-track-player-title" data-track-current>Select a track</div>
                        <div class="tb-track-player-file" data-track-file></div>
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

    <div class="tb-feed-comments tb-collection-comments">
        <h2>Comments</h2>
        <?php if (empty($collectionComments)): ?>
            <p class="tb-muted">No comments yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($collectionComments as $comment): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($comment['author_name'] ?: 'Member'); ?>:</strong>
                        <?php echo nl2br(htmlspecialchars($comment['body'])); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <form method="post" class="tb-feed-comment-form">
            <input type="hidden" name="collection_id" value="<?php echo (int) $collectionId; ?>">
            <textarea name="comment_body" rows="3" required placeholder="Write a comment..."></textarea>
            <button type="submit" name="add_collection_comment" class="tb-btn-secondary">Post Comment</button>
        </form>
    </div>
</section>
