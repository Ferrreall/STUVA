<template>
  <div class="manage-container" :class="{ 'sidebar-open': sidebarOpen }">

    <!-- Sidebar -->
    <div class="sidebar-overlay" @click="closeSidebar"></div>

    <aside class="sidebar" :class="{ open: sidebarOpen }" @click.self="closeSidebar">
      <div class="sidebar-head">
        <span class="sidebar-brand">STUVA</span>
        <button class="sidebar-close" @click="closeSidebar" aria-label="Tutup">
          <X class="icon-sm" />
        </button>
      </div>

      <nav class="sidebar-nav">
        <button
          class="sidebar-item"
          :class="{ active: isActive('/admin/dashboard') }"
          @click="sidebarNavigate('/admin/dashboard')"
        >
          <Home class="icon-sm" />
          <span>Dashboard</span>
        </button>

        <button
          class="sidebar-item"
          :class="{ active: isActive('/admin/siswa') }"
          @click="sidebarNavigate('/admin/siswa')"
        >
          <Users class="icon-sm" />
          <span>Kelola Siswa</span>
        </button>

        <button
          class="sidebar-item"
          :class="{ active: isActive('/admin/guru') }"
          @click="sidebarNavigate('/admin/guru')"
        >
          <GraduationCap class="icon-sm" />
          <span>Kelola Guru</span>
        </button>

        <button
          class="sidebar-item"
          :class="{ active: isActive('/admin/ortu') }"
          @click="sidebarNavigate('/admin/ortu')"
        >
          <HeartHandshake class="icon-sm" />
          <span>Kelola Orang Tua</span>
        </button>

        <button
          class="sidebar-item"
          :class="{ active: isActive('/admin/years') }"
          @click="sidebarNavigate('/admin/years')"
        >
          <CalendarDays class="icon-sm" />
          <span>Tahun Ajaran</span>
        </button>
        
        <button
          class="sidebar-item"
          :class="{ active: isActive('/admin/profile') }"
          @click="sidebarNavigate('/admin/profile')"
        >
          <User class="icon-sm" />
          <span>Profil Saya</span>
        </button>

        <div class="sidebar-divider"></div>

        <button class="sidebar-item logout" @click="handleLogout">
          <LogOut class="icon-sm" />
          <span>Keluar</span>
        </button>
      </nav>

      <div class="sidebar-foot">
        <p class="sidebar-foot-name">Panel Admin</p>
        <p class="sidebar-foot-sub">Tahun Ajaran</p>
      </div>
    </aside>

    <!-- ★ Page Shell ★ -->
    <div class="page-shell">

      <header class="header">
        <div class="header-content">
          <div class="header-left-group">
            <button @click="toggleSidebar" class="sidebar-toggle" aria-label="Menu">
              <Menu class="icon-md" />
            </button>
          </div>

          <div class="header-title-wrap">
            <h1 class="header-title">Tahun Ajaran</h1>
          </div>
