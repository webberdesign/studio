<?php
/*  PAGE NAME: pages/music.php
    SECTION: Music Library (Public)
------------------------------------------------------------*/

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
$unreleasedCover = $placeholderCover;
foreach ($unreleased as $song) {
    if (!empty($song['cover_path']) && $unreleasedCover === $placeholderCover) {
        $unreleasedCover = $song['cover_path'];
    }
    $unreleasedTrackItems[] = [
        'title' => $song['title'],
        'src' => $song['mp3_path'] ?? '',
        'cover' => !empty($song['cover_path']) ? $song['cover_path'] : $placeholderCover,
        'has_cover' => !empty($song['cover_path']),
    ];
}
$unreleasedTrackItemsJson = htmlspecialchars(json_encode($unreleasedTrackItems), ENT_QUOTES, 'UTF-8');
$unreleasedTrackCount = count($unreleased);

// Build data for released tracklist player
$releasedTrackItems = [];
$releasedCover = $placeholderCover;
foreach ($releasedSongs as $song) {
    if (!empty($song['cover_path']) && $releasedCover === $placeholderCover) {
        $releasedCover = $song['cover_path'];
    }
    $releasedTrackItems[] = [
        'title' => $song['title'],
        'src' => $song['mp3_path'] ?? '',
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
    <div id="tbSongsUnreleased" class="tb-songs-pane tb-song-list-pane">
        <?php if (!empty($unreleased)): ?>
            <div class="tb-card-list">
                <?php foreach ($unreleased as $song): ?>
                    <?php $cover = !empty($song['cover_path']) ? $song['cover_path'] : $placeholderCover; ?>
                    <article class="tb-song-card">
                        <div class="tb-song-media tb-song-media--cover">
                            <img src="<?php echo htmlspecialchars($cover); ?>"
                                 alt="<?php echo htmlspecialchars($song['title']); ?>"
                                 class="tb-song-cover<?php echo empty($song['cover_path']) ? ' tb-song-cover--placeholder' : ''; ?>">
                        </div>
                        <div class="tb-song-body">
                            <h2 class="tb-card-title"><?php echo htmlspecialchars($song['title']); ?></h2>
                            <?php if (!empty($song['mp3_path'])): ?>
                                <button class="tb-song-play-btn" data-src="<?php echo htmlspecialchars($song['mp3_path']); ?>"><i class="fas fa-play"></i></button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="tb-empty">No unreleased tracks yet.</p>
        <?php endif; ?>
    </div>

    <!-- Released Songs -->
    <div id="tbSongsReleased" class="tb-songs-pane active tb-song-list-pane">
        <?php if (!empty($releasedSongs)): ?>
            <div class="tb-card-list">
                <?php foreach ($releasedSongs as $song): ?>
                    <?php $cover = !empty($song['cover_path']) ? $song['cover_path'] : $placeholderCover; ?>
                    <article class="tb-song-card">
                        <div class="tb-song-media tb-song-media--cover">
                            <img src="<?php echo htmlspecialchars($cover); ?>"
                                 alt="<?php echo htmlspecialchars($song['title']); ?>"
                                 class="tb-song-cover<?php echo empty($song['cover_path']) ? ' tb-song-cover--placeholder' : ''; ?>">
                        </div>
                        <div class="tb-song-body">
                            <h2 class="tb-card-title"><?php echo htmlspecialchars($song['title']); ?></h2>
                            <?php if (!empty($song['mp3_path'])): ?>
                                <button class="tb-song-play-btn" data-src="<?php echo htmlspecialchars($song['mp3_path']); ?>"><i class="fas fa-play"></i></button>
                            <?php endif; ?>
                            <div class="tb-song-links">
                                <?php if ($showAppleGlobal && !empty($song['apple_music_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($song['apple_music_url']); ?>" target="_blank" rel="noopener"><i class="fab fa-apple"></i></a>
                                <?php endif; ?>
                                <?php if ($showSpotifyGlobal && !empty($song['spotify_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($song['spotify_url']); ?>" target="_blank" rel="noopener"><i class="fab fa-spotify"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
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
</section>
