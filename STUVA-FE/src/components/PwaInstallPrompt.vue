<template>
  <button v-if="canInstall" @click="install" class="pwa-install">
    <Download class="icon-sm" />
    <span>Install App</span>
  </button>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Download } from 'lucide-vue-next'

const canInstall = ref(false)
let deferredPrompt = null

onMounted(() => {
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault()
    deferredPrompt = e
    canInstall.value = true
  })
})

const install = async () => {
  if (!deferredPrompt) return
  deferredPrompt.prompt()
  await deferredPrompt.userChoice
  deferredPrompt = null
  canInstall.value = false
}
</script>

<style scoped>
.pwa-install {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,.25);
  background: rgba(255,255,255,.15);
  color: #ffffff;
  font-weight: 700;
  font-size: .813rem;
  font-family: inherit;
  cursor: pointer;
  transition: all .25s ease;
}
.pwa-install:hover { background: rgba(255,255,255,.28); }
</style>