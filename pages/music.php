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

// Fetch collections for display on the music page
$collections = $pdo->query("SELECT * FROM tb_collections ORDER BY name ASC")
                ->fetchAll(PDO::FETCH_ASSOC);
?>
<section class="tb-section">
    <h1 class="tb-title">Music</h1>
    <p class="tb-subtitle">Take a listen to our unreleased demos or groove to what’s out now.</p>

    <!-- SECTION: Collections / Albums -->
    <?php if (!empty($collections)): ?>
    <h2 style="margin-top:0.5rem; font-size:1.1rem;">Albums</h2>
    <div class="tb-card-grid" style="margin-bottom:1rem;">
        <?php foreach ($collections as $c): ?>
        <?php $cover = !empty($c['cover_path']) ? $c['cover_path'] : $placeholderCover; ?>
        <a href="?page=collection&amp;id=<?php echo $c['id']; ?>" class="tb-card" style="text-decoration:none;">
            <img src="<?php echo htmlspecialchars($cover); ?>" alt="<?php echo htmlspecialchars($c['name']); ?>" class="tb-card-thumb">
            <div class="tb-card-body">
                <h3 class="tb-card-title" style="font-size:0.95rem; margin:0; color:inherit;">
                    <?php echo htmlspecialchars($c['name']); ?>
                </h3>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- SECTION: Toggle between unreleased and released songs -->
    <div class="tb-toggle-pill" id="tbSongsToggle">
        <button type="button" class="active" data-target="unreleased">Unreleased</button>
        <button type="button" data-target="released">Released</button>
    </div>

    <!-- Unreleased Songs -->
    <div id="tbSongsUnreleased" class="tb-songs-pane active">
        <?php foreach ($unreleased as $song): ?>
            <article class="tb-song-card" data-id="<?php echo $song['id']; ?>">
                <div class="tb-song-media">
                    <?php if (!empty($song['cover_path'])): ?>
                        <img src="<?php echo htmlspecialchars($song['cover_path']); ?>"
                             alt="<?php echo htmlspecialchars($song['title']); ?>"
                             class="tb-song-cover">
                    <?php else: ?>
                        <img src="<?php echo $placeholderCover; ?>"
                             alt="<?php echo htmlspecialchars($song['title']); ?>"
                             class="tb-song-cover">
                    <?php endif; ?>
                </div>
                <div class="tb-song-body">
                    <h2 class="tb-card-title"><?php echo htmlspecialchars($song['title']); ?></h2>
                    <?php if (!empty($song['mp3_path'])): ?>
                        <button class="tb-song-play-btn" data-src="<?php echo htmlspecialchars($song['mp3_path']); ?>"><i class="fas fa-play"></i></button>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (empty($unreleased)): ?>
            <p class="tb-empty">No unreleased tracks yet.</p>
        <?php endif; ?>
    </div>

    <!-- Released Songs -->
    <div id="tbSongsReleased" class="tb-songs-pane">
        <?php foreach ($releasedSongs as $song): ?>
            <article class="tb-song-card" data-id="<?php echo $song['id']; ?>">
                <div class="tb-song-media">
                    <?php if (!empty($song['cover_path'])): ?>
                        <img src="<?php echo htmlspecialchars($song['cover_path']); ?>"
                             alt="<?php echo htmlspecialchars($song['title']); ?>"
                             class="tb-song-cover">
                    <?php else: ?>
                        <img src="<?php echo $placeholderCover; ?>"
                             alt="<?php echo htmlspecialchars($song['title']); ?>"
                             class="tb-song-cover">
                    <?php endif; ?>
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
        <?php if (empty($releasedSongs)): ?>
            <p class="tb-empty">No released tracks yet.</p>
        <?php endif; ?>
    </div>
</section>
