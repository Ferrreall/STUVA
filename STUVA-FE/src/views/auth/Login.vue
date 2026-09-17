<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-container">
          <svg class="logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <h1 class="login-title">STUVA</h1>
        <p class="login-subtitle">Student Tracking, Utility, Verification & Administration</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="form-group">
          <label for="username" class="form-label">Username / NISN</label>
          <input
            type="text"
            id="username"
            v-model="formData.username"
            class="form-input"
            placeholder="Masukkan username atau NISN"
            required
            :disabled="authStore.loading"
          />
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <div class="input-wrapper">
            <input
              :type="showPassword ? 'text' : 'password'"
              id="password"
              v-model="formData.password"
              class="form-input"
              placeholder="Masukkan password"
              required
              :disabled="authStore.loading"
            />
            <button type="button" class="toggle-password" @click="showPassword = !showPassword" tabindex="-1">
              <Eye v-if="!showPassword" class="icon-sm" />
              <EyeOff v-else class="icon-sm" />
            </button>
          </div>
        </div>

        <div v-if="authStore.error" class="error-message">
          <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <line x1="12" y1="8" x2="12" y2="12" stroke-width="2" stroke-linecap="round"/>
            <line x1="12" y1="16" x2="12.01" y2="16" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <span>{{ authStore.error }}</span>
        </div>

        <button type="submit" class="btn-login" :disabled="authStore.loading">
          <span v-if="!authStore.loading">
            Masuk
            <svg class="btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M5 12h14M12 5l7 7-7 7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <span v-else class="loading-text">
            <svg class="spinner" viewBox="0 0 24 24">
              <circle class="spinner-circle" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
            </svg>
            Memproses...
          </span>
        </button>
      </form>

      <!-- Debug: Force Logout Button -->
      <div v-if="hasToken" class="debug-section">
        <p class="debug-text">⚠️ Masih ada session aktif</p>
        <button @click="forceLogout" class="btn-logout" :disabled="isLoggingOut">
          {{ isLoggingOut ? 'Menghapus...' : 'Hapus Session & Logout' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '../../stores/authStore'
import apiClient from '../../utils/api'
import { Eye, EyeOff } from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const authStore = useAuthStore()

const formData = ref({
  username: '',
  password: ''
})
const showPassword = ref(false)  

const isLoggingOut = ref(false)

// Check if there's existing token
const hasToken = computed(() => {
  return !!localStorage.getItem('token')
})

onMounted(() => {
  // Tangkap query ?logout=success dari Dashboard
  if (route.query.logout === 'success') {
    toast.success('Berhasil logout')

    // Clean up query URL (/login?logout=success -> /login)
    router.replace({ query: {} })
  }
})

const handleLogin = async () => {
  try { 
    await authStore.login(formData.value.username, formData.value.password)
    
    const role = authStore.role || localStorage.getItem('role')
    let redirectPath = '/siswa/dashboard'

    if (role === 'ortu') {
      redirectPath = '/ortu/dashboard'
    } else if (role === 'guru') {
      redirectPath = '/guru/dashboard'
    } else if (role === 'admin') {
      redirectPath = '/admin/dashboard'
    } else {
      redirectPath = '/siswa/dashboard'
    }

    router.push(redirectPath).catch((error) => {
      console.error('⚠️ Router.push error:', error)
      window.location.href = redirectPath
    })

  } catch (error) {
    console.error('❌ Login error:', error)
    // Toast error sudah otomatis dipanggil dari authStore.login
  }
}

const forceLogout = async () => {
  isLoggingOut.value = true

  try {    
    const token = localStorage.getItem('token')

    if (token) {
      try {
        await apiClient.post('/logout')
      } catch (apiError) {
        console.warn('Logout API failed (continuing anyway):', apiError.message)
      }
    }

    localStorage.clear()

    authStore.token = null
    authStore.user = null
    authStore.role = null
    authStore.error = null

    toast.success('Session berhasil dihapus')

    formData.value = {
      username: '',
      password: ''
    }

  } catch (error) {
    console.error('Force logout error:', error)
    toast.error('Gagal menghapus session')
  } finally {
    isLoggingOut.value = false
  }
}
</script>
<style scoped>
@import '../../assets/css/Login.css';
</style>
