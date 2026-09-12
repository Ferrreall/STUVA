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
      <section class="stats-grid">
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
            <p class="stat-value">{{ stats.pendingRequests }}</p>
            <p class="stat-label">Pengajuan Pending</p>
          </div>
        </div>
      </section>

      <!-- Kelas yang Diajar -->
      <section class="card">
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

      <!-- Pengajuan Izin/Sakit Terbaru -->
      <section class="card">
        <div class="section-header">
          <h2 class="card-title">Pengajuan Izin/Sakit Terbaru</h2>
          <button @click="navigateTo('/guru/pengajuan')" class="btn-see-all">
            Lihat Semua
            <ChevronRight class="icon-xs" />
          </button>
        </div>

        <div v-if="recentRequests.length === 0" class="empty-state">
          <FileText class="icon-lg text-gray" />
          <p class="empty-text">Tidak ada pengajuan baru</p>
        </div>

        <div v-else class="request-list">
          <div 
            v-for="request in recentRequests" 
            :key="request.id" 
            class="request-item"
          >
            <div class="request-student">
              <div class="student-avatar">
                <User class="icon-sm" />
              </div>
              <div class="student-info">
                <p class="student-name">{{ request.studentName }}</p>
                <p class="student-class">{{ request.className }}</p>
              </div>
            </div>

            <div class="request-details">
              <div class="request-type-badge" :class="`badge-${request.type}`">
                {{ request.type.toUpperCase() }}
              </div>
              <div class="request-date">
                <Calendar class="icon-xs text-gray" />
                <span>{{ formatDateRange(request.startDate, request.endDate) }}</span>
              </div>
              <p class="request-description">{{ request.description }}</p>
            </div>

            <div class="request-actions">
              <button 
                @click="rejectRequest(request.id)" 
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
          </div>
        </div>
      </section>

      <!-- Jadwal Mengajar Hari Ini -->
      <section class="card">
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
    </main>

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
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
import { 
  Wifi, 
  User,
  ChevronDown,
  Settings,
  LogOut,
  Users,
  CheckCircle,
  AlertTriangle,
  Clock,
  FileText,
  Calendar,
  X,
  Check,
  ChevronRight
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const showProfileMenu = ref(false)
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')
const processingId = ref(null)

const teacher = ref({
  name: authStore.user?.name || 'Guru Test',
  nip: authStore.user?.nip || '198501012010011001',
  subject: authStore.user?.subject || 'Matematika'
})

const stats = ref({
  totalStudents: 150,
  presentToday: 142,
  absentToday: 8,
  pendingRequests: 5
})

const classes = ref([
  {
    id: 1,
    name: 'X IPA 1',
    subject: 'Matematika',
    studentCount: 32,
    present: 30,
    permission: 1,
    sick: 1,
    absent: 0
  },
  {
    id: 2,
    name: 'X IPA 2',
    subject: 'Matematika',
    studentCount: 30,
    present: 28,
    permission: 0,
    sick: 1,
    absent: 1
  },
  {
    id: 3,
    name: 'XI IPA 1',
    subject: 'Matematika',
    studentCount: 28,
    present: 27,
    permission: 1,
    sick: 0,
    absent: 0
  }
])

const recentRequests = ref([
  {
    id: 1,
    studentName: 'Ahmad Fauzi',
    className: 'X IPA 1',
    type: 'sakit',
    startDate: '2026-09-03',
    endDate: '2026-09-03',
    description: 'Demam dan flu'
  },
  {
    id: 2,
    studentName: 'Siti Aminah',
    className: 'X IPA 2',
    type: 'izin',
    startDate: '2026-09-04',
    endDate: '2026-09-04',
    description: 'Acara keluarga'
  }
])

const todaySchedule = ref([
  {
    id: 1,
    className: 'X IPA 1',
    subject: 'Matematika',
    room: '201',
    startTime: '07:30',
    endTime: '09:00'
  },
  {
    id: 2,
    className: 'X IPA 2',
    subject: 'Matematika',
    room: '202',
    startTime: '09:15',
    endTime: '10:45'
  },
  {
    id: 3,
    className: 'XI IPA 1',
    subject: 'Matematika',
    room: '301',
    startTime: '13:00',
    endTime: '14:30'
  }
])

const displayToast = (message, type = 'success') => {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
  
  setTimeout(() => {
    showToast.value = false
  }, 3000)
}

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
    displayToast('Berhasil logout', 'success')
    
    setTimeout(() => {
      router.push('/login')
    }, 800)
    
  } catch (error) {
    console.error('Logout error:', error)
    displayToast('Terjadi kesalahan saat logout', 'error')
    
    setTimeout(() => {
      router.push('/login')
    }, 1500)
  }
}

const approveRequest = async (requestId) => {
  processingId.value = requestId
  
  try {
    // TODO: Call API
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Remove from list
    const index = recentRequests.value.findIndex(r => r.id === requestId)
    if (index !== -1) {
      recentRequests.value.splice(index, 1)
    }
    
    displayToast('Pengajuan disetujui', 'success')
  } catch (error) {
    displayToast('Gagal menyetujui pengajuan', 'error')
  } finally {
    processingId.value = null
  }
}

const rejectRequest = async (requestId) => {
  processingId.value = requestId
  
  try {
    // TODO: Call API
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Remove from list
    const index = recentRequests.value.findIndex(r => r.id === requestId)
    if (index !== -1) {
      recentRequests.value.splice(index, 1)
    }
    
    displayToast('Pengajuan ditolak', 'success')
  } catch (error) {
    displayToast('Gagal menolak pengajuan', 'error')
  } finally {
    processingId.value = null
  }
}

const takeAttendance = (scheduleId) => {
  navigateTo(`/guru/absensi/${scheduleId}`)
}

const formatDateRange = (start, end) => {
  const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
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
})

onUnmounted(() => {
  if (clickOutsideHandler) {
    document.removeEventListener('click', clickOutsideHandler)
  }
})


</script>

<style scoped>
@import '../../assets/css/DashboardGuru.css';
</style>