<!-- 
          <div class="header-user-chip">
            <div class="avatar-mini">
              <User class="icon-xs" />
            </div>
          </div> -->
        </div>
      </header>

      <main class="main-content">
        <section class="card">
          <div class="toolbar">
            <div class="toolbar-hint">
              Tahun ajaran aktif dipakai untuk pencatatan presensi &amp; pengajuan.
            </div>
            <button @click="openCreate" class="btn btn-primary">
              <Plus class="icon-sm" />
              <span>Tambah TA</span>
            </button>
          </div>
        </section>

        <section class="card">
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p class="loading-text">Memuat data...</p>
          </div>

          <div v-else-if="years.length === 0" class="empty-state">
            <CalendarDays class="icon-lg" />
            <p class="empty-text">Belum ada tahun ajaran</p>
          </div>

          <div v-else class="year-list">
            <div v-for="y in years" :key="y.id" class="year-row" :class="{ active: y.is_active }">
              <div class="year-main">
                <p class="year-name">
                  {{ y.name }}
                  <span v-if="y.is_active" class="active-pill">Aktif</span>
                </p>
                <p class="year-dates" v-if="y.start_date || y.end_date">
                  {{ formatDate(y.start_date) }} — {{ formatDate(y.end_date) }}
                </p>
              </div>

              <div class="year-actions">
                <button
                  v-if="!y.is_active"
                  @click="activateYear(y)"
                  class="btn btn-primary btn-sm"
                  :disabled="processingId === y.id"
                >
                  Aktifkan
                </button>
                <button @click="openEdit(y)" class="btn-icon-action edit" title="Edit">
                  <Edit2 class="icon-sm" />
                </button>
                <button
                  v-if="!y.is_active"
                  @click="confirmDelete(y)"
                  class="btn-icon-action delete"
                  title="Hapus"
                >
                  <Trash2 class="icon-sm" />
                </button>
              </div>
            </div>
          </div>
        </section>
      </main>

    </div><!-- /page-shell -->

    <!-- Modal Tambah/Edit -->
    <Teleport to="body">
      <div v-if="showFormModal" class="modal-overlay" @click="showFormModal = false">
        <div class="modal-container modal-small" @click.stop>
          <div class="modal-header">
            <h3 class="modal-title">{{ isEditing ? 'Edit' : 'Tambah' }} Tahun Ajaran</h3>
            <button @click="showFormModal = false" class="btn-close">
              <X class="icon-sm" />
            </button>
          </div>

          <form @submit.prevent="submitForm" class="modal-body">
            <div class="form-group">
              <label class="form-label">Nama (cth: 2026/2027)</label>
              <input v-model="formData.name" type="text" class="form-input" required />
            </div>

            <div class="form-group">
              <label class="form-label">Tanggal Mulai</label>
              <input v-model="formData.start_date" type="date" class="form-input" />
            </div>

            <div class="form-group">
              <label class="form-label">Tanggal Selesai</label>
              <input v-model="formData.end_date" type="date" class="form-input" />
            </div>

            <div class="form-group toggle-group">
              <label class="form-label">Jadikan Aktif</label>
              <button
                type="button"
                @click="formData.is_active = !formData.is_active"
                class="toggle-switch"
                :class="{ on: formData.is_active }"
              >
                <span class="toggle-knob"></span>
              </button>
              <span class="toggle-label">{{ formData.is_active ? 'Aktif' : 'Tidak' }}</span>
            </div>

            <div class="modal-footer">
              <button type="button" @click="showFormModal = false" class="btn btn-cancel">Batal</button>
              <button type="submit" class="btn btn-submit" :disabled="saving">
                {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Toast -->
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
import { useRouter, useRoute } from 'vue-router'
import apiClient from '../../utils/api'
import { useAcademicYear } from '../../composables/useAcademicYear'
import {
  ChevronLeft, Plus, User, Edit2, Trash2, X, CheckCircle, AlertTriangle,
  Menu, Home, Users, GraduationCap, HeartHandshake, CalendarDays, LogOut
} from 'lucide-vue-next'

const props = defineProps({}) // nggak butuh props, tapi konsisten

const router = useRouter()
const route = useRoute()

// ===== Sidebar =====
const sidebarOpen = ref(false)
const toggleSidebar = () => { sidebarOpen.value = !sidebarOpen.value }
const closeSidebar = () => { sidebarOpen.value = false }
const isActive = (path) => route.path === path
const sidebarNavigate = (path) => {
  sidebarOpen.value = false
  router.push(path)
}
const handleLogout = () => {
  sidebarOpen.value = false
  localStorage.clear()
  router.push({ path: '/login', query: { logout: 'success' } })
}

// ===== TA data (badge + list) =====
const { activeYear, fetchActiveYear } = useAcademicYear()

// ===== State =====
const loading = ref(true)
const years = ref([])
const processingId = ref(null)

const showFormModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const formData = ref({})
const saving = ref(false)

const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

// ===== Helpers =====
const displayToast = (message, type = 'success') => {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
  setTimeout(() => { showToast.value = false }, 3000)
}

const goBack = () => router.back()

const formatDate = (d) => {
  if (!d) return ''
  return new Date(d).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric'
  })
}

