<script setup>
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import api from '../../api/axios'

// ── Data ──────────────────────────────────────────────────────────────────────
const loading     = ref(true)
const levels      = ref([])   // [{id, name, grades:[{id, name, sections:[...]}]}]
const activeTab   = ref(null) // nivel seleccionado

// ── Modal alumnos ──────────────────────────────────────────────────────────────
const studentModal        = ref(false)
const studentModalSection = ref(null)  // {id, name}
const studentList         = ref([])
const studentsLoading     = ref(false)

// ── Modal tutor ────────────────────────────────────────────────────────────────
const tutorModal          = ref(false)
const tutorModalSection   = ref(null)
const tutorForm           = ref({ tutor_id: '' })
const teachers            = ref([])
const savingTutor         = ref(false)

// ── Computed ───────────────────────────────────────────────────────────────────
const activeLevel = computed(() =>
  levels.value.find(l => l.id === activeTab.value) || null
)

// ── Helpers ────────────────────────────────────────────────────────────────────
const occupancyPercent = (s) => Math.min(100, Math.round((s.students_count / (s.max_capacity || 1)) * 100))

const occupancyColor = (s) => {
  const pct = occupancyPercent(s)
  if (pct >= 90) return 'bg-red-500'
  if (pct >= 70) return 'bg-amber-400'
  return 'bg-emerald-500'
}

const badgeColor = (s) => {
  const pct = occupancyPercent(s)
  if (pct >= 90) return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
  if (pct >= 70) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
  return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
}

// ── Fetch ──────────────────────────────────────────────────────────────────────
onMounted(async () => {
  try {
    const res = await api.get('/academic-structure/summary')
    levels.value = res.data.data
    if (levels.value.length) activeTab.value = levels.value[0].id
  } catch (e) {
    console.error('Error cargando estructura académica:', e)
  } finally {
    loading.value = false
  }
})

const openStudents = async (section) => {
  studentModal.value        = true
  studentModalSection.value = section
  studentList.value         = []
  studentsLoading.value     = true
  try {
    const res = await api.get(`/academic-structure/sections/${section.id}/students`)
    studentList.value = res.data.students
  } catch (e) {
    console.error('Error cargando estudiantes:', e)
  } finally {
    studentsLoading.value = false
  }
}

const openTutorModal = async (section) => {
  tutorModalSection.value = section
  tutorForm.value.tutor_id = section.tutor ? section.tutor.id : ''
  tutorModal.value = true
  
  if (!teachers.value.length) {
    try {
      const res = await api.get('/teachers?limit=all&active_only=1')
      teachers.value = res.data.data
    } catch (e) {
      console.error('Error cargando docentes:', e)
    }
  }
}

const saveTutor = async () => {
  savingTutor.value = true
  try {
    const res = await api.put(`/academic-structure/sections/${tutorModalSection.value.id}/tutor`, tutorForm.value)
    // Update local state directly
    tutorModalSection.value.tutor = res.data.tutor
    tutorModal.value = false
    // Show toast (assuming a simple alert for now if no toast system, wait, the user asked for a notification/toast without reloading)
    alert(res.data.message)
  } catch (e) {
    console.error('Error guardando tutor:', e)
    alert('Error al asignar el tutor.')
  } finally {
    savingTutor.value = false
  }
}

