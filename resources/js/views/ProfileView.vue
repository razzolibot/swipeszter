<template>
  <div class="profile-page">
    <div class="profile-header">
      <button class="back-btn" @click="router.back()">←</button>
      <span class="username-title">@{{ username }}</span>
      <div />
    </div>

    <div v-if="loading" class="loading">Betöltés...</div>

    <template v-else-if="profile">
      <div class="profile-info">
        <img v-if="profile.avatar" :src="`/storage/${profile.avatar}`" class="profile-avatar" />
        <div v-else class="profile-avatar-ph">{{ profile.username?.[0]?.toUpperCase() }}</div>

        <h2 class="profile-name">{{ profile.name }}</h2>
        <p class="profile-at">@{{ profile.username }}</p>
        <p v-if="profile.bio" class="profile-bio">{{ profile.bio }}</p>

        <div class="profile-stats">
          <div class="stat"><strong>{{ profile.following_count }}</strong><span>Követett</span></div>
          <div class="stat"><strong>{{ profile.followers_count }}</strong><span>Követő</span></div>
          <div class="stat"><strong>{{ profile.videos_count }}</strong><span>Videó</span></div>
        </div>

        <div class="profile-actions">
          <template v-if="auth.user?.username === username">
            <button class="btn-edit">✏️ Profil szerkesztése</button>
          </template>
          <template v-else>
            <button
              class="btn-follow"
              :class="{ following: profile.is_following }"
              @click="toggleFollow"
            >
              {{ profile.is_following ? '✓ Követed' : '+ Követés' }}
            </button>
          </template>
        </div>
      </div>

      <!-- Videók rácsban -->
      <div class="videos-grid">
        <div
          v-for="v in profile.videos"
          :key="v.id"
          class="video-thumb"
          @click="router.push(`/?v=${v.id}`)"
        >
          <img :src="v.thumbnail_url || ''" class="thumb-img" />
          <div class="thumb-overlay">
            <span>▶ {{ formatCount(v.views_count) }}</span>
          </div>
        </div>
        <div v-if="!profile.videos.length" class="no-videos">
          Még nincs videó 🎬
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'

const route    = useRoute()
const router   = useRouter()
const auth     = useAuthStore()
const username = route.params.username
const profile  = ref(null)
const loading  = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get(`/users/${username}`)
    profile.value = data
  } finally {
    loading.value = false
  }
})

async function toggleFollow() {
  const { data } = await api.post(`/users/${profile.value.id}/follow`)
  profile.value.is_following    = data.following
  profile.value.followers_count = data.followers_count
}

function formatCount(n) {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
  return n
}
</script>

<style scoped>
.profile-page { min-height: 100dvh; background: #000; overflow-y: auto; padding-bottom: 20px; }
.profile-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; }
.back-btn { background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; }
.username-title { font-weight: 700; }
.loading { text-align: center; margin-top: 60px; color: #888; }
.profile-info { display: flex; flex-direction: column; align-items: center; padding: 0 20px 24px; gap: 8px; }
.profile-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; }
.profile-avatar-ph { width: 80px; height: 80px; border-radius: 50%; background: #fe2c55; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; }
.profile-name { font-size: 20px; font-weight: 700; }
.profile-at { color: #888; font-size: 14px; }
.profile-bio { font-size: 14px; text-align: center; max-width: 300px; color: #ccc; }
.profile-stats { display: flex; gap: 32px; margin: 8px 0; }
.stat { display: flex; flex-direction: column; align-items: center; }
.stat strong { font-size: 18px; font-weight: 700; }
.stat span { font-size: 12px; color: #888; }
.btn-follow, .btn-edit { padding: 10px 32px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; background: #fe2c55; color: #fff; }
.btn-follow.following { background: rgba(255,255,255,.15); }
.btn-edit { background: rgba(255,255,255,.15); }
.videos-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; }
.video-thumb { position: relative; aspect-ratio: 9/16; background: #111; overflow: hidden; cursor: pointer; }
.thumb-img { width: 100%; height: 100%; object-fit: cover; }
.thumb-overlay { position: absolute; bottom: 6px; left: 6px; font-size: 12px; font-weight: 600; text-shadow: 0 1px 3px rgba(0,0,0,.8); }
.no-videos { grid-column: 1/-1; text-align: center; padding: 40px; color: #888; }
</style>
