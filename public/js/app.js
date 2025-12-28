/*  PAGE NAME: public/js/app.js
    SECTION: Shell Interactions
------------------------------------------------------------*/

const initShellControls = () => {
  if (window.tbShellInitialized) return;
  window.tbShellInitialized = true;

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
};

const initPageInteractions = (root = document) => {
  const scope = root.querySelector ? root : document;
  // Analytics toggle
  const toggle = scope.querySelector('#tbAnalyticsToggle');
  const ytPane = scope.querySelector('#tbAnalyticsYT');
  const spPane = scope.querySelector('#tbAnalyticsSP');

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
  const videosToggle = scope.querySelector('#tbVideosToggle');
  const videosProduction = scope.querySelector('#tbVideosProduction');
  const videosReleased = scope.querySelector('#tbVideosReleased');
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
  const songsToggle = scope.querySelector('#tbSongsToggle');
  const songsUnreleased = scope.querySelector('#tbSongsUnreleased');
  const songsReleased = scope.querySelector('#tbSongsReleased');
  const songsCollections = scope.querySelector('#tbSongsCollections');
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
  const videoModal = scope.querySelector('#videoModal');
  const videoIframe = scope.querySelector('#tbVideoIframe');
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
    scope.querySelectorAll('.tb-video-card .tb-play-overlay').forEach(btn => {
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
      });
    });
    // close button
    const closeBtn = videoModal.querySelector('.tb-modal-close');
    if (closeBtn) {
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
    }
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

  const videoCommentModal = scope.querySelector('#videoCommentModal');
  const videoCommentTitle = scope.querySelector('#videoCommentTitle');
  if (videoCommentModal && videoCommentTitle) {
    scope.querySelectorAll('.tb-video-comment-trigger').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const card = btn.closest('.tb-video-card');
        const title = card ? card.dataset.videoTitle : '';
        videoCommentTitle.textContent = title ? `${title} Comments` : 'Comments';
        videoCommentModal.classList.add('active');
      });
    });

    const commentClose = videoCommentModal.querySelector('.tb-modal-close');
    if (commentClose) {
      commentClose.addEventListener('click', () => {
        videoCommentModal.classList.remove('active');
      });
    }
    videoCommentModal.addEventListener('click', (e) => {
      if (e.target === videoCommentModal) {
        videoCommentModal.classList.remove('active');
      }
    });
  }

  const videoCommentModal = document.getElementById('videoCommentModal');
  const videoCommentTitle = document.getElementById('videoCommentTitle');
  if (videoCommentModal && videoCommentTitle) {
    document.querySelectorAll('.tb-video-comment-trigger').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const card = btn.closest('.tb-video-card');
        const title = card ? card.dataset.videoTitle : '';
        videoCommentTitle.textContent = title ? `${title} Comments` : 'Comments';
        videoCommentModal.classList.add('active');
      });
    });

    const commentClose = videoCommentModal.querySelector('.tb-modal-close');
    if (commentClose) {
      commentClose.addEventListener('click', () => {
        videoCommentModal.classList.remove('active');
      });
    }
    videoCommentModal.addEventListener('click', (e) => {
      if (e.target === videoCommentModal) {
        videoCommentModal.classList.remove('active');
      }
    });
  }

  var videoCommentModal = document.getElementById('videoCommentModal');
  var videoCommentTitle = document.getElementById('videoCommentTitle');
  if (videoCommentModal && videoCommentTitle) {
    document.querySelectorAll('.tb-video-comment-trigger').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const card = btn.closest('.tb-video-card');
        const title = card ? card.dataset.videoTitle : '';
        videoCommentTitle.textContent = title ? `${title} Comments` : 'Comments';
        videoCommentModal.classList.add('active');
      });
    });

    const commentClose = videoCommentModal.querySelector('.tb-modal-close');
    if (commentClose) {
      commentClose.addEventListener('click', () => {
        videoCommentModal.classList.remove('active');
      });
    }
    videoCommentModal.addEventListener('click', (e) => {
      if (e.target === videoCommentModal) {
        videoCommentModal.classList.remove('active');
      }
    });
  }

  // Song play buttons
  let currentAudio = null;
  let currentPlayBtn = null;
  scope.querySelectorAll('.tb-song-play-btn').forEach(btn => {
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

  const analyticsLoadingPairs = [
    { loadingId: 'yt-loading', contentId: 'yt-content' },
    { loadingId: 'ga-loading', contentId: 'ga-content' },
    { loadingId: 'sp-loading', contentId: 'sp-content' },
    { loadingId: 'app-loading', contentId: 'app-content' },
  ];

  analyticsLoadingPairs.forEach(({ loadingId, contentId }) => {
    const loading = scope.querySelector(`#${loadingId}`);
    const content = scope.querySelector(`#${contentId}`);
    if (loading && content) {
      loading.style.display = 'none';
      content.style.display = 'block';
    }
  });

  const toggleBtn = scope.querySelector('#tbFeedToggle');
  const form = scope.querySelector('#tbFeedPostForm');
  if (toggleBtn && form) {
    toggleBtn.addEventListener('click', () => {
      const isHidden = form.hasAttribute('hidden');
      if (isHidden) {
        form.removeAttribute('hidden');
        toggleBtn.classList.add('active');
        const label = toggleBtn.querySelector('span');
        if (label) label.textContent = 'Hide Form';
      } else {
        form.setAttribute('hidden', '');
        toggleBtn.classList.remove('active');
        const label = toggleBtn.querySelector('span');
        if (label) label.textContent = 'New Post';
      }
    });
  }

  const modal = scope.querySelector('#tbFeedModal');
  const modalImg = scope.querySelector('#tbFeedModalImage');
  const modalClose = scope.querySelector('#tbFeedModalClose');

  const closeModal = () => {
    if (!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    if (modalImg) modalImg.src = '';
  };

  scope.querySelectorAll('.tb-feed-image').forEach((button) => {
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
  if (!window.tbFeedModalKeyHandler) {
    window.tbFeedModalKeyHandler = true;
    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') return;
      const activeModal = document.getElementById('tbFeedModal');
      const activeImg = document.getElementById('tbFeedModalImage');
      if (!activeModal) return;
      activeModal.classList.remove('is-open');
      activeModal.setAttribute('aria-hidden', 'true');
      if (activeImg) activeImg.src = '';
    });
  }
};

const showPageLoading = (message = 'Loading…') => {
  const overlay = document.getElementById('tbPageLoading');
  if (!overlay) return;
  const text = overlay.querySelector('.tb-loading-text');
  if (text) {
    text.textContent = message;
  }
  overlay.classList.add('active');
  overlay.setAttribute('aria-hidden', 'false');
};

const hidePageLoading = () => {
  const overlay = document.getElementById('tbPageLoading');
  if (!overlay) return;
  overlay.classList.remove('active');
  overlay.setAttribute('aria-hidden', 'true');
};

const updateActiveNav = (pageKey) => {
  if (!pageKey) return;
  document.querySelectorAll('[data-nav-page]').forEach((link) => {
    const isActive = link.dataset.navPage === pageKey;
    link.classList.toggle('active', isActive);
  });
};

const shouldHandleAjaxLink = (link) => {
  if (!link) return false;
  if (link.dataset.noAjax === 'true') return false;
  if (link.target && link.target !== '_self') return false;
  if (link.hasAttribute('download')) return false;

  const href = link.getAttribute('href');
  if (!href || href.startsWith('#')) return false;

  const url = new URL(link.href, window.location.href);
  if (url.origin !== window.location.origin) return false;

  const isIndex = url.pathname.endsWith('index.php') || url.searchParams.has('page');
  const isFetchVideo = url.pathname.endsWith('fetchvideo.php');
  return isIndex || isFetchVideo;
};

const fetchAjaxPage = async (url, { push = true } = {}) => {
  const requestUrl = new URL(url.toString());
  requestUrl.searchParams.set('ajax', '1');

  const response = await fetch(requestUrl.toString(), {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
    },
  });

  if (!response.ok) {
    throw new Error('Failed to load page.');
  }

  const html = await response.text();
  const temp = document.createElement('div');
  temp.innerHTML = html;
  const wrapper = temp.querySelector('.tb-ajax-page');
  if (!wrapper) {
    window.location.href = url.toString();
    return;
  }

  const main = document.querySelector('.tb-main');
  if (!main) {
    window.location.href = url.toString();
    return;
  }

  main.innerHTML = wrapper.innerHTML;
  const pageTitle = wrapper.dataset.pageTitle;
  const pageKey = wrapper.dataset.pageKey;

  if (pageTitle) {
    document.title = pageTitle;
  }

  updateActiveNav(pageKey);

  if (push) {
    history.pushState({ url: url.toString() }, '', url.toString());
  }

  initPageInteractions(main);
  if (typeof window.tbInitTrackPlayer === 'function') {
    window.tbInitTrackPlayer();
  }
};

const initAjaxNavigation = () => {
  if (window.tbAjaxInitialized) return;
  window.tbAjaxInitialized = true;

  document.addEventListener('click', async (event) => {
    const link = event.target.closest('a');
    if (!shouldHandleAjaxLink(link)) return;

    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    event.preventDefault();
    const url = new URL(link.href, window.location.href);
    const message = link.dataset.loadingMessage || 'Loading…';

    showPageLoading(message);

    try {
      await fetchAjaxPage(url, { push: true });
    } catch (error) {
      window.location.href = url.toString();
      return;
    } finally {
      hidePageLoading();
    }
  });

  window.addEventListener('popstate', async () => {
    const url = new URL(window.location.href);
    showPageLoading('Loading…');
    try {
      await fetchAjaxPage(url, { push: false });
    } catch (error) {
      window.location.href = url.toString();
    } finally {
      hidePageLoading();
    }
  });
};

document.addEventListener('DOMContentLoaded', () => {
  initShellControls();
  initPageInteractions(document);
  initAjaxNavigation();
});

window.tbInitPageInteractions = initPageInteractions;
window.tbShowPageLoading = showPageLoading;
