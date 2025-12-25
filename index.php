<?php
/*  PAGE NAME: index.php
    SECTION: Bootstrap
------------------------------------------------------------*/
require_once __DIR__ . '/config.php';

// Determine current theme from settings
$currentTheme = tb_get_theme();

// Determine which page is being requested
// Default to the videos page.  Valid pages include our top‑level pages and
// analytics subpages (analytics‑yt and analytics‑sp are still routable but
// aren’t exposed directly in navigation).
$page = $_GET['page'] ?? 'videos';
$validPages = ['videos', 'music', 'feed', 'analytics', 'analytics-yt', 'analytics-web', 'analytics-app', 'analytics-sp', 'settings', 'collection'];
$validPages = ['videos', 'music', 'feed', 'analytics', 'analytics-yt', 'analytics-sp', 'settings', 'collection'];
if (!in_array($page, $validPages, true)) {
    $page = 'videos';
}
?>
<!doctype html>
<html lang="en">
<head>
    <!-- SECTION: Meta / PWA -->
    <meta charset="UTF-8">
    <title>Titty Bingo Studio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Set theme color for browser UI based on current theme -->
    <meta name="theme-color" content="<?php echo ($currentTheme === 'light') ? '#ffffff' : '#0f172a'; ?>">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">

    <!-- SECTION: Fonts / Icons / CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="public/css/app.css">

    <!-- SECTION: Ping Script -->
    <script>
    (function(){
      const payload = {
        domain: location.hostname,
        full_url: location.href,
        name:  'Titty Bingo Studio',
        description: 'Titty Bingo Studio Web App',
        version: '1.0.0',
        icon_url: ''
      };
      const ping = () => navigator.sendBeacon
          ? navigator.sendBeacon('https://apps.webbersites.com/ping.php', JSON.stringify(payload))
          : fetch('https://apps.webbersites.com/ping.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
      ping();
      setInterval(ping, 288e5);
    })();
    </script>
</head>
<body class="tb-body <?php echo ($currentTheme === 'light') ? 'tb-theme-light' : ''; ?>">

<!-- SECTION: Side Drawer -->
<aside id="tbSideNav" class="tb-sidenav">
    <div class="tb-sidenav-header">
        <h2>Titty Bingo Studio</h2>
        <button class="tb-close-btn" id="tbCloseNav"><i class="fas fa-times"></i></button>
    </div>
    <nav class="tb-sidenav-links">
        <a href="?page=videos" class="<?php echo ($page === 'videos') ? 'active' : ''; ?>">
            <i class="fas fa-film"></i> Videos
        </a>
        <a href="?page=music" class="<?php echo ($page === 'music') ? 'active' : ''; ?>">
            <i class="fas fa-music"></i> Music
        </a>
        <a href="?page=feed" class="<?php echo ($page === 'feed') ? 'active' : ''; ?>">
            <i class="fas fa-newspaper"></i> Feed
        </a>
        <a href="?page=analytics" class="<?php echo ($page === 'analytics') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Analytics
        </a>
        <a href="?page=settings" class="<?php echo ($page === 'settings') ? 'active' : ''; ?>">
            <i class="fas fa-gear"></i> Settings
        </a>
    </nav>
</aside>

<!-- SECTION: Main Shell -->
<div class="tb-app-shell">
    <!-- SECTION: Header -->
    <header class="tb-header">
        <button id="tbOpenNav" class="tb-menu-btn">
            <i class="fas fa-bars"></i>
        </button>

        <div class="tb-logo-wrap">
            <!-- Logo at top of page -->
            <img src="assets/img/titty-bingo-logo.png" alt="Titty Bingo" class="tb-logo">
            <span class="tb-logo-tag">Studio</span>
        </div>

        <div class="tb-header-spacer"></div>
    </header>

    <!-- SECTION: Page Content -->
    <main class="tb-main">
        <?php
        switch ($page) {
            case 'music':
                include __DIR__ . '/pages/music.php';
                break;
            case 'analytics':
                // Landing page for analytics that links to individual provider pages
                include __DIR__ . '/pages/analytics.php';
                break;
            case 'analytics-yt':
                include __DIR__ . '/pages/analytics_youtube.php';
                break;
            case 'analytics-web':
                include __DIR__ . '/pages/analytics_web.php';
                break;
            case 'analytics-app':
                include __DIR__ . '/pages/analytics_app.php';
                break;
            case 'analytics-sp':
                include __DIR__ . '/pages/analytics_spotify.php';
                break;
            case 'feed':
                include __DIR__ . '/pages/feed.php';
                break;
            case 'settings':
                include __DIR__ . '/pages/settings.php';
                break;
            case 'collection':
                include __DIR__ . '/pages/collection.php';
                break;
            case 'videos':
            default:
                include __DIR__ . '/pages/videos.php';
                break;
        }
        ?>
    </main>

    <!-- SECTION: Bottom Mobile Nav -->
    <nav class="tb-bottom-nav">
        <a href="?page=videos" class="tb-bottom-item <?php echo ($page === 'videos') ? 'active' : ''; ?>">
            <i class="fas fa-film"></i><span>Videos</span>
        </a>
        <a href="?page=music" class="tb-bottom-item <?php echo ($page === 'music') ? 'active' : ''; ?>">
            <i class="fas fa-music"></i><span>Music</span>
        </a>
        <a href="?page=feed" class="tb-bottom-item <?php echo ($page === 'feed') ? 'active' : ''; ?>">
            <i class="fas fa-newspaper"></i><span>Feed</span>
        </a>
        <a href="?page=analytics" class="tb-bottom-item <?php echo ($page === 'analytics') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i><span>Analytics</span>
        </a>
    </nav>
</div>

<script src="public/js/app.js"></script>
</body>
</html>
