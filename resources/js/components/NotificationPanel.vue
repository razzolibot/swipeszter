<template>
  <div class="notif-overlay" @click.self="$emit('close')">
    <div class="notif-panel">

      <!-- Header -->
      <div class="panel-header">
        <span class="panel-title">Értesítések</span>
        <div class="header-actions">
          <button v-if="store.items.some(n => !n.read)" class="btn-read-all" @click="store.readAll()">
            Mind olvasott
          </button>
          <button class="close-btn" @click="$emit('close')">✕</button>
        </div>
      </div>

      <!-- Lista -->
      <div class="notif-list" ref="listEl" @scroll="onScroll">
        <div v-if="store.loading && !store.items.length" class="state-msg">
          <div class="spinner" />
        </div>

        <div v-else-if="!store.items.length" class="state-msg">
          <span>🔔</span>
          <p>Még nincs értesítésed</p>
        </div>

        <template v-else>
          <div
            v-for="n in store.items"
            :key="n.id"
            class="notif-item"
            :class="{ unread: !n.read }"
            @click="handleClick(n)"
          >
            <!-- Avatar -->
            <div class="notif-avatar">
              <img v-if="n.data.avatar" :src="`/storage/${n.data.avatar}`" class="avatar-img" />
              <div v-else class="avatar-ph">{{ n.data.username?.[0]?.toUpperCase() }}</div>
              <span class="notif-icon">{{ typeIcon(n.type) }}</span>
            </div>

            <!-- Tartalom -->
            <div class="notif-body">
              <p class="notif-msg">{{ n.data.message }}</p>
              <span class="notif-time">{{ timeAgo(n.created_at) }}</span>
            </div>

            <!-- Videó thumbnail ha van -->
            <img
              v-if="n.data.thumbnail"
              :src="n.data.thumbnail"
              class="notif-thumb"
            />
          </div>

          <div v-if="store.loading" class="load-spinner">
            <div class="spinner small" />
          </div>
        </template>
      </div>

    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notifications'

const emit   = defineEmits(['close'])
const router = useRouter()
const store  = useNotificationStore()
const listEl = ref(null)

onMounted(() => store.fetch(true))

function onScroll() {
  const el = listEl.value
  if (!el) return
  const nearBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 100
  if (nearBottom && store.hasMore && !store.loading) store.fetch()
}

function handleClick(n) {
  store.markRead(n.id)
  emit('close')

  if (n.type === 'follow') {
    router.push(`/@${n.data.username}`)
  } else if (n.data.video_id) {
    router.push(`/?v=${n.data.video_id}`)
  }
}

function typeIcon(type) {
  return { like: '❤️', comment: '💬', follow: '👤' }[type] ?? '🔔'
}

function timeAgo(dateStr) {
  const diff = (Date.now() - new Date(dateStr)) / 1000
  if (diff < 60)    return 'most'
  if (diff < 3600)  return Math.floor(diff / 60) + ' perce'
  if (diff < 86400) return Math.floor(diff / 3600) + ' órája'
  return Math.floor(diff / 86400) + ' napja'
}
</script>

<style scoped>
.notif-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 200;
  display: flex;
  align-items: flex-end;
}

.notif-panel {
  width: 100%;
  height: 80dvh;
  background: #1a1a1a;
  border-radius: 20px 20px 0 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px;
  border-bottom: 1px solid rgba(255,255,255,.08);
  flex-shrink: 0;
}

.panel-title { font-size: 17px; font-weight: 700; }

.header-actions { display: flex; align-items: center; gap: 12px; }

.btn-read-all {
  background: none;
  border: none;
  color: #fe2c55;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.close-btn {
  background: none;
  border: none;
  color: #fff;
  font-size: 18px;
  cursor: pointer;
}

/* Lista */
.notif-list {
  flex: 1;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.state-msg {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  height: 200px;
  color: #888;
}
.state-msg span { font-size: 40px; }

/* Értesítés sor */
.notif-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  cursor: pointer;
  transition: background .15s;
  border-bottom: 1px solid rgba(255,255,255,.04);
}
.notif-item:hover { background: rgba(255,255,255,.05); }
.notif-item.unread { background: rgba(254,44,85,.06); }

/* Avatar */
.notif-avatar { position: relative; flex-shrink: 0; }

.avatar-img, .avatar-ph {
  width: 46px; height: 46px;
  border-radius: 50%;
  object-fit: cover;
}
.avatar-ph {
  background: #333;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 700;
}

.notif-icon {
  position: absolute;
  bottom: -4px;
  right: -4px;
  font-size: 16px;
  background: #1a1a1a;
  border-radius: 50%;
  width: 22px; height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Tartalom */
.notif-body { flex: 1; min-width: 0; }
.notif-msg  { font-size: 14px; line-height: 1.4; }
.notif-time { font-size: 12px; color: #888; margin-top: 3px; display: block; }

/* Thumbnail */
.notif-thumb {
  width: 46px; height: 46px;
  border-radius: 6px;
  object-fit: cover;
  flex-shrink: 0;
}

/* Spinner */
.spinner {
  width: 32px; height: 32px;
  border: 3px solid rgba(255,255,255,.15);
  border-top-color: #fe2c55;
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
.spinner.small { width: 24px; height: 24px; }
@keyframes spin { to { transform: rotate(360deg); } }

.load-spinner { display: flex; justify-content: center; padding: 16px; }
</style>
