<template>
  <div class="presensi-container">
    <!-- Header -->
    <header class="header">
      <div class="header-content">
        <button @click="goBack" class="btn-back">
          <ChevronLeft class="icon-md" />
        </button>
        <h1 class="header-title">Riwayat Absen</h1>
        <div class="header-spacer"></div>
      </div>
    </header>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner-large"></div>
      <p class="loading-text">Memuat riwayat...</p>
    </div>

    <main v-else class="main-content">
      <!-- Navigasi Bulan + Ringkasan -->
      <section class="card month-card">
        <div class="month-nav">
          <button @click="prevMonth" class="month-btn" aria-label="Bulan sebelumnya">
            <ChevronLeft class="icon-sm" />
          </button>
          <div class="month-label">
            <Calendar class="icon-sm" />
            <span>{{ monthLabel }}</span>
          </div>
          <button
            @click="nextMonth"
            class="month-btn"
            :disabled="isCurrentMonth"
            aria-label="Bulan berikutnya"
          >
            <ChevronRight class="icon-sm" />
          </button>
        </div>

        <div class="month-stats">
          <div class="mstat ms-hadir">
            <span class="mstat-value">{{ monthStats.hadir }}</span>
            <span class="mstat-label">Hadir</span>
          </div>
          <div class="mstat ms-izin">
            <span class="mstat-value">{{ monthStats.izin }}</span>
            <span class="mstat-label">Izin</span>
          </div>
          <div class="mstat ms-sakit">
            <span class="mstat-value">{{ monthStats.sakit }}</span>
            <span class="mstat-label">Sakit</span>
          </div>
          <div class="mstat ms-dispen">
            <span class="mstat-value">{{ monthStats.dispen }}</span>
            <span class="mstat-label">Dispen</span>
          </div>
          <div class="mstat ms-alpha">
            <span class="mstat-value">{{ monthStats.alpha }}</span>
            <span class="mstat-label">Alpha</span>
          </div>
        </div>
      </section>

      <!-- Filter Chip -->
      <div class="filter-row">
        <button
          v-for="f in filters"
          :key="f.key"
          @click="activeFilter = f.key"
          class="filter-chip"
          :class="{ active: activeFilter === f.key }"
        >
          {{ f.label }}
        </button>
      </div>

      <!-- Daftar Absen -->
      <section class="card list-card">
        <div v-if="filteredAttendances.length === 0" class="empty-state">
          <CalendarX class="icon-lg" />
          <p class="empty-text">Tidak ada catatan absen di periode ini</p>
        </div>

        <div v-else class="att-list">
          <article
            v-for="att in filteredAttendances"
            :key="att.id"
            class="att-item"
            :class="`s-${att.status}`"
          >
            <!-- Kotak tanggal -->
            <div class="att-date">
              <span class="att-dayname">{{ dayName(att.date) }}</span>
              <span class="att-daynum">{{ dayNum(att.date) }}</span>
            </div>

            <!-- Info status + jam -->
            <div class="att-info">
              <span class="att-badge" :class="`b-${att.status}`">
                <span class="badge-dot"></span>
                {{ statusLabel(att.status) }}
              </span>

              <div v-if="att.checkIn" class="att-times">
                <span class="att-time">
                  <LogIn class="icon-xs" />
                  Masuk {{ att.checkIn }}
                </span>
                <span class="att-sep">•</span>
                <span class="att-time">
                  <LogOut class="icon-xs" />
                  Pulang {{ att.checkOut }}
                </span>
              </div>
              <p v-else class="att-note">{{ noteFor(att.status) }}</p>
            </div>

            <!-- Metode absen -->
            <div
              v-if="att.method"
              class="att-method"
              :title="att.method === 'qr' ? 'Scan QR' : 'Verifikasi GPS'"
            >
              <QrCode v-if="att.method === 'qr'" class="icon-sm" />
              <MapPin v-else class="icon-sm" />
            </div>
          </article>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../../utils/api'
import {
  ChevronLeft,
  ChevronRight,
  Calendar,
  CalendarX,
  LogIn,
  LogOut,
  QrCode,
  MapPin
} from 'lucide-vue-next'

const router = useRouter()

const loading = ref(true)
const activeFilter = ref('all')
const currentDate = ref(new Date())
const attendances = ref([])

const filters = [
  { key: 'all',    label: 'Semua' },
  { key: 'hadir',  label: 'Hadir' },
  { key: 'izin',   label: 'Izin' },
  { key: 'sakit',  label: 'Sakit' },
  { key: 'dispen', label: 'Dispen' },
  { key: 'alpha',  label: 'Alpha' }
]

