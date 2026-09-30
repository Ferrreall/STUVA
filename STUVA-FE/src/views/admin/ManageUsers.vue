<template>
  <div class="manage-container" :class="{ 'sidebar-open': sidebarOpen }">
    <!-- ★ Page Shell ★ -->
    <div class="page-shell">

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

            <div class="sidebar-year">
        <CalendarDays class="icon-sm" />
        <span>TA {{ activeYear?.name || '—' }}</span>
      </div>

      <div class="sidebar-foot">
        <p class="sidebar-foot-name">Panel Admin</p>
        <p class="sidebar-foot-sub">Manajemen Pengguna</p>
      </div>
    </aside>

    <!-- Header -->
    <header class="header">
      <div class="header-content">
        <div class="header-left-group">
          <button @click="toggleSidebar" class="sidebar-toggle" aria-label="Menu">
            <Menu class="icon-md" />
          </button>
        </div>

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
          <button @click="exportUsers" class="btn btn-secondary" :disabled="filteredUsers.length === 0">
            <Download class="icon-sm" />
            <span>Export</span>
          </button>
          <button @click="openImport" class="btn btn-primary">
            <Upload class="icon-sm" />
            <span>Import</span>
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
    </div>

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

        <!-- Modal Import -->
    <Teleport to="body">
      <div v-if="showImportModal" class="modal-overlay" @click="showImportModal = false">
        <div class="modal-container" @click.stop>
          <div class="modal-header">
            <h3 class="modal-title">Import {{ roleLabel }}</h3>
            <button @click="showImportModal = false" class="btn-close">
              <X class="icon-sm" />
            </button>
          </div>

          <div class="modal-body">
            <!-- Step 1: template -->
            <div class="import-step">
              <p class="import-step-title">1. Download template dulu</p>
              <p class="import-hint">
                Isi datamu mengikuti kolom &amp; contoh di template. Jangan ubah urutan/nama kolom.
              </p>
              <button @click="downloadTemplate" class="btn btn-secondary">
                <Download class="icon-sm" />
                <span>Download Template</span>
              </button>
            </div>

            <!-- Step 2: upload -->
            <div class="import-step">
              <p class="import-step-title">2. Upload file Excel</p>
              <input
                type="file"
                class="form-input"
                accept=".xlsx,.xls,.csv"
                @change="handleImportFile"
                :disabled="importing"
              />
              <p v-if="parseError" class="loc-msg error">{{ parseError }}</p>

              <div v-if="parsedRows.length" class="import-preview">
                <p class="import-preview-title">
                  <CheckCircle class="icon-sm" />
                  {{ parsedRows.length }} baris terbaca
                  <span v-if="invalidRows.length" class="import-warn">
                    · {{ invalidRows.length }} baris bermasalah (akan dilewati)
                  </span>
                </p>
                <ul class="import-issue-list">
                  <li v-for="(iss, i) in invalidRows.slice(0, 5)" :key="i" class="import-issue">
                    Baris {{ iss.row }}: {{ iss.message }}
                  </li>
                </ul>
              </div>
            </div>

            <!-- Step 3: proses -->
            <div class="import-step">
              <p class="import-step-title">3. Proses import</p>

              <div v-if="importing" class="import-progress">
                <div class="import-progress-bar">
                  <div class="import-progress-fill" :style="{ width: progressPercent + '%' }"></div>
                </div>
                <p class="import-progress-text">{{ importProgress }} / {{ importTotal }}</p>
              </div>

              <button
                @click="processImport"
                class="btn btn-submit"
                :disabled="!parsedRows.length || importing || parsedRows.length === invalidRows.length"
              >
                <Upload class="icon-sm" />
                <span>
                  {{ importing ? 'Mengimport...' : `Import ${validRows.length} Data` }}
                </span>
              </button>

              <!-- hasil -->
              <div v-if="importResults.length" class="import-results">
                <p class="import-summary">
                  ✅ {{ successCount }} berhasil · ❌ {{ failCount }} gagal
                </p>
                <ul class="import-issue-list">
                  <li v-for="(r, i) in importResults.filter(r => !r.ok)" :key="i" class="import-issue">
                    {{ r.name }}: {{ r.message }}
                  </li>
                </ul>
              </div>
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
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import * as XLSX from 'xlsx'
import { useAcademicYear } from '../../composables/useAcademicYear'
import { CalendarDays } from 'lucide-vue-next'   // ikon buat badge
import apiClient from '../../utils/api'
import {
  ChevronLeft, Plus, Search, User, Users, Mail, Phone, School,
  Edit2, Trash2, X, CheckCircle, AlertTriangle,
  Menu, Home, GraduationCap, HeartHandshake, LogOut,
  Download, Upload
} from 'lucide-vue-next'