// ===== Fetch =====
const fetchYears = async () => {
  loading.value = true
  try {
    const res = await apiClient.get('/academic-years')
    years.value = res.data?.data || []
    console.log('✅ Years loaded:', years.value)
  } catch (error) {
    console.error('❌ Error fetching years:', error)
    years.value = []
  } finally {
    loading.value = false
  }
}

// ===== Create/Edit =====
const openCreate = () => {
  isEditing.value = false
  editingId.value = null
  formData.value = { name: '', start_date: '', end_date: '', is_active: false }
  showFormModal.value = true
}

const openEdit = (y) => {
  isEditing.value = true
  editingId.value = y.id
  formData.value = {
    name: y.name || '',
    start_date: y.start_date?.slice(0, 10) || '',
    end_date: y.end_date?.slice(0, 10) || '',
    is_active: !!y.is_active
  }
  showFormModal.value = true
}

const closeForm = () => {
  showFormModal.value = false
  editingId.value = null
}

const submitForm = async () => {
  saving.value = true
  try {
    if (isEditing.value) {
      await apiClient.post(`/academic-years/${editingId.value}`, formData.value)
      displayToast('Tahun ajaran diperbarui', 'success')
    } else {
      await apiClient.post('/academic-years', formData.value)
      displayToast('Tahun ajaran ditambahkan', 'success')
    }
    closeForm()
    await fetchYears()
    fetchActiveYear(true) // refresh badge TA global
  } catch (error) {
    console.error('❌ Error saving year:', error)
    displayToast(error.response?.data?.message || 'Gagal menyimpan', 'error')
  } finally {
    saving.value = false
  }
}

const activateYear = async (y) => {
  processingId.value = y.id
  try {
    await apiClient.post(`/academic-years/${y.id}/activate`)
    displayToast(`TA ${y.name} sekarang aktif`, 'success')
    await fetchYears()
    fetchActiveYear(true)
  } catch (error) {
    displayToast(error.response?.data?.message || 'Gagal mengaktifkan', 'error')
  } finally {
    processingId.value = null
  }
}

const confirmDelete = (y) => {
  if (window.confirm(`Hapus TA ${y.name}?`)) doDelete(y)
}

const doDelete = async (y) => {
  processingId.value = y.id
  try {
    await apiClient.delete(`/academic-years/${y.id}`)
    displayToast('Tahun ajaran dihapus', 'success')
    await fetchYears()
  } catch (error) {
    displayToast(error.response?.data?.message || 'Gagal menghapus', 'error')
  } finally {
    processingId.value = null
  }
}

let escHandler = null
onMounted(() => {
  fetchYears()
  fetchActiveYear(true)

  escHandler = (e) => { if (e.key === 'Escape') closeSidebar() }
  document.addEventListener('keydown', escHandler)
})
onUnmounted(() => {
  document.removeEventListener('keydown', escHandler)
})
</script>

<style scoped>
@import '../../assets/css/ManageUsers.css';
</style>

<style scoped>
/* ===== YEAR LIST (tambahan khusus halaman TA) ===== */
.year-list { display: flex; flex-direction: column; gap: 10px; }

.year-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 14px 16px;
  border: 1px solid var(--gray-200);
  border-radius: 16px;
  background: #ffffff;
  transition: transform .25s var(--ease-out), box-shadow .25s ease;
}
.year-row:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.year-row.active {
  border-color: var(--brand-400);
  background: linear-gradient(180deg, #f5f9ff 0%, #ffffff 45%);
}

.year-main { flex: 1; min-width: 0; }
.year-name {
  margin: 0;
  font-size: .95rem;
  font-weight: 800;
  color: var(--gray-900);
  display: flex;
  align-items: center;
  gap: 10px;
}
.active-pill {
  padding: 3px 10px;
  border-radius: 999px;
  font-size: .65rem;
  background: #d1fae5;
  color: #047857;
  font-weight: 800;
}
.year-dates {
  margin: 3px 0 0;
  font-size: .75rem;
  color: var(--gray-400);
}

.btn-sm { padding: 8px 16px; font-size: .78rem; }
</style>