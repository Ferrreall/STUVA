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
                    <p class="profile-name">{{ admin.name }}</p>
                    <p class="profile-class">Administrator</p>
                    <p class="profile-nisn">{{ admin.username }}</p>
                  </div>
                </div>

                <div class="profile-menu">
                  <button @click="navigateTo('/admin/profile')" class="menu-item">
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
          <p class="hero-hi">{{ greeting }}, {{ admin.name.split(' ')[0] }} 👑</p>
          <p class="hero-sub">{{ todayLabel }} — {{ pendingCount }} pengajuan menunggu diproses.</p>
        </div>
        <div class="hero-emoji">📊</div>
      </section>

      <!-- Quick Stats — klik untuk kelola -->
      <section class="stats-grid col-12">
        <div class="stat-card bg-blue stat-clickable" @click="navigateTo('/admin/siswa')">
          <div class="stat-icon">
            <Users class="icon-lg" />
          </div>
          <div class="stat-content">
            <p class="stat-value">{{ stats.total_siswa }}</p>
            <p class="stat-label">Total Siswa</p>
          </div>
          <ChevronRight class="stat-arrow" />
        </div>

        <div class="stat-card bg-green stat-clickable" @click="navigateTo('/admin/guru')">
          <div class="stat-icon">
            <GraduationCap class="icon-lg" />
          </div>
          <div class="stat-content">
            <p class="stat-value">{{ stats.total_guru }}</p>
            <p class="stat-label">Total Guru</p>
          </div>
          <ChevronRight class="stat-arrow" />
        </div>

        <div class="stat-card bg-yellow stat-clickable" @click="navigateTo('/admin/ortu')">
          <div class="stat-icon">
            <HeartHandshake class="icon-lg" />
          </div>
          <div class="stat-content">
            <p class="stat-value">{{ stats.total_ortu }}</p>
            <p class="stat-label">Total Orang Tua</p>
          </div>
          <ChevronRight class="stat-arrow" />
        </div>
      </section>

      <!-- Semua Pengajuan (Monitoring) -->
      <section class="card col-12">
        <div class="card-title-row">
          <h2 class="card-title">Semua Pengajuan</h2>
          <span v-if="allRequests.length" class="count-chip">{{ allRequests.length }}</span>
        </div>

        <div v-if="loadingRequests" class="loading-state">
          <div class="spinner"></div>
          <p class="loading-text">Memuat pengajuan...</p>
        </div>

        <div v-else-if="allRequests.length === 0" class="empty-state">
          <FileText class="icon-lg" />
          <p class="empty-text">Belum ada pengajuan</p>
        </div>

        <div v-else class="request-list">
          <article
            v-for="request in allRequests"
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
                  {{ typeLabel(request.type).toUpperCase() }}
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

            <span class="request-status-badge" :class="`status-${statusKey(request.status)}`">
              <span class="status-dot"></span>
              {{ getStatusLabel(request.status) }}
            </span>
          </article>
        </div>
      </section>
    </main>

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
  Users, GraduationCap, HeartHandshake, ChevronRight,
  FileText, Calendar, X, CheckCircle, AlertTriangle
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

// ===== UI State =====
const showProfileMenu = ref(false)
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')
const showImageModal = ref(false)
const selectedImage = ref(null)

// ===== Data =====
const loadingRequests = ref(true)
const allRequests = ref([])

const admin = ref({
  name: authStore.user?.name || 'Admin',
  username: authStore.user?.username || 'admin'
})

// ===== Statistik user (live dari /users/stats) =====
const stats = ref({
  total_siswa: 0,
  total_guru: 0,
  total_ortu: 0
})

const normalizeRole = (r) => {
  const v = String(r || '').toLowerCase().trim()
  if (['siswa', 'student'].includes(v)) return 'siswa'
  if (['guru', 'teacher'].includes(v)) return 'guru'
  if (['ortu', 'parent', 'orang_tua', 'wali'].includes(v)) return 'ortu'
  return v
}

const fetchStats = async () => {
  try {
    console.log('📡 Fetching user stats...')
    const res = await apiClient.get('/users/stats')
    console.log('✅ User stats received:', res.data)

    const root = res.data || {}
    const d = root.data ?? root   // handle yang dibungkus "data" atau nggak

        // Bentuk 1: objek — { total_siswa, total_guru, total_ortu }
    if (d && !Array.isArray(d)) {
      stats.value = {
        total_siswa: d.total_siswa ?? d.siswa ?? d.students ?? 0,
        total_guru: d.total_guru ?? d.guru ?? d.teachers ?? 0,
        total_ortu: d.total_ortu ?? d.ortu ?? d.parents ?? d.wali ?? 0
      }
      return
    }

    // Bentuk 2: array — [{ role: 'siswa', total: 10 }, ...]
    if (Array.isArray(d)) {
      const pick = (roleKey) => {
        const row = d.find(x => normalizeRole(x.role || x.name) === roleKey)
        return row ? Number(row.total ?? row.count ?? row.jumlah ?? 0) : 0
      }
      stats.value = {
        total_siswa: pick('siswa'),
        total_guru: pick('guru'),
        total_ortu: pick('ortu')
      }
      return
    }

    throw new Error('Format stats tidak dikenali')

  } catch (error) {
    console.error('❌ Error fetching user stats:', error)
    // Fallback: hitung manual dari /users kalau stats gagal
    try {
      const res = await apiClient.get('/users', { params: { per_page: 1000 } })
      const root = res.data || {}
      const items = Array.isArray(root.data)
        ? root.data
        : Array.isArray(root.data?.data) ? root.data.data : []

      const count = { siswa: 0, guru: 0, ortu: 0 }
      items.forEach(u => {
        const r = normalizeRole(u.role)
        if (count[r] !== undefined) count[r]++
      })

      stats.value = {
        total_siswa: count.siswa,
        total_guru: count.guru,
        total_ortu: count.ortu
      }
    } catch (e2) {
      console.error('❌ Fallback stats juga gagal:', e2)
    }
  }
}

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

const typeLabel = (type) =>
  ({ izin: 'Izin', sakit: 'Sakit', dispen: 'Dispensasi' }[type] || type)

const statusKey = (status) => {
  if (!status) return 'pending'
  if (status === 'pending_parent') return 'parent'
  if (status === 'pending_teacher') return 'school'
  if (status === 'rejected_parent' || status === 'rejected_teacher') return 'rejected'
  return status // approved
}

const getStatusLabel = (status) => {
  const labels = {
    parent: 'Menunggu Ortu',
    school: 'Diproses Guru',
    approved: 'Disetujui',
    rejected: 'Ditolak',
    pending: 'Pending'
  }
  return labels[statusKey(status)] || status
}

const onImgError = (event) => {
  event.target.closest('.request-photo')?.classList.add('photo-hidden')
}

// ===== Computed =====
const pendingCount = computed(() =>
  allRequests.value.filter(req =>
    req.status === 'pending_parent' || req.status === 'pending_teacher'
  ).length
)

// ===== Fetch =====
const fetchRequests = async () => {
  loadingRequests.value = true
  try {
    console.log('📡 Fetching permissions (admin)...')
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

// ===== Sapaan dinamis =====
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
  fetchStats()
})

onUnmounted(() => {
  if (clickOutsideHandler) {
    document.removeEventListener('click', clickOutsideHandler)
  }
})
</script>

<style src="../../assets/css/DashboardAdmin.css"></style>