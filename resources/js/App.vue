<template>
  <router-view />
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
onMounted(() => auth.fetchMe())
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
