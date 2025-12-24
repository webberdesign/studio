<?php
/*  PAGE NAME: pages/feed.php
    SECTION: Social Feed
------------------------------------------------------------*/
require_once __DIR__ . '/../feed_helpers.php';

$feedMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_feed_post'])) {
        $body       = trim($_POST['body'] ?? '');
        $youtubeUrl = trim($_POST['youtube_url'] ?? '');

        if ($body !== '') {
            $photoPaths = tb_feed_handle_photo_uploads($_FILES['photos'] ?? null);
            $videoPath  = tb_feed_handle_video_upload($_FILES['video_upload'] ?? null);

            $stmt = $pdo->prepare("INSERT INTO tb_feed_posts (author_name, body, youtube_url, video_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                'Dahr',
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

            $feedMessage = 'Your update has been posted.';
        } else {
            $feedMessage = 'Please add some text for your update.';
        }
    }

    if (isset($_POST['add_comment']) && !empty($_POST['post_id'])) {
        $postId     = (int) $_POST['post_id'];
        $comment    = trim($_POST['comment_body'] ?? '');
        if ($comment !== '') {
            $stmt = $pdo->prepare("INSERT INTO tb_feed_comments (post_id, author_name, body) VALUES (?, ?, ?)");
            $stmt->execute([$postId, 'Dahr', $comment]);
        }
    }
}

$postsStmt = $pdo->query("SELECT * FROM tb_feed_posts ORDER BY created_at DESC");
$feedPosts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

$mediaStmt = $pdo->prepare("SELECT * FROM tb_feed_media WHERE post_id = ? ORDER BY created_at ASC");
$commentsStmt = $pdo->prepare("SELECT * FROM tb_feed_comments WHERE post_id = ? ORDER BY created_at ASC");
?>

<section class="tb-section">
    <h1 class="tb-title">Feed</h1>
    <p class="tb-subtitle">Share updates, photos, and videos with your audience.</p>

    <?php if ($feedMessage): ?>
        <div class="tb-alert"><?php echo htmlspecialchars($feedMessage); ?></div>
    <?php endif; ?>

    <div class="tb-feed-form">
        <div class="tb-feed-form-header">
            <h2>Post an Update</h2>
            <button type="button" class="tb-toggle-pill tb-feed-toggle" id="tbFeedToggle">
                <span>New Post</span>
            </button>
        </div>
        <form method="post" enctype="multipart/form-data" id="tbFeedPostForm" class="tb-feed-form-body" hidden>
            <label>
                Update Text
                <textarea name="body" rows="4" required placeholder="Share something new..."></textarea>
            </label>
            <label>
                YouTube URL (optional)
                <input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=...">
            </label>
            <label>
                Upload Photos (optional)
                <input type="file" name="photos[]" accept="image/*" multiple>
            </label>
            <label>
                Upload Video (MP4 or MOV)
                <input type="file" name="video_upload" accept="video/mp4,video/quicktime">
            </label>
            <button type="submit" name="add_feed_post" class="tb-btn-primary">Post to Feed</button>
        </form>
    </div>

    <div class="tb-feed-list">
        <?php if (empty($feedPosts)): ?>
            <p class="tb-empty">No updates yet. Be the first to post!</p>
        <?php endif; ?>

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
                        <h3><?php echo htmlspecialchars($post['author_name'] ?: 'Studio Update'); ?></h3>
                        <time><?php echo htmlspecialchars(date('M j, Y g:ia', strtotime($post['created_at']))); ?></time>
                    </div>
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
                                    <strong><?php echo htmlspecialchars($comment['author_name'] ?: 'Guest'); ?>:</strong>
                                    <?php echo nl2br(htmlspecialchars($comment['body'])); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <form method="post" class="tb-feed-comment-form">
                        <input type="hidden" name="post_id" value="<?php echo (int) $post['id']; ?>">
                        <textarea name="comment_body" rows="2" required placeholder="Write a comment..."></textarea>
                        <button type="submit" name="add_comment" class="tb-btn-secondary">Post Comment</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<div class="tb-feed-modal" id="tbFeedModal" aria-hidden="true">
    <div class="tb-feed-modal-content">
        <button type="button" class="tb-feed-modal-close" id="tbFeedModalClose">&times;</button>
        <img src="" alt="Feed image preview" id="tbFeedModalImage">
    </div>
</div>

<script>
(function() {
  const toggleBtn = document.getElementById('tbFeedToggle');
  const form = document.getElementById('tbFeedPostForm');
  if (toggleBtn && form) {
    toggleBtn.addEventListener('click', function() {
      const isHidden = form.hasAttribute('hidden');
      if (isHidden) {
        form.removeAttribute('hidden');
        toggleBtn.classList.add('active');
        toggleBtn.querySelector('span').textContent = 'Hide Form';
      } else {
        form.setAttribute('hidden', '');
        toggleBtn.classList.remove('active');
        toggleBtn.querySelector('span').textContent = 'New Post';
      }
    });
  }

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
</script>
