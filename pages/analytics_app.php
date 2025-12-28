<?php
/*  PAGE NAME: pages/analytics_app.php
    SECTION: TB MusicBox App Analytics
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';
?>
<section class="tb-section">
    <a href="index.php?page=analytics" class="tb-btn-secondary tb-back-link" data-loading-message="Loading Latest Analytics"><i class="fas fa-arrow-left"></i> Back to Analytics</a>
    <h1 class="tb-title">TB MusicBox App</h1>
    <p class="tb-subtitle">Coming soon &mdash; app engagement and playback analytics.</p>

    <div id="app-loading" class="tb-loading">Loading Latest Analytics</div>
    <div id="app-content" style="display:none;">
        <div class="tb-stats-grid">
            <div class="tb-stat-card is-disabled">
                <h3>Installed Users</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Page Views</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Music Plays</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Video Plays</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Comments</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Heart Likes</h3>
                <div class="tb-stat-value">—</div>
            </div>
        </div>

        <p class="tb-coming-soon">This dashboard will light up once the app analytics pipeline is connected.</p>
    </div>
</section>
