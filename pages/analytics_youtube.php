<?php
/*  PAGE NAME: pages/analytics_youtube.php
    SECTION: YouTube Analytics Page
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';
?>
<section class="tb-section">
    <h1 class="tb-title">YouTube Analytics</h1>
    <p class="tb-subtitle">Real-time insights from our YouTube channel</p>
    
    <div id="yt-loading" class="tb-loading">Loading YouTube data…</div>
    <div id="yt-content" style="display:none;">
        <?php include __DIR__ . '/../analytics/youtube_analytics.php'; ?>
    </div>
</section>
<script>
// Show loading message briefly, then reveal content once loaded
window.addEventListener('load', function() {
  const loading = document.getElementById('yt-loading');
  const content = document.getElementById('yt-content');
  if (loading && content) {
    loading.style.display = 'none';
    content.style.display = 'block';
  }
});
</script>
