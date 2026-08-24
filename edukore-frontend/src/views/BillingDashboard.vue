<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const fees = ref([])
const isLoading = ref(true)

const stats = ref({
  totalCollected: 0,
  totalPending: 0,
  overdueCount: 0
})

onMounted(async () => {
  try {
    const res = await api.get('/fees')
    fees.value = res.data.data || res.data
    
    // Calcular estadísticas simples
    let collected = 0;
    let pending = 0;
    let overdue = 0;
    
    fees.value.forEach(fee => {
      const amount = parseFloat(fee.amount);
      if (fee.status === 'paid') collected += amount;
      if (fee.status === 'pending' || fee.status === 'partial') pending += amount;
      if (fee.status === 'overdue') overdue++;
    });
    
    stats.value = {
      totalCollected: collected,
      totalPending: pending,
      overdueCount: overdue
    }
  } catch (err) {
    console.error('Error cargando finanzas', err)
  } finally {
    isLoading.value = false
  }
})

const formatMoney = (amount, currency = 'USD') => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(amount)
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Dashboard Financiero</h2>
        <button class="px-4 py-2 bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 hover:bg-indigo-700 text-slate-900 dark:text-white text-sm font-medium rounded-xl transition-colors">
          + Emitir Nuevo Cobro
        </button>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-6 rounded-2xl border bg-white/5 border-white/10 backdrop-blur-md">
          <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Recaudado (Mes actual)</p>
          <p class="text-3xl font-bold text-emerald-400 mt-2">{{ formatMoney(stats.totalCollected) }}</p>
        </div>
        <div class="p-6 rounded-2xl border bg-white/5 border-white/10 backdrop-blur-md">
          <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Cuentas por Cobrar</p>
          <p class="text-3xl font-bold text-yellow-400 mt-2">{{ formatMoney(stats.totalPending) }}</p>
        </div>
        <div class="p-6 rounded-2xl border bg-white/5 border-white/10 backdrop-blur-md">
          <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Alumnos Morosos</p>
          <p class="text-3xl font-bold text-red-400 mt-2">{{ stats.overdueCount }}</p>
        </div>
      </div>

      <!-- Lista de Cobros -->
      <div class="rounded-2xl border bg-white/5 border-white/10 backdrop-blur-md overflow-hidden">
        <div class="p-6 border-b border-white/10">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Últimos Movimientos</h3>
        </div>
        
        <div v-if="isLoading" class="p-12 flex justify-center">
          <div class="w-8 h-8 rounded-full border-4 border-indigo-500 border-t-transparent animate-spin"></div>
        </div>
        
        <div v-else-if="!fees.length" class="p-12 text-center text-slate-500 dark:text-slate-400">
          No hay registros financieros todavía.
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-white/5 text-slate-500 dark:text-slate-400 uppercase font-semibold text-xs">
              <tr>
                <th class="px-6 py-4">Concepto</th>
                <th class="px-6 py-4">Alumno</th>
                <th class="px-6 py-4">Monto</th>
                <th class="px-6 py-4">Vencimiento</th>
                <th class="px-6 py-4">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10 text-slate-700 dark:text-slate-300">
              <tr v-for="fee in fees" :key="fee.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ fee.title }}</td>
                <td class="px-6 py-4">{{ fee.student?.user?.name ?? 'Desconocido' }}</td>
                <td class="px-6 py-4 font-medium">{{ formatMoney(fee.amount, fee.currency) }}</td>
                <td class="px-6 py-4">{{ fee.due_date }}</td>
                <td class="px-6 py-4">
                  <span v-if="fee.status === 'paid'" class="px-2.5 py-1 text-xs font-bold rounded-md bg-emerald-500/20 text-emerald-400">Pagado</span>
                  <span v-else-if="fee.status === 'pending'" class="px-2.5 py-1 text-xs font-bold rounded-md bg-yellow-500/20 text-yellow-400">Pendiente</span>
                  <span v-else-if="fee.status === 'overdue'" class="px-2.5 py-1 text-xs font-bold rounded-md bg-red-500/20 text-red-400">Vencido</span>
                  <span v-else class="px-2.5 py-1 text-xs font-bold rounded-md bg-white/10 text-slate-700 dark:text-slate-300">{{ fee.status }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
