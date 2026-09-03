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
    // Call logout dari authStore (akan call API logout)
    await authStore.logout()
    
    // Show success toast sebelum redirect
    displayToast('Berhasil logout', 'success')
    
    // Wait for toast to show then redirect
    setTimeout(() => {
      router.push('/login')
    }, 800)
    
  } catch (error) {
    console.error('Logout error:', error)
    displayToast('Terjadi kesalahan saat logout', 'error')
    
    // Tetap redirect meski ada error
    setTimeout(() => {
      router.push('/login')
    }, 1500)
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
  background-color: #2563eb;
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
  color: #2563eb;
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
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  color: #ffffff;
}

.avatar-large {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background-color: #ffffff;
  color: #2563eb;
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

.user-name {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
}

.user-info {
  font-size: 0.875rem;
  color: #dbeafe;
  margin: 4px 0 0 0;
}

/* Banner Warning */
.warning-banner {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  border-radius: 8px;
  border: 1px solid #fecaca;
  background-color: #fef2f2;
  padding: 16px;
  color: #991b1b;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.warning-body {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.warning-title {
  font-weight: 600;
  font-size: 0.875rem;
  margin: 0;
}

.warning-desc {
  margin-top: 2px;
  font-size: 0.75rem;
  color: #b91c1c;
}

.btn-dismiss {
  background: none;
  border: none;
  color: #f87171;
  cursor: pointer;
  padding: 0;
}

.btn-dismiss:hover {
  color: #dc2626;
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

/* Progress Bar */
.progress-section {
  margin-bottom: 16px;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.progress-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.progress-value {
  font-size: 1.125rem;
  font-weight: 700;
}

.progress-bar-bg {
  height: 10px;
  width: 100%;
  background-color: #e5e7eb;
  border-radius: 9999px;
  overflow: hidden;
}

.progress-bar-fill {
  height: 100%;
  border-radius: 9999px;
  transition: all 0.3s ease;
}

/* Grid & Stat Box */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 8px;
  text-align: center;
}

.stat-box {
  border-radius: 8px;
  padding: 8px;
}

.stat-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 500;
}

.stat-value {
  font-size: 1rem;
  font-weight: 700;
}

/* Telemetry List */
.telemetry-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.telemetry-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.875rem;
}

.telemetry-label {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #4b5563;
}

.telemetry-value-bold {
  font-weight: 600;
  color: #1f2937;
}

.telemetry-value-mono {
  font-family: monospace;
  font-size: 0.75rem;
  color: #4b5563;
}

.telemetry-value-sub {
  font-size: 0.75rem;
  color: #6b7280;
}

/* Action Buttons */
.action-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 12px;
  padding: 12px;
  font-weight: 600;
  font-size: 0.875rem;
  border: none;
  cursor: pointer;
  transition: transform 0.1s ease, background-color 0.2s ease;
}

.btn:active {
  transform: scale(0.95);
}

.btn-primary {
  background-color: #2563eb;
  color: #ffffff;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.btn-primary:hover {
  background-color: #1d4ed8;
}

.btn-secondary {
  background-color: #ffffff;
  color: #374151;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.btn-secondary:hover {
  background-color: #f9fafb;
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

.shrink-0 {
  flex-shrink: 0;
}

/* Color Palette Helper Classes */
.text-green { color: #16a34a; }
.text-green-dark { color: #15803d; }
.bg-green { background-color: #22c55e; }
.bg-green-light { background-color: #f0fdf4; }

.text-yellow { color: #ca8a04; }
.text-yellow-dark { color: #a16207; }
.bg-yellow-light { background-color: #fefce8; }

.text-blue { color: #2563eb; }
.text-blue-dark { color: #1d4ed8; }
.bg-blue-light { background-color: #eff6ff; }

.text-red { color: #dc2626; }
.text-red-dark { color: #b91c1c; }
.bg-red { background-color: #ef4444; }
.bg-red-light { background-color: #fef2f2; }

.text-gray { color: #6b7280; }

/* Modal Overlay & Container */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 16px;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-container {
  background-color: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.btn-close:hover {
  background-color: #f3f4f6;
  color: #1f2937;
}

.modal-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

/* Form Styles */
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

.form-select,
.form-input,
.form-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #1f2937;
  background-color: #ffffff;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-select:focus,
.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-textarea {
  resize: vertical;
  font-family: inherit;
}

.form-file {
  padding: 8px 0;
  font-size: 0.875rem;
  color: #374151;
}

.form-hint {
  font-size: 0.75rem;
  color: #6b7280;
  margin: 0;
}

.file-name {
  font-size: 0.75rem;
  color: #2563eb;
  font-weight: 500;
  margin: 4px 0 0 0;
}

/* Button Styles untuk Modal */
.btn-cancel {
  background-color: #ffffff;
  color: #374151;
  border: 1px solid #d1d5db;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.2s ease, border-color 0.2s ease;
}

.btn-cancel:hover {
  background-color: #f9fafb;
  border-color: #9ca3af;
}

.btn-submit {
  background-color: #2563eb;
  color: #ffffff;
  border: none;
  padding: 10px 24px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.1s ease;
}

.btn-submit:hover:not(:disabled) {
  background-color: #1d4ed8;
}

.btn-submit:active:not(:disabled) {
  transform: scale(0.98);
}

.btn-submit:disabled {
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

/* Responsive Modal */
@media (max-width: 640px) {
  .modal-container {
    max-height: 95vh;
    border-radius: 16px 16px 0 0;
  }
  
  .modal-overlay {
    align-items: flex-end;
    padding: 0;
  }
}

/* Request History Styles */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
  gap: 12px;
}

.icon-lg {
  width: 48px;
  height: 48px;
}

.empty-text {
  font-size: 0.875rem;
  color: #9ca3af;
  margin: 0;
}

.request-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.request-item {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 12px;
  background-color: #fefefe;
  transition: box-shadow 0.2s ease;
}

.request-item:hover {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.request-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.request-type-badge {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
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

.request-status-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-pending {
  background-color: #fef3c7;
  color: #92400e;
}

.status-approved {
  background-color: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background-color: #fee2e2;
  color: #991b1b;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: currentColor;
}

.request-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.request-date {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.813rem;
  color: #4b5563;
  font-weight: 500;
}

.request-description {
  font-size: 0.875rem;
  color: #1f2937;
  line-height: 1.5;
  margin: 0;
}

.request-photo {
  margin-top: 4px;
}

.request-photo img {
  width: 100%;
  max-width: 200px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.rejection-note {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 10px;
  margin-top: 4px;
}

.rejection-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #991b1b;
  margin: 0 0 2px 0;
}

.rejection-text {
  font-size: 0.75rem;
  color: #b91c1c;
  margin: 0;
  line-height: 1.4;
}

.request-footer {
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid #f3f4f6;
}

.request-time {
  font-size: 0.7rem;
  color: #9ca3af;
}
</style>