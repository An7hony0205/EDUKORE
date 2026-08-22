<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../../api/axios'

const fees = ref([])
const students = ref([])
const tenant = ref(null)
const isLoading = ref(true)
const isSubmitting = ref(false)

const showModal = ref(false)
const formData = ref({
  student_id: '',
  title: '',
  amount: '',
  due_date: '',
  category: 'ACADEMIC'
})

const fetchFinances = async () => {
  try {
    const res = await api.get('/fees')
    fees.value = res.data.fees.data
    students.value = res.data.students
    tenant.value = res.data.tenant
  } catch (error) {
    console.error(error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchFinances()
})

const isPublic = computed(() => tenant.value?.institution_type === 'PUBLIC')

const openModal = () => {
  formData.value = {
    student_id: students.value[0]?.id || '',
    title: '',
    amount: '',
    due_date: '',
    category: isPublic.value ? 'COMMUNITY' : 'ACADEMIC'
  }
  showModal.value = true
}

const submitFee = async () => {
  isSubmitting.value = true
  try {
    await api.post('/fees', formData.value)
    showModal.value = false
    await fetchFinances()
  } catch (error) {
    if (error.response?.status === 403) {
      alert(error.response.data.message)
    } else {
      alert('Error al crear el cobro')
    }
  } finally {
    isSubmitting.value = false
  }
}

const payFee = async (feeId) => {
  try {
    await api.post('/payments', {
      fee_id: feeId,
      amount_paid: fees.value.find(f => f.id === feeId).amount,
      payment_method: 'TRANSFER'
    })
    fetchFinances()
  } catch (error) {
    alert("Error procesando el pago")
  }
}
</script>

<template>
  <div class="h-full flex flex-col space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Finanzas y Cobros</h1>
        <p class="text-slate-400 text-sm mt-1">
          Modo: <span class="font-bold" :class="isPublic ? 'text-amber-400' : 'text-emerald-400'">
            {{ isPublic ? 'COLEGIO PÚBLICO (Sin pensión)' : 'COLEGIO PRIVADO (Regular)' }}
          </span>
        </p>
      </div>
      <button @click="openModal" class="bg-primary-600 hover:bg-primary-500 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-lg shadow-primary-500/20">
        + Emitir Nuevo Cobro
      </button>
    </div>

    <!-- Table -->
    <div class="bg-brand-surface border border-brand-border rounded-xl overflow-hidden shadow-xl">
      <table class="min-w-full divide-y divide-brand-border">
        <thead class="bg-brand-muted">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Concepto</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Estudiante</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Tipo</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Monto</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Vencimiento</th>
            <th class="px-6 py-4 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Estado</th>
            <th class="px-6 py-4 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Acción</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-brand-border">
          <tr v-if="isLoading">
            <td colspan="7" class="px-6 py-8 text-center text-slate-400">Cargando datos financieros...</td>
          </tr>
          <tr v-else-if="fees.length === 0">
            <td colspan="7" class="px-6 py-8 text-center text-slate-400">No hay cobros emitidos.</td>
          </tr>
          <tr v-for="fee in fees" :key="fee.id" class="hover:bg-white/[0.02]">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-white font-medium">{{ fee.title }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-300">{{ fee.student?.user?.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <span class="px-2 py-1 rounded-md text-[10px] font-bold" 
                :class="fee.category === 'ACADEMIC' ? 'bg-indigo-500/20 text-indigo-400' : 'bg-amber-500/20 text-amber-400'">
                {{ fee.category === 'ACADEMIC' ? 'Académico' : 'Comunitario' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-300">S/ {{ fee.amount }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ fee.due_date }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="px-2 py-1 rounded-full text-xs font-medium" 
                :class="fee.status === 'paid' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'">
                {{ fee.status === 'paid' ? 'Pagado' : 'Pendiente' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
              <button v-if="fee.status !== 'paid'" @click="payFee(fee.id)" class="text-primary-400 hover:text-primary-300 font-medium">
                Simular Pago
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Nuevo Cobro -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
      <div class="bg-brand-surface border border-brand-border rounded-xl w-full max-w-md p-6 shadow-2xl">
        <h2 class="text-xl font-bold text-white mb-4">Emitir Nuevo Cobro</h2>
        
        <form @submit.prevent="submitFee" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1">Estudiante</label>
            <select v-model="formData.student_id" required class="w-full bg-slate-900 border border-brand-border rounded-lg px-4 py-2 text-white focus:border-primary-500 outline-none">
              <option v-for="s in students" :key="s.id" :value="s.id">{{ s.user?.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1">Concepto</label>
            <input type="text" v-model="formData.title" required placeholder="Ej: Pensión Marzo o APAFA" class="w-full bg-slate-900 border border-brand-border rounded-lg px-4 py-2 text-white focus:border-primary-500 outline-none">
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Monto (S/)</label>
              <input type="number" v-model="formData.amount" required min="0" step="0.01" class="w-full bg-slate-900 border border-brand-border rounded-lg px-4 py-2 text-white focus:border-primary-500 outline-none">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-1">Vencimiento</label>
              <input type="date" v-model="formData.due_date" required class="w-full bg-slate-900 border border-brand-border rounded-lg px-4 py-2 text-white focus:border-primary-500 outline-none">
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1">Tipo de Deuda</label>
            <select v-model="formData.category" required :disabled="isPublic" class="w-full bg-slate-900 border border-brand-border rounded-lg px-4 py-2 text-white focus:border-primary-500 outline-none disabled:opacity-50">
              <option value="ACADEMIC" v-if="!isPublic">Académico (Pensiones, Matrículas)</option>
              <option value="COMMUNITY">Comunitario (APAFA, Faenas)</option>
            </select>
            <p v-if="isPublic" class="text-xs text-amber-500 mt-1">En colegios públicos, las pensiones están bloqueadas por ley.</p>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="showModal = false" class="px-4 py-2 text-slate-300 hover:text-white transition-colors">Cancelar</button>
            <button type="submit" :disabled="isSubmitting" class="bg-primary-600 hover:bg-primary-500 text-white px-6 py-2 rounded-lg font-medium disabled:opacity-50">
              {{ isSubmitting ? 'Guardando...' : 'Emitir Cobro' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
