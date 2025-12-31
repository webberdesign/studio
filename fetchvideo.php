<?php
/*  PAGE NAME: fetchvideo.php
    SECTION: Detailed YouTube Video Analytics
------------------------------------------------------------*/
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/user_helpers.php';

// Pull in the refresh token helper used by other analytics pages
include __DIR__ . '/analytics/refreshToken.php';
require_once __DIR__ . '/analytics/cache_helpers.php';

// Determine current theme for styling
$currentUser = tb_get_current_user($pdo);
$settings = tb_get_effective_settings($pdo, $currentUser);
$currentTheme = $settings['theme'];
$adminName = tb_get_admin_display_name($pdo);
$displayName = $adminName ?: ($currentUser['name'] ?? 'Member');
$displayIcon = !empty($currentUser['icon_path']) ? $currentUser['icon_path'] : 'assets/icons/icon-152.png';

$isAjax = isset($_GET['ajax']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

// Retrieve the requested video ID and validate it
$videoId = isset($_GET['videoID']) ? preg_replace('/[^\w-]/', '', $_GET['videoID']) : '';
if (!$videoId) {
    echo 'Missing or invalid video ID.';
    exit;
}

// Read API credentials from config.php (constants) or environment variables
$apiKey    = defined('YOUTUBE_API_KEY') ? YOUTUBE_API_KEY : getenv('YOUTUBE_API_KEY');
$channelId = defined('YOUTUBE_CHANNEL_ID') ? YOUTUBE_CHANNEL_ID : getenv('YOUTUBE_CHANNEL_ID');

// Get a fresh access token for the YouTube Analytics/Data APIs
$userId      = 1;
$accessToken = refreshToken($userId);

if (!$accessToken && !$apiKey) {
    echo 'YouTube credentials are not configured. Set OAuth tokens or YOUTUBE_API_KEY in config.php or environment variables.';
    exit;
}

/*
 * Helper functions copied from the provided fetchvideo.php example.  These
 * functions emit HTML directly.  We wrap the output in our own markup below.
 */

function tb_youtube_api_request($endpoint, array $params, $accessToken = null, $apiKey = null) {
    if ($apiKey) {
        $params['key'] = $apiKey;
    }
    $apiUrl = 'https://www.googleapis.com/youtube/v3/' . ltrim($endpoint, '/') . '?' . http_build_query($params);
    $ch = curl_init($apiUrl);
    $headers = [];
    if ($accessToken) {
        $headers[] = 'Authorization: Bearer ' . $accessToken;
    }
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function fetchTotalViews($videoId, $apiKey, $accessToken) {
    $response = tb_youtube_api_request('videos', [
        'part' => 'statistics',
        'id' => $videoId,
    ], $accessToken, $apiKey);
    if (isset($response['items'][0]['statistics']['viewCount'])) {
        $totalViews = $response['items'][0]['statistics']['viewCount'];
        $formattedTotalViews = number_format($totalViews);
        echo '<p><strong>Total Views:</strong> ' . $formattedTotalViews . '</p>';
    } else {
        echo '<p>Unable to retrieve total view count for the video.</p>';
    }
}

function fetchAnalyticsData($accessToken, $videoId, $dimension, $title, $maxResults = null, $additionalFilters = '') {
    if (!$accessToken) {
        echo '<div class="tb-analytics-subbox"><h3>' . htmlspecialchars($title) . '</h3><p>Missing YouTube access token. Please configure OAuth credentials.</p></div>';
        return;
    }

    $startDate = '2000-01-01';
    $endDate   = date('Y-m-d');
    $metrics   = 'views';
    $filters   = "video==$videoId" . ($additionalFilters ? ";$additionalFilters" : '');
    $sort      = '-views';
    $apiUrl    = "https://youtubeanalytics.googleapis.com/v2/reports?dimensions=$dimension&metrics=$metrics&ids=channel==MINE&startDate=$startDate&endDate=$endDate&filters=" . urlencode($filters) . "&sort=$sort";
    if ($maxResults) {
        $apiUrl .= "&maxResults=$maxResults";
    }
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    echo '<div class="tb-analytics-subbox">';
    echo '<h3>' . htmlspecialchars($title) . '</h3>';
    if (isset($response['rows'])) {
        echo '<ol>';
        foreach ($response['rows'] as $row) {
            $formattedViews = number_format($row[1]);
            echo '<li>' . htmlspecialchars($row[0]) . ' – ' . $formattedViews . ' views</li>';
        }
        echo '</ol>';
    } elseif (isset($response['error']['message'])) {
        echo '<p>Unable to retrieve user activity data: ' . htmlspecialchars($response['error']['message']) . '</p>';
    } else {
        echo '<p>Unable to retrieve user activity data for the dimension: ' . htmlspecialchars($dimension) . '.</p>';
    }
    echo '</div>';
}

function fetchUserActivityByCity($accessToken, $videoId) {
    fetchAnalyticsData($accessToken, $videoId, 'city', 'Views by City', 15);
}

function fetchUserActivityByUSCity($accessToken, $videoId) {
    fetchAnalyticsData($accessToken, $videoId, 'city', 'Views by U.S. City', 15, 'country==US');
}

function fetchUserActivityByCountry($accessToken, $videoId) {
    fetchAnalyticsData($accessToken, $videoId, 'country', 'Views by Country', 15);
}

function fetchUserActivityByProvince($accessToken, $videoId) {
    fetchAnalyticsData($accessToken, $videoId, 'province', 'Views by State', 15, 'country==US');
}

function fetchCurrentStatsDataAPI($apiKey, $videoId, $accessToken) {
    $response = tb_youtube_api_request('videos', [
        'part' => 'statistics',
        'id' => $videoId,
    ], $accessToken, $apiKey);
    if (isset($response['items'][0]['statistics'])) {
        $stats  = $response['items'][0]['statistics'];
        if (isset($stats['likeCount'])) {
            echo '<p><strong>Likes:</strong> ' . number_format($stats['likeCount']) . '</p>';
        }
        if (isset($stats['commentCount'])) {
            echo '<p><strong>Comments:</strong> ' . number_format($stats['commentCount']) . '</p>';
        }
    } else {
        echo '<p>Unable to retrieve statistics from Data API.</p>';
    }
}

function fetchSubscriberCount($apiKey, $channelId, $accessToken) {
    $params = [
        'part' => 'statistics',
    ];
    if ($channelId) {
        $params['id'] = $channelId;
    } else {
        $params['mine'] = 'true';
    }
    $response = tb_youtube_api_request('channels', $params, $accessToken, $apiKey);
    if (isset($response['items'][0]['statistics']['subscriberCount'])) {
        $subscribers = $response['items'][0]['statistics']['subscriberCount'];
        echo '<p><strong>Subscribers:</strong> ' . number_format($subscribers) . '</p>';
    } else {
        echo '<p>Unable to retrieve subscriber count.</p>';
    }
}

function displayVideoThumbnail($videoId) {
    $thumbnailUrl = "https://img.youtube.com/vi/$videoId/mqdefault.jpg";
    $videoUrl     = "https://www.youtube.com/watch?v=$videoId";
    echo '<div class="tb-video-thumb-container" style="margin-bottom:1rem;">';
    echo '<a href="' . htmlspecialchars($videoUrl) . '" target="_blank" rel="noopener">';
    echo '<img src="' . htmlspecialchars($thumbnailUrl) . '" alt="Video Thumbnail" class="tb-video-thumb" style="max-width:100%; border-radius:var(--tb-radius-lg); box-shadow:var(--tb-shadow-soft);">';
    echo '</a>';
    echo '</div>';
}

function fetchVideoTitle($apiKey, $videoId, $accessToken) {
    $response = tb_youtube_api_request('videos', [
        'id' => $videoId,
        'part' => 'snippet',
    ], $accessToken, $apiKey);
    if (isset($response['items'][0]['snippet']['title'])) {
        $videoTitle = $response['items'][0]['snippet']['title'];
        echo '<h2>' . htmlspecialchars($videoTitle) . '</h2>';
    } else {
        echo '<h2>Video</h2>';
    }
}

/*
 * Fetch estimated complete views (90–100%) and average view percentage.  These
 * are optional metrics and can be expensive to compute, so we include them
 * together in a single function.
 */
function fetchViewCompletionMetrics($accessToken, $videoId) {
    $startDate = '2000-01-01';
    $endDate   = date('Y-m-d');
    $metrics   = 'views,averageViewPercentage';
    $filters   = "video==$videoId";
    $apiUrl    = "https://youtubeanalytics.googleapis.com/v2/reports?metrics=$metrics&ids=channel==MINE&startDate=$startDate&endDate=$endDate&filters=" . urlencode($filters);
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    if (isset($response['rows'][0])) {
        $totalViews           = $response['rows'][0][0];
        $averageViewPercentage = $response['rows'][0][1];
        if ($averageViewPercentage >= 90) {
            $estimatedCompleteViews = $totalViews;
        } else {
            $estimatedCompleteViews = round($totalViews * ($averageViewPercentage / 100));
        }
        echo '<p><strong>Average View %:</strong> ' . round($averageViewPercentage, 2) . '%</p>';
        echo '<p><strong>Estimated Complete Views:</strong> ' . number_format($estimatedCompleteViews) . ' of ' . number_format($totalViews) . '</p>';
    } else {
        echo '<p>Unable to retrieve completion metrics.</p>';
    }
}

// Build cached analytics section markup.
$cacheKey = 'analytics_video_' . $videoId;
$sectionContent = tb_cache_read($cacheKey, 7200);
if ($sectionContent === null) {
    ob_start();
    ?>
    <section class="tb-section">
        <a href="index.php?page=analytics" class="tb-btn-secondary tb-back-link" data-loading-message="Loading Latest Analytics"><i class="fas fa-arrow-left"></i> Back to Analytics</a>
        <h1 class="tb-title">Video Analytics</h1>
        <?php
        // Display video title and thumbnail
        fetchVideoTitle($apiKey, $videoId, $accessToken);
        displayVideoThumbnail($videoId);
        ?>
        <div class="tb-analytics-box">
            <?php
            // Overall statistics: views, likes, comments, subscribers
            fetchTotalViews($videoId, $apiKey, $accessToken);
            fetchViewCompletionMetrics($accessToken, $videoId);
            fetchCurrentStatsDataAPI($apiKey, $videoId, $accessToken);
            fetchSubscriberCount($apiKey, $channelId, $accessToken);
            ?>
        </div>
        <div class="tb-analytics-box">
            <?php fetchUserActivityByCity($accessToken, $videoId); ?>
        </div>
        <div class="tb-analytics-box">
            <?php fetchUserActivityByUSCity($accessToken, $videoId); ?>
        </div>
        <div class="tb-analytics-box">
            <?php fetchUserActivityByProvince($accessToken, $videoId); ?>
        </div>
        <div class="tb-analytics-box">
            <?php fetchUserActivityByCountry($accessToken, $videoId); ?>
        </div>
    </section>
    <?php
    $sectionContent = ob_get_clean();
    tb_cache_write($cacheKey, $sectionContent);
}

if ($isAjax) {
    echo '<div class="tb-ajax-page" data-page-key="analytics" data-page-title="Titty Bingo Studio · Video Analytics">';
    echo $sectionContent;
    echo '</div>';
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Titty Bingo Studio &middot; Video Analytics</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Icons and core styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="public/css/app.css">
    <!-- Theme‑dependent meta color -->
    <meta name="theme-color" content="<?php echo ($currentTheme === 'light') ? '#ffffff' : '#0f172a'; ?>">
</head>
<body class="tb-body <?php echo ($currentTheme === 'light') ? 'tb-theme-light' : ''; ?>">

<!-- Side navigation -->
<aside id="tbSideNav" class="tb-sidenav">
    <div class="tb-sidenav-header">
        <h2>Titty Bingo Studio</h2>
        <button class="tb-close-btn" id="tbCloseNav"><i class="fas fa-times"></i></button>
    </div>
    <nav class="tb-sidenav-links">
        <a href="index.php?page=videos" data-nav-page="videos" class="<?php /* highlight if currently on videos? */ ?>">
            <i class="fas fa-film"></i> Videos
        </a>
        <a href="index.php?page=music" data-nav-page="music"><i class="fas fa-music"></i> Music</a>
        <a href="index.php?page=feed" data-nav-page="feed"><i class="fas fa-newspaper"></i> Feed</a>
        <a href="index.php?page=analytics" data-nav-page="analytics" class="active"><i class="fas fa-chart-line"></i> Analytics</a>
        <a href="index.php?page=settings" data-nav-page="settings"><i class="fas fa-gear"></i> Settings</a>
    </nav>
</aside>

<div class="tb-app-shell">
    <!-- Header with menu and logo -->
    <header class="tb-header">
        <button id="tbOpenNav" class="tb-menu-btn"><i class="fas fa-bars"></i></button>
        <div class="tb-logo-wrap">
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

    <!-- Main content -->
    <main class="tb-main">
        <?php echo $sectionContent; ?>
    </main>

    <!-- Bottom navigation -->
    <nav class="tb-bottom-nav">
        <a href="index.php?page=videos" data-nav-page="videos" class="tb-bottom-item"><i class="fas fa-film"></i><span>Videos</span></a>
        <a href="index.php?page=music" data-nav-page="music" class="tb-bottom-item"><i class="fas fa-music"></i><span>Music</span></a>
        <a href="index.php?page=feed" data-nav-page="feed" class="tb-bottom-item"><i class="fas fa-newspaper"></i><span>Feed</span></a>
        <a href="index.php?page=analytics" data-nav-page="analytics" class="tb-bottom-item active"><i class="fas fa-chart-line"></i><span>Analytics</span></a>
    </nav>

    <div id="tbPageLoading" class="tb-loading-overlay" aria-hidden="true">
        <div class="tb-loading">
            <span class="tb-loading-spinner" aria-hidden="true"></span>
            <span class="tb-loading-text">Loading…</span>
        </div>
    </div>
</div>

<!-- Core interactions -->
<script src="public/js/app.js"></script>
</body>
</html>
