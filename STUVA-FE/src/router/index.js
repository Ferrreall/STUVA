import { createRouter, createWebHistory } from 'vue-router'

// Import Views
import Login from '../views/auth/login.vue'
import DashboardSiswa from '../views/siswa/DashboardSiswa.vue'
import DashboardGuru from '../views/guru/Dashboard.vue'
import DashboardOrtu from '../views/ortu/DashboardOrtu.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/siswa/dashboard',
    name: 'DashboardSiswa',
    component: DashboardSiswa,
    meta: { requiresAuth: true, role: 'siswa' }
  },
  {
    path: '/guru/dashboard',
    name: 'DashboardGuru',
    component: DashboardGuru,
    meta: { requiresAuth: true, role: 'guru' }
  },
  {
    path: '/ortu/dashboard',
    name: 'DashboardOrtu',
    component: DashboardOrtu,
    meta: { requiresAuth: true, role: 'ortu' }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation Guard untuk autentikasi
router.beforeEach((to, from, next) => {
  const isAuthenticated = localStorage.getItem('token')
  const userRole = localStorage.getItem('role')

  console.log('🛡️ Router Guard:', {
    from: from.path,
    to: to.path,
    toMeta: to.meta,
    isAuthenticated: !!isAuthenticated,
    userRole: userRole
  })

  // Jika ke halaman yang butuh auth tapi belum login
  if (to.meta.requiresAuth && !isAuthenticated) {
    console.log('❌ Not authenticated, redirecting to login')
    return next('/login')
  }

  // Jika role tidak match dengan yang diperlukan
  if (to.meta.role && userRole && userRole !== to.meta.role) {
    console.log('❌ Role mismatch! Required:', to.meta.role, 'Current:', userRole)
    console.log('🔄 Redirecting to correct dashboard...')

    if (userRole === 'siswa') {
      return next('/siswa/dashboard')
    } else if (userRole === 'guru') {
      return next('/guru/dashboard')
    } else if (userRole === 'ortu') {
      return next('/ortu/dashboard')
    } else {
      return next('/login')
    }
  }

  // Jika sudah login, tidak boleh ke halaman login
  if (to.path === '/login' && isAuthenticated) {
    console.log('✅ Already authenticated, redirecting to dashboard')

    if (userRole === 'siswa') {
      return next('/siswa/dashboard')
    } else if (userRole === 'guru') {
      return next('/guru/dashboard')
    } else if (userRole === 'ortu') {
      return next('/ortu/dashboard')
    } else {
      return next()
    }
  }

  console.log('✅ Access granted to:', to.path)
  next()
})

export default router