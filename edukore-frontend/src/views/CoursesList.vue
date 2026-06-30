<script setup>
import { onMounted } from 'vue'
import { useAcademicStore } from '../stores/academic'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const academicStore = useAcademicStore()

onMounted(() => {
  academicStore.fetchCourses()
})
</script>

<template>
  <DashboardLayout>
    <template #title>Cursos</template>
    <template #subtitle>Gestiona los cursos académicos</template>

    <div class="p-8">
      <div v-if="academicStore.loading" class="flex justify-center items-center h-64">
        <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
      
      <div v-else-if="academicStore.error" class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400">
        {{ academicStore.error }}
      </div>

      <div v-else-if="academicStore.courses.length === 0" class="flex flex-col items-center justify-center py-20 px-4 text-center">
        <div class="w-24 h-24 mb-6 rounded-3xl flex items-center justify-center bg-white/5 border border-white/10 shadow-[0_0_50px_-12px_rgba(99,102,241,0.3)]">
          <svg class="w-12 h-12 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
        </div>
        <h3 class="text-xl font-bold text-white mb-2">No hay cursos disponibles</h3>
        <p class="text-slate-400 max-w-sm">Todavía no has creado ningún curso. Cuando agregues nuevos cursos, aparecerán aquí.</p>
        <button class="mt-6 px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 text-white font-medium hover:from-indigo-400 hover:to-violet-500 transition-all shadow-[0_0_20px_-5px_rgba(99,102,241,0.5)]">
          Crear mi primer curso
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="course in academicStore.courses" 
          :key="course.id"
          class="group relative rounded-2xl p-6 bg-white/5 border border-white/10 hover:border-indigo-500/50 hover:bg-white/10 transition-all duration-300 overflow-hidden flex flex-col"
        >
          <!-- Gradient glow effect on hover -->
          <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-violet-600 rounded-2xl opacity-0 group-hover:opacity-20 blur transition duration-300 z-0"></div>
          
          <div class="relative z-10 flex-1">
            <div class="flex justify-between items-start mb-4">
              <span class="px-3 py-1 text-xs font-medium rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/20">
                {{ course.code }}
              </span>
              <button class="text-slate-400 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
              </button>
            </div>
            
            <h3 class="text-lg font-bold text-white mb-2 line-clamp-2">{{ course.name }}</h3>
            <p class="text-sm text-slate-400 mb-6 line-clamp-3">{{ course.description || 'Sin descripción' }}</p>
          </div>
          
          <div class="relative z-10 pt-4 border-t border-white/10 mt-auto flex items-center justify-between">
            <div class="flex -space-x-2">
              <!-- Mock avatars for students -->
              <div class="w-8 h-8 rounded-full bg-slate-700 border-2 border-gray-900 flex items-center justify-center text-[10px] font-bold text-white">JD</div>
              <div class="w-8 h-8 rounded-full bg-indigo-700 border-2 border-gray-900 flex items-center justify-center text-[10px] font-bold text-white">AM</div>
              <div class="w-8 h-8 rounded-full bg-slate-800 border-2 border-gray-900 flex items-center justify-center text-xs text-slate-400">+5</div>
            </div>
            <button class="text-sm font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
              Ver detalles &rarr;
            </button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
