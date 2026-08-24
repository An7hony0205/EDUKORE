<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/axios'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const periods = ref([])
const newPeriod = ref({ name: '', start_date: '', end_date: '', academic_year_id: '' })
const academicYears = ref([])

const loadData = async () => {
    try {
        const [yearsRes, periodsRes] = await Promise.all([
            api.get('/academic-years'),
            api.get('/academic-periods')
        ])
        academicYears.value = yearsRes.data
        periods.value = periodsRes.data
        if (academicYears.value.length > 0) {
            newPeriod.value.academic_year_id = academicYears.value[0].id
        }
    } catch (e) {
        console.error(e)
    }
}

onMounted(loadData)

const createPeriod = async () => {
    try {
        await api.post('/academic-periods', newPeriod.value)
        newPeriod.value.name = ''
        loadData()
    } catch (e) {
        console.error(e)
        alert('Error al crear el periodo. Verifica que has llenado todos los campos correctamente, incluyendo el Año Académico.')
    }
}

const toggleLock = async (id) => {
    try {
        await api.patch(`/academic-periods/${id}/toggle-lock`)
        loadData()
    } catch (e) {
        console.error(e)
    }
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Periodos Académicos</h1>
      </div>

      <!-- Form -->
      <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl p-6">
        <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Nuevo Periodo</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div>
            <label class="block text-sm text-slate-500 dark:text-slate-400 mb-1">Año Académico</label>
            <select v-model="newPeriod.academic_year_id" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-slate-900 dark:text-white outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500">
              <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.year_name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-slate-500 dark:text-slate-400 mb-1">Nombre (Ej: Bimestre 1)</label>
            <input type="text" v-model="newPeriod.name" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-slate-900 dark:text-white outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500" />
          </div>
          <div>
            <label class="block text-sm text-slate-500 dark:text-slate-400 mb-1">Inicio</label>
            <input type="date" v-model="newPeriod.start_date" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-slate-700 dark:text-slate-300 [color-scheme:dark] outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500" />
          </div>
          <div>
            <label class="block text-sm text-slate-500 dark:text-slate-400 mb-1">Fin</label>
            <div class="flex gap-2">
              <input type="date" v-model="newPeriod.end_date" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg p-2 text-slate-700 dark:text-slate-300 [color-scheme:dark] outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500" />
              <button @click="createPeriod" class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 px-4 py-2 rounded-lg transition-colors">
                  Crear
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- List -->
      <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl overflow-hidden">
        <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
          <thead class="bg-slate-50 dark:bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
            <tr>
              <th class="px-6 py-4">Nombre</th>
              <th class="px-6 py-4">Fechas</th>
              <th class="px-6 py-4 text-center">Estado (Lock)</th>
              <th class="px-6 py-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-brand-border">
            <tr v-for="p in periods" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-white/5">
              <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ p.name }}</td>
              <td class="px-6 py-4">{{ p.start_date }} a {{ p.end_date }}</td>
              <td class="px-6 py-4 text-center">
                  <span v-if="p.is_locked" class="px-2 py-1 bg-rose-500/10 text-rose-400 rounded-md text-xs font-bold border border-rose-500/20">
                      CERRADO (LOCKED)
                  </span>
                  <span v-else class="px-2 py-1 bg-emerald-500/10 text-emerald-400 rounded-md text-xs font-bold border border-emerald-500/20">
                      ABIERTO
                  </span>
              </td>
              <td class="px-6 py-4 text-right">
                  <button @click="toggleLock(p.id)" class="text-sm px-3 py-1 rounded border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-white/10 transition-colors">
                      {{ p.is_locked ? 'Desbloquear' : 'Cerrar Notas' }}
                  </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </DashboardLayout>
</template>
