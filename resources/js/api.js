import axios from 'axios'

const api = axios.create({
    baseURL: '/api',
    headers: { 'Accept': 'application/json' },
})

// Token hozzáadása minden kéréshez
api.interceptors.request.use(config => {
    const token = localStorage.getItem('token')
    if (token) config.headers.Authorization = `Bearer ${token}`
    return config
})

// 401 → kijelentkeztetés
api.interceptors.response.use(
    res => res,
    err => {
        if (err.response?.status === 401) {
            localStorage.removeItem('token')
            window.location.href = '/login'
        }
        return Promise.reject(err)
    }
)

export default api
