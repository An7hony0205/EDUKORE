<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const router = useRouter()
const students = ref([])
const loading = ref(true)
const search = ref('')
const sort = ref('recent')
const pagination = ref({ current_page: 1, last_page: 1 })

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const fetchStudents = async (page = 1) => {
  loading.value = true
  try {
    const res = await api.get(`/students?page=${page}&search=${search.value}&sort=${sort.value}`)
    students.value = res.data.data
    pagination.value = res.data.meta
  } catch (error) {
    console.error("Failed to fetch students", error)
  } finally {
    loading.value = false
  }
}

const downloadReport = async (studentId) => {
  try {
    // Petición JSON estándar (IDM no la intercepta)
    const response = await api.get(`/reports/students/${studentId}/progress-report`);

    if (!response.data.success || !response.data.pdf_base64) {
      throw new Error(response.data.error || 'No se pudo generar el documento');
    }

    // Decodificar Base64 a binario
    const byteCharacters = atob(response.data.pdf_base64);
    const byteNumbers = new Array(byteCharacters.length);
    for (let i = 0; i < byteCharacters.length; i++) {
      byteNumbers[i] = byteCharacters.charCodeAt(i);
    }
    const byteArray = new Uint8Array(byteNumbers);
    const blob = new Blob([byteArray], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);

    // Abrir en nueva pestaña o descargar
    const newWindow = window.open(url, '_blank');
    if (!newWindow) {
      const link = document.createElement('a');
      link.href = url;
      link.download = response.data.filename || `Libreta_${studentId}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    setTimeout(() => window.URL.revokeObjectURL(url), 10000);
  } catch (error) {
    console.error('Error detallado descargando reporte:', error);
    alert('Error al generar la libreta oficial: ' + (error.response?.data?.error || error.message));
  }
};

onMounted(() => {
  fetchStudents()
})
</script>

<template>
  <DashboardLayout>
    <div class="max-w-7xl mx-auto space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <svg class="w-6 h-6 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
            Directorio de Estudiantes
          </h2>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Gestiona los perfiles y matrículas del alumnado.</p>
        </div>
        <router-link to="/students/new" class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
          + Registrar Estudiante
        </router-link>
      </div>

      <!-- Buscador y Tabla -->
      <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row gap-4 items-center justify-between">
          <input 
            v-model="search" 
            @input="fetchStudents(1)"
            type="text" 
            placeholder="Buscar por nombre o correo..." 
            class="bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg px-4 py-2 text-slate-900 dark:text-white text-sm w-full sm:max-w-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500"
          >
          <div class="flex items-center gap-2 w-full sm:w-auto">
            <label class="text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">Ordenar:</label>
            <select v-model="sort" @change="fetchStudents(1)" class="bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 w-full sm:w-auto">
              <option value="recent">Más recientes</option>
              <option value="oldest">Más antiguos</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
            <thead class="bg-slate-50 dark:bg-slate-800/50 text-xs uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th scope="col" class="px-6 py-4 font-medium">Estudiante</th>
                <th scope="col" class="px-6 py-4 font-medium">Código / Matrícula</th>
                <th scope="col" class="px-6 py-4 font-medium">Estado</th>
                <th scope="col" class="px-6 py-4 font-medium">Fecha Registro</th>
                <th scope="col" class="px-6 py-4 font-medium text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading" class="border-b border-slate-200 dark:border-slate-800">
                <td colspan="5" class="px-6 py-8 text-center text-slate-500">Cargando directorio...</td>
              </tr>
              <tr v-else-if="students.length === 0" class="border-b border-slate-200 dark:border-slate-800">
                <td colspan="5" class="px-6 py-8 text-center text-slate-500">No se encontraron estudiantes.</td>
              </tr>
              <tr v-else v-for="student in students" :key="student.id" class="border-b border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-primary-600/20 text-primary-400 flex items-center justify-center font-bold text-xs">
                      {{ student.user.name.charAt(0) }}
                    </div>
                    <div>
                      <div class="text-slate-900 dark:text-white font-medium">{{ student.user.name }}</div>
                      <div class="text-xs text-slate-500">{{ student.user.email }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 font-mono text-xs text-slate-500 dark:text-slate-400">{{ student.enrollment_number }}</td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 rounded-full text-[10px] font-medium uppercase tracking-wider bg-emerald-500/10 text-emerald-400" v-if="student.status === 'activo'">Activo</span>
                  <span class="px-2 py-1 rounded-full text-[10px] font-medium uppercase tracking-wider bg-rose-500/10 text-rose-400" v-else-if="student.status === 'retirado'">Retirado</span>
                  <span class="px-2 py-1 rounded-full text-[10px] font-medium uppercase tracking-wider bg-slate-500/10 text-slate-500 dark:text-slate-400" v-else>{{ student.status }}</span>
                </td>
                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ formatDate(student.created_at) }}</td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-3">
                    <button 
                      type="button" 
                      @click.stop.prevent="downloadReport(student.id)" 
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium rounded-lg transition-colors shadow-sm cursor-pointer"
                      title="Generar Libreta Oficial"
                    >
                      <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                      <span>Libreta</span>
                    </button>
                    <router-link :to="`/student/${student.id}`" class="text-primary-400 hover:text-primary-300 text-xs font-medium">Ver Ficha</router-link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Paginación -->
        <div class="p-4 flex items-center justify-between text-sm text-slate-500 dark:text-slate-400 border-t border-slate-200 dark:border-slate-800" v-if="pagination.last_page > 1">
          <span>Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
          <div class="flex gap-2">
            <button @click="fetchStudents(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="px-3 py-1 rounded bg-white/5 hover:bg-white/10 disabled:opacity-50">Anterior</button>
            <button @click="fetchStudents(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="px-3 py-1 rounded bg-white/5 hover:bg-white/10 disabled:opacity-50">Siguiente</button>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
