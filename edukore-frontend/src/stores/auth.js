import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api/axios'

export const useAuthStore = defineStore('auth', () => {
  // ── State ──────────────────────────────────────────────────────────────────
  const user = ref(null)
  const token = ref(localStorage.getItem('edukore_token') ?? null)
  const isLoading = ref(false)

  // ── Actions ────────────────────────────────────────────────────────────────

  /**
   * Authenticate and persist the session.
   * @param {string} email
   * @param {string} password
   * @param {string} subdomain
   */
  async function login(email, password, subdomain) {
    isLoading.value = true
    try {
      const { data } = await api.post('/login', { email, password, subdomain })
      token.value = data.access_token
      user.value = data.user
      localStorage.setItem('edukore_token', data.access_token)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * End the session on the server and wipe local state.
   */
  async function logout() {
    isLoading.value = true
    try {
      await api.post('/logout')
    } catch {
      // Swallow errors — we log out client-side regardless
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('edukore_token')
      isLoading.value = false
    }
  }

  /**
   * Hydrate the user object from the API (e.g. on app boot).
   */
  async function fetchUser() {
    isLoading.value = true
    try {
      const { data } = await api.get('/me')
      user.value = data.user ?? data
    } catch {
      user.value = null
    } finally {
      isLoading.value = false
    }
  }

  // ── Getters (Role Helpers) ──────────────────────────────────────────────────
  const isTeacher = computed(() => {
    const u = user.value
    if (!u) return false
    if (typeof u.role === 'string' && u.role.toLowerCase() === 'teacher') return true
    if (u.role?.name && u.role.name.toLowerCase() === 'teacher') return true
    if (Array.isArray(u.roles) && u.roles.some(r => (typeof r === 'string' ? r : r.name)?.toLowerCase() === 'teacher')) return true
    if (u.teacher_id || u.teacher) return true
    return false
  })

  const isAdmin = computed(() => {
    const u = user.value
    if (!u) return false
    if (typeof u.role === 'string' && (u.role.toLowerCase() === 'admin' || u.role.toLowerCase() === 'super_admin')) return true
    if (u.role?.name && (u.role.name.toLowerCase() === 'admin' || u.role.name.toLowerCase() === 'super_admin')) return true
    if (Array.isArray(u.roles) && u.roles.some(r => {
      const name = (typeof r === 'string' ? r : r.name)?.toLowerCase()
      return name === 'admin' || name === 'super_admin'
    })) return true
    return false
  })

  const isStudent = computed(() => {
    const u = user.value
    if (!u) return false
    if (typeof u.role === 'string' && u.role.toLowerCase() === 'student') return true
    if (u.role?.name && u.role.name.toLowerCase() === 'student') return true
    if (Array.isArray(u.roles) && u.roles.some(r => (typeof r === 'string' ? r : r.name)?.toLowerCase() === 'student')) return true
    return false
  })

  const isParent = computed(() => {
    const u = user.value
    if (!u) return false
    if (typeof u.role === 'string' && u.role.toLowerCase() === 'parent') return true
    if (u.role?.name && u.role.name.toLowerCase() === 'parent') return true
    if (Array.isArray(u.roles) && u.roles.some(r => (typeof r === 'string' ? r : r.name)?.toLowerCase() === 'parent')) return true
    return false
  })

  return {
    user,
    token,
    isLoading,
    login,
    logout,
    fetchUser,
    isTeacher,
    isAdmin,
    isStudent,
    isParent
  }
})
