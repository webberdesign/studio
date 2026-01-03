<?php
/*  PAGE NAME: index.php
    SECTION: Bootstrap
------------------------------------------------------------*/
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/user_helpers.php';

// Determine current theme from settings
$currentUser = tb_get_current_user($pdo);
$settings = tb_get_effective_settings($pdo, $currentUser);
$currentTheme = $settings['theme'];
$adminName = tb_get_admin_display_name($pdo);
$displayName = $adminName ?: ($currentUser['name'] ?? 'Member');
$displayIcon = !empty($currentUser['icon_path']) ? $currentUser['icon_path'] : 'assets/icons/icon-152.png';
$oneSignalAppId = defined('ONESIGNAL_APP_ID') ? ONESIGNAL_APP_ID : '';

// Determine which page is being requested
// Default to the videos page.  Valid pages include our top‑level pages and
// analytics subpages (analytics‑yt and analytics‑sp are still routable but
// aren’t exposed directly in navigation).
$page = $_GET['page'] ?? 'videos';
$validPages = [
    'videos',
    'music',
    'feed',
    'analytics',
    'analytics-yt',
    'analytics-web',
    'analytics-app',
    'analytics-shop',
    'analytics-sp',
    'analytics-ig',
    'analytics-fb',
    'settings',
    'collection',
];
if (!in_array($page, $validPages, true)) {
    $page = 'videos';
}

$pageTitles = [
    'videos' => 'Titty Bingo Studio · Videos',
    'music' => 'Titty Bingo Studio · Music',
    'feed' => 'Titty Bingo Studio · Feed',
    'analytics' => 'Titty Bingo Studio · Analytics',
    'analytics-yt' => 'Titty Bingo Studio · YouTube Analytics',
    'analytics-web' => 'Titty Bingo Studio · Website Analytics',
    'analytics-app' => 'Titty Bingo Studio · App Analytics',
    'analytics-shop' => 'Titty Bingo Studio · Shop Analytics',
    'analytics-sp' => 'Titty Bingo Studio · Spotify Analytics',
    'analytics-ig' => 'Titty Bingo Studio · Instagram Analytics',
    'analytics-fb' => 'Titty Bingo Studio · Facebook Analytics',
    'settings' => 'Titty Bingo Studio · Settings',
    'collection' => 'Titty Bingo Studio · Collection',
];
$pageTitle = $pageTitles[$page] ?? 'Titty Bingo Studio';
$pageKey = in_array($page, ['analytics-yt', 'analytics-web', 'analytics-app', 'analytics-shop', 'analytics-sp', 'analytics-ig', 'analytics-fb'], true)
    ? 'analytics'
    : $page;

