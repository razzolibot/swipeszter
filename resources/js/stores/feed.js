import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useFeedStore = defineStore('feed', () => {
    const videos   = ref([])
    const loading  = ref(false)
    const nextPage = ref('/api/videos')
    const hasMore  = ref(true)

    async function loadMore() {
        if (loading.value || !hasMore.value) return
        loading.value = true

        try {
            const url = nextPage.value.startsWith('/api')
                ? nextPage.value
                : new URL(nextPage.value).pathname + new URL(nextPage.value).search

            const { data } = await api.get(url)
            videos.value.push(...data.data)
            nextPage.value = data.next_page ?? null
            hasMore.value  = !!data.next_page
        } finally {
            loading.value = false
        }
    }

    async function toggleLike(video) {
        const { data } = await api.post(`/videos/${video.id}/like`)
        video.is_liked    = data.liked
        video.likes_count = data.likes_count
    }

    function reset() {
        videos.value   = []
        nextPage.value = '/api/videos'
        hasMore.value  = true
    }

    return { videos, loading, hasMore, loadMore, toggleLike, reset }
})