const props = defineProps({
  role: { type: String, required: true },
  title: { type: String, required: true }
})

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

const { activeYear, fetchActiveYear } = useAcademicYear()

// ===== Dropdown: daftar siswa (form ortu) =====
const studentOptions = ref([])

const rawStudents = ref([])

const fetchStudentOptions = async () => {
  if (props.role !== 'ortu') return
  try {
    const res = await apiClient.get('/users', { params: { role: 'siswa', per_page: 1000 } })
    const root = res.data || {}
    const items = Array.isArray(root.data)
      ? root.data
      : Array.isArray(root.data?.data) ? root.data.data : []
    rawStudents.value = items   
    studentOptions.value = items.map(u => ({
      value: u.id,
      label: `${u.name} — ${u.class_name || 'tanpa kelas'}`
    }))
  } catch (error) {
    console.error('❌ Gagal ambil daftar siswa:', error)
    studentOptions.value = []
  }
}

// ===== EXPORT =====
const exportUsers = () => {
  const rows = filteredUsers.value.map((u, i) => {
    const base = {
      'No': i + 1,
      'Nama Lengkap': u.name || '',
      [usernameLabel.value]: u[usernameField.value] || u.username || '',
      'Email': u.email || '',
      'No. HP': u.phone_number || u.phone || '',
    }
    if (props.role === 'siswa')  base['Kelas'] = formatClasses(u.class_name)
    if (props.role === 'guru') {
      base['Mata Pelajaran'] = u.subject || ''
      base['Kelas yang Diajar'] = formatClasses(u.class_name)
    }
    if (props.role === 'ortu')   base['Anak'] = u.student?.name || '-'
    return base
  })

  const ws = XLSX.utils.json_to_sheet(rows)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, roleLabel.value)
  XLSX.writeFile(wb, `daftar-${props.role}-${new Date().toISOString().slice(0, 10)}.xlsx`)
}

// ===== IMPORT =====
const showImportModal = ref(false)
const parsedRows = ref([])        // baris valid siap dikirim
const invalidRows = ref([])       // baris bermasalah
const parseError = ref('')
const importing = ref(false)
const importProgress = ref(0)
const importTotal = ref(0)
const importResults = ref([])

const validRows = computed(() => parsedRows.value)
const progressPercent = computed(() =>
  importTotal.value ? Math.round((importProgress.value / importTotal.value) * 100) : 0
)
const successCount = computed(() => importResults.value.filter(r => r.ok).length)
const failCount = computed(() => importResults.value.filter(r => !r.ok).length)

const openImport = () => {
  parsedRows.value = []
  invalidRows.value = []
  parseError.value = ''
  importResults.value = []
  importProgress.value = 0
  importTotal.value = 0
  showImportModal.value = true
}

// kolom template per role
const templateColumns = computed(() => {
  const base = ['Nama Lengkap', usernameLabel.value, 'Email', 'No. HP', 'Password']
  if (props.role === 'siswa') return [...base, 'Kelas']
  if (props.role === 'guru')  return [...base, 'Mata Pelajaran', 'Kelas yang Diajar (pisah koma)']
  if (props.role === 'ortu')  return [...base, 'NISN Anak']
  return base
})

const downloadTemplate = () => {
  const headers = templateColumns.value
  const example = {}
  headers.forEach(h => { example[h] = '' })
  example['Nama Lengkap'] = 'Contoh Nama'
  example[usernameLabel.value] = '1234567890'
  example['Email'] = 'contoh@sekolah.id'
  example['No. HP'] = '081234567890'
  example['Password'] = 'min8karakter'
  if (props.role === 'siswa')  example['Kelas'] = 'XII RPL 1'
  if (props.role === 'guru') {
    example['Mata Pelajaran'] = 'Matematika'
    example['Kelas yang Diajar (pisah koma)'] = 'XII RPL 1, XI RPL 2'
  }
  if (props.role === 'ortu')   example['NISN Anak'] = '1234567890'

  const ws = XLSX.utils.json_to_sheet([example], { header: headers })
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, 'Template')
  XLSX.writeFile(wb, `template-import-${props.role}.xlsx`)
}

