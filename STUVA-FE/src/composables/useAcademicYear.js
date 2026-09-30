import { ref } from 'vue'
import apiClient from '../utils/api'

// state dibuat SEKALI di luar → dibagi antar halaman (global cache)
const activeYear = ref(null)
const loaded = ref(false)

export function useAcademicYear() {
  const fetchActiveYear = async () => {
    // kalau udah pernah load, nggak fetch lagi (hemat request)
    if (loaded.value) return
    try {
      const res = await apiClient.get('/academic-years')
      const items = res.data?.data || []
      activeYear.value = items.find(y => y.is_active) || null
      loaded.value = true
    } catch (e) {
      console.error('❌ Gagal ambil tahun ajaran:', e)
    }
  }

  return { activeYear, loaded, fetchActiveYear }
}