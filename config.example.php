<?php
/*  PAGE NAME: config.php
    SECTION: DB + App Config
------------------------------------------------------------*/

session_start();

$TB_DB_HOST = 'localhost';
$TB_DB_NAME = 'xx';
$TB_DB_USER = 'xx';
$TB_DB_PASS = 'xx';

try {
    $pdo = new PDO(
        "mysql:host={$TB_DB_HOST};dbname={$TB_DB_NAME};charset=utf8mb4",
        $TB_DB_USER,
        $TB_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('DB Connection failed');
}

define('GA_API_KEY', 'your-ga-api-key-here');
define('GA_PROPERTY_ID', 'your-ga4-property-id-here');
define('GA_CLIENT_ID', 'your-google-oauth-client-id-here');
define('GA_CLIENT_SECRET', 'your-google-oauth-client-secret-here');
define('GA_REFRESH_TOKEN', 'your-google-oauth-refresh-token-here');
define('GA_OAUTH_REDIRECT_URI', 'https://your-domain.com/analytics/oauth2callback.php');

define('YOUTUBE_API_KEY', 'your-youtube-api-key-here');
define('YOUTUBE_CHANNEL_ID', 'your-youtube-channel-id-here');
define('YOUTUBE_CLIENT_ID', 'your-youtube-oauth-client-id-here');
define('YOUTUBE_CLIENT_SECRET', 'your-youtube-oauth-client-secret-here');
define('YOUTUBE_REFRESH_TOKEN', 'your-youtube-oauth-refresh-token-here');
define('YOUTUBE_OAUTH_REDIRECT_URI', 'https://your-domain.com/analytics/oauth2callback.php');

define('ONESIGNAL_APP_ID', 'your-onesignal-app-id-here');
define('ONESIGNAL_REST_API_KEY', 'your-onesignal-rest-api-key-here');

function tb_is_admin() {
    return !empty($_SESSION['tb_admin']);
}

/**
 * Load application settings from the settings.json file.  In addition to
 * the theme, this file may contain flags controlling the visibility of
 * third‑party service buttons (e.g. Apple Music, Spotify).  Defaults are
 * provided for missing values.  The structure of the settings file is
 * expected to be a simple JSON object, e.g. {"theme":"dark",
 * "show_spotify":true,"show_apple":true}.
 *
 * @return array associative array of settings with keys:
 *               - theme: 'light' or 'dark'
 *               - show_spotify: bool
 *               - show_apple: bool
 */
function tb_get_settings(): array {
    $settingsFile = __DIR__ . '/settings.json';
    // Define defaults
    $defaults = [
        'theme'        => 'dark',
        'show_spotify' => true,
        'show_apple'   => true,
    ];
    if (file_exists($settingsFile)) {
        $json = @file_get_contents($settingsFile);
        if ($json !== false) {
            $data = @json_decode($json, true);
            if (is_array($data)) {
                foreach ($defaults as $key => $val) {
                    if (array_key_exists($key, $data)) {
                        $defaults[$key] = $data[$key];
                    }
                }
            }
        }
    }
    // Normalize boolean values
    $defaults['show_spotify'] = !empty($defaults['show_spotify']);
    $defaults['show_apple']   = !empty($defaults['show_apple']);
    // Normalize theme string
    $defaults['theme'] = ($defaults['theme'] === 'light') ? 'light' : 'dark';
    return $defaults;
}

/**
 * Retrieve just the current theme from settings.  Provided for
 * backwards compatibility.  Defaults to dark mode if settings file
 * cannot be parsed or theme is missing.
 *
 * @return string 'light' or 'dark'
 */
function tb_get_theme(): string {
    $settings = tb_get_settings();
    return ($settings['theme'] === 'light') ? 'light' : 'dark';
}

/**
 * Persist updates to the settings.json file.  Only keys present in the
 * provided $updates array will be changed; all other existing settings
 * are preserved.  Accepts keys 'theme', 'show_spotify', 'show_apple',
 * and 'show_apple'.
 *
 * @param array $updates
 * @return void
 */
function tb_set_settings(array $updates): void {
    $settingsFile = __DIR__ . '/settings.json';
    $current = tb_get_settings();
    foreach ($updates as $key => $val) {
        if ($key === 'theme') {
            $current['theme'] = ($val === 'light') ? 'light' : 'dark';
        } elseif ($key === 'show_spotify') {
            $current['show_spotify'] = (bool)$val;
        } elseif ($key === 'show_apple') {
            $current['show_apple'] = (bool)$val;
        }
    }
    @file_put_contents($settingsFile, json_encode($current));
}

/**
 * Persist the selected theme to settings.json. Expects 'light' or 'dark'.
 * Uses the unified settings setter.
 *
 * @param string $theme
 * @return void
 */
function tb_set_theme(string $theme): void {
    tb_set_settings(['theme' => $theme]);
}

function tb_require_admin() {
    if (!tb_is_admin()) {
        header('Location: admin.php');
        exit;
    }
}

if (!function_exists('tb_get_admin_display_name')) {
    function tb_get_admin_display_name(PDO $pdo): ?string {
        if (!tb_is_admin()) {
            return null;
        }
        $username = $_SESSION['tb_admin'] ?? '';
        if ($username === '') {
            return null;
        }
        $stmt = $pdo->prepare("SELECT display_name, username FROM tb_admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$admin) {
            return null;
        }
        return $admin['display_name'] ?: $admin['username'];
    }
}

if (!function_exists('tb_get_current_user')) {
    function tb_get_current_user(PDO $pdo): ?array {
        $token = $_COOKIE['tb_device_token'] ?? '';
        if ($token === '') {
            return null;
        }
        $stmt = $pdo->prepare(
            "SELECT u.id, u.name, u.icon_path
             FROM tb_user_devices d
             JOIN tb_users u ON u.id = d.user_id
             WHERE d.device_token = ?
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }
}

if (!function_exists('tb_get_comment_author')) {
    function tb_get_comment_author(PDO $pdo): ?string {
        $adminName = tb_get_admin_display_name($pdo);
        if ($adminName) {
            return $adminName;
        }
        $user = tb_get_current_user($pdo);
        if ($user && !empty($user['name'])) {
            return $user['name'];
        }
        return null;
    }
}

?>
