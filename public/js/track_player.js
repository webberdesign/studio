/*  PAGE NAME: track_player.js
    SECTION: Tracklist Player for Collections and Unreleased Music
------------------------------------------------------------*/

const TRACK_PLAY_PATH =
  "M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80v352c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z";
const TRACK_PAUSE_PATH =
  "M48 64C21.5 64 0 85.5 0 112v288c0 26.5 21.5 48 48 48h32c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48H48zm192 0c-26.5 0-48 21.5-48 48v288c0 26.5 21.5 48 48 48h32c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48h-32z";
const TRACK_PLAYER_STATE_KEY = "tbTrackPlayerState";

const getTracksFromContainer = (container) => {
  const tracksData = container.dataset.tracks || "[]";
  try {
    const parsed = JSON.parse(tracksData);
    return Array.isArray(parsed) ? parsed : [];
  } catch (error) {
    return [];
  }
};

const getSourceType = (src) => {
  if (!src) return "";
  let filename = src;
  try {
    const url = new URL(src, window.location.href);
    filename = url.pathname;
  } catch (error) {
    filename = src;
  }
  const ext = filename.split(".").pop()?.toLowerCase() || "";
  if (ext === "mp3") return "audio/mpeg";
  if (ext === "m4a") return "audio/mp4";
  if (ext === "aac") return "audio/aac";
  return "";
};

const buildTracklists = () => {
  const tracklistContainers = Array.from(document.querySelectorAll("[data-tracklist]"));
  return tracklistContainers.map((container, index) => ({
    id: container.dataset.tracklistId || container.id || `tracklist-${index}`,
    container,
    tracks: getTracksFromContainer(container),
    rows: Array.from(container.querySelectorAll("[data-track-index]")),
    currentIndex: null,
  }));
};

