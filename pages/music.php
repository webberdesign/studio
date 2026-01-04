<?php
/*  PAGE NAME: pages/music.php
    SECTION: Music Library (Public)
------------------------------------------------------------*/
require_once __DIR__ . '/../user_helpers.php';
require_once __DIR__ . '/../onesignal_helpers.php';

$currentUser = tb_get_current_user($pdo);
$canCreateCollection = $currentUser || tb_is_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_song_comment']) && !empty($_POST['song_id'])) {
        $songId = (int) $_POST['song_id'];
        $comment = trim($_POST['comment_body'] ?? '');
        $authorName = tb_get_comment_author($pdo);
        $authorUserId = tb_get_comment_author_id($pdo);
        if ($comment !== '' && $authorName) {
            $stmt = $pdo->prepare("INSERT INTO tb_song_comments (song_id, author_name, author_user_id, body) VALUES (?, ?, ?, ?)");
            $stmt->execute([$songId, $authorName, $authorUserId, $comment]);
            $titleStmt = $pdo->prepare("SELECT title FROM tb_songs WHERE id = ? LIMIT 1");
            $titleStmt->execute([$songId]);
            $song = $titleStmt->fetch(PDO::FETCH_ASSOC);
            $context = $song && !empty($song['title']) ? $song['title'] : 'a track';
            tb_notify_comment($pdo, $context, $authorName, $authorUserId, '/?page=music');
        }
    }

    if (isset($_POST['add_collection_comment']) && !empty($_POST['collection_id'])) {
        $collectionId = (int) $_POST['collection_id'];
        $comment = trim($_POST['comment_body'] ?? '');
        $authorName = tb_get_comment_author($pdo);
        $authorUserId = tb_get_comment_author_id($pdo);
        if ($comment !== '' && $authorName) {
            $stmt = $pdo->prepare("INSERT INTO tb_collection_comments (collection_id, author_name, author_user_id, body) VALUES (?, ?, ?, ?)");
            $stmt->execute([$collectionId, $authorName, $authorUserId, $comment]);
            $titleStmt = $pdo->prepare("SELECT name FROM tb_collections WHERE id = ? LIMIT 1");
            $titleStmt->execute([$collectionId]);
            $collection = $titleStmt->fetch(PDO::FETCH_ASSOC);
            $context = $collection && !empty($collection['name']) ? $collection['name'] : 'a collection';
            tb_notify_comment($pdo, $context, $authorName, $authorUserId, '/?page=music');
        }
    }

    if (isset($_POST['add_collection_public']) && $canCreateCollection) {
        $collectionName = trim($_POST['collection_name'] ?? '');
        $collectionCover = trim($_POST['collection_cover'] ?? '');
        if ($collectionName !== '') {
            $stmt = $pdo->prepare("INSERT INTO tb_collections (name, cover_path) VALUES (?, ?)");
            $stmt->execute([$collectionName, $collectionCover !== '' ? $collectionCover : null]);
        }
    }
}

// Fetch songs and separate unreleased vs released
$stmt = $pdo->query("SELECT * FROM tb_songs ORDER BY position ASC, created_at DESC");
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unreleased = [];
$releasedSongs = [];
foreach ($songs as $s) {
    if (!empty($s['is_released'])) {
        $releasedSongs[] = $s;
    } else {
        $unreleased[] = $s;
    }
}

$songCommentThreads = [];
if (!empty($unreleased)) {
    $songIds = array_map('intval', array_column($unreleased, 'id'));
    $placeholders = implode(',', array_fill(0, count($songIds), '?'));
    $commentsStmt = $pdo->prepare("SELECT * FROM tb_song_comments WHERE song_id IN ($placeholders) ORDER BY created_at ASC");
    $commentsStmt->execute($songIds);
    foreach ($commentsStmt->fetchAll(PDO::FETCH_ASSOC) as $comment) {
        $songCommentThreads[$comment['song_id']][] = $comment;
    }
}
// Placeholder cover (app icon) for tracks without artwork
// Global settings determine whether to show Spotify and Apple buttons.  Retrieve
// them once for the page to avoid repeated file reads.
$settings = tb_get_effective_settings($pdo);
$showSpotifyGlobal = !empty($settings['show_spotify']);
$showAppleGlobal   = !empty($settings['show_apple']);

