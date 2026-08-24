<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import api from '../../api/axios'
import * as feather from 'feather-icons'

const route = useRoute()
const router = useRouter()
const sectionId = route.params.id

const loading = ref(true)
const error = ref('')
const details = ref(null)
const activeTab = ref('resumen')

onMounted(async () => {
  try {
    const response = await api.get(`/sections/${sectionId}/details`)
    details.value = response.data
  } catch (err) {
    error.value = 'No se pudo cargar la información de la sección.'
  } finally {
    loading.value = false
    setTimeout(() => {
      feather.replace()
    }, 100)
  }
})
</script>

<template>
  <DashboardLayout>
    <div class="p-8 max-w-5xl mx-auto space-y-8">
      <!-- Breadcrumbs / Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <button @click="router.push('/academic-structure')" class="text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors flex items-center gap-1 mb-4">
            <i data-feather="arrow-left" class="w-4 h-4"></i> Volver a Estructura
          </button>
          
          <div v-if="loading" class="h-10 w-64 bg-white/5 animate-pulse rounded-lg mb-2"></div>
          <div v-else-if="details" class="flex items-center gap-3 mb-2">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">{{ details.section.grade_level }} — Sección {{ details.section.name }}</h1>
          </div>
          
          <div v-if="loading" class="h-6 w-48 bg-white/5 animate-pulse rounded-lg"></div>
          <p v-else-if="details" class="text-slate-500 dark:text-slate-400 text-lg flex items-center gap-2">
            {{ details.section.academic_year }}
            <span class="text-slate-600">•</span>
            {{ details.section.level }}
          </p>
        </div>
      </div>

      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else-if="error" class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400">
        {{ error }}
      </div>

      <div v-else-if="details" class="space-y-6">
        <!-- Tabs -->
        <div class="border-b border-slate-200 dark:border-slate-800 overflow-x-auto">
          <nav class="-mb-px flex space-x-8">
            <button 
              @click="activeTab = 'resumen'"
              :class="[
                activeTab === 'resumen' 
                  ? 'border-indigo-500 text-indigo-400' 
                  : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:text-slate-300 hover:border-slate-300',
                'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors'
              ]"
            >
              Resumen
            </button>
            <button 
              @click="activeTab = 'estudiantes'"
              :class="[
                activeTab === 'estudiantes' 
                  ? 'border-indigo-500 text-indigo-400' 
                  : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:text-slate-300 hover:border-slate-300',
                'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors'
              ]"
            >
              Estudiantes
            </button>
            <button 
              @click="activeTab = 'docentes'"
              :class="[
                activeTab === 'docentes' 
                  ? 'border-indigo-500 text-indigo-400' 
                  : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:text-slate-300 hover:border-slate-300',
                'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors'
              ]"
            >
              Docentes
            </button>
            <button 
              @click="activeTab = 'cursos'"
              :class="[
                activeTab === 'cursos' 
                  ? 'border-indigo-500 text-indigo-400' 
                  : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:text-slate-300 hover:border-slate-300',
                'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors'
              ]"
            >
              Cursos
            </button>
          </nav>
        </div>

        <!-- Tab Content: Resumen -->
        <div v-if="activeTab === 'resumen'" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center">
                <i data-feather="users" class="w-6 h-6"></i>
              </div>
              <div>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Estudiantes</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ details.stats.students_count }}</p>
              </div>
            </div>
            
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                <i data-feather="book-open" class="w-6 h-6"></i>
              </div>
              <div>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Cursos</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ details.stats.courses_count }}</p>
              </div>
            </div>
            
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6 flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center">
                <i data-feather="briefcase" class="w-6 h-6"></i>
              </div>
              <div>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Docentes</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ details.stats.teachers_count }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab Content: Estudiantes -->
        <div v-if="activeTab === 'estudiantes'" class="bg-white/5 border border-white/10 rounded-2xl overflow-hidden">
          <div v-if="details.lists.students.length === 0" class="p-8 text-center text-slate-500 dark:text-slate-400">
            No hay estudiantes matriculados en esta sección.
          </div>
          <table v-else class="min-w-full divide-y divide-white/10">
            <thead class="bg-black/20">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nombre</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nº Matrícula</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10 bg-transparent">
              <tr v-for="student in details.lists.students" :key="student.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-slate-900 dark:text-white">{{ student.name }}</div>
                  <div class="text-sm text-slate-500 dark:text-slate-400">{{ student.email }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">
                  {{ student.enrollment_number || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 py-1 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    {{ student.status || 'Activo' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Tab Content: Docentes -->
        <div v-if="activeTab === 'docentes'" class="bg-white/5 border border-white/10 rounded-2xl overflow-hidden">
          <div v-if="details.lists.teachers.length === 0" class="p-8 text-center text-slate-500 dark:text-slate-400">
            No hay docentes asignados a esta sección.
          </div>
          <table v-else class="min-w-full divide-y divide-white/10">
            <thead class="bg-black/20">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Docente</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cursos Asignados</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10 bg-transparent">
              <tr v-for="teacher in details.lists.teachers" :key="teacher.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-slate-900 dark:text-white">{{ teacher.name }}</div>
                  <div class="text-sm text-slate-500 dark:text-slate-400">{{ teacher.email }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-wrap gap-2">
                    <span v-for="c in teacher.courses" :key="c" class="px-2 py-1 text-xs rounded bg-white/10 text-slate-700 dark:text-slate-300">
                      {{ c }}
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Tab Content: Cursos -->
        <div v-if="activeTab === 'cursos'" class="bg-white/5 border border-white/10 rounded-2xl overflow-hidden">
          <div v-if="details.lists.courses.length === 0" class="p-8 text-center text-slate-500 dark:text-slate-400">
            No hay cursos asignados a esta sección.
          </div>
          <table v-else class="min-w-full divide-y divide-white/10">
            <thead class="bg-black/20">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Curso</th>
                <th class="px-6 py-4 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Docente</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10 bg-transparent">
              <tr v-for="course in details.lists.courses" :key="course.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                  {{ course.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">
                  {{ course.teacher_name }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </DashboardLayout>
</template>
