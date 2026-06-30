<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'

const children = ref([])
const selectedChild = ref(null)
const isLoading = ref(true)

onMounted(async () => {
  try {
    const res = await api.get('/parent-portal/children')
    children.value = res.data.data || res.data
    if (children.value.length > 0) {
      selectedChild.value = children.value[0]
    }
  } catch (err) {
    console.error('Failed to load children data', err)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <h2 class="text-2xl font-bold text-white">Portal de Padres</h2>
      
      <!-- Selector de Hijos -->
      <div v-if="children.length > 1" class="relative">
        <select 
          v-model="selectedChild"
          class="appearance-none bg-brand-surface border border-brand-border text-white text-sm rounded-xl px-4 py-2.5 pr-10 focus:outline-none focus:ring-2 focus:ring-primary-500"
        >
          <option v-for="child in children" :key="child.id" :value="child" class="bg-brand-dark text-white">
            {{ child.user?.name ?? 'Estudiante' }}
          </option>
        </select>
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </div>
    </div>

    <div v-if="isLoading" class="flex justify-center p-12">
      <div class="w-8 h-8 rounded-full border-4 border-primary-500 border-t-transparent animate-spin"></div>
    </div>
    
    <div v-else-if="!selectedChild" class="text-center p-12 text-slate-400 border border-brand-border rounded-2xl bg-brand-surface">
      No hay estudiantes vinculados a tu cuenta.
    </div>

    <div v-else class="space-y-6">
      <div class="flex items-center gap-4 p-4 rounded-2xl border border-brand-border bg-brand-surface">
        <div class="w-12 h-12 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold text-lg">
          {{ selectedChild.user?.name?.charAt(0) ?? 'E' }}
        </div>
        <div>
          <h3 class="text-lg font-bold text-white">{{ selectedChild.user?.name }}</h3>
          <p class="text-sm text-slate-400">Estudiante activo</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Notas -->
        <div class="p-6 rounded-2xl border bg-brand-surface border-brand-border">
          <h3 class="text-lg font-semibold text-white mb-6">Calificaciones Recientes</h3>
          <div v-if="!selectedChild.grades?.length" class="text-sm text-slate-400 py-4">
            No hay calificaciones registradas.
          </div>
          <div v-else class="space-y-3">
            <div v-for="item in selectedChild.grades" :key="item.id" class="p-4 rounded-xl bg-white/5 flex items-center justify-between">
              <div>
                <p class="font-medium text-slate-200">{{ item.evaluation?.title ?? 'Evaluación' }}</p>
              </div>
              <div class="text-xl font-bold text-primary-400">{{ item.score }}</div>
            </div>
          </div>
        </div>

        <!-- Asistencias -->
        <div class="p-6 rounded-2xl border bg-brand-surface border-brand-border">
          <h3 class="text-lg font-semibold text-white mb-6">Registro de Asistencia</h3>
          <div v-if="!selectedChild.attendance?.length" class="text-sm text-slate-400 py-4">
            No hay registros recientes.
          </div>
          <div v-else class="space-y-3">
            <div v-for="item in selectedChild.attendance" :key="item.id" class="p-4 rounded-xl bg-white/5 flex items-center justify-between">
              <p class="font-medium text-slate-200">{{ item.date }}</p>
              <span class="px-2.5 py-1 text-xs font-semibold rounded-md bg-white/10 text-slate-300">
                {{ item.status }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
