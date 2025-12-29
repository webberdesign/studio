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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_video_comment']) && !empty($_POST['video_id'])) {
        $videoId = (int) $_POST['video_id'];
        $comment = trim($_POST['comment_body'] ?? '');
        if ($comment !== '') {
            $stmt = $pdo->prepare("INSERT INTO tb_video_comments (video_id, author_name, body) VALUES (?, ?, ?)");
            $stmt->execute([$videoId, 'Dahr', $comment]);
        }
    }
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

$videoCommentThreads = [];
if (!empty($videos)) {
    $videoIds = array_map('intval', array_column($videos, 'id'));
    $placeholders = implode(',', array_fill(0, count($videoIds), '?'));
    $commentsStmt = $pdo->prepare("SELECT * FROM tb_video_comments WHERE video_id IN ($placeholders) ORDER BY created_at ASC");
    $commentsStmt->execute($videoIds);
    foreach ($commentsStmt->fetchAll(PDO::FETCH_ASSOC) as $comment) {
        $videoCommentThreads[$comment['video_id']][] = $comment;
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
        <article class="tb-video-card" data-video-id="<?php echo htmlspecialchars($videoId); ?>" data-video-db-id="<?php echo (int) $video['id']; ?>" data-video-title="<?php echo htmlspecialchars($video['title']); ?>">
            <div class="tb-video-media">
                <?php if ($thumb): ?>
                    <img src="<?php echo htmlspecialchars($thumb); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" class="tb-card-thumb">
                <?php endif; ?>
                <!-- play overlay inside the card -->
                <button class="tb-play-overlay"><i class="fas fa-play"></i></button>
            </div>
            <div class="tb-card-body">
                <h2 class="tb-card-title"><?php echo htmlspecialchars($video['title']); ?></h2>
                <button type="button" class="tb-video-comment-trigger" aria-label="Open comments for <?php echo htmlspecialchars($video['title']); ?>">
                    <i class="fas fa-comment"></i>
                </button>
            </div>
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
        <article class="tb-video-card" data-video-id="<?php echo htmlspecialchars($videoId); ?>" data-video-db-id="<?php echo (int) $video['id']; ?>" data-video-title="<?php echo htmlspecialchars($video['title']); ?>">
            <div class="tb-video-media">
                <?php if ($thumb): ?>
                    <img src="<?php echo htmlspecialchars($thumb); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" class="tb-card-thumb">
                <?php endif; ?>
                <!-- play overlay inside the card -->
                <button class="tb-play-overlay"><i class="fas fa-play"></i></button>
            </div>
            <div class="tb-card-body">
                <h2 class="tb-card-title"><?php echo htmlspecialchars($video['title']); ?></h2>
            </div>
        </article>
        <?php /* editing disabled in public view */ ?>
        <?php endforeach; ?>
        <?php if (empty($released)): ?>
            <p class="tb-empty">No released videos yet.</p>
        <?php endif; ?>
    </div>

    <!-- Modal for video playback -->
    <div id="videoModal" class="tb-video-modal">
        <div class="tb-modal-content tb-video-modal-content">
            <button class="tb-modal-close">&times;</button>
            <div class="tb-modal-iframe-container">
                <iframe id="tbVideoIframe" src="" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
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

    <div id="videoCommentModal" class="tb-video-modal tb-video-comment-modal">
        <div class="tb-modal-content tb-video-comment-content">
            <button class="tb-modal-close" type="button">&times;</button>
            <div class="tb-video-comment-header">
                <h3 id="videoCommentTitle">Comments</h3>
            </div>
            <div class="tb-feed-comments">
                <h4>Comments</h4>
                <?php if (empty($production)): ?>
                    <p class="tb-muted">No comments yet.</p>
                <?php else: ?>
                    <?php foreach ($production as $video): ?>
                        <?php $comments = $videoCommentThreads[$video['id']] ?? []; ?>
                        <div class="tb-video-comment-thread" data-video-id="<?php echo (int) $video['id']; ?>" hidden>
                            <?php if (empty($comments)): ?>
                                <p class="tb-muted">No comments yet.</p>
                            <?php else: ?>
                                <ul>
                                    <?php foreach ($comments as $comment): ?>
                                        <li>
                                            <strong><?php echo htmlspecialchars($comment['author_name'] ?: 'Dahr'); ?>:</strong>
                                            <?php echo nl2br(htmlspecialchars($comment['body'])); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <form method="post" class="tb-feed-comment-form">
                <input type="hidden" name="video_id" value="">
                <textarea name="comment_body" rows="2" required placeholder="Write a comment..."></textarea>
                <button type="submit" name="add_video_comment" class="tb-btn-secondary">Post Comment</button>
            </form>
        </div>
    </div>
</section>
