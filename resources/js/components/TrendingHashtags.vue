<template>
  <div v-if="hashtags.length" class="trending-bar" ref="barEl">
    <!-- "Neked" gomb (főfeed) -->
    <button
      class="pill"
      :class="{ active: !activeSlug }"
      @click="selectAll"
    >
      🏠 Neked
    </button>

    <router-link
      v-for="tag in hashtags"
      :key="tag.id"
      :to="`/hashtag/${tag.slug}`"
      class="pill"
      :class="{ active: activeSlug === tag.slug }"
      @click="activeSlug = tag.slug"
    >
      #{{ tag.name }}
      <span class="pill-count">{{ formatCount(tag.videos_count) }}</span>
    </router-link>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api'

const route      = useRoute()
const hashtags   = ref([])
const barEl      = ref(null)
const activeSlug = ref(null)

onMounted(async () => {
  try {
    const { data } = await api.get('/hashtags/trending')
    hashtags.value = data

    // Ha hashtag oldalon vagyunk, jelöljük aktívnak
    if (route.name === 'hashtag') {
      activeSlug.value = route.params.slug
      scrollToActive()
    }
  } catch {}
})

function selectAll() {
  activeSlug.value = null
}

function scrollToActive() {
  // Aktív pill-t láthatóvá görgetjük
  setTimeout(() => {
    const active = barEl.value?.querySelector('.pill.active')
    active?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' })
  }, 100)
}

function formatCount(n) {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K'
  return n
}
</script>

<style scoped>
.trending-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 90;
  display: flex;
  gap: 8px;
  padding: 10px 12px;
  overflow-x: auto;
  scrollbar-width: none;
  background: linear-gradient(to bottom, rgba(0,0,0,.7) 0%, transparent 100%);
  -webkit-overflow-scrolling: touch;
}
.trending-bar::-webkit-scrollbar { display: none; }

.pill {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 14px;
  border-radius: 20px;
  border: 1.5px solid rgba(255,255,255,.35);
  background: rgba(0,0,0,.45);
  backdrop-filter: blur(6px);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: background .15s, border-color .15s;
  white-space: nowrap;
}

.pill:hover {
  background: rgba(255,255,255,.15);
  border-color: rgba(255,255,255,.6);
}

.pill.active {
  background: #fe2c55;
  border-color: #fe2c55;
}

.pill-count {
  font-size: 11px;
  font-weight: 400;
  opacity: .8;
}
</style>
