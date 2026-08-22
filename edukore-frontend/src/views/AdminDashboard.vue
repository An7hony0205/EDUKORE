<script setup>
import { onMounted, ref } from 'vue'
import api from '../api/axios'

const stats = ref({
  students: 0,
  teachers: 0,
  enrollments: 0
})

onMounted(async () => {
  // In a real scenario we fetch the dashboard stats
  try {
    const res = await api.get('/students') // just rough check
    stats.value.students = res.data?.data?.length || 0
  } catch (err) {}
})
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-2xl p-5 border bg-white/5 border-white/10">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Estudiantes</span>
          <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-primary-600/20 text-primary-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-white">{{ stats.students || '—' }}</p>
      </div>
      
      <div class="rounded-2xl p-5 border bg-white/5 border-white/10">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Docentes</span>
          <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-emerald-500/20 text-emerald-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"></path></svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-white">{{ stats.teachers || '—' }}</p>
      </div>
      
      <div class="rounded-2xl p-5 border bg-white/5 border-white/10">
        <div class="flex items-center justify-between mb-3">
          <span class="text-xs font-semibold uppercase tracking-widest text-slate-500">Matrículas</span>
          <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-amber-500/20 text-amber-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-white">{{ stats.enrollments || '—' }}</p>
      </div>
    </div>

    <!-- Quick Actions -->
    <h3 class="text-lg font-bold text-white mt-8 mb-4">Acciones Rápidas</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <router-link to="/students" class="p-4 rounded-xl border border-brand-border bg-brand-surface hover:bg-white/5 transition-colors flex flex-col items-center justify-center gap-2">
        <div class="w-10 h-10 rounded-full bg-primary-600/20 text-primary-400 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
        </div>
        <span class="text-sm font-medium text-slate-300">Nuevo Estudiante</span>
      </router-link>
      <router-link to="/course-assignments" class="p-4 rounded-xl border border-brand-border bg-brand-surface hover:bg-white/5 transition-colors flex flex-col items-center justify-center gap-2">
        <div class="w-10 h-10 rounded-full bg-primary-600/20 text-primary-400 flex items-center justify-center">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        </div>
        <span class="text-sm font-medium text-slate-300">Asignar Docente</span>
      </router-link>
    </div>
  </div>
</template>
