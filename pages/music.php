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
    <div id="tbSongsUnreleased" class="tb-songs-pane tb-tracklist-pane">
        <div class="tb-tracklist" data-tracklist data-tracks="<?php echo $unreleasedTrackItemsJson; ?>">
            <div class="tb-tracklist-header">
                <img src="<?php echo htmlspecialchars($unreleasedCover); ?>" alt="Unreleased cover" class="tb-tracklist-cover">
                <div class="tb-tracklist-meta">
                    <h2 class="tb-title">Unreleased</h2>
                    <p class="tb-tracklist-count"><?php echo $unreleasedTrackCount; ?> track<?php echo $unreleasedTrackCount === 1 ? '' : 's'; ?></p>
                    <p class="tb-subtitle">Listen to our unreleased demos.</p>
                </div>
            </div>

            <?php if (!empty($unreleased)): ?>
                <div class="tb-tracklist-rows">
                    <?php foreach ($unreleasedTrackItems as $index => $track): ?>
                        <button type="button" class="tb-track-row" data-track-index="<?php echo $index; ?>">
                            <span class="tb-track-cover-wrap">
                                <img src="<?php echo htmlspecialchars($track['cover']); ?>" alt="" class="tb-track-cover<?php echo empty($track['has_cover']) ? ' is-placeholder' : ''; ?>">
                            </span>
                            <span class="tb-track-main">
                                <span class="tb-track-number"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                                <span class="tb-track-title"><?php echo htmlspecialchars($track['title']); ?></span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="tb-track-player is-hidden" data-track-player>
                    <div class="tb-track-player-info">
                        <img src="<?php echo htmlspecialchars($unreleasedCover); ?>" alt="" class="tb-track-player-cover" data-track-cover>
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
                <p class="tb-empty">No unreleased tracks yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Released Songs -->
    <div id="tbSongsReleased" class="tb-songs-pane active tb-tracklist-pane">
        <div class="tb-tracklist" data-tracklist data-tracks="<?php echo $releasedTrackItemsJson; ?>">
            <div class="tb-tracklist-header">
                <img src="<?php echo htmlspecialchars($releasedCover); ?>" alt="Released cover" class="tb-tracklist-cover">
                <div class="tb-tracklist-meta">
                    <h2 class="tb-title">Released</h2>
                    <p class="tb-tracklist-count"><?php echo $releasedTrackCount; ?> track<?php echo $releasedTrackCount === 1 ? '' : 's'; ?></p>
                    <p class="tb-subtitle">Stream what’s out now.</p>
                </div>
            </div>

            <?php if (!empty($releasedSongs)): ?>
                <div class="tb-tracklist-rows">
                    <?php foreach ($releasedTrackItems as $index => $track): ?>
                        <div class="tb-track-row" data-track-index="<?php echo $index; ?>">
                            <div class="tb-track-main">
                                <span class="tb-track-cover-wrap">
                                    <img src="<?php echo htmlspecialchars($track['cover']); ?>" alt="" class="tb-track-cover<?php echo empty($track['has_cover']) ? ' is-placeholder' : ''; ?>">
                                </span>
                                <span class="tb-track-number"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                                <span class="tb-track-title"><?php echo htmlspecialchars($track['title']); ?></span>
                            </div>
                            <div class="tb-track-links">
                                <?php if ($showAppleGlobal && !empty($track['apple'])): ?>
                                    <a href="<?php echo htmlspecialchars($track['apple']); ?>" target="_blank" rel="noopener" aria-label="Apple Music">
                                        <i class="fab fa-apple"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($showSpotifyGlobal && !empty($track['spotify'])): ?>
                                    <a href="<?php echo htmlspecialchars($track['spotify']); ?>" target="_blank" rel="noopener" aria-label="Spotify">
                                        <i class="fab fa-spotify"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="tb-track-player is-hidden" data-track-player>
                    <div class="tb-track-player-info">
                        <img src="<?php echo htmlspecialchars($releasedCover); ?>" alt="" class="tb-track-player-cover" data-track-cover>
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
                <p class="tb-empty">No released tracks yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Collections -->
    <div id="tbSongsCollections" class="tb-songs-pane tb-tracklist-pane">
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
