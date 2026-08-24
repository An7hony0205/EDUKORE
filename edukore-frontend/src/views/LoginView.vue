<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const subdomain = ref('')
const email = ref('')
const password = ref('')
const errorMsg = ref('')
const showPassword = ref(false)

async function handleLogin() {
  errorMsg.value = ''
  try {
    await auth.login(email.value, password.value, subdomain.value)
    router.push('/dashboard')
  } catch (err) {
    errorMsg.value =
      err?.response?.data?.message ??
      'Credenciales inválidas. Por favor intenta de nuevo.'
  }
}
</script>

<template>
  <!-- Full-screen dark gradient backdrop -->
  <div
    class="min-h-screen flex items-center justify-center px-4"
    style="background: linear-gradient(135deg, #0a0f1e 0%, #111827 50%, #1a2035 100%)"
  >
    <!-- Ambient glow blobs for depth -->
    <div
      class="absolute top-0 left-1/4 w-96 h-96 rounded-full opacity-10 blur-3xl pointer-events-none"
      style="background: radial-gradient(circle, #6366f1, transparent)"
    />
    <div
      class="absolute bottom-0 right-1/4 w-80 h-80 rounded-full opacity-10 blur-3xl pointer-events-none"
      style="background: radial-gradient(circle, #8b5cf6, transparent)"
    />

    <!-- Glassmorphism card -->
    <div
      class="relative w-full max-w-md rounded-2xl border border-white/10 shadow-2xl"
      style="
        background: rgba(255, 255, 255, 0.04);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
      "
    >
      <!-- Card inner padding -->
      <div class="px-8 py-10">

        <!-- ── Logo / Wordmark ────────────────────────────────────────────── -->
        <div class="flex flex-col items-center mb-10">
          <!-- Icon mark -->
          <div class="mb-3">
            <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
              <defs>
                <linearGradient id="logoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#6366f1"/>
                  <stop offset="100%" stop-color="#8b5cf6"/>
                </linearGradient>
              </defs>
              <rect width="48" height="48" rx="12" fill="url(#logoGrad)"/>
              <path d="M12 24 L24 14 L36 24 L36 36 L24 30 L12 36 Z" fill="white" fill-opacity="0.9"/>
              <circle cx="24" cy="22" r="4" fill="white"/>
            </svg>
          </div>
          <!-- Wordmark -->
          <h1 class="text-2xl font-bold tracking-tight" style="color: #f0f4ff;">
            Edu<span style="color: #818cf8;">Kore</span>
          </h1>
          <p class="text-sm mt-1" style="color: #64748b;">Plataforma educativa inteligente</p>
        </div>

        <!-- ── Form ──────────────────────────────────────────────────────── -->
        <form @submit.prevent="handleLogin" class="space-y-5" novalidate>

          <!-- Institución (Subdomain) -->
          <div>
            <label class="block text-xs font-semibold uppercase tracking-widest mb-2" style="color: #94a3b8;">
              Institución
            </label>
            <div class="relative">
              <input
                v-model="subdomain"
                type="text"
                placeholder="mi-escuela"
                autocomplete="organization"
                class="w-full rounded-xl px-4 py-3 text-sm outline-none transition-all duration-200 placeholder-slate-600 bg-white/5 border border-brand-border text-slate-200 focus:border-primary-500"
              />
              <span
                class="absolute right-4 top-1/2 -translate-y-1/2 text-xs"
                style="color: #475569;"
              >.edukore.app</span>
            </div>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-xs font-semibold uppercase tracking-widest mb-2" style="color: #94a3b8;">
              Correo electrónico
            </label>
            <input
              v-model="email"
              type="email"
              placeholder="admin@miescuela.com"
              autocomplete="email"
              class="w-full rounded-xl px-4 py-3 text-sm outline-none transition-all duration-200 placeholder-slate-600 bg-white/5 border border-brand-border text-slate-200 focus:border-primary-500"
            />
          </div>

          <!-- Password -->
          <div>
            <label class="block text-xs font-semibold uppercase tracking-widest mb-2" style="color: #94a3b8;">
              Contraseña
            </label>
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                autocomplete="current-password"
                class="w-full rounded-xl px-4 py-3 pr-12 text-sm outline-none transition-all duration-200 placeholder-slate-600 bg-white/5 border border-brand-border text-slate-200 focus:border-primary-500"
              />
              <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-lg transition-colors duration-150"
                style="color: #64748b;"
                @click="showPassword = !showPassword"
              >
                <!-- Eye / Eye-off SVG -->
                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                  <line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Error message -->
          <Transition name="fade">
            <div
              v-if="errorMsg"
              class="flex items-start gap-3 rounded-xl px-4 py-3 text-sm"
              style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5;"
            >
              <svg class="w-4 h-4 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
              </svg>
              <span>{{ errorMsg }}</span>
            </div>
          </Transition>

          <!-- CTA button -->
          <button
            type="submit"
            :disabled="auth.isLoading"
            class="relative w-full flex items-center justify-center gap-2 rounded-xl py-3 px-6 text-sm font-semibold transition-all duration-200 overflow-hidden bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
          >
            <!-- Spinner -->
            <svg
              v-if="auth.isLoading"
              class="w-4 h-4 animate-spin"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="white" stroke-width="4"/>
              <path class="opacity-75" fill="white" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            <span>{{ auth.isLoading ? 'Iniciando sesión…' : 'Iniciar sesión' }}</span>
          </button>
        </form>

        <!-- Footer -->
        <p class="text-center text-xs mt-8" style="color: #334155;">
          © {{ new Date().getFullYear() }} EduKore · Todos los derechos reservados
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
.animate-spin {
  animation: spin 0.75s linear infinite;
}
</style>
