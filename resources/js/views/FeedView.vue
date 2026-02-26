<template>
  <div class="feed-container" ref="feedEl">
    <VideoCard
      v-for="(video, i) in feed.videos"
      :key="video.id"
      :video="video"
      :is-active="currentIndex === i"
      @open-comments="openComments"
    />

    <!-- Betöltés spinner -->
    <div v-if="feed.loading" class="loading-spinner">
      <div class="spinner" />
    </div>
  </div>

  <!-- Navigációs sáv -->
  <nav class="bottom-nav">
    <router-link to="/" class="nav-btn active">
      <span class="icon">🏠</span>
    </router-link>
    <router-link to="/upload" class="nav-btn">
      <span class="icon plus">＋</span>
    </router-link>
    <router-link v-if="auth.user" :to="`/@${auth.user.username}`" class="nav-btn">
      <img v-if="auth.user.avatar" :src="`/storage/${auth.user.avatar}`" class="nav-avatar" />
      <span v-else class="icon">👤</span>
    </router-link>
    <router-link v-else to="/login" class="nav-btn">
      <span class="icon">👤</span>
    </router-link>
  </nav>

  <!-- Komment panel -->
  <CommentPanel
    v-if="commentVideoId"
    :video-id="commentVideoId"
    @close="commentVideoId = null"
  />
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useSwipe } from '@vueuse/core'
import { useFeedStore } from '@/stores/feed'
import { useAuthStore } from '@/stores/auth'
import VideoCard from '@/components/VideoCard.vue'
import CommentPanel from '@/components/CommentPanel.vue'

const feed = useFeedStore()
const auth = useAuthStore()
const feedEl = ref(null)
const currentIndex = ref(0)
const commentVideoId = ref(null)

onMounted(async () => {
  feed.reset()
  await feed.loadMore()
  setupScroll()
})

onUnmounted(() => {
  feedEl.value?.removeEventListener('scroll', onScroll)
})

function setupScroll() {
  feedEl.value?.addEventListener('scroll', onScroll, { passive: true })
}

function onScroll() {
  const el = feedEl.value
  if (!el) return
  const idx = Math.round(el.scrollTop / window.innerHeight)
  currentIndex.value = idx

  // Betöltés ha közel az aljához
  const nearBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - window.innerHeight * 2
  if (nearBottom) feed.loadMore()
}

function openComments(videoId) {
  commentVideoId.value = videoId
}
</script>

<style scoped>
.feed-container {
  height: 100dvh;
  overflow-y: scroll;
  scroll-snap-type: y mandatory;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.feed-container::-webkit-scrollbar { display: none; }

.loading-spinner {
  height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  scroll-snap-align: start;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(255,255,255,.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Bottom Nav */
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0; right: 0;
  height: 60px;
  background: rgba(0,0,0,.8);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: space-around;
  z-index: 100;
  border-top: 1px solid rgba(255,255,255,.1);
}

.nav-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  color: #fff;
}

.icon { font-size: 24px; }

.icon.plus {
  width: 44px; height: 44px;
  background: #fe2c55;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: 300;
}

.nav-avatar {
  width: 32px; height: 32px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #fff;
}
</style>
