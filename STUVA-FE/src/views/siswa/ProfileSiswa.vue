<template>
  <div class="profile-container">
    <!-- Header -->
    <header class="header">
      <div class="header-content">
        <button @click="goBack" class="btn-back">
          <ChevronLeft class="icon-md" />
        </button>
        <h1 class="header-title">Profil Saya</h1>
        <div class="header-spacer"></div>
      </div>
    </header>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner-large"></div>
      <p class="loading-text">Memuat profil...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <AlertTriangle class="icon-xl text-red" />
      <p class="error-text">{{ error }}</p>
      <button @click="fetchProfile" class="btn btn-primary">
        Coba Lagi
      </button>
    </div>

<!-- Profile Content -->
<main v-else class="main-content">
  <!-- Avatar Section -->
  <section class="avatar-section">
    <div class="avatar-large">
      <User class="icon-xl" />
    </div>
    <h2 class="profile-name">{{ profile.name }}</h2>
    <p class="profile-role">{{ roleLabel }}</p>
  </section>

  <!-- Dua Kolom Kartu -->
  <div class="cards-grid">
    <!-- Kolom Kiri: Kartu Tinggi -->
    <div class="cards-col">
      <!-- Personal Info Card -->
      <section class="card">
        <div class="card-header">
          <h3 class="card-title">Informasi Pribadi</h3>
          <button @click="editMode = !editMode" class="btn-icon">
            <Edit2 v-if="!editMode" class="icon-sm" />
            <X v-else class="icon-sm" />
          </button>
        </div>

        <div v-if="!editMode" class="info-list">
          <div class="info-item">
            <div class="info-label">
              <User class="icon-sm text-blue" />
              <span>Nama Lengkap</span>
            </div>
            <span class="info-value">{{ profile.name || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <CreditCard class="icon-sm text-blue" />
              <span>NISN</span>
            </div>
            <span class="info-value">{{ profile.nisn || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <School class="icon-sm text-blue" />
              <span>Kelas</span>
            </div>
            <span class="info-value">{{ profile.class_name || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <Calendar class="icon-sm text-blue" />
              <span>Tanggal Lahir</span>
            </div>
            <span class="info-value">{{ formatDate(profile.birth_date) || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <MapPin class="icon-sm text-blue" />
              <span>Tempat Lahir</span>
            </div>
            <span class="info-value">{{ profile.birth_place || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <Users class="icon-sm text-blue" />
              <span>Jenis Kelamin</span>
            </div>
            <span class="info-value">{{ profile.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <Home class="icon-sm text-blue" />
              <span>Alamat</span>
            </div>
            <span class="info-value">{{ profile.address || '-' }}</span>
          </div>
        </div>

        <!-- Edit Form -->
        <form v-else @submit.prevent="updateProfile" class="edit-form">
          <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input 
              v-model="editForm.name" 
              type="text" 
              class="form-input"
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label">Tempat Lahir</label>
            <input 
              v-model="editForm.birth_place" 
              type="text" 
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Tanggal Lahir</label>
            <input 
              v-model="editForm.birth_date" 
              type="date" 
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Alamat</label>
            <textarea 
              v-model="editForm.address" 
              class="form-textarea"
              rows="3"
            ></textarea>
          </div>

          <div class="form-actions">
            <button type="button" @click="cancelEdit" class="btn btn-secondary">
              Batal
            </button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </section>
    </div>

    <!-- Kolom Kanan: Tumpukan Kartu Pendek -->
    <div class="cards-col">
      <!-- Contact Info Card -->
      <section class="card">
        <h3 class="card-title">Kontak</h3>
        
        <div class="info-list">
          <div class="info-item">
            <div class="info-label">
              <Mail class="icon-sm text-green" />
              <span>Email</span>
            </div>
            <span class="info-value">{{ profile.email || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <Phone class="icon-sm text-green" />
              <span>No. HP</span>
            </div>
            <span class="info-value">{{ profile.phone || '-' }}</span>
          </div>
        </div>
      </section>

      <!-- Parent Info Card -->
      <section v-if="profile.parent" class="card">
        <h3 class="card-title">Informasi Orang Tua</h3>
        
        <div class="info-list">
          <div class="info-item">
            <div class="info-label">
              <User class="icon-sm text-purple" />
              <span>Nama Orang Tua</span>
            </div>
            <span class="info-value">{{ profile.parent.name || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <Phone class="icon-sm text-purple" />
              <span>No. HP Orang Tua</span>
            </div>
            <span class="info-value">{{ profile.parent.phone || '-' }}</span>
          </div>
        </div>
      </section>

      <!-- Account Info Card -->
      <section class="card">
        <h3 class="card-title">Informasi Akun</h3>
        
        <div class="info-list">
          <div class="info-item">
            <div class="info-label">
              <Key class="icon-sm text-orange" />
              <span>Username</span>
            </div>
            <span class="info-value">{{ profile.username || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <Clock class="icon-sm text-orange" />
              <span>Bergabung Sejak</span>
            </div>
            <span class="info-value">{{ formatDate(profile.created_at) || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <Shield class="icon-sm text-orange" />
              <span>Status Akun</span>
            </div>
            <span class="info-value">
              <span :class="profile.is_active ? 'badge badge-success' : 'badge badge-danger'">
                {{ profile.is_active ? 'Aktif' : 'Tidak Aktif' }}
              </span>
            </span>
          </div>
        </div>
      </section>
    </div>
  </div>

  <!-- Change Password Section -->
  <section class="card">
    <h3 class="card-title">Ubah Password</h3>
    
    <button @click="showPasswordModal = true" class="btn btn-outline">
      <Lock class="icon-sm" />
      <span>Ganti Password</span>
    </button>
  </section>
</main>
    <!-- Password Modal -->
    <Teleport to="body">
      <div v-if="showPasswordModal" class="modal-overlay" @click="showPasswordModal = false">
        <div class="modal-container" @click.stop>
          <div class="modal-header">
            <h3 class="modal-title">Ubah Password</h3>
            <button @click="showPasswordModal = false" class="btn-close">
              <X class="icon-sm" />
            </button>
          </div>

          <form @submit.prevent="changePassword" class="modal-body">
            <div class="form-group">
              <label class="form-label">Password Lama</label>
              <input 
                v-model="passwordForm.old_password" 
                type="password" 
                class="form-input"
                required
              />
            </div>

            <div class="form-group">
              <label class="form-label">Password Baru</label>
              <input 
                v-model="passwordForm.new_password" 
                type="password" 
                class="form-input"
                minlength="6"
                required
              />
            </div>

            <div class="form-group">
              <label class="form-label">Konfirmasi Password Baru</label>
              <input 
                v-model="passwordForm.confirm_password" 
                type="password" 
                class="form-input"
                required
              />
            </div>

            <div class="modal-footer">
              <button type="button" @click="showPasswordModal = false" class="btn btn-secondary">
                Batal
              </button>
              <button type="submit" class="btn btn-primary" :disabled="changingPassword">
                {{ changingPassword ? 'Menyimpan...' : 'Simpan' }}
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../../utils/api'
import {
  ChevronLeft,
  User,
  Edit2,
  X,
  CreditCard,
  Calendar,
  MapPin,
  Users,
  Home,
  Mail,
  Phone,
  Key,
  Clock,
  Shield,
  Lock,
  CheckCircle,
  AlertTriangle,
  School
} from 'lucide-vue-next'


const roleLabel = computed(() => {
  const r = profile.value.role || 'siswa'
  return r.charAt(0).toUpperCase() + r.slice(1)  // "siswa" → "Siswa"
})

const router = useRouter()

const loading = ref(true)
const error = ref(null)
const profile = ref({})
const editMode = ref(false)
const editForm = ref({})
const saving = ref(false)
const showPasswordModal = ref(false)
const changingPassword = ref(false)
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

const passwordForm = ref({
  old_password: '',
  new_password: '',
  confirm_password: ''
})

const goBack = () => {
  router.back()
}

const displayToast = (message, type = 'success') => {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
  
  setTimeout(() => {
    showToast.value = false
  }, 3000)
}

const fetchProfile = async () => {
  loading.value = true
  error.value = null

  try {
    console.log('📡 Fetching profile from /me...')
    const response = await apiClient.get('/me')
    
    console.log('✅ Profile data received:', response.data)
    profile.value = response.data.data || response.data
    
    // Initialize edit form
    editForm.value = {
      name: profile.value.name,
      birth_place: profile.value.birth_place,
      birth_date: profile.value.birth_date,
      address: profile.value.address
    }
    
  } catch (err) {
    console.error('❌ Error fetching profile:', err)
    error.value = err.response?.data?.message || 'Gagal memuat profil'
  } finally {
    loading.value = false
  }
}

const updateProfile = async () => {
  saving.value = true

  try {
    console.log('📡 Updating profile...')
    const response = await apiClient.put('/me', editForm.value)
    
    console.log('✅ Profile updated:', response.data)
    
    // Update local profile data
    Object.assign(profile.value, editForm.value)
    
    editMode.value = false
    displayToast('Profil berhasil diperbarui', 'success')
    
  } catch (err) {
    console.error('❌ Error updating profile:', err)
    displayToast(err.response?.data?.message || 'Gagal memperbarui profil', 'error')
  } finally {
    saving.value = false
  }
}

const cancelEdit = () => {
  editMode.value = false
  // Reset form
  editForm.value = {
    name: profile.value.name,
    birth_place: profile.value.birth_place,
    birth_date: profile.value.birth_date,
    address: profile.value.address
  }
}

const changePassword = async () => {
  // Validate
  if (passwordForm.value.new_password !== passwordForm.value.confirm_password) {
    displayToast('Password baru tidak cocok', 'error')
    return
  }

  changingPassword.value = true

  try {
    console.log('📡 Changing password...')
    await apiClient.post('/change-password', {
      old_password: passwordForm.value.old_password,
      new_password: passwordForm.value.new_password
    })
    
    console.log('✅ Password changed')
    
    showPasswordModal.value = false
    passwordForm.value = {
      old_password: '',
      new_password: '',
      confirm_password: ''
    }
    
    displayToast('Password berhasil diubah', 'success')
    
  } catch (err) {
    console.error('❌ Error changing password:', err)
    displayToast(err.response?.data?.message || 'Gagal mengubah password', 'error')
  } finally {
    changingPassword.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return null
  
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', { 
    day: 'numeric', 
    month: 'long', 
    year: 'numeric' 
  })
}

onMounted(() => {
  fetchProfile()
})

</script>

<style scoped>
@import '../../assets/css/ProfileSiswa.css';
</style>