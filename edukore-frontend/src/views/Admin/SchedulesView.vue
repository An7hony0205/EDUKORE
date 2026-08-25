<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import api from '../../api/axios'

// ── Estructura académica en cascada ────────────────────────────────────────────
const levels            = ref([])
const selectedLevelId   = ref('')
const selectedGradeId   = ref('')
const selectedSectionId = ref('')

const filteredGrades = computed(() => {
  const lvl = levels.value.find(l => l.id === selectedLevelId.value)
  return lvl ? lvl.grades : []
})
const filteredSections = computed(() => {
  const gr = filteredGrades.value.find(g => g.id === selectedGradeId.value)
  return gr ? gr.sections : []
})

watch(selectedLevelId, () => { selectedGradeId.value = ''; selectedSectionId.value = '' })
watch(selectedGradeId, () => { selectedSectionId.value = '' })
watch(selectedSectionId, () => { if (selectedSectionId.value) { fetchSchedule(); fetchAssignments() } })

// ── Datos del horario ──────────────────────────────────────────────────────────
const loadingSchedule = ref(false)
const blocks          = ref([])         // [{id, day_of_week, start_time, end_time, course_name, teacher_name, room, type}]

// Asignaciones disponibles en la sección
const assignments = ref([])             // [{id, course_name, teacher_name, room}]

// ── Modal de asignación ────────────────────────────────────────────────────────
const showModal       = ref(false)
const modalSlot       = ref(null)       // {day, start_time, end_time}
const modalForm       = ref({ course_assignment_id: '', room: '' })
const saving          = ref(false)
const modalError      = ref('')

const timeSlots = ref([])

const DAYS = [
  { num: 1, label: 'Lunes' },
  { num: 2, label: 'Martes' },
  { num: 3, label: 'Miércoles' },
  { num: 4, label: 'Jueves' },
  { num: 5, label: 'Viernes' },
]

// ── Configuración de Bloques ───────────────────────────────────────────────────
const showConfigModal = ref(false)
const configSlots = ref([])
const savingConfig = ref(false)

// Paleta de colores por asignación (determinista)
const PALETTE = [
  'bg-indigo-100 dark:bg-indigo-900/50 border-indigo-300 dark:border-indigo-700 text-indigo-800 dark:text-indigo-200',
  'bg-emerald-100 dark:bg-emerald-900/50 border-emerald-300 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200',
  'bg-amber-100 dark:bg-amber-900/50 border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-200',
  'bg-rose-100 dark:bg-rose-900/50 border-rose-300 dark:border-rose-700 text-rose-800 dark:text-rose-200',
  'bg-cyan-100 dark:bg-cyan-900/50 border-cyan-300 dark:border-cyan-700 text-cyan-800 dark:text-cyan-200',
  'bg-purple-100 dark:bg-purple-900/50 border-purple-300 dark:border-purple-700 text-purple-800 dark:text-purple-200',
  'bg-lime-100 dark:bg-lime-900/50 border-lime-300 dark:border-lime-700 text-lime-800 dark:text-lime-200',
  'bg-orange-100 dark:bg-orange-900/50 border-orange-300 dark:border-orange-700 text-orange-800 dark:text-orange-200',
]

// Mapeo estable: course_assignment_id → color index
const colorMap = computed(() => {
  const map = {}
  let idx = 0
  blocks.value.forEach(b => {
    if (b.course_assignment_id && !(b.course_assignment_id in map)) {
      map[b.course_assignment_id] = idx++ % PALETTE.length
    }
  })
  return map
})

const blockColor = (b) => PALETTE[colorMap.value[b.course_assignment_id] ?? 0]

// Busca el bloque en la cuadrícula (day, slot)
const getBlock = (day, slot) =>
  blocks.value.find(b =>
    b.day_of_week === day &&
    b.start_time  === slot.start &&
    b.end_time    === slot.end
  ) || null

// ── Fetch ──────────────────────────────────────────────────────────────────────
onMounted(async () => {
  try {
    const res = await api.get('/academic-structure/summary')
    levels.value = res.data.data
  } catch (e) { console.error(e) }
})

