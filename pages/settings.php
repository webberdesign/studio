<?php
/*  PAGE NAME: pages/settings.php
    SECTION: Settings Page
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../user_helpers.php';

// Load settings and handle form submission.  The settings page allows
// visitors to choose light or dark mode and toggle the visibility of
// Apple Music and Spotify buttons.  These preferences are stored in
// settings.json and persist across sessions.
$currentUser = tb_get_current_user($pdo);
$settings     = tb_get_effective_settings($pdo, $currentUser);
$currentTheme = $settings['theme'];
$showSpotify  = !empty($settings['show_spotify']);
$showApple    = !empty($settings['show_apple']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine new theme based on whether the checkbox is checked.  In
    // this UI the checkbox value is 'light' when checked; absent when dark.
    $postedTheme = $_POST['theme'] ?? '';
    $newTheme = ($postedTheme === 'light') ? 'light' : 'dark';
    // Determine service flags
    $newShowSpotify = isset($_POST['show_spotify']) ? true : false;
    $newShowApple   = isset($_POST['show_apple']) ? true : false;
    if ($currentUser) {
        tb_update_user_settings($pdo, (int)$currentUser['id'], [
            'theme' => $newTheme,
            'show_spotify' => $newShowSpotify,
            'show_apple' => $newShowApple,
        ]);
    } else {
        tb_set_settings([
            'theme'        => $newTheme,
            'show_spotify' => $newShowSpotify,
            'show_apple'   => $newShowApple
        ]);
    }
    // Update local variables
    $settings     = tb_get_effective_settings($pdo, $currentUser);
    $currentTheme = $settings['theme'];
    $showSpotify  = !empty($settings['show_spotify']);
    $showApple    = !empty($settings['show_apple']);
    // Optional: redirect to avoid resubmission
    if (!headers_sent()) {
        header('Location: ?page=settings');
        exit;
    }
    $saveMessage = 'Settings saved.';
}
?>
<section class="tb-section">
    <h1 class="tb-title">Settings</h1>
    <p class="tb-subtitle">Customize your experience</p>
    <?php if (!empty($saveMessage)): ?>
        <div class="tb-alert"><?php echo htmlspecialchars($saveMessage); ?></div>
    <?php endif; ?>

    <form method="post" class="tb-settings-form">
        <div class="tb-form-group">
            <!-- Theme toggle switch -->
            <label class="tb-switch">
                <input type="checkbox" name="theme" value="light" <?php echo ($currentTheme === 'light' ? 'checked' : ''); ?> />
                <span class="tb-slider"></span>
                <span class="tb-switch-label">Light Mode</span>
            </label>
        </div>
        <div class="tb-form-group" style="margin-top:1rem;">
            <!-- Service visibility checkboxes -->
            <label class="tb-form-checkbox">
                <input type="checkbox" name="show_spotify" value="1" <?php echo ($showSpotify ? 'checked' : ''); ?>>
                Show Spotify Buttons
            </label>
            <label class="tb-form-checkbox" style="margin-left:1rem;">
                <input type="checkbox" name="show_apple" value="1" <?php echo ($showApple ? 'checked' : ''); ?>>
                Show Apple Music Buttons
            </label>
        </div>
        <button type="submit" class="tb-btn-primary">Save Settings</button>
    </form>

    <div class="tb-settings-links" style="margin-top:1.5rem;">
        <a href="admin.php" class="tb-btn-secondary"><i class="fas fa-lock"></i> Admin Login</a>
    </div>
</section>
