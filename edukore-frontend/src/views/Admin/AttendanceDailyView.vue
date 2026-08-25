<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import api from '../../api/axios'

// ── Estado ────────────────────────────────────────────────────────────────────
const today        = new Date().toISOString().split('T')[0]
const selectedDate = ref(today)
const loading      = ref(false)
const saving       = ref(false)
const saved        = ref(false)

// Estructura académica en cascada
const levels           = ref([])
const selectedLevelId  = ref('')
const selectedGradeId  = ref('')
const selectedSectionId= ref('')

// Datos de la sesión de asistencia
const sectionName = ref('')
const students    = ref([])   // [{ student_id, name, enrollment_number, status, remarks }]

// ── Computed: cascada ─────────────────────────────────────────────────────────
const filteredGrades = computed(() => {
  const lvl = levels.value.find(l => l.id === selectedLevelId.value)
  return lvl ? lvl.grades : []
})

const filteredSections = computed(() => {
  const gr = filteredGrades.value.find(g => g.id === selectedGradeId.value)
  return gr ? gr.sections : []
})

// Métricas en vivo
const metrics = computed(() => ({
  present:   students.value.filter(s => s.status === 'present').length,
  late:      students.value.filter(s => s.status === 'late').length,
  absent:    students.value.filter(s => s.status === 'absent').length,
  justified: students.value.filter(s => s.status === 'justified').length,
}))

// ── Watchers cascada ──────────────────────────────────────────────────────────
watch(selectedLevelId, () => { selectedGradeId.value = ''; selectedSectionId.value = '' })
watch(selectedGradeId, () => { selectedSectionId.value = '' })
watch([selectedSectionId, selectedDate], () => {
  if (selectedSectionId.value) fetchAttendance()
})

// ── Fetch ─────────────────────────────────────────────────────────────────────
onMounted(async () => {
  try {
    const res = await api.get('/academic-structure/summary')
    levels.value = res.data.data
  } catch (e) { console.error('Error cargando estructura:', e) }
})

const fetchAttendance = async () => {
  loading.value = true
  students.value = []
  saved.value    = false
  try {
    const res = await api.get('/section-attendance', {
      params: { section_id: selectedSectionId.value, date: selectedDate.value }
    })
    sectionName.value = res.data.section.name
    students.value    = res.data.students
  } catch (e) {
    console.error('Error cargando asistencia:', e)
  } finally {
    loading.value = false
  }
}

// ── Acciones ──────────────────────────────────────────────────────────────────
const setStatus = (student, status) => { student.status = status }

const markAllPresent = () => {
  students.value.forEach(s => s.status = 'present')
}

