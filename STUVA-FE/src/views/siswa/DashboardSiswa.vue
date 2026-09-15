<template>
  <div class="dashboard-container">
    <!-- Header -->
    <header class="header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="app-name">STUVA</h1>
        </div>
        <div class="header-right">
          <div class="pwa-badge">
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
                    <p class="profile-name">{{ user.name }}</p>
                    <p class="profile-class">{{ user.class_name }}</p>
                    <p class="profile-nisn">NISN: {{ user.username }}</p>
                  </div>
                </div>
                
                <div class="profile-menu">
                  <button @click="navigateTo('/siswa/profile')" class="menu-item">
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
<p class="hero-hi">{{ greeting }}, {{ user.name.split(' ')[0] }} 👋</p>
<p class="hero-sub">{{ todayLabel }} — Jangan lupa absen hari ini, ya!</p>
        </div>
        <div class="hero-emoji">📚</div>
      </section>
      <!-- Sticky Banner Warning 90% (Conditional Rendering) -->
      <div 
        v-if="showWarningBanner && attendanceStats.percentage < 90" 
        class="warning-banner col-12"
      >
        <div class="warning-body">
          <AlertTriangle class="icon-md text-red shrink-0" />
          <div>
            <h3 class="warning-title">Peringatan Kehadiran</h3>
            <p class="warning-desc">
              Presensi kamu saat ini {{ attendanceStats.percentage }}%. Batas minimum kehadiran adalah 90%.
            </p>
          </div>
        </div>
        <button @click="dismissBanner" class="btn-dismiss">
          <X class="icon-sm" />
        </button>
      </div>

