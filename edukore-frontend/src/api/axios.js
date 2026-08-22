import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// ── Request interceptor ──────────────────────────────────────────────────────
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('edukore_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error),
)

// ── Response interceptor ─────────────────────────────────────────────────────
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.clear()
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    } else if (error.response?.status === 403) {
      // Show friendly message
      const msg = error.response.data?.message || 'No tienes permiso para acceder a este módulo.'
      alert(msg) // Ideally we use a toast notification here
      window.location.href = '/dashboard'
    }
    return Promise.reject(error)
  },
)

export default api
