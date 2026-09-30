import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico'],
      manifest: {
        name: 'STUVA — Sistem Presensi Siswa',
        short_name: 'STUVA',
        description: 'Presensi, pengajuan izin & monitoring lokasi',
        theme_color: '#7c3aed',        // ungu siswa (ganti sesuai selera)
        background_color: '#f4f2fb',
        display: 'standalone',          // fullscreen tanpa browser UI
        start_url: '/',
        icons: [
          { src: 'icons/icon-192.png', sizes: '192x192', type: 'image/png' },
          { src: 'icons/icon-512.png', sizes: '512x512', type: 'image/png' },
          { src: 'icons/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' }
        ]
      }
    })
  ],
  // ... config lain yang udah ada (server, dll)
})