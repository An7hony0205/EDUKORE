<script setup>
import { ref, watch, onMounted } from 'vue'
import api from '../../api/axios'

const props = defineProps({
  assignment: {
    type: Object,
    required: true
  }
})

const today = new Date().toISOString().split('T')[0]
const selectedDate = ref(today)
const attendanceRecords = ref({}) // enrollment_id -> status
const saveStatus = ref('All changes saved')
let saveTimeout = null

const statuses = [
  { code: 'Presente', label: 'P', color: 'bg-emerald-500 hover:bg-emerald-600 text-white', tooltip: 'Presente' },
  { code: 'Tardanza', label: 'T', color: 'bg-amber-500 hover:bg-amber-600 text-white', tooltip: 'Tardanza' },
  { code: 'Ausente', label: 'A', color: 'bg-red-500 hover:bg-red-600 text-white', tooltip: 'Ausente' },
  { code: 'Justificado', label: 'J', color: 'bg-indigo-500 hover:bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 text-white', tooltip: 'Justificado' }
]

const loadAttendance = async () => {
  try {
    const res = await api.get('/attendance', {
      params: {
        course_assignment_id: props.assignment.id,
        date: selectedDate.value
      }
    })
    
    // reset
    const records = {}
    props.assignment.section.enrollments.forEach(e => {
        records[e.id] = null // default to null
    })
    
    // apply fetched
    res.data.forEach(record => {
      records[record.enrollment_id] = record.status
    })
    
    attendanceRecords.value = records
  } catch (error) {
    console.error("Failed to load attendance", error)
  }
}

onMounted(() => {
  loadAttendance()
})

watch(selectedDate, () => {
  loadAttendance()
})

const setStatus = (enrollmentId, status) => {
  attendanceRecords.value[enrollmentId] = status
  triggerSave()
}

const markAllPresent = () => {
  if (selectedDate.value > today) {
    alert('No se puede registrar asistencia en el futuro.')
    return
  }
  for (const enrollmentId of Object.keys(attendanceRecords.value)) {
    if (!attendanceRecords.value[enrollmentId]) {
      attendanceRecords.value[enrollmentId] = 'Presente'
    }
  }
  triggerSave()
}

const triggerSave = () => {
  saveStatus.value = 'Saving...'
  if (saveTimeout) clearTimeout(saveTimeout)
  
  saveTimeout = setTimeout(async () => {
    await saveBulk()
  }, 1000)
}

const saveBulk = async () => {
  const recordsToSave = []
  
  for (const [enrollmentId, status] of Object.entries(attendanceRecords.value)) {
    if (status) {
      recordsToSave.push({
        enrollment_id: enrollmentId,
        status: status
      })
    }
  }

  if (recordsToSave.length === 0) {
      saveStatus.value = 'All changes saved'
      return
  }

  try {
    await api.post('/attendance/bulk', {
      course_assignment_id: props.assignment.id,
      date: selectedDate.value,
      attendances: recordsToSave
    })
    saveStatus.value = 'All changes saved'
  } catch (error) {
    console.error("Failed to save attendance", error)
    saveStatus.value = 'Error saving changes'
  }
}
</script>

<template>
  <div class="flex flex-col space-y-6 h-full">
    <!-- Header Controls -->
    <div class="flex justify-between items-center">
        <div class="flex items-center gap-4">
            <label class="text-slate-500 dark:text-slate-400 font-medium">Fecha:</label>
            <input 
                type="date" 
                v-model="selectedDate" 
                :max="today"
                class="bg-brand-surface border border-brand-border rounded-lg px-4 py-2 text-white focus:ring-2 focus:ring-slate-500 dark:focus:ring-slate-400 outline-none"
            />
            <button @click="markAllPresent" class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Marcar a todos como Presentes
            </button>
        </div>
        
        <div class="text-sm text-slate-400 flex items-center gap-2">
            <div 
            class="w-2 h-2 rounded-full"
            :class="[
                saveStatus === 'Saving...' ? 'bg-amber-400 animate-pulse' : 
                saveStatus === 'Error saving changes' ? 'bg-red-500' : 'bg-emerald-500'
            ]"
            ></div>
            {{ saveStatus }}
        </div>
    </div>

    <!-- Student List -->
    <div class="flex-grow overflow-auto border border-brand-border rounded-xl bg-brand-surface">
        <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
            <thead class="text-xs text-slate-400 uppercase bg-brand-muted sticky top-0 z-10 border-b border-brand-border">
                <tr>
                    <th scope="col" class="px-6 py-4 font-medium">Estudiante</th>
                    <th scope="col" class="px-6 py-4 font-medium text-center">Estado de Asistencia</th>
                </tr>
            </thead>
            <tbody>
                <tr 
                    v-for="enrollment in assignment.section.enrollments" 
                    :key="enrollment.id"
                    class="border-b border-brand-border hover:bg-white/[0.02]"
                >
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-white">{{ enrollment.student.user.name }}</div>
                        <div class="text-xs text-slate-500">{{ enrollment.student.user.email }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center gap-2">
                            <button 
                                v-for="status in statuses" 
                                :key="status.code"
                                @click="setStatus(enrollment.id, status.code)"
                                :class="[
                                    'w-10 h-10 rounded-full font-bold transition-all border-2 flex items-center justify-center',
                                    attendanceRecords[enrollment.id] === status.code 
                                        ? status.color + ' border-transparent scale-110 shadow-lg' 
                                        : 'bg-transparent border-white/10 text-slate-500 hover:border-white/30'
                                ]"
                                :title="status.code"
                            >
                                {{ status.label }}
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="assignment.section.enrollments.length === 0">
                    <td colspan="2" class="px-6 py-8 text-center text-slate-500">
                        No hay estudiantes matriculados en esta sección.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
  </div>
</template>
