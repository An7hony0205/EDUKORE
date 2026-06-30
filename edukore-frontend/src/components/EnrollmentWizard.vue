<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../api/axios'

const props = defineProps({
  studentId: {
    type: String,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

const step = ref(1)
const loading = ref(false)
const submitting = ref(false)
const error = ref(null)

// Datos del estudiante
const student = ref(null)

// Jerarquía
const academicYears = ref([])
const selectedYear = ref(null)
const yearDetails = ref(null)

const selectedLevel = ref(null)
const selectedGrade = ref(null)

const sections = ref([])
const selectedSection = ref(null)

const statusOptions = ['preinscrito', 'pendiente_documentacion', 'matriculado']
const selectedStatus = ref('preinscrito')

const fetchStudent = async () => {
  try {
    const res = await api.get(`/students/${props.studentId}`)
    student.value = res.data.data
  } catch (err) {
    console.error(err)
  }
}

const fetchYears = async () => {
  try {
    const res = await api.get('/academic-years')
    academicYears.value = res.data.filter(y => y.status !== 'cerrado')
  } catch (err) {
    console.error(err)
  }
}

const fetchYearDetails = async (yearId) => {
  loading.value = true
  try {
    const res = await api.get(`/academic-years/${yearId}`)
    yearDetails.value = res.data
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

const fetchSections = async () => {
  loading.value = true
  try {
    const res = await api.get('/sections')
    // Filtrar secciones activas que correspondan al grado seleccionado
    sections.value = res.data.filter(s => s.grade_level_id === selectedGrade.value && s.status === 'activo')
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

const nextStep = async () => {
  if (step.value === 1) {
    step.value = 2
  } else if (step.value === 2) {
    await fetchYearDetails(selectedYear.value)
    step.value = 3
  } else if (step.value === 3) {
    step.value = 4
  } else if (step.value === 4) {
    await fetchSections()
    step.value = 5
  } else if (step.value === 5) {
    step.value = 6
  }
}

const prevStep = () => {
  if (step.value > 1) step.value--
}

const submitEnrollment = async () => {
  submitting.value = true
  error.value = null
  try {
    await api.post('/enrollments', {
      student_id: props.studentId,
      academic_year_id: selectedYear.value,
      section_id: selectedSection.value,
      status: selectedStatus.value
    })
    emit('success')
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al matricular estudiante'
  } finally {
    submitting.value = false
  }
}

// Computados para UI
const availableLevels = computed(() => {
  if (!yearDetails.value) return []
  return yearDetails.value.levels || []
})

const availableGrades = computed(() => {
  const level = availableLevels.value.find(l => l.id === selectedLevel.value)
  return level ? level.grade_levels : []
})

onMounted(async () => {
  loading.value = true
  await fetchStudent()
  await fetchYears()
  loading.value = false
})
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-slate-900 border border-white/10 rounded-2xl w-full max-w-2xl overflow-hidden shadow-2xl flex flex-col">
      
      <!-- Header -->
      <div class="p-6 border-b border-white/10 flex justify-between items-center">
        <h2 class="text-xl font-bold text-white">Asistente de Matrícula</h2>
        <button @click="$emit('close')" class="text-slate-400 hover:text-white transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>

      <!-- Steps Indicator -->
      <div class="bg-slate-800/50 px-6 py-3 border-b border-white/5 flex gap-2 overflow-x-auto">
        <div v-for="i in 6" :key="i" class="flex items-center gap-2">
          <div :class="['w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors', step >= i ? 'bg-indigo-500 text-white' : 'bg-slate-700 text-slate-400']">
            {{ i }}
          </div>
          <div v-if="i < 6" :class="['w-8 h-1 rounded', step > i ? 'bg-indigo-500' : 'bg-slate-700']"></div>
        </div>
      </div>

      <!-- Body -->
      <div class="p-6 min-h-[300px] flex-1 overflow-y-auto">
        <div v-if="loading" class="flex justify-center items-center h-full text-slate-500">Cargando...</div>
        
        <template v-else>
          <!-- Paso 1: Estudiante -->
          <div v-if="step === 1" class="space-y-4">
            <h3 class="text-lg font-medium text-white">1. Verificar Datos del Estudiante</h3>
            <div v-if="student" class="p-4 bg-white/5 rounded-xl border border-white/10">
              <div class="text-white font-medium text-lg">{{ student.user.name }}</div>
              <div class="text-slate-400 text-sm mt-1">Código: {{ student.enrollment_number }}</div>
              <div class="text-slate-400 text-sm">Estado actual: <span class="uppercase text-xs text-indigo-400 font-bold tracking-wider">{{ student.status }}</span></div>
            </div>
            <p class="text-slate-400 text-sm">Confirma que vas a matricular a este estudiante antes de continuar.</p>
          </div>

          <!-- Paso 2: Año Académico -->
          <div v-if="step === 2" class="space-y-4">
            <h3 class="text-lg font-medium text-white">2. Seleccionar Año Académico</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <button 
                v-for="year in academicYears" :key="year.id"
                @click="selectedYear = year.id"
                :class="['p-4 rounded-xl border text-left transition-all', selectedYear === year.id ? 'bg-indigo-500/20 border-indigo-500 text-white' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10']"
              >
                <div class="font-medium text-lg">{{ year.year_name }}</div>
                <div class="text-xs text-slate-400 mt-1">Inicio: {{ year.start_date }}</div>
              </button>
            </div>
            <div v-if="academicYears.length === 0" class="text-rose-400 text-sm">No hay años académicos abiertos disponibles.</div>
          </div>

          <!-- Paso 3: Nivel -->
          <div v-if="step === 3" class="space-y-4">
            <h3 class="text-lg font-medium text-white">3. Seleccionar Nivel Académico</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <button 
                v-for="level in availableLevels" :key="level.id"
                @click="selectedLevel = level.id"
                :class="['p-4 rounded-xl border text-left transition-all', selectedLevel === level.id ? 'bg-indigo-500/20 border-indigo-500 text-white' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10']"
              >
                <div class="font-medium text-lg">{{ level.name }}</div>
              </button>
            </div>
            <div v-if="availableLevels.length === 0" class="text-rose-400 text-sm">No hay niveles configurados para este año.</div>
          </div>

          <!-- Paso 4: Grado -->
          <div v-if="step === 4" class="space-y-4">
            <h3 class="text-lg font-medium text-white">4. Seleccionar Grado</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <button 
                v-for="grade in availableGrades" :key="grade.id"
                @click="selectedGrade = grade.id"
                :class="['p-4 rounded-xl border text-center transition-all', selectedGrade === grade.id ? 'bg-indigo-500/20 border-indigo-500 text-white' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10']"
              >
                <div class="font-medium">{{ grade.name }}</div>
              </button>
            </div>
            <div v-if="availableGrades.length === 0" class="text-rose-400 text-sm">No hay grados configurados para este nivel.</div>
          </div>

          <!-- Paso 5: Sección -->
          <div v-if="step === 5" class="space-y-4">
            <h3 class="text-lg font-medium text-white">5. Seleccionar Sección</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <button 
                v-for="section in sections" :key="section.id"
                @click="selectedSection = section.id"
                :class="['p-4 rounded-xl border text-center transition-all', selectedSection === section.id ? 'bg-indigo-500/20 border-indigo-500 text-white' : 'bg-white/5 border-white/10 text-slate-300 hover:bg-white/10']"
              >
                <div class="font-bold text-xl">{{ section.name }}</div>
                <div class="text-xs text-slate-400 mt-1">Capacidad: {{ section.capacity }}</div>
              </button>
            </div>
            <div v-if="sections.length === 0" class="text-rose-400 text-sm">No hay secciones activas para este grado.</div>
          </div>

          <!-- Paso 6: Confirmación -->
          <div v-if="step === 6" class="space-y-6">
            <h3 class="text-lg font-medium text-white">6. Resumen y Confirmación</h3>
            
            <div v-if="error" class="bg-rose-500/10 border border-rose-500/50 text-rose-400 p-4 rounded-lg text-sm">
              {{ error }}
            </div>

            <div class="p-6 bg-white/5 rounded-xl border border-white/10 space-y-4">
              <div class="flex justify-between border-b border-white/10 pb-2">
                <span class="text-slate-400">Estudiante:</span>
                <span class="text-white font-medium">{{ student?.user.name }}</span>
              </div>
              <div class="flex justify-between border-b border-white/10 pb-2">
                <span class="text-slate-400">Sección asignada:</span>
                <span class="text-white font-medium">
                  {{ yearDetails?.levels.find(l => l.id === selectedLevel)?.name }} - 
                  {{ availableGrades.find(g => g.id === selectedGrade)?.name }} 
                  "{{ sections.find(s => s.id === selectedSection)?.name }}"
                </span>
              </div>
              <div class="flex justify-between items-center pt-2">
                <span class="text-slate-400">Estado inicial:</span>
                <select v-model="selectedStatus" class="bg-slate-800 border border-slate-600 rounded px-2 py-1 text-sm text-white focus:outline-none focus:border-indigo-500">
                  <option v-for="st in statusOptions" :key="st" :value="st">{{ st }}</option>
                </select>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Footer Actions -->
      <div class="p-6 border-t border-white/10 flex justify-between bg-slate-800/30">
        <button @click="prevStep" :class="['px-6 py-2 rounded-lg text-sm font-medium transition-colors', step > 1 ? 'text-slate-300 hover:bg-white/5' : 'text-slate-600 cursor-not-allowed']" :disabled="step === 1 || submitting">
          Atrás
        </button>
        
        <button v-if="step < 6" @click="nextStep" :disabled="
          (step === 2 && !selectedYear) || 
          (step === 3 && !selectedLevel) || 
          (step === 4 && !selectedGrade) || 
          (step === 5 && !selectedSection)
        " class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
          Siguiente
        </button>
        
        <button v-if="step === 6" @click="submitEnrollment" :disabled="submitting" class="bg-emerald-600 hover:bg-emerald-500 text-white px-8 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50">
          {{ submitting ? 'Procesando...' : 'Confirmar Matrícula' }}
        </button>
      </div>

    </div>
  </div>
</template>
