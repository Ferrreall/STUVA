import { createRouter, createWebHistory } from 'vue-router'


// Import Views
import Login from '../views/auth/Login.vue'
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

  // Jika role tidak match dengan yang diperlukan
  if (to.meta.role && userRole && userRole !== to.meta.role) {
    console.error(' Akses ditolak: Anda tidak memiliki izin untuk mengakses halaman ini.')
    console.info(' Silakan login dengan akun yang sesuai untuk mengakses halaman ini.')

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
    console.info('Already authenticated, redirecting to dashboard')

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

  console.info(' Access granted to:', to.path)
  next()
})

export default router