<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import api from '../api/axios'

const route = useRoute()
const router = useRouter()
const courseId = route.params.id

const course = ref(null)
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const response = await api.get(`/courses/${courseId}`)
    course.value = response.data
  } catch (err) {
    error.value = 'No se pudo cargar la información del curso.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <DashboardLayout>
    <div v-if="loading" class="flex justify-center items-center h-full">
      <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
    </div>
    
    <div v-else-if="error" class="p-8">
      <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400">
        {{ error }}
      </div>
      <button @click="router.push('/courses')" class="mt-4 text-indigo-400 hover:text-indigo-300">&larr; Volver a cursos</button>
    </div>
    
    <div v-else-if="course" class="p-8 max-w-5xl mx-auto space-y-8">
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <button @click="router.push('/courses')" class="text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors flex items-center gap-1 mb-4">
            &larr; Volver a cursos
          </button>
          <div class="flex items-center gap-3 mb-2">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">{{ course.name }}</h1>
            <span v-if="course.is_active === false" class="px-3 py-1 text-xs font-medium rounded-full bg-red-500/10 text-red-400 border border-red-500/20">
              Inactivo
            </span>
            <span v-else class="px-3 py-1 text-xs font-medium rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
              Activo
            </span>
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-lg" v-if="course.code">Código: <span class="text-slate-900 dark:text-white">{{ course.code }}</span></p>
        </div>
        <div>
          <button @click="router.push('/course-assignments')" class="px-5 py-2.5 bg-indigo-600/20 hover:bg-indigo-600/40 text-indigo-300 text-sm font-medium rounded-xl transition-colors border border-indigo-500/30 flex items-center gap-2">
            Gestionar Asignaciones
          </button>
        </div>
      </div>

      <!-- General Info -->
      <div class="bg-white/5 border border-white/10 rounded-2xl p-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Información General</h2>
        <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ course.description || 'Este curso no cuenta con una descripción detallada.' }}</p>
      </div>

      <!-- Assignments -->
      <div class="bg-white/5 border border-white/10 rounded-2xl p-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6">Asignaciones Docentes Existentes</h2>
        
        <div v-if="!course.course_assignments || course.course_assignments.length === 0" class="text-center py-10 bg-black/20 rounded-xl border border-slate-200 dark:border-slate-800">
          <p class="text-slate-500 dark:text-slate-400 mb-2">No hay docentes asignados a este curso actualmente.</p>
          <p class="text-sm text-slate-500">Puedes asignar docentes desde el módulo de Asignación Docente.</p>
        </div>
        
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="assignment in course.course_assignments" :key="assignment.id" class="bg-black/30 border border-slate-200 dark:border-slate-700 rounded-xl p-4 hover:border-indigo-500/30 transition-colors">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-full bg-indigo-500/20 text-indigo-300 flex items-center justify-center font-bold text-lg border border-indigo-500/30">
                {{ assignment.teacher?.name?.charAt(0) || 'D' }}
              </div>
              <div>
                <p class="font-bold text-slate-900 dark:text-white">{{ assignment.teacher?.name || 'Desconocido' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Docente Titular</p>
              </div>
            </div>
            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 text-sm text-slate-700 dark:text-slate-300 space-y-1">
              <p><span class="text-slate-500">Nivel:</span> {{ assignment.section?.grade_level?.level?.name || 'N/A' }}</p>
              <p><span class="text-slate-500">Grado:</span> {{ assignment.section?.grade_level?.name || 'N/A' }}</p>
              <p><span class="text-slate-500">Sección:</span> {{ assignment.section?.name || 'N/A' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
