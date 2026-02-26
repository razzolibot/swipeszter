<template>
  <div class="video-card" :class="{ active: isActive }">
    <!-- Videó lejátszó (src-t a hls.js állítja be, nem Vue) -->
    <video
      ref="videoEl"
      class="video-player"
      :poster="video.thumbnail_url"
      loop
      playsinline
      muted
      @click="onVideoClick"
      @timeupdate="onTimeUpdate"
    />

    <!-- Play ikon ha szüneteltetve -->
    <transition name="fade">
      <div v-if="paused" class="play-overlay">
        <span>▶</span>
      </div>
    </transition>

    <!-- Double-tap szívecske animáció -->
    <transition name="heart-pop">
      <div v-if="heartAnim" class="heart-anim">❤️</div>
    </transition>

    <!-- Progress bar -->
    <div class="progress-bar">
      <div class="progress-fill" :style="{ width: progress + '%' }" />
    </div>

    <!-- Jobb oldali akció gombok -->
    <div class="action-buttons">
      <!-- Avatar + követés -->
      <div class="action-avatar" @click="goToProfile">
        <img v-if="video.user.avatar" :src="`/storage/${video.user.avatar}`" class="avatar" />
        <div v-else class="avatar-placeholder">{{ video.user.username?.[0]?.toUpperCase() }}</div>
        <button v-if="auth.user && auth.user.id !== video.user.id" class="follow-btn" @click.stop="toggleFollow">
          {{ following ? '✓' : '+' }}
        </button>
      </div>

      <!-- Like -->
      <div class="action-item" @click="handleLike">
        <span class="action-icon" :class="{ liked: video.is_liked }">❤️</span>
        <span class="action-count">{{ formatCount(video.likes_count) }}</span>
      </div>

      <!-- Komment -->
      <div class="action-item" @click="$emit('open-comments', video.id)">
        <span class="action-icon">💬</span>
        <span class="action-count">{{ formatCount(video.comments_count) }}</span>
      </div>

      <!-- Megtekintések -->
      <div class="action-item">
        <span class="action-icon">👁️</span>
        <span class="action-count">{{ formatCount(video.views_count) }}</span>
      </div>
    </div>

    <!-- Alsó info -->
    <div class="video-info">
      <div class="username" @click="goToProfile">@{{ video.user.username }}</div>
      <div v-if="video.title" class="video-title">{{ video.title }}</div>
      <div v-if="video.description" class="video-desc">
        <HashtagText :text="video.description" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useFeedStore } from '@/stores/feed'
import { useAuthStore } from '@/stores/auth'
import HashtagText from '@/components/HashtagText.vue'
import api from '@/api'
// hls.js lazy import — csak Chrome/Firefox-ban töltődik be
let HlsLib = null
async function getHls() {
  if (!HlsLib) {
    const mod = await import('hls.js')
    HlsLib = mod.default
  }
  return HlsLib
}

const props = defineProps({
  video: Object,
  isActive: Boolean,
})
const emit = defineEmits(['open-comments'])

const router    = useRouter()
const feed      = useFeedStore()
const auth      = useAuthStore()
const videoEl   = ref(null)
const paused    = ref(false)
const progress  = ref(0)
const following = ref(false)
const heartAnim = ref(false)  // double-tap like animáció
let hls = null
let lastTap = 0

// ─── HLS betöltés ────────────────────────────────────────────────
onMounted(() => {
  if (!videoEl.value || !props.video.hls_url) return
  initHls()
})

onUnmounted(() => {
  hls?.destroy()
  hls = null
})

async function initHls() {
  const src = props.video.hls_url
  const Hls = await getHls()

  if (Hls.isSupported()) {
    // Chrome, Firefox, Edge → hls.js
    hls = new Hls({
      enableWorker: true,
      lowLatencyMode: false,
      backBufferLength: 10,
    })
    hls.loadSource(src)
    hls.attachMedia(videoEl.value)
    hls.on(Hls.Events.MANIFEST_PARSED, () => {
      if (props.isActive) videoEl.value?.play().catch(() => {})
    })
    hls.on(Hls.Events.ERROR, (_, data) => {
      if (data.fatal) hls?.destroy()
    })
  } else if (videoEl.value.canPlayType('application/vnd.apple.mpegurl')) {
    // Safari → natív HLS támogatás, hls.js nem kell
    videoEl.value.src = src
    if (props.isActive) videoEl.value.play().catch(() => {})
  }
}

