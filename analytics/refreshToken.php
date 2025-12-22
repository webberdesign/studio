<?php
/*  FILE: analytics/refreshToken.php
    SECTION: OAuth refresh helper for YouTube Analytics
------------------------------------------------------------*/

/**
 * Refreshes an OAuth access token for the YouTube Analytics API.
 *
 * We avoid storing secrets in the codebase.  Instead, provide the following
 * environment variables (or define them in your config.php) at runtime:
 *   - YOUTUBE_REFRESH_TOKEN
 *   - YOUTUBE_CLIENT_ID
 *   - YOUTUBE_CLIENT_SECRET
 *
 * The refreshed access token is cached per request so multiple Analytics
 * calls on the same page do not repeatedly hit Google's token endpoint.
 *
 * @param int $userId Unused placeholder for future multi‑user support.
 * @return string OAuth access token or an empty string on failure.
 */
function refreshToken($userId) {
    static $cachedToken = null;

    // Reuse the token if we already fetched it during this request and it
    // has not expired (add a 60‑second buffer before expiry).
    if ($cachedToken && isset($cachedToken['expires_at']) && $cachedToken['expires_at'] > time() + 60) {
        return $cachedToken['access_token'];
    }

    // Prefer environment variables, but also allow constants from config.php.
    $refreshToken = getenv('YOUTUBE_REFRESH_TOKEN') ?: (defined('YOUTUBE_REFRESH_TOKEN') ? YOUTUBE_REFRESH_TOKEN : null);
    $clientId     = getenv('YOUTUBE_CLIENT_ID')     ?: (defined('YOUTUBE_CLIENT_ID') ? YOUTUBE_CLIENT_ID : null);
    $clientSecret = getenv('YOUTUBE_CLIENT_SECRET') ?: (defined('YOUTUBE_CLIENT_SECRET') ? YOUTUBE_CLIENT_SECRET : null);

    if (!$refreshToken || !$clientId || !$clientSecret) {
        error_log('YouTube refresh token not configured. Set YOUTUBE_REFRESH_TOKEN, YOUTUBE_CLIENT_ID, and YOUTUBE_CLIENT_SECRET.');
        return '';
    }

    $postFields = http_build_query([
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type'    => 'refresh_token',
    ]);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        error_log('YouTube refresh token request failed: ' . curl_error($ch));
        curl_close($ch);
        return '';
    }

    $data = json_decode($response, true);
    curl_close($ch);

    if (!isset($data['access_token'], $data['expires_in'])) {
        $message = isset($data['error_description']) ? $data['error_description'] : json_encode($data);
        error_log('YouTube refresh token response missing access token: ' . $message);
        return '';
    }

    $cachedToken = [
        'access_token' => $data['access_token'],
        'expires_at'   => time() + (int) $data['expires_in'],
    ];

    return $cachedToken['access_token'];
}
