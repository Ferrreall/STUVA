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
        <p class="login-subtitle">Sistem Manajemen Sekolah</p>
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
          <input 
            type="password" 
            id="password" 
            v-model="formData.password" 
            class="form-input"
            placeholder="Masukkan password"
            required
            :disabled="authStore.loading"
          />
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
          <span v-if="!authStore.loading">Masuk</span>
          <span v-else class="loading-text">
            <svg class="spinner" viewBox="0 0 24 24">
              <circle class="spinner-circle" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
            </svg>
            Memproses...
          </span>
        </button>
      </form>

      <div class="login-footer">
        <p class="footer-text">Demo Account:</p>
        <div class="demo-accounts">
          <button @click="useDemoAccount('siswa')" class="demo-btn" :disabled="authStore.loading">
            Siswa
          </button>
          <button @click="useDemoAccount('guru')" class="demo-btn" :disabled="authStore.loading">
            Guru
          </button>
          <button @click="useDemoAccount('ortu')" class="demo-btn" :disabled="authStore.loading">
            Orang Tua
          </button>
        </div>
      </div>

      <!-- Debug: Force Logout Button -->
      <div v-if="hasToken" class="debug-section">
        <p class="debug-text">⚠️ Masih ada session aktif</p>
        <button @click="forceLogout" class="btn-logout" :disabled="isLoggingOut">
          {{ isLoggingOut ? 'Menghapus...' : 'Hapus Session & Logout' }}
        </button>
      </div>
    </div>

    <!-- Toast Notification -->
    <Teleport to="body">
      <div v-if="showToast" class="toast-notification" :class="toastType">
        <div class="toast-content">
          <svg v-if="toastType === 'success'" class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M20 6L9 17l-5-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <svg v-else class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="10" stroke-width="2"/>
            <line x1="15" y1="9" x2="9" y2="15" stroke-width="2" stroke-linecap="round"/>
            <line x1="9" y1="9" x2="15" y2="15" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <span>{{ toastMessage }}</span>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import apiClient from '../../utils/api'

const router = useRouter()
const authStore = useAuthStore()

const formData = ref({
  username: '',
  password: ''
})

const isLoggingOut = ref(false)
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

// Check if there's existing token
const hasToken = computed(() => {
  return !!localStorage.getItem('token')
})

onMounted(() => {
  // Clear error on mount
  authStore.error = null
})

const displayToast = (message, type = 'success') => {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
  
  setTimeout(() => {
    showToast.value = false
  }, 3000)
}

const handleLogin = async () => {
  try {
    console.log('🔐 Starting login process...')
    console.log('📝 Username:', formData.value.username)
    
    // Memanggil fungsi login dari Pinia authStore
    await authStore.login(formData.value.username, formData.value.password)
    
    console.log('✅ Login successful!')
    console.log('👤 User role:', authStore.role)
    console.log('💾 Token:', authStore.token ? 'exists' : 'missing')
    console.log('💾 LocalStorage role:', localStorage.getItem('role'))
    console.log('💾 LocalStorage token:', localStorage.getItem('token') ? 'exists' : 'missing')
    
    // Redirect halaman sesuai role setelah login sukses
    const role = authStore.role || localStorage.getItem('role')
    let redirectPath = '/siswa/dashboard' // default
    
    if (role === 'ortu') {
      redirectPath = '/ortu/dashboard'
      console.log('🔄 Redirecting to Orang Tua dashboard...')
    } else if (role === 'guru') {
      redirectPath = '/guru/dashboard'
      console.log('🔄 Redirecting to Guru dashboard...')
    } else {
      redirectPath = '/siswa/dashboard'
      console.log('🔄 Redirecting to Siswa dashboard...')
    }
    
    console.log('🎯 Final redirect path:', redirectPath)
    
    // Small delay to ensure localStorage is written
    await new Promise(resolve => setTimeout(resolve, 100))
    
    console.log('🚀 Executing router.push...')
    router.push(redirectPath).then(() => {
      console.log('✅ Router.push completed successfully')
    }).catch((error) => {
      console.error('❌ Router.push error:', error)
      // Fallback: force navigation
      console.log('🔄 Trying window.location fallback...')
      window.location.href = redirectPath
    })
    
  } catch (error) {
    console.error('❌ Login error:', error)
    displayToast(authStore.error || 'Login gagal', 'error')
  }
}

