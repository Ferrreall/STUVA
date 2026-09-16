import { createRouter, createWebHistory } from 'vue-router'


// Import Views
import Login from '../views/auth/Login.vue'
import DashboardSiswa from '../views/siswa/DashboardSiswa.vue'
import ProfileSiswa from '../views/siswa/ProfileSiswa.vue'
import DashboardGuru from '../views/guru/DashboardGuru.vue'
import ProfileGuru from '../views/guru/ProfileGuru.vue'
import DashboardOrtu from '../views/ortu/DashboardOrtu.vue'
import ProfileOrtu from '../views/ortu/ProfileOrtu.vue'
import DashboardAdmin from '../views/admin/DashboardAdmin.vue'
import ManageUsers from '../views/admin/ManageUsers.vue'



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
    path: '/siswa/profile',
    name: 'ProfileSiswa',
    component: ProfileSiswa,
    meta: { requiresAuth: true, role: 'siswa' }
  },
  {
  path: '/siswa/presensi',
  name: 'PresensiSiswa',
  component: () => import('../views/siswa/PresensiSiswa.vue'),
  meta: { requiresAuth: true, role: 'siswa' }
  },
  {
    path: '/guru/dashboard',
    name: 'DashboardGuru',
    component: DashboardGuru,
    meta: { requiresAuth: true, role: 'guru' }
  },
  {
    path: '/guru/profile',
    name: 'ProfileGuru',
    component: ProfileGuru,
    meta: { requiresAuth: true, role: 'guru' }
  },
  {
    path: '/ortu/dashboard',
    name: 'DashboardOrtu',
    component: DashboardOrtu,
    meta: { requiresAuth: true, role: 'ortu' }
  },
  {
    path: '/ortu/profile',
    name: 'ProfileOrtu',
    component: ProfileOrtu,
    meta: { requiresAuth: true, role: 'ortu' }
  },
  {
    path: '/admin/dashboard',
    name: 'DashboardAdmin',
    component: DashboardAdmin,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/profile',
    name: 'ProfileAdmin',
    component: () => import('../views/admin/ProfileAdmin.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
    {
    path: '/admin/siswa',
    name: 'AdminSiswa',
    component: ManageUsers,
    props: { role: 'siswa', title: 'Kelola Siswa' },
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/guru',
    name: 'AdminGuru',
    component: ManageUsers,
    props: { role: 'guru', title: 'Kelola Guru' },
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/ortu',
    name: 'AdminOrtu',
    component: ManageUsers,
    props: { role: 'ortu', title: 'Kelola Orang Tua' },
    meta: { requiresAuth: true, role: 'admin' }
  },
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
    console.error('🚫 Akses ditolak: Anda tidak memiliki izin untuk mengakses halaman ini.')
    console.info('ℹ️ Silakan login dengan akun yang sesuai untuk mengakses halaman ini.')

    if (userRole === 'siswa') {
      return next('/siswa/dashboard')
    } else if (userRole === 'guru') {
      return next('/guru/dashboard')
    } else if (userRole === 'ortu') {
      return next('/ortu/dashboard')
    } else if (userRole === 'admin') {
      return next('/admin/dashboard')
    } else {
      return next('/login')
    }
  }

  // Jika sudah login, tidak boleh ke halaman login
  if (to.path === '/login' && isAuthenticated) {
    console.info('✅ Already authenticated, redirecting to dashboard')

    if (userRole === 'siswa') {
      return next('/siswa/dashboard')
    } else if (userRole === 'guru') {
      return next('/guru/dashboard')
    } else if (userRole === 'ortu') {
      return next('/ortu/dashboard')
    } else if (userRole === 'admin') {
      return next('/admin/dashboard')
    } else {
      return next()
    }
  }

  console.info(' Access granted to:', to.path)
  next()
})

export default router