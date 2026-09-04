import { defineStore } from 'pinia'
import apiClient from '../utils/api'
import { useToast } from 'vue-toastification'

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
      const toast = useToast()
      this.loading = true
      this.error = null

      try {
        const response = await apiClient.post('/login', { username, password })
        const data = response.data

        const token = data.access_token || data.token
        if (token) {
          this.token = token
          localStorage.setItem('token', token)
        }

        const userRole = data.user?.role || data.role
        this.role = userRole
        this.user = data.user || { username }

        localStorage.setItem('role', userRole)
        localStorage.setItem('username', username)
        if (data.user) {
          localStorage.setItem('user', JSON.stringify(data.user))
        }

        toast.success('Berhasil login!')
        return data
      } catch (err) {
        this.error = err.response?.data?.message || 'Username atau password salah.'
        toast.error(this.error)
        throw new Error(this.error)
      } finally {
        this.loading = false
      }
    },

    async logout() {
      this.loading = true
      try {
        await apiClient.post('/logout')
      } catch (err) {
        console.warn('Backend logout error:', err)
      } finally {
        this.token = null
        this.user = null
        this.role = null
        this.error = null

        localStorage.removeItem('token')
        localStorage.removeItem('role')
        localStorage.removeItem('username')
        localStorage.removeItem('user')

        this.loading = false
      }
    }
  }
})