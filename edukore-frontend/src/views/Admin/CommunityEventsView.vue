<script setup>
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import { ref, onMounted } from 'vue'
import api from '../../api/axios'

const events = ref([])
const isLoading = ref(true)
const showModal = ref(false)
const formData = ref({
  title: '',
  description: '',
  date: '',
  event_type: 'FAENA'
})

const fetchEvents = async () => {
  try {
    const res = await api.get('/events')
    events.value = res.data
  } catch (error) {
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchEvents()
})

const submitEvent = async () => {
  try {
    await api.post('/events', formData.value)
    showModal.value = false
    fetchEvents()
  } catch (error) {
    alert('Error creando evento')
  }
}

const updateParticipantStatus = async (participantId, status) => {
  try {
    await api.patch(`/event-participants/${participantId}/status`, { status })
    fetchEvents()
  } catch (error) {
    alert('Error actualizando asistencia')
  }
}
</script>

<template>
  <DashboardLayout>
  <div class="h-full flex flex-col space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Comunidad y Faenas</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Gestiona asambleas, reuniones y trabajo comunitario</p>
      </div>
      <button @click="showModal = true" class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 px-4 py-2 rounded-lg font-medium transition-colors shadow-lg shadow-primary-500/20">
        + Nuevo Evento
      </button>
    </div>

    <!-- Lista de eventos -->
    <div v-if="isLoading" class="text-slate-500 dark:text-slate-400">Cargando eventos...</div>
    <div v-else-if="events.length === 0" class="text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-900 p-8 rounded-xl border border-slate-200 dark:border-slate-800 text-center">
      No hay eventos programados. Haz clic en "Nuevo Evento" para empezar.
    </div>

    <div v-else class="space-y-6">
      <div v-for="evt in events" :key="evt.id" class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-xl overflow-hidden shadow-xl p-6">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
              {{ evt.title }}
              <span class="px-2 py-1 rounded text-[10px] font-bold" 
                :class="evt.event_type === 'FAENA' ? 'bg-amber-500/20 text-amber-400' : 'bg-indigo-500/20 text-indigo-400'">
                {{ evt.event_type }}
              </span>
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ evt.description }}</p>
            <p class="text-xs text-slate-500 mt-1">Fecha: {{ new Date(evt.date).toLocaleString() }}</p>
          </div>
        </div>

        <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2 border-b border-slate-200 dark:border-slate-800 pb-2">Pase de Lista (Apoderados)</h3>
        
        <table class="min-w-full divide-y divide-brand-border mt-2">
          <thead class="bg-slate-50 dark:bg-slate-50 dark:bg-slate-800/50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Apoderado</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Estado</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Marcar Asistencia</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-brand-border">
            <tr v-for="p in evt.participants" :key="p.id" class="hover:bg-white/[0.02]">
              <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-900 dark:text-white">{{ p.user?.name || 'Usuario desconocido' }}</td>
              <td class="px-4 py-2 whitespace-nowrap text-sm">
                <span class="px-2 py-1 rounded-full text-xs"
                  :class="{
                    'bg-slate-500/20 text-slate-500 dark:text-slate-400': p.status === 'PENDING',
                    'bg-emerald-500/20 text-emerald-400': p.status === 'PRESENT',
                    'bg-rose-500/20 text-rose-400': p.status === 'ABSENT',
                    'bg-amber-500/20 text-amber-400': p.status === 'EXCUSED'
                  }">
                  {{ p.status }}
                </span>
              </td>
              <td class="px-4 py-2 whitespace-nowrap text-sm space-x-2">
                <button v-if="p.status !== 'PRESENT'" @click="updateParticipantStatus(p.id, 'PRESENT')" class="text-emerald-400 hover:text-emerald-300">AsistiÃ³</button>
                <button v-if="p.status !== 'ABSENT'" @click="updateParticipantStatus(p.id, 'ABSENT')" class="text-rose-400 hover:text-rose-300">FaltÃ³</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Nuevo Evento -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
      <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-xl w-full max-w-md p-6 shadow-2xl">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Planificar Evento</h2>
        
        <form @submit.prevent="submitEvent" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">TÃ­tulo</label>
            <input type="text" v-model="formData.title" required class="w-full bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">DescripciÃ³n</label>
            <textarea v-model="formData.description" class="w-full bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Fecha</label>
            <input type="date" v-model="formData.date" required class="w-full bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tipo</label>
            <select v-model="formData.event_type" required class="w-full bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white">
              <option value="FAENA">Faena / Mantenimiento</option>
              <option value="MEETING">ReuniÃ³n APAFA / Asamblea</option>
            </select>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="showModal = false" class="px-4 py-2 text-slate-700 dark:text-slate-300">Cancelar</button>
            <button type="submit" class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 px-6 py-2 rounded-lg">Crear</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  </DashboardLayout>
</template>
