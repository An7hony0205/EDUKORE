<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/axios'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const data = ref(null)
const announcements = ref([])
const isLoading = ref(true)

const fetchData = async () => {
  try {
    const [dashRes, annRes] = await Promise.all([
        api.get('/parent-dashboard'),
        api.get('/announcements')
    ])
    data.value = dashRes.data
    announcements.value = annRes.data
  } catch (error) {
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <DashboardLayout>
    <div class="h-full flex flex-col space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Portal de Apoderado</h1>
        <p class="text-slate-400 text-sm mt-1">Visión consolidada de tus estudiantes, finanzas y comunidad</p>
      </div>

      <div v-if="isLoading" class="text-slate-400">Cargando información...</div>
      <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Hijos -->
        <div class="col-span-1 md:col-span-2 space-y-6">
          <h2 class="text-lg font-bold text-white">Mis Estudiantes</h2>
          <div v-if="data.students.length === 0" class="text-slate-400 text-sm">No tienes estudiantes asociados.</div>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div v-for="student in data.students" :key="student.id" class="bg-brand-surface border border-brand-border p-4 rounded-xl flex items-center gap-4 shadow-lg">
              <div class="w-12 h-12 bg-indigo-500/20 text-indigo-400 rounded-full flex items-center justify-center font-bold text-xl uppercase">
                {{ student.user?.name.charAt(0) }}
              </div>
              <div>
                <h3 class="text-white font-medium">{{ student.user?.name }}</h3>
                <p class="text-xs text-slate-400">Estudiante activo</p>
              </div>
            </div>
          </div>

          <h2 class="text-lg font-bold text-white pt-4">Deudas Económicas (Pensiones)</h2>
          <div v-if="data.fees.length === 0" class="text-emerald-400 text-sm bg-emerald-500/10 p-4 rounded-xl border border-emerald-500/20 shadow-lg">
            ¡Felicidades! No tienes deudas académicas pendientes.
          </div>
          <div v-else class="space-y-3">
            <div v-for="fee in data.fees" :key="fee.id" class="bg-brand-surface border border-rose-500/30 p-4 rounded-xl flex justify-between items-center shadow-lg">
              <div>
                <h3 class="text-rose-400 font-medium text-sm">{{ fee.title }}</h3>
                <p class="text-xs text-slate-400">Vence: {{ new Date(fee.due_date).toLocaleDateString() }}</p>
              </div>
              <div class="font-bold text-white">
                {{ fee.currency }} {{ fee.amount }}
              </div>
            </div>
          </div>
        </div>

        <!-- Obligaciones y Comunidad -->
        <div class="col-span-1 space-y-6">
          <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-5 shadow-lg">
            <h2 class="text-amber-400 font-bold mb-4 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
              Obligaciones Cívicas
            </h2>
            <div v-if="data.obligations.length === 0" class="text-slate-400 text-sm">
              Estás al día con tus compromisos de APAFA y faenas.
            </div>
            <div v-else class="space-y-3">
              <div v-for="obs in data.obligations" :key="obs.id" class="bg-black/20 p-3 rounded-lg border border-amber-500/20">
                <h4 class="text-sm font-semibold text-white">{{ obs.title }}</h4>
                <p class="text-xs text-slate-300 mt-1">{{ obs.description }}</p>
                <span class="inline-block mt-2 text-[10px] uppercase font-bold text-rose-400 bg-rose-500/20 px-2 py-1 rounded">Pendiente de resolución</span>
              </div>
            </div>
          </div>

          <div class="bg-brand-surface border border-brand-border rounded-xl p-5 shadow-lg">
            <h2 class="text-white font-bold mb-4">Próximos Eventos</h2>
            <div v-if="data.upcoming_events.length === 0" class="text-slate-400 text-sm">
              No hay eventos programados.
            </div>
            <div v-else class="space-y-3">
              <div v-for="ev in data.upcoming_events" :key="ev.id" class="border-b border-brand-border pb-3 last:border-0 last:pb-0">
                <h4 class="text-sm font-semibold text-indigo-400">{{ ev.event?.title }}</h4>
                <p class="text-xs text-slate-400 mt-1">{{ new Date(ev.event?.date).toLocaleString() }}</p>
              </div>
            </div>
          </div>
        </div>
        
      </div>
      </div>
      </div>
  </DashboardLayout>
</template>
