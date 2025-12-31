<?php
/*  PAGE NAME: admin.php
    SECTION: Admin & Settings Panel
------------------------------------------------------------*/
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/feed_helpers.php';

// Determine which tab is selected
$tab = $_GET['tab'] ?? 'videos';
$validTabs = ['videos', 'songs', 'collections', 'feed', 'users', 'app', 'settings'];
if (!in_array($tab, $validTabs, true)) {
    $tab = 'videos';
}

// Current settings for styling and feature flags
$settings = tb_get_settings();
$currentTheme = $settings['theme'];
$showSpotify = !empty($settings['show_spotify']);
$showApple   = !empty($settings['show_apple']);
$adminDisplayName = tb_get_admin_display_name($pdo);

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM tb_admin_users WHERE username = ?");
    $stmt->execute([$u]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && hash('sha256', $p) === $user['password_hash']) {
        $_SESSION['tb_admin'] = $user['username'];
        header('Location: admin.php');
        exit;
    }
    $loginError = 'Invalid login';
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['tb_admin']);
    header('Location: admin.php');
    exit;
}

// Helper to compute next position for a table
function tb_next_position(PDO $pdo, string $table): int {
    $stmt = $pdo->query("SELECT MAX(position) AS max_pos FROM {$table}");
    $maxPos = $stmt->fetch(PDO::FETCH_ASSOC)['max_pos'];
    return ($maxPos !== null) ? ((int)$maxPos + 1) : 0;
}

function tb_generate_invite_pin(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function tb_handle_user_icon_upload(array $file): ?string {
    if (empty($file['tmp_name'])) {
        return null;
    }
    $uploadDir = 'uploads/user_icons/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    $filename = time() . '_' . basename($file['name']);
    $uploadPath = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return $uploadPath;
    }
    return null;
}

