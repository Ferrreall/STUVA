import apiClient from '../utils/api'

/**
 * Konfigurasi endpoint lokasi.
 * Sesuaikan dengan routes/api.php backend:
 * - Ping: Route::post('/siswa/location/ping', ...) → '/siswa/location/ping'
 * - Live: belum ada di routes kamu → tambahin dulu di backend
 */
const PING_ENDPOINT = '/location/ping'
const LIVE_ENDPOINT = '/location/live'

/**
 * Kirim ping lokasi ke server.
 * Body: { latitude, longitude }
 * Response: { status: 'success', message: 'Location updated successfully' }
 */
export const pingLocation = async ({ latitude, longitude, battery_level = null }) => {
  const { data } = await apiClient.post(PING_ENDPOINT, {
    latitude,
    longitude,
    battery_level   // integer 0-100, atau null kalau Battery API nggak support
  })
  return data
}

/**
 * Ambil daftar lokasi live semua user (untuk monitoring/map admin-guru).
 */
export const getLiveLocations = async () => {
  const { data } = await apiClient.get(LIVE_ENDPOINT)
  return data
}

const CHILD_STATUS_ENDPOINT = '/ortu/child-status'

/**
 * Ambil status anak (lokasi + baterai terakhir) untuk dashboard ortu.
 * Response: { status, data: { student, last_location: {...} } }
 */
export const getChildStatus = async () => {
  const { data } = await apiClient.get(CHILD_STATUS_ENDPOINT)
  return data
}