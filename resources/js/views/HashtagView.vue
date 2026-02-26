<template>
  <div class="hashtag-page">
    <div class="hashtag-header">
      <button class="back-btn" @click="router.back()">←</button>
      <div class="header-info">
        <h1 class="hashtag-title">#{{ slug }}</h1>
        <span v-if="hashtag" class="videos-count">{{ formatCount(hashtag.videos_count) }} videó</span>
      </div>
    </div>

    <div v-if="loading && !videos.length" class="loading">
      <div class="spinner" />
    </div>

    <div v-else-if="!videos.length" class="empty">
      <span>🏷️</span>
      <p>Még nincs videó ezzel a hashtaggel</p>
    </div>

    <!-- Videók rácsban (mint a profil oldal) -->
    <div v-else class="videos-grid" ref="gridEl">
      <div
        v-for="v in videos"
        :key="v.id"
        class="video-thumb"
        @click="openVideo(v)"
      >
        <img :src="v.thumbnail_url || ''" class="thumb-img" loading="lazy" />
        <div class="thumb-overlay">
          <span>▶ {{ formatCount(v.views_count) }}</span>
        </div>
      </div>
    </div>

    <div v-if="loading && videos.length" class="load-more-spinner">
      <div class="spinner" />
    </div>

    <!-- Videó lejátszó modal -->
    <div v-if="activeVideo" class="video-modal" @click.self="activeVideo = null">
      <div class="modal-inner">
        <button class="modal-close" @click="activeVideo = null">✕</button>
        <VideoCard :video="activeVideo" :is-active="true" @open-comments="activeVideo = null" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import VideoCard from '@/components/VideoCard.vue'
import api from '@/api'

const route  = useRoute()
const router = useRouter()
const slug   = route.params.slug

const hashtag    = ref(null)
const videos     = ref([])
const loading    = ref(true)
const nextPage   = ref(null)
const hasMore    = ref(true)
const activeVideo = ref(null)
const gridEl     = ref(null)

onMounted(async () => {
  await loadVideos()
  window.addEventListener('scroll', onScroll, { passive: true })
})

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
})

async function loadVideos() {
  if (loading.value && videos.value.length) return
  loading.value = true

  try {
    const url = nextPage.value
      ? new URL(nextPage.value).pathname + new URL(nextPage.value).search
      : `/api/hashtags/${slug}/videos`

    const { data } = await api.get(url)
    hashtag.value = data.hashtag
    videos.value.push(...data.data)
    nextPage.value = data.next_page ?? null
    hasMore.value  = !!data.next_page
  } finally {
    loading.value = false
  }
}

function onScroll() {
  const nearBottom = window.innerHeight + window.scrollY >= document.body.offsetHeight - 300
  if (nearBottom && hasMore.value && !loading.value) loadVideos()
}

function openVideo(video) {
  activeVideo.value = video
}

function formatCount(n) {
  if (!n) return '0'
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
  return n
}
</script>

<style scoped>
.hashtag-page {
  min-height: 100dvh;
  background: #000;
  color: #fff;
  padding-bottom: 20px;
}

.hashtag-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255,255,255,.1);
  position: sticky;
  top: 0;
  background: rgba(0,0,0,.9);
  backdrop-filter: blur(10px);
  z-index: 10;
}

.back-btn {
  background: none;
  border: none;
  color: #fff;
  font-size: 22px;
  cursor: pointer;
  flex-shrink: 0;
}

.hashtag-title {
  font-size: 22px;
  font-weight: 800;
  color: #fe2c55;
}

.videos-count {
  font-size: 13px;
  color: #888;
  display: block;
  margin-top: 2px;
}

.loading, .empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  min-height: 50dvh;
  color: #888;
}

.empty span { font-size: 48px; }

.spinner {
  width: 36px; height: 36px;
  border: 3px solid rgba(255,255,255,.2);
  border-top-color: #fe2c55;
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.videos-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2px;
  padding-top: 2px;
}

.video-thumb {
  position: relative;
  aspect-ratio: 9/16;
  background: #111;
  overflow: hidden;
  cursor: pointer;
}

.thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: opacity .2s;
}
.video-thumb:hover .thumb-img { opacity: .8; }

.thumb-overlay {
  position: absolute;
  bottom: 6px;
  left: 6px;
  font-size: 12px;
  font-weight: 600;
  text-shadow: 0 1px 3px rgba(0,0,0,.9);
}

.load-more-spinner {
  display: flex;
  justify-content: center;
  padding: 20px;
}

/* Videó modal */
.video-modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.9);
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-inner {
  position: relative;
  width: min(400px, 100vw);
  height: 100dvh;
}

.modal-close {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 210;
  background: rgba(0,0,0,.6);
  border: none;
  color: #fff;
  width: 36px; height: 36px;
  border-radius: 50%;
  font-size: 16px;
  cursor: pointer;
}
</style>
