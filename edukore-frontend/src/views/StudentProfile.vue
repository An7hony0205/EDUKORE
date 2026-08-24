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
const attendanceData = ref(null)
const auditData = ref(null)

const fetchStudentBase = async () => {
  try {
    const response = await api.get(`/students/${route.params.id}`)
    student.value = response.data.data
  } catch (error) {
    console.error("Error loading student data", error)
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
  } else if (tab === 'attendance' && !attendanceData.value) {
    try {
      const res = await api.get(`/students/${route.params.id}/attendance`)
      attendanceData.value = res.data.data
    } catch (error) {
      console.error("Failed to fetch attendance data", error)
    }
  } else if (tab === 'audit' && !auditData.value) {
    try {
      const res = await api.get(`/students/${route.params.id}/audit`)
      auditData.value = res.data.data
    } catch (error) {
      console.error("Failed to fetch audit data", error)
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
        <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl p-6 flex items-center gap-6">
          <div class="w-20 h-20 rounded-full bg-primary-600/20 text-primary-400 flex items-center justify-center font-bold text-3xl">
            {{ student.user.name.charAt(0) }}
          </div>
          <div>
            <div class="flex items-center gap-3">
              <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ student.user.name }}</h2>
              <span class="px-2 py-1 rounded-full text-[10px] font-medium uppercase tracking-wider bg-emerald-500/10 text-emerald-400" v-if="student.status === 'activo'">Activo</span>
            </div>
            <p class="text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-4">
              <span><strong class="text-slate-700 dark:text-slate-300">ID:</strong> {{ student.enrollment_number }}</span>
              <span><strong class="text-slate-700 dark:text-slate-300">Email:</strong> {{ student.user.email }}</span>
            </p>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex space-x-1 bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 p-1 rounded-xl w-full overflow-x-auto">
          <button @click="loadTab('personal')" :class="[activeTab === 'personal' ? 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-white/5']" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">Datos Personales</button>
          <button v-if="auth.user?.tenant?.active_modules?.academic !== false" @click="loadTab('academic')" :class="[activeTab === 'academic' ? 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-white/5']" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">Historial Académico</button>
          <button v-if="auth.user?.tenant?.active_modules?.attendance" @click="loadTab('attendance')" :class="[activeTab === 'attendance' ? 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-white/5']" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">Asistencia</button>
          <button v-if="auth.user?.tenant?.active_modules?.finances" @click="loadTab('finances')" :class="[activeTab === 'finances' ? 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-white/5']" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">Estado Financiero</button>
          <button @click="loadTab('audit')" :class="[activeTab === 'audit' ? 'bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-white/5']" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">Auditoría</button>
        </div>

        <!-- Tab Content -->
        <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl p-6 min-h-[300px]">
          
          <div v-if="activeTab === 'personal'" class="space-y-8">
            <div>
              <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Información del Alumno</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                <div><span class="block text-slate-500 mb-1">Nombre Completo</span><span class="text-slate-700 dark:text-slate-300">{{ student.user.name }}</span></div>
                <div><span class="block text-slate-500 mb-1">Fecha de Nacimiento</span><span class="text-slate-700 dark:text-slate-300">{{ student.date_of_birth || 'No registrada' }}</span></div>
              </div>
            </div>
            
            <div class="border-t border-slate-200 dark:border-slate-800 pt-6">
              <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Núcleo Familiar (Apoderados)</h3>
              <div v-if="student.parents && student.parents.length > 0" class="space-y-4">
                <div v-for="parent in student.parents" :key="parent.id" class="flex items-center gap-4 p-4 bg-white/5 rounded-xl border border-white/5">
                  <div class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center font-medium">{{ parent.user.name.charAt(0) }}</div>
                  <div>
                    <div class="text-slate-900 dark:text-white font-medium">{{ parent.user.name }} <span class="text-xs text-primary-400 ml-2">{{ parent.pivot.relationship_type }}</span></div>
                    <div class="text-xs text-slate-500">{{ parent.user.email }} • {{ parent.phone }}</div>
                  </div>
                </div>
              </div>
              <div v-else class="text-slate-500 text-sm">No hay apoderados registrados.</div>
            </div>
          </div>

          <div v-else-if="activeTab === 'academic'" class="space-y-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-slate-900 dark:text-white">Historial de Matrículas</h3>
              <button @click="isWizardOpen = true" class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                + Matricular Alumno
              </button>
            </div>

            <div v-if="academicData === null" class="text-center py-12 text-slate-500">Cargando historial...</div>
            <div v-else-if="academicData.length === 0" class="text-center py-12 text-slate-500">
              No hay matrículas registradas para este estudiante.
            </div>
            <div v-else class="space-y-4">
              <div v-for="enrollment in academicData" :key="enrollment.id" class="p-4 bg-white/5 rounded-xl border border-slate-200 dark:border-slate-800 flex flex-col md:flex-row justify-between md:items-center gap-4">
                <div>
                  <div class="text-slate-900 dark:text-white font-medium">
                    {{ enrollment.section.grade_level.level.name }} - {{ enrollment.section.grade_level.name }} "{{ enrollment.section.name }}"
                  </div>
                  <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Año Académico: <span class="text-slate-700 dark:text-slate-300 font-medium">{{ enrollment.section.grade_level.level.academic_year.year_name }}</span>
                  </div>
                </div>
                <div class="flex items-center gap-4">
                  <div class="text-xs text-slate-500 text-right">
                    Matriculado el:<br/>
                    <span class="text-slate-500 dark:text-slate-400">{{ new Date(enrollment.created_at).toLocaleDateString() }}</span>
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
                <div class="bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                  <div class="text-slate-500 dark:text-slate-400 text-xs mb-1">Total Deuda</div>
                  <div class="text-xl font-bold text-slate-900 dark:text-white">${{ financesData.summary.total_expected.toFixed(2) }}</div>
                </div>
                <div class="bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                  <div class="text-slate-500 dark:text-slate-400 text-xs mb-1">Total Pagado</div>
                  <div class="text-xl font-bold text-emerald-400">${{ financesData.summary.total_paid.toFixed(2) }}</div>
                </div>
                <div class="bg-white/5 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
                  <div class="text-slate-500 dark:text-slate-400 text-xs mb-1">Saldo Pendiente</div>
                  <div class="text-xl font-bold text-primary-400">${{ financesData.summary.balance_due.toFixed(2) }}</div>
                </div>
                <div class="bg-white/5 border border-rose-500/30 rounded-xl p-4">
                  <div class="text-rose-400 text-xs mb-1">Mora / Vencido</div>
                  <div class="text-xl font-bold text-rose-400">${{ financesData.summary.total_overdue.toFixed(2) }}</div>
                </div>
              </div>

              <!-- List of Fees -->
              <div>
                <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-4">Obligaciones (Fees)</h3>
                <div v-if="financesData.fees.length === 0" class="text-center py-8 text-slate-500 bg-white/5 rounded-xl border border-slate-200 dark:border-slate-800">
                  No hay cargos financieros para este estudiante.
                </div>
                <div v-else class="space-y-3">
                  <div v-for="fee in financesData.fees" :key="fee.id" class="p-4 bg-white/5 rounded-xl border border-slate-200 dark:border-slate-800">
                    <div class="flex justify-between items-start mb-2">
                      <div>
                        <h4 class="text-slate-900 dark:text-white font-medium">{{ fee.title }}</h4>
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ fee.fee_type?.name ?? 'General' }}</span>
                      </div>
                      <div class="text-right">
                        <div class="text-slate-900 dark:text-white font-bold">${{ (fee.amount + fee.tax_amount + fee.penalty_amount - fee.discount_amount).toFixed(2) }}</div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium uppercase tracking-wider"
                          :class="{
                            'bg-emerald-500/10 text-emerald-400': fee.status === 'paid',
                            'bg-amber-500/10 text-amber-400': fee.status === 'partial',
                            'bg-rose-500/10 text-rose-400': fee.status === 'overdue',
                            'bg-slate-500/10 text-slate-500 dark:text-slate-400': fee.status === 'pending' || fee.status === 'cancelled',
                          }"
                        >
                          {{ fee.status }}
                        </span>
                      </div>
                    </div>
                    
                    <div class="flex justify-between items-center text-xs mt-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                      <div class="text-slate-500 dark:text-slate-400">
                        Vencimiento: <span class="text-slate-200">{{ fee.due_date }}</span>
                      </div>
                      <div class="flex gap-2">
                        <!-- Action button to process payment if not fully paid -->
                        <button @click="openPaymentModal(fee)" v-if="fee.status !== 'paid' && fee.status !== 'cancelled'" class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 px-3 py-1.5 rounded text-xs font-medium transition-colors">
                          Registrar Pago
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="activeTab === 'attendance'">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium text-slate-900 dark:text-white">Registro de Asistencia</h3>
            </div>
            
            <div v-if="attendanceData === null" class="text-slate-500">Cargando...</div>
            <div v-else-if="attendanceData.length === 0" class="text-slate-500 dark:text-slate-400 bg-white/5 p-6 rounded-lg text-center border border-white/5">
              No hay registros de asistencia para este estudiante.
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-white/10 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <th class="py-3 px-4 font-medium">Fecha</th>
                    <th class="py-3 px-4 font-medium">Curso</th>
                    <th class="py-3 px-4 font-medium">Estado</th>
                    <th class="py-3 px-4 font-medium">Notas</th>
                  </tr>
                </thead>
                <tbody class="text-sm">
                  <tr v-for="att in attendanceData" :key="att.id" class="border-b border-white/5 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                    <td class="py-3 px-4 text-slate-700 dark:text-slate-300">{{ att.date }}</td>
                    <td class="py-3 px-4 text-slate-900 dark:text-white font-medium">{{ att.course_assignment?.course?.name || 'Desconocido' }}</td>
                    <td class="py-3 px-4">
                      <span :class="{
                        'bg-emerald-500/10 text-emerald-400': att.status === 'Presente',
                        'bg-rose-500/10 text-rose-400': att.status === 'Ausente',
                        'bg-amber-500/10 text-amber-400': att.status === 'Tardanza',
                        'bg-blue-500/10 text-blue-400': att.status === 'Justificado'
                      }" class="px-2 py-1 rounded-full text-xs font-medium uppercase tracking-wider">
                        {{ att.status }}
                      </span>
                    </td>
                    <td class="py-3 px-4 text-slate-500 dark:text-slate-400">{{ att.notes || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <div v-else-if="activeTab === 'audit'">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium text-slate-900 dark:text-white">Auditoria y Log de Actividad</h3>
            </div>
            
            <div v-if="auditData === null" class="text-slate-500">Cargando registros...</div>
            <div v-else-if="auditData.length === 0" class="text-slate-500 dark:text-slate-400 bg-white/5 p-6 rounded-lg text-center border border-white/5">
              No hay registros de auditoria para este estudiante.
            </div>
            <div v-else class="space-y-4">
              <div v-for="log in auditData" :key="log.id" class="p-4 bg-white/5 rounded-xl border border-white/10">
                <div class="flex justify-between items-start mb-2">
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-xs font-medium uppercase" :class="{
                      'bg-emerald-500/10 text-emerald-400': log.event === 'created',
                      'bg-amber-500/10 text-amber-400': log.event === 'updated',
                      'bg-rose-500/10 text-rose-400': log.event === 'deleted'
                    }">{{ log.event }}</span>
                    <span class="text-slate-900 dark:text-white font-medium">{{ log.description }}</span>
                  </div>
                  <span class="text-xs text-slate-500">{{ new Date(log.created_at).toLocaleString() }}</span>
                </div>
                <div class="text-sm text-slate-500 dark:text-slate-400 mb-2">
                  Por: <span class="text-slate-700 dark:text-slate-300 font-medium">{{ log.causer?.name || 'Sistema' }}</span>
                </div>
                
                <div v-if="log.properties && Object.keys(log.properties).length > 0" class="mt-3 p-3 bg-black/20 rounded border border-white/5 overflow-x-auto">
                  <div v-if="log.properties.old" class="mb-2">
                    <div class="text-xs text-slate-500 mb-1 uppercase tracking-wider font-semibold">Valores Anteriores</div>
                    <pre class="text-xs text-rose-300 m-0">{{ JSON.stringify(log.properties.old, null, 2) }}</pre>
                  </div>
                  <div v-if="log.properties.attributes">
                    <div class="text-xs text-slate-500 mb-1 uppercase tracking-wider font-semibold">Nuevos Valores</div>
                    <pre class="text-xs text-emerald-300 m-0">{{ JSON.stringify(log.properties.attributes, null, 2) }}</pre>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-12">
            <p class="text-slate-500 dark:text-slate-400">Módulo en construcción.</p>
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
