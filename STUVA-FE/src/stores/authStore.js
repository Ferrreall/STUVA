    import { defineStore } from 'pinia'
    import apiClient from '../utils/api'

    export const useAuthStore = defineStore('auth', {
      state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null,
        role: localStorage.getItem('role') || null,
        loading: false,
        error: null
      }),

      actions: {
        async login(username, password) {
          this.loading = true
          this.error = null

          try {
            console.log('🔐 Login API call started...')

            // Memanggil API http://10.195.4.109:8000/api/login
            const response = await apiClient.post('/login', {
              username: username,
              password: password
            })

            const data = response.data
            console.log('✅ Login API response:', data)

            // Simpan data token, user, dan role dari response API
            if (data.token) {
              this.token = data.token
              localStorage.setItem('token', data.token)
              console.log('💾 Token saved')
            }

            // Tentukan role berdasarkan response backend atau deteksi otomatis
            const userRole = data.user?.role || data.role || this.detectRoleFromUsername(username)
            console.log('👤 Detected role:', userRole)

            this.role = userRole
            this.user = data.user || { username }

            localStorage.setItem('role', userRole)
            localStorage.setItem('username', username)
            if (data.user) {
              localStorage.setItem('user', JSON.stringify(data.user))
            }

            console.log('✅ Login success! Stored data:', { role: userRole, username })

            return data
          } catch (err) {
            console.error('❌ Login API error:', err)
            // Ambil pesan error dari response backend jika ada
            this.error = err.response?.data?.message || 'Username atau password salah.'
            throw this.error
          } finally {
            this.loading = false
          }
        },

        // Helper function untuk detect role dari username
        detectRoleFromUsername(username) {
          console.log('🔍 Detecting role from username:', username)

          // Deteksi berdasarkan format username
          if (username.startsWith('08')) {
            // Format nomor HP untuk orang tua
            console.log('→ Detected: Orang Tua (phone number format)')
            return 'ortu'
          } else if (username.length === 18 && username.startsWith('19')) {
            // Format NIP untuk guru (18 digit, mulai dengan 19)
            console.log('→ Detected: Guru (NIP format)')
            return 'guru'
          } else if (username.length === 10 && /^\d+$/.test(username)) {
            // Format NISN untuk siswa (10 digit angka)
            console.log('→ Detected: Siswa (NISN format)')
            return 'siswa'
          } else if (username.toLowerCase().includes('ortu') || username.toLowerCase().includes('parent')) {
            console.log('→ Detected: Orang Tua (keyword match)')
            return 'ortu'
          } else if (username.toLowerCase().includes('guru') || username.toLowerCase().includes('teacher')) {
            console.log('→ Detected: Guru (keyword match)')
            return 'guru'
          } else {
            console.log('→ Default: Siswa')
            return 'siswa'
          }
        },

        async logout() {
          this.loading = true

          try {
            console.log('🚪 Logout started from authStore...')

            // Panggil API logout (optional - tergantung backend)
            if (this.token) {
              try {
                console.log('📡 Calling logout API with token:', this.token.substring(0, 20) + '...')
                const response = await apiClient.post('/logout')
                console.log('✅ Logout API success:', response.status)
              } catch (error) {
                // Jika API logout gagal, tetap lanjutkan logout di frontend
                console.warn('⚠️ Logout API failed (continuing with local logout):', {
                  message: error.message,
                  status: error.response?.status,
                  data: error.response?.data
                })
              }
            } else {
              console.log('⚠️ No token found, skipping API call')
            }

            // Clear state dan localStorage
            console.log('🧹 Clearing auth state...')
            this.token = null
            this.user = null
            this.role = null
            this.error = null

            console.log('🧹 Clearing localStorage...')
            localStorage.removeItem('token')
            localStorage.removeItem('role')
            localStorage.removeItem('username')
            localStorage.removeItem('user')

            console.log('✅ Logout complete')
            return true
          } catch (error) {
            console.error('❌ Logout error:', error)
            // Tetap clear localStorage meski ada error
            this.token = null
            this.user = null
            this.role = null
            localStorage.removeItem('token')
            localStorage.removeItem('role')
            localStorage.removeItem('username')
            localStorage.removeItem('user')
            throw error
          } finally {
            this.loading = false
          }
        }
      }
    })