// ─── Aktív/inaktív kezelés ───────────────────────────────────────
watch(() => props.isActive, (active) => {
  if (!videoEl.value) return
  if (active) {
    videoEl.value.play().catch(() => {})
    paused.value = false
    api.post(`/videos/${props.video.id}/view`, { watched_percent: 0 }).catch(() => {})
  } else {
    videoEl.value.pause()
    videoEl.value.currentTime = 0
    paused.value = false
  }
})

// ─── Play/pause kattintásra + double-tap like ────────────────────
function onVideoClick() {
  const now = Date.now()
  if (now - lastTap < 300) {
    // Double-tap → like
    doubleTapLike()
  } else {
    // Single tap → play/pause
    togglePlay()
  }
  lastTap = now
}

function togglePlay() {
  if (!videoEl.value) return
  if (videoEl.value.paused) {
    videoEl.value.play().catch(() => {})
    paused.value = false
  } else {
    videoEl.value.pause()
    paused.value = true
  }
}

async function doubleTapLike() {
  if (!auth.token) { router.push('/login'); return }
  if (!props.video.is_liked) {
    heartAnim.value = true
    setTimeout(() => heartAnim.value = false, 1000)
    await feed.toggleLike(props.video)
  }
}

function onTimeUpdate() {
  if (!videoEl.value?.duration) return
  progress.value = (videoEl.value.currentTime / videoEl.value.duration) * 100
}

// ─── Like / Follow / Navigate ────────────────────────────────────
async function handleLike() {
  if (!auth.token) { router.push('/login'); return }
  await feed.toggleLike(props.video)
}

async function toggleFollow() {
  if (!auth.token) { router.push('/login'); return }
  const { data } = await api.post(`/users/${props.video.user.id}/follow`)
  following.value = data.following
}

function goToProfile() {
  router.push(`/@${props.video.user.username}`)
}

function formatCount(n) {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
  return n
}
</script>

<style scoped>
.video-card {
  position: relative;
  height: 100dvh;
  width: 100%;
  background: #000;
  scroll-snap-align: start;
  overflow: hidden;
}

.video-player {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.play-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,.3);
  font-size: 64px;
}

.fade-enter-active, .fade-leave-active { transition: opacity .2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Double-tap szívecske */
.heart-anim {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 100px;
  pointer-events: none;
  z-index: 50;
}
.heart-pop-enter-active {
  animation: heartPop .8s ease forwards;
}
.heart-pop-leave-active {
  transition: opacity .2s;
}
.heart-pop-leave-to { opacity: 0; }

@keyframes heartPop {
  0%   { transform: translate(-50%, -50%) scale(0);   opacity: 1; }
  50%  { transform: translate(-50%, -50%) scale(1.3); opacity: 1; }
  100% { transform: translate(-50%, -50%) scale(1);   opacity: 0; }
}

/* Progress bar */
.progress-bar {
  position: absolute;
  bottom: 61px;
  left: 0; right: 0;
  height: 2px;
  background: rgba(255,255,255,.3);
}
.progress-fill {
  height: 100%;
  background: #fe2c55;
  transition: width .1s linear;
}

/* Akció gombok */
.action-buttons {
  position: absolute;
  right: 12px;
  bottom: 120px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.action-avatar {
  position: relative;
  cursor: pointer;
}

.avatar, .avatar-placeholder {
  width: 48px; height: 48px;
  border-radius: 50%;
  border: 2px solid #fff;
  object-fit: cover;
}

.avatar-placeholder {
  background: #fe2c55;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 18px;
}

.follow-btn {
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 20px; height: 20px;
  border-radius: 50%;
  background: #fe2c55;
  border: none;
  color: #fff;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  gap: 4px;
}

.action-icon { font-size: 32px; transition: transform .1s; }
.action-icon:active { transform: scale(1.3); }
.action-icon.liked { filter: drop-shadow(0 0 6px #fe2c55); }

.action-count {
  font-size: 12px;
  font-weight: 600;
  text-shadow: 0 1px 3px rgba(0,0,0,.8);
}

/* Alsó info */
.video-info {
  position: absolute;
  bottom: 72px;
  left: 12px;
  right: 80px;
  text-shadow: 0 1px 3px rgba(0,0,0,.8);
}

.username {
  font-weight: 700;
  font-size: 16px;
  margin-bottom: 4px;
  cursor: pointer;
}

.video-title {
  font-weight: 600;
  font-size: 15px;
  margin-bottom: 4px;
}

.video-desc {
  font-size: 14px;
  opacity: .9;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
