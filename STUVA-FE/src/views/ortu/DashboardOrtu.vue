<template>
  <div class="dashboard-container">
    <!-- Header -->
    <header class="header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="app-name">STUVA</h1>
        </div>
        <div class="header-right">
          <div class="pwa-badge-circle">
            <Wifi class="icon-xs" />
          </div>
          <div class="profile-wrapper">
            <button @click="toggleProfileMenu" class="profile-button">
              <div class="avatar">
                <User class="icon-sm" />
              </div>
              <ChevronDown class="icon-xs chevron" :class="{ 'rotated': showProfileMenu }" />
            </button>
            
            <!-- Profile Dropdown Menu -->
            <Transition name="dropdown">
              <div v-if="showProfileMenu" class="profile-dropdown">
                <div class="profile-header">
                  <div class="avatar-large">
                    <User class="icon-md" />
                  </div>
                  <div class="profile-info">
                    <p class="profile-name">{{ parent.name }}</p>
                    <p class="profile-role">Orang Tua</p>
                    <p class="profile-student">Wali dari {{ parent.studentName }}</p>
                  </div>
                </div>
                
                <div class="profile-menu">
                  <button @click="navigateTo('/ortu/profile')" class="menu-item">
                    <User class="icon-sm" />
                    <span>Profil Saya</span>
                  </button>
                  <div class="menu-divider"></div>
                  <button @click="handleLogout" class="menu-item logout">
                    <LogOut class="icon-sm" />
                    <span>Keluar</span>
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </header>

    <main class="main-content">
      <!-- Hero Greeting -->
      <section class="hero-greeting col-12">
        <div>
          <p class="hero-hi">{{ greeting }}, {{ parent.name.split(' ')[0] }} 👋</p>
          <p class="hero-sub">{{ todayLabel }} — Pantau kehadiran {{ parent.studentName }} di sini.</p>
        </div>
        <div class="hero-emoji">🌅</div>
      </section>

      <!-- Info Siswa Card -->
      <section class="card col-12">
        <h2 class="card-title">Informasi Siswa</h2>
        <div class="student-info-grid">
          <div class="info-item">
            <span class="info-label">Nama</span>
            <span class="info-value">{{ parent.studentName }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Kelas</span>
            <span class="info-value">{{ parent.studentClass }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">NISN</span>
            <span class="info-value">{{ parent.studentNISN }}</span>
          </div>
<div class="info-item" :class="attendanceStats.percentage < 90 ? 'tile-red' : 'tile-green'">
  <span class="info-label">Kehadiran</span>
  <span class="info-value" :class="attendanceStats.percentage < 90 ? 'text-red' : 'text-green'">
    {{ attendanceStats.percentage }}%
  </span>
</div>
        </div>
      </section>

            <!-- Rekap Presensi Anak -->
      <section class="card col-8">
        <h2 class="card-title">Rekap Presensi — {{ parent.studentName }}</h2>

        <!-- Progress Bar Persentase -->
        <div class="progress-section">
          <div class="progress-header">
            <span class="progress-label">Tingkat Kehadiran</span>
            <span
              class="progress-value"
              :class="attendanceStats.percentage < 90 ? 'text-red' : 'text-green'"
            >
              {{ attendanceStats.percentage }}%
            </span>
          </div>
          <div class="progress-bar-bg">
            <div
              class="progress-bar-fill"
              :class="attendanceStats.percentage < 90 ? 'bg-red' : 'bg-green'"
              :style="{ width: `${attendanceStats.percentage}%` }"
            ></div>
          </div>
        </div>

        <!-- Chart & Detail Stat -->
        <div class="summary-layout">
          <!-- Doughnut Chart -->
          <div class="chart-wrapper">
            <Doughnut :data="chartData" :options="chartOptions" />
            <div class="chart-center">
              <span
                class="chart-center-value"
                :class="attendanceStats.percentage < 90 ? 'text-red' : 'text-green-dark'"
              >
                {{ attendanceStats.percentage }}%
              </span>
              <span class="chart-center-label">Hadir</span>
            </div>
          </div>

          <!-- Stat Grid -->
          <div class="stat-grid">
            <div class="stat-box bg-green-light">
              <span class="stat-label text-green">Hadir</span>
              <span class="stat-value text-green-dark">{{ attendanceStats.hadir }}</span>
            </div>
            <div class="stat-box bg-yellow-light">
              <span class="stat-label text-yellow">Sakit</span>
              <span class="stat-value text-yellow-dark">{{ attendanceStats.sakit }}</span>
            </div>
            <div class="stat-box bg-purple-light">
              <span class="stat-label text-purple">Izin</span>
              <span class="stat-value text-purple-dark">{{ attendanceStats.izin }}</span>
            </div>
            <div class="stat-box bg-cyan-light">
              <span class="stat-label text-cyan">Dispen</span>
              <span class="stat-value text-cyan-dark">{{ attendanceStats.dispen }}</span>
            </div>
            <div class="stat-box bg-red-light">
              <span class="stat-label text-red">Alpha</span>
              <span class="stat-value text-red-dark">{{ attendanceStats.alpha }}</span>
            </div>
          </div>
        </div>

        <p class="summary-total">Total {{ totalHari }} hari tercatat semester ini</p>
      </section>

            <!-- Telemetri Perangkat Anak (LIVE) -->
      <section class="card col-4">
        <div class="card-title-row">
          <h2 class="card-title">Perangkat {{ parent.studentName }}</h2>
          <label class="auto-refresh-toggle">
            <input type="checkbox" v-model="autoRefresh" />
            Auto
          </label>
        </div>

        <div class="telemetry-list">
          <!-- Daya Baterai -->
          <div class="telemetry-item">
            <div class="telemetry-label">
              <BatteryCharging class="icon-sm text-blue" />
              <span>Daya Baterai</span>
            </div>
            <span class="telemetry-value-bold">
              {{ lastLoc?.battery_level != null ? `${lastLoc.battery_level}%` : '--' }}
            </span>
          </div>

          <!-- Koordinat -->
          <div class="telemetry-item">
            <div class="telemetry-label">
              <MapPin class="icon-sm text-red" />
              <span>Lokasi Terakhir</span>
            </div>
            <span class="telemetry-value-mono">
              {{ lastLoc
                ? `${Number(lastLoc.latitude).toFixed(5)}, ${Number(lastLoc.longitude).toFixed(5)}`
                : '--' }}
            </span>
          </div>

          <!-- Update terakhir -->
          <div class="telemetry-item">
            <div class="telemetry-label">
              <Clock class="icon-sm text-gray" />
              <span>Update Terakhir</span>
            </div>
            <span class="telemetry-value-sub">{{ lastUpdateLabel }}</span>
          </div>
        </div>

        <!-- status / error / refresh -->
        <p v-if="childLoading" class="loc-msg">⏳ Memuat data terbaru...</p>
        <p v-else-if="childError" class="loc-msg error">{{ childError }}</p>
        <p v-else-if="!lastLoc" class="loc-msg warn">
          Belum ada data. Minta anak menyalakan Tracking Lokasi di HP-nya.
        </p>

        <button @click="fetchChildStatus" class="btn-refresh" :disabled="childLoading">
          <RotateCw class="icon-sm" :class="{ spinning: childLoading }" />
          <span>Refresh</span>
        </button>
      </section>

      <!-- Pengajuan Menunggu Persetujuan -->
      <section class="card col-12">
        <div class="card-title-row">
          <h2 class="card-title">Menunggu Persetujuan</h2>
          <span v-if="pendingRequests.length > 0" class="count-chip">{{ pendingRequests.length }}</span>
        </div>

          <div v-if="loadingRequests" class="loading-state">
            <div class="spinner"></div>
            <p class="loading-text">Memuat pengajuan...</p>
          </div>

          <div v-else-if="pendingRequests.length === 0" class="empty-state">
            <CheckCircle class="icon-lg" />
            <p class="empty-text">Semua pengajuan telah diproses</p>
          </div>

          <div v-else class="request-list">
          <article
            v-for="request in pendingRequests"
            :key="request.id"
            class="request-card pending-item"
            :class="`t-${request.type}`"
          >
            <header class="request-card-head">
              <div class="request-type">
                <span class="type-icon" :class="`ti-${request.type}`">
                  <FileText v-if="request.type === 'izin'" class="icon-sm" />
                  <Thermometer v-else-if="request.type === 'sakit'" class="icon-sm" />
                  <Clock v-else class="icon-sm" />
                </span>
                <div>
                  <p class="type-name">{{ typeLabel(request.type) }}</p>
                  <p class="request-time">Diajukan {{ request.createdAt }}</p>
                </div>
              </div>
              <span class="request-status-badge status-pending">
                <span class="status-dot"></span>
                Menunggu
              </span>
            </header>

            <p class="request-description">{{ request.description }}</p>

            <div class="request-date-chip">
              <Calendar class="icon-sm" />
              <span>{{ formatDateRange(request.startDate, request.endDate) }}</span>
            </div>

            <div v-if="request.photo" class="request-photo">
              <img :src="request.photo" alt="Bukti" @click="openImageModal(request.photo)" @error="onImgError" />
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
              <button
                @click="openRejectModal(request)"
                class="btn btn-reject"
                :disabled="processingId === request.id"
              >
                <X class="icon-sm" />
                <span>Tolak</span>
              </button>
              <button
                @click="approveRequest(request.id)"
                class="btn btn-approve"
                :disabled="processingId === request.id"
              >
                <CheckCircle class="icon-sm" />
                <span>{{ processingId === request.id ? 'Memproses...' : 'Setujui' }}</span>
              </button>
            </div>
          </article>
        </div>
      </section>

      <!-- Riwayat Pengajuan -->
      <section class="card col-12">
        <div class="card-title-row">
          <h2 class="card-title">Riwayat Pengajuan</h2>
          <span v-if="processedRequests.length" class="count-chip count-chip-muted">{{ processedRequests.length }}</span>
        </div>

          <div v-if="loadingRequests" class="loading-state">
            <div class="spinner"></div>
            <p class="loading-text">Memuat riwayat...</p>
          </div>

          <div v-else-if="processedRequests.length === 0" class="empty-state">
            <FileText class="icon-lg" />
            <p class="empty-text">Belum ada riwayat</p>
          </div>

          <div v-else class="request-list">
          <article
            v-for="request in processedRequests"
            :key="request.id"
            class="request-card"
            :class="`t-${request.type}`"
          >
            <header class="request-card-head">
              <div class="request-type">
                <span class="type-icon" :class="`ti-${request.type}`">
                  <FileText v-if="request.type === 'izin'" class="icon-sm" />
                  <Thermometer v-else-if="request.type === 'sakit'" class="icon-sm" />
                  <Clock v-else class="icon-sm" />
                </span>
                <div>
                  <p class="type-name">{{ typeLabel(request.type) }}</p>
                  <p class="request-time">Diproses {{ request.processedAt }}</p>
                </div>
              </div>
              <span class="request-status-badge" :class="`status-${statusKey(request.status)}`">
                <span class="status-dot"></span>
                {{ getStatusLabel(request.status) }}
              </span>
            </header>

            <p class="request-description">{{ request.description }}</p>

            <div class="request-date-chip">
              <Calendar class="icon-sm" />
              <span>{{ formatDateRange(request.startDate, request.endDate) }}</span>
            </div>

            <div v-if="request.photo" class="request-photo">
              <img :src="request.photo" alt="Bukti" @click="openImageModal(request.photo)" @error="onImgError" />
            </div>

            <div v-if="request.status === 'rejected' && request.rejectionNote" class="rejection-note">
              <AlertTriangle class="icon-sm text-red shrink-0" />
              <div>
                <p class="rejection-label">Alasan Penolakan</p>
                <p class="rejection-text">{{ request.rejectionNote }}</p>
              </div>
            </div>
          </article>
        </div>
      </section>
    </main>

    <!-- Modal Penolakan -->
    <Teleport to="body">
      <div v-if="showRejectModal" class="modal-overlay" @click="closeRejectModal">
        <div class="modal-container" @click.stop>
          <div class="modal-header">
            <h3 class="modal-title">Alasan Penolakan</h3>
            <button @click="closeRejectModal" class="btn-close">
              <X class="icon-sm" />
            </button>
          </div>

          <form @submit.prevent="rejectRequest" class="modal-body">
            <div class="form-group">
              <label for="rejectionNote" class="form-label">
                Tuliskan alasan penolakan
              </label>
              <textarea 
                id="rejectionNote" 
                v-model="rejectionNote" 
                class="form-textarea"
                rows="4"
                placeholder="Contoh: Tidak ada bukti surat keterangan dokter..."
                required
              ></textarea>
            </div>

            <div class="modal-footer">
              <button type="button" @click="closeRejectModal" class="btn btn-cancel">
                Batal
              </button>
              <button type="submit" class="btn btn-submit btn-danger" :disabled="isProcessing">
                {{ isProcessing ? 'Memproses...' : 'Kirim Penolakan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Gambar -->
    <Teleport to="body">
      <div v-if="showImageModal" class="modal-overlay" @click="closeImageModal">
        <div class="image-modal-container" @click.stop>
          <button @click="closeImageModal" class="btn-close-image">
            <X class="icon-md" />
          </button>
          <img :src="selectedImage" alt="Bukti" class="modal-image" />
        </div>
      </div>
    </Teleport>

    <!-- Toast Notification -->
    <Teleport to="body">
      <div v-if="showToast" class="toast-notification" :class="toastType">
        <div class="toast-content">
          <CheckCircle v-if="toastType === 'success'" class="icon-sm" />
          <AlertTriangle v-else class="icon-sm" />
          <span>{{ toastMessage }}</span>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import apiClient from '../../utils/api'
import { Doughnut } from 'vue-chartjs'
import { getChildStatus } from '../../services/locationService'
import {
  Wifi,
  AlertTriangle,
  X,
  CheckCircle,
  Calendar,
  FileText,
  User,
  ChevronDown,
  Settings,
  LogOut,
  Thermometer,
  Clock,
  BatteryCharging,
  MapPin,
  RotateCw
} from 'lucide-vue-next'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const router = useRouter()
const authStore = useAuthStore()

// ===== UI State =====
const showRejectModal = ref(false)
const showImageModal = ref(false)
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')
const rejectionNote = ref('')
const selectedRequestId = ref(null)
const selectedImage = ref(null)
const isProcessing = ref(false)
const processingId = ref(null)
const showProfileMenu = ref(false)

// ===== Data State =====
const loadingRequests = ref(true)
const allRequests = ref([])

const parent = ref({
  name: authStore.user?.name || 'Bapak/Ibu Orang Tua',
  studentName: 'Siswa Test',
  studentClass: 'XII RPL 1',
  studentNISN: '1234567890',
  attendancePercentage: 88
})

// ===== Rekap Presensi Anak =====
const attendanceStats = ref({
  percentage: 0,
  hadir: 0,
  sakit: 0,
  izin: 0,
  dispen: 0,
  alpha: 0
})

// ===== Telemetri Perangkat Anak (LIVE dari /ortu/child-status) =====
const childStatus = ref(null)      // seluruh data dari API
const childLoading = ref(false)
const childError = ref(null)
const autoRefresh = ref(true)
let pollTimer = null

const fetchChildStatus = async () => {
  childLoading.value = true
  try {
    const res = await getChildStatus()
    childStatus.value = res.data || null
    childError.value = null
  } catch (error) {
    console.error('❌ Error fetching child status:', error)
    // 400 = ortu belum dihubungkan ke siswa
    childError.value = error.response?.data?.message || 'Gagal mengambil data anak'
    childStatus.value = null
  } finally {
    childLoading.value = false
  }
}

// polling tiap 30 detik selama dashboard terbuka
const startPolling = () => {
  fetchChildStatus()
  pollTimer = setInterval(() => {
    if (autoRefresh.value && !document.hidden) fetchChildStatus()
  }, 30000)
}

const stopPolling = () => {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

// data lokasi anak — bentuk response: data.last_location
const lastLoc = computed(() => childStatus.value?.last_location || null)

const lastUpdateLabel = computed(() => {
  if (!lastLoc.value?.updated_at) return '—'
  return formatTimeAgo(lastLoc.value.updated_at)
})

// ===== Chart =====
const totalHari = computed(() => {
  const { hadir, sakit, izin, dispen, alpha } = attendanceStats.value
  return hadir + sakit + izin + dispen + alpha
})

const chartData = computed(() => {
  const { hadir, sakit, izin, dispen, alpha } = attendanceStats.value
  const total = hadir + sakit + izin + dispen + alpha

  if (total === 0) {
    return {
      labels: ['Belum ada data'],
      datasets: [{
        data: [1],
        backgroundColor: ['#e5e7eb'],
        borderWidth: 0,
        borderRadius: 0,
        spacing: 0
      }]
    }
  }

  return {
    labels: ['Hadir', 'Sakit', 'Izin', 'Dispen', 'Alpha'],
    datasets: [{
      data: [hadir, sakit, izin, dispen, alpha],
      backgroundColor: ['#10b981', '#f59e0b', '#8b5cf6', '#06b6d4', '#f43f5e'],
      hoverBackgroundColor: ['#059669', '#d97706', '#7c3aed', '#0891b2', '#e11d48'],
      borderWidth: 0,
      borderRadius: 6,
      spacing: 3,
      hoverOffset: 8
    }]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  cutout: '75%',
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgba(67, 20, 7, 0.92)',
      titleColor: '#ffffff',
      bodyColor: '#f5f0ea',
      padding: 10,
      cornerRadius: 10,
      usePointStyle: true,
      boxWidth: 8,
      boxHeight: 8,
      callbacks: {
        label: (ctx) => ` ${ctx.parsed} hari`
      }
    }
  },
  animation: {
    duration: 900,
    easing: 'easeOutQuart',
    animateRotate: true
  }
}

// ===== Fetch statistik presensi anak =====
const normalizeStatus = (s) => {
  if (!s) return 'alpha'
  const v = String(s).toLowerCase().trim()
  const map = {
    hadir: 'hadir', present: 'hadir', masuk: 'hadir',
    izin: 'izin', permission: 'izin',
    sakit: 'sakit', sick: 'sakit',
    dispen: 'dispen', dispensasi: 'dispen',
    alpha: 'alpha', absent: 'alpha', alpa: 'alpha'
  }
  return map[v] || v
}

const fetchAttendanceStats = async () => {
  try {
    console.log('📡 Fetching attendance stats (ortu)...')
    const res = await apiClient.get('/ortu/attendance-history', {
      params: { per_page: 500 }
    })

    const root = res.data || {}

    // Backend kirim "summary" — langsung pakai (format sama dengan siswa)
    const s = root.summary
    if (s) {
      attendanceStats.value = {
        hadir: s.hadir || 0,
        sakit: s.sakit || 0,
        izin: s.izin || 0,
        dispen: s.dispen || 0,
        alpha: s.alpha || 0,
        percentage: 0
      }
    } else {
      // Fallback: hitung manual dari items
      const items = Array.isArray(root.data)
        ? root.data
        : Array.isArray(root.data?.data) ? root.data.data : []

      const stats = { hadir: 0, sakit: 0, izin: 0, dispen: 0, alpha: 0 }
      items.forEach(item => {
        const k = normalizeStatus(item.status)
        if (stats[k] !== undefined) stats[k]++
      })
      attendanceStats.value = { ...stats, percentage: 0 }
    }

    // Persentase = hadir / total
    const t = totalHari.value
    attendanceStats.value.percentage = t > 0
      ? Math.round((attendanceStats.value.hadir / t) * 100)
      : 0

    console.log('✅ Attendance stats:', attendanceStats.value)
  } catch (error) {
    console.error('❌ Error fetching attendance stats:', error)
  }
}

// greeting based on time of day

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 11) return 'Selamat pagi'
  if (h < 15) return 'Selamat siang'
  if (h < 19) return 'Selamat sore'
  return 'Selamat malam'
})

const todayLabel = new Date().toLocaleDateString('id-ID', {
  weekday: 'long', day: 'numeric', month: 'long'
})

// ===== Helpers =====

// 📌 fix foto bukti broken (sama seperti siswa)
const buildPhotoUrl = (path) => {
  if (!path) return null
  if (path.startsWith('http')) return path
  const base = import.meta.env.VITE_API_URL.replace('/api', '')
  return `${base}/storage/${path}`
}

const formatDateRange = (start, end) => {
  const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  }

  if (start === end) {
    return formatDate(start)
  }
  return `${formatDate(start)} - ${formatDate(end)}`
}

const formatTimeAgo = (dateString) => {
  if (!dateString) return 'Baru saja'
  const diffMs = Date.now() - new Date(dateString)
  const mins = Math.floor(diffMs / 60000)
  const hours = Math.floor(diffMs / 3600000)
  const days = Math.floor(diffMs / 86400000)
  if (mins < 1) return 'Baru saja'
  if (mins < 60) return `${mins} menit yang lalu`
  if (hours < 24) return `${hours} jam yang lalu`
  if (days === 1) return '1 hari yang lalu'
  if (days < 7) return `${days} hari yang lalu`
  return `${Math.floor(days / 7)} minggu yang lalu`
}

const typeLabel = (type) =>
  ({ izin: 'Izin', sakit: 'Sakit', dispen: 'Dispensasi' }[type] || type)

const statusKey = (status) => {
  if (!status) return 'pending'
  if (status === 'pending_parent') return 'pending'
  if (status === 'pending_teacher') return 'school'
  if (status === 'rejected_parent' || status === 'rejected_teacher') return 'rejected'
  return status // approved
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Menunggu Kamu',
    school: 'Diproses Guru',
    approved: 'Disetujui',
    rejected: 'Ditolak'
  }
  return labels[statusKey(status)] || status
}

