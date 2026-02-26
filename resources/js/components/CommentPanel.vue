<template>
  <div class="comment-overlay" @click.self="$emit('close')">
    <div class="comment-panel">
      <div class="panel-header">
        <span class="panel-title">Kommentek</span>
        <button class="close-btn" @click="$emit('close')">✕</button>
      </div>

      <div class="comments-list" ref="listEl">
        <div v-if="loading" class="loading">Betöltés...</div>
        <template v-else>
          <div v-for="c in comments" :key="c.id" class="comment">
            <img v-if="c.user.avatar" :src="`/storage/${c.user.avatar}`" class="c-avatar" />
            <div v-else class="c-avatar-ph">{{ c.user.username?.[0]?.toUpperCase() }}</div>
            <div class="c-body">
              <span class="c-username">@{{ c.user.username }}</span>
              <p class="c-content">{{ c.content }}</p>
              <span class="c-time">{{ timeAgo(c.created_at) }}</span>
            </div>
          </div>
          <div v-if="!comments.length" class="empty">Még nincs komment. Légy az első! 💬</div>
        </template>
      </div>

      <div class="comment-input" v-if="auth.token">
        <input
          v-model="newComment"
          placeholder="Írj valamit..."
          @keydown.enter="postComment"
          maxlength="500"
        />
        <button @click="postComment" :disabled="!newComment.trim()">→</button>
      </div>
      <div v-else class="login-prompt">
        <router-link to="/login" @click="$emit('close')">Jelentkezz be</router-link> a kommenthez!
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/api'

const props  = defineProps({ videoId: Number })
const emit   = defineEmits(['close'])
const auth   = useAuthStore()
const comments  = ref([])
const loading   = ref(true)
const newComment = ref('')

onMounted(async () => {
  const { data } = await api.get(`/videos/${props.videoId}/comments`)
  comments.value = data.data ?? []
  loading.value = false
})

async function postComment() {
  if (!newComment.value.trim()) return
  const { data } = await api.post(`/videos/${props.videoId}/comments`, { content: newComment.value })
  comments.value.unshift(data)
  newComment.value = ''
}

function timeAgo(dateStr) {
  const diff = (Date.now() - new Date(dateStr)) / 1000
  if (diff < 60)    return 'most'
  if (diff < 3600)  return Math.floor(diff/60) + ' perce'
  if (diff < 86400) return Math.floor(diff/3600) + ' órája'
  return Math.floor(diff/86400) + ' napja'
}
</script>

<style scoped>
.comment-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.5);
  z-index: 200;
  display: flex;
  align-items: flex-end;
}

.comment-panel {
  width: 100%;
  height: 70dvh;
  background: #1a1a1a;
  border-radius: 16px 16px 0 0;
  display: flex;
  flex-direction: column;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255,255,255,.1);
}
.panel-title { font-weight: 700; }
.close-btn { background: none; border: none; color: #fff; font-size: 18px; cursor: pointer; }

.comments-list {
  flex: 1;
  overflow-y: auto;
  padding: 12px 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.comment {
  display: flex;
  gap: 10px;
}

.c-avatar, .c-avatar-ph {
  width: 36px; height: 36px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}
.c-avatar-ph {
  background: #fe2c55;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 14px;
}

.c-body { flex: 1; }
.c-username { font-weight: 700; font-size: 13px; }
.c-content { font-size: 14px; margin: 2px 0; line-height: 1.4; }
.c-time { font-size: 11px; color: #888; }

.empty, .loading {
  text-align: center;
  color: #888;
  margin: auto;
}

.comment-input {
  display: flex;
  gap: 8px;
  padding: 12px 16px;
  border-top: 1px solid rgba(255,255,255,.1);
}
.comment-input input {
  flex: 1;
  background: rgba(255,255,255,.1);
  border: none;
  border-radius: 20px;
  padding: 10px 16px;
  color: #fff;
  font-size: 14px;
  outline: none;
}
.comment-input button {
  background: #fe2c55;
  border: none;
  border-radius: 50%;
  width: 40px; height: 40px;
  color: #fff;
  font-size: 18px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.comment-input button:disabled { opacity: .4; }

.login-prompt {
  padding: 16px;
  text-align: center;
  font-size: 14px;
  color: #888;
  border-top: 1px solid rgba(255,255,255,.1);
}
.login-prompt a { color: #fe2c55; text-decoration: none; }
</style>
