<script setup>
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../../api/axios'
import GradebookTable from '../../components/Academic/GradebookTable.vue'
import AttendanceTracker from '../../components/Academic/AttendanceTracker.vue'

const route = useRoute()
const courseId = route.params.id

const loading = ref(true)
const activeTab = ref('students')

const assignment = ref(null)
const evaluations = ref([])

const fetchCourseData = async () => {
  try {
    const res = await api.get(`/course-assignments/${courseId}/gradebook`)
    assignment.value = res.data.assignment
    evaluations.value = res.data.evaluations
  } catch (error) {
    console.error("Error loading course data", error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCourseData()
})
</script>

<template>
  <DashboardLayout>
  <div class="h-full flex flex-col space-y-6">
    <!-- Header -->
    <div v-if="!loading && assignment" class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold text-slate-900 dark:text-white">{{ assignment.course?.name }}</h2>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          {{ assignment.section?.grade_level?.level?.name }} &bull; 
          {{ assignment.section?.grade_level?.name }} &bull; 
          SecciÃ³n {{ assignment.section?.name }}
        </p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-800 overflow-x-auto">
      <nav class="-mb-px flex space-x-8">
        <button 
          @click="activeTab = 'students'"
          :class="[
            activeTab === 'students' 
              ? 'border-primary-500 text-primary-400' 
              : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:text-slate-300 hover:border-slate-300',
            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Estudiantes
        </button>
        <button 
          @click="activeTab = 'attendance'"
          :class="[
            activeTab === 'attendance' 
              ? 'border-primary-500 text-primary-400' 
              : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:text-slate-300 hover:border-slate-300',
            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Asistencia
        </button>
        <button 
          @click="activeTab = 'gradebook'"
          :class="[
            activeTab === 'gradebook' 
              ? 'border-primary-500 text-primary-400' 
              : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:text-slate-300 hover:border-slate-300',
            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Evaluaciones y Calificaciones
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="flex-grow">
      <div v-if="loading" class="flex justify-center p-12">
        <div class="w-8 h-8 rounded-full border-4 border-primary-500 border-t-transparent animate-spin"></div>
      </div>
      
      <div v-else-if="activeTab === 'students'" class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl p-6 overflow-hidden flex flex-col h-full">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Lista de Estudiantes ({{ assignment.section?.enrollments?.length || 0 }})</h3>
        <div class="overflow-y-auto">
          <table class="min-w-full divide-y divide-brand-border">
            <thead class="bg-white/5">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nombre</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">NÂº MatrÃ­cula</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-brand-border">
              <tr v-for="enrollment in assignment.section?.enrollments" :key="enrollment.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-200">
                  <div class="font-medium text-slate-900 dark:text-white">{{ enrollment.student?.user?.name }}</div>
                  <div class="text-slate-500 text-xs">{{ enrollment.student?.user?.email }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                  {{ enrollment.student?.enrollment_number || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-500/10 text-emerald-400">
                    {{ enrollment.status || 'Activo' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else-if="activeTab === 'gradebook'" class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl p-6 overflow-hidden flex flex-col h-full">
        <GradebookTable 
          :assignment="assignment" 
          :evaluations="evaluations"
          @refresh="fetchCourseData"
        />
      </div>

      <div v-else-if="activeTab === 'attendance'" class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl p-6 overflow-hidden flex flex-col h-full">
        <AttendanceTracker :assignment="assignment" />
      </div>
    </div>
  </div>
  </DashboardLayout>
</template>