const fetchTimeSlots = async () => {
  try {
    const res = await api.get('/time-slots', { params: { level_id: selectedLevelId.value || '' } })
    timeSlots.value = res.data.data.map(ts => ({
      ...ts,
      start: ts.start_time,
      end: ts.end_time,
      label: `${ts.name} (${ts.start_time} - ${ts.end_time})`,
      isBreak: ts.isBreak
    }))
  } catch (e) { console.error('Error cargando bloques de hora:', e) }
}

const fetchSchedule = async () => {
  loadingSchedule.value = true
  blocks.value = []
  try {
    await fetchTimeSlots()
    const res = await api.get('/schedules', { params: { section_id: selectedSectionId.value } })
    blocks.value = res.data.data
  } catch (e) { console.error('Error cargando horario:', e) }
  finally { loadingSchedule.value = false }
}

const fetchAssignments = async () => {
  try {
    // Trae todas las asignaciones de la sección
    const res = await api.get('/course-assignments', { params: { section_id: selectedSectionId.value } })
    assignments.value = (res.data.data ?? res.data).map(a => ({
      id:          a.id,
      label:       `${a.course?.name ?? '—'} — ${a.teacher?.name ?? '—'}`,
      room:        a.room ?? '',
    }))
  } catch (e) { console.error('Error cargando asignaciones:', e) }
}

// ── Modal ──────────────────────────────────────────────────────────────────────
const openModal = (day, slot) => {
  modalSlot.value = { day, start_time: slot.start, end_time: slot.end }
  modalForm.value = { course_assignment_id: '', room: '' }
  modalError.value = ''
  showModal.value  = true
}

const closeModal = () => { showModal.value = false }

const saveBlock = async () => {
  saving.value     = true
  modalError.value = ''
  try {
    await api.post('/schedules', {
      section_id:           selectedSectionId.value,
      course_assignment_id: modalForm.value.course_assignment_id || null,
      day_of_week:          modalSlot.value.day,
      start_time:           modalSlot.value.start_time,
      end_time:             modalSlot.value.end_time,
      room:                 modalForm.value.room || null,
      type:                 'academic',
    })
    closeModal()
    await fetchSchedule()
  } catch (e) {
    modalError.value = e.response?.data?.message ?? 'Error al guardar el bloque.'
  } finally {
    saving.value = false
  }
}

const deleteBlock = async (id) => {
  if (!confirm('¿Eliminar este bloque del horario?')) return
  try {
    await api.delete(`/schedules/${id}`)
    blocks.value = blocks.value.filter(b => b.id !== id)
  } catch (e) { console.error('Error eliminando bloque:', e) }
}
const openConfigModal = async () => {
  await fetchTimeSlots()
  configSlots.value = timeSlots.value.map(ts => ({ ...ts }))
  showConfigModal.value = true
}

const closeConfigModal = () => { showConfigModal.value = false }

const addConfigSlot = () => {
  configSlots.value.push({
    id: null,
    name: `Bloque ${configSlots.value.length + 1}`,
    start_time: '11:30',
    end_time: '12:15',
    type: 'academic',
    order_index: configSlots.value.length + 1
  })
}

const removeConfigSlot = (idx) => {
  if (!confirm('¿Eliminar este bloque de la configuración?')) return
  configSlots.value.splice(idx, 1)
}

