<template>
  <!-- Trending hashtag sáv (feed felett lebeg) -->
  <TrendingHashtags />

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

    <!-- Üres feed -->
    <div v-if="!feed.loading && !feed.videos.length" class="empty-feed">
      <span>🎬</span>
      <p>Még nincs videó — töltsd fel az elsőt!</p>
      <router-link to="/upload" class="btn-upload">+ Feltöltés</router-link>
    </div>
  </div>

  <!-- Navigációs sáv -->
  <nav class="bottom-nav">
    <router-link to="/" class="nav-btn">
      <span class="icon">🏠</span>
    </router-link>

    <router-link to="/upload" class="nav-btn">
      <span class="icon plus">＋</span>
    </router-link>

    <!-- Értesítés csengő (csak bejelentkezve) -->
    <button v-if="auth.user" class="nav-btn bell-btn" @click="showNotifications = true">
      <span class="icon">🔔</span>
      <span v-if="notifs.unreadCount > 0" class="badge">
        {{ notifs.unreadCount > 9 ? '9+' : notifs.unreadCount }}
      </span>
    </button>

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

  <!-- Értesítés panel -->
  <NotificationPanel
    v-if="showNotifications"
    @close="showNotifications = false"
  />
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useFeedStore } from '@/stores/feed'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import VideoCard from '@/components/VideoCard.vue'
import CommentPanel from '@/components/CommentPanel.vue'
import NotificationPanel from '@/components/NotificationPanel.vue'
import TrendingHashtags from '@/components/TrendingHashtags.vue'

const feed              = useFeedStore()
const auth              = useAuthStore()
const notifs            = useNotificationStore()
const feedEl            = ref(null)
const currentIndex      = ref(0)
const commentVideoId    = ref(null)
const showNotifications = ref(false)

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

.loading-spinner, .empty-feed {
  height: 100dvh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  scroll-snap-align: start;
}

.empty-feed span { font-size: 56px; }
.empty-feed p    { color: #888; font-size: 15px; }

.btn-upload {
  padding: 12px 28px;
  background: #fe2c55;
  border-radius: 10px;
  color: #fff;
  font-weight: 700;
  text-decoration: none;
  margin-top: 8px;
}

.spinner {
  width: 40px; height: 40px;
  border: 3px solid rgba(255,255,255,.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Bottom Nav */
.bottom-nav {
  position: fixed;
  bottom: 0; left: 0; right: 0;
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

.bell-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #fff;
  position: relative;
}

.badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #fe2c55;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  border: 2px solid #000;
}
</style>
