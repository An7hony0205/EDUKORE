<script setup>
import { ref } from 'vue'
import api from '../api/axios'

const props = defineProps({
  fee: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'success'])

const amountPaid = ref(
  (props.fee.amount + props.fee.tax_amount + props.fee.penalty_amount - props.fee.discount_amount).toFixed(2)
)
const paymentMethod = ref('cash')
const reference = ref('')
const isSubmitting = ref(false)
const error = ref(null)

const submitPayment = async () => {
  isSubmitting.value = true
  error.value = null
  try {
    await api.post('/payments', {
      fee_id: props.fee.id,
      amount_paid: amountPaid.value,
      payment_method: paymentMethod.value,
      reference: reference.value
    })
    emit('success')
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al procesar el pago'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-[#1a2035] rounded-2xl border border-white/10 w-full max-w-md shadow-2xl overflow-hidden">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-white/10 flex justify-between items-center bg-white/5">
        <h3 class="text-lg font-semibold text-white">Registrar Pago</h3>
        <button @click="$emit('close')" class="text-slate-400 hover:text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-4">
        <div v-if="error" class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-lg text-rose-400 text-sm">
          {{ error }}
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-400 mb-1">Concepto</label>
          <div class="text-white font-medium">{{ fee.title }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-400 mb-1">Monto a Pagar (USD)</label>
          <input
            v-model="amountPaid"
            type="number"
            step="0.01"
            class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-400 mb-1">Método de Pago</label>
          <select
            v-model="paymentMethod"
            class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
          >
            <option value="cash">Efectivo</option>
            <option value="bank_transfer">Transferencia Bancaria</option>
            <option value="credit_card">Tarjeta de Crédito/Débito</option>
            <option value="check">Cheque</option>
          </select>
        </div>

        <div v-if="paymentMethod !== 'cash'">
          <label class="block text-sm font-medium text-slate-400 mb-1">Referencia / Nro. Transacción</label>
          <input
            v-model="reference"
            type="text"
            class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-2 text-white focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            placeholder="Ej. TX-123456789"
          >
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-white/10 bg-white/5 flex justify-end gap-3">
        <button
          @click="$emit('close')"
          class="px-4 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 transition-colors"
        >
          Cancelar
        </button>
        <button
          @click="submitPayment"
          :disabled="isSubmitting"
          class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
        >
          <svg v-if="isSubmitting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Confirmar Pago
        </button>
      </div>
    </div>
  </div>
</template>
