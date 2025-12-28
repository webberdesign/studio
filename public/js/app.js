/*  PAGE NAME: public/js/app.js
    SECTION: Shell Interactions
------------------------------------------------------------*/

document.addEventListener('DOMContentLoaded', () => {
  // Side nav toggle
  const sideNav = document.getElementById('tbSideNav');
  const openBtn = document.getElementById('tbOpenNav');
  const closeBtn = document.getElementById('tbCloseNav');

  if (openBtn && sideNav) {
    openBtn.addEventListener('click', () => {
      sideNav.classList.add('open');
    });
  }

  if (closeBtn && sideNav) {
    closeBtn.addEventListener('click', () => {
      sideNav.classList.remove('open');
    });
  }

  // Click outside to close
  document.addEventListener('click', (e) => {
    if (!sideNav) return;
    if (!sideNav.classList.contains('open')) return;

    const withinNav = sideNav.contains(e.target);
    const withinTrigger = openBtn && openBtn.contains(e.target);
    if (!withinNav && !withinTrigger) sideNav.classList.remove('open');
  });

  // Analytics toggle
  const toggle = document.getElementById('tbAnalyticsToggle');
  const ytPane = document.getElementById('tbAnalyticsYT');
  const spPane = document.getElementById('tbAnalyticsSP');

  if (toggle && ytPane && spPane) {
    toggle.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if (!btn) return;
      const target = btn.dataset.target;

      toggle.querySelectorAll('button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      ytPane.classList.toggle('active', target === 'yt');
      spPane.classList.toggle('active', target === 'sp');
    });
  }

  // Videos toggle
  const videosToggle = document.getElementById('tbVideosToggle');
  const videosProduction = document.getElementById('tbVideosProduction');
  const videosReleased = document.getElementById('tbVideosReleased');
  if (videosToggle && videosProduction && videosReleased) {
    videosToggle.addEventListener('click', (e) => {
      // find the button that has data-target attribute
      const btn = e.target.closest('button[data-target]');
      if (!btn) return;
      const target = btn.dataset.target;
      // switch active class on buttons
      videosToggle.querySelectorAll('button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      // toggle panes
      videosProduction.classList.toggle('active', target === 'production');
      videosReleased.classList.toggle('active', target === 'released');
    });
  }

  // Songs toggle
  const songsToggle = document.getElementById('tbSongsToggle');
  const songsUnreleased = document.getElementById('tbSongsUnreleased');
  const songsReleased = document.getElementById('tbSongsReleased');
  const songsCollections = document.getElementById('tbSongsCollections');
  if (songsToggle && songsUnreleased && songsReleased && songsCollections) {
    songsToggle.addEventListener('click', (e) => {
      // find the button with data-target on click; could be nested inside
      const btn = e.target.closest('button[data-target]');
      if (!btn) return;
      const target = btn.dataset.target;
      songsToggle.querySelectorAll('button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      songsUnreleased.classList.toggle('active', target === 'unreleased');
      songsReleased.classList.toggle('active', target === 'released');
      songsCollections.classList.toggle('active', target === 'collections');
    });
  }

  // Video modal logic
  const videoModal = document.getElementById('videoModal');
  const videoIframe = document.getElementById('tbVideoIframe');
  if (videoModal && videoIframe) {
    const commentPanel = videoModal.querySelector('.tb-video-comment-panel');
    const commentToggle = commentPanel ? commentPanel.querySelector('.tb-video-comment-toggle') : null;
    const commentForm = commentPanel ? commentPanel.querySelector('.tb-feed-comment-form') : null;

    if (commentToggle && commentForm) {
      commentToggle.addEventListener('click', () => {
        const isHidden = commentForm.hasAttribute('hidden');
        if (isHidden) {
          commentForm.removeAttribute('hidden');
          commentToggle.setAttribute('aria-expanded', 'true');
        } else {
          commentForm.setAttribute('hidden', '');
          commentToggle.setAttribute('aria-expanded', 'false');
        }
      });
    }

    // delegate click on play overlays
    document.querySelectorAll('.tb-video-card .tb-play-overlay').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const card = btn.closest('.tb-video-card');
        const id = card ? card.dataset.videoId : null;
        if (!id) return;
        // build embed url
        const url = `https://www.youtube.com/embed/${id}?autoplay=1&rel=0`;
        videoIframe.src = url;
        videoModal.classList.add('active');
        if (commentPanel) {
          const isProduction = card && card.dataset.videoStatus === 'production';
          commentPanel.hidden = !isProduction;
          if (commentForm) {
            commentForm.setAttribute('hidden', '');
          }
          if (commentToggle) {
            commentToggle.setAttribute('aria-expanded', 'false');
          }
        }
        if (window.tbSharedTrackPlayer) {
          window.tbSharedTrackPlayer.pause();
        }
        // On small screens attempt to enter full screen automatically
        if (window.innerWidth <= 768 && typeof videoModal.requestFullscreen === 'function') {
          try {
            videoModal.requestFullscreen();
          } catch (err) {
            // ignore if cannot enter full screen
          }
        }
      });
    });
    // close button
    const closeBtn = videoModal.querySelector('.tb-modal-close');
    closeBtn.addEventListener('click', () => {
      videoModal.classList.remove('active');
      videoIframe.src = '';
      if (commentForm) {
        commentForm.setAttribute('hidden', '');
      }
      if (commentToggle) {
        commentToggle.setAttribute('aria-expanded', 'false');
      }
    });
    // click outside content to close
    videoModal.addEventListener('click', (e) => {
      if (e.target === videoModal) {
        videoModal.classList.remove('active');
        videoIframe.src = '';
        if (commentForm) {
          commentForm.setAttribute('hidden', '');
        }
        if (commentToggle) {
          commentToggle.setAttribute('aria-expanded', 'false');
        }
      }
    });
  }

  // Song play buttons
  let currentAudio = null;
  let currentPlayBtn = null;
  document.querySelectorAll('.tb-song-play-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const src = btn.dataset.src;
      if (!src) return;
      // if new audio or none
      if (!currentAudio || currentAudio.src !== src) {
        if (currentAudio) {
          currentAudio.pause();
          if (currentPlayBtn) {
            currentPlayBtn.classList.remove('playing');
            const prevIcon = currentPlayBtn.querySelector('i');
            if (prevIcon) {
              prevIcon.classList.remove('fa-pause');
              prevIcon.classList.add('fa-play');
            }
          }
        }
        currentAudio = new Audio(src);
        currentAudio.play();
        currentPlayBtn = btn;
        btn.classList.add('playing');
        const icon = btn.querySelector('i');
        if (icon) {
          icon.classList.remove('fa-play');
          icon.classList.add('fa-pause');
        }
      } else {
        // toggle play/pause
        if (currentAudio.paused) {
          currentAudio.play();
          btn.classList.add('playing');
          const icon = btn.querySelector('i');
          if (icon) {
            icon.classList.remove('fa-play');
            icon.classList.add('fa-pause');
          }
        } else {
          currentAudio.pause();
          btn.classList.remove('playing');
          const icon = btn.querySelector('i');
          if (icon) {
            icon.classList.remove('fa-pause');
            icon.classList.add('fa-play');
          }
        }
      }
    });
  });

  // Theme toggle
  const themeToggleBtn = document.getElementById('tbThemeToggleBtn');
  if (themeToggleBtn) {
    // initialise theme: default to light when none stored
    let savedTheme = localStorage.getItem('tb_theme');
    if (!savedTheme) {
      // no saved theme: set to light
      savedTheme = 'light';
      localStorage.setItem('tb_theme', savedTheme);
    }
    const isLight = savedTheme === 'light';
    if (isLight) {
      document.body.classList.add('tb-theme-light');
      themeToggleBtn.classList.add('active');
      const icon = themeToggleBtn.querySelector('i');
      if (icon) {
        // show moon icon in light mode to indicate switch to dark
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
      }
    }
    themeToggleBtn.addEventListener('click', () => {
      const currentlyLight = document.body.classList.toggle('tb-theme-light');
      // update local storage
      localStorage.setItem('tb_theme', currentlyLight ? 'light' : 'dark');
      themeToggleBtn.classList.toggle('active', currentlyLight);
      const icon = themeToggleBtn.querySelector('i');
      if (icon) {
        if (currentlyLight) {
          // show moon icon when in light mode
          icon.classList.remove('fa-sun');
          icon.classList.add('fa-moon');
        } else {
          // show sun icon when in dark mode
          icon.classList.remove('fa-moon');
          icon.classList.add('fa-sun');
        }
      }
    });
  }

  // PWA: register service worker if present
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js').catch(() => {});
  }
});