const saveAttendance = async () => {
  saving.value = true
  saved.value  = false
  try {
    await api.post('/section-attendance/bulk', {
      section_id:  selectedSectionId.value,
      date:        selectedDate.value,
      attendances: students.value.map(s => ({
        student_id: s.student_id,
        status:     s.status,
        remarks:    s.remarks || null,
      })),
    })
    saved.value = true
    // Refetch to sync saved flag per row
    await fetchAttendance()
  } catch (e) {
    console.error('Error guardando:', e)
    alert('Error al guardar la asistencia. Por favor reintenta.')
  } finally {
    saving.value = false
  }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const STATUS_OPTIONS = [
  { key: 'present',   label: 'Presente',    ring: 'ring-emerald-500', active: 'bg-emerald-500 text-white', text: 'text-emerald-600 dark:text-emerald-400' },
  { key: 'late',      label: 'Tarde',       ring: 'ring-amber-400',   active: 'bg-amber-400 text-white',   text: 'text-amber-500 dark:text-amber-400' },
  { key: 'absent',    label: 'Falta',       ring: 'ring-red-500',     active: 'bg-red-500 text-white',     text: 'text-red-600 dark:text-red-400' },
  { key: 'justified', label: 'Justificado', ring: 'ring-indigo-400',  active: 'bg-indigo-500 text-white',  text: 'text-indigo-600 dark:text-indigo-400' },
]

const BADGE_STYLE = {
  present:   'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
  late:      'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
  absent:    'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
  justified: 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300',
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-7xl mx-auto space-y-6">

      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Registro de Asistencia
          </h2>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Selecciona una sección y fecha para registrar la asistencia diaria.</p>
        </div>
      </div>

      <!-- ── Barra de Filtros ─────────────────────────────────────────────── -->
      <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

          <!-- Fecha -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wide">Fecha</label>
            <input v-model="selectedDate" type="date"
              class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none [color-scheme:light] dark:[color-scheme:dark]">
          </div>

          <!-- Nivel -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wide">Nivel</label>
            <select v-model="selectedLevelId"
              class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
              <option value="">-- Nivel --</option>
              <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
            </select>
          </div>

          <!-- Grado -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wide">Grado</label>
            <select v-model="selectedGradeId" :disabled="!selectedLevelId"
              class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none disabled:opacity-40">
              <option value="">-- Grado --</option>
              <option v-for="g in filteredGrades" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
          </div>

          <!-- Sección -->
          <div>
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wide">Sección</label>
            <select v-model="selectedSectionId" :disabled="!selectedGradeId"
              class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none disabled:opacity-40">
              <option value="">-- Sección --</option>
              <option v-for="s in filteredSections" :key="s.id" :value="s.id">
                Sección {{ s.name }} ({{ s.students_count }}/{{ s.max_capacity }})
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- ── Tabla ────────────────────────────────────────────────────────── -->
      <template v-if="selectedSectionId">

        <!-- Sin alumnos / loading -->
        <div v-if="loading"
          class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">
          <div class="p-6 space-y-3">
            <div v-for="i in 5" :key="i" class="h-12 rounded-xl bg-slate-100 dark:bg-slate-700 animate-pulse"></div>
          </div>
        </div>

        <div v-else-if="!students.length"
          class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-12 text-center text-slate-400 dark:text-slate-500">
          <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <p class="font-medium">Sin alumnos matriculados en esta sección.</p>
        </div>

        <div v-else class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">

          <!-- Cabecera de tabla con acciones -->
          <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h3 class="font-semibold text-slate-900 dark:text-white">
                Sección {{ sectionName }} —
                <span class="text-slate-500 dark:text-slate-400 font-normal text-sm">
                  {{ new Date(selectedDate + 'T00:00:00').toLocaleDateString('es-PE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                </span>
              </h3>
              <!-- Métricas en vivo -->
              <div class="flex flex-wrap gap-2 mt-2">
                <span :class="['text-xs font-semibold px-2.5 py-0.5 rounded-full', BADGE_STYLE.present]">
                  ✓ {{ metrics.present }} Presentes
                </span>
                <span :class="['text-xs font-semibold px-2.5 py-0.5 rounded-full', BADGE_STYLE.late]">
                  ⏱ {{ metrics.late }} Tardanzas
                </span>
                <span :class="['text-xs font-semibold px-2.5 py-0.5 rounded-full', BADGE_STYLE.absent]">
                  ✗ {{ metrics.absent }} Faltas
                </span>
                <span :class="['text-xs font-semibold px-2.5 py-0.5 rounded-full', BADGE_STYLE.justified]">
                  ◇ {{ metrics.justified }} Justificados
                </span>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <button @click="markAllPresent" type="button"
                class="px-3 py-2 text-xs font-semibold rounded-xl border border-emerald-300 dark:border-emerald-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors">
                ✓ Todos Presentes
              </button>
              <button @click="saveAttendance" type="button" :disabled="saving"
                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition-colors disabled:opacity-50 flex items-center gap-2 shadow-sm">
                <span v-if="saving" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                <span v-else-if="saved">✓ Guardado</span>
                <span v-else>Guardar Asistencia</span>
              </button>
            </div>
          </div>

          <!-- Tabla -->
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-left text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-700/30 border-b border-slate-200 dark:border-slate-700">
                  <th class="px-5 py-3 font-medium w-10">#</th>
                  <th class="px-4 py-3 font-medium">Matrícula</th>
                  <th class="px-4 py-3 font-medium">Estudiante</th>
                  <th class="px-4 py-3 font-medium">Estado</th>
                  <th class="px-4 py-3 font-medium">Observación</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                <tr v-for="(student, idx) in students" :key="student.student_id"
                  class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                  <!-- # -->
                  <td class="px-5 py-3 text-slate-400 dark:text-slate-500 text-xs">{{ idx + 1 }}</td>

                  <!-- Matrícula -->
                  <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">
                    {{ student.enrollment_number || '—' }}
                  </td>

                  <!-- Nombre -->
                  <td class="px-4 py-3">
                    <span class="font-medium text-slate-900 dark:text-white">{{ student.name }}</span>
                  </td>

                  <!-- Estado: pill buttons -->
                  <td class="px-4 py-3">
                    <div class="flex flex-wrap gap-1.5">
                      <button
                        v-for="opt in STATUS_OPTIONS"
                        :key="opt.key"
                        @click="setStatus(student, opt.key)"
                        :class="[
                          'px-2.5 py-0.5 rounded-full text-xs font-semibold border transition-all',
                          student.status === opt.key
                            ? opt.active + ' border-transparent shadow-sm'
                            : 'border-slate-200 dark:border-slate-600 text-slate-500 dark:text-slate-400 hover:border-slate-400'
                        ]"
                      >{{ opt.label }}</button>
                    </div>
                  </td>

                  <!-- Observación -->
                  <td class="px-4 py-3">
                    <input v-model="student.remarks"
                      type="text"
                      placeholder="Opcional..."
                      class="w-full max-w-xs bg-transparent border border-slate-200 dark:border-slate-600 rounded-lg px-2.5 py-1 text-xs text-slate-700 dark:text-slate-300 focus:ring-1 focus:ring-indigo-500 outline-none placeholder-slate-300 dark:placeholder-slate-600">
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Footer save button (secondary) -->
          <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
            <button @click="saveAttendance" type="button" :disabled="saving"
              class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors disabled:opacity-50 flex items-center gap-2 shadow-sm">
              <span v-if="saving" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
              <span v-else-if="saved">✓ Asistencia guardada</span>
              <span v-else>Guardar Asistencia</span>
            </button>
          </div>
        </div>
      </template>

      <!-- Estado vacío: sin sección seleccionada -->
      <div v-else
        class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-14 text-center text-slate-400 dark:text-slate-500">
        <svg class="w-14 h-14 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="font-medium">Selecciona una sección para comenzar el registro.</p>
        <p class="text-xs mt-1">Elige nivel → grado → sección en el panel de filtros.</p>
      </div>

    </div>
  </DashboardLayout>
</template>