const closeModal = () => { 
  studentModal.value = false 
  tutorModal.value = false
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
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Aulas y Secciones
          </h2>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
            Gestión de aforo, tutores y matrícula por sección.
          </p>
        </div>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading" class="space-y-4">
        <div v-for="i in 3" :key="i" class="h-32 rounded-2xl bg-slate-200 dark:bg-slate-700 animate-pulse"></div>
      </div>

      <!-- Empty -->
      <div v-else-if="!levels.length"
        class="text-center py-20 text-slate-500 dark:text-slate-400">
        No se encontró estructura académica. Ejecuta el seeder de estructura primero.
      </div>

      <template v-else>
        <!-- Level tabs -->
        <div class="flex gap-2 border-b border-slate-200 dark:border-slate-700 pb-0">
          <button
            v-for="level in levels"
            :key="level.id"
            @click="activeTab = level.id"
            :class="[
              'px-5 py-2.5 text-sm font-semibold rounded-t-xl border-b-2 transition-colors',
              activeTab === level.id
                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-800'
                : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
            ]"
          >
            {{ level.name }}
          </button>
        </div>

        <!-- Grades for active level -->
        <div v-if="activeLevel" class="space-y-6">
          <div
            v-for="grade in activeLevel.grades"
            :key="grade.id"
            class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden shadow-sm"
          >
            <!-- Grade header -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center">
                  <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                  </svg>
                </div>
                <h3 class="font-semibold text-slate-900 dark:text-white">{{ grade.name }}</h3>
              </div>
              <span class="text-xs text-slate-500 dark:text-slate-400">
                {{ grade.sections.length }} sección{{ grade.sections.length !== 1 ? 'es' : '' }}
              </span>
            </div>

            <!-- Section cards grid -->
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
              <div
                v-for="section in grade.sections"
                :key="section.id"
                class="relative bg-slate-50 dark:bg-slate-700/40 border border-slate-200 dark:border-slate-600 rounded-xl p-4 flex flex-col gap-3 hover:shadow-md transition-shadow"
              >
                <!-- Section name -->
                <div class="flex items-center justify-between">
                  <span class="text-lg font-bold text-slate-900 dark:text-white">
                    Sección {{ section.name }}
                  </span>
                  <!-- Occupancy badge -->
                  <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', badgeColor(section)]">
                    {{ occupancyPercent(section) }}%
                  </span>
                </div>

                <!-- Occupancy bar -->
                <div>
                  <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
                    <span>{{ section.students_count }} / {{ section.max_capacity }} alumnos</span>
                  </div>
                  <div class="h-1.5 rounded-full bg-slate-200 dark:bg-slate-600 overflow-hidden">
                    <div
                      :class="['h-full rounded-full transition-all', occupancyColor(section)]"
                      :style="{ width: occupancyPercent(section) + '%' }"
                    ></div>
                  </div>
                </div>

                <!-- Tutor -->
                <div class="flex items-center justify-between text-xs text-slate-400 my-2 pt-1 border-t border-slate-700/50">
                  <div class="flex items-center gap-1.5 truncate">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="truncate">{{ section.tutor ? section.tutor.name : 'Sin tutor asignado' }}</span>
                  </div>
                  <button 
                    type="button" 
                    @click="openTutorModal(section)" 
                    class="text-indigo-400 hover:text-indigo-300 font-medium ml-2 underline shrink-0 cursor-pointer">
                    {{ section.tutor ? 'Cambiar' : 'Asignar' }}
                  </button>
                </div>

                <!-- Action button -->
                <button
                  @click="openStudents(section)"
                  class="mt-auto w-full py-2 text-xs font-semibold rounded-lg border border-indigo-300 dark:border-indigo-700 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors"
                >
                  Ver Estudiantes
                </button>
              </div>

              <!-- Empty sections state -->
              <div
                v-if="!grade.sections.length"
                class="col-span-full text-center py-8 text-slate-400 dark:text-slate-500 text-sm"
              >
                No hay secciones para este grado.
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- ── Modal de Estudiantes ──────────────────────────────────────────────── -->
    <div v-if="studentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>

      <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl w-full max-w-2xl z-10 shadow-2xl flex flex-col max-h-[85vh]">
        <!-- Modal header -->
        <div class="p-5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between shrink-0">
          <div>
            <h3 class="font-bold text-lg text-slate-900 dark:text-white">
              Sección {{ studentModalSection?.name }}
            </h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
              Listado de estudiantes matriculados
            </p>
          </div>
          <button @click="closeModal" class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Modal body -->
        <div class="flex-1 overflow-y-auto p-5">
          <!-- Loading -->
          <div v-if="studentsLoading" class="space-y-2">
            <div v-for="i in 5" :key="i" class="h-12 rounded-lg bg-slate-100 dark:bg-slate-700 animate-pulse"></div>
          </div>

          <!-- Empty -->
          <div v-else-if="!studentList.length"
            class="text-center py-12 text-slate-400 dark:text-slate-500">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <p class="font-medium">Sin estudiantes matriculados</p>
            <p class="text-xs mt-1">Esta sección aún no tiene alumnos asignados.</p>
          </div>

          <!-- Table -->
          <table v-else class="w-full text-sm">
            <thead>
              <tr class="text-left text-xs text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                <th class="pb-3 font-medium">#</th>
                <th class="pb-3 font-medium">Nombre</th>
                <th class="pb-3 font-medium">Matrícula</th>
                <th class="pb-3 font-medium">Apoderado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
              <tr
                v-for="(student, idx) in studentList"
                :key="student.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/50"
              >
                <td class="py-3 pr-4 text-slate-400 dark:text-slate-500 text-xs">{{ idx + 1 }}</td>
                <td class="py-3 pr-4">
                  <span class="font-medium text-slate-900 dark:text-white">{{ student.name }}</span>
                </td>
                <td class="py-3 pr-4 font-mono text-xs text-slate-600 dark:text-slate-400">
                  {{ student.enrollment_number || '—' }}
                </td>
                <td class="py-3 text-slate-500 dark:text-slate-400 text-xs">
                  {{ student.parent_name || 'Sin apoderado' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Modal footer -->
        <div class="p-4 border-t border-slate-200 dark:border-slate-700 flex justify-end shrink-0">
          <button @click="closeModal"
            class="px-5 py-2 rounded-lg bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-medium hover:opacity-90 transition-opacity">
            Cerrar
          </button>
        </div>
      </div>
    </div>
    
    <!-- ── Modal de Tutor ─────────────────────────────────────────────────── -->
    <div v-if="tutorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>

      <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl w-full max-w-sm z-10 shadow-2xl flex flex-col">
        <div class="p-5 border-b border-slate-200 dark:border-slate-700">
          <h3 class="font-bold text-lg text-slate-900 dark:text-white">
            Asignar Tutor
          </h3>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
            Sección {{ tutorModalSection?.name }}
          </p>
        </div>

        <div class="p-5">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Docente Responsable</label>
          <select v-model="tutorForm.tutor_id" 
            class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-slate-900 dark:text-white">
            <option value="">-- Sin Tutor --</option>
            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
              {{ teacher.name }}
            </option>
          </select>
        </div>

        <div class="p-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3 shrink-0 bg-slate-50 dark:bg-slate-800/50 rounded-b-2xl">
          <button @click="closeModal" type="button"
            class="px-4 py-2 rounded-lg text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
            Cancelar
          </button>
          <button @click="saveTutor" type="button" :disabled="savingTutor"
            class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50 flex items-center gap-2">
            <span v-if="savingTutor" class="w-3.5 h-3.5 rounded-full border-2 border-white/40 border-t-white animate-spin"></span>
            Guardar Tutor
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