$placeholderCover = 'assets/icons/icon-192.png';
// Determine if the current user is an admin.  Admins can edit songs
// directly from the music page using the same inputs as the admin panel.
$isAdmin = tb_is_admin();

// Build data for the unreleased tracklist player
$unreleasedTrackItems = [];
foreach ($unreleased as $song) {
    $audioPath = $song['mp3_path'] ?? '';
    if ($audioPath === '' && !empty($song['m4a_path'])) {
        $audioPath = $song['m4a_path'];
    }
    $unreleasedTrackItems[] = [
        'title' => $song['title'],
        'src' => $audioPath,
        'mp3' => $song['mp3_path'] ?? '',
        'm4a' => $song['m4a_path'] ?? '',
        'cover' => !empty($song['cover_path']) ? $song['cover_path'] : $placeholderCover,
        'has_cover' => !empty($song['cover_path']),
    ];
}
$unreleasedTrackItemsJson = htmlspecialchars(json_encode($unreleasedTrackItems), ENT_QUOTES, 'UTF-8');
$unreleasedTrackCount = count($unreleased);

// Build data for released tracklist player
$releasedTrackItems = [];
foreach ($releasedSongs as $song) {
    $audioPath = $song['mp3_path'] ?? '';
    if ($audioPath === '' && !empty($song['m4a_path'])) {
        $audioPath = $song['m4a_path'];
    }
    $releasedTrackItems[] = [
        'title' => $song['title'],
        'src' => $audioPath,
        'mp3' => $song['mp3_path'] ?? '',
        'm4a' => $song['m4a_path'] ?? '',
        'cover' => !empty($song['cover_path']) ? $song['cover_path'] : $placeholderCover,
        'has_cover' => !empty($song['cover_path']),
        'apple' => $song['apple_music_url'] ?? '',
        'spotify' => $song['spotify_url'] ?? '',
    ];
}
$releasedTrackItemsJson = htmlspecialchars(json_encode($releasedTrackItems), ENT_QUOTES, 'UTF-8');
$releasedTrackCount = count($releasedSongs);

// Fetch collections for display on the music page
$collections = $pdo->query("SELECT * FROM tb_collections ORDER BY name ASC")
                ->fetchAll(PDO::FETCH_ASSOC);

