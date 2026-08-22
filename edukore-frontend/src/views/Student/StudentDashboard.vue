<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/axios'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const data = ref(null)
const announcements = ref([])
const isLoading = ref(true)

const fetchData = async () => {
  try {
    const [dashRes, annRes] = await Promise.all([
        api.get('/student-dashboard'),
        api.get('/announcements')
    ])
    data.value = dashRes.data
    announcements.value = annRes.data
  } catch (error) {
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchData()
})

const getStatusColor = (status) => {
  if (status === 'PRESENT') return 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20'
  if (status === 'ABSENT') return 'text-rose-400 bg-rose-500/10 border-rose-500/20'
  if (status === 'LATE') return 'text-amber-400 bg-amber-500/10 border-amber-500/20'
  return 'text-slate-400 bg-slate-500/10 border-slate-500/20'
}
</script>

<template>
  <DashboardLayout>
    <div class="h-full flex flex-col space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Portal del Estudiante</h1>
        <p class="text-slate-400 text-sm mt-1">Resumen de tu rendimiento acadÃ©mico</p>
      </div>

            <div v-if="isLoading" class="text-slate-400">Cargando información...</div>
      <div v-else class="flex flex-col space-y-6">
        <!-- Anuncios -->
        <div v-if="announcements.length > 0" class="bg-indigo-900/20 border border-indigo-500/30 p-5 rounded-xl shadow-lg">
          <h2 class="text-lg font-bold text-indigo-400 flex items-center gap-2 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
            Tablón de Anuncios
          </h2>
          <div class="space-y-4">
            <div v-for="ann in announcements" :key="ann.id" class="bg-brand-surface/50 rounded-lg p-4">
              <h3 class="text-white font-semibold">{{ ann.title }}</h3>
              <p class="text-slate-300 text-sm mt-1 whitespace-pre-wrap">{{ ann.body }}</p>
              <div class="text-xs text-slate-500 mt-2">Publicado por {{ ann.author?.name || 'Administración' }} el {{ new Date(ann.created_at).toLocaleDateString() }}</div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Cursos y Notas -->
        <div class="col-span-1 md:col-span-2 space-y-6">
          <h2 class="text-lg font-bold text-white">Mis Cursos</h2>
          <div v-if="data.courses.length === 0" class="text-slate-400 text-sm">No tienes cursos activos.</div>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div v-for="course in data.courses" :key="course.id" class="bg-brand-surface border border-brand-border p-4 rounded-xl shadow-lg">
              <h3 class="text-indigo-400 font-bold text-lg">{{ course.course?.name || 'Curso' }}</h3>
              <p class="text-xs text-slate-400 mt-1">Docente: {{ course.teacher?.name || 'No asignado' }}</p>
            </div>
          </div>

          <h2 class="text-lg font-bold text-white pt-4">Ãšltimas Calificaciones</h2>
          <div v-if="data.recent_grades.length === 0" class="text-slate-400 text-sm">No hay calificaciones recientes.</div>
          <div v-else class="space-y-3">
            <div v-for="grade in data.recent_grades" :key="grade.id" class="bg-brand-surface border border-brand-border p-4 rounded-xl flex justify-between items-center shadow-lg">
              <div>
                <h3 class="text-white font-medium">{{ grade.evaluation?.title }}</h3>
                <p class="text-xs text-slate-400">{{ grade.evaluation?.course_assignment?.course?.name }}</p>
              </div>
              <div class="flex items-center gap-4">
                <div class="text-right">
                  <p class="text-xl font-bold" :class="grade.score >= 11 ? 'text-emerald-400' : 'text-rose-400'">
                    {{ grade.score }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Asistencia -->
        <div class="col-span-1 space-y-6">
          <div class="bg-brand-surface border border-brand-border rounded-xl p-5 shadow-lg">
            <h2 class="text-white font-bold mb-4 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
              Asistencia Reciente
            </h2>
            <div v-if="data.recent_attendance.length === 0" class="text-slate-400 text-sm">
              No hay registros de asistencia.
            </div>
            <div v-else class="space-y-3">
              <div v-for="att in data.recent_attendance" :key="att.id" class="flex items-center justify-between border-b border-brand-border pb-3 last:border-0 last:pb-0">
                <div>
                  <h4 class="text-sm font-medium text-slate-200">{{ att.course_assignment?.course?.name }}</h4>
                  <p class="text-xs text-slate-400">{{ new Date(att.date).toLocaleDateString() }}</p>
                </div>
                <span :class="['px-2 py-1 rounded text-xs font-bold border', getStatusColor(att.status)]">
                  {{ att.status }}
                </span>
              </div>
            </div>
          </div>
        </div>
        
      </div>
      </div>
      </div>
  </DashboardLayout>
</template>

