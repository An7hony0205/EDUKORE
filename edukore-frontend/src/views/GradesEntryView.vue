<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAcademicStore } from '../stores/academic'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const route = useRoute()
const router = useRouter()
const academicStore = useAcademicStore()

const evaluationId = route.params.id
const students = ref([])
const gradesData = ref({})

const loadStudentsAndGrades = async () => {
  try {
    // In a real app we might fetch students linked to this evaluation,
    // and then fetch their existing grades.
    const enrolledStudents = await academicStore.fetchEvaluationStudents(evaluationId)
    students.value = enrolledStudents
    
    // Fetch existing grades
    const existingGrades = await academicStore.fetchGrades(evaluationId)
    
    // Initialize form data
    students.value.forEach(student => {
      const record = existingGrades?.find(g => g.student_id === student.id)
      gradesData.value[student.id] = {
        score: record ? record.score : null,
        feedback: record ? record.feedback : ''
      }
    })
  } catch (err) {
    console.error('Failed to load students and grades:', err)
  }
}

const saveGrades = async () => {
  const records = students.value.map(student => ({
    student_id: student.id,
    score: gradesData.value[student.id].score,
    feedback: gradesData.value[student.id].feedback
  })).filter(r => r.score !== null && r.score !== '') // Only save provided grades

  const payload = {
    evaluation_id: evaluationId,
    records
  }

  try {
    await academicStore.saveGradesBulk(payload)
    alert('Calificaciones guardadas correctamente.')
  } catch (err) {
    console.error('Failed to save grades:', err)
    alert('Error al guardar las calificaciones.')
  }
}

onMounted(() => {
  loadStudentsAndGrades()
})
</script>

<template>
  <DashboardLayout>
    <div class="p-8 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Registro de Calificaciones</h2>
          <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">Ingresa las notas y comentarios para esta evaluación</p>
        </div>
        <div class="flex gap-3">
          <button 
            @click="router.back()"
            class="px-4 py-2 text-sm rounded-lg font-medium bg-slate-50 dark:bg-slate-50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-300 hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700 backdrop-blur-sm"
          >
            Volver
          </button>
          <button 
            @click="saveGrades"
            class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 hover:bg-indigo-700 text-slate-900 dark:text-white font-medium py-2 px-5 rounded-lg transition-colors flex items-center gap-2 shadow-lg shadow-indigo-500/20"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
            Guardar Calificaciones
          </button>
        </div>
      </div>

      <div class="rounded-2xl border bg-slate-900/50 backdrop-blur-xl" style="border-color: rgba(255,255,255,0.08);">
        <div v-if="academicStore.loading" class="p-8 text-center text-slate-500 dark:text-slate-400">
          <div class="animate-pulse flex flex-col items-center">
            <div class="h-6 w-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin mb-3"></div>
            Cargando datos...
          </div>
        </div>
        
        <div v-else-if="students.length === 0" class="p-8 text-center text-slate-500 dark:text-slate-400">
          No hay estudiantes para esta evaluación.
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
            <thead class="bg-slate-50 dark:bg-slate-800/40 text-xs uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700/50">
              <tr>
                <th scope="col" class="px-6 py-4 font-semibold">Estudiante</th>
                <th scope="col" class="px-6 py-4 font-semibold w-40">Nota</th>
                <th scope="col" class="px-6 py-4 font-semibold">Comentario (Opcional)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
              <tr v-for="student in students" :key="student.id" class="hover:bg-white/[0.02] transition-colors">
                <td class="px-6 py-4 font-medium text-slate-200">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-white dark:bg-slate-900 flex items-center justify-center text-xs font-bold text-indigo-400 border border-slate-200 dark:border-slate-700">
                      {{ student.first_name.charAt(0) }}{{ student.last_name.charAt(0) }}
                    </div>
                    {{ student.first_name }} {{ student.last_name }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <input 
                    type="number" 
                    min="0" 
                    max="100" 
                    v-model="gradesData[student.id].score"
                    class="w-full bg-slate-950/50 border border-slate-200 dark:border-slate-700/80 text-slate-900 dark:text-white text-sm rounded-lg focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 block px-3 py-2 transition-all"
                    placeholder="0-20"
                  />
                </td>
                <td class="px-6 py-4">
                  <input 
                    type="text" 
                    v-model="gradesData[student.id].feedback"
                    class="w-full bg-slate-950/50 border border-slate-200 dark:border-slate-700/80 text-slate-900 dark:text-white text-sm rounded-lg focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 block px-3 py-2 transition-all"
                    placeholder="Añadir comentario..."
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
