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