$isAjax = isset($_GET['ajax']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if (!$isAjax) {
    $userId = $currentUser['id'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO tb_app_opens (user_id, opened_at) VALUES (?, NOW())");
    $stmt->execute([$userId]);
}
if ($isAjax) {
    ob_start();
    switch ($page) {
        case 'music':
            include __DIR__ . '/pages/music.php';
            break;
        case 'analytics':
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
        case 'analytics-shop':
            include __DIR__ . '/pages/analytics_shop.php';
            break;
        case 'analytics-sp':
            include __DIR__ . '/pages/analytics_spotify.php';
            break;
        case 'analytics-ig':
            include __DIR__ . '/pages/analytics_instagram.php';
            break;
        case 'analytics-fb':
            include __DIR__ . '/pages/analytics_facebook.php';
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
    $content = ob_get_clean();
    echo '<div class="tb-ajax-page" data-page-key="' . htmlspecialchars($pageKey) . '" data-page-title="' . htmlspecialchars($pageTitle) . '">';
    echo $content;
    echo '</div>';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <!-- SECTION: Meta / PWA -->
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Set theme color for browser UI based on current theme -->
    <meta name="theme-color" content="<?php echo ($currentTheme === 'light') ? '#ffffff' : '#0f172a'; ?>">
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">

    <!-- SECTION: Fonts / Icons / CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="public/css/app.css">

    <!-- SECTION: OneSignal -->
    <script>
      window.tbOneSignalAppId = <?php echo json_encode($oneSignalAppId); ?>;
    </script>
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>

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
<div class="tb-lock-screen" id="tbLockScreen" data-auth-endpoint="user_session.php" aria-hidden="true">
    <div class="tb-lock-card">
        <div class="tb-lock-title">Welcome to Titty Bingo Studio</div>
        <p class="tb-lock-subtitle">Enter your 6-digit invite code to unlock this device.</p>
        <div class="tb-lock-inputs" role="group" aria-label="6-digit invite code">
            <?php for ($i = 0; $i < 6; $i++): ?>
                <input
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="1"
                    class="tb-lock-input"
                    aria-label="PIN digit <?php echo $i + 1; ?>"
                >
            <?php endfor; ?>
        </div>
        <p class="tb-lock-error" id="tbLockError" role="alert" aria-live="polite"></p>
    </div>
</div>

<!-- SECTION: Side Drawer -->
<aside id="tbSideNav" class="tb-sidenav">
    <div class="tb-sidenav-header">
        <h2>Titty Bingo Studio</h2>
        <button class="tb-close-btn" id="tbCloseNav"><i class="fas fa-times"></i></button>
    </div>
    <nav class="tb-sidenav-links">
        <a href="?page=videos" data-nav-page="videos" class="<?php echo ($page === 'videos') ? 'active' : ''; ?>">
            <i class="fas fa-film"></i> Videos
        </a>
        <a href="?page=music" data-nav-page="music" class="<?php echo ($page === 'music') ? 'active' : ''; ?>">
            <i class="fas fa-music"></i> Music
        </a>
        <a href="?page=feed" data-nav-page="feed" class="<?php echo ($page === 'feed') ? 'active' : ''; ?>">
            <i class="fas fa-newspaper"></i> Feed
        </a>
        <a href="?page=analytics" data-nav-page="analytics" class="<?php echo ($pageKey === 'analytics') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Analytics
        </a>
        <a href="?page=settings" data-nav-page="settings" class="<?php echo ($page === 'settings') ? 'active' : ''; ?>">
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
        <div class="tb-header-profile">
            <span class="tb-header-avatar">
                <img src="<?php echo htmlspecialchars($displayIcon); ?>" alt="Profile" class="tb-header-avatar-img">
            </span>
            <span class="tb-header-name"><?php echo htmlspecialchars($displayName); ?></span>
        </div>
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
            case 'analytics-shop':
                include __DIR__ . '/pages/analytics_shop.php';
                break;
            case 'analytics-sp':
                include __DIR__ . '/pages/analytics_spotify.php';
                break;
            case 'analytics-ig':
                include __DIR__ . '/pages/analytics_instagram.php';
                break;
            case 'analytics-fb':
                include __DIR__ . '/pages/analytics_facebook.php';
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

    <div class="tb-track-player is-hidden" data-track-player data-track-player-global>
        <div class="tb-track-player-info">
            <img src="assets/icons/icon-192.png" alt="" class="tb-track-player-cover" data-track-cover>
            <div>
                <div class="tb-track-player-label">Now playing</div>
                <div class="tb-track-player-title" data-track-current>Select a track</div>
                <div class="tb-track-player-file" data-track-file></div>
            </div>
        </div>
        <div class="tb-track-player-controls">
            <button type="button" class="tb-track-control" data-track-prev aria-label="Previous track">
                <svg viewBox="0 0 320 512" aria-hidden="true"><path d="M267.5 440.6c-9.5 7.9-22.8 9.7-34.1 4.4S215 428.4 215 416V96c0-12.4 7.2-23.7 18.4-29s24.5-3.6 34.1 4.4l-160 160v41.7l160 160z" fill="currentColor"></path></svg>
            </button>
            <button type="button" class="tb-track-control tb-track-play" data-track-play aria-label="Play">
                <svg viewBox="0 0 384 512" aria-hidden="true"><path d="M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80v352c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z" fill="currentColor"></path></svg>
            </button>
            <button type="button" class="tb-track-control" data-track-next aria-label="Next track">
                <svg viewBox="0 0 320 512" aria-hidden="true"><path d="M52.5 71.4c9.5-7.9 22.8-9.7 34.1-4.4S105 83.6 105 96v320c0 12.4-7.2 23.7-18.4 29s-24.5 3.6-34.1-4.4l160-160v-41.7L52.5 71.4z" fill="currentColor"></path></svg>
            </button>
        </div>
    </div>

    <!-- SECTION: Bottom Mobile Nav -->
    <nav class="tb-bottom-nav">
        <a href="?page=videos" data-nav-page="videos" class="tb-bottom-item <?php echo ($page === 'videos') ? 'active' : ''; ?>">
            <i class="fas fa-film"></i><span>Videos</span>
        </a>
        <a href="?page=music" data-nav-page="music" class="tb-bottom-item <?php echo ($page === 'music') ? 'active' : ''; ?>">
            <i class="fas fa-music"></i><span>Music</span>
        </a>
        <a href="?page=feed" data-nav-page="feed" class="tb-bottom-item <?php echo ($page === 'feed') ? 'active' : ''; ?>">
            <i class="fas fa-newspaper"></i><span>Feed</span>
        </a>
        <a href="?page=analytics" data-nav-page="analytics" class="tb-bottom-item <?php echo ($pageKey === 'analytics') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i><span>Analytics</span>
        </a>
    </nav>

    <div id="tbPageLoading" class="tb-loading-overlay" aria-hidden="true">
        <div class="tb-loading">
            <span class="tb-loading-spinner" aria-hidden="true"></span>
            <span class="tb-loading-text">Loading…</span>
        </div>
    </div>
</div>

<script src="public/js/app.js" defer></script>
<script src="public/js/track_player.js" defer></script>
</body>
</html>
