<?php
/*  PAGE NAME: pages/analytics_web.php
    SECTION: Titty Bingo Website Analytics (GA4)
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';
?>
<section class="tb-section">
    <a href="index.php?page=analytics" class="tb-btn-secondary tb-back-link"><i class="fas fa-arrow-left"></i> Back to Analytics</a>
    <h1 class="tb-title">Titty Bingo Website</h1>
    <p class="tb-subtitle">Google Analytics totals for the website.</p>
    <div id="ga-loading" class="tb-loading">Loading Latest Analytics…</div>
    <div id="ga-content" style="display:none;">
        <?php include __DIR__ . '/../analytics/google_analytics.php'; ?>
    </div>
</section>
<script>
window.addEventListener('load', function() {
  const loading = document.getElementById('ga-loading');
  const content = document.getElementById('ga-content');
  if (loading && content) {
    loading.style.display = 'none';
    content.style.display = 'block';
  }
});
</script>
