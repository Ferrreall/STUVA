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
                    <p class="profile-name">{{ teacher.name }}</p>
                    <p class="profile-class">{{ teacher.subject }}</p>
                    <p class="profile-nisn">NIP: {{ teacher.nip }}</p>
                  </div>
                </div>
                
                <div class="profile-menu">
                  <button @click="navigateTo('/guru/profile')" class="menu-item">
                    <User class="icon-sm" />
                    <span>Profil Saya</span>
                  </button>
                  <button @click="navigateTo('/guru/settings')" class="menu-item">
                    <Settings class="icon-sm" />
                    <span>Pengaturan</span>
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
      <!-- Quick Stats -->
      <section class="stats-grid col-12">
        <div class="stat-card bg-blue">
          <div class="stat-icon">
            <Users class="icon-lg" />
          </div>
          <div class="stat-content">
            <p class="stat-value">{{ stats.totalStudents }}</p>
            <p class="stat-label">Total Siswa</p>
          </div>
        </div>

        <div class="stat-card bg-green">
          <div class="stat-icon">
            <CheckCircle class="icon-lg" />
          </div>
          <div class="stat-content">
            <p class="stat-value">{{ stats.presentToday }}</p>
            <p class="stat-label">Hadir Hari Ini</p>
          </div>
        </div>

        <div class="stat-card bg-red">
          <div class="stat-icon">
            <AlertTriangle class="icon-lg" />
          </div>
          <div class="stat-content">
            <p class="stat-value">{{ stats.absentToday }}</p>
            <p class="stat-label">Tidak Hadir</p>
          </div>
        </div>

        <div class="stat-card bg-yellow">
          <div class="stat-icon">
            <Clock class="icon-lg" />
          </div>
          <div class="stat-content">
            <p class="stat-value">{{ pendingRequests.length }}</p>
            <p class="stat-label">Pengajuan Pending</p>
          </div>
        </div>
      </section>

      <!-- Kelas yang Diajar -->
      <section class="card col-8">
        <h2 class="card-title">Kelas yang Diajar</h2>
        
        <div class="class-list">
          <div 
            v-for="classItem in classes" 
            :key="classItem.id" 
            class="class-item"
            @click="navigateTo(`/guru/kelas/${classItem.id}`)"
          >
            <div class="class-header">
              <div class="class-info">
                <h3 class="class-name">{{ classItem.name }}</h3>
                <p class="class-subject">{{ classItem.subject }}</p>
              </div>
              <div class="class-count">
                <Users class="icon-sm text-blue" />
                <span>{{ classItem.studentCount }} siswa</span>
              </div>
            </div>
            
            <div class="class-stats">
              <div class="stat-item">
                <span class="stat-label">Hadir</span>
                <span class="stat-value text-green">{{ classItem.present }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Izin</span>
                <span class="stat-value text-blue">{{ classItem.permission }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Sakit</span>
                <span class="stat-value text-yellow">{{ classItem.sick }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Alpha</span>
                <span class="stat-value text-red">{{ classItem.absent }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Jadwal Mengajar Hari Ini -->
      <section class="card col-4">
        <h2 class="card-title">Jadwal Mengajar Hari Ini</h2>
        
        <div class="schedule-list">
          <div 
            v-for="schedule in todaySchedule" 
            :key="schedule.id" 
            class="schedule-item"
          >
            <div class="schedule-time">
              <Clock class="icon-sm text-blue" />
              <span>{{ schedule.startTime }} - {{ schedule.endTime }}</span>
            </div>
            <div class="schedule-details">
              <h3 class="schedule-class">{{ schedule.className }}</h3>
              <p class="schedule-subject">{{ schedule.subject }}</p>
              <p class="schedule-room">Ruang: {{ schedule.room }}</p>
            </div>
            <button 
              @click="takeAttendance(schedule.id)" 
              class="btn btn-primary-sm"
            >
              Absen
            </button>
          </div>
        </div>
      </section>

      <!-- Pengajuan Izin/Sakit Terbaru -->
      <section class="card col-12">
        <div class="section-header">
          <div class="card-title-row">
            <h2 class="card-title">Menunggu Verifikasi</h2>
            <span v-if="pendingRequests.length" class="count-chip">{{ pendingRequests.length }}</span>
          </div>
          <button @click="navigateTo('/guru/pengajuan')" class="btn-see-all">
            Lihat Semua
            <ChevronRight class="icon-xs" />
          </button>
        </div>

        <div v-if="loadingRequests" class="loading-state">
          <div class="spinner"></div>
          <p class="loading-text">Memuat pengajuan...</p>
        </div>

        <div v-else-if="pendingRequests.length === 0" class="empty-state">
          <CheckCircle class="icon-lg" />
          <p class="empty-text">Semua pengajuan telah diverifikasi</p>
        </div>

        <div v-else class="request-list">
          <article
            v-for="request in pendingRequests"
            :key="request.id"
            class="request-item"
            :class="`t-${request.type}`"
          >
            <div class="request-student">
              <div class="student-avatar">
                <User class="icon-sm" />
              </div>
              <div class="student-info">
                <p class="student-name">{{ request.studentName }}</p>
                <p class="student-class">{{ request.className }}</p>
                <p class="request-time">{{ request.createdAt }}</p>
              </div>
            </div>

            <div class="request-details">
              <div class="request-details-top">
                <div class="request-type-badge" :class="`badge-${request.type}`">
                  {{ request.type.toUpperCase() }}
                </div>
                <div class="request-date">
                  <Calendar class="icon-xs" />
                  <span>{{ formatDateRange(request.startDate, request.endDate) }}</span>
                </div>
              </div>
              <p class="request-description">{{ request.description }}</p>

              <div v-if="request.photo" class="request-photo">
                <img :src="request.photo" alt="Bukti" @click="openImageModal(request.photo)" @error="onImgError" />
              </div>
            </div>

            <div class="request-actions">
              <button
                @click="openRejectModal(request)"
                class="btn btn-reject-sm"
                :disabled="processingId === request.id"
              >
                <X class="icon-xs" />
              </button>
              <button
                @click="approveRequest(request.id)"
                class="btn btn-approve-sm"
                :disabled="processingId === request.id"
              >
                <Check class="icon-xs" />
              </button>
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
              <label class="form-label">Tuliskan alasan penolakan</label>
              <textarea
                v-model="rejectionNote"
                class="form-textarea"
                rows="4"
                placeholder="Contoh: Surat keterangan tidak jelas..."
                required
              ></textarea>
            </div>
            <div class="modal-footer">
              <button type="button" @click="closeRejectModal" class="btn btn-cancel">Batal</button>
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
  Wifi, User, ChevronDown, Settings, LogOut,
  Users, CheckCircle, AlertTriangle, Clock,
  FileText, Calendar, X, Check, ChevronRight
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

// ===== UI State =====
const showProfileMenu = ref(false)
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')
const processingId = ref(null)
const isProcessing = ref(false)
const showRejectModal = ref(false)
const rejectionNote = ref('')
const selectedRequestId = ref(null)
const showImageModal = ref(false)
const selectedImage = ref(null)

// ===== Data =====
const loadingRequests = ref(true)
const allRequests = ref([])

const teacher = ref({
  name: authStore.user?.name || 'Guru Test',
  nip: authStore.user?.username || '-',
  subject: 'Matematika'
})

// TODO: nanti dari API attendance khusus guru
const stats = ref({
  totalStudents: 150,
  presentToday: 142,
  absentToday: 8
})

// ===== Helpers =====

// 📌 fix foto bukti broken
const buildPhotoUrl = (path) => {
  if (!path) return null
  if (path.startsWith('http')) return path
  const base = import.meta.env.VITE_API_URL.replace('/api', '')
  return `${base}/storage/${path}`
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

const formatDateRange = (start, end) => {
  const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
  }
  if (start === end) return formatDate(start)
  return `${formatDate(start)} - ${formatDate(end)}`
}

const onImgError = (event) => {
  event.target.closest('.request-photo')?.classList.add('photo-hidden')
}

const displayToast = (message, type = 'success') => {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
  setTimeout(() => { showToast.value = false }, 3000)
}

// ===== Fetch =====
const fetchRequests = async () => {
  loadingRequests.value = true
  try {
    console.log('📡 Fetching permissions (guru)...')
    const response = await apiClient.get('/permissions')
    const permissions = response.data.data || response.data || []

    allRequests.value = permissions.map(item => ({
      id: item.id,
      studentName: item.student?.name || 'Siswa',
      className: item.student?.class_name || '-',
      type: item.type,
      startDate: item.start_date,
      endDate: item.end_date,
      description: item.reason,
      photo: buildPhotoUrl(item.attachment),
      status: item.status,
      createdAt: formatTimeAgo(item.created_at)
    }))
  } catch (error) {
    console.error('❌ Error fetching requests:', error)
    allRequests.value = []
  } finally {
    loadingRequests.value = false
  }
}

// Yang menunggu verifikasi GURU
const pendingRequests = computed(() =>
  allRequests.value.filter(req => req.status === 'pending_teacher')
)

// ===== Actions =====
const approveRequest = async (requestId) => {
  processingId.value = requestId
  try {
    await apiClient.post(`/permissions/${requestId}/teacher-approve`, {
      action: 'approve'
    })
    displayToast('Pengajuan disetujui!', 'success')
    await fetchRequests()
  } catch (error) {
    console.error('❌ Error approving:', error)
    const msg = error.response?.data?.message || 'Gagal menyetujui pengajuan'
    displayToast(msg, 'error')
  } finally {
    processingId.value = null
  }
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

const rejectRequest = async () => {
  isProcessing.value = true
  try {
    await apiClient.post(`/permissions/${selectedRequestId.value}/teacher-approve`, {
      action: 'reject',
      rejection_reason: rejectionNote.value
    })
    closeRejectModal()
    displayToast('Pengajuan ditolak.', 'success')
    await fetchRequests()
  } catch (error) {
    console.error('❌ Error rejecting:', error)
    const msg = error.response?.data?.message || 'Gagal menolak pengajuan'
    displayToast(msg, 'error')
  } finally {
    isProcessing.value = false
  }
}

// ===== Image modal =====
const openImageModal = (imageUrl) => {
  selectedImage.value = imageUrl
  showImageModal.value = true
}
const closeImageModal = () => {
  showImageModal.value = false
  selectedImage.value = null
}

// ===== Profile & nav =====
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

const takeAttendance = (scheduleId) => {
  navigateTo(`/guru/absensi/${scheduleId}`)
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

<style src="../../assets/css/DashboardGuru.css"></style>
