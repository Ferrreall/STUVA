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
                  <button @click="navigateTo('/siswa/settings')" class="menu-item">
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
      <!-- Sticky Banner Warning 90% (Conditional Rendering) -->
      <div 
        v-if="showWarningBanner && attendanceStats.percentage < 90" 
        class="warning-banner"
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
      <section class="card">
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

        <!-- Detail Stat Grid -->
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
          <div class="stat-box bg-red-light">
            <span class="stat-label text-red">Alpha</span>
            <span class="stat-value text-red-dark">{{ attendanceStats.alpha }}</span>
          </div>
        </div>
      </section>

      <!-- Telemetri MDM Status (Simulasi Hardcode PWA) -->
      <section class="card">
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
      <section class="action-grid">
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
      <section class="card">
        <h2 class="card-title">Riwayat Pengajuan</h2>
        
        <div v-if="requestHistory.length === 0" class="empty-state">
          <FileText class="icon-lg text-gray" />
          <p class="empty-text">Belum ada pengajuan</p>
        </div>

        <div v-else class="request-list">
          <div 
            v-for="request in requestHistory" 
            :key="request.id" 
            class="request-item"
          >
            <div class="request-header">
              <div class="request-type-badge" :class="`badge-${request.type}`">
                {{ request.type.toUpperCase() }}
              </div>
              <div class="request-status-badge" :class="`status-${request.status}`">
                <span class="status-dot"></span>
                <span>{{ getStatusLabel(request.status) }}</span>
              </div>
            </div>
            
            <div class="request-body">
              <div class="request-date">
                <Calendar class="icon-sm text-gray" />
                <span>{{ formatDateRange(request.startDate, request.endDate) }}</span>
              </div>
              <p class="request-description">{{ request.description }}</p>
              
              <div v-if="request.photo" class="request-photo">
                <img :src="request.photo" alt="Bukti" />
              </div>

              <div v-if="request.status === 'rejected' && request.rejectionNote" class="rejection-note">
                <AlertTriangle class="icon-sm text-red" />
                <div>
                  <p class="rejection-label">Alasan Penolakan:</p>
                  <p class="rejection-text">{{ request.rejectionNote }}</p>
                </div>
              </div>
            </div>

            <div class="request-footer">
              <span class="request-time">Diajukan {{ request.createdAt }}</span>
            </div>
          </div>
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
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/authStore'
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

const attendanceStats = ref({
  percentage: 88,
  hadir: 40,
  sakit: 2,
  izin: 1,
  alpha: 3
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

// Data riwayat pengajuan (simulasi - nanti dari API)
const requestHistory = ref([
  {
    id: 1,
    type: 'sakit',
    startDate: '2026-09-01',
    endDate: '2026-09-02',
    description: 'Demam tinggi dan flu',
    photo: null,
    status: 'approved',
    createdAt: '1 hari yang lalu',
    rejectionNote: null
  },
  {
    id: 2,
    type: 'izin',
    startDate: '2026-08-28',
    endDate: '2026-08-28',
    description: 'Acara keluarga',
    photo: null,
    status: 'pending',
    createdAt: '5 hari yang lalu',
    rejectionNote: null
  },
  {
    id: 3,
    type: 'dispen',
    startDate: '2026-08-25',
    endDate: '2026-08-25',
    description: 'Mengikuti lomba coding',
    photo: null,
    status: 'rejected',
    createdAt: '1 minggu yang lalu',
    rejectionNote: 'Tidak ada bukti surat dari penyelenggara lomba'
  }
])

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
    // Simulasi API call
    // Dalam implementasi nyata, kirim ke backend dengan FormData
    const requestData = new FormData()
    requestData.append('type', formData.value.type)
    requestData.append('startDate', formData.value.startDate)
    requestData.append('endDate', formData.value.endDate)
    requestData.append('description', formData.value.description)
    if (formData.value.photo) {
      requestData.append('photo', formData.value.photo)
    }
    requestData.append('status', 'pending') // Status pending menunggu approval orang tua
    
    // TODO: Ganti dengan API call sebenarnya
    // await api.post('/api/siswa/pengajuan-izin', requestData)
    
    // Simulasi delay
    await new Promise(resolve => setTimeout(resolve, 1500))
    
    // Tambahkan ke riwayat (simulasi)
    const newRequest = {
      id: Date.now(),
      type: formData.value.type,
      startDate: formData.value.startDate,
      endDate: formData.value.endDate,
      description: formData.value.description,
      photo: formData.value.photo ? URL.createObjectURL(formData.value.photo) : null,
      status: 'pending',
      createdAt: 'Baru saja',
      rejectionNote: null
    }
    requestHistory.value.unshift(newRequest)
    
    // Tutup modal dan tampilkan notifikasi sukses
    closeModal()
    displayToast('Pengajuan berhasil dikirim! Menunggu persetujuan orang tua.', 'success')
    
  } catch (error) {
    displayToast('Gagal mengirim pengajuan. Silakan coba lagi.', 'error')
    console.error('Error submitting request:', error)
  } finally {
    isSubmitting.value = false
  }
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak'
  }
  return labels[status] || status
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