// ===== Normalisasi data API =====
// backend kadang kirim 'present'/'izin'/'permission' dll — seragamkan
const normalizeStatus = (s) => {
  if (!s) return 'alpha'
  const v = String(s).toLowerCase().trim()
  const map = {
    hadir: 'hadir', present: 'hadir', masuk: 'hadir', hadir_pagi: 'hadir',
    izin: 'izin', permission: 'izin',
    sakit: 'sakit', sick: 'sakit',
    dispen: 'dispen', dispensasi: 'dispen',
    alpha: 'alpha', absent: 'alpha', alpa: 'alpha'
  }
  return map[v] || v
}

// ambil 'HH:MM' dari '06:35:00' atau timestamp full '2024-01-01T06:35:00.000Z'
const cutTime = (t) => {
  if (!t) return null
  const m = String(t).match(/(\d{1,2}:\d{2})/)
  return m ? m[1].padStart(5, '0') : null
}

// ambil 'YYYY-MM-DD' dari date atau timestamp
const cutDate = (d) => {
  if (!d) return null
  return String(d).slice(0, 10)
}

const normalizeMethod = (m) => {
  if (!m) return null
  const v = String(m).toLowerCase()
  if (v.includes('qr')) return 'qr'
  if (v.includes('gps') || v.includes('location')) return 'gps'
  return v
}

// ===== Fetch dari API =====
const fetchAttendances = async () => {
  loading.value = true
  try {
    console.log('📡 Fetching attendance history...')
    const res = await apiClient.get('/siswa/attendance-history', {
      params: { per_page: 100 } // minta banyak sekaligus biar riwayat nggak kepotong paginasi
    })

    console.log('✅ Attendance data received:', res.data)

    const root = res.data || {}

    // handle 2 kemungkinan bentuk response:
    // 1. paginator Laravel : { data: { data: [...] } }
    // 2. array langsung    : { data: [...] }
    const items = Array.isArray(root.data)
      ? root.data
      : Array.isArray(root.data?.data)
        ? root.data.data
        : []

    attendances.value = items.map(item => ({
      id: item.id,
      date: cutDate(item.date || item.attendance_date || item.created_at),
      status: normalizeStatus(item.status),
      checkIn: cutTime(item.check_in || item.time_in || item.checkin_at),
      checkOut: cutTime(item.check_out || item.time_out || item.checkout_at),
      method: normalizeMethod(item.method),
      note: item.notes || null   // field notes dari API kamu
    }))

  } catch (error) {
    console.error('❌ Error fetching attendance history:', error)
    attendances.value = []
  } finally {
    loading.value = false
  }
}

// ===== Navigasi bulan =====
const monthLabel = computed(() =>
  currentDate.value.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
)

const isCurrentMonth = computed(() => {
  const now = new Date()
  return currentDate.value.getFullYear() === now.getFullYear() &&
         currentDate.value.getMonth() === now.getMonth()
})

const prevMonth = () => {
  const d = new Date(currentDate.value)
  d.setMonth(d.getMonth() - 1)
  currentDate.value = d
}

const nextMonth = () => {
  if (isCurrentMonth.value) return
  const d = new Date(currentDate.value)
  d.setMonth(d.getMonth() + 1)
  currentDate.value = d
}

// ===== Filtering =====
const monthAttendances = computed(() => {
  const y = currentDate.value.getFullYear()
  const m = currentDate.value.getMonth()
  return attendances.value.filter(a => {
    if (!a.date) return false
    const d = new Date(a.date)
    return d.getFullYear() === y && d.getMonth() === m
  })
})

const monthStats = computed(() => {
  const stats = { hadir: 0, izin: 0, sakit: 0, dispen: 0, alpha: 0 }
  monthAttendances.value.forEach(a => {
    if (stats[a.status] !== undefined) stats[a.status]++
  })
  return stats
})

const filteredAttendances = computed(() => {
  if (activeFilter.value === 'all') return monthAttendances.value
  return monthAttendances.value.filter(a => a.status === activeFilter.value)
})

// ===== Helpers =====
const statusLabel = (s) =>
  ({ hadir: 'Hadir', izin: 'Izin', sakit: 'Sakit', dispen: 'Dispen', alpha: 'Alpha' }[s] || s)

const noteFor = (status) => ({
  izin: 'Tidak masuk — izin disetujui',
  sakit: 'Tidak masuk — sakit',
  dispen: 'Tidak masuk — dispensasi',
  alpha: 'Tidak masuk — tanpa keterangan'
}[status] || '-')

const dayName = (date) =>
  new Date(date).toLocaleDateString('id-ID', { weekday: 'short' })

const dayNum = (date) => new Date(date).getDate()

const goBack = () => router.back()

onMounted(fetchAttendances)
</script>

<style scoped>
@import '../../assets/css/PresensiSiswa.css';
</style>