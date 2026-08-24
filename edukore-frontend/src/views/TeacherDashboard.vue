<script setup>
import DashboardLayout from '../layouts/DashboardLayout.vue'
import { onMounted, ref } from 'vue'

// In a real app we'd fetch the teacher's assigned courses
const myCourses = ref([
  { id: 1, name: 'MatemÃ¡tica', section: '5.Âº Secundaria A' },
  { id: 2, name: 'FÃ­sica', section: '4.Âº Secundaria B' },
])
</script>

<template>
  <DashboardLayout>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Â¿QuÃ© tengo que hacer hoy?</h2>
        <p class="text-slate-500 dark:text-slate-400 mt-1">AquÃ­ estÃ¡n tus cursos asignados.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="course in myCourses" :key="course.id" class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 flex flex-col">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-1">{{ course.name }}</h3>
        <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">{{ course.section }}</p>
        
        <div class="mt-auto flex flex-col gap-3">
          <button class="w-full py-2 px-4 rounded-xl bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 font-medium text-sm transition-colors text-center">
            Tomar Asistencia
          </button>
          <button class="w-full py-2 px-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-700 dark:text-slate-300 font-medium text-sm transition-colors text-center">
            Registrar Calificaciones
          </button>
        </div>
      </div>
    </div>
  </div>
  </DashboardLayout>
</template>
