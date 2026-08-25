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

const gradingSystem     = ref('competency') // 'competency' | 'numeric'
const rubrics           = ref([]) // Para numeric
const competencies      = ref([]) // Para competency
const studentsStructure = ref([])

const activeTabId       = ref('consolidado') 
const activeActivityId  = ref('')

const activityData      = ref(null) 
const loading           = ref(false)
const saving            = ref(false)
const saved             = ref(false)

const scoreInputs       = ref([]) 

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

const currentRubric = computed(() => {
  return rubrics.value.find(r => r.id === activeTabId.value)
})

const currentCompetency = computed(() => {
  return competencies.value.find(c => c.id === activeTabId.value)
})

// ── Watchers ──────────────────────────────────────────────────────────────────
watch(selectedLevelId, () => { selectedGradeId.value = ''; selectedSectionId.value = ''; selectedAssignmentId.value = '' })
watch(selectedGradeId, () => { selectedSectionId.value = ''; selectedAssignmentId.value = '' })
watch(selectedSectionId, () => { 
  selectedAssignmentId.value = ''
  if (selectedSectionId.value) fetchAssignments() 
})
watch([selectedTermId, selectedAssignmentId], () => {
  if (selectedTermId.value && selectedAssignmentId.value) {
    activeTabId.value = 'consolidado'
    activeActivityId.value = ''
    fetchStructure()
  }
})

watch(activeTabId, (newVal) => {
  if (newVal === 'consolidado') {
    activeActivityId.value = ''
    fetchStructure()
  } else {
    if (gradingSystem.value === 'numeric') {
      const rubric = rubrics.value.find(r => r.id === newVal)
      if (rubric && rubric.activities && rubric.activities.length > 0) {
        selectActivity(rubric.activities[0].id)
      } else {
        activeActivityId.value = ''
        activityData.value = null
      }
    }
  }
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
    console.error("Error cargando datos iniciales:", error)
  }
})

const fetchAssignments = async () => {
  try {
    const res = await api.get('/course-assignments', { params: { section_id: selectedSectionId.value } })
    assignments.value = res.data?.data ?? res.data ?? []
  } catch (e) { console.error('Error cargando cursos:', e) }
}

const fetchStructure = async () => {
  loading.value = true
  try {
    const res = await api.get('/grades/structure', {
      params: { course_assignment_id: selectedAssignmentId.value, term_id: selectedTermId.value }
    })
    
    gradingSystem.value = res.data?.grading_system || 'competency'
    studentsStructure.value = res.data?.students || []

    if (gradingSystem.value === 'numeric') {
      rubrics.value = res.data?.rubrics || []
    } else {
      competencies.value = res.data?.competencies || []
    }
  } catch (e) {
    console.error('Error cargando estructura de notas:', e)
  } finally {
    loading.value = false
  }
}

// ── NUMERIC: Actividades ──────────────────────────────────────────────────────
const selectActivity = async (activityId) => {
  activeActivityId.value = activityId
  saved.value = false
  loading.value = true
  scoreInputs.value = []
  try {
    const res = await api.get(`/grades/activity/${activityId}`)
    activityData.value = {
      activity: res.data?.activity || {},
      students: res.data?.students || []
    }
  } catch (e) {
    console.error('Error cargando actividad:', e)
  } finally {
    loading.value = false
  }
}

const addActivity = async (rubricId) => {
  const name = prompt('Nombre de la nueva actividad:')
  if (!name || !name.trim()) return
  try {
    await api.post(`/grades/rubrics/${rubricId}/activities`, { name })
    await fetchStructure()
    const updatedRubric = rubrics.value.find(r => r.id === rubricId)
    if (updatedRubric && updatedRubric.activities.length > 0) {
      selectActivity(updatedRubric.activities[updatedRubric.activities.length - 1].id)
    }
  } catch (e) {
    alert('Error al crear la actividad: ' + (e.response?.data?.message || e.message))
  }
}

