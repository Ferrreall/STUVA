<template>
  <div class="presensi-container" :class="{ 'sidebar-open': sidebarOpen }">

    <div class="sidebar-overlay" @click="closeSidebar"></div>

    <aside class="sidebar" :class="{ open: sidebarOpen }" @click.self="closeSidebar">
      <div class="sidebar-head">
        <span class="sidebar-brand">STUVA</span>
        <button class="sidebar-close" @click="closeSidebar" aria-label="Tutup">
          <X class="icon-sm" />
        </button>
      </div>

      <nav class="sidebar-nav">
        <button
          class="sidebar-item"
          :class="{ active: isActive('/siswa/dashboard') }"
          @click="sidebarNavigate('/siswa/dashboard')"
        >
          <Home class="icon-sm" />
          <span>Dashboard</span>
        </button>

        <button
          class="sidebar-item"
          :class="{ active: isActive('/siswa/presensi') }"
          @click="sidebarNavigate('/siswa/presensi')"
        >
          <Calendar class="icon-sm" />
          <span>Riwayat Absen</span>
        </button>

        <button
          class="sidebar-item"
          :class="{ active: isActive('/siswa/profile') }"
          @click="sidebarNavigate('/siswa/profile')"
        >
          <User class="icon-sm" />
          <span>Profil Saya</span>
        </button>
        
        <button
          class="sidebar-item"
          :class="{ active: isActive('/siswa/absen') }"
          @click="sidebarNavigate('/siswa/absen')"
        >
          <MapPin class="icon-sm" />
          <span>Absen</span>
        </button>

        <div class="sidebar-divider"></div>

        <button class="sidebar-item logout" @click="handleLogout">
          <LogOut class="icon-sm" />
          <span>Keluar</span>
        </button>
      </nav>
    </aside>

    <div class="page-shell">
      <header class="header">
        <div class="header-content">
          <div class="header-left-group">
            <button @click="toggleSidebar" class="sidebar-toggle" aria-label="Menu">
              <Menu class="icon-md" />
            </button>
            <h1 class="header-title">Absen Hari Ini</h1>
          </div>
        </div>
      </header>

      <main class="main-content">
        <!-- Kartu utama absen -->
        <section class="card absen-card">
          <div class="absen-clock">
            {{ currentTime }}
          </div>

          <!-- Belum absen masuk -->
          <div v-if="statusData?.can_check_in" class="absen-state">
            <p class="absen-state-text">Kamu belum absen masuk hari ini</p>
            <button
              @click="doAbsen"
              class="absen-big-btn masuk"
              :disabled="submitting || !gpsReady"
            >
              <LogIn class="absen-btn-icon" />
              <span>{{ submitting ? 'Memproses...' : 'Absen Masuk' }}</span>
            </button>
          </div>

          <!-- Sudah masuk, belum pulang -->
          <div v-else-if="statusData?.can_check_out" class="absen-state">
            <p class="absen-state-text">
              Masuk pukul <strong>{{ statusData.check_in }}</strong>
            </p>
            <button
              @click="doAbsen"
              class="absen-big-btn pulang"
              :disabled="submitting || !gpsReady"
            >
              <LogOut class="absen-btn-icon" />
              <span>{{ submitting ? 'Memproses...' : 'Absen Pulang' }}</span>
            </button>
          </div>

          <!-- Selesai -->
          <div v-else-if="statusData?.done" class="absen-state">
            <CheckCircle class="absen-done-icon" />
            <p class="absen-state-text">Selesai untuk hari ini 🎉</p>
            <div class="absen-times-recap">
              <span>↑ Masuk {{ statusData.check_in }}</span>
              <span>↓ Pulang {{ statusData.check_out }}</span>
            </div>
          </div>

          <!-- Loading status -->
          <div v-else class="absen-state">
            <div class="spinner"></div>
          </div>

          <!-- GPS status -->
          <p class="absen-gps">
            <MapPin class="icon-xs" />
            {{ gpsText }}
          </p>
          <p v-if="absenError" class="loc-msg error">{{ absenError }}</p>
        </section>
      </main>
    </div>

    <!-- Toast -->
    <Teleport to="body">
      <div v-if="showToast" class="toast-notification" :class="toastType">
        <div class="toast-content">
          <CheckCircle v-if="toastType === 'success'" class="icon-sm" />
          <span>{{ toastMessage }}</span>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'   // ← cuma useRouter
import apiClient from '../../utils/api'
import { useGeolocation } from '../../composables/useGeolocation'
import {
  Menu, LogIn, LogOut, MapPin, CheckCircle,
  ChevronLeft, Home, Calendar, User, X
} from 'lucide-vue-next'


const router = useRouter()
const route = useRoute()

