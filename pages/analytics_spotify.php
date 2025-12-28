<?php
/*  PAGE NAME: pages/analytics_spotify.php
    SECTION: Spotify Analytics Page
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';
?>
<section class="tb-section">
    <a href="index.php?page=analytics" class="tb-btn-secondary tb-back-link" data-loading-message="Loading Latest Analytics"><i class="fas fa-arrow-left"></i> Back to Analytics</a>
    <h1 class="tb-title">Spotify Analytics</h1>
    <p class="tb-subtitle">Real-time insights from our Spotify profile</p>
    
    <div id="sp-loading" class="tb-loading">Loading Latest Analytics</div>
    <div id="sp-content" style="display:none;">
        <?php include __DIR__ . '/../analytics/spotify_analytics.php'; ?>
    </div>
</section>
