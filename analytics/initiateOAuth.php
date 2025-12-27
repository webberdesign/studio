<?php
/*  FILE: analytics/initiateOAuth.php
    SECTION: Google OAuth Initiation (YouTube/GA)
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';

$service = $_GET['service'] ?? 'youtube';
$service = in_array($service, ['youtube', 'ga'], true) ? $service : 'youtube';

if ($service === 'ga') {
    $clientId = getenv('GA_CLIENT_ID') ?: (defined('GA_CLIENT_ID') ? GA_CLIENT_ID : null);
    $redirectUri = getenv('GA_OAUTH_REDIRECT_URI')
        ?: (defined('GA_OAUTH_REDIRECT_URI') ? GA_OAUTH_REDIRECT_URI : null);
    $scope = 'https://www.googleapis.com/auth/analytics.readonly';
} else {
    $clientId = getenv('YOUTUBE_CLIENT_ID') ?: (defined('YOUTUBE_CLIENT_ID') ? YOUTUBE_CLIENT_ID : null);
    $redirectUri = getenv('YOUTUBE_OAUTH_REDIRECT_URI')
        ?: (defined('YOUTUBE_OAUTH_REDIRECT_URI') ? YOUTUBE_OAUTH_REDIRECT_URI : null);
    $scope = 'https://www.googleapis.com/auth/yt-analytics.readonly https://www.googleapis.com/auth/youtube.readonly';
}

if (!$clientId || !$redirectUri) {
    echo 'Missing OAuth client configuration. Set client ID and redirect URI.';
    exit;
}

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?'
    . 'response_type=code'
    . '&client_id=' . urlencode($clientId)
    . '&redirect_uri=' . urlencode($redirectUri)
    . '&scope=' . urlencode($scope)
    . '&access_type=offline'
    . '&prompt=consent'
    . '&state=' . urlencode($service);

header('Location: ' . $authUrl);
exit;