// Handle CRUD if logged in and not login form
if (tb_is_admin() && $_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['login'])) {
    // Update existing video (from inline edit on public page)
    if (isset($_POST['update_video']) && !empty($_POST['id'])) {
        $videoId = (int)($_POST['id']);
        $title   = trim($_POST['title'] ?? '');
        $url     = trim($_POST['youtube_url'] ?? '');
        $released = isset($_POST['released']) ? 1 : 0;
        if ($title && $url) {
            $stmt = $pdo->prepare("UPDATE tb_videos SET title = ?, youtube_url = ?, is_released = ? WHERE id = ?");
            $stmt->execute([$title, $url, $released, $videoId]);
        }
    }

    // Update existing song (from inline edit on public page)
    if (isset($_POST['update_song']) && !empty($_POST['id'])) {
        $songId = (int)($_POST['id']);
        $title  = trim($_POST['title'] ?? '');
        $mp3    = trim($_POST['mp3_path'] ?? '');
        // Determine initial cover path from hidden input
        $cover  = trim($_POST['cover_path'] ?? '');
        // Process uploaded cover if provided
        if (!empty($_FILES['cover_upload']['tmp_name'])) {
            $uploadDir  = 'uploads/';
            // ensure uploads directory exists
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $uploadPath = $uploadDir . time() . '_' . basename($_FILES['cover_upload']['name']);
            if (move_uploaded_file($_FILES['cover_upload']['tmp_name'], $uploadPath)) {
                $cover = $uploadPath;
            }
        }
        $apple = trim($_POST['apple_music_url'] ?? '');
        $spot  = trim($_POST['spotify_url'] ?? '');
        $released = isset($_POST['released']) ? 1 : 0;
        $collectionId = trim($_POST['collection_id'] ?? '');
        if ($title) {
            // Build update statement; use parameter binding for nulls
            $stmt = $pdo->prepare("UPDATE tb_songs SET title = ?, mp3_path = ?, cover_path = ?, apple_music_url = ?, spotify_url = ?, is_released = ?, collection_id = ? WHERE id = ?");
            $stmt->execute([
                $title,
                ($mp3 !== '' ? $mp3 : null),
                ($cover !== '' ? $cover : null),
                ($apple !== '' ? $apple : null),
                ($spot  !== '' ? $spot  : null),
                $released,
                $collectionId !== '' ? (int)$collectionId : null,
                $songId
            ]);
        }
    }
    // Add video
    if (isset($_POST['add_video'])) {
        $title = trim($_POST['title'] ?? '');
        $url   = trim($_POST['youtube_url'] ?? '');
        $released = isset($_POST['released']) ? 1 : 0;
        if ($title && $url) {
            $pos = tb_next_position($pdo, 'tb_videos');
            $stmt = $pdo->prepare("INSERT INTO tb_videos (title, youtube_url, is_released, position) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $url, $released, $pos]);
        }
    }
    // Delete video
    // When delete_video is set, its value holds the ID; use that instead of relying on separate 'id' field
    if (isset($_POST['delete_video']) && !empty($_POST['delete_video'])) {
        $videoIdToDelete = (int)$_POST['delete_video'];
        $stmt = $pdo->prepare("DELETE FROM tb_videos WHERE id = ?");
        $stmt->execute([$videoIdToDelete]);
    }
    // Save video order and release states
    if (isset($_POST['save_order_videos']) && isset($_POST['order'])) {
        $order = array_filter(explode(',', $_POST['order']), function($id) { return $id !== ''; });
        $pos = 0;
        $updatePos    = $pdo->prepare("UPDATE tb_videos SET position = ? WHERE id = ?");
        $updateRelease = $pdo->prepare("UPDATE tb_videos SET is_released = ? WHERE id = ?");
        foreach ($order as $id) {
            $updatePos->execute([$pos++, $id]);
            $isReleased = isset($_POST['released_state'][$id]) ? 1 : 0;
            $updateRelease->execute([$isReleased, $id]);
        }
    }

    // Add song
    if (isset($_POST['add_song'])) {
        $title = trim($_POST['title'] ?? '');
        $mp3   = trim($_POST['mp3_path'] ?? '');
        $cover = trim($_POST['cover_path'] ?? '');
        $apple = trim($_POST['apple_music_url'] ?? '');
        $spot  = trim($_POST['spotify_url'] ?? '');
        $released = isset($_POST['released']) ? 1 : 0;
        $collectionId = trim($_POST['collection_id'] ?? '');
        if ($title) {
            $pos = tb_next_position($pdo, 'tb_songs');
            $stmt = $pdo->prepare("INSERT INTO tb_songs (title, mp3_path, cover_path, apple_music_url, spotify_url, is_released, position, collection_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $title,
                $mp3 !== '' ? $mp3 : null,
                $cover !== '' ? $cover : null,
                $apple !== '' ? $apple : null,
                $spot !== '' ? $spot : null,
                $released,
                $pos,
                $collectionId !== '' ? (int)$collectionId : null
            ]);
        }
    }
    // Delete song
    // When delete_song is set, its value holds the ID
    if (isset($_POST['delete_song']) && !empty($_POST['delete_song'])) {
        $songIdToDelete = (int)$_POST['delete_song'];
        $stmt = $pdo->prepare("DELETE FROM tb_songs WHERE id = ?");
        $stmt->execute([$songIdToDelete]);
    }
    // Save song order and release states
    if (isset($_POST['save_order_songs']) && isset($_POST['order'])) {
        $order = array_filter(explode(',', $_POST['order']), function($id) { return $id !== ''; });
        $pos = 0;
        $updatePos    = $pdo->prepare("UPDATE tb_songs SET position = ? WHERE id = ?");
        $updateRelease = $pdo->prepare("UPDATE tb_songs SET is_released = ? WHERE id = ?");
        foreach ($order as $id) {
            $updatePos->execute([$pos++, $id]);
            $isReleased = isset($_POST['released_state'][$id]) ? 1 : 0;
            $updateRelease->execute([$isReleased, $id]);
        }
    }

    // Add collection
    if (isset($_POST['add_collection'])) {
        $name = trim($_POST['name'] ?? '');
        $cover = trim($_POST['cover_path'] ?? '');
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO tb_collections (name, cover_path) VALUES (?, ?)");
            $stmt->execute([$name, $cover !== '' ? $cover : null]);
        }
    }
    // Delete collection
    if (isset($_POST['delete_collection']) && !empty($_POST['id'])) {
        $stmt = $pdo->prepare("DELETE FROM tb_collections WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        // Optionally set collection_id to null for songs in this collection
        $pdo->prepare("UPDATE tb_songs SET collection_id = NULL WHERE collection_id = ?")->execute([$_POST['id']]);
    }

    // Add feed post
    if (isset($_POST['add_feed_post'])) {
        $body       = trim($_POST['body'] ?? '');
        $youtubeUrl = trim($_POST['youtube_url'] ?? '');
        if ($body !== '') {
            $photoPaths = tb_feed_handle_photo_uploads($_FILES['photos'] ?? null);
            $videoPath  = tb_feed_handle_video_upload($_FILES['video_upload'] ?? null);

            $stmt = $pdo->prepare("INSERT INTO tb_feed_posts (author_name, body, youtube_url, video_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $adminDisplayName ?: 'Admin',
                $body,
                $youtubeUrl !== '' ? $youtubeUrl : null,
                $videoPath,
            ]);

            $postId = (int) $pdo->lastInsertId();
            if (!empty($photoPaths)) {
                $mediaStmt = $pdo->prepare("INSERT INTO tb_feed_media (post_id, file_path, media_type) VALUES (?, ?, 'image')");
                foreach ($photoPaths as $path) {
                    $mediaStmt->execute([$postId, $path]);
                }
            }
        }
    }

    // Delete feed post
    if (isset($_POST['delete_feed_post']) && !empty($_POST['delete_feed_post'])) {
        tb_feed_delete_post($pdo, (int) $_POST['delete_feed_post']);
    }

    // Delete feed comment
    if (isset($_POST['delete_feed_comment']) && !empty($_POST['delete_feed_comment'])) {
        $commentId = (int) $_POST['delete_feed_comment'];
        $stmt = $pdo->prepare("DELETE FROM tb_feed_comments WHERE id = ?");
        $stmt->execute([$commentId]);
    }

    // Update settings (theme and service visibility)
    if (isset($_POST['update_theme'])) {
        $newTheme = $_POST['theme'] ?? 'dark';
        $showSpotifyFlag = isset($_POST['show_spotify']) ? 1 : 0;
        $showAppleFlag   = isset($_POST['show_apple']) ? 1 : 0;
        // Persist theme and service flags
        tb_set_settings([
            'theme'        => ($newTheme === 'light') ? 'light' : 'dark',
            'show_spotify' => $showSpotifyFlag,
            'show_apple'   => $showAppleFlag
        ]);
        // Refresh in-memory settings
        $settings     = tb_get_settings();
        $currentTheme = $settings['theme'];
        $showSpotify  = !empty($settings['show_spotify']);
        $showApple    = !empty($settings['show_apple']);
    }

    if (isset($_POST['update_admin_profile']) && tb_is_admin()) {
        $displayName = trim($_POST['display_name'] ?? '');
        $stmt = $pdo->prepare("UPDATE tb_admin_users SET display_name = ? WHERE username = ?");
        $stmt->execute([$displayName !== '' ? $displayName : null, $_SESSION['tb_admin']]);
        $adminDisplayName = tb_get_admin_display_name($pdo);
    }

    if (isset($_POST['add_user'])) {
        $name = trim($_POST['name'] ?? '');
        $pinInput = preg_replace('/\D+/', '', (string)($_POST['unlock_pin'] ?? ''));
        $pin = strlen($pinInput) === 6 ? $pinInput : '';
        if ($name !== '') {
            if ($pin === '') {
                $pin = tb_generate_invite_pin();
            }
            $attempts = 0;
            while ($attempts < 5) {
                $pinCheck = $pdo->prepare("SELECT id FROM tb_users WHERE unlock_pin = ? LIMIT 1");
                $pinCheck->execute([$pin]);
                if (!$pinCheck->fetch(PDO::FETCH_ASSOC)) {
                    break;
                }
                $pin = tb_generate_invite_pin();
                $attempts++;
            }
            $pinCheck = $pdo->prepare("SELECT id FROM tb_users WHERE unlock_pin = ? LIMIT 1");
            $pinCheck->execute([$pin]);
            if ($pinCheck->fetch(PDO::FETCH_ASSOC)) {
                $pin = '';
            }
            $iconPath = tb_handle_user_icon_upload($_FILES['icon_upload'] ?? []);
            if ($pin !== '') {
                $stmt = $pdo->prepare("INSERT INTO tb_users (name, icon_path, unlock_pin) VALUES (?, ?, ?)");
                $stmt->execute([$name, $iconPath, $pin]);
            }
        }
    }

    if (isset($_POST['delete_user']) && !empty($_POST['delete_user'])) {
        $userId = (int) $_POST['delete_user'];
        $stmt = $pdo->prepare("DELETE FROM tb_users WHERE id = ?");
        $stmt->execute([$userId]);
    }

    // Redirect back to avoid form resubmission
    header('Location: admin.php?tab=' . urlencode($tab));
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Titty Bingo Studio Settings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="public/css/app.css">
    <meta name="theme-color" content="<?php echo ($currentTheme === 'light') ? '#ffffff' : '#0f172a'; ?>">
    <!-- Include SortableJS for drag-and-drop -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
