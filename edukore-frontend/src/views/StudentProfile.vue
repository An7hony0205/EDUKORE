<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../api/axios'
import EnrollmentWizard from '../components/EnrollmentWizard.vue'
import PaymentModal from '../components/PaymentModal.vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const route = useRoute()
const auth = useAuthStore()
const student = ref(null)
const loading = ref(true)
const activeTab = ref('personal')

// Lazy loaded data
const academicData = ref(null)
const financesData = ref(null)

const fetchStudentBase = async () => {
  try {
    const res = await api.get(`/students/${route.params.id}`)
    student.value = res.data.data
  } catch (error) {
    console.error("Failed to fetch student", error)
  } finally {
    loading.value = false
  }
}

const loadTab = async (tab) => {
  activeTab.value = tab
  if (tab === 'academic' && !academicData.value) {
    try {
      const res = await api.get(`/students/${route.params.id}/academic`)
      academicData.value = res.data.data
    } catch (error) {
      console.error("Failed to fetch academic history", error)
    }
  } else if (tab === 'finances' && !financesData.value) {
    try {
      const res = await api.get(`/students/${route.params.id}/finances`)
      financesData.value = res.data
    } catch (error) {
      console.error("Failed to fetch finances data", error)
    }
  }
}

const isWizardOpen = ref(false)
const selectedFeeForPayment = ref(null)

const openPaymentModal = (fee) => {
  selectedFeeForPayment.value = fee
}

const onPaymentSuccess = () => {
  selectedFeeForPayment.value = null
  // Reload finances tab to show updated data
  financesData.value = null
  loadTab('finances')
}

onMounted(() => {
  fetchStudentBase()
})
</script>

