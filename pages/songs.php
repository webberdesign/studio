<?php
/*  PAGE NAME: pages/songs.php
    SECTION: Unreleased Songs (Public)
------------------------------------------------------------*/

// fetch songs and separate unreleased vs released
$stmt = $pdo->query("SELECT * FROM tb_songs ORDER BY created_at DESC");
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
?>
<section class="tb-section">
    <h1 class="tb-title">Songs</h1>
    <p class="tb-subtitle">Take a listen to our unreleased demos or groove to what’s out now.</p>

    <!-- SECTION: Toggle between unreleased and released songs -->
    <div class="tb-toggle-pill" id="tbSongsToggle">
        <button type="button" class="active" data-target="unreleased">Unreleased</button>
        <button type="button" data-target="released">Released</button>
    </div>

    <!-- Unreleased Songs -->
    <div id="tbSongsUnreleased" class="tb-songs-pane active">
        <?php foreach ($unreleased as $song): ?>
        <article class="tb-song-card">
            <div class="tb-song-media">
                <?php if (!empty($song['cover_path'])): ?>
                    <img src="<?php echo htmlspecialchars($song['cover_path']); ?>"
                         alt="<?php echo htmlspecialchars($song['title']); ?>"
                         class="tb-song-cover">
                <?php else: ?>
                    <div class="tb-song-cover tb-song-cover--placeholder">
                        <i class="fas fa-compact-disc"></i>
                    </div>
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
        <article class="tb-song-card">
            <div class="tb-song-media">
                <?php if (!empty($song['cover_path'])): ?>
                    <img src="<?php echo htmlspecialchars($song['cover_path']); ?>"
                         alt="<?php echo htmlspecialchars($song['title']); ?>"
                         class="tb-song-cover">
                <?php else: ?>
                    <div class="tb-song-cover tb-song-cover--placeholder">
                        <i class="fas fa-compact-disc"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="tb-song-body">
                <h2 class="tb-card-title"><?php echo htmlspecialchars($song['title']); ?></h2>
                <?php if (!empty($song['mp3_path'])): ?>
                    <button class="tb-song-play-btn" data-src="<?php echo htmlspecialchars($song['mp3_path']); ?>"><i class="fas fa-play"></i></button>
                <?php endif; ?>
                <div class="tb-song-links">
                    <?php if (!empty($song['apple_music_url'])): ?>
                        <a href="<?php echo htmlspecialchars($song['apple_music_url']); ?>" target="_blank" rel="noopener"><i class="fab fa-apple"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($song['spotify_url'])): ?>
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