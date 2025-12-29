/*  PAGE NAME: vibe_audio_player.js
    SECTION: Audio Playlist Player for Collections
------------------------------------------------------------*/

/*
 * This script implements a simple audio playlist player inspired by the
 * "vibe player" demo.  It loads track data from a PHP endpoint
 * (vibeplayer.php?collectionId=...) and binds controls for a mini
 * player bar and a full‑screen player overlay.  The player uses a
 * single HTML5 Audio element to play local MP3 files rather than
 * YouTube videos.
 */

// Global playlist and player state
let tracks = [];
let currentIndex = 0;
let audio = new Audio();
let isReady = false;
let isPlaying = false;
let nextTrackTimeout = null;
let progressInterval = null;
const logEvent = (event, extra = {}) => {
  const track = tracks[currentIndex];
  console.debug('[vibe-player]', event, {
    index: currentIndex,
    title: track?.title,
    src: track?.src,
    time: audio.currentTime,
    duration: audio.duration,
    readyState: audio.readyState,
    networkState: audio.networkState,
    paused: audio.paused,
    ...extra,
  });
};

// Path data for Font Awesome icons (play, pause) used in our inline SVGs.
const PLAY_PATH = "M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80L0 432c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z";
const PAUSE_PATH = "M48 64C21.5 64 0 85.5 0 112L0 400c0 26.5 21.5 48 48 48l32 0c26.5 0 48-21.5 48-48l0-288c0-26.5-21.5-48-48-48L48 64zm192 0c-26.5 0-48 21.5-48 48l0 288c0 26.5 21.5 48 48 48l32 0c26.5 0 48-21.5 48-48l0-288c0-26.5-21.5-48-48-48l-32 0z";

// Load track data from the PHP endpoint.  The collection ID is read
// from the `id` query parameter in the current page's URL.  If no
// collection ID is present, no tracks will be loaded.
async function loadTrackData() {
  try {
    const params = new URLSearchParams(window.location.search);
    const collectionId = params.get('id');
    if (!collectionId) {
      tracks = [];
      return;
    }
    const resp = await fetch(`vibeplayer.php?collectionId=${encodeURIComponent(collectionId)}`);
    if (!resp.ok) {
      console.error('Failed to fetch playlist:', resp.status);
      tracks = [];
      return;
    }
    const data = await resp.json();
    if (Array.isArray(data)) {
      tracks = data;
    } else {
      tracks = [];
    }
  } catch (err) {
    console.error('Error loading tracks:', err);
    tracks = [];
  }
}

// Build the playlist UI
function populateTrackList() {
  const list = document.getElementById('trackList');
  if (!list) return;
  list.innerHTML = '';
  tracks.forEach((track, index) => {
    const li = document.createElement('li');
    li.textContent = track.title;
    li.addEventListener('click', () => {
      loadTrack(index);
      play();
    });
    list.appendChild(li);
  });
  updateTrackHighlight();
}

// Highlight the current track in the list
function updateTrackHighlight() {
  const list = document.getElementById('trackList');
  if (!list) return;
  Array.from(list.children).forEach((li, idx) => {
    li.classList.toggle('active', idx === currentIndex);
  });
}

// Update displayed track info (title and cover)
function updateTrackInfo() {
  const track = tracks[currentIndex];
  if (!track) return;
  const miniInfo  = document.getElementById('miniTrackInfo');
  const fullInfo  = document.getElementById('fullTrackInfo');
  const miniCover = document.getElementById('miniCover');
  const fullCover = document.getElementById('fullCover');
  if (miniInfo) miniInfo.textContent = track.title;
  if (fullInfo) fullInfo.textContent = track.title;
  const coverUrl = track.cover || '';
  if (miniCover && coverUrl) miniCover.src = coverUrl;
  if (fullCover && coverUrl) fullCover.src = coverUrl;
  if (miniCover && !coverUrl) miniCover.src = 'assets/icons/icon-192.png';
  if (fullCover && !coverUrl) fullCover.src = 'assets/icons/icon-192.png';
}

// Load a track by index
function loadTrack(index) {
  if (!tracks || index < 0 || index >= tracks.length) return;
  currentIndex = index;
  const track = tracks[currentIndex];
  if (!track || !track.src) return;
  updateTrackHighlight();
  updateTrackInfo();
  // Set new source and load
  audio.src = track.src;
  audio.load();
  // Reset progress
  const miniBar = document.getElementById('miniProgress');
  if (miniBar) miniBar.style.width = '0%';
  const fullBar = document.getElementById('fullProgress');
  if (fullBar) fullBar.style.width = '0%';
}

// Play the current track
function play() {
  if (!tracks || tracks.length === 0) return;
  audio.play();
  isPlaying = true;
  // Update play buttons to pause icons
  updatePlayButtons(true);
}

// Pause the current track
function pause() {
  audio.pause();
  isPlaying = false;
  // Update buttons to play icons
  updatePlayButtons(false);
}

// Toggle play/pause
function togglePlayPause() {
  if (isPlaying) {
    pause();
  } else {
    play();
  }
}

// Move to the next track (wrap around)
function nextTrack() {
  if (!tracks || tracks.length === 0) return;
  currentIndex = (currentIndex + 1) % tracks.length;
  loadTrack(currentIndex);
  if (isPlaying) play();
}

