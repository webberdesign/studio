<?php
/*  PAGE NAME: pages/music.php
    SECTION: Music Library (Public)
------------------------------------------------------------*/
require_once __DIR__ . '/../user_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_song_comment']) && !empty($_POST['song_id'])) {
        $songId = (int) $_POST['song_id'];
        $comment = trim($_POST['comment_body'] ?? '');
        $authorName = tb_get_comment_author($pdo);
        if ($comment !== '' && $authorName) {
            $stmt = $pdo->prepare("INSERT INTO tb_song_comments (song_id, author_name, body) VALUES (?, ?, ?)");
            $stmt->execute([$songId, $authorName, $comment]);
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
$settings = tb_get_settings();
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
        <?php if (!empty($collections)): ?>
            <div class="tb-card-grid" style="margin-bottom:1rem;">
                <?php foreach ($collections as $c): ?>
                    <?php
                    $cover = !empty($c['cover_path']) ? $c['cover_path'] : $placeholderCover;
                    $coverClass = !empty($c['cover_path']) ? '' : ' is-placeholder';
                    ?>
                    <a href="?page=collection&amp;id=<?php echo $c['id']; ?>" class="tb-card" style="text-decoration:none;">
                        <img src="<?php echo htmlspecialchars($cover); ?>" alt="<?php echo htmlspecialchars($c['name']); ?>" class="tb-card-thumb tb-collection-cover<?php echo $coverClass; ?>">
                        <div class="tb-card-body">
                            <h3 class="tb-card-title" style="font-size:0.95rem; margin:0; color:inherit;">
                                <?php echo htmlspecialchars($c['name']); ?>
                            </h3>
                        </div>
                    </a>
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
</section>
