<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import api from '../../api/axios'

// ── Estado ────────────────────────────────────────────────────────────────────
const terms             = ref([])
const levels            = ref([])
const assignments       = ref([])

const selectedTermId    = ref('')
const selectedLevelId   = ref('')
const selectedGradeId   = ref('')
const selectedSectionId = ref('')
const selectedAssignmentId = ref('')

const sheetData         = ref(null)
const loading           = ref(false)
const saving            = ref(false)
const saved             = ref(false)

// ── Computed Cascadas ─────────────────────────────────────────────────────────
const filteredGrades = computed(() => {
  if (!levels.value) return []
  const lvl = levels.value.find(l => l.id === selectedLevelId.value)
  return lvl?.grades || []
})
const filteredSections = computed(() => {
  if (!filteredGrades.value) return []
  const gr = filteredGrades.value.find(g => g.id === selectedGradeId.value)
  return gr?.sections || []
})

// ── Watchers ──────────────────────────────────────────────────────────────────
watch(selectedLevelId, () => { selectedGradeId.value = ''; selectedSectionId.value = ''; selectedAssignmentId.value = '' })
watch(selectedGradeId, () => { selectedSectionId.value = ''; selectedAssignmentId.value = '' })
watch(selectedSectionId, () => { 
  selectedAssignmentId.value = ''
  if (selectedSectionId.value) fetchAssignments() 
})
watch([selectedTermId, selectedAssignmentId], () => {
  if (selectedTermId.value && selectedAssignmentId.value) fetchSheet()
})

// ── Lifecycle & Fetching ──────────────────────────────────────────────────────
onMounted(async () => {
  try {
    const [termsRes, structRes] = await Promise.all([
      api.get('/academic-terms'),
      api.get('/academic-structure/summary')
    ])
    terms.value = termsRes.data?.data || []
    levels.value = structRes.data?.data || []
    if (terms.value.length > 0) {
      selectedTermId.value = terms.value[0].id
    }
  } catch (error) {
    console.error("Error cargando datos iniciales de calificaciones:", error)
  }
})

const fetchAssignments = async () => {
  try {
    const res = await api.get('/course-assignments', { params: { section_id: selectedSectionId.value } })
    assignments.value = res.data?.data ?? res.data ?? []
  } catch (e) { console.error('Error cargando cursos:', e) }
}

const fetchSheet = async () => {
  loading.value = true
  saved.value = false
  sheetData.value = null
  try {
    const res = await api.get('/grades/sheet', {
      params: { course_assignment_id: selectedAssignmentId.value, term_id: selectedTermId.value }
    })
    
    const rawCriteria = res.data?.criteria || []
    const rawStudents = res.data?.students || []

    const criteria = rawCriteria.map(c => ({ ...c }))
    
    // Map array to object for easier v-model binding: { [criterion_id]: score }
    const students = rawStudents.map(s => {
      const gradesMap = {}
      s.grades?.forEach(g => { gradesMap[g.criterion_id] = g.score })
      return { ...s, gradesMap, localAverage: s.average }
    })
    
    sheetData.value = { criteria, students }
    recalculateAverages()
  } catch (e) {
    console.error('Error cargando notas:', e)
  } finally {
    loading.value = false
  }
}

// ── Lógica de Planilla ────────────────────────────────────────────────────────
const getScore = (student, criterionId) => student.gradesMap[criterionId]
const setScore = (student, criterionId, val) => {
  // Solo numérico de 0 a 20 o vacío
  let parsed = parseFloat(val)
  if (isNaN(parsed) || val === '') {
    student.gradesMap[criterionId] = null
  } else {
    parsed = Math.max(0, Math.min(20, parsed)) // Clamp 0-20
    student.gradesMap[criterionId] = parsed
  }
  recalculateAverages()
  saved.value = false
}

const recalculateAverages = () => {
  if (!sheetData.value) return
  sheetData.value.students.forEach(s => {
    let sum = 0
    let totalWeight = 0
    sheetData.value.criteria.forEach(c => {
      const val = s.gradesMap[c.id]
      if (val !== null && val !== undefined) {
        sum += val * c.weight
        totalWeight += parseFloat(c.weight)
      }
    })
    s.localAverage = totalWeight > 0 ? (sum / totalWeight).toFixed(1) : null
  })
}