// validasi 1 baris → balikin { payload } atau { error }
const validateRow = (row, existingIdentifiers, seenIdentifiers) => {
  const get = (k) => String(row[k] ?? '').trim()

  const name = get('Nama Lengkap')
  const identifier = get(usernameLabel.value)
  const email = get('Email')
  let password = get('Password')

  if (!name)   return { error: 'Nama kosong' }
  if (!identifier) return { error: `${usernameLabel.value} kosong` }
  if (!email)  return { error: 'Email kosong' }

  if (existingIdentifiers.has(identifier)) return { error: `${usernameLabel.value} ${identifier} sudah terdaftar` }
  if (seenIdentifiers.has(identifier))     return { error: `${usernameLabel.value} ${identifier} duplikat di file` }

  // password kosong → pakai identifier (NISN/NIP 10 digit aman); kalau pendek → random
  if (!password) password = identifier.length >= 8 ? identifier : Math.random().toString(36).slice(2, 10)

  const payload = {
    name,
    email,
    password,
    phone_number: get('No. HP'),
    role: props.role,
  }
  payload[usernameField.value] = identifier

  if (props.role === 'siswa') {
    const kelas = get('Kelas')
    if (!kelas) return { error: 'Kelas kosong' }
    payload.class_name = kelas
    payload.username = identifier
  }
  if (props.role === 'guru') {
    const kelasStr = get('Kelas yang Diajar (pisah koma)')
    if (!kelasStr) return { error: 'Kelas yang diajar kosong' }
    const mapel = get('Mata Pelajaran')
    if (!mapel) return { error: 'Mata pelajaran kosong' }
    payload.subject = mapel
    payload.class_name = JSON.stringify(kelasStr.split(',').map(s => s.trim()).filter(Boolean))
    payload.username = identifier
  }
  if (props.role === 'ortu') {
    const nisnAnak = get('NISN Anak')
    if (!nisnAnak) return { error: 'NISN Anak kosong' }
    const anak = rawStudents.value.find(s =>
      String(s.nisn || s.username || '').trim() === nisnAnak
    )
    if (!anak) return { error: `Siswa dengan NISN ${nisnAnak} tidak ditemukan` }
    payload.student_id = anak.id
    payload.username = get('Username')
    if (!payload.username) return { error: 'Username kosong' }
  }

  return { payload }
}

const handleImportFile = (e) => {
  const file = e.target.files[0]
  if (!file) return
  parseError.value = ''
  parsedRows.value = []
  invalidRows.value = []

  const reader = new FileReader()
  reader.onload = (ev) => {
    try {
      const wb = XLSX.read(ev.target.result, { type: 'array' })
      const ws = wb.Sheets[wb.SheetNames[0]]
      const json = XLSX.utils.sheet_to_json(ws, { defval: '' })

      const existing = new Set(
        users.value.map(u => String(u[usernameField.value] || u.username || '').trim())
      )
      const seen = new Set()
      const valid = []
      const invalid = []

      json.forEach((row, idx) => {
        const res = validateRow(row, existing, seen)
        if (res.error) {
          invalid.push({ row: idx + 2, message: res.error }) // +2: header + 1-based
        } else {
          valid.push(res.payload)
          seen.add(String(row[usernameLabel.value] ?? '').trim())
        }
      })

      parsedRows.value = valid
      invalidRows.value = invalid
      if (json.length === 0) parseError.value = 'File kosong / tidak ada baris data.'

    } catch (err) {
      console.error(err)
      parseError.value = 'Gagal membaca file. Pastikan format sesuai template.'
    }
  }
  reader.readAsArrayBuffer(file)
}

const processImport = async () => {
  importing.value = true
  importResults.value = []
  importTotal.value = validRows.value.length
  importProgress.value = 0

  for (const payload of validRows.value) {
    try {
      await apiClient.post('/users', payload)
      importResults.value.push({ name: payload.name, ok: true, message: 'Berhasil' })
    } catch (e) {
      importResults.value.push({
        name: payload.name,
        ok: false,
        message: e.response?.data?.message || 'Gagal (mungkin data duplikat di server)'
      })
    }
    importProgress.value++
  }

  importing.value = false
  await fetchUsers()
  await fetchStats()
  displayToast(`Import selesai: ${successCount.value} berhasil, ${failCount.value} gagal`,
    failCount.value ? 'error' : 'success')
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

let escHandler = null

onMounted(() => {
  fetchUsers()
  fetchStats()
  fetchStudentOptions()
  fetchClassOptions()

  escHandler = (e) => {
    if (e.key === 'Escape') closeSidebar()
  }
  document.addEventListener('keydown', escHandler)
})
// ★ komponen di-reuse antar route (/admin/siswa ↔ /admin/guru ↔ /admin/ortu),
//   onMounted cuma jalan sekali → refetch tiap role berubah
watch(() => props.role, (newRole, oldRole) => {
  if (newRole !== oldRole) {
    search.value = ''          // reset pencarian biar nggak nyangkut
    fetchUsers()
    fetchStats()
    fetchStudentOptions()
    fetchClassOptions()
    fetchActiveYear()   
  }
})
onUnmounted(() => {
  document.removeEventListener('keydown', escHandler)
})
</script>

<style scoped>
@import '../../assets/css/ManageUsers.css';
</style>