const forceLogout = async () => {
  isLoggingOut.value = true
  
  try {
    console.log('🚪 Force logout started...')
    
    const token = localStorage.getItem('token')
    
    // Try to call logout API if token exists
    if (token) {
      try {
        console.log('📡 Calling logout API...')
        await apiClient.post('/logout')
        console.log('✅ Logout API success')
      } catch (apiError) {
        console.warn('⚠️ Logout API failed (continuing anyway):', apiError.message)
      }
    }
    
    // Clear everything
    console.log('🧹 Clearing localStorage...')
    localStorage.clear()
    
    console.log('🧹 Clearing authStore...')
    authStore.token = null
    authStore.user = null
    authStore.role = null
    authStore.error = null
    
    displayToast('Session berhasil dihapus', 'success')
    
    // Refresh form
    formData.value = {
      username: '',
      password: ''
    }
    
    console.log('✅ Force logout complete')
    
  } catch (error) {
    console.error('❌ Force logout error:', error)
    displayToast('Gagal menghapus session', 'error')
  } finally {
    isLoggingOut.value = false
  }
}

const useDemoAccount = (role) => {
  if (role === 'siswa') {
    formData.value.username = '1234567890'
    formData.value.password = 'password123'
  } else if (role === 'guru') {
    formData.value.username = '198501012026011001'
    formData.value.password = 'password123'
  } else if (role === 'ortu') {
    formData.value.username = '081234567890'
    formData.value.password = 'password123'
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.login-card {
  background-color: #ffffff;
  border-radius: 16px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  width: 100%;
  max-width: 420px;
  padding: 40px 32px;
}

.login-header {
  text-align: center;
  margin-bottom: 32px;
}

.logo-container {
  display: inline-block;
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.logo-icon {
  width: 36px;
  height: 36px;
  color: #ffffff;
}

.login-title {
  font-size: 2rem;
  font-weight: 800;
  color: #1f2937;
  margin: 0 0 8px 0;
  letter-spacing: -0.02em;
}

.login-subtitle {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.938rem;
  color: #1f2937;
  background-color: #ffffff;
  transition: all 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-input:disabled {
  background-color: #f9fafb;
  cursor: not-allowed;
}

.error-message {
  display: flex;
  align-items: center;
  gap: 8px;
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 12px;
  color: #dc2626;
  font-size: 0.875rem;
}

.icon-sm {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.btn-login {
  width: 100%;
  padding: 14px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-login:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.3);
}

.btn-login:active:not(:disabled) {
  transform: translateY(0);
}

.btn-login:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}

.loading-text {
  display: flex;
  align-items: center;
  gap: 8px;
}

.spinner {
  width: 18px;
  height: 18px;
  animation: spin 1s linear infinite;
}

.spinner-circle {
  stroke-dasharray: 50;
  stroke-dashoffset: 25;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.login-footer {
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
  text-align: center;
}

.footer-text {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0 0 12px 0;
}

.demo-accounts {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.demo-btn {
  padding: 10px 16px;
  background-color: #f3f4f6;
  color: #374151;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.demo-btn:hover:not(:disabled) {
  background-color: #e5e7eb;
  border-color: #d1d5db;
}

.demo-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Debug Section */
.debug-section {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #fecaca;
  text-align: center;
}

.debug-text {
  font-size: 0.813rem;
  color: #dc2626;
  font-weight: 600;
  margin: 0 0 12px 0;
}

.btn-logout {
  width: 100%;
  padding: 12px 20px;
  background-color: #dc2626;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-logout:hover:not(:disabled) {
  background-color: #b91c1c;
}

.btn-logout:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Toast Notification */
.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  background-color: #ffffff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  z-index: 9999;
  min-width: 280px;
  animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.toast-content {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 0.875rem;
  font-weight: 500;
}

.toast-notification.success {
  border-left: 4px solid #22c55e;
  color: #15803d;
}

.toast-notification.success .icon-sm {
  color: #22c55e;
}

.toast-notification.error {
  border-left: 4px solid #ef4444;
  color: #b91c1c;
}

.toast-notification.error .icon-sm {
  color: #ef4444;
}

@media (max-width: 480px) {
  .login-card {
    padding: 32px 24px;
  }
  
  .login-title {
    font-size: 1.75rem;
  }
  
  .toast-notification {
    right: 10px;
    left: 10px;
    min-width: auto;
  }
}
</style>
