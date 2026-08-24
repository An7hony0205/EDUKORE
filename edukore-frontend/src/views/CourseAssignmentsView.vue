<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const assignments = ref([])
const loading = ref(true)

// Modal state
const showModal = ref(false)
const isSubmitting = ref(false)

// Options for selects
const teachers = ref([])
const courses = ref([])
const sections = ref([])

// Form state
const form = ref({
  teacher_id: '',
  course_id: '',
  section_id: '',
  weekly_hours: 4,
  room: '',
  schedule: '',
  is_substitute: false
})

const fetchAssignments = async () => {
  try {
    const res = await api.get('/course-assignments')
    assignments.value = res.data.data
  } catch (error) {
    console.error("Failed to fetch assignments", error)
  } finally {
    loading.value = false
  }
}

const fetchOptions = async () => {
  try {
    const [tRes, cRes, sRes] = await Promise.all([
      api.get('/teachers?active_only=1&limit=all'),
      api.get('/courses?status=active'),
      api.get('/sections')
    ])
    teachers.value = tRes.data.data || tRes.data
    courses.value = cRes.data.data || cRes.data
    sections.value = sRes.data.data || sRes.data
  } catch (error) {
    console.error("Failed to fetch options", error)
  }
}

onMounted(() => {
  fetchAssignments()
  fetchOptions()
})

