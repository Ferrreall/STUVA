<template>
  <div class="dashboard-container">
    <!-- Header -->
    <header class="header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="app-name">STUVA Admin</h1>
        </div>
        <div class="header-right">
          <div class="admin-badge">
            <Shield class="icon-xs" />
            <span>Admin</span>
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
                    <Shield class="icon-md" />
                  </div>
                  <div class="profile-info">
                    <p class="profile-name">{{ admin.name }}</p>
                    <p class="profile-role">Administrator</p>
                  </div>
                </div>
                
                <div class="profile-menu">
                  <button @click="navigateTo('/admin/profile')" class="menu-item">
                    <User class="icon-sm" />
                    <span>Profil Saya</span>
                  </button>
                  <button @click="navigateTo('/admin/settings')" class="menu-item">
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
      <!-- Stats Cards -->
      <section class="stats-grid">
        <div class="stat-card stat-siswa">
          <div class="stat-icon">
            <GraduationCap class="icon-lg" />
          </div>
          <div class="stat-info">
            <p class="stat-label">Total Siswa</p>
            <p class="stat-value">{{ stats.totalSiswa }}</p>
          </div>
        </div>

        <div class="stat-card stat-guru">
          <div class="stat-icon">
            <BookOpen class="icon-lg" />
          </div>
          <div class="stat-info">
            <p class="stat-label">Total Guru</p>
            <p class="stat-value">{{ stats.totalGuru }}</p>
          </div>
        </div>

        <div class="stat-card stat-ortu">
          <div class="stat-icon">
            <Users class="icon-lg" />
          </div>
          <div class="stat-info">
            <p class="stat-label">Total Orang Tua</p>
            <p class="stat-value">{{ stats.totalOrtu }}</p>
          </div>
        </div>
      </section>

      <!-- Tabs -->
      <section class="tabs-container">
        <div class="tabs">
          <button 
            @click="activeTab = 'siswa'" 
            class="tab-button"
            :class="{ active: activeTab === 'siswa' }"
          >
            <GraduationCap class="icon-sm" />
            <span>Siswa</span>
          </button>
          <button 
            @click="activeTab = 'guru'" 
            class="tab-button"
            :class="{ active: activeTab === 'guru' }"
          >
            <BookOpen class="icon-sm" />
            <span>Guru</span>
          </button>
          <button 
            @click="activeTab = 'ortu'" 
            class="tab-button"
            :class="{ active: activeTab === 'ortu' }"
          >
            <Users class="icon-sm" />
            <span>Orang Tua</span>
          </button>
        </div>
      </section>

      <!-- Content Area -->
      <section class="card">
        <div class="card-header">
          <h2 class="card-title">Data {{ getTabLabel(activeTab) }}</h2>
          <button @click="openAddModal" class="btn btn-primary">
            <Plus class="icon-sm" />
            <span>Tambah {{ getTabLabel(activeTab) }}</span>
          </button>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
          <Search class="icon-sm search-icon" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Cari berdasarkan nama atau NISN/NIP..."
            class="search-input"
          />
        </div>

        <!-- Data Table -->
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th v-if="activeTab === 'siswa'">NISN</th>
                <th v-if="activeTab === 'guru'">NIP</th>
                <th v-if="activeTab === 'ortu'">No. HP</th>
                <th>Nama Lengkap</th>
                <th v-if="activeTab === 'siswa'">Kelas</th>
                <th v-if="activeTab === 'guru'">Mata Pelajaran</th>
                <th v-if="activeTab === 'ortu'">Nama Siswa</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredData.length === 0">
                <td :colspan="activeTab === 'siswa' ? 6 : activeTab === 'guru' ? 6 : 6" class="text-center">
                  <div class="empty-state-small">
                    <FileText class="icon-md text-gray" />
                    <p>Tidak ada data</p>
                  </div>
                </td>
              </tr>
              <tr v-else v-for="(item, index) in filteredData" :key="item.id">
                <td>{{ index + 1 }}</td>
                <td v-if="activeTab === 'siswa'">{{ item.nisn }}</td>
                <td v-if="activeTab === 'guru'">{{ item.nip }}</td>
                <td v-if="activeTab === 'ortu'">{{ item.phone }}</td>
                <td class="font-semibold">{{ item.name }}</td>
                <td v-if="activeTab === 'siswa'">{{ item.class_name }}</td>
                <td v-if="activeTab === 'guru'">{{ item.subject }}</td>
                <td v-if="activeTab === 'ortu'">{{ item.student_name }}</td>
                <td>
                  <span class="badge" :class="item.is_active ? 'badge-success' : 'badge-danger'">
                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button @click="openEditModal(item)" class="btn-icon btn-edit" title="Edit">
                      <Edit2 class="icon-xs" />
                    </button>
                    <button @click="openDeleteModal(item)" class="btn-icon btn-delete" title="Hapus">
                      <Trash2 class="icon-xs" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <!-- Add/Edit Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-container modal-large" @click.stop>
          <div class="modal-header">
            <h3 class="modal-title">
              {{ isEditMode ? 'Edit' : 'Tambah' }} {{ getTabLabel(activeTab) }}
            </h3>
            <button @click="closeModal" class="btn-close">
              <X class="icon-sm" />
            </button>
          </div>

          <form @submit.prevent="submitForm" class="modal-body">
            <!-- Siswa Form -->
            <template v-if="activeTab === 'siswa'">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">NISN <span class="required">*</span></label>
                  <input 
                    v-model="formData.nisn" 
                    type="text" 
                    class="form-input"
                    placeholder="1234567890"
                    required
                    :disabled="isEditMode"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                  <input 
                    v-model="formData.name" 
                    type="text" 
                    class="form-input"
                    placeholder="Nama lengkap siswa"
                    required
                  />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Kelas <span class="required">*</span></label>
                  <input 
                    v-model="formData.class_name" 
                    type="text" 
                    class="form-input"
                    placeholder="XII RPL 1"
                    required
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Email</label>
                  <input 
                    v-model="formData.email" 
                    type="email" 
                    class="form-input"
                    placeholder="siswa@example.com"
                  />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">No. HP</label>
                  <input 
                    v-model="formData.phone" 
                    type="text" 
                    class="form-input"
                    placeholder="081234567890"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Password <span v-if="!isEditMode" class="required">*</span></label>
                  <input 
                    v-model="formData.password" 
                    type="password" 
                    class="form-input"
                    :placeholder="isEditMode ? 'Kosongkan jika tidak diubah' : 'Password'"
                    :required="!isEditMode"
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Status</label>
                <div class="radio-group">
                  <label class="radio-label">
                    <input 
                      v-model="formData.is_active" 
                      type="radio" 
                      :value="true"
                    />
                    <span>Aktif</span>
                  </label>
                  <label class="radio-label">
                    <input 
                      v-model="formData.is_active" 
                      type="radio" 
                      :value="false"
                    />
                    <span>Nonaktif</span>
                  </label>
                </div>
              </div>
            </template>

            <!-- Guru Form -->
            <template v-if="activeTab === 'guru'">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">NIP <span class="required">*</span></label>
                  <input 
                    v-model="formData.nip" 
                    type="text" 
                    class="form-input"
                    placeholder="198501012026011001"
                    required
                    :disabled="isEditMode"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                  <input 
                    v-model="formData.name" 
                    type="text" 
                    class="form-input"
                    placeholder="Nama lengkap guru"
                    required
                  />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Mata Pelajaran <span class="required">*</span></label>
                  <input 
                    v-model="formData.subject" 
                    type="text" 
                    class="form-input"
                    placeholder="Matematika"
                    required
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Email</label>
                  <input 
                    v-model="formData.email" 
                    type="email" 
                    class="form-input"
                    placeholder="guru@example.com"
                  />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">No. HP</label>
                  <input 
                    v-model="formData.phone" 
                    type="text" 
                    class="form-input"
                    placeholder="081234567890"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Password <span v-if="!isEditMode" class="required">*</span></label>
                  <input 
                    v-model="formData.password" 
                    type="password" 
                    class="form-input"
                    :placeholder="isEditMode ? 'Kosongkan jika tidak diubah' : 'Password'"
                    :required="!isEditMode"
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Status</label>
                <div class="radio-group">
                  <label class="radio-label">
                    <input 
                      v-model="formData.is_active" 
                      type="radio" 
                      :value="true"
                    />
                    <span>Aktif</span>
                  </label>
                  <label class="radio-label">
                    <input 
                      v-model="formData.is_active" 
                      type="radio" 
                      :value="false"
                    />
                    <span>Nonaktif</span>
                  </label>
                </div>
              </div>
            </template>

            <!-- Orang Tua Form -->
            <template v-if="activeTab === 'ortu'">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">No. HP <span class="required">*</span></label>
                  <input 
                    v-model="formData.phone" 
                    type="text" 
                    class="form-input"
                    placeholder="081234567890"
                    required
                    :disabled="isEditMode"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                  <input 
                    v-model="formData.name" 
                    type="text" 
                    class="form-input"
                    placeholder="Nama lengkap orang tua"
                    required
                  />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">NISN Siswa <span class="required">*</span></label>
                  <input 
                    v-model="formData.student_nisn" 
                    type="text" 
                    class="form-input"
                    placeholder="1234567890"
                    required
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Nama Siswa <span class="required">*</span></label>
                  <input 
                    v-model="formData.student_name" 
                    type="text" 
                    class="form-input"
                    placeholder="Nama siswa"
                    required
                  />
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Email</label>
                  <input 
                    v-model="formData.email" 
                    type="email" 
                    class="form-input"
                    placeholder="ortu@example.com"
                  />
                </div>
                <div class="form-group">
                  <label class="form-label">Password <span v-if="!isEditMode" class="required">*</span></label>
                  <input 
                    v-model="formData.password" 
                    type="password" 
                    class="form-input"
                    :placeholder="isEditMode ? 'Kosongkan jika tidak diubah' : 'Password'"
                    :required="!isEditMode"
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Status</label>
                <div class="radio-group">
                  <label class="radio-label">
                    <input 
                      v-model="formData.is_active" 
                      type="radio" 
                      :value="true"
                    />
                    <span>Aktif</span>
                  </label>
                  <label class="radio-label">
                    <input 
                      v-model="formData.is_active" 
                      type="radio" 
                      :value="false"
                    />
                    <span>Nonaktif</span>
                  </label>
                </div>
              </div>
            </template>

            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn btn-secondary">
                Batal
              </button>
              <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                {{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
        <div class="modal-container" @click.stop>
          <div class="modal-header">
            <h3 class="modal-title">Konfirmasi Hapus</h3>
            <button @click="closeDeleteModal" class="btn-close">
              <X class="icon-sm" />
            </button>
          </div>

          <div class="modal-body">
            <div class="delete-confirmation">
              <AlertTriangle class="icon-xl text-red" />
              <p class="delete-message">
                Apakah Anda yakin ingin menghapus <strong>{{ selectedItem?.name }}</strong>?
              </p>
              <p class="delete-warning">Tindakan ini tidak dapat dibatalkan.</p>
            </div>

            <div class="modal-footer">
              <button type="button" @click="closeDeleteModal" class="btn btn-secondary">
                Batal
              </button>
              <button @click="confirmDelete" class="btn btn-danger" :disabled="isDeleting">
                {{ isDeleting ? 'Menghapus...' : 'Hapus' }}
              </button>
            </div>
          </div>
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
import {
  Shield,
  User,
  ChevronDown,
  Settings,
  LogOut,
  GraduationCap,
  BookOpen,
  Users,
  Plus,
  Search,
  Edit2,
  Trash2,
  FileText,
  X,
  AlertTriangle,
  CheckCircle
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const showProfileMenu = ref(false)
const activeTab = ref('siswa')
const searchQuery = ref('')
const showModal = ref(false)
const showDeleteModal = ref(false)
const isEditMode = ref(false)
const isSubmitting = ref(false)
const isDeleting = ref(false)
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')
const selectedItem = ref(null)

const admin = ref({
  name: authStore.user?.name || 'Administrator'
})

const formData = ref({})

const stats = ref({
  totalSiswa: 150,
  totalGuru: 25,
  totalOrtu: 150
})

// Demo Data
const siswaData = ref([
  { id: 1, nisn: '1234567890', name: 'Ahmad Maulana', class_name: 'XII RPL 1', email: 'ahmad@student.com', phone: '081234567890', is_active: true },
  { id: 2, nisn: '1234567891', name: 'Siti Nurhaliza', class_name: 'XII RPL 1', email: 'siti@student.com', phone: '081234567891', is_active: true },
  { id: 3, nisn: '1234567892', name: 'Budi Santoso', class_name: 'XII RPL 2', email: 'budi@student.com', phone: '081234567892', is_active: false }
])

const guruData = ref([
  { id: 1, nip: '198501012026011001', name: 'Dr. Suryadi', subject: 'Matematika', email: 'suryadi@teacher.com', phone: '081234567893', is_active: true },
  { id: 2, nip: '198501012026011002', name: 'Ir. Dewi Sartika', subject: 'Bahasa Indonesia', email: 'dewi@teacher.com', phone: '081234567894', is_active: true }
])

const ortuData = ref([
  { id: 1, phone: '081234567895', name: 'Bapak Ahmad', student_nisn: '1234567890', student_name: 'Ahmad Maulana', email: 'ahmad.ortu@parent.com', is_active: true },
  { id: 2, phone: '081234567896', name: 'Ibu Siti', student_nisn: '1234567891', student_name: 'Siti Nurhaliza', email: 'siti.ortu@parent.com', is_active: true }
])

const currentData = computed(() => {
  if (activeTab.value === 'siswa') return siswaData.value
  if (activeTab.value === 'guru') return guruData.value
  return ortuData.value
})

const filteredData = computed(() => {
  if (!searchQuery.value) return currentData.value
  
  const query = searchQuery.value.toLowerCase()
  return currentData.value.filter(item => {
    const name = item.name.toLowerCase()
    const identifier = activeTab.value === 'siswa' ? item.nisn :
                      activeTab.value === 'guru' ? item.nip :
                      item.phone
    return name.includes(query) || identifier.includes(query)
  })
})

const getTabLabel = (tab) => {
  const labels = {
    siswa: 'Siswa',
    guru: 'Guru',
    ortu: 'Orang Tua'
  }
  return labels[tab]
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
    console.log('🚪 Admin logging out...')
    await authStore.logout()
  } catch (error) {
    console.error('❌ Logout error:', error)
  } finally {
    router.push({ path: '/login', query: { logout: 'success' } })
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

const resetForm = () => {
  formData.value = {
    is_active: true
  }
}

const openAddModal = () => {
  isEditMode.value = false
  resetForm()
  showModal.value = true
}

const openEditModal = (item) => {
  isEditMode.value = true
  formData.value = { ...item }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const openDeleteModal = (item) => {
  selectedItem.value = item
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  selectedItem.value = null
}

const submitForm = async () => {
  isSubmitting.value = true
  
  try {
    // TODO: API Integration
    // POST /admin/{siswa|guru|ortu} untuk create
    // PUT /admin/{siswa|guru|ortu}/{id} untuk update
    
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    if (isEditMode.value) {
      // Update existing
      const index = currentData.value.findIndex(item => item.id === formData.value.id)
      if (index !== -1) {
        currentData.value[index] = { ...formData.value }
      }
      displayToast(`${getTabLabel(activeTab.value)} berhasil diperbarui!`, 'success')
    } else {
      // Add new
      const newItem = {
        ...formData.value,
        id: Date.now()
      }
      currentData.value.push(newItem)
      displayToast(`${getTabLabel(activeTab.value)} berhasil ditambahkan!`, 'success')
      
      // Update stats
      if (activeTab.value === 'siswa') stats.value.totalSiswa++
      else if (activeTab.value === 'guru') stats.value.totalGuru++
      else stats.value.totalOrtu++
    }
    
    closeModal()
  } catch (error) {
    displayToast('Gagal menyimpan data. Silakan coba lagi.', 'error')
    console.error('Error submitting form:', error)
  } finally {
    isSubmitting.value = false
  }
}

const confirmDelete = async () => {
  isDeleting.value = true
  
  try {
    // TODO: API Integration
    // DELETE /admin/{siswa|guru|ortu}/{id}
    
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    const index = currentData.value.findIndex(item => item.id === selectedItem.value.id)
    if (index !== -1) {
      currentData.value.splice(index, 1)
      
      // Update stats
      if (activeTab.value === 'siswa') stats.value.totalSiswa--
      else if (activeTab.value === 'guru') stats.value.totalGuru--
      else stats.value.totalOrtu--
    }
    
    displayToast(`${getTabLabel(activeTab.value)} berhasil dihapus!`, 'success')
    closeDeleteModal()
  } catch (error) {
    displayToast('Gagal menghapus data. Silakan coba lagi.', 'error')
    console.error('Error deleting item:', error)
  } finally {
    isDeleting.value = false
  }
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
.dashboard-container {
  min-height: 100vh;
  background-color: #f9fafb;
  padding-bottom: 80px;
}

/* Header */
.header {
  background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
  padding: 12px 16px;
  color: #ffffff;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
  font-weight: 700;
  margin: 0;
  color: #ffffff;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.admin-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  background-color: #374151;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
}

/* Profile Dropdown */
.profile-wrapper {
  position: relative;
}

.profile-button {
  display: flex;
  align-items: center;
  gap: 8px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 8px;
  transition: background-color 0.2s;
}

.profile-button:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
}

.chevron {
  color: #ffffff;
  transition: transform 0.2s;
}

.chevron.rotated {
  transform: rotate(180deg);
}

.profile-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background-color: #ffffff;
  border-radius: 12px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  min-width: 280px;
  overflow: hidden;
  z-index: 200;
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.profile-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
  color: #ffffff;
}

.avatar-large {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background-color: rgba(255, 255, 255, 0.2);
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
  color: #ffffff;
}

.profile-role {
  font-size: 0.75rem;
  color: #d1d5db;
  margin: 0;
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
  cursor: pointer;
  transition: background-color 0.2s;
  color: #374151;
  font-size: 0.875rem;
  font-weight: 500;
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

/* Main Content */
.main-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px 16px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.stat-card {
  background-color: #ffffff;
  border-radius: 12px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border-left: 4px solid;
}

.stat-siswa {
  border-left-color: #2563eb;
}

.stat-guru {
  border-left-color: #059669;
}

.stat-ortu {
  border-left-color: #7c3aed;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-siswa .stat-icon {
  background-color: #dbeafe;
  color: #2563eb;
}

.stat-guru .stat-icon {
  background-color: #d1fae5;
  color: #059669;
}

.stat-ortu .stat-icon {
  background-color: #ede9fe;
  color: #7c3aed;
}

.stat-info {
  flex: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0 0 4px 0;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

/* Tabs */
.tabs-container {
  background-color: #ffffff;
  border-radius: 12px;
  padding: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tabs {
  display: flex;
  gap: 4px;
}

.tab-button {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 16px;
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  transition: all 0.2s;
}

.tab-button:hover {
  background-color: #f3f4f6;
}

.tab-button.active {
  background-color: #1f2937;
  color: #ffffff;
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
  flex-wrap: wrap;
  gap: 12px;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

/* Search Bar */
.search-bar {
  position: relative;
  margin-bottom: 16px;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
}

.search-input {
  width: 100%;
  padding: 10px 12px 10px 40px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Table */
.table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background-color: #f9fafb;
}

.data-table th {
  padding: 12px;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 700;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 2px solid #e5e7eb;
}

.data-table td {
  padding: 12px;
  font-size: 0.875rem;
  color: #1f2937;
  border-bottom: 1px solid #e5e7eb;
}

.data-table tbody tr:hover {
  background-color: #f9fafb;
}

.font-semibold {
  font-weight: 600;
}

.text-center {
  text-align: center;
}

.empty-state-small {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 40px 20px;
}

.empty-state-small p {
  font-size: 0.875rem;
  color: #9ca3af;
  margin: 0;
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

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 4px;
}

.btn-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-edit {
  background-color: #dbeafe;
  color: #2563eb;
}

.btn-edit:hover {
  background-color: #bfdbfe;
}

.btn-delete {
  background-color: #fee2e2;
  color: #dc2626;
}

.btn-delete:hover {
  background-color: #fecaca;
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
  background-color: #1f2937;
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background-color: #111827;
}

.btn-secondary {
  background-color: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover {
  background-color: #e5e7eb;
}

.btn-danger {
  background-color: #dc2626;
  color: #ffffff;
}

.btn-danger:hover:not(:disabled) {
  background-color: #b91c1c;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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

.modal-large {
  max-width: 700px;
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

/* Form */
.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 16px;
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

.required {
  color: #dc2626;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input:disabled {
  background-color: #f3f4f6;
  color: #9ca3af;
  cursor: not-allowed;
}

.radio-group {
  display: flex;
  gap: 16px;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 0.875rem;
  color: #374151;
}

.radio-label input[type="radio"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

/* Delete Confirmation */
.delete-confirmation {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 20px 0;
  text-align: center;
}

.delete-message {
  font-size: 0.938rem;
  color: #1f2937;
  margin: 0;
}

.delete-warning {
  font-size: 0.813rem;
  color: #6b7280;
  margin: 0;
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
  width: 32px;
  height: 32px;
}

.icon-xl {
  width: 64px;
  height: 64px;
}

.text-gray {
  color: #9ca3af;
}

.text-red {
  color: #dc2626;
}

/* Responsive */
@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-large {
    max-width: 100%;
  }
  
  .data-table {
    font-size: 0.813rem;
  }
  
  .data-table th,
  .data-table td {
    padding: 8px;
  }
}
</style>
