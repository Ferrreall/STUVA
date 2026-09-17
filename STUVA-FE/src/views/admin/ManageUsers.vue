<template>
  <div class="manage-container">
    <!-- Header -->
    <header class="header">
      <div class="header-content">
        <button @click="goBack" class="btn-back">
          <ChevronLeft class="icon-md" />
        </button>
            <div class="header-title-wrap">
                <h1 class="header-title">{{ title }}</h1>
                <span v-if="totalCount !== null" class="header-count">{{ totalCount }}</span>
            </div>
        <button @click="openCreate" class="btn-add-header">
          <Plus class="icon-sm" />
        </button>
      </div>
    </header>

    <main class="main-content">
      <!-- Toolbar -->
      <section class="card toolbar-card">
        <div class="toolbar">
          <div class="search-box">
            <Search class="icon-sm" />
            <input
              v-model="search"
              type="text"
              class="search-input"
              :placeholder="`Cari ${roleLabel.toLowerCase()}...`"
            />
          </div>
          <button @click="openCreate" class="btn btn-primary">
            <Plus class="icon-sm" />
            <span>Tambah {{ roleLabel }}</span>
          </button>
        </div>
      </section>

      <!-- Daftar User -->
      <section class="card">
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p class="loading-text">Memuat data...</p>
        </div>

        <div v-else-if="filteredUsers.length === 0" class="empty-state">
          <Users class="icon-lg" />
          <p class="empty-text">
            {{ search ? 'Tidak ada hasil pencarian' : `Belum ada ${roleLabel.toLowerCase()} terdaftar` }}
          </p>
        </div>

        <div v-else class="user-list">
          <div
            v-for="u in filteredUsers"
            :key="u.id"
            class="user-row"
          >
            <div class="user-identity">
              <div class="user-avatar">
                <User class="icon-sm" />
              </div>
              <div class="user-main">
                <p class="user-name">{{ u.name }}</p>
                <p class="user-username">{{ usernameLabel }}: {{ u[usernameField] || u.username || '-' }}</p>
              </div>
            </div>

            <div class="user-detail">
              <p class="detail-line">
                <Mail class="icon-xs" />
                {{ u.email || '-' }}
              </p>
              <p class="detail-line">
                <Phone class="icon-xs" />
                {{ u.phone_number || '-' }}
              </p>
              <p v-if="formatClasses(u.class_name)" class="detail-line">
                <School class="icon-xs" />
                {{ formatClasses(u.class_name) }}
              </p>
            </div>

            <div class="user-actions">
              <button @click="openEdit(u)" class="btn-icon-action edit" title="Edit">
                <Edit2 class="icon-sm" />
              </button>
              <button @click="confirmDelete(u)" class="btn-icon-action delete" title="Hapus">
                <Trash2 class="icon-sm" />
              </button>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Modal Tambah/Edit -->
    <Teleport to="body">
      <div v-if="showFormModal" class="modal-overlay" @click="closeForm">
        <div class="modal-container" @click.stop>
          <div class="modal-header">
            <h3 class="modal-title">
              {{ isEditing ? 'Edit' : 'Tambah' }} {{ roleLabel }}
            </h3>
            <button @click="closeForm" class="btn-close">
              <X class="icon-sm" />
            </button>
          </div>

            <form @submit.prevent="submitForm" class="modal-body">
            <div v-for="field in formFields" :key="field.key" class="form-group">
              <label class="form-label">{{ field.label }}</label>

              <!-- dropdown (pilih 1: kelas siswa, anak ortu) -->
              <select
                v-if="field.type === 'select'"
                v-model="formData[field.key]"
                class="form-input"
                :required="field.required"
              >
                <option value="" disabled>Pilih {{ field.label.toLowerCase() }}...</option>
                <option v-for="opt in field.options" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>

              <!-- ★ LANGKAH C: checkbox multi-pilih (kelas yang diajar guru) ★ -->
              <div
                v-else-if="field.type === 'multiselect'"
                class="checkbox-grid"
              >
                <label
                  v-for="opt in field.options"
                  :key="opt.value"
                  class="checkbox-item"
                >
                  <input
                    type="checkbox"
                    :value="opt.value"
                    v-model="formData[field.key]"
                  />
                  <span>{{ opt.label }}</span>
                </label>
              </div>

              <!-- input biasa -->
              <input
                v-else
                v-model="formData[field.key]"
                :type="field.type"
                class="form-input"
                :required="field.required"
                :minlength="field.type === 'password' ? 8 : undefined"
                :placeholder="field.placeholder"
              />
            </div>

            <div class="modal-footer">
              <button type="button" @click="closeForm" class="btn btn-cancel">
                Batal
              </button>
              <button type="submit" class="btn btn-submit" :disabled="saving">
                {{ saving ? 'Menyimpan...' : 'Simpan' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Konfirmasi Hapus -->
    <Teleport to="body">
      <div v-if="showDeleteModal" class="modal-overlay" @click="showDeleteModal = false">
        <div class="modal-container modal-small" @click.stop>
          <div class="modal-body delete-body">
            <div class="delete-icon">
              <AlertTriangle class="icon-lg" />
            </div>
            <h3 class="delete-title">Hapus {{ roleLabel }}?</h3>
            <p class="delete-desc">
              <strong>{{ deleteTarget?.name }}</strong> akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.
            </p>
            <div class="modal-footer">
              <button @click="showDeleteModal = false" class="btn btn-cancel">
                Batal
              </button>
              <button @click="doDelete" class="btn btn-submit btn-danger" :disabled="deleting">
                {{ deleting ? 'Menghapus...' : 'Ya, Hapus' }}
              </button>
            </div>
          </div>
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../../utils/api'
import {
  ChevronLeft, Plus, Search, User, Users, Mail, Phone, School,
  Edit2, Trash2, X, CheckCircle, AlertTriangle
} from 'lucide-vue-next'

const props = defineProps({
  role: { type: String, required: true },
  title: { type: String, required: true }
})

const router = useRouter()

const roleLabel = computed(() =>
  ({ siswa: 'Siswa', guru: 'Guru', ortu: 'Orang Tua' }[props.role] || props.role)
)

const usernameField = computed(() =>
  ({ siswa: 'nisn', guru: 'nip', ortu: 'username' }[props.role] || 'username')
)

const usernameLabel = computed(() =>
  ({ siswa: 'NISN', guru: 'NIP', ortu: 'Username' }[props.role] || 'Username')
)

// ===== State =====
const loading = ref(true)
const users = ref([])
const search = ref('')
const totalCount = ref(null)

const showFormModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const formData = ref({})
const saving = ref(false)

const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

// ===== Dropdown: daftar siswa (form ortu) =====
const studentOptions = ref([])

const fetchStudentOptions = async () => {
  if (props.role !== 'ortu') return
  try {
    const res = await apiClient.get('/users', { params: { role: 'siswa', per_page: 1000 } })
    const root = res.data || {}
    const items = Array.isArray(root.data)
      ? root.data
      : Array.isArray(root.data?.data) ? root.data.data : []
    studentOptions.value = items.map(u => ({
      value: u.id,
      label: `${u.name} — ${u.class_name || 'tanpa kelas'}`
    }))
  } catch (error) {
    console.error('❌ Gagal ambil daftar siswa:', error)
    studentOptions.value = []
  }
}

// ===== Dropdown: daftar kelas (form siswa & guru) =====
const classOptions = ref([])

const fetchClassOptions = async () => {
  if (props.role !== 'siswa' && props.role !== 'guru') return
  try {
    const res = await apiClient.get('/available-classes')
    const items = res.data?.data || []
    classOptions.value = items.map(c => ({
      value: c.class_name,   // murni "XII RPL 1" → dikirim ke backend
      label: c.label         // "XII RPL 1 -- Pak Candra" → tampil di UI
    }))
  } catch (error) {
    console.error('❌ Gagal ambil daftar kelas:', error)
    classOptions.value = []
  }
}

// helper: tampilkan class_name (string / JSON array) rapi di list
const formatClasses = (cn) => {
  if (!cn) return ''
  let arr = cn
  if (typeof cn === 'string') {
    try {
      const parsed = JSON.parse(cn)
      if (Array.isArray(parsed)) arr = parsed
    } catch { arr = [cn] }
  }
  return Array.isArray(arr) ? arr.join(', ') : String(arr)
}

// ===== Form fields dinamis per role =====
const formFields = computed(() => {
  const fields = [
    { key: 'name', label: 'Nama Lengkap', type: 'text', required: true }
  ]

  if (props.role !== 'ortu') {
    fields.push({ key: usernameField.value, label: usernameLabel.value, type: 'text', required: true })
  } else {
    fields.push({ key: 'username', label: 'Username', type: 'text', required: true })
  }

  fields.push({ key: 'email', label: 'Email', type: 'email', required: true })
  fields.push({ key: 'phone_number', label: 'No. HP', type: 'tel' })

  if (props.role === 'siswa') {
    fields.push({
      key: 'class_name',
      label: 'Kelas',
      type: 'select',              // ★ dropdown pilih 1 kelas
      required: true,
      options: classOptions.value
    })
  }
  if (props.role === 'guru') {
    fields.push({ key: 'subject', label: 'Mata Pelajaran', type: 'text', required: true, placeholder: 'cth: Matematika' })
    fields.push({
      key: 'class_name',
      label: 'Kelas yang Diajar',
      type: 'multiselect',         // ★ checkbox, bisa banyak kelas
      required: true,
      options: classOptions.value
    })
  }
  if (props.role === 'ortu') {
    fields.push({
      key: 'student_id',
      label: 'Anak (Pilih Siswa)',
      type: 'select',
      required: true,
      options: studentOptions.value
    })
  }
  if (!isEditing.value) {
    fields.push({ key: 'password', label: 'Password', type: 'password', required: true })
  }
  return fields
})

// ===== Helpers =====
const displayToast = (message, type = 'success') => {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true
  setTimeout(() => { showToast.value = false }, 3000)
}

const goBack = () => router.back()

const normalizeRole = (r) => {
  const v = String(r || '').toLowerCase().trim()
  if (['siswa', 'student'].includes(v)) return 'siswa'
  if (['guru', 'teacher'].includes(v)) return 'guru'
  if (['ortu', 'parent', 'orang_tua', 'wali'].includes(v)) return 'ortu'
  return v
}

// ===== Fetch stats =====
const fetchStats = async () => {
  try {
    const res = await apiClient.get('/users/stats')
    const root = res.data || {}
    const d = root.data ?? root
    let count = null

    if (d && !Array.isArray(d)) {
      const map = {
        siswa: d.total_siswa ?? d.siswa ?? d.students,
        guru:  d.total_guru ?? d.guru ?? d.teachers,
        ortu:  d.total_ortu ?? d.ortu ?? d.parents ?? d.wali
      }
      count = map[props.role]
    } else if (Array.isArray(d)) {
      const row = d.find(x => normalizeRole(x.role || x.name) === props.role)
      count = row ? Number(row.total ?? row.count ?? row.jumlah ?? 0) : 0
    }

    totalCount.value = count !== undefined && count !== null ? Number(count) : null
  } catch (error) {
    console.error('❌ Error fetching stats:', error)
    totalCount.value = null
  }
}

// ===== Fetch users =====
const fetchUsers = async () => {
  loading.value = true
  try {
    const res = await apiClient.get('/users', { params: { per_page: 1000 } })
    const root = res.data || {}
    const items = Array.isArray(root.data)
      ? root.data
      : Array.isArray(root.data?.data) ? root.data.data : []

    users.value = items.filter(u => normalizeRole(u.role) === props.role)
    totalCount.value = users.value.length
  } catch (error) {
    console.error('❌ Error fetching users:', error)
    users.value = []
  } finally {
    loading.value = false
  }
}

// ===== Filter search =====
const filteredUsers = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return users.value
  return users.value.filter(u =>
    String(u.name || '').toLowerCase().includes(q) ||
    String(u[usernameField.value] || u.username || '').includes(q) ||
    String(u.email || '').toLowerCase().includes(q)
  )
})

