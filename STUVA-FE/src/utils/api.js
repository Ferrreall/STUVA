import axios from 'axios'

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})


apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
)

// Response interceptor untuk handle errors
apiClient.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    // Jika unauthorized (401), hanya redirect jika bukan sedang submit form
    if (error.response && error.response.status === 401) {
      // Cek apakah ini request ke endpoint tertentu yang tidak boleh auto-redirect
      const isPermissionRequest = error.config?.url?.includes('/permission')

      if (!isPermissionRequest) {
        localStorage.removeItem('token')
        localStorage.removeItem('role')
        localStorage.removeItem('username')
        localStorage.removeItem('user')

        // Redirect ke login jika belum di halaman login
        if (window.location.pathname !== '/login') {
          window.location.href = '/login'
        }
      }
    }
    return Promise.reject(error);
  }
)

export default apiClient