<!-- Card Ringkasan Absensi -->
<section class="card col-8">
  <h2 class="card-title">Rekap Presensi Semester</h2>

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
      <div class="stat-box bg-blue-light">
        <span class="stat-label text-blue">Izin</span>
        <span class="stat-value text-blue-dark">{{ attendanceStats.izin }}</span>
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

      <!-- Telemetri MDM Status (Simulasi Hardcode PWA) -->
      <section class="card col-4">
        <h2 class="card-title">Status Perangkat & Telemetri</h2>
        
        <div class="telemetry-list">
          <div class="telemetry-item">
            <div class="telemetry-label">
              <BatteryCharging class="icon-sm text-blue" />
              <span>Daya Baterai</span>
            </div>
            <span class="telemetry-value-bold">{{ mdmStatus.battery }}%</span>
          </div>

          <div class="telemetry-item">
            <div class="telemetry-label">
              <MapPin class="icon-sm text-red" />
              <span>Lokasi Terakhir</span>
            </div>
            <span class="telemetry-value-mono">
              {{ mdmStatus.latitude }}, {{ mdmStatus.longitude }}
            </span>
          </div>

          <div class="telemetry-item">
            <div class="telemetry-label">
              <Clock class="icon-sm text-gray" />
              <span>Terakhir Diperbarui</span>
            </div>
            <span class="telemetry-value-sub">{{ mdmStatus.lastSync }}</span>
          </div>
        </div>
      </section>

      <!-- Quick Action Buttons -->
      <section class="action-grid col-12">
        <button @click="openModal" class="btn btn-primary">
          <FilePlus class="icon-md" />
          <span>Ajukan Izin</span>
        </button>

        <button @click="navigateTo('/siswa/presensi')" class="btn btn-secondary">
          <Calendar class="icon-md text-gray" />
          <span>Riwayat Absen</span>
        </button>
      </section>

      <!-- Riwayat Pengajuan Izin/Sakit/Dispen -->
      <!-- Riwayat Pengajuan Izin/Sakit/Dispen -->
      <section class="card col-12">
        <div class="card-title-row">
          <h2 class="card-title">Riwayat Pengajuan</h2>
          <span v-if="requestHistory.length" class="count-chip">{{ requestHistory.length }}</span>
        </div>

        <div v-if="loadingHistory" class="loading-state">
          <div class="spinner"></div>
          <p class="loading-text">Memuat riwayat...</p>
        </div>

        <div v-else-if="requestHistory.length === 0" class="empty-state">
          <FileText class="icon-lg" />
          <p class="empty-text">Belum ada pengajuan</p>
        </div>

        <div v-else class="request-grid">
          <article
            v-for="request in requestHistory"
            :key="request.id"
            class="request-card"
            :class="`t-${request.type}`"
          >
            <!-- Head: ikon + jenis + status -->
            <header class="request-card-head">
              <div class="request-type">
                <span class="type-icon" :class="`ti-${request.type}`">
                  <FileText v-if="request.type === 'izin'" class="icon-sm" />
                  <Thermometer v-else-if="request.type === 'sakit'" class="icon-sm" />
                  <Clock v-else class="icon-sm" />
                </span>
                <div>
                  <p class="type-name">{{ typeLabel(request.type) }}</p>
                  <p class="request-time">{{ request.createdAt }}</p>
                </div>
              </div>
              <span class="request-status-badge" :class="`status-${statusKey(request.status)}`">
                <span class="status-dot"></span>
                {{ getStatusLabel(request.status) }}
              </span>
            </header>

            <p class="request-description">{{ request.description }}</p>

            <!-- Tanggal -->
            <div class="request-date-chip">
              <Calendar class="icon-sm" />
              <span>{{ formatDateRange(request.startDate, request.endDate) }}</span>
            </div>

            <!-- Bukti foto -->
            <div v-if="request.photo" class="request-photo">
              <img :src="request.photo" alt="Bukti pengajuan" @error="onImgError" />
            </div>

            <!-- Catatan penolakan -->
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

    <!-- Modal Pengajuan Izin/Sakit/Dispen -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-container" @click.stop>
          <div class="modal-header">
            <h3 class="modal-title">Ajukan Izin/Sakit/Dispen</h3>
            <button @click="closeModal" class="btn-close">
              <X class="icon-sm" />
            </button>
          </div>

          <form @submit.prevent="submitRequest" class="modal-body">
            <!-- Dropdown Jenis Pengajuan -->
            <div class="form-group">
              <label for="type" class="form-label">Jenis Pengajuan</label>
              <select 
                id="type" 
                v-model="formData.type" 
                class="form-select"
                required
              >
                <option value="" disabled>Pilih jenis pengajuan</option>
                <option value="izin">Izin</option>
                <option value="sakit">Sakit</option>
                <option value="dispen">Dispensasi</option>
              </select>
            </div>

            <!-- Tanggal Mulai -->
            <div class="form-group">
              <label for="startDate" class="form-label">Tanggal Mulai</label>
              <input 
                type="date" 
                id="startDate" 
                v-model="formData.startDate" 
                class="form-input"
                required
              />
            </div>

            <!-- Tanggal Selesai -->
            <div class="form-group">
              <label for="endDate" class="form-label">Tanggal Selesai</label>
              <input 
                type="date" 
                id="endDate" 
                v-model="formData.endDate" 
                class="form-input"
                :min="formData.startDate"
                required
              />
            </div>

            <!-- Keterangan -->
            <div class="form-group">
              <label for="description" class="form-label">Keterangan</label>
              <textarea 
                id="description" 
                v-model="formData.description" 
                class="form-textarea"
                rows="4"
                placeholder="Tuliskan alasan pengajuan..."
                required
              ></textarea>
            </div>

            <!-- Upload Foto -->
            <div class="form-group">
              <label for="photo" class="form-label">Upload Foto (Opsional)</label>
              <input 
                type="file" 
                id="photo" 
                @change="handleFileUpload" 
                class="form-file"
                accept="image/*"
              />
              <p class="form-hint">Format: JPG, PNG. Maksimal 5MB</p>
              <p v-if="formData.photo" class="file-name">{{ formData.photo.name }}</p>
            </div>

            <!-- Tombol Submit -->
            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn btn-cancel">
                Batal
              </button>
              <button type="submit" class="btn btn-submit" :disabled="isSubmitting">
                {{ isSubmitting ? 'Mengirim...' : 'Kirim Pengajuan' }}
              </button>
            </div>
          </form>
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
import { Thermometer } from 'lucide-vue-next' // tambahin ke import lucide yang udah ada
import { 
  Wifi, 
  AlertTriangle, 
  X, 
  BatteryCharging, 
  MapPin, 
  Clock, 
  FilePlus, 
  Calendar,
  CheckCircle,
  FileText,
  User,
  ChevronDown,
  Settings,
  LogOut
} from 'lucide-vue-next'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

