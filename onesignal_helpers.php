<?php
/*  PAGE NAME: onesignal_helpers.php
    SECTION: OneSignal Web Push Helpers
------------------------------------------------------------*/

if (!function_exists('tb_get_onesignal_config')) {
    function tb_get_onesignal_config(): array {
        $appId = defined('ONESIGNAL_APP_ID') ? ONESIGNAL_APP_ID : getenv('ONESIGNAL_APP_ID');
        $apiKey = defined('ONESIGNAL_REST_API_KEY') ? ONESIGNAL_REST_API_KEY : getenv('ONESIGNAL_REST_API_KEY');
        return [
            'app_id' => trim((string)$appId),
            'rest_api_key' => trim((string)$apiKey),
        ];
    }
}

if (!function_exists('tb_build_app_url')) {
    function tb_build_app_url(string $path = ''): ?string {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host === '') {
            return null;
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = $scheme . '://' . $host;
        if ($path === '') {
            return $base;
        }
        if ($path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }
        return $base . $path;
    }
}

if (!function_exists('tb_get_push_recipient_ids')) {
    function tb_get_push_recipient_ids(PDO $pdo, ?int $excludeUserId = null): array {
        if ($excludeUserId !== null) {
            $stmt = $pdo->prepare(
                "SELECT onesignal_id
                 FROM tb_user_push_subscriptions
                 WHERE onesignal_id IS NOT NULL
                   AND user_id != ?
                 ORDER BY id ASC"
            );
            $stmt->execute([$excludeUserId]);
        } else {
            $stmt = $pdo->query(
                "SELECT onesignal_id
                 FROM tb_user_push_subscriptions
                 WHERE onesignal_id IS NOT NULL
                 ORDER BY id ASC"
            );
        }
        return array_values(array_filter(array_map(
            static fn($row) => $row['onesignal_id'] ?? null,
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        )));
    }
}

if (!function_exists('tb_send_onesignal_notification')) {
    function tb_send_onesignal_notification(
        PDO $pdo,
        string $title,
        string $message,
        ?int $excludeUserId = null,
        array $options = []
    ): bool {
        $config = tb_get_onesignal_config();
        if ($config['app_id'] === '' || $config['rest_api_key'] === '') {
            return false;
        }
        $recipientIds = tb_get_push_recipient_ids($pdo, $excludeUserId);
        if (empty($recipientIds)) {
            return false;
        }

        $payload = [
            'app_id' => $config['app_id'],
            'include_player_ids' => $recipientIds,
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
        ];

        if (!empty($options['url'])) {
            $payload['url'] = $options['url'];
        }

        $ch = curl_init('https://onesignal.com/api/v1/notifications');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Basic ' . $config['rest_api_key'],
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }
}

if (!function_exists('tb_notify_comment')) {
    function tb_notify_comment(
        PDO $pdo,
        string $contextLabel,
        string $authorName,
        ?int $authorUserId = null,
        ?string $relativeUrl = null
    ): void {
        $title = 'New comment';
        $message = sprintf('%s commented on %s.', $authorName !== '' ? $authorName : 'Someone', $contextLabel);
        $url = $relativeUrl ? tb_build_app_url($relativeUrl) : null;
        tb_send_onesignal_notification($pdo, $title, $message, $authorUserId, ['url' => $url]);
    }
}

if (!function_exists('tb_notify_unreleased_video')) {
    function tb_notify_unreleased_video(PDO $pdo, string $videoTitle): void {
        $title = 'New unreleased video';
        $message = sprintf('New unreleased video posted: %s.', $videoTitle);
        $url = tb_build_app_url('/?page=videos');
        tb_send_onesignal_notification($pdo, $title, $message, null, ['url' => $url]);
    }
}