// ===== Create =====
const openCreate = () => {
  isEditing.value = false
  editingId.value = null
  formData.value = {
    name: '',
    [usernameField.value]: '',
    email: '',
    phone_number: '',
    class_name: props.role === 'guru' ? [] : '',   // ★ guru: array, siswa: string
    subject: '',
    student_id: '',
    password: ''
  }
  showFormModal.value = true
}

// ===== Edit =====
const openEdit = (u) => {
  isEditing.value = true
  editingId.value = u.id

  // class_name guru tersimpan sebagai JSON string → parse jadi array utk checkbox
  let classes = u.class_name || ''
  if (props.role === 'guru') {
    if (typeof classes === 'string') {
      try {
        const parsed = JSON.parse(classes)
        classes = Array.isArray(parsed) ? parsed : [classes]
      } catch { classes = classes ? [classes] : [] }
    } else if (!Array.isArray(classes)) {
      classes = classes ? [classes] : []
    }
  }

  formData.value = {
    name: u.name || '',
    [usernameField.value]: u[usernameField.value] || u.username || '',
    email: u.email || '',
    phone_number: u.phone_number || u.phone || '',
    class_name: classes,
    subject: u.subject || '',
    student_id: u.student_id || ''
  }
  showFormModal.value = true
}

const closeForm = () => {
  showFormModal.value = false
  editingId.value = null
}

