<template>
  <div class="loc-card">
    <!-- Peringatan HTTPS -->
    <div v-if="!isSecure" class="loc-alert">
      ⚠️ Geolocation diblokir: buka aplikasi via <strong>HTTPS</strong> (atau localhost).
    </div>

    <!-- Peringatan tidak didukung -->
    <div v-else-if="!isSupported" class="loc-alert">
      ⚠️ Browser ini tidak mendukung Geolocation.
    </div>

    <!-- Header: judul + toggle -->
    <div class="loc-head">
      <div class="loc-title-wrap">
        <h3 class="loc-title">Tracking Lokasi</h3>
        <span class="loc-status" :class="`st-${status}`">
          <span class="loc-dot"></span>
          {{ statusLabel }}
        </span>
      </div>

      <button
        class="loc-toggle"
        :class="{ on: isTracking }"
        :disabled="!isSupported || !isSecure"
        @click="toggleTracking"
        :aria-label="isTracking ? 'Matikan tracking' : 'Aktifkan tracking'"
      >
        <span class="loc-knob"></span>
      </button>
    </div>

    <!-- Error message -->
    <p v-if="error" class="loc-error">{{ error }}</p>

    <!-- Koordinat live (debug/preview) -->
    <div class="loc-body">
      <div class="loc-row">
        <span class="loc-key">Latitude</span>
        <span class="loc-val mono">{{ coords ? coords.latitude.toFixed(6) : '—' }}</span>
      </div>
      <div class="loc-row">
        <span class="loc-key">Longitude</span>
        <span class="loc-val mono">{{ coords ? coords.longitude.toFixed(6) : '—' }}</span>
      </div>
      <div class="loc-row">
        <span class="loc-key">Akurasi</span>
        <span class="loc-val">{{ coords ? `±${Math.round(coords.accuracy)} m` : '—' }}</span>
      </div>
      <div class="loc-row">
        <span class="loc-key">Ping terakhir</span>
        <span class="loc-val">
          <template v-if="lastPingAt">
            {{ lastPingAt.toLocaleTimeString('id-ID') }}
            <span class="ping-flag" :class="lastPingOk ? 'ok' : 'fail'">
              {{ lastPingOk ? '✓ terkirim' : '✕ gagal' }}
            </span>
          </template>
          <template v-else>—</template>
        </span>
      </div>
    </div>

    <p class="loc-hint" v-if="isTracking">
      Posisi dikirim otomatis ke server setiap {{ intervalSec }} detik selama toggle aktif.
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useGeolocation } from '../composables/useGeolocation'

const props = defineProps({
  intervalMs: { type: Number, default: 60_000 }, // ping tiap 60 detik
  autoStart: { type: Boolean, default: false }   // true = langsung nyala saat mount
})

const {
  isSupported, isSecure,
  status, statusLabel,
  coords, error,
  isTracking,
  lastPingAt, lastPingOk,
  toggleTracking,
  startTracking,
} = useGeolocation({ intervalMs: props.intervalMs })

const intervalSec = computed(() => Math.round(props.intervalMs / 1000))

if (props.autoStart) startTracking()
</script>

<style scoped>
/* Token lokal — default tema siswa (ungu).
   Dipakai di halaman ortu/guru? Ganti 2 nilai --lc-brand di bawah. */
.loc-card {
  --lc-brand: #7c3aed;
  --lc-brand-light: #ede9fe;
  --lc-surface: rgba(255, 255, 255, .8);

  border-radius: 22px;
  background: var(--lc-surface);
  backdrop-filter: blur(16px) saturate(160%);
  -webkit-backdrop-filter: blur(16px) saturate(160%);
  border: 1px solid rgba(255,255,255,.85);
  box-shadow: 0 1px 2px rgba(30,10,78,.04), 0 8px 24px -12px rgba(30,10,78,.08);
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  font-family: inherit;
  color: #191627;
}

/* peringatan HTTPS/unsupported */
.loc-alert {
  padding: 10px 14px;
  border-radius: 12px;
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
  font-size: .8rem;
  line-height: 1.5;
}

.loc-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.loc-title {
  margin: 0 0 6px;
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .1em;
  color: #6f6a87;
  display: flex;
  align-items: center;
  gap: 8px;
}
.loc-title::before {
  content: '';
  width: 18px; height: 4px;
  border-radius: 999px;
  background: linear-gradient(90deg, var(--lc-brand), #ec4899);
}

/* status pill */
.loc-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: .68rem;
  font-weight: 700;
}
.st-idle        { background: #f1f0f7; color: #6f6a87; }
.st-locating    { background: #dbeafe; color: #1e40af; }
.st-active      { background: #d1fae5; color: #047857; }
.st-denied      { background: #ffe4e6; color: #be123c; }
.st-timeout     { background: #fef3c7; color: #92400e; }
.st-unavailable { background: #fef3c7; color: #92400e; }
.st-insecure    { background: #ffe4e6; color: #be123c; }

.loc-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: currentColor;
}
.st-active .loc-dot,
.st-locating .loc-dot {
  animation: loc-pulse 1.6s ease-in-out infinite;
}
@keyframes loc-pulse {
  0%, 100% { opacity: 1;   transform: scale(1); }
  50%      { opacity: .4;  transform: scale(.75); }
}

/* toggle switch */
.loc-toggle {
  position: relative;
  width: 52px; height: 30px;
  border-radius: 999px;
  border: none;
  background: #cfccdf;
  cursor: pointer;
  transition: background .25s ease;
  flex-shrink: 0;
}
.loc-toggle.on {
  background: linear-gradient(135deg, #34d399, #10b981);
  box-shadow: 0 4px 12px -2px rgba(16,185,129,.5);
}
.loc-toggle:disabled { opacity: .4; cursor: not-allowed; }

.loc-knob {
  position: absolute;
  top: 3px; left: 3px;
  width: 24px; height: 24px;
  border-radius: 50%;
  background: #ffffff;
  box-shadow: 0 2px 6px rgba(0,0,0,.25);
  transition: transform .25s cubic-bezier(.34, 1.56, .64, 1);
}
.loc-toggle.on .loc-knob { transform: translateX(22px); }

/* error */
.loc-error {
  margin: 0;
  font-size: .78rem;
  color: #be123c;
  line-height: 1.5;
}

/* baris koordinat */
.loc-body {
  display: flex;
  flex-direction: column;
}

.loc-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px dashed #e5e3f0;
  font-size: .85rem;
}
.loc-row:last-child { border-bottom: none; }

.loc-key { color: #6f6a87; font-weight: 500; }

.loc-val {
  font-weight: 700;
  color: #191627;
  font-variant-numeric: tabular-nums;
  text-align: right;
}
.loc-val.mono {
  font-family: 'SF Mono', 'Fira Code', monospace;
  font-size: .8rem;
  color: var(--lc-brand);
  background: #f5f3ff;
  padding: 3px 10px;
  border-radius: 8px;
}

.ping-flag {
  margin-left: 6px;
  font-size: .68rem;
  font-weight: 700;
}
.ping-flag.ok   { color: #059669; }
.ping-flag.fail { color: #e11d48; }

.loc-hint {
  margin: 0;
  font-size: .7rem;
  color: #9d99b3;
  line-height: 1.5;
}
</style>