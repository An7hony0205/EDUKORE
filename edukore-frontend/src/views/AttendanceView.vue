<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAcademicStore } from '../stores/academic'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const route = useRoute()
const router = useRouter()
const academicStore = useAcademicStore()

const courseAssignmentId = route.params.id
const attendanceDate = ref(new Date().toISOString().split('T')[0])
const students = ref([])
const attendanceData = ref({})

const loadEnrollments = async () => {
  try {
    const enrollments = await academicStore.fetchCourseEnrollments(courseAssignmentId)
    students.value = enrollments.map(e => e.student)
    
    // Initialize attendance data structure
    students.value.forEach(student => {
      attendanceData.value[student.id] = 'Presente'
    })
  } catch (err) {
    console.error('Failed to load enrollments:', err)
  }
}

const loadAttendance = async () => {
  try {
    const existing = await academicStore.fetchAttendance(courseAssignmentId, attendanceDate.value)
    if (existing && existing.length > 0) {
      existing.forEach(record => {
        attendanceData.value[record.student_id] = record.status
      })
    } else {
      // reset to 'Presente' if no existing data for the day
      students.value.forEach(student => {
        attendanceData.value[student.id] = 'Presente'
      })
    }
  } catch (err) {
    console.error('Failed to load existing attendance:', err)
  }
}

const onDateChange = () => {
  loadAttendance()
}

const saveAttendance = async () => {
  const records = students.value.map(student => ({
    student_id: student.id,
    status: attendanceData.value[student.id]
  }))

  const payload = {
    course_assignment_id: courseAssignmentId,
    date: attendanceDate.value,
    records
  }

  try {
    await academicStore.saveAttendanceBulk(payload)
    alert('Asistencia guardada correctamente.')
  } catch (err) {
    console.error('Failed to save attendance:', err)
    alert('Error al guardar la asistencia.')
  }
}

onMounted(async () => {
  await loadEnrollments()
  await loadAttendance()
})
</script>

<template>
  <DashboardLayout>
    <div class="p-8 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Registro de Asistencia</h2>
          <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">Gestiona la asistencia de los estudiantes del curso</p>
        </div>
        <button 
          @click="router.back()"
          class="px-4 py-2 text-sm rounded-lg font-medium bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700"
        >
          Volver
        </button>
      </div>

      <div class="p-6 rounded-2xl border" style="background: rgba(255,255,255,0.02); border-color: rgba(255,255,255,0.08);">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <div class="flex items-center gap-3">
            <label for="attendanceDate" class="text-sm font-semibold text-slate-700 dark:text-slate-300">Fecha:</label>
            <input 
              id="attendanceDate"
              type="date" 
              v-model="attendanceDate"
              @change="onDateChange"
              class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 text-slate-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block px-3 py-2"
            />
          </div>
          <button 
            @click="saveAttendance"
            class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 hover:bg-indigo-700 text-slate-900 dark:text-white font-medium py-2 px-5 rounded-lg transition-colors flex items-center gap-2"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
            Guardar Asistencia
          </button>
        </div>

        <div v-if="academicStore.loading" class="text-slate-500 dark:text-slate-400 text-sm py-4">
          Cargando...
        </div>
        
        <div v-else-if="students.length === 0" class="text-slate-500 dark:text-slate-400 text-sm py-4">
          No hay estudiantes matriculados en este curso.
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
            <thead class="bg-slate-50 dark:bg-slate-50 dark:bg-slate-800/50 text-xs uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
              <tr>
                <th scope="col" class="px-4 py-3 rounded-tl-lg">Estudiante</th>
                <th scope="col" class="px-4 py-3 rounded-tr-lg">Estado de Asistencia</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-700/50">
              <tr v-for="student in students" :key="student.id" class="hover:bg-slate-50 dark:hover:bg-slate-50 dark:bg-slate-800/30 transition-colors">
                <td class="px-4 py-4 font-medium text-slate-200">
                  {{ student.first_name }} {{ student.last_name }}
                </td>
                <td class="px-4 py-4">
                  <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="radio" :name="'status_' + student.id" value="Presente" v-model="attendanceData[student.id]" class="w-4 h-4 text-indigo-600 bg-slate-700 border-slate-600 focus:ring-indigo-500 focus:ring-offset-slate-800" />
                      <span class="text-slate-700 dark:text-slate-300">Presente</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="radio" :name="'status_' + student.id" value="Tardanza" v-model="attendanceData[student.id]" class="w-4 h-4 text-amber-500 bg-slate-700 border-slate-600 focus:ring-amber-500 focus:ring-offset-slate-800" />
                      <span class="text-slate-700 dark:text-slate-300">Tardanza</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="radio" :name="'status_' + student.id" value="Ausente" v-model="attendanceData[student.id]" class="w-4 h-4 text-rose-500 bg-slate-700 border-slate-600 focus:ring-rose-500 focus:ring-offset-slate-800" />
                      <span class="text-slate-700 dark:text-slate-300">Ausente</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="radio" :name="'status_' + student.id" value="Justificado" v-model="attendanceData[student.id]" class="w-4 h-4 text-emerald-500 bg-slate-700 border-slate-600 focus:ring-emerald-500 focus:ring-offset-slate-800" />
                      <span class="text-slate-700 dark:text-slate-300">Justificado</span>
                    </label>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
