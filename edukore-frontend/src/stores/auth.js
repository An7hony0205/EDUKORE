import { defineStore } from 'pinia'
import { ref } from 'vue'
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

  return { user, token, isLoading, login, logout, fetchUser }
})
