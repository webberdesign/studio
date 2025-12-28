<?php
/*  PAGE NAME: pages/analytics_youtube.php
    SECTION: YouTube Analytics Page
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';
?>
<section class="tb-section">
    <a href="index.php?page=analytics" class="tb-btn-secondary tb-back-link" data-loading-message="Loading Latest Analytics"><i class="fas fa-arrow-left"></i> Back to Analytics</a>
    <h1 class="tb-title">YouTube Analytics</h1>
    <p class="tb-subtitle">Real-time insights from our YouTube channel</p>
    
    <div id="yt-loading" class="tb-loading">Loading Latest Analytics</div>
    <div id="yt-content" style="display:none;">
        <?php include __DIR__ . '/../analytics/youtube_analytics.php'; ?>
    </div>
</section>
