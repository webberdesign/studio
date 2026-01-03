<?php
/*  PAGE NAME: user_session.php
    SECTION: User Device Unlock
------------------------------------------------------------*/
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/user_helpers.php';

header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);
if (!is_array($payload)) {
    $payload = [];
}

$action = $payload['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';
$deviceToken = trim((string)($payload['device_token'] ?? $_POST['device_token'] ?? ''));

function tb_json_response(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($action === '') {
    tb_json_response(['success' => false, 'message' => 'Missing action.'], 400);
}

if ($deviceToken === '') {
    tb_json_response(['success' => false, 'message' => 'Missing device token.'], 400);
}

if ($action === 'validate') {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.name, u.icon_path
         FROM tb_user_devices d
         JOIN tb_users u ON u.id = d.user_id
         WHERE d.device_token = ?
         LIMIT 1"
    );
    $stmt->execute([$deviceToken]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        tb_json_response(['success' => false, 'message' => 'Device not registered.'], 404);
    }

    setcookie('tb_device_token', $deviceToken, [
        'expires' => time() + 60 * 60 * 24 * 365,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    tb_json_response(['success' => true, 'user' => $user]);
}

if ($action === 'register_push') {
    $oneSignalId = trim((string)($payload['onesignal_id'] ?? $_POST['onesignal_id'] ?? ''));
    if ($oneSignalId === '') {
        tb_json_response(['success' => false, 'message' => 'Missing OneSignal ID.'], 400);
    }

    $stmt = $pdo->prepare(
        "SELECT u.id
         FROM tb_user_devices d
         JOIN tb_users u ON u.id = d.user_id
         WHERE d.device_token = ?
         LIMIT 1"
    );
    $stmt->execute([$deviceToken]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        tb_json_response(['success' => false, 'message' => 'Device not registered.'], 404);
    }

    $insertStmt = $pdo->prepare(
        "INSERT INTO tb_user_push_subscriptions (user_id, onesignal_id, created_at, updated_at)
         VALUES (?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), updated_at = NOW()"
    );
    $insertStmt->execute([(int)$user['id'], $oneSignalId]);

    tb_json_response(['success' => true]);
}

if ($action === 'unlock') {
    $pin = preg_replace('/\D+/', '', (string)($payload['pin'] ?? $_POST['pin'] ?? ''));
    if (strlen($pin) !== 6) {
        tb_json_response(['success' => false, 'message' => 'Invite code must be 6 digits.'], 400);
    }

    $userStmt = $pdo->prepare("SELECT id, name, icon_path FROM tb_users WHERE unlock_pin = ? LIMIT 1");
    $userStmt->execute([$pin]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        tb_json_response(['success' => false, 'message' => 'Invite code not recognized.'], 404);
    }

    $deviceStmt = $pdo->prepare("SELECT user_id FROM tb_user_devices WHERE device_token = ? LIMIT 1");
    $deviceStmt->execute([$deviceToken]);
    $deviceOwner = $deviceStmt->fetch(PDO::FETCH_ASSOC);
    if ($deviceOwner && (int)$deviceOwner['user_id'] !== (int)$user['id']) {
        tb_json_response(['success' => false, 'message' => 'This device is already linked to another account.'], 409);
    }

    if (!$deviceOwner) {
        $insertStmt = $pdo->prepare("INSERT INTO tb_user_devices (user_id, device_token) VALUES (?, ?)");
        $insertStmt->execute([$user['id'], $deviceToken]);
    }

    setcookie('tb_device_token', $deviceToken, [
        'expires' => time() + 60 * 60 * 24 * 365,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    tb_json_response(['success' => true, 'user' => $user]);
}

tb_json_response(['success' => false, 'message' => 'Unknown action.'], 400);