// 📌 fix foto bukti broken — prefix /storage/
const buildPhotoUrl = (path) => {
  if (!path) return null
  if (path.startsWith('http')) return path
  const base = import.meta.env.VITE_API_URL.replace('/api', '')
  return `${base}/storage/${path}`
}

// ===== Chart Presensi =====
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
    legend: { display: false }, // legend sudah direpresentasikan stat boxes
    tooltip: {
      backgroundColor: 'rgba(46, 16, 101, 0.92)',
      titleColor: '#ffffff',
      bodyColor: '#e2e8f0',
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

const router = useRouter()
const authStore = useAuthStore()

const showWarningBanner = ref(true)
const showModal = ref(false)
const isSubmitting = ref(false)
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')
const showProfileMenu = ref(false)

const user = ref({
  name: authStore.user?.name || 'Siswa Test',
  username: authStore.user?.username || '1234567890',
  class_name: authStore.user?.class_name || 'XII RPL 1'
})

// greeting
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

const attendanceStats = ref({
  percentage: 0,
  hadir: 0,
  sakit: 0,
  izin: 0,
  dispen: 0,
  alpha: 0
})

const mdmStatus = ref({
  battery: 85,
  latitude: -6.917464,
  longitude: 107.619123,
  lastSync: '10 menit yang lalu'
})

const formData = ref({
  type: '',
  startDate: '',
  endDate: '',
  description: '',
  photo: null
})

// Data riwayat pengajuan
const requestHistory = ref([])
const loadingHistory = ref(false)

// Fetch riwayat pengajuan dari API
const fetchPermissions = async () => {
  loadingHistory.value = true
  
  try {
    console.log('📡 Fetching permissions history...')
    const response = await apiClient.get('/permissions')
    
    console.log('✅ Permissions data received:', response.data)
    
    // Map data dari API ke format yang digunakan di component
    const permissions = response.data.data || response.data || []
    requestHistory.value = permissions.map(item => ({
      id: item.id,
      type: item.type,
      startDate: item.start_date,
      endDate: item.end_date,
      description: item.reason || item.description,
      photo: buildPhotoUrl(item.attachment),
      status: item.status,
      createdAt: formatTimeAgo(item.created_at),
      rejectionNote: item.rejection_reason || item.rejectionNote
    }))
    
  } catch (error) {
    console.error('❌ Error fetching permissions:', error)
    // Jika error, tetap gunakan array kosong
    requestHistory.value = []
  } finally {
    loadingHistory.value = false
  }
}

// ===== Fetch statistik presensi (API sama dengan halaman riwayat) =====
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
    console.log('📡 Fetching attendance stats...')
    const res = await apiClient.get('/siswa/attendance-history', {
      params: { per_page: 500 }
    })

    const root = res.data || {}

    // Backend sudah kirim "summary" — langsung pakai
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
      // Fallback: hitung sendiri dari items kalau summary nggak ada
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

// Format waktu relatif
const formatTimeAgo = (dateString) => {
  if (!dateString) return 'Baru saja'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)
  
  if (diffMins < 1) return 'Baru saja'
  if (diffMins < 60) return `${diffMins} menit yang lalu`
  if (diffHours < 24) return `${diffHours} jam yang lalu`
  if (diffDays === 1) return '1 hari yang lalu'
  if (diffDays < 7) return `${diffDays} hari yang lalu`
  if (diffDays < 30) return `${Math.floor(diffDays / 7)} minggu yang lalu`
  return `${Math.floor(diffDays / 30)} bulan yang lalu`
}

const dismissBanner = () => {
  showWarningBanner.value = false
}

const navigateTo = (path) => {
  showProfileMenu.value = false
  router.push(path)
}

const toggleProfileMenu = () => {
  showProfileMenu.value = !showProfileMenu.value
}

const handleLogout = async () => {
  showProfileMenu.value = false
  try {
    await authStore.logout()
  } catch (error) {
    console.error('Logout error:', error)
  } finally {
    // Langsung push tanpa setTimeout, bawa query loggedOut
    router.push({ path: '/login', query: { logout: 'success' } })
  }
}