const createTrackPlayerInstance = (player) => {
  const prevBtn = player.querySelector("[data-track-prev]");
  const playBtn = player.querySelector("[data-track-play]");
  const nextBtn = player.querySelector("[data-track-next]");
  const currentLabel = player.querySelector("[data-track-current]");
  const currentFile = player.querySelector("[data-track-file]");
  const coverImg = player.querySelector("[data-track-cover]");
  const playIcon = playBtn ? playBtn.querySelector("svg path") : null;

  let tracklists = [];
  let activeListId = null;
  let activeIndex = null;
  let isPlaying = false;
  const audio = new Audio();
  let currentSource = "";
  let pendingSeekTime = null;
  let lastSavedTime = 0;
  let hasRestored = false;

  const getPlayableSource = (track) => {
    if (!track) return "";
    const sources = [track.mp3, track.m4a, track.src];

    for (const source of sources) {
      if (!source) continue;
      const type = getSourceType(source);
      if (!type) return source;
      const support = audio.canPlayType(type);
      if (support === "maybe" || support === "probably") {
        return source;
      }
    }

    return "";
  };

  const getActiveList = () =>
    tracklists.find((list) => list.id === activeListId) || null;

  const updateRows = () => {
    const activeList = getActiveList();
    tracklists.forEach((list) => {
      list.rows.forEach((row, index) => {
        const isActiveList = list === activeList;
        row.classList.toggle(
          "is-playing",
          isActiveList && isPlaying && index === activeIndex
        );
      });
    });
  };

  const updatePlayerInfo = () => {
    const activeList = getActiveList();
    if (!activeList) return;
    const track = activeList.tracks[activeIndex];
    if (!track) return;
    if (currentLabel) currentLabel.textContent = track.title;
    if (currentFile) {
      if (currentSource) {
        let filename = currentSource;
        try {
          const url = new URL(currentSource, window.location.href);
          filename = url.pathname.split("/").pop() || currentSource;
        } catch (error) {
          filename = currentSource.split("/").pop() || currentSource;
        }
        currentFile.textContent = decodeURIComponent(filename);
      } else {
        currentFile.textContent = "";
      }
    }
    if (coverImg) {
      coverImg.src = track.cover || "assets/icons/icon-192.png";
    }
  };

  const updatePlayIcon = (playing) => {
    if (!playIcon) return;
    playIcon.setAttribute("d", playing ? TRACK_PAUSE_PATH : TRACK_PLAY_PATH);
    playBtn.setAttribute("aria-label", playing ? "Pause" : "Play");
  };

  const saveState = () => {
    if (!activeListId || activeIndex === null) return;
    const state = {
      listId: activeListId,
      index: activeIndex,
      time: audio.currentTime || 0,
      wasPlaying: isPlaying,
    };
    sessionStorage.setItem(TRACK_PLAYER_STATE_KEY, JSON.stringify(state));
  };

  const clearState = () => {
    sessionStorage.removeItem(TRACK_PLAYER_STATE_KEY);
  };

  const loadTrack = (list, index) => {
    if (!list) return;
    if (index < 0 || index >= list.tracks.length) return;
    if (!audio.paused) {
      audio.pause();
    }
    activeListId = list.id;
    activeIndex = index;
    const track = list.tracks[index];
    const source = getPlayableSource(track);
    pendingSeekTime = null;
    currentSource = source;
    audio.src = source;
    if (source) {
      audio.load();
    }
    updatePlayerInfo();
    player.classList.remove("is-hidden");
    saveState();
  };

  const findPlayableIndex = (list, startIndex, direction) => {
    if (!list || !list.tracks.length) return null;
    let index = startIndex;
    for (let i = 0; i < list.tracks.length; i += 1) {
      const track = list.tracks[index];
      if (track && getPlayableSource(track)) {
        return index;
      }
      index = (index + direction + list.tracks.length) % list.tracks.length;
    }
    return null;
  };

  const ensureActiveList = () => {
    const activeList = getActiveList();
    if (activeList) return activeList;
    const firstList = tracklists.find((list) => list.tracks.length > 0);
    if (!firstList) return null;
    activeListId = firstList.id;
    return firstList;
  };

  const play = () => {
    const activeList = ensureActiveList();
    if (!activeList) return;
    if (activeIndex === null) {
      const firstPlayable = findPlayableIndex(activeList, 0, 1);
      if (firstPlayable === null) {
        if (currentFile) {
          currentFile.textContent = "Unsupported audio format";
        }
        return;
      }
      loadTrack(activeList, firstPlayable);
    }
    if (!audio.src) {
      if (currentFile) {
        currentFile.textContent = "Unsupported audio format";
      }
      return;
    }
    const playPromise = audio.play();
    isPlaying = true;
    player.classList.remove("is-hidden");
    updatePlayIcon(true);
    updateRows();
    saveState();
    if (playPromise && typeof playPromise.catch === "function") {
      playPromise.catch((error) => {
        isPlaying = false;
        updatePlayIcon(false);
        updateRows();
        if (currentFile && error && error.name === "NotSupportedError") {
          currentFile.textContent = "Unsupported audio format";
        }
      });
    }
  };

  const pause = () => {
    audio.pause();
    isPlaying = false;
    updatePlayIcon(false);
    updateRows();
    saveState();
  };

  const togglePlay = () => {
    if (isPlaying) {
      pause();
    } else {
      play();
    }
  };

  const advanceToNextTrack = () => {
    const activeList = getActiveList();
    if (!activeList) return;
    const startIndex = activeIndex === null ? 0 : activeIndex + 1;
    const nextIndex = findPlayableIndex(
      activeList,
      startIndex % activeList.tracks.length,
      1
    );
    if (nextIndex === null) return;
    loadTrack(activeList, nextIndex);
    play();
  };

  const nextTrack = () => {
    const shouldResume = isPlaying;
    advanceToNextTrack();
    if (!shouldResume) {
      pause();
    }
  };

  const prevTrack = () => {
    const activeList = getActiveList();
    if (!activeList) return;
    const startIndex =
      activeIndex === null ? activeList.tracks.length - 1 : activeIndex - 1;
    const prevIndex = findPlayableIndex(
      activeList,
      (startIndex + activeList.tracks.length) % activeList.tracks.length,
      -1
    );
    if (prevIndex === null) return;
    loadTrack(activeList, prevIndex);
    if (isPlaying) play();
  };

  const attachRowHandlers = () => {
    tracklists.forEach((list) => {
      list.rows.forEach((row) => {
        row.querySelectorAll("a").forEach((link) => {
          link.addEventListener("click", (event) => {
            event.stopPropagation();
          });
        });
        row.addEventListener("click", () => {
          const index = Number(row.dataset.trackIndex);
          if (Number.isNaN(index)) return;
          loadTrack(list, index);
          play();
        });
      });
    });
  };

  const restoreState = () => {
    if (hasRestored) return;
    hasRestored = true;
    const savedState = sessionStorage.getItem(TRACK_PLAYER_STATE_KEY);
    if (!savedState) return;
    let parsed = null;
    try {
      parsed = JSON.parse(savedState);
    } catch (error) {
      clearState();
      return;
    }
    if (!parsed || !parsed.listId) return;
    const list = tracklists.find((item) => item.id === parsed.listId);
    if (!list) return;
    const index = Number(parsed.index);
    if (Number.isNaN(index)) return;
    pendingSeekTime = typeof parsed.time === "number" ? parsed.time : null;
    loadTrack(list, index);
    player.classList.remove("is-hidden");
    updatePlayIcon(false);
    updateRows();
  };

  const setTracklists = (lists) => {
    tracklists = lists;
    attachRowHandlers();

    const hasTracks = tracklists.some((list) => list.tracks.length > 0);
    const hasSavedState = Boolean(sessionStorage.getItem(TRACK_PLAYER_STATE_KEY));
    if (!hasTracks && !isPlaying && !hasSavedState) {
      player.style.display = "none";
      return;
    }

    player.style.display = "";
    updateRows();
  };

  if (playBtn) playBtn.addEventListener("click", togglePlay);
  if (nextBtn) nextBtn.addEventListener("click", nextTrack);
  if (prevBtn) prevBtn.addEventListener("click", prevTrack);

  audio.addEventListener("ended", () => {
    advanceToNextTrack();
  });

  audio.addEventListener("loadedmetadata", () => {
    if (pendingSeekTime !== null) {
      audio.currentTime = pendingSeekTime;
      pendingSeekTime = null;
    }
  });

  audio.addEventListener("timeupdate", () => {
    if (!activeListId || activeIndex === null) return;
    if (Math.abs(audio.currentTime - lastSavedTime) >= 1) {
      lastSavedTime = audio.currentTime;
      saveState();
    }
  });

  updatePlayIcon(false);
  updateRows();

  window.tbSharedTrackPlayer = {
    pause,
    play,
    get isPlaying() {
      return isPlaying;
    },
  };

  return {
    player,
    setTracklists,
    restoreState,
  };
};

const initTrackPlayer = () => {
  const tracklists = buildTracklists();
  const sharedPlayer = document.querySelector("[data-track-player-global]");
  const fallbackPlayer =
    !sharedPlayer && tracklists.length === 1
      ? tracklists[0].container.querySelector("[data-track-player]")
      : null;
  const player = sharedPlayer || fallbackPlayer;

  if (!player) return;

  if (sharedPlayer) {
    tracklists.forEach((list) => {
      const localPlayer = list.container.querySelector("[data-track-player]");
      if (localPlayer) {
        localPlayer.style.display = "none";
      }
    });
  }

  let instance = window.tbTrackPlayerInstance;
  if (!instance || instance.player !== player) {
    instance = createTrackPlayerInstance(player);
    window.tbTrackPlayerInstance = instance;
  }

  instance.setTracklists(tracklists);
  instance.restoreState();
};

document.addEventListener("DOMContentLoaded", () => {
  initTrackPlayer();
});

window.tbInitTrackPlayer = initTrackPlayer;