const onKeyDown = (e, index) => {
  if (e.key === 'Enter' || e.key === 'ArrowDown') {
    e.preventDefault()
    const nextInput = scoreInputs.value[index + 1]
    if (nextInput) { nextInput.focus(); nextInput.select() }
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    const prevInput = scoreInputs.value[index - 1]
    if (prevInput) { prevInput.focus(); prevInput.select() }
  }
}

const saveActivityGrades = async () => {
  if (!activityData.value) return
  saving.value = true
  saved.value = false
  try {
    const grades = activityData.value.students.map(s => ({ student_id: s.id, score: s.score }))
    await api.post(`/grades/activity/${activeActivityId.value}`, { grades })
    saved.value = true
    await fetchStructure()
  } catch (e) {
    alert('Error al guardar calificaciones: ' + (e.response?.data?.message || e.message))
  } finally {
    saving.value = false
  }
}

// ── COMPETENCY: Calificación Literal ──────────────────────────────────────────
const setLiteral = (student, compId, literal) => {
  if (!student.evaluations) student.evaluations = {}
  if (!student.evaluations[compId]) student.evaluations[compId] = { score_literal: null, descriptive_conclusion: null }
  
  if (student.evaluations[compId].score_literal === literal) {
    student.evaluations[compId].score_literal = null // Deselect if click again
  } else {
    student.evaluations[compId].score_literal = literal
  }
  saved.value = false
}

const saveCompetencyGrades = async () => {
  saving.value = true
  saved.value = false
  try {
    const evaluations = []
    studentsStructure.value.forEach(s => {
      const compId = activeTabId.value
      if (s.evaluations && s.evaluations[compId]) {
        evaluations.push({
          student_id: s.id,
          competency_id: compId,
          score_literal: s.evaluations[compId].score_literal,
          descriptive_conclusion: s.evaluations[compId].descriptive_conclusion
        })
      }
    })

    await api.post(`/grades/competency-sync`, { 
      term_id: selectedTermId.value,
      evaluations 
    })
    saved.value = true
  } catch (e) {
    alert('Error al guardar competencias: ' + (e.response?.data?.message || e.message))
  } finally {
    saving.value = false
  }
}