const onImgError = (event) => {
  event.target.closest('.request-photo')?.classList.add('photo-hidden')
}

// ===== Fetch dari API =====
const fetchRequests = async () => {
  loadingRequests.value = true
  try {
    console.log('📡 Fetching permissions...')
    const response = await apiClient.get('/permissions')  // ← GANTI ke sini
    const permissions = response.data.data || response.data || []

    allRequests.value = permissions.map(item => {
      const isProcessed = item.status !== 'pending_parent'
      return {
        id: item.id,
        type: item.type,
        startDate: item.start_date,
        endDate: item.end_date,
        description: item.reason || item.description,
        photo: buildPhotoUrl(item.attachment),
        status: item.status,
        createdAt: formatTimeAgo(item.created_at),
        processedAt: isProcessed ? formatTimeAgo(item.processed_at || item.updated_at) : null,
        rejectionNote: item.rejection_reason
      }
    })
  } catch (error) {
    console.error('❌ Error fetching requests:', error)
    allRequests.value = []
  } finally {
    loadingRequests.value = false
  }
}

// ===== Computed =====
const pendingRequests = computed(() =>
  allRequests.value.filter(req => req.status === 'pending_parent')
)

const processedRequests = computed(() =>
  allRequests.value.filter(req => req.status !== 'pending_parent')
)

