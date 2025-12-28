<?php
/*  PAGE NAME: pages/analytics.php
    SECTION: Analytics Landing Page
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';

// This page acts as a hub for analytics providers.  Instead of loading all
// analytics at once, it links out to the dedicated YouTube and Spotify
// analytics pages which load their own data (with a loading indicator).
?>
<section class="tb-section">
    <h1 class="tb-title">Analytics</h1>
    <p class="tb-subtitle">Choose which platform’s insights you’d like to view.</p>

    <div class="tb-analytics-links">
        <a href="?page=analytics-yt" class="tb-analytics-link" data-loading-message="Loading Latest Analytics">
            <i class="fab fa-youtube"></i>
            <span>YouTube Analytics</span>
        </a>
        <a href="?page=analytics-sp" class="tb-analytics-link" data-loading-message="Loading Latest Analytics">
            <i class="fab fa-spotify"></i>
            <span>Spotify Analytics</span>
        </a>
        <a href="?page=analytics-web" class="tb-analytics-link" data-loading-message="Loading Latest Analytics">
            <i class="fas fa-globe"></i>
            <span>Titty Bingo Website</span>
        </a>
        <a href="?page=analytics-app" class="tb-analytics-link" data-loading-message="Loading Latest Analytics">
            <i class="fas fa-mobile-screen-button"></i>
            <span>TB MusicBox App</span>
        </a>
        <a href="?page=analytics-ig" class="tb-analytics-link" data-loading-message="Loading Latest Analytics">
            <i class="fab fa-instagram"></i>
            <span>Instagram Analytics</span>
        </a>
        <a href="?page=analytics-fb" class="tb-analytics-link" data-loading-message="Loading Latest Analytics">
            <i class="fab fa-facebook"></i>
            <span>Facebook Analytics</span>
        </a>
    </div>
</section>