const saveConfig = async () => {
  // Validación frontend
  for (let slot of configSlots.value) {
    if (!slot.name.trim() || !slot.start_time || !slot.end_time) {
      alert('Todos los bloques deben tener nombre y un rango de horas válido.')
      return
    }
    if (slot.start_time === slot.end_time) {
      alert(`El bloque "${slot.name}" tiene la misma hora de inicio y fin.`)
      return
    }
  }

  savingConfig.value = true
  try {
    const payload = {
      level_id: selectedLevelId.value || null,
      slots: configSlots.value.map(s => ({
        name: s.name,
        start_time: s.start_time.length === 5 ? s.start_time + ':00' : s.start_time,
        end_time: s.end_time.length === 5 ? s.end_time + ':00' : s.end_time,
        type: s.type,
      }))
    }
    
    await api.post('/time-slots/sync', payload)
    
    await fetchTimeSlots()
    if (selectedSectionId.value) fetchSchedule() // Refresh matrix si hay sección
    closeConfigModal()
  } catch (e) {
    console.error('Error guardando configuración:', e)
    const msg = e.response?.data?.message || e.message || 'Error desconocido'
    alert('Error al guardar configuración: ' + msg)
  } finally {
    savingConfig.value = false
  }
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
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Constructor de Horarios
          </h2>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Diseña el horario semanal de cada sección.</p>
        </div>
        <button @click="openConfigModal" type="button"
          class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center gap-2 shadow-sm">
          ⚙️ Configurar Bloques de Hora
        </button>
      </div>

      <!-- ── Filtros ─────────────────────────────────────────────────────────── -->
      <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wide">Nivel</label>
            <select v-model="selectedLevelId"
              class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
              <option value="">-- Nivel --</option>
              <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wide">Grado</label>
            <select v-model="selectedGradeId" :disabled="!selectedLevelId"
              class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none disabled:opacity-40">
              <option value="">-- Grado --</option>
              <option v-for="g in filteredGrades" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wide">Sección</label>
            <select v-model="selectedSectionId" :disabled="!selectedGradeId"
              class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none disabled:opacity-40">
              <option value="">-- Sección --</option>
              <option v-for="s in filteredSections" :key="s.id" :value="s.id">Sección {{ s.name }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- ── Placeholder sin sección ─────────────────────────────────────────── -->
      <div v-if="!selectedSectionId"
        class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-14 text-center text-slate-400 dark:text-slate-500">
        <svg class="w-14 h-14 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p class="font-medium">Selecciona una sección para ver el horario.</p>
      </div>

      <!-- ── Matriz semanal ───────────────────────────────────────────────────── -->
      <div v-else class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">

        <!-- Loading overlay -->
        <div v-if="loadingSchedule" class="p-10 space-y-3">
          <div v-for="i in 7" :key="i" class="h-14 rounded-xl bg-slate-100 dark:bg-slate-700 animate-pulse"></div>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-36">Hora</th>
                <th v-for="d in DAYS" :key="d.num"
                  class="px-3 py-3 text-center text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">
                  {{ d.label }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="slot in timeSlots" :key="slot.id || slot.start"
                :class="[
                  'border-b border-slate-100 dark:border-slate-700/60',
                  slot.isBreak ? 'bg-slate-50/80 dark:bg-slate-700/20' : ''
                ]">
                <!-- Hora -->
                <td class="px-4 py-2 text-xs font-mono text-slate-500 dark:text-slate-400 whitespace-nowrap">
                  <span :class="slot.isBreak ? 'font-semibold text-amber-600 dark:text-amber-400' : ''">
                    {{ slot.label }}
                  </span>
                  <span v-if="slot.isBreak" class="block text-amber-500 dark:text-amber-400 text-xs">Recreo</span>
                </td>

                <!-- Celdas por día -->
                <td v-for="d in DAYS" :key="d.num"
                  class="px-2 py-2 align-top min-w-[140px]">

                  <!-- Bloque Recreo: celda fija no editable -->
                  <div v-if="slot.isBreak"
                    class="h-10 rounded-lg border border-dashed border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/10 flex items-center justify-center">
                    <span class="text-xs text-amber-500 dark:text-amber-400">☕ Recreo</span>
                  </div>

                  <!-- Bloque ocupado -->
                  <div v-else-if="getBlock(d.num, slot)" :class="['rounded-lg border p-2 relative group', blockColor(getBlock(d.num, slot))]">
                    <p class="font-semibold text-xs leading-tight line-clamp-2">
                      {{ getBlock(d.num, slot).course_name ?? 'Sin curso' }}
                    </p>
                    <p class="text-xs opacity-70 mt-0.5 truncate">{{ getBlock(d.num, slot).teacher_name ?? '—' }}</p>
                    <p v-if="getBlock(d.num, slot).room" class="text-xs opacity-60 mt-0.5 truncate">🚪 {{ getBlock(d.num, slot).room }}</p>
                    <!-- Botón eliminar (aparece en hover) -->
                    <button @click="deleteBlock(getBlock(d.num, slot).id)"
                      class="absolute top-1 right-1 w-5 h-5 rounded-full bg-white/70 dark:bg-black/40 text-slate-500 hover:text-red-600 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
                      title="Eliminar bloque">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>

                  <!-- Celda vacía: botón + -->
                  <button v-else @click="openModal(d.num, slot)"
                    class="w-full h-14 rounded-lg border-2 border-dashed border-slate-200 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors flex items-center justify-center text-slate-300 dark:text-slate-600 hover:text-indigo-500 dark:hover:text-indigo-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── Modal Asignación de Bloque ────────────────────────────────────────── -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>

      <div class="relative bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl w-full max-w-md z-10 shadow-2xl">
        <!-- Header modal -->
        <div class="p-5 border-b border-slate-200 dark:border-slate-700">
          <h3 class="font-bold text-lg text-slate-900 dark:text-white">Asignar Bloque</h3>
          <p class="text-xs text-slate-400 mt-0.5" v-if="modalSlot">
            {{ ['', 'Lunes','Martes','Miércoles','Jueves','Viernes'][modalSlot.day] }}
            · {{ modalSlot.start_time }} – {{ modalSlot.end_time }}
          </p>
        </div>

        <!-- Body modal -->
        <div class="p-5 space-y-4">
          <!-- Error -->
          <div v-if="modalError" class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg px-4 py-2">
            {{ modalError }}
          </div>

          <!-- Asignación docente -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Asignación Docente *</label>
            <select v-model="modalForm.course_assignment_id"
              class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
              <option value="">-- Seleccionar asignación --</option>
              <option v-for="a in assignments" :key="a.id" :value="a.id">{{ a.label }}</option>
            </select>
          </div>

          <!-- Aula opcional -->
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
              Aula <span class="text-slate-400 font-normal text-xs">(opcional, sobreescribe aula de la asignación)</span>
            </label>
            <input v-model="modalForm.room" type="text" placeholder="Ej: Aula A-101"
              class="w-full bg-slate-50 dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600 rounded-xl px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
          </div>
        </div>

        <!-- Footer modal -->
        <div class="p-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3 bg-slate-50 dark:bg-slate-800/50 rounded-b-2xl">
          <button @click="closeModal" type="button"
            class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
            Cancelar
          </button>
          <button @click="saveBlock" type="button" :disabled="saving || !modalForm.course_assignment_id"
            class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-50 flex items-center gap-2">
            <span v-if="saving" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
            Guardar Bloque
          </button>
        </div>
      </div>
    </div>

    <!-- ── Modal Configuración de Bloques ────────────────────────────────────────── -->
    <div v-if="showConfigModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeConfigModal"></div>

      <div class="relative bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl w-full max-w-2xl z-10 shadow-2xl flex flex-col max-h-[90vh]">
        
        <div class="p-5 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 rounded-t-2xl">
          <div>
            <h3 class="font-bold text-lg text-slate-900 dark:text-white flex items-center gap-2">
              ⚙️ Configuración de Bloques de Hora
            </h3>
            <p class="text-xs text-slate-500 mt-1">Nivel: {{ levels.find(l => l.id === selectedLevelId)?.name || 'General (Todos los niveles)' }}</p>
          </div>
          <button @click="closeConfigModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <div class="p-5 overflow-y-auto flex-1 space-y-3">
          <div v-for="(slot, idx) in configSlots" :key="idx" class="flex items-center gap-3 bg-slate-50 dark:bg-slate-900/30 p-3 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="flex-1">
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Nombre</label>
              <input v-model="slot.name" type="text" placeholder="Ej: 1° Hora"
                class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-2.5 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="w-24">
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Inicio</label>
              <input v-model="slot.start_time" type="time"
                class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-2.5 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="w-24">
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Fin</label>
              <input v-model="slot.end_time" type="time"
                class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-2.5 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="w-32">
              <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Tipo</label>
              <select v-model="slot.type"
                class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg px-2.5 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="academic">Pedagógica</option>
                <option value="break">Recreo / Descanso</option>
                <option value="assembly">Asamblea</option>
              </select>
            </div>
            <button @click="removeConfigSlot(idx)" type="button" class="mt-4 w-8 h-8 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Eliminar bloque">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>

          <button @click="addConfigSlot" type="button" class="w-full py-3 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl text-slate-500 font-medium hover:text-indigo-600 hover:border-indigo-400 dark:hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/10 transition-colors flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Añadir Nuevo Bloque
          </button>
        </div>

        <div class="p-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3 bg-slate-50 dark:bg-slate-800/50 rounded-b-2xl">
          <button @click="closeConfigModal" type="button" class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Cancelar</button>
          <button @click="saveConfig" type="button" :disabled="savingConfig" class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-50 flex items-center gap-2">
            <span v-if="savingConfig" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
            Guardar Configuración
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
