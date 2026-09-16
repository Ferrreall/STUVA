import { ref, computed, onUnmounted, getCurrentInstance } from 'vue'
import { pingLocation } from '../services/locationService'

/**
 * Composable HTML5 Geolocation + auto ping ke API.
 *
 * Status lifecycle:
 *  idle       → tracking mati
 *  locating   → sedang mengambil posisi
 *  active     → tracking berjalan & posisi didapat
 *  denied     → user menolak izin lokasi
 *  timeout    → GPS timeout
 *  unavailable→ posisi tidak tersedia (sinyal hilang)
 *  insecure   → diblokir karena non-HTTPS
 */
export function useGeolocation(options = {}) {
  const {
    intervalMs = 60_000,     // kirim ping ke server tiap 60 detik
    autoPing = true,         // langsung ping begitu tracking aktif
    pingWhenHidden = false,  // false = skip ping saat tab hidden (hemat baterai)
  } = options

  // ===== Environment checks =====
  const isSupported = typeof navigator !== 'undefined' && 'geolocation' in navigator
  const isSecure = typeof window !== 'undefined' && (
    window.isSecureContext ||
    ['localhost', '127.0.0.1'].includes(window.location.hostname)
  )

  // ===== Reactive state =====
  const status = ref('idle')
  const coords = ref(null)      // { latitude, longitude, accuracy, timestamp }
  const error = ref(null)
  const isTracking = ref(false)
  const lastPingAt = ref(null)
  const lastPingOk = ref(null)  // true/false/null
  const pingCount = ref(0)


    // ===== Baterai (Battery Status API — hanya Chromium: Chrome/Edge/Opera) =====
  const batteryLevel = ref(null)   // 0-100, null kalau browser nggak support
  let batteryManager = null

  const getBatteryLevel = async () => {
    if (!('getBattery' in navigator)) return null
    try {
      const battery = await navigator.getBattery()
      return Math.round(battery.level * 100)   // integer 0-100
    } catch (e) {
      console.warn('Gagal mengambil status baterai:', e)
      return null
    }
  }

  // dengarkan perubahan level biar tampilan selalu segar
  const bindBatteryEvents = (battery) => {
    batteryManager = battery
    const update = () => { batteryLevel.value = Math.round(battery.level * 100) }
    update()
    battery.addEventListener('levelchange', update)
    battery.addEventListener('chargingchange', update)
  }

  const unbindBatteryEvents = () => {
    if (!batteryManager) return
    batteryManager.onlevelchange = null
    batteryManager.onchargingchange = null
    batteryManager = null
  }

  // internal timers
  let watchId = null
  let pingTimer = null

  const statusLabel = computed(() => ({
    idle:        'Tracking Mati',
    locating:    'Mengambil lokasi...',
    active:      'Tracking Aktif',
    denied:      'Izin Ditolak',
    timeout:     'Sinyal GPS Timeout',
    unavailable: 'Lokasi Tidak Tersedia',
    insecure:    'Butuh HTTPS',
  }[status.value] || status.value))

  // ===== Error mapping (spesifikasi: denied / timeout / unsupported) =====
  const describeError = (err) => {
    switch (err.code) {
      case err.PERMISSION_DENIED:
        return 'Izin lokasi ditolak. Aktifkan izin lokasi lewat ikon 🔒 di address bar.'
      case err.POSITION_UNAVAILABLE:
        return 'Sinyal GPS tidak tersedia. Coba pindah ke tempat lebih terbuka.'
      case err.TIMEOUT:
        return 'Waktu pengambilan lokasi habis (10 detik). Coba lagi.'
      default:
        return `Gagal mengambil lokasi: ${err.message}`
    }
  }

  const geoOptions = {
    enableHighAccuracy: true, // presisi tinggi (GPS)
    timeout: 10000,           // maksimal 10 detik
    maximumAge: 0,            // jangan pakai cache posisi lama
  }

  const handleSuccess = (position) => {
    coords.value = {
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
      accuracy: position.coords.accuracy,
      timestamp: position.timestamp,
    }
    error.value = null
    if (isTracking.value) status.value = 'active'
  }

  const handleError = (err) => {
    error.value = describeError(err)

    if (err.code === err.PERMISSION_DENIED) {
      status.value = 'denied'
      stopTracking() // percuma lanjut kalau izin ditolak
    } else if (err.code === err.TIMEOUT) {
      status.value = 'timeout' // tracking tetap jalan, mungkin sinyal balik
    } else {
      status.value = 'unavailable'
    }
  }

    // ===== Ping ke API =====
  const sendPing = async () => {
    if (!coords.value) return null
    if (!pingWhenHidden && document.hidden) return null // skip saat tab tidak terlihat

    try {
      const battery = await getBatteryLevel()   // ← ambil baterai saat ping

      const res = await pingLocation({
        latitude: coords.value.latitude,
        longitude: coords.value.longitude,
        battery_level: battery,                  // ← ikut dikirim (bisa null)
      })
      lastPingAt.value = new Date()
      lastPingOk.value = true
      pingCount.value++
      return res
    } catch (err) {
      lastPingAt.value = new Date()
      lastPingOk.value = false
      console.error('❌ Gagal kirim ping lokasi:', err)
      throw err
    }
  }

  /**
   * Ambil posisi SEKALI (getCurrentPosition) lalu ping ke API.
   * Berguna untuk "absen lokasi" atau test koneksi.
   */
  const pingOnce = () => new Promise((resolve, reject) => {
    if (!isSupported) {
      error.value = 'Browser/perangkat tidak mendukung Geolocation.'
      return reject(new Error(error.value))
    }
    if (!isSecure) {
      status.value = 'insecure'
      error.value = 'Geolocation diblokir: halaman harus diakses via HTTPS (atau localhost).'
      return reject(new Error(error.value))
    }

    status.value = 'locating'
    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        handleSuccess(pos)
        try {
          resolve(await sendPing())
        } catch (_) {
          resolve(null) // posisi berhasil tapi ping gagal → jangan hard-fail
        }
      },
      (err) => {
        handleError(err)
        reject(err)
      },
      geoOptions
    )
  })

  /**
   * Aktifkan tracking berkelanjutan:
   * watchPosition (posisi real-time) + setInterval (ping berkala ke API).
   */
  const startTracking = async () => {
    if (!isSupported) {
      error.value = 'Browser/perangkat tidak mendukung Geolocation.'
      return false
    }
    if (!isSecure) {
      status.value = 'insecure'
      error.value = 'Geolocation diblokir: aplikasi harus diakses via HTTPS (atau localhost).'
      return false
    }
    if (isTracking.value) return true // sudah jalan

    isTracking.value = true
    status.value = 'locating'

        // cek baterai sekali + pasang listener
    getBatteryLevel().then(lvl => { batteryLevel.value = lvl })
    if ('getBattery' in navigator) {
      navigator.getBattery()
        .then(bindBatteryEvents)
        .catch(() => {})
    }

    // 1) pantau perubahan posisi terus-menerus
    watchId = navigator.geolocation.watchPosition(handleSuccess, handleError, geoOptions)

    // 2) ping pertama langsung (jangan tunggu interval)
    if (autoPing) {
      try { await pingOnce() } catch (_) { /* error sudah di-set handleError */ }
    }

    // 3) ping berkala sesuai interval
    if (intervalMs > 0) {
      pingTimer = setInterval(() => {
        if (coords.value) sendPing().catch(() => {})
      }, intervalMs)
    }

    return true
  }

  const stopTracking = () => {
    isTracking.value = false
    if (watchId !== null) {
      navigator.geolocation.clearWatch(watchId)
      watchId = null
    }
    if (pingTimer) {
      clearInterval(pingTimer)
      pingTimer = null

    unbindBatteryEvents()
    }
    if (['active', 'locating', 'timeout'].includes(status.value)) {
      status.value = 'idle'
    }
  }

  const toggleTracking = () => {
    if (isTracking.value) stopTracking()
    else startTracking()
  }

  // auto-cleanup kalau dipakai di dalam component setup
  if (getCurrentInstance()) {
    onUnmounted(stopTracking)
  }

    // ===== Ambil baterai SEGERA saat composable dipakai =====
  // (nggak nunggu tracking dinyalain)
  getBatteryLevel().then(lvl => { batteryLevel.value = lvl })
  if ('getBattery' in navigator) {
    navigator.getBattery()
      .then(bindBatteryEvents)
      .catch(() => {})
  }

  return {
    // env
    isSupported,
    isSecure,
    // state
    status,
    statusLabel,
    coords,
    error,
    isTracking,
    lastPingAt,
    lastPingOk,
    pingCount,
    batteryLevel,
    getBatteryLevel,
    // actions
    pingOnce,
    startTracking,
    stopTracking,
    toggleTracking,
    sendPing,
  }
}