// ===== Submit =====
const submitForm = async () => {
  saving.value = true
  try {
    const payload = { ...formData.value, role: props.role }

    if (props.role === 'siswa') payload.username = payload.nisn
    if (props.role === 'guru') {
      payload.username = payload.nip
      // ★ kirim sebagai JSON string — format yang dibaca getAvailableClasses
      payload.class_name = JSON.stringify(payload.class_name || [])
    }

    if (isEditing.value && !payload.password) {
      delete payload.password
    }

    if (isEditing.value) {
      await apiClient.post(`/users/${editingId.value}`, payload)
      displayToast(`${roleLabel.value} berhasil diperbarui`, 'success')
    } else {
      await apiClient.post('/users', payload)
      displayToast(`${roleLabel.value} baru berhasil ditambahkan`, 'success')
    }

    closeForm()
    await fetchUsers()
    await fetchStats()
  } catch (error) {
    console.error('❌ Error saving user:', error)
    const msg = error.response?.data?.message || 'Gagal menyimpan data'
    displayToast(msg, 'error')
  } finally {
    saving.value = false
  }
}

// ===== Delete =====
const confirmDelete = (u) => {
  deleteTarget.value = u
  showDeleteModal.value = true
}

const doDelete = async () => {
  deleting.value = true
  try {
    await apiClient.delete(`/users/${deleteTarget.value.id}`)
    displayToast(`${roleLabel.value} berhasil dihapus`, 'success')
    showDeleteModal.value = false
    deleteTarget.value = null
    await fetchUsers()
    await fetchStats()
  } catch (error) {
    console.error('❌ Error deleting user:', error)
    const msg = error.response?.data?.message || 'Gagal menghapus data'
    displayToast(msg, 'error')
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  fetchUsers()
  fetchStats()
  fetchStudentOptions()
  fetchClassOptions()
})
</script>

<style scoped>
@import '../../assets/css/ManageUsers.css';
</style>