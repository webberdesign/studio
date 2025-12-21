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
?>

<section class="tb-section">
    <h1 class="tb-title"><?php echo htmlspecialchars($collection['name']); ?></h1>
    <?php if (!empty($collection['cover_path'])): ?>
        <div style="text-align:center; margin-bottom:1rem;">
            <img src="<?php echo htmlspecialchars($collection['cover_path']); ?>" alt="<?php echo htmlspecialchars($collection['name']); ?>" style="max-width:200px; border-radius:12px;">
        </div>
    <?php endif; ?>
    <p class="tb-subtitle">Listen to the tracks from this collection.</p>
</section>

<!-- Player UI -->
<div class="vibe-player">
  <!-- Sticky mini-player bar -->
  <div id="miniPlayer">
    <div id="miniProgressContainer" class="progress-container">
      <div id="miniProgress" class="progress-bar"></div>
    </div>
    <div class="mini-left">
      <img id="miniCover" class="mini-cover" alt="Track cover">
      <div id="miniTrackInfo" class="mini-info">Loading…</div>
    </div>
    <div class="mini-buttons">
      <button id="miniPlayPauseBtn" aria-label="Play">
        <!-- Play icon -->
        <svg class="icon" viewBox="0 0 384 512" aria-hidden="true"><path d="M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80L0 432c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z" fill="currentColor"></path></svg>
      </button>
      <button id="miniNextBtn" aria-label="Next track">
        <!-- Forward icon -->
        <svg class="icon" viewBox="0 0 512 512" aria-hidden="true"><path d="M52.5 440.6c-9.5 7.9-22.8 9.7-34.1 4.4S0 428.4 0 416L0 96C0 83.6 7.2 72.3 18.4 67s24.5-3.6 34.1 4.4L224 214.3l0 41.7 0 41.7L52.5 440.6zM256 352l0-96 0-128 0-32c0-12.4 7.2-23.7 18.4-29s24.5-3.6 34.1 4.4l192 160c7.3 6.1 11.5 15.1 11.5 24.6s-4.2 18.5-11.5 24.6l-192 160c-9.5 7.9-22.8 9.7-34.1 4.4s-18.4-16.6-18.4-29l0-64z" fill="currentColor"></path></svg>
      </button>
      <button id="expandBtn" aria-label="Expand player">
        <!-- Chevron-up icon -->
        <svg class="icon" viewBox="0 0 512 512" aria-hidden="true"><path d="M233.4 105.4c12.5-12.5 32.8-12.5 45.3 0l192 192c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L256 173.3 86.6 342.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l192-192z" fill="currentColor"></path></svg>
      </button>
    </div>
  </div>

  <!-- Full player overlay -->
  <div id="fullPlayer" class="collapsed">
    <div class="full-header">
      <button id="collapseBtn" aria-label="Collapse player">
        <!-- Chevron-down icon -->
        <svg class="icon" viewBox="0 0 512 512" aria-hidden="true"><path d="M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z" fill="currentColor"></path></svg>
      </button>
      <div id="fullTrackInfo" class="full-info">Loading…</div>
    </div>
    <img id="fullCover" class="full-cover" alt="Track cover">
    <div id="fullProgressContainer" class="progress-container">
      <div id="fullProgress" class="progress-bar"></div>
    </div>
    <div class="full-controls">
      <button id="fullPlayPauseBtn" aria-label="Play">
        <!-- Play icon -->
        <svg class="icon" viewBox="0 0 384 512" aria-hidden="true"><path d="M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80L0 432c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z" fill="currentColor"></path></svg>
      </button>
      <button id="fullNextBtn" aria-label="Next track">
        <!-- Forward icon -->
        <svg class="icon" viewBox="0 0 512 512" aria-hidden="true"><path d="M52.5 440.6c-9.5 7.9-22.8 9.7-34.1 4.4S0 428.4 0 416L0 96C0 83.6 7.2 72.3 18.4 67s24.5-3.6 34.1 4.4L224 214.3l0 41.7 0 41.7L52.5 440.6zM256 352l0-96 0-128 0-32c0-12.4 7.2-23.7 18.4-29s24.5-3.6 34.1 4.4l192 160c7.3 6.1 11.5 15.1 11.5 24.6s-4.2 18.5-11.5 24.6l-192 160c-9.5 7.9-22.8 9.7-34.1 4.4s-18.4-16.6-18.4-29l0-64z" fill="currentColor"></path></svg>
      </button>
    </div>
    <ol id="trackList"></ol>
  </div>
</div>

<!-- Include CSS and JS for the player -->
<link rel="stylesheet" href="public/css/vibeplayer.css">
<script src="public/js/vibe_audio_player.js"></script>