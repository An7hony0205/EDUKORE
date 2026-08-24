<script setup>
import { ref } from 'vue'
import api from '../../api/axios'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const isGeneratingPdf = ref(false)
const isExportingCsv = ref(false)
const studentId = ref('034477bd-5469-4857-b1d6-c1a4cf55e1e7') // Hardcoded demo student

const generateReportCard = async () => {
  if (!studentId.value) return alert('Ingresa un ID de estudiante')
  if (isGeneratingPdf.value) return
  
  isGeneratingPdf.value = true
  try {
    const res = await api.get(`/reports/student-report-card/${studentId.value}/export`)
        const byteCharacters = atob(res.data.file_data)
    const byteNumbers = new Array(byteCharacters.length)
    for (let i = 0; i < byteCharacters.length; i++) {
      byteNumbers[i] = byteCharacters.charCodeAt(i)
    }
    const byteArray = new Uint8Array(byteNumbers)
    const blob = new Blob([byteArray], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `boletin_${studentId.value}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error(error)
    alert('Error capturado: ' + error.message)
  } finally {
    isGeneratingPdf.value = false
  }
}

const exportEnrollments = async () => {
  if (isExportingCsv.value) return
  isExportingCsv.value = true
  try {
    const res = await api.get(`/reports/enrollments/csv`)
        const byteCharacters = atob(res.data.file_data)
    const byteNumbers = new Array(byteCharacters.length)
    for (let i = 0; i < byteCharacters.length; i++) {
      byteNumbers[i] = byteCharacters.charCodeAt(i)
    }
    const byteArray = new Uint8Array(byteNumbers)
    const blob = new Blob([byteArray], { type: 'text/csv' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'matriculas_export.csv')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error(error)
    alert('Error al exportar matrículas.')
  } finally {
    isExportingCsv.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="h-full flex flex-col space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Reportes y Exportación</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Genera documentos oficiales y exporta datos de la institución.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Boletín PDF -->
        <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 p-6 rounded-xl shadow-lg flex flex-col">
          <div class="flex items-center gap-3 mb-4">
            <div class="p-3 bg-red-500/10 rounded-lg text-red-400">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">Libreta de Notas (Boletín)</h2>
              <p class="text-xs text-slate-500 dark:text-slate-400">Exporta las calificaciones en PDF</p>
            </div>
          </div>
                    <div class="mt-auto space-y-4">
              <div>
                <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">ID del Estudiante</label>
                <input v-model="studentId" type="text" class="w-full bg-white border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500 rounded-lg px-3 py-2" placeholder="Ej: 034477bd-..." />
              </div>
              <button @click="generateReportCard" :disabled="isGeneratingPdf" class="w-full bg-red-600 hover:bg-red-500 text-white py-2 rounded-lg font-medium transition disabled:opacity-50">
                <span v-if="isGeneratingPdf">Generando...</span>
                <span v-else>Generar PDF</span>
              </button>
            </div>
          </div>
  
          <!-- Matrículas CSV -->
          <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 p-6 rounded-xl shadow-lg flex flex-col">
            <div class="flex items-center gap-3 mb-4">
              <div class="p-3 bg-emerald-500/10 rounded-lg text-emerald-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
              </div>
              <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Reporte de Matrículas</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Exporta la lista de alumnos a Excel/CSV</p>
              </div>
            </div>
            
            <div class="mt-auto">
              <button @click="exportEnrollments" :disabled="isExportingCsv" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white py-2 rounded-lg font-medium transition disabled:opacity-50">
                <span v-if="isExportingCsv">Exportando...</span>
                <span v-else>Exportar CSV</span>
              </button>
          </div>
        </div>

      </div>
    </div>
  </DashboardLayout>
</template>
