<script setup>
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/axios'

const router = useRouter()
const assignments = ref([])
const loading = ref(true)

const fetchDashboard = async () => {
  try {
    const res = await api.get('/teacher/dashboard')
    assignments.value = res.data.assignments
  } catch (error) {
    console.error("Failed to load teacher dashboard", error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboard()
})
</script>

<template>
  <DashboardLayout>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Mis Cursos Asignados</h2>
    </div>

    <div v-if="loading" class="flex justify-center p-12">
      <div class="w-8 h-8 rounded-full border-4 border-primary-500 border-t-transparent animate-spin"></div>
    </div>

    <div v-else-if="assignments.length === 0" class="text-center p-12 bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl text-slate-500 dark:text-slate-400">
      No tienes cursos asignados en este momento.
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="assignment in assignments" 
        :key="assignment.id"
        class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl p-6 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors cursor-pointer group flex flex-col"
        @click="router.push(`/teacher/courses/${assignment.id}`)"
      >
        <div class="flex-grow">
          <div class="flex justify-between items-start mb-4">
            <div class="p-3 bg-primary-600/20 text-primary-400 rounded-xl">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
          </div>
          
          <h3 class="text-xl font-bold text-slate-100 group-hover:text-primary-400 transition-colors">
            {{ assignment.course?.name }}
          </h3>
          <p class="text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-2">
            <span>{{ assignment.section?.grade_level?.level?.name }}</span>
            <span>&bull;</span>
            <span>{{ assignment.section?.grade_level?.name }}</span>
            <span>&bull;</span>
            <span class="font-medium text-slate-700 dark:text-slate-300">SecciÃ³n {{ assignment.section?.name }}</span>
          </p>
          <div class="mt-4 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            {{ assignment.students_count }} estudiantes
          </div>
        </div>
        
        <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center text-sm">
          <span class="text-primary-400 font-medium group-hover:underline">Ver Curso &rarr;</span>
        </div>
      </div>
    </div>
  </div>
  </DashboardLayout>
</template>