// Update play/pause button icons
function updatePlayButtons(isPlayingNow) {
  const miniPlay = document.getElementById('miniPlayPauseBtn');
  const fullPlay = document.getElementById('fullPlayPauseBtn');
  const playPath  = document.getElementById('playIconPath');
  const pausePath = document.getElementById('pauseIconPath');
  // Buttons contain inline SVG; update path to reflect state
  function updateBtn(btn) {
    if (!btn) return;
    const svgPath = btn.querySelector('svg.icon path');
    if (!svgPath) return;
    if (isPlayingNow) {
      svgPath.setAttribute('d', PAUSE_PATH);
      btn.setAttribute('aria-label', 'Pause');
    } else {
      svgPath.setAttribute('d', PLAY_PATH);
      btn.setAttribute('aria-label', 'Play');
    }
  }
  updateBtn(miniPlay);
  updateBtn(fullPlay);
}

// Update progress bars based on current time
function updateProgress() {
  if (!audio || !tracks || tracks.length === 0) return;
  const duration = audio.duration;
  const current  = audio.currentTime;
  let percent = 0;
  if (duration && duration > 0) {
    percent = (current / duration) * 100;
  }
  const miniBar = document.getElementById('miniProgress');
  const fullBar = document.getElementById('fullProgress');
  if (miniBar) miniBar.style.width = `${percent}%`;
  if (fullBar) fullBar.style.width = `${percent}%`;
}

// Expand the full player overlay
function expandFullPlayer() {
  const fullPlayer = document.getElementById('fullPlayer');
  if (!fullPlayer) return;
  fullPlayer.classList.remove('collapsed');
  fullPlayer.classList.add('expanded');
  const mini = document.getElementById('miniPlayer');
  if (mini) mini.style.display = 'none';
}

// Collapse the full player overlay
function collapseFullPlayer() {
  const fullPlayer = document.getElementById('fullPlayer');
  if (!fullPlayer) return;
  fullPlayer.classList.remove('expanded');
  fullPlayer.classList.add('collapsed');
  const mini = document.getElementById('miniPlayer');
  if (mini) mini.style.display = 'flex';
}

// Initialize the player after DOM is ready
document.addEventListener('DOMContentLoaded', async () => {
  await loadTrackData();
  if (!tracks || tracks.length === 0) {
    console.warn('No tracks loaded');
    return;
  }
  // Load first track but do not start playing
  loadTrack(0);
  isReady = true;
  // Populate playlist UI
  populateTrackList();
  updateTrackInfo();
  // Hook up UI controls
  const miniPlay = document.getElementById('miniPlayPauseBtn');
  const fullPlay = document.getElementById('fullPlayPauseBtn');
  const miniNext = document.getElementById('miniNextBtn');
  const fullNext = document.getElementById('fullNextBtn');
  const expandBtn = document.getElementById('expandBtn');
  const collapseBtn = document.getElementById('collapseBtn');
  if (miniPlay) miniPlay.addEventListener('click', togglePlayPause);
  if (fullPlay) fullPlay.addEventListener('click', togglePlayPause);
  if (miniNext) miniNext.addEventListener('click', nextTrack);
  if (fullNext) fullNext.addEventListener('click', nextTrack);
  if (expandBtn) expandBtn.addEventListener('click', expandFullPlayer);
  if (collapseBtn) collapseBtn.addEventListener('click', collapseFullPlayer);
  // Progress bar seek events
  const miniProgressContainer = document.getElementById('miniProgressContainer');
  const fullProgressContainer = document.getElementById('fullProgressContainer');
  if (miniProgressContainer) {
    miniProgressContainer.addEventListener('click', (e) => {
      if (!isReady) return;
      const rect = miniProgressContainer.getBoundingClientRect();
      const percent = (e.clientX - rect.left) / rect.width;
      if (audio.duration > 0) {
        audio.currentTime = percent * audio.duration;
        updateProgress();
      }
    });
  }
  if (fullProgressContainer) {
    fullProgressContainer.addEventListener('click', (e) => {
      if (!isReady) return;
      const rect = fullProgressContainer.getBoundingClientRect();
      const percent = (e.clientX - rect.left) / rect.width;
      if (audio.duration > 0) {
        audio.currentTime = percent * audio.duration;
        updateProgress();
      }
    });
  }
  // Handle audio events
  audio.addEventListener('ended', () => {
    logEvent('ended');
    nextTrack();
  });
  audio.addEventListener('error', () => {
    logEvent('error', { error: audio.error });
    if (!isPlaying) return;
    nextTrack();
  });
  audio.addEventListener('stalled', () => {
    logEvent('stalled');
  });
  audio.addEventListener('waiting', () => {
    logEvent('waiting');
  });
  audio.addEventListener('suspend', () => {
    logEvent('suspend');
  });
  audio.addEventListener('playing', () => {
    logEvent('playing');
  });
  audio.addEventListener('pause', () => {
    logEvent('pause');
  });
  audio.addEventListener('loadedmetadata', () => {
    logEvent('loadedmetadata');
  });
  audio.addEventListener('timeupdate', () => {
    updateProgress();
  });
});