const openModal = () => {
  showModal.value = true
  // Reset form
  formData.value = {
    type: '',
    startDate: '',
    endDate: '',
    description: '',
    photo: null
  }
}

const closeModal = () => {
  showModal.value = false
}

const handleFileUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Validasi ukuran file (5MB)
    if (file.size > 5 * 1024 * 1024) {
      displayToast('Ukuran file maksimal 5MB', 'error')
      event.target.value = ''
      return
    }
    formData.value.photo = file
  }
}

const displayToast = (message, type = 'success') => {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
  
  setTimeout(() => {
    showToast.value = false
  }, 3000)
}

const submitRequest = async () => {
  isSubmitting.value = true
  
  try {
    console.log('📡 Submitting permission request...')
    console.log('🔑 Token:', localStorage.getItem('token') ? 'exists' : 'missing')
    
    // Kirim ke API /permission dengan FormData
    const requestData = new FormData()
    requestData.append('type', formData.value.type)
    requestData.append('start_date', formData.value.startDate)
    requestData.append('end_date', formData.value.endDate)
    requestData.append('reason', formData.value.description)
    
    if (formData.value.photo) {
      requestData.append('attachment', formData.value.photo)
      console.log('📎 Attachment:', formData.value.photo.name)
    }
    
    console.log('📤 Sending data:', {
      type: formData.value.type,
      start_date: formData.value.startDate,
      end_date: formData.value.endDate,
      reason: formData.value.description
    })
    
    const response = await apiClient.post('/permissions', requestData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    console.log('✅ Permission request submitted:', response.data)
    
    closeModal()
    displayToast('Pengajuan berhasil dikirim! Menunggu persetujuan orang tua.', 'success')
    
    // Refresh riwayat setelah berhasil submit
    await fetchPermissions()
    
  } catch (error) {
    console.error('❌ Error submitting request:', error)
    console.error('❌ Error response:', error.response?.data)
    console.error('❌ Error status:', error.response?.status)
    
    let errorMessage = 'Gagal mengirim pengajuan. Silakan coba lagi.'
    
    if (error.response?.status === 401) {
      errorMessage = 'Session expired. Silakan login kembali.'
      // Redirect manual ke login setelah 2 detik
      setTimeout(() => {
        localStorage.clear()
        router.push('/login')
      }, 2000)
    } else if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    } else if (error.message) {
      errorMessage = error.message
    }
    
    displayToast(errorMessage, 'error')
  } finally {
    isSubmitting.value = false
  }
}

const typeLabel = (type) =>
  ({ izin: 'Izin', sakit: 'Sakit', dispen: 'Dispensasi' }[type] || type)

// normalisasi: pending_parent / pending_admin / apapun → 'pending'
const statusKey = (status) => {
  if (!status) return 'pending'
  if (status === 'pending_parent') return 'parent'
  if (status === 'pending_teacher') return 'school'
  if (status === 'rejected_parent' || status === 'rejected_teacher') return 'rejected'
  return status
}

const getStatusLabel = (status) => {
  const labels = {
    parent: 'Menunggu Ortu',
    school: 'Diproses Sekolah',
    approved: 'Disetujui',
    rejected: 'Ditolak'
  }
  return labels[statusKey(status)] || status
}

// sembunyikan gambar yang gagal load, jangan tampil ikon rusak
const onImgError = (event) => {
  event.target.closest('.request-photo')?.classList.add('photo-hidden')
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

// Close dropdown when clicking outside
let clickOutsideHandler = null

onMounted(() => {
  clickOutsideHandler = (e) => {
    const profileWrapper = document.querySelector('.profile-wrapper')
    if (profileWrapper && !profileWrapper.contains(e.target)) {
      showProfileMenu.value = false
    }
  }
  document.addEventListener('click', clickOutsideHandler)
  
  // Fetch riwayat pengajuan saat component dimount
  fetchPermissions()
  fetchAttendanceStats()
})

onUnmounted(() => {
  if (clickOutsideHandler) {
    document.removeEventListener('click', clickOutsideHandler)
  }
})




</script>

<style scoped>
@import '../../assets/css/DashboardSiswa.css';
</style>