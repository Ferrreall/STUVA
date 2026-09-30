import { ref, computed, onUnmounted, getCurrentInstance } from 'vue'
import { pingLocation } from '../services/locationService'

/**
 * Composable HTML5 Geolocation + auto ping ke API.
 */
export function useGeolocation(options = {}) {
  const {
    intervalMs = 60_000,
    autoPing = true,
    pingWhenHidden = false,
  } = options

  // ===== Environment checks =====
  const isSupported = typeof navigator !== 'undefined' && 'geolocation' in navigator
  const isSecure = typeof window !== 'undefined' && (
    window.isSecureContext ||
    ['localhost', '127.0.0.1'].includes(window.location.hostname)
  )

  // ===== Reactive state =====
  const status = ref('idle')
  const coords = ref(null)
  const error = ref(null)
  const isTracking = ref(false)
  const lastPingAt = ref(null)
  const lastPingOk = ref(null)
  const pingCount = ref(0)

  // ===== Baterai =====
  const batteryLevel = ref(null)
  let batteryManager = null

  const getBatteryLevel = async () => {
    if (!('getBattery' in navigator)) return null
    try {
      const battery = await navigator.getBattery()
      return Math.round(battery.level * 100)
    } catch (e) {
      console.warn('Gagal mengambil status baterai:', e)
      return null
    }
  }

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

  // UBAH: Set enableHighAccuracy ke false & berikan tolerance maximumAge agar responsif
  const geoOptions = {
    enableHighAccuracy: false, 
    timeout: 10000,
    maximumAge: 30000, // Gunakan cache lokasi 30 dtk terakhir agar cepat
  }

  const handleSuccess = (position) => {
    coords.value = {
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
      accuracy: position.coords.accuracy,
      timestamp: position.timestamp,
    }
    error.value = null
    // PERBAIKAN: Selalu ubah status ke 'active' saat lokasi berhasil didapat!
    status.value = 'active'
  }

  const handleError = (err) => {
    error.value = describeError(err)

    if (err.code === err.PERMISSION_DENIED) {
      status.value = 'denied'
      stopTracking()
    } else if (err.code === err.TIMEOUT) {
      status.value = 'timeout'
    } else {
      status.value = 'unavailable'
    }
  }

  // ===== Ping ke API =====
  const sendPing = async () => {
    if (!coords.value) return null
    if (!pingWhenHidden && document.hidden) return null

    try {
      const battery = await getBatteryLevel()

      const res = await pingLocation({
        latitude: coords.value.latitude,
        longitude: coords.value.longitude,
        battery_level: battery,
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
        handleSuccess(pos) // Sekarang status otomatis menjadi 'active' di sini
        try {
          resolve(await sendPing())
        } catch (_) {
          resolve(null)
        }
      },
      (err) => {
        handleError(err)
        reject(err)
      },
      geoOptions
    )
  })

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
    if (isTracking.value) return true

    isTracking.value = true
    status.value = 'locating'

    getBatteryLevel().then(lvl => { batteryLevel.value = lvl })
    if ('getBattery' in navigator) {
      navigator.getBattery()
        .then(bindBatteryEvents)
        .catch(() => {})
    }

    watchId = navigator.geolocation.watchPosition(handleSuccess, handleError, geoOptions)

    if (autoPing) {
      try { await pingOnce() } catch (_) {}
    }

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

  if (getCurrentInstance()) {
    onUnmounted(stopTracking)
  }

  getBatteryLevel().then(lvl => { batteryLevel.value = lvl })
  if ('getBattery' in navigator) {
    navigator.getBattery()
      .then(bindBatteryEvents)
      .catch(() => {})
  }

  return {
    isSupported,
    isSecure,
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
    pingOnce,
    startTracking,
    stopTracking,
    toggleTracking,
    sendPing,
  }
}