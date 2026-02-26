import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
    { path: '/',         name: 'feed',     component: () => import('@/views/FeedView.vue') },
    { path: '/login',    name: 'login',    component: () => import('@/views/LoginView.vue') },
    { path: '/register', name: 'register', component: () => import('@/views/RegisterView.vue') },
    { path: '/upload',   name: 'upload',   component: () => import('@/views/UploadView.vue'),  meta: { auth: true } },
    { path: '/@:username', name: 'profile', component: () => import('@/views/ProfileView.vue') },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to) => {
    const auth = useAuthStore()
    if (to.meta.auth && !auth.token) return { name: 'login' }
})

export default router