<template>
  <DashboardLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      <div v-if="loading" class="text-slate-500">Cargando perfil...</div>
      
      <div v-else-if="student" class="space-y-6">
        <!-- Cabecera -->
        <div class="bg-brand-surface border border-brand-border rounded-2xl p-6 flex items-center gap-6">
          <div class="w-20 h-20 rounded-full bg-primary-600/20 text-primary-400 flex items-center justify-center font-bold text-3xl">
            {{ student.user.name.charAt(0) }}
          </div>
          <div>
            <div class="flex items-center gap-3">
              <h2 class="text-2xl font-bold text-white">{{ student.user.name }}</h2>
              <span class="px-2 py-1 rounded-full text-[10px] font-medium uppercase tracking-wider bg-emerald-500/10 text-emerald-400" v-if="student.status === 'activo'">Activo</span>
            </div>
            <p class="text-slate-400 mt-1 flex items-center gap-4">
              <span><strong class="text-slate-300">ID:</strong> {{ student.enrollment_number }}</span>
              <span><strong class="text-slate-300">Email:</strong> {{ student.user.email }}</span>
            </p>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex space-x-1 bg-brand-surface border border-brand-border p-1 rounded-xl w-full overflow-x-auto">
          <button @click="loadTab('personal')" :class="[activeTab === 'personal' ? 'bg-primary-600 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5']" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">Datos Personales</button>
          <button v-if="auth.user?.tenant?.active_modules?.academic !== false" @click="loadTab('academic')" :class="[activeTab === 'academic' ? 'bg-primary-600 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5']" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">Historial Académico</button>
          <button v-if="auth.user?.tenant?.active_modules?.attendance" @click="loadTab('attendance')" :class="[activeTab === 'attendance' ? 'bg-primary-600 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5']" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">Asistencia</button>
          <button v-if="auth.user?.tenant?.active_modules?.finances" @click="loadTab('finances')" :class="[activeTab === 'finances' ? 'bg-primary-600 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5']" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">Estado Financiero</button>
          <button @click="loadTab('audit')" :class="[activeTab === 'audit' ? 'bg-primary-600 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5']" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">Auditoría</button>
        </div>

        <!-- Tab Content -->
        <div class="bg-brand-surface border border-brand-border rounded-2xl p-6 min-h-[300px]">
          
          <div v-if="activeTab === 'personal'" class="space-y-8">
            <div>
              <h3 class="text-lg font-medium text-white mb-4">Información del Alumno</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div><span class="block text-slate-500 mb-1">Nombre Completo</span><span class="text-slate-300">{{ student.user.name }}</span></div>
                <div><span class="block text-slate-500 mb-1">Fecha de Nacimiento</span><span class="text-slate-300">{{ student.date_of_birth || 'No registrada' }}</span></div>
              </div>
            </div>
            
            <div class="border-t border-brand-border pt-6">
              <h3 class="text-lg font-medium text-white mb-4">Núcleo Familiar (Apoderados)</h3>
              <div v-if="student.parents && student.parents.length > 0" class="space-y-4">
                <div v-for="parent in student.parents" :key="parent.id" class="flex items-center gap-4 p-4 bg-white/5 rounded-xl border border-white/5">
                  <div class="w-10 h-10 rounded-full bg-brand-muted flex items-center justify-center font-medium">{{ parent.user.name.charAt(0) }}</div>
                  <div>
                    <div class="text-white font-medium">{{ parent.user.name }} <span class="text-xs text-primary-400 ml-2">{{ parent.pivot.relationship }}</span></div>
                    <div class="text-xs text-slate-500">{{ parent.user.email }} • {{ parent.phone }}</div>
                  </div>
                </div>
              </div>
              <div v-else class="text-slate-500 text-sm">No hay apoderados registrados.</div>
            </div>
          </div>

          <div v-else-if="activeTab === 'academic'" class="space-y-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-white">Historial de Matrículas</h3>
              <button @click="isWizardOpen = true" class="bg-primary-600 hover:bg-primary-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                + Matricular Alumno
              </button>
            </div>

            <div v-if="academicData === null" class="text-center py-12 text-slate-500">Cargando historial...</div>
            <div v-else-if="academicData.length === 0" class="text-center py-12 text-slate-500">
              No hay matrículas registradas para este estudiante.
            </div>
            <div v-else class="space-y-4">
              <div v-for="enrollment in academicData" :key="enrollment.id" class="p-4 bg-white/5 rounded-xl border border-brand-border flex flex-col md:flex-row justify-between md:items-center gap-4">
                <div>
                  <div class="text-white font-medium">
                    {{ enrollment.section.grade_level.level.name }} - {{ enrollment.section.grade_level.name }} "{{ enrollment.section.name }}"
                  </div>
                  <div class="text-xs text-slate-400 mt-1">
                    Año Académico: <span class="text-slate-300 font-medium">{{ enrollment.section.grade_level.level.academic_year.year_name }}</span>
                  </div>
                </div>
                <div class="flex items-center gap-4">
                  <div class="text-xs text-slate-500 text-right">
                    Matriculado el:<br/>
                    <span class="text-slate-400">{{ new Date(enrollment.enrolled_at).toLocaleDateString() }}</span>
                  </div>
                  <span class="px-2 py-1 rounded-full text-[10px] font-medium uppercase tracking-wider bg-primary-500/10 text-primary-400">
                    {{ enrollment.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="activeTab === 'finances'" class="space-y-6">
            <div v-if="financesData === null" class="text-center py-12 text-slate-500">Cargando estado financiero...</div>
            <div v-else class="space-y-6">
              <!-- Summary row -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white/5 border border-brand-border rounded-xl p-4">
                  <div class="text-slate-400 text-xs mb-1">Total Deuda</div>
                  <div class="text-xl font-bold text-white">${{ financesData.summary.total_expected.toFixed(2) }}</div>
                </div>
                <div class="bg-white/5 border border-brand-border rounded-xl p-4">
                  <div class="text-slate-400 text-xs mb-1">Total Pagado</div>
                  <div class="text-xl font-bold text-emerald-400">${{ financesData.summary.total_paid.toFixed(2) }}</div>
                </div>
                <div class="bg-white/5 border border-brand-border rounded-xl p-4">
                  <div class="text-slate-400 text-xs mb-1">Saldo Pendiente</div>
                  <div class="text-xl font-bold text-primary-400">${{ financesData.summary.balance_due.toFixed(2) }}</div>
                </div>
                <div class="bg-white/5 border border-rose-500/30 rounded-xl p-4">
                  <div class="text-rose-400 text-xs mb-1">Mora / Vencido</div>
                  <div class="text-xl font-bold text-rose-400">${{ financesData.summary.total_overdue.toFixed(2) }}</div>
                </div>
              </div>

              <!-- List of Fees -->
              <div>
                <h3 class="text-lg font-medium text-white mb-4">Obligaciones (Fees)</h3>
                <div v-if="financesData.fees.length === 0" class="text-center py-8 text-slate-500 bg-white/5 rounded-xl border border-brand-border">
                  No hay cargos financieros para este estudiante.
                </div>
                <div v-else class="space-y-3">
                  <div v-for="fee in financesData.fees" :key="fee.id" class="p-4 bg-white/5 rounded-xl border border-brand-border">
                    <div class="flex justify-between items-start mb-2">
                      <div>
                        <h4 class="text-white font-medium">{{ fee.title }}</h4>
                        <span class="text-xs text-slate-400">{{ fee.fee_type?.name ?? 'General' }}</span>
                      </div>
                      <div class="text-right">
                        <div class="text-white font-bold">${{ (fee.amount + fee.tax_amount + fee.penalty_amount - fee.discount_amount).toFixed(2) }}</div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium uppercase tracking-wider"
                          :class="{
                            'bg-emerald-500/10 text-emerald-400': fee.status === 'paid',
                            'bg-amber-500/10 text-amber-400': fee.status === 'partial',
                            'bg-rose-500/10 text-rose-400': fee.status === 'overdue',
                            'bg-slate-500/10 text-slate-400': fee.status === 'pending' || fee.status === 'cancelled',
                          }"
                        >
                          {{ fee.status }}
                        </span>
                      </div>
                    </div>
                    
                    <div class="flex justify-between items-center text-xs mt-3 pt-3 border-t border-brand-border">
                      <div class="text-slate-400">
                        Vencimiento: <span class="text-slate-200">{{ fee.due_date }}</span>
                      </div>
                      <div class="flex gap-2">
                        <!-- Action button to process payment if not fully paid -->
                        <button @click="openPaymentModal(fee)" v-if="fee.status !== 'paid' && fee.status !== 'cancelled'" class="bg-primary-600 hover:bg-primary-500 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors">
                          Registrar Pago
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-12">
            <p class="text-slate-400">Módulo en construcción.</p>
          </div>

        </div>
      </div>
      
      <EnrollmentWizard 
        v-if="isWizardOpen" 
        :student-id="student?.id" 
        @close="isWizardOpen = false" 
        @success="isWizardOpen = false; loadTab('academic')" 
      />
      <PaymentModal
        v-if="selectedFeeForPayment"
        :fee="selectedFeeForPayment"
        @close="selectedFeeForPayment = null"
        @success="onPaymentSuccess"
      />
    </div>
  </DashboardLayout>
</template>
