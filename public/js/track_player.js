/*  PAGE NAME: track_player.js
    SECTION: Tracklist Player for Collections and Unreleased Music
------------------------------------------------------------*/

const TRACK_PLAY_PATH =
  "M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80v352c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z";
const TRACK_PAUSE_PATH =
  "M48 64C21.5 64 0 85.5 0 112v288c0 26.5 21.5 48 48 48h32c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48H48zm192 0c-26.5 0-48 21.5-48 48v288c0 26.5 21.5 48 48 48h32c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48h-32z";

const initTracklistPlayer = (container) => {
  const tracksData = container.dataset.tracks || "[]";
  let tracks = [];
  try {
    tracks = JSON.parse(tracksData);
  } catch (error) {
    tracks = [];
  }

  const rows = Array.from(container.querySelectorAll("[data-track-index]"));
  const player = container.querySelector("[data-track-player]");
  if (!player || tracks.length === 0) {
    if (player) {
      player.style.display = "none";
    }
    return;
  }

  const prevBtn = player.querySelector("[data-track-prev]");
  const playBtn = player.querySelector("[data-track-play]");
  const nextBtn = player.querySelector("[data-track-next]");
  const currentLabel = player.querySelector("[data-track-current]");
  const coverImg = player.querySelector("[data-track-cover]");
  const playIcon = playBtn ? playBtn.querySelector("svg path") : null;

  let currentIndex = 0;
  let isPlaying = false;
  const audio = new Audio();

  const updateRows = () => {
    rows.forEach((row, index) => {
      row.classList.toggle("is-playing", isPlaying && index === currentIndex);
    });
  };

  const updatePlayerInfo = () => {
    const track = tracks[currentIndex];
    if (!track) return;
    if (currentLabel) currentLabel.textContent = track.title;
    if (coverImg) {
      coverImg.src = track.cover || "assets/icons/icon-192.png";
    }
  };

  const updatePlayIcon = (playing) => {
    if (!playIcon) return;
    playIcon.setAttribute("d", playing ? TRACK_PAUSE_PATH : TRACK_PLAY_PATH);
    playBtn.setAttribute("aria-label", playing ? "Pause" : "Play");
  };

  const loadTrack = (index) => {
    if (index < 0 || index >= tracks.length) return;
    currentIndex = index;
    const track = tracks[currentIndex];
    if (!track || !track.src) return;
    audio.src = track.src;
    audio.load();
    updatePlayerInfo();
  };

  const play = () => {
    if (!tracks.length) return;
    if (!audio.src) {
      loadTrack(currentIndex);
    }
    audio.play();
    isPlaying = true;
    updatePlayIcon(true);
    updateRows();
  };

  const pause = () => {
    audio.pause();
    isPlaying = false;
    updatePlayIcon(false);
    updateRows();
  };

  const togglePlay = () => {
    if (isPlaying) {
      pause();
    } else {
      play();
    }
  };

  const nextTrack = () => {
    currentIndex = (currentIndex + 1) % tracks.length;
    loadTrack(currentIndex);
    if (isPlaying) play();
  };

  const prevTrack = () => {
    currentIndex = (currentIndex - 1 + tracks.length) % tracks.length;
    loadTrack(currentIndex);
    if (isPlaying) play();
  };

  rows.forEach((row) => {
    row.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", (event) => {
        event.stopPropagation();
      });
    });
    row.addEventListener("click", () => {
      const index = Number(row.dataset.trackIndex);
      if (Number.isNaN(index)) return;
      loadTrack(index);
      play();
    });
  });

  if (playBtn) playBtn.addEventListener("click", togglePlay);
  if (nextBtn) nextBtn.addEventListener("click", nextTrack);
  if (prevBtn) prevBtn.addEventListener("click", prevTrack);

  audio.addEventListener("ended", () => {
    nextTrack();
    play();
  });

  loadTrack(0);
  updatePlayIcon(false);
};

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-tracklist]").forEach((container) => {
    initTracklistPlayer(container);
  });
});
