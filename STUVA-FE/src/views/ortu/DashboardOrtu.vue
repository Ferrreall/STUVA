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
          <div class="info-item" :class="parent.attendancePercentage < 90 ? 'tile-red' : 'tile-green'">
            <span class="info-label">Kehadiran</span>
            <span class="info-value" :class="parent.attendancePercentage < 90 ? 'text-red' : 'text-green'">
              {{ parent.attendancePercentage }}%
            </span>
          </div>
        </div>
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
  Clock
} from 'lucide-vue-next'

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
})

onUnmounted(() => {
  if (clickOutsideHandler) {
    document.removeEventListener('click', clickOutsideHandler)
  }
})
</script>

<style scoped>
@import '../../assets/css/DashboardOrTu.css';
</style>
