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
        <p class="profile-role">{{ profile.role_display }}</p>
      </section>

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
              <User class="icon-sm text-green" />
              <span>Nama Lengkap</span>
            </div>
            <span class="info-value">{{ profile.name || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <CreditCard class="icon-sm text-green" />
              <span>NIP</span>
            </div>
            <span class="info-value">{{ profile.nip || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <BookOpen class="icon-sm text-green" />
              <span>Mata Pelajaran</span>
            </div>
            <span class="info-value">{{ profile.subject || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <Calendar class="icon-sm text-green" />
              <span>Tanggal Lahir</span>
            </div>
            <span class="info-value">{{ formatDate(profile.birth_date) || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <MapPin class="icon-sm text-green" />
              <span>Tempat Lahir</span>
            </div>
            <span class="info-value">{{ profile.birth_place || '-' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <Users class="icon-sm text-green" />
              <span>Jenis Kelamin</span>
            </div>
            <span class="info-value">{{ profile.gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
          </div>

          <div class="info-item">
            <div class="info-label">
              <Home class="icon-sm text-green" />
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
            <label class="form-label">Mata Pelajaran</label>
            <input 
              v-model="editForm.subject" 
              type="text" 
              class="form-input"
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
import { ref, onMounted } from 'vue'
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
  BookOpen
} from 'lucide-vue-next'

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
    console.log('📡 Fetching guru profile from /me...')
    const response = await apiClient.get('/me')
    
    console.log('✅ Profile data received:', response.data)
    profile.value = response.data.data || response.data
    
    // Initialize edit form
    editForm.value = {
      name: profile.value.name,
      subject: profile.value.subject,
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
    subject: profile.value.subject,
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
.profile-container {
  min-height: 100vh;
  background-color: #f9fafb;
  padding-bottom: 80px;
}

/* Header */
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
  max-width: 800px;
  margin: 0 auto;
}

.btn-back {
  background: none;
  border: none;
  color: #ffffff;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: background-color 0.2s;
}

.btn-back:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.header-title {
  font-size: 1.125rem;
  font-weight: 700;
  margin: 0;
}

.header-spacer {
  width: 32px;
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  gap: 16px;
}

.spinner-large {
  width: 48px;
  height: 48px;
  border: 4px solid #e5e7eb;
  border-top-color: #059669;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  font-size: 0.938rem;
  color: #6b7280;
  margin: 0;
}

/* Error State */
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  gap: 16px;
}

.icon-xl {
  width: 64px;
  height: 64px;
}

.error-text {
  font-size: 0.938rem;
  color: #dc2626;
  margin: 0;
  text-align: center;
}

/* Main Content */
.main-content {
  max-width: 800px;
  margin: 0 auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Avatar Section */
.avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32px 16px;
  background-color: #ffffff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.avatar-large {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.profile-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 4px 0;
}

.profile-role {
  font-size: 0.938rem;
  color: #6b7280;
  margin: 0;
}

/* Card */
.card {
  background-color: #ffffff;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.card-title {
  font-size: 1rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 16px 0;
}

.btn-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f3f4f6;
  border: none;
  border-radius: 8px;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover {
  background-color: #e5e7eb;
  color: #1f2937;
}

/* Info List */
.info-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.info-label {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 500;
  flex: 1;
}

.info-value {
  font-size: 0.875rem;
  color: #1f2937;
  font-weight: 600;
  text-align: right;
  flex: 1;
}

/* Edit Form */
.edit-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
}

.form-input,
.form-textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #1f2937;
  transition: all 0.2s;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.form-textarea {
  resize: vertical;
  font-family: inherit;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 8px;
}

/* Buttons */
.btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background-color: #059669;
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background-color: #047857;
}

.btn-secondary {
  background-color: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover {
  background-color: #e5e7eb;
}

.btn-outline {
  background-color: transparent;
  color: #059669;
  border: 1px solid #059669;
}

.btn-outline:hover {
  background-color: #ecfdf5;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Badge */
.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-success {
  background-color: #d1fae5;
  color: #065f46;
}

.badge-danger {
  background-color: #fee2e2;
  color: #991b1b;
}

/* Modal */
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
}

.modal-container {
  background-color: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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
  transition: background-color 0.2s;
}

.btn-close:hover {
  background-color: #f3f4f6;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

/* Toast */
.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  background-color: #ffffff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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

/* Icons */
.icon-sm {
  width: 16px;
  height: 16px;
}

.icon-md {
  width: 20px;
  height: 20px;
}

/* Color Utilities */
.text-green { color: #059669; }
.text-orange { color: #f59e0b; }
.text-red { color: #dc2626; }

@media (max-width: 640px) {
  .info-item {
    flex-direction: column;
    gap: 4px;
  }
  
  .info-value {
    text-align: left;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
}
</style>