</head>
<body class="tb-body tb-body--admin <?php echo ($currentTheme === 'light') ? 'tb-theme-light' : ''; ?>">
<div class="tb-admin-shell">
    <header class="tb-admin-header">
        <a href="index.php" class="tb-admin-back"><i class="fas fa-arrow-left"></i></a>
        <h1>Titty Bingo Studio Admin</h1>
        <?php if (tb_is_admin()): ?>
            <a href="?logout=1" class="tb-admin-logout"><i class="fas fa-right-from-bracket"></i></a>
        <?php endif; ?>
    </header>

    <?php if (!tb_is_admin()): ?>
        <!-- Login Form -->
        <main class="tb-admin-main">
            <form method="post" class="tb-form-card">
                <h2><i class="fas fa-user-gear"></i> Admin Login</h2>
                <?php if (!empty($loginError)): ?>
                    <p class="tb-error"><?php echo htmlspecialchars($loginError); ?></p>
                <?php endif; ?>
                <label>Username
                    <input type="text" name="username" required>
                </label>
                <label>Password
                    <input type="password" name="password" required>
                </label>
                <button type="submit" name="login" class="tb-btn-primary">Log In</button>
            </form>
        </main>
    <?php else: ?>
        <main class="tb-admin-main">
            <div class="tb-toggle-pill tb-admin-tabs">
                <a href="?tab=videos" class="<?php echo ($tab === 'videos') ? 'active' : ''; ?>">Videos</a>
                <a href="?tab=songs" class="<?php echo ($tab === 'songs') ? 'active' : ''; ?>">Music</a>
                <a href="?tab=collections" class="<?php echo ($tab === 'collections') ? 'active' : ''; ?>">Collections</a>
                <a href="?tab=feed" class="<?php echo ($tab === 'feed') ? 'active' : ''; ?>">Feed</a>
                <a href="?tab=users" class="<?php echo ($tab === 'users') ? 'active' : ''; ?>">Users</a>
                <a href="?tab=app" class="<?php echo ($tab === 'app') ? 'active' : ''; ?>">App</a>
                <a href="?tab=settings" class="<?php echo ($tab === 'settings') ? 'active' : ''; ?>">Settings</a>
            </div>

            <?php if ($tab === 'videos'): ?>
                <?php
                $videos = $pdo->query("SELECT * FROM tb_videos ORDER BY position ASC, created_at DESC")
                                  ->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <section class="tb-admin-section">
                    <h2>Videos</h2>
                    <!-- Add / Edit video form -->
                    <form id="videoForm" method="post" class="tb-form-inline">
                        <input type="hidden" name="tab" value="videos">
                        <!-- hidden id for editing mode -->
                        <input type="hidden" name="id" id="videoEditIdHidden" value="">
                        <label>Title
                            <input type="text" name="title" required>
                        </label>
                        <label>YouTube URL
                            <input type="url" name="youtube_url" required>
                        </label>
                        <label class="tb-form-checkbox">Released?
                            <input type="checkbox" name="released" value="1">
                        </label>
                        <button type="submit" name="add_video" id="videoAddBtn" class="tb-btn-primary">
                            <i class="fas fa-plus"></i> Add
                        </button>
                        <button type="submit" name="update_video" id="videoUpdateBtn" class="tb-btn-primary" style="display:none;">
                            Update
                        </button>
                        <button type="button" id="videoCancelEdit" class="tb-btn-secondary" style="display:none;">
                            Cancel
                        </button>
                    </form>

                    <!-- Reorder and toggle release state.  Delete buttons use formaction so they don’t interfere with this form. -->
                    <form id="videoReorderForm" method="post">
                        <input type="hidden" name="tab" value="videos">
                        <input type="hidden" name="order" id="videoOrderInput" value="">
                        <ul class="tb-admin-list" id="videoList">
                            <?php foreach ($videos as $v): ?>
                                <li data-id="<?php echo $v['id']; ?>"
                                    data-title="<?php echo htmlspecialchars($v['title'], ENT_QUOTES); ?>"
                                    data-url="<?php echo htmlspecialchars($v['youtube_url'], ENT_QUOTES); ?>"
                                    data-released="<?php echo $v['is_released']; ?>">
                                    <span class="tb-handle"><i class="fas fa-bars"></i></span>
                                    <a href="#" class="tb-item-title tb-edit-item" data-id="<?php echo $v['id']; ?>" title="Edit">
                                        <?php echo htmlspecialchars($v['title']); ?>
                                    </a>
                                    <label class="tb-form-checkbox" style="margin-left:auto; margin-right:0.5rem;">
                                        Released
                                        <input type="checkbox" name="released_state[<?php echo $v['id']; ?>]" value="1" <?php echo ($v['is_released'] ? 'checked' : ''); ?>>
                                    </label>
                                    <button type="submit" name="delete_video" value="<?php echo $v['id']; ?>" formaction="admin.php?tab=videos" formmethod="post" class="tb-inline-delete-btn"><i class="fas fa-trash"></i></button>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($videos)): ?>
                                <li class="tb-empty">No videos yet.</li>
                            <?php endif; ?>
                        </ul>
                        <?php if (!empty($videos)): ?>
                        <button type="submit" name="save_order_videos" class="tb-btn-primary" style="margin-top:0.75rem;">Save Order</button>
                        <?php endif; ?>
                    </form>
                </section>
            <?php elseif ($tab === 'songs'): ?>
                <?php
                $songs = $pdo->query("SELECT * FROM tb_songs ORDER BY position ASC, created_at DESC")
                                  ->fetchAll(PDO::FETCH_ASSOC);
                // fetch collections for dropdown
                $collections = $pdo->query("SELECT * FROM tb_collections ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <section class="tb-admin-section">
                    <h2>Music</h2>
                    <!-- Add / Edit song form -->
                    <form id="songForm" method="post" enctype="multipart/form-data" class="tb-form-inline">
                        <input type="hidden" name="tab" value="songs">
                        <!-- hidden id for editing mode -->
                        <input type="hidden" name="id" id="songEditIdHidden" value="">
                        <!-- preserve existing cover path when editing -->
                        <input type="hidden" name="cover_path" id="songEditCoverPathHidden" value="">
                        <label>Title
                            <input type="text" name="title" required>
                        </label>
                        <label>Audio Path / URL (MP3 or M4A, optional)
                            <input type="text" name="mp3_path">
                        </label>
                        <label>Cover Image (optional)
                            <input type="file" name="cover_upload" accept="image/*">
                        </label>
                        <label>Apple Music URL (optional)
                            <input type="text" name="apple_music_url">
                        </label>
                        <label>Spotify URL (optional)
                            <input type="text" name="spotify_url">
                        </label>
                        <label>Collection
                            <select name="collection_id" id="songFormCollectionSelect">
                                <option value="">None</option>
                                <?php foreach ($collections as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="tb-form-checkbox">Released?
                            <input type="checkbox" name="released" value="1">
                        </label>
                        <button type="submit" name="add_song" id="songAddBtn" class="tb-btn-primary">
                            <i class="fas fa-plus"></i> Add
                        </button>
                        <button type="submit" name="update_song" id="songUpdateBtn" class="tb-btn-primary" style="display:none;">
                            Update
                        </button>
                        <button type="button" id="songCancelEdit" class="tb-btn-secondary" style="display:none;">
                            Cancel
                        </button>
                    </form>

                    <!-- Reorder and toggle release state for songs -->
                    <form id="songReorderForm" method="post">
                        <input type="hidden" name="tab" value="songs">
                        <input type="hidden" name="order" id="songOrderInput" value="">
                        <ul class="tb-admin-list" id="songList">
                            <?php foreach ($songs as $s): ?>
                                <li data-id="<?php echo $s['id']; ?>"
                                    data-title="<?php echo htmlspecialchars($s['title'], ENT_QUOTES); ?>"
                                    data-mp3="<?php echo htmlspecialchars($s['mp3_path'] ?? '', ENT_QUOTES); ?>"
                                    data-apple="<?php echo htmlspecialchars($s['apple_music_url'] ?? '', ENT_QUOTES); ?>"
                                    data-spotify="<?php echo htmlspecialchars($s['spotify_url'] ?? '', ENT_QUOTES); ?>"
                                    data-released="<?php echo $s['is_released']; ?>"
                                    data-collection="<?php echo htmlspecialchars($s['collection_id'] ?? '', ENT_QUOTES); ?>"
                                    data-cover="<?php echo htmlspecialchars($s['cover_path'] ?? '', ENT_QUOTES); ?>">
                                    <span class="tb-handle"><i class="fas fa-bars"></i></span>
                                    <a href="#" class="tb-item-title tb-edit-item" data-id="<?php echo $s['id']; ?>" title="Edit">
                                        <?php echo htmlspecialchars($s['title']); ?>
                                    </a>
                                    <label class="tb-form-checkbox" style="margin-left:auto; margin-right:0.5rem;">
                                        Released
                                        <input type="checkbox" name="released_state[<?php echo $s['id']; ?>]" value="1" <?php echo ($s['is_released'] ? 'checked' : ''); ?>>
                                    </label>
                                    <button type="submit" name="delete_song" value="<?php echo $s['id']; ?>" formaction="admin.php?tab=songs" formmethod="post" class="tb-inline-delete-btn"><i class="fas fa-trash"></i></button>
                                </li>
                            <?php endforeach; ?>
                            <?php if (empty($songs)): ?>
                                <li class="tb-empty">No music yet.</li>
                            <?php endif; ?>
                        </ul>
                        <?php if (!empty($songs)): ?>
                        <button type="submit" name="save_order_songs" class="tb-btn-primary" style="margin-top:0.75rem;">Save Order</button>
                        <?php endif; ?>
                    </form>
                </section>
            <?php elseif ($tab === 'collections'): ?>
                <?php
                $collections = $pdo->query("SELECT * FROM tb_collections ORDER BY name ASC")
                                   ->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <section class="tb-admin-section">
                    <h2>Collections</h2>
                    <form method="post" class="tb-form-inline">
                        <input type="hidden" name="tab" value="collections">
                        <label>Name
                            <input type="text" name="name" required>
                        </label>
                        <label>Cover Image (optional)
                            <input type="text" name="cover_path">
                        </label>
                        <button type="submit" name="add_collection" class="tb-btn-primary">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </form>
                    <ul class="tb-admin-list">
                        <?php foreach ($collections as $c): ?>
                            <li>
                                <span><?php echo htmlspecialchars($c['name']); ?></span>
                                <form method="post" class="tb-inline-delete">
                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                    <input type="hidden" name="tab" value="collections">
                                    <button type="submit" name="delete_collection"><i class="fas fa-trash"></i></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($collections)): ?>
                            <li class="tb-empty">No collections yet.</li>
                        <?php endif; ?>
                    </ul>
                </section>
            <?php elseif ($tab === 'settings'): ?>
                <section class="tb-admin-section">
                    <h2>Settings</h2>
                    <form method="post" class="tb-form-inline">
                        <input type="hidden" name="tab" value="settings">
                        <label>Select Theme
                            <select name="theme">
                                <option value="dark" <?php echo ($currentTheme === 'dark') ? 'selected' : ''; ?>>Dark</option>
                                <option value="light" <?php echo ($currentTheme === 'light') ? 'selected' : ''; ?>>Light</option>
                            </select>
                        </label>
                        <label class="tb-form-checkbox">
                            <input type="checkbox" name="show_spotify" value="1" <?php echo ($showSpotify) ? 'checked' : ''; ?>>
                            Show Spotify Buttons
                        </label>
                        <label class="tb-form-checkbox">
                            <input type="checkbox" name="show_apple" value="1" <?php echo ($showApple) ? 'checked' : ''; ?>>
                            Show Apple Music Buttons
                        </label>
                        <button type="submit" name="update_theme" class="tb-btn-primary">
                            Save Settings
                        </button>
                    </form>
                </section>
                <section class="tb-admin-section">
                    <h2>Admin Profile</h2>
                    <form method="post" class="tb-form-inline">
                        <input type="hidden" name="tab" value="settings">
                        <label>Display Name
                            <input type="text" name="display_name" value="<?php echo htmlspecialchars($adminDisplayName ?? ''); ?>" placeholder="Admin name">
                        </label>
                        <button type="submit" name="update_admin_profile" class="tb-btn-primary">
                            Update Name
                        </button>
                    </form>
                </section>
            <?php elseif ($tab === 'users'): ?>
                <?php
                $users = $pdo->query("SELECT * FROM tb_users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
                $deviceCountsStmt = $pdo->prepare("SELECT COUNT(*) FROM tb_user_devices WHERE user_id = ?");
                ?>
                <section class="tb-admin-section">
                    <h2>Users</h2>
                    <form method="post" enctype="multipart/form-data" class="tb-form-inline">
                        <input type="hidden" name="tab" value="users">
                        <label>Name
                            <input type="text" name="name" required>
                        </label>
                        <label>Invite Code (optional)
                            <input type="text" name="unlock_pin" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="Auto-generate if empty">
                        </label>
                        <label>Icon Image (optional)
                            <input type="file" name="icon_upload" accept="image/*">
                        </label>
                        <button type="submit" name="add_user" class="tb-btn-primary">
                            <i class="fas fa-user-plus"></i> Add User
                        </button>
                    </form>

                    <ul class="tb-admin-list">
                        <?php foreach ($users as $user): ?>
                            <?php
                            $deviceCountsStmt->execute([$user['id']]);
                            $deviceCount = (int) $deviceCountsStmt->fetchColumn();
                            ?>
                            <li>
                                <div style="display:flex; align-items:center; gap:0.75rem;">
                                    <?php if (!empty($user['icon_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($user['icon_path']); ?>" alt="" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                                    <?php else: ?>
                                        <span class="tb-muted"><i class="fas fa-user"></i></span>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        <div class="tb-muted" style="font-size:0.85rem;">
                                            Invite Code: <?php echo htmlspecialchars($user['unlock_pin']); ?>
                                            • Devices: <?php echo $deviceCount; ?>
                                        </div>
                                    </div>
                                </div>
                                <form method="post" class="tb-inline-delete">
                                    <input type="hidden" name="tab" value="users">
                                    <button type="submit" name="delete_user" value="<?php echo (int) $user['id']; ?>" class="tb-inline-delete-btn"><i class="fas fa-trash"></i></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <li class="tb-empty">No users yet.</li>
                        <?php endif; ?>
                    </ul>
                </section>
            <?php elseif ($tab === 'app'): ?>
                <?php
                $appOpens = $pdo->query("SELECT opened_at FROM tb_app_opens ORDER BY opened_at DESC LIMIT 11")
                                ->fetchAll(PDO::FETCH_ASSOC);
                $lastOpened = $appOpens[0]['opened_at'] ?? null;
                $previousOpens = array_slice($appOpens, 1, 10);
                ?>
                <section class="tb-admin-section">
                    <h2>App Opens</h2>
                    <?php if ($lastOpened): ?>
                        <p><strong>Last opened:</strong> <?php echo htmlspecialchars(date('M j, Y g:ia', strtotime($lastOpened))); ?></p>
                        <?php if (!empty($previousOpens)): ?>
                            <ul class="tb-admin-list">
                                <?php foreach ($previousOpens as $open): ?>
                                    <li><?php echo htmlspecialchars(date('M j, Y g:ia', strtotime($open['opened_at']))); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="tb-muted">No previous opens logged yet.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="tb-muted">No opens logged yet.</p>
                    <?php endif; ?>
                </section>
            <?php elseif ($tab === 'feed'): ?>
                <?php
                $feedPosts = $pdo->query("SELECT * FROM tb_feed_posts ORDER BY created_at DESC")
                                 ->fetchAll(PDO::FETCH_ASSOC);
                $mediaStmt = $pdo->prepare("SELECT * FROM tb_feed_media WHERE post_id = ? ORDER BY created_at ASC");
                $commentsStmt = $pdo->prepare("SELECT * FROM tb_feed_comments WHERE post_id = ? ORDER BY created_at ASC");
                ?>
                <section class="tb-admin-section">
                    <h2>Feed</h2>
                    <div class="tb-feed-form">
                        <div class="tb-feed-form-header">
                            <h3>Post an Update</h3>
                        </div>
                        <form method="post" enctype="multipart/form-data" class="tb-feed-form-body">
                            <input type="hidden" name="tab" value="feed">
                            <label>Update Text
                                <textarea name="body" rows="4" required placeholder="Share something new..."></textarea>
                            </label>
                            <label>YouTube URL (optional)
                                <input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=...">
                            </label>
                            <label>Upload Photos (optional)
                                <input type="file" name="photos[]" accept="image/*" multiple>
                            </label>
                            <label>Upload Video (MP4 or MOV)
                                <input type="file" name="video_upload" accept="video/mp4,video/quicktime">
                            </label>
                            <button type="submit" name="add_feed_post" class="tb-btn-primary">
                                <i class="fas fa-plus"></i> Post Update
                            </button>
                        </form>
                    </div>

                    <div class="tb-admin-feed-list">
                        <?php foreach ($feedPosts as $post): ?>
                            <?php
                            $mediaStmt->execute([$post['id']]);
                            $mediaItems = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);
                            $commentsStmt->execute([$post['id']]);
                            $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
                            $youtubeId = $post['youtube_url'] ? tb_feed_parse_youtube_id($post['youtube_url']) : null;
                            ?>
                            <article class="tb-feed-card">
                                <header>
                                    <div>
                                        <h3><?php echo htmlspecialchars($post['author_name'] ?: 'Member'); ?></h3>
                                        <time><?php echo htmlspecialchars(date('M j, Y g:ia', strtotime($post['created_at']))); ?></time>
                                    </div>
                                    <form method="post" class="tb-inline-delete">
                                        <input type="hidden" name="tab" value="feed">
                                        <button type="submit" name="delete_feed_post" value="<?php echo (int) $post['id']; ?>" class="tb-inline-delete-btn"><i class="fas fa-trash"></i></button>
                                    </form>
                                </header>
                                <p class="tb-feed-body"><?php echo nl2br(htmlspecialchars($post['body'])); ?></p>

                                <?php if ($youtubeId): ?>
                                    <div class="tb-feed-media">
                                        <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($youtubeId); ?>" allowfullscreen></iframe>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($post['video_path'])): ?>
                                    <div class="tb-feed-media">
                                        <video controls src="<?php echo htmlspecialchars($post['video_path']); ?>"></video>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($mediaItems)): ?>
                                    <div class="tb-feed-gallery">
                                        <?php foreach ($mediaItems as $media): ?>
                                            <button type="button" class="tb-feed-image" data-image-src="<?php echo htmlspecialchars($media['file_path']); ?>">
                                                <img src="<?php echo htmlspecialchars($media['file_path']); ?>" alt="Feed media">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="tb-feed-comments">
                                    <h4>Comments</h4>
                                    <?php if (empty($comments)): ?>
                                        <p class="tb-muted">No comments yet.</p>
                                    <?php else: ?>
                                        <ul>
                                            <?php foreach ($comments as $comment): ?>
                                                <li>
                                                    <strong><?php echo htmlspecialchars($comment['author_name'] ?: 'Member'); ?>:</strong>
                                                    <?php echo nl2br(htmlspecialchars($comment['body'])); ?>
                                                    <form method="post" class="tb-inline-delete">
                                                        <input type="hidden" name="tab" value="feed">
                                                        <button type="submit" name="delete_feed_comment" value="<?php echo (int) $comment['id']; ?>" class="tb-inline-delete-btn"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                        <?php if (empty($feedPosts)): ?>
                            <p class="tb-empty">No feed updates yet.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    <?php endif; ?>
</div>
<div class="tb-feed-modal" id="tbFeedModal" aria-hidden="true">
    <div class="tb-feed-modal-content">
        <button type="button" class="tb-feed-modal-close" id="tbFeedModalClose">&times;</button>
        <img src="" alt="Feed image preview" id="tbFeedModalImage">
    </div>
</div>
<script>
// Initialize Sortable for videos and songs if lists exist
window.addEventListener('load', function() {
  if (typeof Sortable !== 'undefined') {
    const videoList = document.getElementById('videoList');
    if (videoList) {
      new Sortable(videoList, {
        animation: 150,
        handle: '.tb-handle',
        onSort: function () {
          const ids = Array.from(videoList.querySelectorAll('li[data-id]')).map(li => li.getAttribute('data-id'));
          const orderInput = document.getElementById('videoOrderInput');
          if (orderInput) orderInput.value = ids.join(',');
        }
      });
      const idsInit = Array.from(videoList.querySelectorAll('li[data-id]')).map(li => li.getAttribute('data-id'));
      const orderInputInit = document.getElementById('videoOrderInput');
      if (orderInputInit) orderInputInit.value = idsInit.join(',');
    }
    const songList = document.getElementById('songList');
    if (songList) {
      new Sortable(songList, {
        animation: 150,
        handle: '.tb-handle',
        onSort: function () {
          const ids = Array.from(songList.querySelectorAll('li[data-id]')).map(li => li.getAttribute('data-id'));
          const orderInput = document.getElementById('songOrderInput');
          if (orderInput) orderInput.value = ids.join(',');
        }
      });
      const idsInit2 = Array.from(songList.querySelectorAll('li[data-id]')).map(li => li.getAttribute('data-id'));
      const orderInputInit2 = document.getElementById('songOrderInput');
      if (orderInputInit2) orderInputInit2.value = idsInit2.join(',');
    }
  }
});

// Feed image modal for admin
(function() {
  const modal = document.getElementById('tbFeedModal');
  const modalImg = document.getElementById('tbFeedModalImage');
  const modalClose = document.getElementById('tbFeedModalClose');

  function closeModal() {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    if (modalImg) modalImg.src = '';
  }

  document.querySelectorAll('.tb-feed-image').forEach((button) => {
    button.addEventListener('click', () => {
      const src = button.getAttribute('data-image-src');
      if (modal && modalImg && src) {
        modalImg.src = src;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
      }
    });
  });

  if (modal) {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
  }
  if (modalClose) {
    modalClose.addEventListener('click', closeModal);
  }
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeModal();
    }
  });
})();

  // Inline edit via top forms for videos
  (function() {
    const videoForm     = document.getElementById('videoForm');
    if (!videoForm) return;
    const idInput       = document.getElementById('videoEditIdHidden');
    const titleInput    = videoForm.querySelector('input[name="title"]');
    const urlInput      = videoForm.querySelector('input[name="youtube_url"]');
    const releaseChk    = videoForm.querySelector('input[name="released"]');
    const addBtn        = document.getElementById('videoAddBtn');
    const updateBtn     = document.getElementById('videoUpdateBtn');
    const cancelBtn     = document.getElementById('videoCancelEdit');
    // attach click listeners on edit links
    document.querySelectorAll('#videoList .tb-edit-item').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const li = link.closest('li');
        if (!li) return;
        // populate form
        idInput.value    = li.getAttribute('data-id') || '';
        titleInput.value = li.getAttribute('data-title') || '';
        urlInput.value   = li.getAttribute('data-url') || '';
        releaseChk.checked = (li.getAttribute('data-released') === '1');
        // toggle buttons
        addBtn.style.display    = 'none';
        updateBtn.style.display = '';
        cancelBtn.style.display = '';
        // scroll into view
        videoForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => {
        // reset form
        idInput.value    = '';
        titleInput.value = '';
        urlInput.value   = '';
        releaseChk.checked = false;
        addBtn.style.display    = '';
        updateBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
      });
    }
  })();

  // Inline edit via top form for songs
  (function() {
    const songForm    = document.getElementById('songForm');
    if (!songForm) return;
    const idInput     = document.getElementById('songEditIdHidden');
    const coverPathHidden = document.getElementById('songEditCoverPathHidden');
    const titleInput  = songForm.querySelector('input[name="title"]');
    const mp3Input    = songForm.querySelector('input[name="mp3_path"]');
    const coverInput  = songForm.querySelector('input[name="cover_upload"]');
    const appleInput  = songForm.querySelector('input[name="apple_music_url"]');
    const spotifyInput= songForm.querySelector('input[name="spotify_url"]');
    const releasedChk = songForm.querySelector('input[name="released"]');
    const collectionSelect = document.getElementById('songFormCollectionSelect');
    const addBtn    = document.getElementById('songAddBtn');
    const updateBtn = document.getElementById('songUpdateBtn');
    const cancelBtn = document.getElementById('songCancelEdit');
    // attach click listeners on song titles
    document.querySelectorAll('#songList .tb-edit-item').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const li = link.closest('li');
        if (!li) return;
        // populate fields
        idInput.value    = li.getAttribute('data-id') || '';
        coverPathHidden.value = li.getAttribute('data-cover') || '';
        titleInput.value = li.getAttribute('data-title') || '';
        mp3Input.value   = li.getAttribute('data-mp3') || '';
        appleInput.value = li.getAttribute('data-apple') || '';
        spotifyInput.value = li.getAttribute('data-spotify') || '';
        releasedChk.checked = (li.getAttribute('data-released') === '1');
        // set collection select
        const collectionVal = li.getAttribute('data-collection');
        if (collectionSelect) {
          for (let i=0; i<collectionSelect.options.length; i++) {
            const opt = collectionSelect.options[i];
            opt.selected = (opt.value === collectionVal);
          }
        }
        // hide add, show update/cancel
        addBtn.style.display    = 'none';
        updateBtn.style.display = '';
        cancelBtn.style.display = '';
        // scroll into view
        songForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });
    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => {
        // reset form fields
        idInput.value    = '';
        coverPathHidden.value = '';
        titleInput.value = '';
        mp3Input.value   = '';
        // do not clear file input (coverInput) because cannot set value; just let user choose; but we can clear if editing cancelled
        coverInput.value  = '';
        appleInput.value = '';
        spotifyInput.value = '';
        releasedChk.checked = false;
        // reset collection select to none
        if (collectionSelect) collectionSelect.selectedIndex = 0;
        // show add, hide update/cancel
        addBtn.style.display    = '';
        updateBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
      });
    }
  })();
</script>
</body>
</html>