const downloadReport = async (studentId) => {
  try {
    // Petición JSON estándar (IDM no la intercepta)
    const response = await api.get(`/reports/students/${studentId}/progress-report`);

    if (!response.data.success || !response.data.pdf_base64) {
      throw new Error(response.data.error || 'No se pudo generar el documento');
    }

    // Decodificar Base64 a binario
    const byteCharacters = atob(response.data.pdf_base64);
    const byteNumbers = new Array(byteCharacters.length);
    for (let i = 0; i < byteCharacters.length; i++) {
      byteNumbers[i] = byteCharacters.charCodeAt(i);
    }
    const byteArray = new Uint8Array(byteNumbers);
    const blob = new Blob([byteArray], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);

    // Abrir en nueva pestaña o descargar
    const newWindow = window.open(url, '_blank');
    if (!newWindow) {
      const link = document.createElement('a');
      link.href = url;
      link.download = response.data.filename || `Libreta_${studentId}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    setTimeout(() => window.URL.revokeObjectURL(url), 10000);
  } catch (error) {
    console.error('Error detallado descargando reporte:', error);
    alert('Error al generar la libreta oficial: ' + (error.response?.data?.error || error.message));
  }
};

// ── Helpers Visuales ──────────────────────────────────────────────────────────
const gradeColor = (val) => {
  if (val === null || val === undefined || val === '') return 'text-slate-900 dark:text-white'
  return parseFloat(val) < 11 
    ? 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/10' 
    : 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/10'
}

const getRubricAverage = (student, rubricId) => {
  const avgObj = student.averages?.find(a => a.criterion_id === rubricId)
  return avgObj?.average
}

const literalColor = (literal, isSelected) => {
  if (!isSelected) return 'bg-slate-100 text-slate-500 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700'
  switch (literal) {
    case 'AD': return 'bg-blue-600 text-white shadow-sm ring-2 ring-blue-500/30'
    case 'A': return 'bg-green-600 text-white shadow-sm ring-2 ring-green-500/30'
    case 'B': return 'bg-yellow-500 text-white shadow-sm ring-2 ring-yellow-500/30'
    case 'C': return 'bg-red-600 text-white shadow-sm ring-2 ring-red-500/30'
    default: return ''
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
            Calificaciones
          </h2>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Registro de evaluaciones por periodo y curso.</p>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Periodo</label>
            <select v-model="selectedTermId" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">-- Periodo --</option>
              <option v-for="t in terms" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Nivel</label>
            <select v-model="selectedLevelId" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">-- Nivel --</option>
              <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Grado</label>
            <select v-model="selectedGradeId" :disabled="!selectedLevelId" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-40">
              <option value="">-- Grado --</option>
              <option v-for="g in filteredGrades" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 uppercase mb-1">Sección</label>
            <select v-model="selectedSectionId" :disabled="!selectedGradeId" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-40">
              <option value="">-- Sección --</option>
              <option v-for="s in filteredSections" :key="s.id" :value="s.id">Sección {{ s.name }}</option>
            </select>
          </div>
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
        <p class="font-medium">Selecciona el periodo, nivel, grado, sección y curso para comenzar a calificar.</p>
      </div>

      <!-- Main Grading Interface -->
      <div v-else class="flex flex-col gap-4">
        
        <!-- Tabs -->
        <div class="flex overflow-x-auto hide-scrollbar gap-2 pb-1">
          <button 
            @click="activeTabId = 'consolidado'"
            :class="['px-5 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors', activeTabId === 'consolidado' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700']"
          >
            Consolidado General
          </button>
          
          <template v-if="gradingSystem === 'numeric'">
            <button 
              v-for="rubric in rubrics" :key="rubric.id"
              @click="activeTabId = rubric.id"
              :class="['px-5 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors', activeTabId === rubric.id ? 'bg-indigo-600 text-white shadow-md' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700']"
            >
              {{ rubric.name }} <span class="opacity-70 text-xs ml-1">({{ (rubric.weight * 100) }}%)</span>
            </button>
          </template>

          <template v-if="gradingSystem === 'competency'">
            <button 
              v-for="comp in competencies" :key="comp.id"
              @click="activeTabId = comp.id"
              :class="['px-5 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap transition-colors', activeTabId === comp.id ? 'bg-indigo-600 text-white shadow-md' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700']"
            >
              {{ comp.name }}
            </button>
          </template>
        </div>

        <div v-if="loading && activeTabId === 'consolidado'" class="bg-white dark:bg-slate-800 rounded-2xl p-10 flex justify-center border border-slate-200 dark:border-slate-700">
          <span class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></span>
        </div>

        <!-- ───────────────────────────────────────────────────────── -->
        <!-- VISTA: CONSOLIDADO -->
        <!-- ───────────────────────────────────────────────────────── -->
        <div v-else-if="activeTabId === 'consolidado'" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
            <h3 class="font-bold text-slate-800 dark:text-slate-100">Matriz de Resultados</h3>
            <p class="text-xs text-slate-500 mt-1">Resumen del progreso del estudiante en este periodo.</p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="bg-slate-100 dark:bg-slate-700/50 border-b-2 border-slate-200 dark:border-slate-600">
                  <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300 w-10">#</th>
                  <th class="px-4 py-3 text-left font-semibold text-slate-600 dark:text-slate-300">Estudiante</th>
                  
                  <template v-if="gradingSystem === 'numeric'">
                    <th v-for="rubric in rubrics" :key="'h-' + rubric.id" class="px-3 py-3 text-center font-semibold text-slate-600 dark:text-slate-300 min-w-[120px] border-l border-slate-200 dark:border-slate-700">
                      {{ rubric.name }}
                    </th>
                    <th class="px-4 py-3 text-center font-bold text-slate-900 dark:text-white bg-slate-200 dark:bg-slate-600/50 border-l border-slate-300 dark:border-slate-600 min-w-[120px]">
                      PROM. FINAL
                    </th>
                  </template>
                  
                  <template v-if="gradingSystem === 'competency'">
                    <th v-for="comp in competencies" :key="'hc-' + comp.id" class="px-3 py-3 text-center font-semibold text-slate-600 dark:text-slate-300 min-w-[120px] border-l border-slate-200 dark:border-slate-700">
                      {{ comp.name }}
                    </th>
                  </template>
                  
                  <th class="px-4 py-3 text-center font-semibold text-slate-600 dark:text-slate-300 min-w-[80px] border-l border-slate-200 dark:border-slate-700">Acciones</th>

                </tr>
              </thead>
              <tbody>
                <tr v-if="studentsStructure.length === 0">
                  <td :colspan="10" class="px-6 py-8 text-center text-slate-400">No hay estudiantes matriculados en esta sección.</td>
                </tr>
                <tr v-for="(student, idx) in studentsStructure" :key="student.id" class="border-b border-slate-100 dark:border-slate-700/60 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                  <td class="px-4 py-3 text-slate-400 text-xs text-center">{{ idx + 1 }}</td>
                  <td class="px-4 py-3">
                    <p class="font-medium text-slate-900 dark:text-white">{{ student.name }}</p>
                    <p class="text-[10px] text-slate-400 font-mono">{{ student.enrollment_number ?? 'EST-'+student.id.substring(0,6) }}</p>
                  </td>
                  
                  <!-- Numeric columns -->
                  <template v-if="gradingSystem === 'numeric'">
                    <td v-for="rubric in rubrics" :key="rubric.id" :class="['px-3 py-3 text-center font-semibold border-l border-slate-100 dark:border-slate-700', gradeColor(getRubricAverage(student, rubric.id))]">
                      {{ getRubricAverage(student, rubric.id) ?? '--' }}
                    </td>
                    <td :class="['px-4 py-3 text-center font-bold text-lg border-l border-slate-200 dark:border-slate-700', gradeColor(student.final_average)]">
                      {{ student.final_average ?? '--' }}
                    </td>
                  </template>

                  <!-- Competency columns -->
                  <template v-if="gradingSystem === 'competency'">
                    <td v-for="comp in competencies" :key="comp.id" class="px-3 py-3 text-center font-bold border-l border-slate-100 dark:border-slate-700">
                      <span v-if="student.evaluations && student.evaluations[comp.id]?.score_literal" :class="['inline-block w-8 h-8 leading-8 text-center rounded-full text-xs shadow-sm ring-1 ring-inset', student.evaluations[comp.id].score_literal === 'AD' ? 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-900/30 dark:text-blue-400' : student.evaluations[comp.id].score_literal === 'A' ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400' : student.evaluations[comp.id].score_literal === 'B' ? 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400']">
                        {{ student.evaluations[comp.id].score_literal }}
                      </span>
                      <span v-else class="text-slate-300">-</span>
                    </td>
                  </template>

                  <td class="px-4 py-3 text-center border-l border-slate-200 dark:border-slate-700">
                    <button 
                      type="button" 
                      @click.stop.prevent="downloadReport(student.id)" 
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium rounded-lg transition-colors shadow-sm cursor-pointer"
                      title="Generar Libreta Oficial"
                    >
                      <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                      <span>Libreta</span>
                    </button>
                  </td>

                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ───────────────────────────────────────────────────────── -->
        <!-- VISTA: NUMERIC MODE (Actividades) -->
        <!-- ───────────────────────────────────────────────────────── -->
        <div v-else-if="gradingSystem === 'numeric'" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm flex flex-col">
          <!-- Actividades Pills -->
          <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300 mr-2">Actividades:</span>
            
            <button
              v-for="activity in currentRubric?.activities" :key="activity.id"
              @click="selectActivity(activity.id)"
              :class="['px-4 py-1.5 rounded-full text-sm font-medium transition-colors border', activeActivityId === activity.id ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-700' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700']"
            >
              {{ activity.name }}
            </button>

            <button @click="addActivity(currentRubric.id)" class="px-3 py-1.5 rounded-full border border-dashed border-slate-300 dark:border-slate-600 text-slate-500 dark:text-slate-400 text-sm font-medium hover:text-indigo-600 hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Nueva Actividad
            </button>
          </div>

          <div v-if="loading" class="p-10 flex justify-center">
            <span class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></span>
          </div>

          <div v-else-if="!activeActivityId" class="p-14 text-center text-slate-400">
            <p class="font-medium">Selecciona o crea una actividad para comenzar a calificar.</p>
          </div>

          <!-- Tabla de Calificación Rápida -->
          <div v-else-if="activityData" class="flex flex-col">
            <div class="px-5 py-3 flex items-center justify-between border-b border-slate-100 dark:border-slate-700">
              <p class="text-sm text-slate-500 dark:text-slate-400">Calificando: <strong class="text-slate-800 dark:text-slate-200">{{ activityData.activity.name }}</strong></p>
              
              <button @click="saveActivityGrades" :disabled="saving" class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-sm disabled:opacity-50">
                <span v-if="saving" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                <span v-else-if="saved">✓ Guardado</span>
                <span v-else>Guardar Notas</span>
              </button>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-sm border-collapse">
                <thead>
                  <tr class="bg-slate-50 dark:bg-slate-700/30 border-b border-slate-200 dark:border-slate-700">
                    <th class="px-4 py-3 text-left font-semibold text-slate-500 w-10">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-500">Estudiante</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 w-48">Nota (0-20)</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-500 w-32">Estado</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="activityData.students.length === 0">
                    <td colspan="4" class="px-6 py-8 text-center text-slate-400">No hay estudiantes.</td>
                  </tr>
                  <tr v-for="(student, idx) in activityData.students" :key="student.id" class="border-b border-slate-100 dark:border-slate-700/60 hover:bg-slate-50/80 dark:hover:bg-slate-800/80 transition-colors">
                    <td class="px-4 py-3 text-slate-400 text-xs text-center">{{ idx + 1 }}</td>
                    <td class="px-4 py-3 flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-500 dark:text-slate-400 shrink-0">
                        {{ student.name.substring(0,2).toUpperCase() }}
                      </div>
                      <div>
                        <p class="font-medium text-slate-900 dark:text-white">{{ student.name }}</p>
                        <p class="text-[10px] text-slate-400 font-mono">{{ student.enrollment_number || student.id.substring(0,8) }}</p>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                      <input 
                        v-model="student.score"
                        :ref="el => { scoreInputs[idx] = el }"
                        @keydown="e => onKeyDown(e, idx)"
                        type="number" step="0.1" min="0" max="20"
                        placeholder="--"
                        @input="saved = false"
                        :class="[
                          'w-20 text-center font-bold text-lg rounded-xl px-2 py-1.5 border hover:border-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 outline-none transition-all shadow-inner',
                          gradeColor(student.score),
                          !student.score && student.score !== 0 ? 'border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900' : 'border-transparent'
                        ]"
                      >
                    </td>
                    <td class="px-4 py-3 text-center">
                      <span v-if="student.score !== null && student.score !== ''" :class="['px-2.5 py-1 rounded-md text-xs font-semibold', parseFloat(student.score) >= 11 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400']">
                        {{ parseFloat(student.score) >= 11 ? 'Aprobado' : 'Desaprobado' }}
                      </span>
                      <span v-else class="text-xs text-slate-400">-</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ───────────────────────────────────────────────────────── -->
        <!-- VISTA: COMPETENCY MODE -->
        <!-- ───────────────────────────────────────────────────────── -->
        <div v-else-if="gradingSystem === 'competency'" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm flex flex-col">
          <div class="px-5 py-3 flex items-center justify-between border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
            <p class="text-sm text-slate-500 dark:text-slate-400">Evaluando Competencia: <strong class="text-slate-800 dark:text-slate-200">{{ currentCompetency?.name }}</strong></p>
            
            <button @click="saveCompetencyGrades" :disabled="saving" class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-sm disabled:opacity-50">
              <span v-if="saving" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
              <span v-else-if="saved">✓ Guardado</span>
              <span v-else>Guardar Evaluación Literal</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="bg-slate-50 dark:bg-slate-700/30 border-b border-slate-200 dark:border-slate-700">
                  <th class="px-4 py-3 text-left font-semibold text-slate-500 w-10">#</th>
                  <th class="px-4 py-3 text-left font-semibold text-slate-500">Estudiante</th>
                  <th class="px-4 py-3 text-center font-semibold text-slate-500 w-72">Nivel de Logro</th>
                  <th class="px-4 py-3 text-left font-semibold text-slate-500">Conclusión Descriptiva</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="studentsStructure.length === 0">
                  <td colspan="4" class="px-6 py-8 text-center text-slate-400">No hay estudiantes matriculados en esta sección.</td>
                </tr>
                <tr v-for="(student, idx) in studentsStructure" :key="student.id" class="border-b border-slate-100 dark:border-slate-700/60 hover:bg-slate-50/80 dark:hover:bg-slate-800/80 transition-colors">
                  <td class="px-4 py-3 text-slate-400 text-xs text-center">{{ idx + 1 }}</td>
                  <td class="px-4 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-500 dark:text-slate-400 shrink-0">
                      {{ student.name.substring(0,2).toUpperCase() }}
                    </div>
                    <div>
                      <p class="font-medium text-slate-900 dark:text-white">{{ student.name }}</p>
                      <p class="text-[10px] text-slate-400 font-mono">{{ student.enrollment_number || student.id.substring(0,8) }}</p>
                    </div>
                  </td>
                  
                  <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1.5" v-if="student.evaluations && student.evaluations[activeTabId]">
                      <button @click="setLiteral(student, activeTabId, 'AD')" :class="['w-10 h-10 rounded-full font-bold transition-all', literalColor('AD', student.evaluations[activeTabId]?.score_literal === 'AD')]">AD</button>
                      <button @click="setLiteral(student, activeTabId, 'A')" :class="['w-10 h-10 rounded-full font-bold transition-all', literalColor('A', student.evaluations[activeTabId]?.score_literal === 'A')]">A</button>
                      <button @click="setLiteral(student, activeTabId, 'B')" :class="['w-10 h-10 rounded-full font-bold transition-all', literalColor('B', student.evaluations[activeTabId]?.score_literal === 'B')]">B</button>
                      <button @click="setLiteral(student, activeTabId, 'C')" :class="['w-10 h-10 rounded-full font-bold transition-all', literalColor('C', student.evaluations[activeTabId]?.score_literal === 'C')]">C</button>
                    </div>
                    <div class="flex items-center justify-center gap-1.5" v-else>
                      <button @click="setLiteral(student, activeTabId, 'AD')" :class="['w-10 h-10 rounded-full font-bold transition-all', literalColor('AD', false)]">AD</button>
                      <button @click="setLiteral(student, activeTabId, 'A')" :class="['w-10 h-10 rounded-full font-bold transition-all', literalColor('A', false)]">A</button>
                      <button @click="setLiteral(student, activeTabId, 'B')" :class="['w-10 h-10 rounded-full font-bold transition-all', literalColor('B', false)]">B</button>
                      <button @click="setLiteral(student, activeTabId, 'C')" :class="['w-10 h-10 rounded-full font-bold transition-all', literalColor('C', false)]">C</button>
                    </div>
                  </td>
                  
                  <td class="px-4 py-3">
                    <textarea 
                      v-if="student.evaluations && student.evaluations[activeTabId]"
                      v-model="student.evaluations[activeTabId].descriptive_conclusion"
                      @input="saved = false"
                      placeholder="Escribe la conclusión descriptiva..."
                      class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500 resize-y min-h-[42px]"
                      rows="1"
                    ></textarea>
                  </td>

                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

    </div>
  </DashboardLayout>
</template>

<style scoped>
input[type=number]::-webkit-inner-spin-button, 
input[type=number]::-webkit-outer-spin-button { 
  -webkit-appearance: none; 
  margin: 0; 
}
input[type=number] {
  -moz-appearance: textfield;
}
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