// Control visual de nota (Rojo < 11, Azul >= 11)
const gradeColor = (val) => {
  if (val === null || val === undefined || val === '') return 'text-slate-900 dark:text-white'
  return parseFloat(val) < 11 
    ? 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/10' 
    : 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/10'
}

// ── Criterios ─────────────────────────────────────────────────────────────────
const addCriterion = () => {
  if (!sheetData.value) return
  sheetData.value.criteria.push({
    id: 'new-' + Date.now(),
    name: 'Nuevo Criterio',
    weight: 1.0,
  })
  recalculateAverages()
}
const removeCriterion = (idx) => {
  if (!sheetData.value) return
  if (!confirm('¿Eliminar este criterio y todas sus notas?')) return
  const crit = sheetData.value.criteria[idx]
  sheetData.value.criteria.splice(idx, 1)
  // Limpiar notas locales de este criterio
  sheetData.value.students.forEach(s => delete s.gradesMap[crit.id])
  recalculateAverages()
  saved.value = false
}

// ── Guardar ───────────────────────────────────────────────────────────────────
const saveGrades = async () => {
  if (!sheetData.value) return
  saving.value = true
  saved.value = false
  try {
    // Aplanar notas
    const flatGrades = []
    sheetData.value.students.forEach(s => {
      Object.keys(s.gradesMap).forEach(critId => {
        flatGrades.push({
          student_id: s.id,
          criterion_id: critId,
          score: s.gradesMap[critId]
        })
      })
    })

    await api.post('/grades/bulk-sync', {
      course_assignment_id: selectedAssignmentId.value,
      term_id: selectedTermId.value,
      criteria: sheetData.value.criteria,
      grades: flatGrades
    })
    saved.value = true
    await fetchSheet() // Refetch para consolidar UUIDs nuevos de criterios
  } catch (e) {
    console.error('Error guardando:', e)
    alert('Error al guardar calificaciones: ' + (e.response?.data?.message || e.message))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-[1400px] mx-auto space-y-6">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Registro de Calificaciones
          </h2>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Planilla de notas por curso y periodo.</p>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
          <!-- Periodo -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Periodo</label>
            <select v-model="selectedTermId" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">-- Periodo --</option>
              <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <!-- Nivel -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Nivel</label>
            <select v-model="selectedLevelId" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">-- Nivel --</option>
              <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
            </select>
          </div>
          <!-- Grado -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Grado</label>
            <select v-model="selectedGradeId" :disabled="!selectedLevelId" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-40">
              <option value="">-- Grado --</option>
              <option v-for="g in filteredGrades" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
          </div>
          <!-- Sección -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Sección</label>
            <select v-model="selectedSectionId" :disabled="!selectedGradeId" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-40">
              <option value="">-- Sección --</option>
              <option v-for="s in filteredSections" :key="s.id" :value="s.id">Sección {{ s.name }}</option>
            </select>
          </div>
          <!-- Curso -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Curso Asignado</label>
            <select v-model="selectedAssignmentId" :disabled="!selectedSectionId" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-40">
              <option value="">-- Curso --</option>
              <option v-for="a in assignments" :key="a.id" :value="a.id">{{ a.course?.name }} ({{ a.teacher?.name }})</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Estado Vacío -->
      <div v-if="!selectedAssignmentId || !selectedTermId" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-14 text-center text-slate-400">
        <svg class="w-14 h-14 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p class="font-medium">Selecciona el periodo, nivel, grado, sección y curso para ver la planilla.</p>
      </div>

      <!-- Planilla -->
      <div v-else class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden flex flex-col">
        
        <div v-if="loading" class="p-10 space-y-3">
          <div v-for="i in 5" :key="i" class="h-12 rounded-xl bg-slate-100 dark:bg-slate-700 animate-pulse"></div>
        </div>

        <template v-else-if="sheetData">
          <!-- Acciones Criterios -->
          <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex flex-wrap gap-4 items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Criterios de Evaluación:</span>
              <button @click="addCriterion" class="px-3 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400 text-xs font-semibold hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">
                + Añadir Criterio
              </button>
            </div>
            <button @click="saveGrades" :disabled="saving" class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-sm disabled:opacity-50">
              <span v-if="saving" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
              <span v-else-if="saved">✓ Guardado</span>
              <span v-else>Guardar Calificaciones</span>
            </button>
          </div>

          <!-- Tabla Spreadsheet -->
          <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="bg-slate-100 dark:bg-slate-700/50 border-b-2 border-slate-200 dark:border-slate-600">
                  <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300 w-10">#</th>
                  <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap sticky left-0 bg-slate-100 dark:bg-slate-700/50 z-10 w-64 border-r border-slate-200 dark:border-slate-700">Estudiante</th>
                  
                  <!-- Criterios (Dinámicos) -->
                  <th v-for="(crit, cIdx) in sheetData.criteria" :key="crit.id" class="px-3 py-2 text-center border-r border-slate-200 dark:border-slate-700 min-w-[140px] group">
                    <div class="flex flex-col items-center gap-1 relative">
                      <input v-model="crit.name" type="text" class="w-full bg-transparent text-center font-semibold text-slate-700 dark:text-slate-200 focus:ring-1 focus:ring-indigo-500 rounded px-1 outline-none">
                      <div class="flex items-center gap-1 text-xs text-slate-500">
                        <span>Peso:</span>
                        <input v-model="crit.weight" @change="recalculateAverages" type="number" step="0.1" min="0" class="w-14 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded px-1 text-center outline-none">
                      </div>
                      <button @click="removeCriterion(cIdx)" class="absolute -top-1 -right-1 text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity bg-white dark:bg-slate-800 rounded-full w-5 h-5 flex items-center justify-center">×</button>
                    </div>
                  </th>
                  
                  <th class="px-4 py-3 text-center font-bold text-slate-900 dark:text-white bg-slate-200 dark:bg-slate-600/50 min-w-[100px]">PROMEDIO</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="sheetData.students.length === 0">
                  <td :colspan="sheetData.criteria.length + 3" class="px-6 py-8 text-center text-slate-400">No hay estudiantes matriculados en esta sección.</td>
                </tr>
                <tr v-for="(student, idx) in sheetData.students" :key="student.id" class="border-b border-slate-100 dark:border-slate-700/60 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                  <td class="px-4 py-3 text-slate-400 text-xs text-center">{{ idx + 1 }}</td>
                  
                  <!-- Estudiante Sticky -->
                  <td class="px-4 py-3 sticky left-0 bg-white dark:bg-slate-800 group-hover:bg-slate-50/50 dark:group-hover:bg-slate-800/50 border-r border-slate-100 dark:border-slate-700 z-10">
                    <p class="font-medium text-slate-900 dark:text-white truncate" :title="student.name">{{ student.name }}</p>
                    <p class="text-[10px] text-slate-400 font-mono">{{ student.enrollment_number }}</p>
                  </td>

                  <!-- Inputs de Notas -->
                  <td v-for="crit in sheetData.criteria" :key="crit.id" class="px-2 py-2 border-r border-slate-100 dark:border-slate-700 text-center">
                    <input 
                      :value="getScore(student, crit.id)"
                      @input="e => setScore(student, crit.id, e.target.value)"
                      type="number" step="1" min="0" max="20"
                      placeholder="--"
                      :class="[
                        'w-16 text-center font-semibold rounded-lg px-2 py-1.5 border border-transparent hover:border-slate-300 dark:hover:border-slate-600 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition-colors',
                        gradeColor(getScore(student, crit.id))
                      ]"
                    >
                  </td>

                  <!-- Promedio -->
                  <td :class="['px-4 py-3 text-center font-bold text-lg', gradeColor(student.localAverage)]">
                    {{ student.localAverage ?? '--' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </div>

    </div>
  </DashboardLayout>
</template>

<style scoped>
/* Ocultar flechas del input number para la planilla */
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>
