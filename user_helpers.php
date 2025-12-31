<?php
/*  PAGE NAME: user_helpers.php
    SECTION: User + Admin Identity Helpers
------------------------------------------------------------*/

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

if (!function_exists('tb_get_effective_settings')) {
    function tb_get_effective_settings(PDO $pdo, ?array $user = null): array {
        $settings = tb_get_settings();
        if (!$user) {
            $user = tb_get_current_user($pdo);
        }
        if (!$user) {
            return $settings;
        }
        $stmt = $pdo->prepare("SELECT theme, show_spotify, show_apple FROM tb_users WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$user['id']]);
        $userSettings = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$userSettings) {
            return $settings;
        }
        if (!empty($userSettings['theme'])) {
            $settings['theme'] = $userSettings['theme'] === 'light' ? 'light' : 'dark';
        }
        if ($userSettings['show_spotify'] !== null) {
            $settings['show_spotify'] = (bool)$userSettings['show_spotify'];
        }
        if ($userSettings['show_apple'] !== null) {
            $settings['show_apple'] = (bool)$userSettings['show_apple'];
        }
        return $settings;
    }
}

if (!function_exists('tb_update_user_settings')) {
    function tb_update_user_settings(PDO $pdo, int $userId, array $updates): void {
        $theme = $updates['theme'] ?? null;
        $showSpotify = array_key_exists('show_spotify', $updates) ? (int)(bool)$updates['show_spotify'] : null;
        $showApple = array_key_exists('show_apple', $updates) ? (int)(bool)$updates['show_apple'] : null;
        $stmt = $pdo->prepare(
            "UPDATE tb_users
             SET theme = ?, show_spotify = ?, show_apple = ?
             WHERE id = ?"
        );
        $stmt->execute([
            $theme !== null ? (($theme === 'light') ? 'light' : 'dark') : null,
            $showSpotify,
            $showApple,
            $userId,
        ]);
    }
}
