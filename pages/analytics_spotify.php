<?php
/*  PAGE NAME: pages/analytics_spotify.php
    SECTION: Spotify Analytics Page
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';
?>
<section class="tb-section">
    <h1 class="tb-title">Spotify Analytics</h1>
    <p class="tb-subtitle">Real-time insights from our Spotify profile</p>
    
    <div id="sp-loading" class="tb-loading">Loading Latest Analytics…</div>
    <div id="sp-content" style="display:none;">
        <?php include __DIR__ . '/../analytics/spotify_analytics.php'; ?>
    </div>
</section>
<script>
window.addEventListener('load', function() {
  const loading = document.getElementById('sp-loading');
  const content = document.getElementById('sp-content');
  if (loading && content) {
    loading.style.display = 'none';
    content.style.display = 'block';
  }
});
</script>
