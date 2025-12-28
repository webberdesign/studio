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

    <div class="tb-track-player is-hidden" data-track-player data-track-player-global>
        <div class="tb-track-player-info">
            <img src="<?php echo htmlspecialchars($placeholderCover); ?>" alt="" class="tb-track-player-cover" data-track-cover>
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

    <!-- Unreleased Songs -->
    <div id="tbSongsUnreleased" class="tb-songs-pane tb-tracklist-pane">
        <?php if (!empty($unreleased)): ?>
            <div class="tb-tracklist" data-tracklist data-tracklist-id="unreleased" data-tracks="<?php echo $unreleasedTrackItemsJson; ?>">
                <div class="tb-tracklist-rows">
                    <?php foreach ($unreleased as $index => $song): ?>
                        <?php $cover = !empty($song['cover_path']) ? $song['cover_path'] : $placeholderCover; ?>
                        <button type="button" class="tb-track-row" data-track-index="<?php echo $index; ?>">
                            <span class="tb-track-number"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                            <span class="tb-track-cover-wrap">
                                <img src="<?php echo htmlspecialchars($cover); ?>" alt="" class="tb-track-cover<?php echo empty($song['cover_path']) ? ' is-placeholder' : ''; ?>">
                            </span>
                            <span class="tb-track-main">
                                <span class="tb-track-title"><?php echo htmlspecialchars($song['title']); ?></span>
                            </span>
                        </button>
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
                        <button type="button" class="tb-track-row" data-track-index="<?php echo $index; ?>">
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
                        </button>
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
</section>
