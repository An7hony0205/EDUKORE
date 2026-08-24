<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import GlobalSearch from '../components/ui/GlobalSearch.vue'
import Breadcrumbs from '../components/ui/Breadcrumbs.vue'

import api from '../api/axios'

const router = useRouter()
const auth = useAuthStore()

const isSidebarOpen = ref(false)
const tenantLogo = ref(null)

const isMobileClose = () => {
  isSidebarOpen.value = false
}

onMounted(async () => {
  if (auth.token && !auth.user) {
    await auth.fetchUser()
  }
  
  if (auth.token) {
    try {
      const res = await api.get('/settings')
      if (res.data && res.data.logo_url) {
        tenantLogo.value = `http://localhost:8000/storage/${res.data.logo_url}`
      }
    } catch (e) {
      console.error('Error fetching layout settings', e)
    }
  }
})

const isDarkMode = ref(localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches))

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.setItem('theme', 'light')
  }
}

onMounted(() => {
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
})

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

// User initials for avatar
const initials = () => {
  const name = auth.user?.name ?? auth.user?.email ?? 'U'
  return name
    .split(' ')
    .slice(0, 2)
    .map((n) => n[0].toUpperCase())
    .join('')
}
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-brand-dark">
    
    <!-- Mobile overlay -->
    <div v-if="isSidebarOpen" @click="isSidebarOpen = false" class="fixed inset-0 bg-black/60 z-20 md:hidden backdrop-blur-sm transition-opacity"></div>

    <!-- ── Sidebar ────────────────────────────────────────────────── -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-30 w-64 shrink-0 border-r flex flex-col transition-transform duration-300 md:relative md:translate-x-0',
        isSidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'bg-white dark:bg-brand-surface border-slate-200 dark:border-brand-border'
      ]"
    >
      <!-- Logo area -->
      <div class="flex items-center justify-between px-6 py-6 border-b border-slate-200 dark:border-brand-border">
        <div class="flex items-center gap-3">
          <div v-if="!tenantLogo" class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-900 dark:bg-white text-white dark:text-slate-900">
            <svg width="18" height="18" viewBox="0 0 48 48" fill="currentColor">
              <path d="M12 24L24 14L36 24L36 36L24 30L12 36Z" fill-opacity="0.9"/>
              <circle cx="24" cy="22" r="4"/>
            </svg>
          </div>
          <div v-else class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-brand-border overflow-hidden">
            <img :src="tenantLogo" alt="Logo Institución" class="w-full h-full object-cover" />
          </div>
          <span class="text-base font-bold text-slate-900 dark:text-slate-100">
            {{ auth.user?.tenant?.name || 'EduKore' }}
          </span>
        </div>
        <!-- Close button for mobile -->
        <button @click="isSidebarOpen = false" class="md:hidden text-slate-400 hover:text-slate-900 dark:hover:text-white">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <!-- Dashboard -->
        <router-link to="/dashboard" custom v-slot="{ isActive, navigate }">
          <NavItem icon="grid" label="Panel Principal" :active="isActive" @click="navigate(); isMobileClose()" />
        </router-link>

        <!-- ACADÉMICO -->
        <template v-if="auth.user?.role?.name === 'admin' || auth.user?.role?.name === 'teacher'">
          <div class="pt-4 pb-2">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Académico</p>
          </div>
          <router-link to="/students" custom v-slot="{ isActive, navigate }">
            <NavItem icon="users" label="Estudiantes" :active="isActive" @click="navigate(); isMobileClose()" />
          </router-link>
          <router-link v-if="auth.user?.role?.name === 'admin'" to="/teachers" custom v-slot="{ isActive, navigate }">
            <NavItem icon="users" label="Docentes" :active="isActive" @click="navigate(); isMobileClose()" />
          </router-link>
          <router-link to="/course-assignments" custom v-slot="{ isActive, navigate }">
            <NavItem icon="book-open" label="Cursos" :active="isActive" @click="navigate(); isMobileClose()" />
          </router-link>
          <!-- Links for attendance, grades... handled via courses mostly for teachers, but reports here -->
          <router-link to="/reports" custom v-slot="{ isActive, navigate }">
            <NavItem icon="bar-chart-2" label="Reportes" :active="isActive" @click="navigate(); isMobileClose()" />
          </router-link>
        </template>

        <!-- FINANZAS -->
        <template v-if="auth.user?.role?.name === 'admin' && auth.user?.tenant?.active_modules?.finances">
          <div class="pt-4 pb-2">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Finanzas & Comunidad</p>
          </div>
          <router-link to="/finances" custom v-slot="{ isActive, navigate }">
            <NavItem icon="credit-card" label="Pagos y Cobros" :active="isActive" @click="navigate(); isMobileClose()" />
          </router-link>
          <router-link to="/community-events" custom v-slot="{ isActive, navigate }" v-if="false">
            <NavItem icon="users" label="Faenas y Eventos" :active="isActive" @click="navigate(); isMobileClose()" />
          </router-link>
          <router-link to="/announcements" custom v-slot="{ isActive, navigate }" v-if="false">
            <NavItem icon="clipboard" label="Circulares" :active="isActive" @click="navigate(); isMobileClose()" />
          </router-link>
        </template>

        <!-- ADMINISTRACIÓN -->
        <template v-if="auth.user?.role?.name === 'admin'">
          <div class="pt-4 pb-2">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Administración</p>
          </div>
          <router-link to="/academic-periods" custom v-slot="{ isActive, navigate }">
            <NavItem icon="calendar" label="Periodos Acad." :active="isActive" @click="navigate(); isMobileClose()" />
          </router-link>
          <router-link to="/settings" custom v-slot="{ isActive, navigate }">
            <NavItem icon="settings" label="Configuración" :active="isActive" @click="navigate(); isMobileClose()" />
          </router-link>
          <router-link to="/audit-logs" custom v-slot="{ isActive, navigate }">
            <NavItem icon="shield" label="Auditoría" :active="isActive" @click="navigate(); isMobileClose()" />
          </router-link>
        </template>
      </nav>

      <!-- Bottom user area -->
      <div class="px-4 py-4 border-t border-slate-200 dark:border-brand-border">
        <button
          @click="handleLogout"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 text-slate-500 dark:text-slate-400 hover:bg-red-50 hover:dark:bg-red-500/10 hover:text-red-600 hover:dark:text-red-400"
        >
          <!-- Logout icon -->
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
            <polyline points="16 17 21 12 16 7"/>
            <line x1="21" y1="12" x2="9" y2="12"/>
          </svg>
          Cerrar sesión
        </button>
      </div>
    </aside>

    <!-- ── Main content ────────────────────────────────────────────────── -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">

      <!-- Top bar -->
      <header
        class="flex items-center justify-between px-4 md:px-8 py-4 border-b border-slate-200 dark:border-brand-border shrink-0 z-10 bg-white/80 dark:bg-brand-dark/80 backdrop-blur-md"
      >
        <div class="flex items-center gap-4 flex-1">
          <button @click="isSidebarOpen = true" class="md:hidden text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white p-2 -ml-2 rounded-lg hover:bg-slate-200 dark:hover:bg-white/5 transition-colors">
             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
          </button>
          
          <div class="hidden md:block w-full max-w-md">
            <!-- Global Search Component -->
            <GlobalSearch />
          </div>
        </div>

        <!-- Theme Toggle & Avatar -->
        <div class="flex items-center gap-4 ml-4">
          <button @click="toggleDarkMode" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/5 transition-colors">
            <svg v-if="isDarkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
          </button>
          
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold text-white shrink-0 bg-slate-900 dark:bg-white dark:text-slate-900">
              {{ initials() }}
            </div>
            <div class="hidden sm:block">
              <p class="text-sm font-medium leading-tight dark:text-slate-200 text-slate-900">{{ auth.user?.name ?? '—' }}</p>
              <p class="text-xs leading-tight truncate max-w-[120px] dark:text-slate-400 text-slate-500">{{ auth.user?.role?.name ?? 'Usuario' }}</p>
            </div>
          </div>
        </div>
      </header>

      <!-- Page body -->
      <div class="flex-1 overflow-y-auto p-4 md:p-8 relative">
        <Breadcrumbs />
        <slot />
      </div>

    </main>
  </div>
</template>

<script>
// Inline functional sub-components to avoid extra files at this stage
import { defineComponent, h } from 'vue'

// Feather-style icon paths map
const iconPaths = {
  grid: '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
  users: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
  'book-open': '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
  'bar-chart-2': '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
  settings: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
  'credit-card': '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
  shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
  calendar: '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
  clipboard: '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>',
}

function FeatherIcon(name, size = 16) {
  return h('svg', {
    xmlns: 'http://www.w3.org/2000/svg',
    width: size, height: size,
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    'stroke-width': '2',
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    innerHTML: iconPaths[name] ?? '',
  })
}

export default defineComponent({
  components: {
    NavItem: defineComponent({
      props: { icon: String, label: String, active: Boolean },
      setup(props) {
        return () => h('button', {
          class: [
            'w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200',
            props.active
              ? 'bg-slate-200 text-slate-900 dark:bg-slate-800 dark:text-white'
              : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5'
          ]
        }, [
          FeatherIcon(props.icon, 16),
          h('span', props.label),
        ])
      },
    }),
  },
})
</script>
