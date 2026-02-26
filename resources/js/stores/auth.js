import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/api'

export const useAuthStore = defineStore('auth', () => {
    const user  = ref(null)
    const token = ref(localStorage.getItem('token'))

    async function fetchMe() {
        if (!token.value) return
        try {
            const { data } = await api.get('/me')
            user.value = data
        } catch {
            logout()
        }
    }

    async function login(email, password) {
        const { data } = await api.post('/login', { email, password })
        token.value = data.token
        user.value  = data.user
        localStorage.setItem('token', data.token)
    }

    async function register(payload) {
        const { data } = await api.post('/register', payload)
        token.value = data.token
        user.value  = data.user
        localStorage.setItem('token', data.token)
    }

    function logout() {
        api.post('/logout').catch(() => {})
        token.value = null
        user.value  = null
        localStorage.removeItem('token')
    }

    return { user, token, fetchMe, login, register, logout }
})
