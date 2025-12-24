<?php
/*  FILE: feed_helpers.php
    SECTION: Feed helpers (uploads, parsing, CRUD)
------------------------------------------------------------*/

function tb_feed_upload_dir(): string {
    $relative = 'uploads/feed/';
    $absolute = __DIR__ . '/' . $relative;
    if (!is_dir($absolute)) {
        @mkdir($absolute, 0755, true);
    }
    return $relative;
}

function tb_feed_sanitize_filename(string $filename): string {
    $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
    return trim($filename, '_');
}

function tb_feed_store_file(array $file, array $allowedMimes, array $allowedExts): ?string {
    if (empty($file['tmp_name']) || !empty($file['error'])) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
    if ($finfo) {
        finfo_close($finfo);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!$mime || !in_array($mime, $allowedMimes, true) || !in_array($extension, $allowedExts, true)) {
        return null;
    }

    $uploadDir = tb_feed_upload_dir();
    $safeName  = tb_feed_sanitize_filename(pathinfo($file['name'], PATHINFO_FILENAME));
    $filename  = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safeName . '.' . $extension;
    $target    = __DIR__ . '/' . $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $uploadDir . $filename;
    }

    return null;
}

function tb_feed_handle_photo_uploads(?array $files): array {
    if (!$files || empty($files['name']) || !is_array($files['name'])) {
        return [];
    }

    $paths = [];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    foreach ($files['name'] as $idx => $name) {
        $file = [
            'name'     => $name,
            'type'     => $files['type'][$idx] ?? '',
            'tmp_name' => $files['tmp_name'][$idx] ?? '',
            'error'    => $files['error'][$idx] ?? 0,
            'size'     => $files['size'][$idx] ?? 0,
        ];
        $stored = tb_feed_store_file($file, $allowedMimes, $allowedExts);
        if ($stored) {
            $paths[] = $stored;
        }
    }

    return $paths;
}

function tb_feed_handle_video_upload(?array $file): ?string {
    if (!$file) {
        return null;
    }

    $allowedMimes = ['video/mp4', 'video/quicktime'];
    $allowedExts  = ['mp4', 'mov'];

    return tb_feed_store_file($file, $allowedMimes, $allowedExts);
}

function tb_feed_parse_youtube_id(string $url): ?string {
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/))([\w\-]{11})~', $url, $m)) {
        return $m[1];
    }
    return null;
}

function tb_feed_delete_post(PDO $pdo, int $postId): void {
    $mediaStmt = $pdo->prepare("SELECT file_path FROM tb_feed_media WHERE post_id = ?");
    $mediaStmt->execute([$postId]);
    $mediaFiles = $mediaStmt->fetchAll(PDO::FETCH_COLUMN);

    $videoStmt = $pdo->prepare("SELECT video_path FROM tb_feed_posts WHERE id = ?");
    $videoStmt->execute([$postId]);
    $videoPath = $videoStmt->fetchColumn();

    $pdo->prepare("DELETE FROM tb_feed_posts WHERE id = ?")->execute([$postId]);

    foreach ($mediaFiles as $filePath) {
        $absolute = __DIR__ . '/' . ltrim($filePath, '/');
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    if ($videoPath) {
        $absolute = __DIR__ . '/' . ltrim($videoPath, '/');
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
}
