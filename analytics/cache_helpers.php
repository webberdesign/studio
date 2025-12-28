<?php
/*  FILE: analytics/cache_helpers.php
    SECTION: Simple file cache helpers for analytics pages
------------------------------------------------------------*/

declare(strict_types=1);

function tb_cache_directory(): string {
    $baseDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'tb_analytics_cache';
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0755, true);
    }
    return $baseDir;
}

function tb_cache_path(string $key): string {
    return tb_cache_directory() . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_\-]/i', '_', $key) . '.html';
}

function tb_cache_read(string $key, int $ttlSeconds): ?string {
    $path = tb_cache_path($key);
    if (!file_exists($path)) {
        return null;
    }
    $age = time() - filemtime($path);
    if ($age > $ttlSeconds) {
        return null;
    }
    $contents = file_get_contents($path);
    return $contents === false ? null : $contents;
}

function tb_cache_write(string $key, string $value): void {
    $path = tb_cache_path($key);
    file_put_contents($path, $value);
}
