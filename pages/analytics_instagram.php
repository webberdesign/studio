<?php
/*  PAGE NAME: pages/analytics_instagram.php
    SECTION: Instagram Analytics Page
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';
?>
<section class="tb-section">
    <a href="index.php?page=analytics" class="tb-btn-secondary tb-back-link" data-loading-message="Loading Latest Analytics"><i class="fas fa-arrow-left"></i> Back to Analytics</a>
    <h1 class="tb-title">Instagram Analytics</h1>
    <p class="tb-subtitle">Coming soon &mdash; Instagram audience and engagement snapshots.</p>

    <div id="ig-loading" class="tb-loading">Loading Latest Analytics</div>
    <div id="ig-content" style="display:none;">
        <div class="tb-stats-grid">
            <div class="tb-stat-card is-disabled">
                <h3>Followers</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Profile Visits</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Reels Plays</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Story Views</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Top Post Saves</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Engagement Rate</h3>
                <div class="tb-stat-value">—</div>
            </div>
        </div>

        <p class="tb-coming-soon">Instagram analytics will appear here once the data feed is connected.</p>
    </div>
</section>
