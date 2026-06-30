<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'

const grades = ref([])
const attendance = ref([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    const [gradesRes, attendanceRes] = await Promise.all([
      api.get('/student-portal/grades'),
      api.get('/student-portal/attendance')
    ])
    grades.value = gradesRes.data.data || gradesRes.data
    attendance.value = attendanceRes.data.data || attendanceRes.data
  } catch (err) {
    console.error('Failed to load student data', err)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold text-white">Mi Portal de Estudiante</h2>
    </div>

    <div v-if="isLoading" class="flex justify-center p-12">
      <div class="w-8 h-8 rounded-full border-4 border-indigo-500 border-t-transparent animate-spin"></div>
    </div>
    
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      
      <!-- Notas -->
      <div class="p-6 rounded-2xl border bg-brand-surface border-brand-border">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-primary-600/20 text-primary-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-white">Mis Calificaciones</h3>
        </div>
        
        <div v-if="!grades.length" class="text-sm text-slate-400 text-center py-8">
          No hay calificaciones registradas.
        </div>
        <div v-else class="space-y-3">
          <div v-for="item in grades" :key="item.id" class="p-4 rounded-xl bg-white/5 flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-200">{{ item.evaluation?.title ?? 'Evaluación' }}</p>
              <p class="text-xs text-slate-400">{{ item.course?.name ?? 'Curso' }}</p>
            </div>
            <div class="text-xl font-bold text-primary-400">
              {{ item.score }}
            </div>
          </div>
        </div>
      </div>

      <!-- Asistencias -->
      <div class="p-6 rounded-2xl border bg-brand-surface border-brand-border">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-emerald-500/20 text-emerald-400">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-white">Mi Asistencia</h3>
        </div>
        
        <div v-if="!attendance.length" class="text-sm text-slate-400 text-center py-8">
          No hay registros de asistencia.
        </div>
        <div v-else class="space-y-3">
          <div v-for="item in attendance" :key="item.id" class="p-4 rounded-xl bg-white/5 flex items-center justify-between">
            <div>
              <p class="font-medium text-slate-200">{{ item.date }}</p>
              <p class="text-xs text-slate-400">{{ item.course?.name ?? 'Curso' }}</p>
            </div>
            <div>
              <span v-if="item.status === 'present'" class="px-2.5 py-1 text-xs font-semibold rounded-md bg-emerald-500/20 text-emerald-400">Presente</span>
              <span v-else-if="item.status === 'absent'" class="px-2.5 py-1 text-xs font-semibold rounded-md bg-red-500/20 text-red-400">Ausente</span>
              <span v-else-if="item.status === 'late'" class="px-2.5 py-1 text-xs font-semibold rounded-md bg-yellow-500/20 text-yellow-400">Tardanza</span>
              <span v-else class="px-2.5 py-1 text-xs font-semibold rounded-md bg-primary-500/20 text-primary-400">Justificado</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
