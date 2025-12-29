<?php
/*  PAGE NAME: pages/analytics_facebook.php
    SECTION: Facebook Analytics Page
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';
?>
<section class="tb-section">
    <a href="index.php?page=analytics" class="tb-btn-secondary tb-back-link" data-loading-message="Loading Latest Analytics"><i class="fas fa-arrow-left"></i> Back to Analytics</a>
    <h1 class="tb-title">Facebook Analytics</h1>
    <p class="tb-subtitle">Coming soon &mdash; Facebook reach and engagement highlights.</p>

    <div id="fb-loading" class="tb-loading">Loading Latest Analytics</div>
    <div id="fb-content" style="display:none;">
        <div class="tb-stats-grid">
            <div class="tb-stat-card is-disabled">
                <h3>Followers</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Page Likes</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Page Views</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Total Reach</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Post Impressions</h3>
                <div class="tb-stat-value">—</div>
            </div>
            <div class="tb-stat-card is-disabled">
                <h3>Video Views</h3>
                <div class="tb-stat-value">—</div>
            </div>
        </div>

        <p class="tb-coming-soon">Facebook analytics will populate once the reporting pipeline is live.</p>
    </div>
</section>
