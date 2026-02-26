<template>
  <div class="auth-page">
    <div class="auth-card">
      <h1 class="logo">swipeszter 🎬</h1>
      <p class="subtitle">Hozz létre egy fiókot</p>

      <form @submit.prevent="submit">
        <input v-model="form.name" placeholder="Teljes neved" required />
        <input v-model="form.username" placeholder="@felhasználónév" required pattern="[a-zA-Z0-9_\-]+" />
        <input v-model="form.email" type="email" placeholder="Email" required />
        <input v-model="form.password" type="password" placeholder="Jelszó (min. 8 karakter)" required />
        <input v-model="form.password_confirmation" type="password" placeholder="Jelszó mégegyszer" required />
        <p v-if="errors.length" class="error">{{ errors[0] }}</p>
        <button type="submit" :disabled="loading">
          {{ loading ? 'Regisztráció...' : 'Regisztráció' }}
        </button>
      </form>

      <p class="switch">Van már fiókod? <router-link to="/login">Jelentkezz be!</router-link></p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router  = useRouter()
const auth    = useAuthStore()
const loading = ref(false)
const errors  = ref([])
const form    = ref({ name: '', username: '', email: '', password: '', password_confirmation: '' })

async function submit() {
  loading.value = true
  errors.value  = []
  try {
    await auth.register(form.value)
    router.push('/')
  } catch (e) {
    const data = e.response?.data
    if (data?.errors) errors.value = Object.values(data.errors).flat()
    else errors.value = [data?.message ?? 'Hiba történt.']
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-page { min-height: 100dvh; background: #000; display: flex; align-items: center; justify-content: center; padding: 20px; }
.auth-card { width: 100%; max-width: 380px; background: #111; border-radius: 16px; padding: 40px 32px; }
.logo { font-size: 28px; font-weight: 900; margin-bottom: 8px; }
.subtitle { color: #888; margin-bottom: 32px; font-size: 14px; }
form { display: flex; flex-direction: column; gap: 12px; }
input { padding: 14px 16px; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); border-radius: 10px; color: #fff; font-size: 15px; outline: none; transition: border-color .2s; }
input:focus { border-color: #fe2c55; }
button[type=submit] { padding: 14px; background: #fe2c55; border: none; border-radius: 10px; color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 8px; }
button:disabled { opacity: .6; }
.error { color: #fe2c55; font-size: 13px; }
.switch { text-align: center; margin-top: 20px; color: #888; font-size: 14px; }
.switch a { color: #fe2c55; text-decoration: none; font-weight: 600; }
</style>
