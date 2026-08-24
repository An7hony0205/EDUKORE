import axios from 'axios'

// ─────────────────────────────────────────────────────────────────────────────
// Instancia de Axios configurada para la API de EDUKORE.
// El baseURL se puede sobrescribir con la variable de entorno VITE_API_URL.
// ─────────────────────────────────────────────────────────────────────────────
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api/v1',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// ── Request Interceptor ───────────────────────────────────────────────────────
// Inyecta el Bearer token en cada petición saliente.
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

// ── Response Interceptor ──────────────────────────────────────────────────────
// Manejo global y centralizado de errores HTTP.
// Los componentes individuales solo necesitan manejar errores 422 (validación).
// ─────────────────────────────────────────────────────────────────────────────
api.interceptors.response.use(
  // Respuestas 2xx → pasar sin modificar
  (response) => response,

  async (error) => {
    const status  = error.response?.status
    const data    = error.response?.data

    // ── 401 Unauthorized: sesión expirada o token inválido ────────────────────
    // Limpia el store de Pinia y redirige a /login usando el router de Vue
    // para no romper el historial del navegador con window.location.href.
    if (status === 401) {
      // Importación dinámica para evitar dependencia circular con el store
      const { useAuthStore } = await import('../stores/auth')
      const authStore = useAuthStore()
      authStore.$patch({ user: null, token: null })
      localStorage.removeItem('edukore_token')

      // Solo redirige si no estamos ya en /login
      if (window.location.pathname !== '/login') {
        const { default: router } = await import('../router/index')
        router.push({ name: 'login' })
      }

      return Promise.reject(error)
    }

    // ── 403 Forbidden: el usuario no tiene permisos ────────────────────────────
    // NO rompemos la UI ni redirigimos agresivamente.
    // Disparamos un evento global que los componentes/layout pueden escuchar
    // para mostrar una notificación/toast apropiada.
    if (status === 403) {
      const message = data?.message ?? 'No tienes permiso para realizar esta acción.'
      window.dispatchEvent(
        new CustomEvent('edukore:error', {
          detail: { type: 'forbidden', message },
        }),
      )
      return Promise.reject(error)
    }

    // ── 422 Unprocessable Entity: errores de validación ───────────────────────
    // Se normaliza el objeto de errores en una estructura consistente y se
    // propaga hacia el componente que realizó la petición para que lo muestre.
    // Estructura garantizada: { message: string, errors: { field: string[] } }
    if (status === 422) {
      const normalizedError = {
        message: data?.message ?? 'Los datos enviados no son válidos.',
        errors: data?.errors ?? {},
      }
      // Adjuntamos el objeto normalizado al error original para fácil acceso
      error.validationErrors = normalizedError
      return Promise.reject(error)
    }

    // ── 500+ Server Errors ────────────────────────────────────────────────────
    // Disparamos un evento global genérico de error de servidor.
    if (status >= 500) {
      window.dispatchEvent(
        new CustomEvent('edukore:error', {
          detail: {
            type: 'server',
            message: 'Error interno del servidor. Por favor, inténtalo de nuevo.',
          },
        }),
      )
    }

    // Cualquier otro error se propaga normalmente
    return Promise.reject(error)
  },
)

export default api
