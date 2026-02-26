<template>
  <router-view />
</template>

<script setup>
import { onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'

const auth   = useAuthStore()
const notifs = useNotificationStore()

onMounted(() => auth.fetchMe())

// Reverb WebSocket kapcsolat — csak bejelentkezve
watch(() => auth.user, async (user) => {
  if (!user) return

  // Unread count betöltése
  await notifs.fetchUnreadCount()

  // Laravel Echo + Reverb real-time
  try {
    const Echo = (await import('./echo')).default
    Echo.private(`App.Models.User.${user.id}`)
      .notification((notification) => {
        notifs.addRealtime(notification)
      })
  } catch (e) {
    console.warn('Reverb nem elérhető, polling mód')
  }
}, { immediate: true })
</script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  background: #000;
  color: #fff;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  overflow: hidden;
  height: 100dvh;
}

#app {
  height: 100dvh;
  overflow: hidden;
}
</style>
