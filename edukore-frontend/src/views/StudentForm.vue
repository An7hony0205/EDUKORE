<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const router = useRouter()
const submitting = ref(false)
const error = ref(null)

// ── Estructura académica en cascada ────────────────────────────────────────────
const academicStructure  = ref([])   // árbol completo de niveles → grados → secciones
const selectedLevelId    = ref('')
const selectedGradeId    = ref('')

const filteredGrades = computed(() => {
  const level = academicStructure.value.find(l => l.id === selectedLevelId.value)
  return level ? level.grades : []
})

const filteredSections = computed(() => {
  const grade = filteredGrades.value.find(g => g.id === selectedGradeId.value)
  return grade ? grade.sections : []
})

// Cascada: resetear grado y sección si cambia el nivel
watch(selectedLevelId, () => {
  selectedGradeId.value        = ''
  form.value.student.section_id = ''
})
// Cascada: resetear sección si cambia el grado
watch(selectedGradeId, () => {
  form.value.student.section_id = ''
})

onMounted(async () => {
  try {
    const res = await api.get('/academic-structure/summary')
    academicStructure.value = res.data.data
  } catch (e) {
    console.error('Error cargando estructura académica:', e)
  }
})

const form = ref({
  student: {
    name:            '',
    email:           '',
    document_number: '',
    date_of_birth:   '',
    section_id:      '',
    // enrollment_number se autogenera en el backend — no se envía
  },
  parents: [
    { name: '', document_number: '', email: '', phone: '', address: '', relationship: 'Padre' }
  ]
})

const addParent = () => {
  form.value.parents.push({ name: '', document_number: '', email: '', phone: '', address: '', relationship: 'Madre' })
}

const removeParent = (index) => {
  form.value.parents.splice(index, 1)
}

const submitForm = async () => {
  submitting.value = true
  error.value = null
  try {
    const res = await api.post('/students', form.value)
    router.push(`/student/${res.data.data.id}`)
  } catch (err) {
    console.error(err)
    error.value = err.response?.data?.error || err.response?.data?.message || 'Error al registrar estudiante'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <template #title>Registrar Estudiante</template>
    <div class="max-w-4xl mx-auto space-y-6">
      <div>
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
          Registro de Estudiante (Onboarding)
        </h2>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Registra al alumno y a sus apoderados en un solo paso.</p>
      </div>

      <div v-if="error" class="bg-rose-500/10 border border-rose-500/50 text-rose-400 p-4 rounded-lg text-sm">
        {{ error }}
      </div>

      <form @submit.prevent="submitForm" class="space-y-8">
        <!-- Datos del Estudiante -->
        <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl p-6">
          <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">1. Datos del Alumno</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
              <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Nombre Completo</label>
              <input v-model="form.student.name" required type="text" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 focus:outline-none">
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Correo Institucional <span class="text-slate-400">(opcional)</span></label>
              <input v-model="form.student.email" type="email" placeholder="Se generará si se deja vacío" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 focus:outline-none">
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">DNI del Alumno</label>
              <input v-model="form.student.document_number" required type="text" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 focus:outline-none">
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Fecha de Nacimiento</label>
              <input v-model="form.student.date_of_birth" required type="date" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-700 dark:text-slate-300 focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 focus:outline-none [color-scheme:dark]">
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Código de Matrícula</label>
              <input disabled type="text" placeholder="Se generará automáticamente" class="w-full bg-slate-100 border border-slate-200 dark:bg-slate-900/50 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-500 cursor-not-allowed focus:outline-none">
            </div>
          </div>

          <!-- Sección académica en cascada -->
          <div class="mt-6 border-t border-slate-200 dark:border-slate-700 pt-5">
            <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
              <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              Asignación de Aula
              <span class="text-xs font-normal text-slate-400">(opcional)</span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Nivel -->
              <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Nivel Académico</label>
                <select v-model="selectedLevelId"
                  class="w-full bg-slate-800 text-slate-100 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                  <option value="">-- Nivel --</option>
                  <option v-for="level in academicStructure" :key="level.id" :value="level.id">
                    {{ level.name }}
                  </option>
                </select>
              </div>
              <!-- Grado -->
              <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Grado</label>
                <select v-model="selectedGradeId" :disabled="!selectedLevelId"
                  class="w-full bg-slate-800 text-slate-100 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none disabled:opacity-40">
                  <option value="">-- Grado --</option>
                  <option v-for="grade in filteredGrades" :key="grade.id" :value="grade.id">
                    {{ grade.name }}
                  </option>
                </select>
              </div>
              <!-- Sección con aforo -->
              <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Sección</label>
                <select v-model="form.student.section_id" :disabled="!selectedGradeId"
                  class="w-full bg-slate-800 text-slate-100 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none disabled:opacity-40">
                  <option value="">-- Sección --</option>
                  <option v-for="section in filteredSections" :key="section.id" :value="section.id">
                    Sección {{ section.name }} ({{ section.students_count }}/{{ section.max_capacity }})
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Datos de los Apoderados -->
        <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-slate-900 dark:text-white">2. Datos de los Apoderados</h3>
            <button type="button" @click="addParent" class="text-primary-400 hover:text-primary-300 text-xs font-medium bg-primary-500/10 px-3 py-1.5 rounded-lg">+ Añadir Apoderado</button>
          </div>
          
          <div class="space-y-6">
            <div v-for="(parent, index) in form.parents" :key="index" class="p-4 bg-white/5 rounded-xl border border-slate-200 dark:border-slate-800 relative">
              <button v-if="form.parents.length > 1" type="button" @click="removeParent(index)" class="absolute top-4 right-4 text-slate-500 hover:text-rose-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Nombre Completo</label>
                  <input v-model="parent.name" required type="text" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 focus:outline-none">
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">DNI / Documento</label>
                  <input v-model="parent.document_number" required type="text" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 focus:outline-none">
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Parentesco</label>
                  <select v-model="parent.relationship" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 focus:outline-none appearance-none">
                    <option value="Padre" class="bg-brand-dark dark:bg-slate-800">Padre</option>
                    <option value="Madre" class="bg-brand-dark dark:bg-slate-800">Madre</option>
                    <option value="Tutor" class="bg-brand-dark dark:bg-slate-800">Tutor/a Legal</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Correo Electrónico <span class="text-slate-400">(opcional)</span></label>
                  <input v-model="parent.email" type="email" placeholder="Se generará si se deja vacío" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 focus:outline-none">
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Teléfono</label>
                  <input v-model="parent.phone" type="text" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 focus:outline-none">
                </div>
                <div class="md:col-span-2">
                  <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Dirección</label>
                  <input v-model="parent.address" type="text" placeholder="Ej. Av. Los Álamos 456, Lima" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 focus:outline-none">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-4">
          <button type="button" @click="router.push('/students')" class="px-6 py-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancelar</button>
          <button type="submit" :disabled="submitting" class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 px-8 py-2 rounded-lg font-medium transition-colors disabled:opacity-50">
            {{ submitting ? 'Registrando...' : 'Completar Registro' }}
          </button>
        </div>
      </form>
    </div>
  </DashboardLayout>
</template>