$collectionCommentThreads = [];
if (!empty($collections)) {
    $collectionIds = array_map('intval', array_column($collections, 'id'));
    $placeholders = implode(',', array_fill(0, count($collectionIds), '?'));
    $commentsStmt = $pdo->prepare("SELECT * FROM tb_collection_comments WHERE collection_id IN ($placeholders) ORDER BY created_at ASC");
    $commentsStmt->execute($collectionIds);
    foreach ($commentsStmt->fetchAll(PDO::FETCH_ASSOC) as $comment) {
        $collectionCommentThreads[$comment['collection_id']][] = $comment;
    }
}
?>
<section class="tb-section">
    <h1 class="tb-title">Music</h1>
    <p class="tb-subtitle">Take a listen to our unreleased demos or groove to what’s out now.</p>

    <!-- SECTION: Toggle between released, unreleased, and collections -->
    <div class="tb-toggle-pill" id="tbSongsToggle">
        <button type="button" class="active" data-target="released">Released</button>
        <button type="button" data-target="unreleased">Unreleased</button>
        <button type="button" data-target="collections">Collections</button>
    </div>

    <!-- Unreleased Songs -->
    <div id="tbSongsUnreleased" class="tb-songs-pane tb-tracklist-pane">
        <?php if (!empty($unreleased)): ?>
            <div class="tb-tracklist" data-tracklist data-tracklist-id="unreleased" data-tracks="<?php echo $unreleasedTrackItemsJson; ?>">
                <div class="tb-tracklist-rows">
                    <?php foreach ($unreleased as $index => $song): ?>
                        <?php $cover = !empty($song['cover_path']) ? $song['cover_path'] : $placeholderCover; ?>
                        <div class="tb-track-row" role="button" tabindex="0" data-track-index="<?php echo $index; ?>">
                            <span class="tb-track-number"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                            <span class="tb-track-cover-wrap">
                                <img src="<?php echo htmlspecialchars($cover); ?>" alt="" class="tb-track-cover<?php echo empty($song['cover_path']) ? ' is-placeholder' : ''; ?>">
                            </span>
                            <span class="tb-track-main">
                                <span class="tb-track-title"><?php echo htmlspecialchars($song['title']); ?></span>
                                <span class="tb-track-actions">
                                    <button type="button" class="tb-track-comment-trigger" data-song-db-id="<?php echo (int) $song['id']; ?>" data-song-title="<?php echo htmlspecialchars($song['title']); ?>" aria-label="Open comments for <?php echo htmlspecialchars($song['title']); ?>">
                                        <i class="fas fa-comment"></i>
                                    </button>
                                </span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <p class="tb-empty">No unreleased tracks yet.</p>
        <?php endif; ?>
    </div>

    <!-- Released Songs -->
    <div id="tbSongsReleased" class="tb-songs-pane active tb-tracklist-pane">
        <?php if (!empty($releasedSongs)): ?>
            <div class="tb-tracklist" data-tracklist data-tracklist-id="released" data-tracks="<?php echo $releasedTrackItemsJson; ?>">
                <div class="tb-tracklist-rows">
                    <?php foreach ($releasedSongs as $index => $song): ?>
                        <?php $cover = !empty($song['cover_path']) ? $song['cover_path'] : $placeholderCover; ?>
                        <div class="tb-track-row" role="button" tabindex="0" data-track-index="<?php echo $index; ?>">
                            <span class="tb-track-number"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                            <span class="tb-track-cover-wrap">
                                <img src="<?php echo htmlspecialchars($cover); ?>" alt="" class="tb-track-cover<?php echo empty($song['cover_path']) ? ' is-placeholder' : ''; ?>">
                            </span>
                            <span class="tb-track-main">
                                <span class="tb-track-title"><?php echo htmlspecialchars($song['title']); ?></span>
                                <span class="tb-track-links">
                                    <?php if ($showAppleGlobal && !empty($song['apple_music_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($song['apple_music_url']); ?>" target="_blank" rel="noopener"><i class="fab fa-apple"></i></a>
                                    <?php endif; ?>
                                    <?php if ($showSpotifyGlobal && !empty($song['spotify_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($song['spotify_url']); ?>" target="_blank" rel="noopener"><i class="fab fa-spotify"></i></a>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <p class="tb-empty">No released tracks yet.</p>
        <?php endif; ?>
    </div>

    <!-- Collections -->
    <div id="tbSongsCollections" class="tb-songs-pane">
        <?php if ($canCreateCollection): ?>
            <div class="tb-feed-form tb-collection-form">
                <div class="tb-feed-form-header">
                    <div class="tb-collection-create-header">
                        <h3>Create a collection</h3>
                        <p>Add a title and optional cover URL to start grouping tracks.</p>
                    </div>
                    <button type="button" class="tb-toggle-pill tb-feed-toggle" id="tbCollectionToggle">
                        <span>New Collection</span>
                    </button>
                </div>
                <form method="post" class="tb-form-inline tb-collection-create" id="tbCollectionCreateForm" hidden>
                    <label>
                        Collection name
                        <input type="text" name="collection_name" required placeholder="New collection name">
                    </label>
                    <label>
                        Cover image URL (optional)
                        <input type="url" name="collection_cover" placeholder="https://...">
                    </label>
                    <button type="submit" name="add_collection_public" class="tb-btn-secondary">Create collection</button>
                </form>
            </div>
        <?php endif; ?>
        <?php if (!empty($collections)): ?>
            <div class="tb-card-grid tb-collection-grid" style="margin-bottom:1rem;">
                <?php foreach ($collections as $c): ?>
                    <?php
                    $cover = !empty($c['cover_path']) ? $c['cover_path'] : $placeholderCover;
                    $coverClass = !empty($c['cover_path']) ? '' : ' is-placeholder';
                    ?>
                    <div class="tb-card tb-collection-card">
                        <a href="?page=collection&amp;id=<?php echo $c['id']; ?>" class="tb-collection-link">
                            <img src="<?php echo htmlspecialchars($cover); ?>" alt="<?php echo htmlspecialchars($c['name']); ?>" class="tb-card-thumb tb-collection-cover<?php echo $coverClass; ?>">
                        </a>
                        <div class="tb-card-body">
                            <div class="tb-card-header">
                                <a href="?page=collection&amp;id=<?php echo $c['id']; ?>" class="tb-card-title-link">
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </a>
                                <button type="button" class="tb-collection-comment-trigger" data-collection-db-id="<?php echo (int) $c['id']; ?>" data-collection-title="<?php echo htmlspecialchars($c['name']); ?>" aria-label="Open comments for <?php echo htmlspecialchars($c['name']); ?>">
                                    <i class="fas fa-comment"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="tb-empty">No collections yet.</p>
        <?php endif; ?>
    </div>

    <div id="songCommentModal" class="tb-video-modal tb-song-comment-modal">
        <div class="tb-modal-content tb-song-comment-content">
            <button class="tb-modal-close" type="button">&times;</button>
            <div class="tb-song-comment-header">
                <h3 id="songCommentTitle">Comments</h3>
            </div>
            <div class="tb-feed-comments">
                <h4>Comments</h4>
                <?php if (empty($unreleased)): ?>
                    <p class="tb-muted">No comments yet.</p>
                <?php else: ?>
                    <?php foreach ($unreleased as $song): ?>
                        <?php $comments = $songCommentThreads[$song['id']] ?? []; ?>
                        <div class="tb-song-comment-thread" data-song-id="<?php echo (int) $song['id']; ?>" hidden>
                            <?php if (empty($comments)): ?>
                                <p class="tb-muted">No comments yet.</p>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($comments as $comment): ?>
                                        <li>
                                            <strong><?php echo htmlspecialchars($comment['author_name'] ?: 'Member'); ?>:</strong>
                                            <?php echo nl2br(htmlspecialchars($comment['body'])); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <form method="post" class="tb-feed-comment-form">
                <input type="hidden" name="song_id" value="">
                <textarea name="comment_body" rows="2" required placeholder="Write a comment..."></textarea>
                <button type="submit" name="add_song_comment" class="tb-btn-secondary">Post Comment</button>
            </form>
        </div>
    </div>

    <div id="collectionCommentModal" class="tb-video-modal tb-song-comment-modal">
        <div class="tb-modal-content tb-song-comment-content">
            <button class="tb-modal-close" type="button">&times;</button>
            <div class="tb-song-comment-header">
                <h3 id="collectionCommentTitle">Comments</h3>
            </div>
            <div class="tb-feed-comments">
                <h4>Comments</h4>
                <?php if (empty($collections)): ?>
                    <p class="tb-muted">No comments yet.</p>
                <?php else: ?>
                    <?php foreach ($collections as $collection): ?>
                        <?php $comments = $collectionCommentThreads[$collection['id']] ?? []; ?>
                        <div class="tb-collection-comment-thread" data-collection-id="<?php echo (int) $collection['id']; ?>" hidden>
                            <?php if (empty($comments)): ?>
                                <p class="tb-muted">No comments yet.</p>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($comments as $comment): ?>
                                        <li>
                                            <strong><?php echo htmlspecialchars($comment['author_name'] ?: 'Member'); ?>:</strong>
                                            <?php echo nl2br(htmlspecialchars($comment['body'])); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <form method="post" class="tb-feed-comment-form">
                <input type="hidden" name="collection_id" value="">
                <textarea name="comment_body" rows="2" required placeholder="Write a comment..."></textarea>
                <button type="submit" name="add_collection_comment" class="tb-btn-secondary">Post Comment</button>
            </form>
        </div>
    </div>
</section>
