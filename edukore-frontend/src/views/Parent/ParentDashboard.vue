<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/axios'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const isLoading = ref(true)
const data = ref(null)

onMounted(async () => {
  try {
    const response = await api.get('/parent-dashboard')
    data.value = response.data
  } catch(e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <DashboardLayout>
    <div class="h-full flex flex-col space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Portal de Familia</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Visión consolidada de tus hijos</p>
      </div>

      <div v-if="isLoading" class="text-slate-500 dark:text-slate-400">Cargando información...</div>
      
      <div v-else-if="data" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="student in data.students" :key="student.id" class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 p-5 rounded-2xl shadow-lg">
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold text-lg border border-indigo-500/30">
              {{ student.user?.name?.charAt(0) || 'E' }}
            </div>
            <div>
              <h2 class="text-slate-900 dark:text-white font-bold">{{ student.user?.name || 'Estudiante' }}</h2>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ student.current_section || 'Sin sección asignada' }}</p>
            </div>
          </div>
          
          <div class="flex justify-end pt-4 border-t border-slate-200 dark:border-slate-800">
            <button class="text-sm font-medium text-indigo-400 hover:text-indigo-300">Ver Perfil &rarr;</button>
          </div>
        </div>
      </div>
      
      <div v-if="data && data.students.length === 0" class="text-slate-500 dark:text-slate-400 text-center py-10 bg-white/5 rounded-2xl">
        No tienes estudiantes asociados a tu cuenta.
      </div>
    </div>
  </DashboardLayout>
</template>