// ===== Actions =====
const displayToast = (message, type = 'success') => {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
  setTimeout(() => { showToast.value = false }, 3000)
}

const openRejectModal = (request) => {
  selectedRequestId.value = request.id
  rejectionNote.value = ''
  showRejectModal.value = true
}

const closeRejectModal = () => {
  showRejectModal.value = false
  selectedRequestId.value = null
  rejectionNote.value = ''
}

const openImageModal = (imageUrl) => {
  selectedImage.value = imageUrl
  showImageModal.value = true
}

const closeImageModal = () => {
  showImageModal.value = false
  selectedImage.value = null
}

const approveRequest = async (requestId) => {
  processingId.value = requestId
  isProcessing.value = true
  try {
    await apiClient.post(`/permissions/${requestId}/parent-approve`, {
      action: 'approve'
    })
    displayToast('Disetujui! Pengajuan diteruskan ke guru.', 'success')
    await fetchRequests()
  } catch (error) {
    console.error('❌ Error approving:', error)
    let msg = 'Gagal menyetujui pengajuan.'
    if (error.response?.data?.message) msg = error.response.data.message
    displayToast(msg, 'error')
  } finally {
    processingId.value = null
    isProcessing.value = false
  }
}

const rejectRequest = async () => {
  isProcessing.value = true
  try {
    await apiClient.post(`/permissions/${selectedRequestId.value}/parent-approve`, {
      action: 'reject',
      rejection_reason: rejectionNote.value
    })
    closeRejectModal()
    displayToast('Pengajuan ditolak.', 'success')
    await fetchRequests()
  } catch (error) {
    console.error('❌ Error rejecting:', error)
    let msg = 'Gagal menolak pengajuan.'
    if (error.response?.data?.message) msg = error.response.data.message
    displayToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

// ===== Profile menu =====
const toggleProfileMenu = () => {
  showProfileMenu.value = !showProfileMenu.value
}

const navigateTo = (path) => {
  showProfileMenu.value = false
  router.push(path)
}

const handleLogout = async () => {
  showProfileMenu.value = false
  try {
    await authStore.logout()
  } catch (error) {
    console.error('❌ Logout error:', error)
  } finally {
    router.push({ path: '/login', query: { logout: 'success' } })
  }
}

// ===== Lifecycle =====
let clickOutsideHandler = null

onMounted(() => {
  clickOutsideHandler = (e) => {
    const profileWrapper = document.querySelector('.profile-wrapper')
    if (profileWrapper && !profileWrapper.contains(e.target)) {
      showProfileMenu.value = false
    }
  }
  document.addEventListener('click', clickOutsideHandler)

  fetchRequests()
  fetchAttendanceStats()
  startPolling()
})

onUnmounted(() => {
  if (clickOutsideHandler) {
    document.removeEventListener('click', clickOutsideHandler)
  }
  stopPolling()
})
</script>

<style scoped>
@import '../../assets/css/DashboardOrtu.css';
</style>
