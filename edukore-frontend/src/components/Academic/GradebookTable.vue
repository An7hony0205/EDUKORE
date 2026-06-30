<script setup>
import { ref, computed, watch } from 'vue'
import api from '@/api/axios'

const props = defineProps({
  assignment: {
    type: Object,
    required: true
  },
  evaluations: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['refresh'])

// Create a reactive state for grades: gradesState[enrollment_id][evaluation_id] = score
const gradesState = ref({})
const saveStatus = ref('All changes saved') // 'Saving...', 'Saved', 'Error'
let saveTimeout = null

// Initialize state
const initializeGrades = () => {
  const state = {}
  props.assignment.section.enrollments.forEach(enrollment => {
    state[enrollment.id] = {}
    enrollment.grades.forEach(grade => {
      state[enrollment.id][grade.evaluation_id] = grade.score
    })
  })
  gradesState.value = state
}

initializeGrades()

// Watch for changes in props (when a new evaluation is added, etc.)
watch(() => props.assignment, () => {
  initializeGrades()
}, { deep: true })


// Calculate average per student
const calculateAverage = (enrollmentId) => {
  const studentGrades = gradesState.value[enrollmentId] || {}
  let totalScore = 0
  let totalWeight = 0

  props.evaluations.forEach(ev => {
    const score = parseFloat(studentGrades[ev.id])
    if (!isNaN(score)) {
      const weight = parseFloat(ev.weight) || 100
      totalScore += (score * (weight / 100))
      totalWeight += weight
    }
  })

  // Simple weighted average assuming total weight approaches 100%
  if (totalWeight === 0) return '-'
  return (totalScore / (totalWeight / 100)).toFixed(2)
}

// Auto-save logic
const onGradeChange = (enrollmentId, evaluationId, event) => {
  let val = event.target.value
  
  if (val !== '' && !isNaN(val)) {
      gradesState.value[enrollmentId][evaluationId] = parseFloat(val)
  } else {
      gradesState.value[enrollmentId][evaluationId] = null
  }

  saveStatus.value = 'Saving...'
  
  if (saveTimeout) clearTimeout(saveTimeout)
  
  saveTimeout = setTimeout(async () => {
    await saveGrade(enrollmentId, evaluationId, gradesState.value[enrollmentId][evaluationId])
  }, 1000) // 1 second debounce
}

const saveGrade = async (enrollmentId, evaluationId, score) => {
  try {
    const payload = {
      evaluation_id: evaluationId,
      grades: [
        {
          enrollment_id: enrollmentId,
          score: score
        }
      ]
    }
    
    await api.post('/grades/bulk', payload)
    saveStatus.value = 'All changes saved'
  } catch (error) {
    console.error("Failed to save grade", error)
    saveStatus.value = 'Error saving changes'
  }
}

// Publish evaluation
const publishEvaluation = async (evaluationId) => {
  if (!confirm("¿Estás seguro de que deseas publicar estas notas? Los estudiantes podrán verlas.")) return;
  
  try {
    await api.post(`/evaluations/${evaluationId}/publish`)
    emit('refresh')
  } catch (error) {
    alert("Error publicando notas")
  }
}

</script>

<template>
  <div class="flex flex-col h-full space-y-4">
    <!-- Toolbar -->
    <div class="flex justify-between items-center">
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
      <div>
        <button class="bg-primary-600 hover:bg-primary-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
          + Nueva Evaluación
        </button>
      </div>
    </div>

    <!-- Table Wrapper -->
    <div class="flex-grow overflow-auto border border-brand-border rounded-xl bg-brand-surface">
      <table class="w-full text-left text-sm text-slate-300">
        <thead class="text-xs text-slate-400 uppercase bg-brand-muted sticky top-0 z-10">
          <tr>
            <th scope="col" class="px-6 py-4 font-medium border-b border-r border-brand-border bg-brand-surface sticky left-0 z-20">
              Estudiante
            </th>
            <th 
              v-for="ev in evaluations" 
              :key="ev.id"
              scope="col" 
              class="px-4 py-3 font-medium border-b border-r border-brand-border min-w-[150px] bg-brand-surface"
            >
              <div class="flex justify-between items-start">
                <div>
                  <div class="text-white">{{ ev.title }}</div>
                  <div class="text-[10px] mt-1">{{ ev.category }} &bull; {{ ev.weight }}%</div>
                </div>
                <div v-if="ev.is_published" class="text-[10px] bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded-full">
                  Publicado
                </div>
                <button v-else @click="publishEvaluation(ev.id)" class="text-[10px] bg-slate-700 hover:bg-slate-600 text-white px-2 py-0.5 rounded-full transition-colors" title="Publicar notas">
                  Borrador
                </button>
              </div>
            </th>
            <th scope="col" class="px-6 py-4 font-bold text-white border-b border-brand-border bg-brand-surface w-32 text-center">
              Promedio
            </th>
          </tr>
        </thead>
        <tbody>
          <tr 
            v-for="enrollment in assignment.section.enrollments" 
            :key="enrollment.id"
            class="border-b border-brand-border hover:bg-white/[0.02]"
          >
            <td class="px-6 py-3 whitespace-nowrap border-r border-brand-border bg-brand-surface sticky left-0 z-10">
              <div class="font-medium text-white">{{ enrollment.student.user.name }}</div>
              <div class="text-xs text-slate-500">{{ enrollment.student.user.email }}</div>
            </td>
            
            <td 
              v-for="ev in evaluations" 
              :key="ev.id"
              class="border-r border-brand-border p-0"
            >
              <input 
                type="number" 
                step="0.01"
                min="0"
                :disabled="ev.academic_period && ev.academic_period.is_locked"
                :class="[
                  'w-full h-full min-h-[56px] px-4 py-2 bg-transparent text-white border-none focus:ring-2 focus:ring-inset focus:ring-primary-500 hover:bg-white/5 outline-none text-center',
                  (ev.academic_period && ev.academic_period.is_locked) ? 'opacity-50 cursor-not-allowed bg-red-900/10' : ''
                ]"
                :value="gradesState[enrollment.id][ev.id]"
                @input="onGradeChange(enrollment.id, ev.id, $event)"
              />
            </td>
            
            <td class="px-6 py-3 text-center font-bold text-primary-400 bg-primary-500/5">
              {{ calculateAverage(enrollment.id) }}
            </td>
          </tr>
          
          <tr v-if="assignment.section.enrollments.length === 0">
            <td :colspan="evaluations.length + 2" class="px-6 py-8 text-center text-slate-500">
              No hay estudiantes matriculados en esta sección.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
