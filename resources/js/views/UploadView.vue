<template>
  <div class="upload-page">
    <div class="upload-header">
      <button class="back-btn" @click="router.back()">← Vissza</button>
      <h2>Feltöltés</h2>
      <div />
    </div>

    <div class="upload-content">
      <!-- Drop zone -->
      <div
        v-if="!preview"
        class="drop-zone"
        :class="{ dragover }"
        @click="fileInput.click()"
        @dragover.prevent="dragover = true"
        @dragleave="dragover = false"
        @drop.prevent="onDrop"
      >
        <span class="upload-icon">🎬</span>
        <p>Húzd ide a videót</p>
        <p class="hint">vagy kattints a kiválasztáshoz</p>
        <p class="hint">MP4, MOV · max 512MB</p>
      </div>

      <!-- Előnézet + form -->
      <div v-else class="upload-form">
        <video class="preview-video" :src="preview" controls />

        <div class="form-fields">
          <input v-model="form.title" placeholder="Cím (opcionális)" maxlength="200" />
          <textarea v-model="form.description" placeholder="Leírás (opcionális)" maxlength="2000" rows="3" />
        </div>

        <div class="upload-progress" v-if="uploading">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: uploadProgress + '%' }" />
          </div>
          <span>{{ uploadProgress }}%</span>
        </div>

        <p v-if="error" class="error">{{ error }}</p>

        <div class="form-actions">
          <button class="btn-secondary" @click="reset">Mégse</button>
          <button class="btn-primary" @click="upload" :disabled="uploading">
            {{ uploading ? 'Feltöltés...' : '✓ Közzétesz' }}
          </button>
        </div>
      </div>

      <!-- Sikeres feltöltés -->
      <div v-if="success" class="success-msg">
        <span>🎉</span>
        <p>Videó feltöltve! Feldolgozás folyamatban...</p>
        <button class="btn-primary" @click="router.push('/')">Főoldal</button>
      </div>
    </div>

    <input ref="fileInput" type="file" accept="video/mp4,video/quicktime,video/x-msvideo" class="hidden" @change="onFileSelect" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'

const router         = useRouter()
const fileInput      = ref(null)
const preview        = ref('')
const dragover       = ref(false)
const uploading      = ref(false)
const uploadProgress = ref(0)
const success        = ref(false)
const error          = ref('')
const selectedFile   = ref(null)
const form           = ref({ title: '', description: '' })

function onFileSelect(e) { handleFile(e.target.files[0]) }
function onDrop(e) { dragover.value = false; handleFile(e.dataTransfer.files[0]) }

function handleFile(file) {
  if (!file) return
  selectedFile.value = file
  preview.value = URL.createObjectURL(file)
}

function reset() {
  preview.value = ''
  selectedFile.value = null
  form.value = { title: '', description: '' }
  error.value = ''
}

async function upload() {
  if (!selectedFile.value) return
  uploading.value = true
  error.value = ''
  uploadProgress.value = 0

  const fd = new FormData()
  fd.append('video', selectedFile.value)
  if (form.value.title) fd.append('title', form.value.title)
  if (form.value.description) fd.append('description', form.value.description)

  try {
    await api.post('/videos', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: e => { uploadProgress.value = Math.round(e.loaded / e.total * 100) },
    })
    success.value = true
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Feltöltési hiba.'
  } finally {
    uploading.value = false
  }
}
</script>

<style scoped>
.upload-page { min-height: 100dvh; background: #000; display: flex; flex-direction: column; }
.upload-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,.1); }
.back-btn { background: none; border: none; color: #fff; font-size: 16px; cursor: pointer; }
h2 { font-size: 18px; font-weight: 700; }
.upload-content { flex: 1; padding: 24px 20px; display: flex; flex-direction: column; gap: 20px; overflow-y: auto; }
.drop-zone { border: 2px dashed rgba(255,255,255,.3); border-radius: 16px; padding: 60px 20px; text-align: center; cursor: pointer; transition: border-color .2s; }
.drop-zone.dragover { border-color: #fe2c55; }
.upload-icon { font-size: 48px; display: block; margin-bottom: 12px; }
.hint { color: #888; font-size: 13px; margin-top: 4px; }
.preview-video { width: 100%; max-height: 300px; border-radius: 12px; background: #111; }
.form-fields { display: flex; flex-direction: column; gap: 10px; }
input, textarea { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); border-radius: 10px; padding: 12px 16px; color: #fff; font-size: 14px; outline: none; resize: none; }
input:focus, textarea:focus { border-color: #fe2c55; }
.upload-progress { display: flex; align-items: center; gap: 12px; }
.progress-bar { flex: 1; height: 4px; background: rgba(255,255,255,.2); border-radius: 2px; }
.progress-fill { height: 100%; background: #fe2c55; border-radius: 2px; transition: width .2s; }
.form-actions { display: flex; gap: 12px; }
.btn-primary { flex: 1; padding: 14px; background: #fe2c55; border: none; border-radius: 10px; color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; }
.btn-secondary { flex: 1; padding: 14px; background: rgba(255,255,255,.1); border: none; border-radius: 10px; color: #fff; font-size: 16px; cursor: pointer; }
.btn-primary:disabled { opacity: .6; }
.error { color: #fe2c55; font-size: 13px; }
.success-msg { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 16px; margin: auto; }
.success-msg span { font-size: 64px; }
.hidden { display: none; }
</style>
