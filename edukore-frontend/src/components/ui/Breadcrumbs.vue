<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

// We can map route names to friendly labels here or use route.meta
const breadcrumbs = computed(() => {
  const crumbs = []
  crumbs.push({ name: 'Inicio', path: '/dashboard' })
  
  if (route.name !== 'dashboard') {
    // Basic mapping for now, can be expanded
    const map = {
      'students': 'Estudiantes',
      'student-profile': 'Perfil Estudiante',
      'courses': 'Cursos',
      'course-assignments': 'Asignación Docente',
      'academic-periods': 'Periodos Académicos',
      'billing': 'Finanzas',
      'reports': 'Reportes',
      'settings': 'Configuración',
      'audit-logs': 'Auditoría'
    }
    
    if (map[route.name]) {
      crumbs.push({ name: map[route.name], path: route.path })
    }
  }
  
  return crumbs
})
</script>

<template>
  <nav class="flex text-sm text-slate-500 dark:text-slate-400 font-medium mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
      <li v-for="(crumb, index) in breadcrumbs" :key="index" class="inline-flex items-center">
        <router-link v-if="index < breadcrumbs.length - 1" :to="crumb.path" class="inline-flex items-center hover:text-slate-900 dark:hover:text-white transition-colors">
          {{ crumb.name }}
        </router-link>
        <span v-else class="text-slate-900 dark:text-white">{{ crumb.name }}</span>
        
        <svg v-if="index < breadcrumbs.length - 1" class="w-4 h-4 text-slate-400 dark:text-slate-500 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
      </li>
    </ol>
  </nav>
</template>
