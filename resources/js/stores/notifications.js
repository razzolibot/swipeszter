import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useNotificationStore = defineStore('notifications', () => {
    const items       = ref([])
    const unreadCount = ref(0)
    const loading     = ref(false)
    const nextPage    = ref(null)
    const hasMore     = ref(true)

    // ─── Lekérdezés ──────────────────────────────────────────────
    async function fetch(reset = false) {
        if (loading.value) return
        if (reset) { items.value = []; nextPage.value = null; hasMore.value = true }
        loading.value = true

        try {
            const url = nextPage.value
                ? new URL(nextPage.value).pathname + new URL(nextPage.value).search
                : '/api/notifications'

            const { data } = await api.get(url)
            items.value.push(...data.data)
            unreadCount.value = data.unread_count
            nextPage.value    = data.next_page ?? null
            hasMore.value     = !!data.next_page
        } finally {
            loading.value = false
        }
    }

    async function fetchUnreadCount() {
        try {
            const { data } = await api.get('/api/notifications/unread-count')
            unreadCount.value = data.count
        } catch {}
    }

    async function readAll() {
        await api.post('/api/notifications/read-all')
        items.value.forEach(n => n.read = true)
        unreadCount.value = 0
    }

    async function markRead(id) {
        await api.patch(`/api/notifications/${id}/read`)
        const n = items.value.find(n => n.id === id)
        if (n) {
            n.read = true
            unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
    }

    // Real-time értesítés hozzáadása (Reverb push)
    function addRealtime(notification) {
        items.value.unshift({ ...notification, read: false })
        unreadCount.value++
    }

    return { items, unreadCount, loading, hasMore, fetch, fetchUnreadCount, readAll, markRead, addRealtime }
})
