<?php
/*  PAGE NAME: pages/videos.php
    SECTION: Videos Library (Public)
------------------------------------------------------------*/

/**
 * Converts a YouTube URL into a thumbnail URL (mqdefault).
 */
function tb_youtube_thumb(string $url): ?string {
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/))([\w\-]{11})~', $url, $m)) {
        $id = $m[1];
        return "https://img.youtube.com/vi/{$id}/mqdefault.jpg";
    }
    return null;
}

// fetch all videos and separate released vs in production by order
$stmt = $pdo->query("SELECT * FROM tb_videos ORDER BY position ASC, created_at DESC");
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$production = [];
$released = [];
foreach ($videos as $video) {
    if (!empty($video['is_released'])) {
        $released[] = $video;
    } else {
        $production[] = $video;
    }
}

// Determine if the current user is an admin (not used here anymore).
$isAdmin = tb_is_admin();
?>
<section class="tb-section">
    <h1 class="tb-title">Videos</h1>
    <p class="tb-subtitle">Watch unreleased demos or released works.</p>

    <!-- SECTION: Toggle between production and released -->
    <div class="tb-toggle-pill" id="tbVideosToggle">
        <button type="button" class="active" data-target="production">In Production</button>
        <button type="button" data-target="released">Released</button>
    </div>

    <!-- In Production Videos -->
    <div id="tbVideosProduction" class="tb-videos-pane active">
        <?php foreach ($production as $video):
            $thumb = tb_youtube_thumb($video['youtube_url']);
            $videoId = null;
            if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/))([\w\-]{11})~', $video['youtube_url'], $m)) {
                $videoId = $m[1];
            }
        ?>
        <article class="tb-video-card" data-video-id="<?php echo htmlspecialchars($videoId); ?>" data-video-status="production">
            <?php if ($thumb): ?>
                <img src="<?php echo htmlspecialchars($thumb); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" class="tb-card-thumb">
            <?php endif; ?>
            <div class="tb-card-body">
                <h2 class="tb-card-title"><?php echo htmlspecialchars($video['title']); ?></h2>
            </div>
            <!-- play overlay inside the card -->
            <button class="tb-play-overlay"><i class="fas fa-play"></i></button>
        </article>
        <?php /* editing disabled in public view */ ?>
        <?php endforeach; ?>
        <?php if (empty($production)): ?>
            <p class="tb-empty">No in-production videos currently.</p>
        <?php endif; ?>
    </div>

    <!-- Released Videos -->
    <div id="tbVideosReleased" class="tb-videos-pane">
        <?php foreach ($released as $video):
            $thumb = tb_youtube_thumb($video['youtube_url']);
            $videoId = null;
            if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/))([\w\-]{11})~', $video['youtube_url'], $m)) {
                $videoId = $m[1];
            }
        ?>
        <article class="tb-video-card" data-video-id="<?php echo htmlspecialchars($videoId); ?>" data-video-status="released">
            <?php if ($thumb): ?>
                <img src="<?php echo htmlspecialchars($thumb); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" class="tb-card-thumb">
            <?php endif; ?>
            <div class="tb-card-body">
                <h2 class="tb-card-title"><?php echo htmlspecialchars($video['title']); ?></h2>
            </div>
            <!-- play overlay inside the card -->
            <button class="tb-play-overlay"><i class="fas fa-play"></i></button>
        </article>
        <?php /* editing disabled in public view */ ?>
        <?php endforeach; ?>
        <?php if (empty($released)): ?>
            <p class="tb-empty">No released videos yet.</p>
        <?php endif; ?>
    </div>

    <!-- Modal for video playback -->
    <div id="videoModal" class="tb-video-modal">
        <div class="tb-modal-content">
            <button class="tb-modal-close">&times;</button>
            <div class="tb-modal-iframe-container">
                <iframe id="tbVideoIframe" src="" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
            </div>
            <div class="tb-video-comment-panel" hidden>
                <button type="button" class="tb-video-comment-toggle" aria-expanded="false">
                    <i class="fas fa-comment"></i>
                    <span>Comment</span>
                </button>
                <form class="tb-feed-comment-form" hidden>
                    <textarea rows="2" placeholder="Write a comment..."></textarea>
                    <button type="button" class="tb-btn-secondary">Post Comment</button>
                </form>
            </div>
        </div>
    </div>
</section>