// ===== Sidebar =====
const sidebarOpen = ref(false)
const toggleSidebar = () => { sidebarOpen.value = !sidebarOpen.value }
const closeSidebar = () => { sidebarOpen.value = false }
const isActive = (path) => route.path === path        // ← yang error ini
const sidebarNavigate = (path) => {
  sidebarOpen.value = false
  router.push(path)
}
const handleLogout = () => {
  sidebarOpen.value = false
  localStorage.clear()
  router.push({ path: '/login', query: { logout: 'success' } })
}

// ===== GPS =====
const {
  status: locStatus,
  coords: liveCoords,
  pingOnce,
} = useGeolocation({})

const gpsReady = computed(() => !!liveCoords.value)
const gpsText = computed(() => ({
  idle: 'GPS: tekan tombol untuk mulai',
  locating: 'GPS: mengambil lokasi...',
  active: 'GPS: lokasi siap ✓',
  denied: 'GPS: izin ditolak',
  timeout: 'GPS: timeout, coba lagi',
  unavailable: 'GPS: sinyal tidak tersedia',
  insecure: 'GPS: butuh HTTPS',
}[locStatus.value] || locStatus.value))

// ===== Absen =====
const statusData = ref(null)
const submitting = ref(false)
const absenError = ref(null)
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

const currentTime = ref(new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }))
let clockTimer = null

const fetchStatus = async () => {
  try {
    const res = await apiClient.get('/siswa/absen-status')
    statusData.value = res.data?.data || {}
  } catch (e) {
    console.error('❌ Gagal ambil status absen:', e)
  }
}

const doAbsen = async () => {
  absenError.value = null
  submitting.value = true

  try {
    // 1. pastikan GPS dapet
    if (!liveCoords.value) {
      await pingOnce().catch(() => { throw new Error('gps') })
    }
    if (!liveCoords.value) throw new Error('gps')

    // 2. kirim absen
    const res = await apiClient.post('/siswa/absen', {
      latitude: liveCoords.value.latitude,
      longitude: liveCoords.value.longitude,
    })

    displayToast(res.data.message || 'Absen berhasil!', 'success')
    await fetchStatus()
  } catch (e) {
    if (e.message === 'gps') {
      absenError.value = 'Gagal ambil lokasi GPS. Pastikan izin lokasi aktif & kamu di luar ruangan.'
    } else {
      absenError.value = e.response?.data?.message || 'Gagal absen'
    }
  } finally {
    submitting.value = false
  }
}

const displayToast = (msg, type) => {
  toastMessage.value = msg
  toastType.value = type
  showToast.value = true
  setTimeout(() => { showToast.value = false }, 3000)
}

let escHandler = null
onMounted(() => {
  fetchStatus()
  // GPS diambil duluan biar tombol langsung siap
  pingOnce().catch(() => {})

  clockTimer = setInterval(() => {
    currentTime.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
  }, 10000)

  escHandler = (e) => { if (e.key === 'Escape') closeSidebar() }
  document.addEventListener('keydown', escHandler)
})
onUnmounted(() => {
  clearInterval(clockTimer)
  document.removeEventListener('keydown', escHandler)
})
</script>

<style scoped>
@import '../../assets/css/PresensiSiswa.css';
</style>

<style scoped>
.absen-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 48px 24px;
  gap: 16px;
  text-align: center;
}

.absen-clock {
  font-size: 3.5rem;
  font-weight: 800;
  letter-spacing: -.03em;
  font-variant-numeric: tabular-nums;
  background: linear-gradient(92deg, var(--brand-700), var(--pink-500));
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

.absen-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}

.absen-state-text { margin: 0; font-size: 1rem; color: var(--gray-700); }

.absen-big-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  width: 180px;
  height: 180px;
  border-radius: 50%;
  border: none;
  color: #ffffff;
  font-size: 1.1rem;
  font-weight: 800;
  font-family: inherit;
  cursor: pointer;
  transition: transform .18s var(--ease-spring), box-shadow .25s ease;
}
.absen-big-btn.masuk {
  background: linear-gradient(135deg, #34d399, #10b981);
  box-shadow: 0 16px 40px -10px rgba(16,185,129,.6);
}
.absen-big-btn.pulang {
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  box-shadow: 0 16px 40px -10px rgba(245,158,11,.6);
}
.absen-big-btn:hover:not(:disabled) { transform: scale(1.06); }
.absen-big-btn:active:not(:disabled) { transform: scale(.95); }
.absen-big-btn:disabled { opacity: .5; cursor: not-allowed; }
.absen-btn-icon { width: 36px; height: 36px; }

.absen-done-icon { width: 72px; height: 72px; color: #10b981; }

.absen-times-recap {
  display: flex;
  gap: 16px;
  font-size: .875rem;
  font-weight: 700;
  color: var(--gray-700);
  font-variant-numeric: tabular-nums;
}

.absen-gps {
  margin: 0;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: .78rem;
  color: var(--gray-500);
}
</style>