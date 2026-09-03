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
/* Layout Container */
.dashboard-container {
  min-height: 100vh;
  background-color: #f9fafb;
  padding-bottom: 80px;
  color: #1f2937;
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.main-content {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Header Styles */
.header {
  background-color: #059669;
  padding: 12px 16px;
  color: #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1200px;
  margin: 0 auto;
}

.header-left {
  display: flex;
  align-items: center;
}

.app-name {
  font-size: 1.25rem;
  font-weight: 800;
  margin: 0;
  letter-spacing: -0.02em;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pwa-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.15);
  color: #22c55e;
}

.profile-wrapper {
  position: relative;
}

.profile-button {
  display: flex;
  align-items: center;
  gap: 6px;
  background-color: rgba(255, 255, 255, 0.15);
  border: none;
  border-radius: 20px;
  padding: 4px 8px 4px 4px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.profile-button:hover {
  background-color: rgba(255, 255, 255, 0.25);
}

.avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background-color: #ffffff;
  color: #059669;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chevron {
  transition: transform 0.2s ease;
  color: #ffffff;
}

.chevron.rotated {
  transform: rotate(180deg);
}

.profile-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 280px;
  background-color: #ffffff;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  z-index: 1000;
}

.profile-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #ffffff;
}

.avatar-large {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background-color: #ffffff;
  color: #059669;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.profile-info {
  flex: 1;
  min-width: 0;
}

.profile-name {
  font-size: 0.938rem;
  font-weight: 700;
  margin: 0 0 2px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.profile-class {
  font-size: 0.75rem;
  margin: 0;
  opacity: 0.9;
}

.profile-nisn {
  font-size: 0.7rem;
  margin: 2px 0 0 0;
  opacity: 0.8;
}

.profile-menu {
  padding: 8px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 10px 12px;
  background: none;
  border: none;
  border-radius: 8px;
  color: #374151;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s ease;
  text-align: left;
}

.menu-item:hover {
  background-color: #f3f4f6;
}

.menu-item.logout {
  color: #dc2626;
}

.menu-item.logout:hover {
  background-color: #fee2e2;
}

.menu-divider {
  height: 1px;
  background-color: #e5e7eb;
  margin: 8px 0;
}

/* Dropdown Animation */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-radius: 12px;
  color: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.bg-blue { background-color: #3b82f6; }
.bg-green { background-color: #22c55e; }
.bg-red { background-color: #ef4444; }
.bg-yellow { background-color: #f59e0b; }

.stat-icon {
  flex-shrink: 0;
  opacity: 0.9;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  line-height: 1;
}

.stat-label {
  font-size: 0.75rem;
  margin: 4px 0 0 0;
  opacity: 0.9;
}

/* Card Styling */
.card {
  border-radius: 12px;
  background-color: #ffffff;
  padding: 16px;
  border: 1px solid #f3f4f6;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.card-title {
  margin-bottom: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.btn-see-all {
  display: flex;
  align-items: center;
  gap: 4px;
  background: none;
  border: none;
  color: #3b82f6;
  font-size: 0.813rem;
  font-weight: 600;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 6px;
  transition: background-color 0.2s ease;
}

.btn-see-all:hover {
  background-color: #eff6ff;
}

/* Class List */
.class-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.class-item {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.class-item:hover {
  border-color: #059669;
  box-shadow: 0 2px 4px rgba(5, 150, 105, 0.1);
}

.class-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.class-name {
  font-size: 1rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 4px 0;
}

.class-subject {
  font-size: 0.813rem;
  color: #6b7280;
  margin: 0;
}

.class-count {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.813rem;
  color: #6b7280;
}

.class-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 8px;
  background-color: #f9fafb;
  border-radius: 8px;
}

.stat-item .stat-label {
  font-size: 0.7rem;
  color: #6b7280;
  margin: 0 0 4px 0;
}

.stat-item .stat-value {
  font-size: 1rem;
  font-weight: 700;
}

/* Request List */
.request-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.request-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 12px;
  transition: box-shadow 0.2s ease;
}

.request-item:hover {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.request-student {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 140px;
}

.student-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: #f3f4f6;
  color: #6b7280;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.student-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 2px 0;
}

.student-class {
  font-size: 0.75rem;
  color: #6b7280;
  margin: 0;
}

.request-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.request-type-badge {
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 0.7rem;
  font-weight: 700;
  width: fit-content;
}

.badge-izin {
  background-color: #dbeafe;
  color: #1e40af;
}

.badge-sakit {
  background-color: #fef3c7;
  color: #92400e;
}

.badge-dispen {
  background-color: #e0e7ff;
  color: #4338ca;
}

.request-date {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  color: #6b7280;
}

.request-description {
  font-size: 0.813rem;
  color: #4b5563;
  margin: 0;
  line-height: 1.4;
}

.request-actions {
  display: flex;
  gap: 6px;
  flex-shrink: 0;
}

.btn-reject-sm,
.btn-approve-sm {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-reject-sm {
  background-color: #fee2e2;
  color: #dc2626;
}

.btn-reject-sm:hover:not(:disabled) {
  background-color: #fecaca;
}

.btn-approve-sm {
  background-color: #d1fae5;
  color: #059669;
}

.btn-approve-sm:hover:not(:disabled) {
  background-color: #a7f3d0;
}

.btn-reject-sm:disabled,
.btn-approve-sm:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Schedule List */
.schedule-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.schedule-item {
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 12px;
}

.schedule-time {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 140px;
  font-size: 0.813rem;
  font-weight: 600;
  color: #3b82f6;
}

.schedule-details {
  flex: 1;
}

.schedule-class {
  font-size: 0.938rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 4px 0;
}

.schedule-subject {
  font-size: 0.813rem;
  color: #6b7280;
  margin: 0 0 2px 0;
}

.schedule-room {
  font-size: 0.75rem;
  color: #9ca3af;
  margin: 0;
}

.btn-primary-sm {
  padding: 8px 16px;
  background-color: #059669;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 0.813rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.btn-primary-sm:hover {
  background-color: #047857;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
  gap: 12px;
}

.empty-text {
  font-size: 0.875rem;
  color: #9ca3af;
  margin: 0;
}

/* Icons Utilities */
.icon-xs {
  width: 14px;
  height: 14px;
}

.icon-sm {
  width: 16px;
  height: 16px;
}

.icon-md {
  width: 20px;
  height: 20px;
}

.icon-lg {
  width: 24px;
  height: 24px;
}

/* Color Utilities */
.text-blue { color: #3b82f6; }
.text-green { color: #22c55e; }
.text-yellow { color: #f59e0b; }
.text-red { color: #ef4444; }
.text-gray { color: #6b7280; }

/* Toast Notification */
.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  background-color: #ffffff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  z-index: 2000;
  min-width: 300px;
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

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .class-stats {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .request-item {
    flex-direction: column;
  }
  
  .request-student {
    width: 100%;
  }
  
  .request-actions {
    width: 100%;
    justify-content: flex-end;
  }
  
  .schedule-item {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