const openModal = () => {
  form.value = {
    teacher_id: '',
    course_id: '',
    section_id: '',
    weekly_hours: 4,
    room: '',
    schedule: '',
    is_substitute: false
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}

const submitForm = async () => {
  if (!form.value.teacher_id || !form.value.course_id || !form.value.section_id) {
    alert("Por favor completa los campos obligatorios (Docente, Curso y Sección).")
    return
  }
  
  isSubmitting.value = true
  try {
    await api.post('/course-assignments', form.value)
    await fetchAssignments()
    closeModal()
  } catch (error) {
    console.error("Error creating assignment", error)
    const msg = error.response?.data?.message || "Ocurrió un error al guardar la asignación."
    alert(msg)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            Asignación Docente
          </h2>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Administra la carga lectiva, cursos y horarios de los profesores.</p>
        </div>
        <button @click="openModal" class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
          + Nueva Asignación
        </button>
      </div>

      <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl overflow-hidden backdrop-blur-md">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
            <thead class="bg-slate-50 dark:bg-slate-50 dark:bg-slate-800/50 text-xs uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th scope="col" class="px-6 py-4 font-medium">Docente</th>
                <th scope="col" class="px-6 py-4 font-medium">Curso</th>
                <th scope="col" class="px-6 py-4 font-medium">Sección</th>
                <th scope="col" class="px-6 py-4 font-medium">Aula / Horario</th>
                <th scope="col" class="px-6 py-4 font-medium text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading" class="border-b border-white/5">
                <td colspan="5" class="px-6 py-8 text-center text-slate-500">Cargando asignaciones...</td>
              </tr>
              <tr v-else-if="assignments.length === 0" class="border-b border-slate-200 dark:border-slate-800">
                <td colspan="5" class="px-6 py-8 text-center text-slate-500">No hay asignaciones docentes registradas.</td>
              </tr>
              <tr v-else v-for="assignment in assignments" :key="assignment.id" class="border-b border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-700 text-slate-900 dark:text-white flex items-center justify-center font-bold text-xs">
                      {{ assignment.teacher?.name?.charAt(0) || 'D' }}
                    </div>
                    <div>
                      <div class="text-slate-900 dark:text-white font-medium">{{ assignment.teacher?.name || 'Desconocido' }}</div>
                      <div class="text-xs text-primary-400 font-medium" v-if="assignment.is_substitute">Suplente</div>
                      <div class="text-xs text-slate-500" v-else>Titular ({{ assignment.weekly_hours }}h)</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-slate-900 dark:text-white font-medium">{{ assignment.course?.name }}</td>
                <td class="px-6 py-4">
                  <div class="text-sm">
                    {{ assignment.section?.grade_level?.name }} "{{ assignment.section?.name }}"
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-700 dark:text-slate-300">Aula: {{ assignment.room || 'No asignada' }}</div>
                  <div class="text-xs text-slate-500 mt-1">{{ assignment.schedule || 'Sin horario configurado' }}</div>
                </td>
                <td class="px-6 py-4 text-right">
                  <button class="text-primary-400 hover:text-primary-300 text-xs font-medium">Editar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal for New Assignment -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
      
      <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl w-full max-w-2xl z-10 overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between shrink-0">
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">Nueva Asignación Docente</h3>
          <button @click="closeModal" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>
        
        <div class="p-6 overflow-y-auto flex-1">
          <form @submit.prevent="submitForm" class="space-y-6" id="assignment-form">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Docente -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Docente *</label>
                <select v-model="form.teacher_id" required class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-slate-500 dark:focus:ring-slate-400 outline-none">
                  <option value="" disabled>-- Seleccionar Docente --</option>
                  <option v-for="t in teachers" :key="t.id" :value="t.id" class="bg-white dark:bg-slate-900">{{ t.name }}</option>
                </select>
              </div>

              <!-- Curso -->
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Curso *</label>
                <select v-model="form.course_id" required class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-slate-500 dark:focus:ring-slate-400 outline-none">
                  <option value="" disabled>-- Seleccionar Curso --</option>
                  <option v-for="c in courses" :key="c.id" :value="c.id" class="bg-white dark:bg-slate-900">{{ c.name }}</option>
                </select>
              </div>

              <!-- Sección -->
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Sección *</label>
                <select v-model="form.section_id" required class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-slate-500 dark:focus:ring-slate-400 outline-none">
                  <option value="" disabled>-- Seleccionar Sección --</option>
                  <option v-for="s in sections" :key="s.id" :value="s.id" class="bg-white dark:bg-slate-900">{{ s.grade_level?.name }} "{{ s.name }}"</option>
                </select>
              </div>

              <!-- Horas Semanales -->
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Horas Semanales</label>
                <input type="number" v-model="form.weekly_hours" min="1" max="40" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-slate-500 dark:focus:ring-slate-400 outline-none" />
              </div>

              <!-- Aula -->
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Aula</label>
                <input type="text" v-model="form.room" placeholder="Ej: A-302" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-slate-500 dark:focus:ring-slate-400 outline-none" />
              </div>

              <!-- Horario -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Horario</label>
                <input type="text" v-model="form.schedule" placeholder="Ej: Lunes y Miércoles 8:00 AM - 10:00 AM" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-slate-500 dark:focus:ring-slate-400 outline-none" />
              </div>

              <!-- Suplente Checkbox -->
              <div class="md:col-span-2 flex items-center gap-3 mt-2">
                <input type="checkbox" id="is_substitute" v-model="form.is_substitute" class="w-5 h-5 rounded border-slate-200 dark:border-slate-800 bg-white/5 text-primary-600 focus:ring-slate-500 dark:focus:ring-slate-400 focus:ring-offset-slate-900" />
                <label for="is_substitute" class="text-sm font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                  Este docente es suplente (reemplazo temporal)
                </label>
              </div>
            </div>
          </form>
        </div>

        <div class="p-6 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3 shrink-0 bg-brand-dark/30">
          <button @click="closeModal" type="button" class="px-5 py-2.5 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
            Cancelar
          </button>
          <button form="assignment-form" type="submit" :disabled="isSubmitting" class="px-5 py-2.5 bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 disabled:opacity-50 text-slate-900 dark:text-white font-medium rounded-xl transition-colors flex items-center gap-2">
            <span v-if="isSubmitting" class="w-4 h-4 rounded-full border-2 border-white/50 border-t-white animate-spin"></span>
            Crear Asignación
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
