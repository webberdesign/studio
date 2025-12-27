<?php
/*  FILE: analytics/oauth2callback.php
    SECTION: Google OAuth Callback (YouTube/GA)
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';

$service = $_GET['state'] ?? 'youtube';
$service = in_array($service, ['youtube', 'ga'], true) ? $service : 'youtube';

if ($service === 'ga') {
    $clientId = getenv('GA_CLIENT_ID') ?: (defined('GA_CLIENT_ID') ? GA_CLIENT_ID : null);
    $clientSecret = getenv('GA_CLIENT_SECRET') ?: (defined('GA_CLIENT_SECRET') ? GA_CLIENT_SECRET : null);
    $redirectUri = getenv('GA_OAUTH_REDIRECT_URI')
        ?: (defined('GA_OAUTH_REDIRECT_URI') ? GA_OAUTH_REDIRECT_URI : null);
} else {
    $clientId = getenv('YOUTUBE_CLIENT_ID') ?: (defined('YOUTUBE_CLIENT_ID') ? YOUTUBE_CLIENT_ID : null);
    $clientSecret = getenv('YOUTUBE_CLIENT_SECRET') ?: (defined('YOUTUBE_CLIENT_SECRET') ? YOUTUBE_CLIENT_SECRET : null);
    $redirectUri = getenv('YOUTUBE_OAUTH_REDIRECT_URI')
        ?: (defined('YOUTUBE_OAUTH_REDIRECT_URI') ? YOUTUBE_OAUTH_REDIRECT_URI : null);
}

if (!$clientId || !$clientSecret || !$redirectUri) {
    echo 'Missing OAuth client configuration. Set client ID, secret, and redirect URI.';
    exit;
}

if (!isset($_GET['code'])) {
    echo 'Missing authorization code.';
    exit;
}

$postFields = http_build_query([
    'code' => $_GET['code'],
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code',
]);

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);
$response = curl_exec($ch);
if ($response === false) {
    echo 'OAuth token request failed: ' . htmlspecialchars(curl_error($ch));
    curl_close($ch);
    exit;
}
curl_close($ch);

$data = json_decode($response, true);
if (!isset($data['access_token'])) {
    echo 'Unable to retrieve access token.';
    echo '<pre>' . htmlspecialchars($response) . '</pre>';
    exit;
}

$refreshToken = $data['refresh_token'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OAuth Tokens</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; padding: 24px; }
        code, pre { background: #f5f5f5; padding: 12px; display: block; }
    </style>
</head>
<body>
    <h1>OAuth Tokens</h1>
    <p>Store the refresh token in your configuration for long-lived access.</p>
    <?php if ($refreshToken): ?>
        <h2>Refresh Token</h2>
        <code><?php echo htmlspecialchars($refreshToken); ?></code>
    <?php else: ?>
        <p>No refresh token was returned. Ensure you used access_type=offline and prompt=consent.</p>
    <?php endif; ?>
    <h2>Access Token</h2>
    <pre><?php echo htmlspecialchars($data['access_token']); ?></pre>
    <p>Expires in: <?php echo htmlspecialchars((string) ($data['expires_in'] ?? 'unknown')); ?> seconds.</p>
</body>
</html>
