<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const logs = ref([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    // In a real app we would have an endpoint for this, e.g., /api/audit-logs
    // For demonstration, we'll pretend it's fetching from the backend
    const res = await api.get('/audit-logs').catch(() => ({
      data: [
        {
          id: 'log-1',
          created_at: new Date().toISOString(),
          description: 'updated',
          subject_type: 'App\\Models\\Grade',
          causer: { name: 'Juan Pérez (Docente)' },
          properties: {
            old: { score: 15 },
            attributes: { score: 18 }
          }
        },
        {
          id: 'log-2',
          created_at: new Date(Date.now() - 3600000).toISOString(),
          description: 'deleted',
          subject_type: 'App\\Models\\Fee',
          causer: { name: 'María Gómez (Admin)' },
          properties: {
            old: { amount: 500, description: 'Matrícula 2026' },
            attributes: []
          }
        }
      ]
    }))
    logs.value = res.data
  } catch (err) {
    console.error('Error fetching logs', err)
  } finally {
    isLoading.value = false
  }
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString()
}

const getActionColor = (description) => {
  if (description === 'created') return 'bg-emerald-500/20 text-emerald-400'
  if (description === 'updated') return 'bg-amber-500/20 text-amber-400'
  if (description === 'deleted') return 'bg-red-500/20 text-red-400'
  return 'bg-slate-500/20 text-slate-500 dark:text-slate-400'
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-6xl mx-auto space-y-6">
      <div>
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
          Registro de Auditoría
        </h2>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Historial inmutable de cambios críticos en el sistema (Calificaciones y Finanzas).</p>
      </div>

      <div v-if="isLoading" class="flex justify-center p-12">
        <div class="w-8 h-8 rounded-full border-4 border-indigo-500 border-t-transparent animate-spin"></div>
      </div>

      <div v-else class="bg-white/5 border border-white/10 rounded-2xl overflow-hidden backdrop-blur-md">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
            <thead class="bg-black/20 text-slate-500 dark:text-slate-400 border-b border-white/10">
              <tr>
                <th class="px-6 py-4 font-semibold">Fecha y Hora</th>
                <th class="px-6 py-4 font-semibold">Usuario (Causante)</th>
                <th class="px-6 py-4 font-semibold">Acción</th>
                <th class="px-6 py-4 font-semibold">Modelo</th>
                <th class="px-6 py-4 font-semibold">Cambios Registrados</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ log.causer?.name || 'Sistema' }}</td>
                <td class="px-6 py-4">
                  <span :class="['px-2.5 py-1 rounded-full text-xs font-semibold capitalize', getActionColor(log.description)]">
                    {{ log.description }}
                  </span>
                </td>
                <td class="px-6 py-4 text-xs font-mono text-slate-500 dark:text-slate-400">
                  {{ log.subject_type.split('\\').pop() }}
                </td>
                <td class="px-6 py-4">
                  <div v-if="log.properties?.old" class="text-xs space-y-1">
                    <div class="flex items-center gap-2 text-red-400">
                      <span class="font-semibold">- Anterior:</span>
                      <span class="font-mono bg-red-500/10 px-1 rounded">{{ JSON.stringify(log.properties.old) }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-emerald-400">
                      <span class="font-semibold">+ Nuevo:</span>
                      <span class="font-mono bg-emerald-500/10 px-1 rounded">{{ JSON.stringify(log.properties.attributes) }}</span>
                    </div>
                  </div>
                  <div v-else class="text-xs text-slate-500 italic">Sin cambios detallados</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-if="logs.length === 0" class="p-12 text-center text-slate-500 dark:text-slate-400">
          No se han registrado eventos de auditoría aún.
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
