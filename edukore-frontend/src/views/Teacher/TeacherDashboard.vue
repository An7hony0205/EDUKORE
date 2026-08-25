<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import api from '../../api/axios'

const router = useRouter()
const summary = ref(null)
const myCourses = ref([])
const mySchedule = ref([])
const loading = ref(true)

const activeTab = ref('overview') // 'overview' | 'schedule'

const fetchDashboardData = async () => {
  try {
    const [summaryRes, coursesRes, scheduleRes] = await Promise.all([
      api.get('/teacher/dashboard-summary'),
      api.get('/teacher/my-courses'),
      api.get('/teacher/my-schedule')
    ])
    
    summary.value = summaryRes.data
    myCourses.value = coursesRes.data.data
    mySchedule.value = scheduleRes.data.data
  } catch (error) {
    console.error("Failed to load teacher dashboard", error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchDashboardData()
})

const getDayName = (dayNumber) => {
  const days = { 1: 'Lunes', 2: 'Martes', 3: 'Miércoles', 4: 'Jueves', 5: 'Viernes' }
  return days[dayNumber] || 'Día'
}

const goToAttendance = (sectionId, assignmentId) => {
  // Navigate to daily attendance and auto-select section & assignment
  router.push({ name: 'daily-attendance', query: { section_id: sectionId, assignment_id: assignmentId } })
}

const goToGrades = (sectionId, assignmentId) => {
  // Navigate to grades and auto-select section & assignment
  router.push({ name: 'grades', query: { section_id: sectionId, assignment_id: assignmentId } })
}
</script>

<template>
  <DashboardLayout>
    <div v-if="loading" class="flex justify-center items-center min-h-[50vh]">
      <div class="w-10 h-10 rounded-full border-4 border-indigo-500 border-t-transparent animate-spin"></div>
    </div>
    
    <div v-else-if="summary" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      
      <!-- Welcome Header -->
      <div class="bg-gradient-to-r from-indigo-600 to-indigo-900 rounded-3xl p-8 text-white shadow-lg relative overflow-hidden">
        <div class="relative z-10">
          <h1 class="text-3xl font-bold mb-2">Bienvenido, {{ summary.teacher.name }}</h1>
          <p class="text-indigo-200">Panel de Control Docente</p>
          
          <div class="flex flex-wrap gap-4 mt-6">
            <div class="bg-white/10 backdrop-blur border border-white/20 rounded-xl px-5 py-3 text-center">
              <span class="block text-2xl font-bold">{{ summary.metrics.total_courses }}</span>
              <span class="text-xs text-indigo-200 uppercase tracking-wider">Cursos</span>
            </div>
            <div class="bg-white/10 backdrop-blur border border-white/20 rounded-xl px-5 py-3 text-center">
              <span class="block text-2xl font-bold">{{ summary.metrics.total_sections }}</span>
              <span class="text-xs text-indigo-200 uppercase tracking-wider">Secciones</span>
            </div>
            <div class="bg-white/10 backdrop-blur border border-white/20 rounded-xl px-5 py-3 text-center">
              <span class="block text-2xl font-bold">{{ summary.metrics.total_students }}</span>
              <span class="text-xs text-indigo-200 uppercase tracking-wider">Alumnos</span>
            </div>
          </div>
        </div>
        <div class="absolute right-0 bottom-0 opacity-10 pointer-events-none transform translate-x-1/4 translate-y-1/4">
          <svg class="w-96 h-96" fill="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z" /><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" /></svg>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex space-x-4 border-b border-slate-200 dark:border-slate-700">
        <button 
          @click="activeTab = 'overview'" 
          :class="['pb-3 px-2 text-sm font-semibold transition-colors', activeTab === 'overview' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300']"
        >
          Vista General
        </button>
        <button 
          @click="activeTab = 'schedule'" 
          :class="['pb-3 px-2 text-sm font-semibold transition-colors', activeTab === 'schedule' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300']"
        >
          Mi Horario Semanal
        </button>
      </div>

      <!-- Tab: Overview -->
      <div v-if="activeTab === 'overview'" class="space-y-8">
        
        <!-- Clases de Hoy -->
        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Mis Clases de Hoy
          </h2>
          
          <div v-if="!summary.todays_classes || summary.todays_classes.length === 0" class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-6 text-center text-slate-500 border border-slate-200 dark:border-slate-700">
            No tienes clases programadas para hoy. ¡Buen día!
          </div>
          <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="cls in summary.todays_classes" :key="cls.id" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
              <div class="flex justify-between items-start mb-3">
                <span class="inline-block px-2.5 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-bold rounded-lg border border-indigo-100 dark:border-indigo-800">
                  {{ String(cls.start_time).substring(0,5) }} - {{ String(cls.end_time).substring(0,5) }}
                </span>
                <span class="text-xs text-slate-500 bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded">Aula {{ cls.room || 'N/A' }}</span>
              </div>
              <h3 class="font-bold text-slate-800 dark:text-white truncate" :title="cls.course_assignment?.course?.name">{{ cls.course_assignment?.course?.name }}</h3>
              <p class="text-sm text-slate-500 mt-1 truncate">
                {{ cls.section?.grade?.academic_level?.name }} - {{ cls.section?.grade?.name }} | Sección {{ cls.section?.name }}
              </p>
              <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <button @click="goToAttendance(cls.section_id, cls.course_assignment_id)" class="w-full text-center px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                  Pasar Asistencia
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Mis Cursos -->
        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
            Mis Asignaturas / Cursos
          </h2>

          <div v-if="myCourses.length === 0" class="text-center p-12 bg-slate-50 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 rounded-2xl text-slate-500">
            No tienes cursos asignados.
          </div>
          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="assign in myCourses" :key="assign.id" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-6 shadow-sm flex flex-col">
              <div class="flex-grow">
                <div class="flex justify-between items-start mb-3">
                  <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                  </div>
                  <span class="bg-slate-100 dark:bg-slate-700 text-slate-500 text-xs px-2 py-1 rounded-lg">
                    Aula {{ assign.section?.room || 'N/A' }}
                  </span>
                </div>
                
                <h3 class="text-lg font-bold text-slate-800 dark:text-white leading-tight">
                  {{ assign.course?.name }}
                </h3>
                <p class="text-slate-500 text-sm mt-1">
                  {{ assign.section?.grade?.academic_level?.name }} &bull; {{ assign.section?.grade?.name }}
                  <span class="font-semibold text-slate-700 dark:text-slate-300">Sección {{ assign.section?.name }}</span>
                </p>
                
                <div class="mt-4 flex items-center gap-2 text-sm text-slate-500 bg-slate-50 dark:bg-slate-900/50 p-2 rounded-lg border border-slate-100 dark:border-slate-700">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <span>{{ assign.students_count }} Estudiantes</span>
                </div>
              </div>
              
              <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700 flex gap-3">
                <button @click="goToAttendance(assign.section_id, assign.id)" class="flex-1 py-2 bg-indigo-50 dark:bg-indigo-900/20 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-1.5 border border-indigo-100 dark:border-indigo-800">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                  Asistencia
                </button>
                <button @click="goToGrades(assign.section_id, assign.id)" class="flex-1 py-2 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-1.5 border border-emerald-100 dark:border-emerald-800">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                  Calificar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab: Schedule -->
      <div v-if="activeTab === 'schedule'" class="space-y-6">
        <h2 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
          <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
          Mi Horario Semanal
        </h2>

        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="bg-slate-50 dark:bg-slate-700/50">
                  <th class="px-4 py-3 border-b border-slate-200 dark:border-slate-600 text-left font-semibold text-slate-500 w-32">Día</th>
                  <th class="px-4 py-3 border-b border-slate-200 dark:border-slate-600 text-left font-semibold text-slate-500 w-32">Hora</th>
                  <th class="px-4 py-3 border-b border-slate-200 dark:border-slate-600 text-left font-semibold text-slate-500">Materia</th>
                  <th class="px-4 py-3 border-b border-slate-200 dark:border-slate-600 text-left font-semibold text-slate-500">Sección</th>
                  <th class="px-4 py-3 border-b border-slate-200 dark:border-slate-600 text-left font-semibold text-slate-500 w-24">Aula</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                <tr v-if="mySchedule.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-slate-500">No hay clases programadas en tu horario.</td>
                </tr>
                <tr v-for="sch in mySchedule" :key="sch.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                  <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200">{{ getDayName(sch.day_of_week) }}</td>
                  <td class="px-4 py-3 text-slate-600 dark:text-slate-300 font-mono text-xs">
                    {{ String(sch.start_time).substring(0,5) }} - {{ String(sch.end_time).substring(0,5) }}
                  </td>
                  <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ sch.course_assignment?.course?.name }}</td>
                  <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
                    {{ sch.section?.grade?.name }} "{{ sch.section?.name }}"
                  </td>
                  <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ sch.room || 'N/A' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
    </div>
  </DashboardLayout>
</template>
