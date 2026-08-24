<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const sections = ref([])
const periods = ref([])
const selectedSection = ref('')
const selectedPeriod = ref('')
const isGenerating = ref(false)

const loadData = async () => {
    try {
        const [secRes, perRes] = await Promise.all([
            api.get('/sections'),
            api.get('/academic-periods')
        ])
        sections.value = secRes.data
        periods.value = perRes.data
    } catch (e) {
        console.error(e)
    }
}

onMounted(loadData)

const generateBulkPdfs = async () => {
    if (!selectedSection.value) return
    isGenerating.value = true
    try {
        const url = `/reports/section/${selectedSection.value}/report-cards`
        const res = await api.get(url, { 
            params: { academic_period_id: selectedPeriod.value },
            responseType: 'blob' 
        })
        
        // Create download link for the blob
        const blob = new Blob([res.data], { type: 'application/pdf' })
        const downloadUrl = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = downloadUrl
        link.setAttribute('download', `ReportCards_Section_${selectedSection.value}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (e) {
        console.error("Failed to generate PDFs", e)
        alert("Error generando los PDFs masivos")
    } finally {
        isGenerating.value = false
    }
}
</script>

<template>
  <DashboardLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Generación de Reportes</h1>
      </div>

      <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl p-8 max-w-2xl">
        <h2 class="text-xl font-medium text-slate-900 dark:text-white mb-6">Generar Libretas Masivas (PDF)</h2>
        
        <div class="space-y-6">
            <div>
                <label class="block text-sm text-slate-500 dark:text-slate-400 mb-2">Seleccionar Sección</label>
                <select v-model="selectedSection" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg p-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-slate-500 dark:focus:ring-slate-400 outline-none">
                    <option value="" disabled>-- Seleccione una sección --</option>
                    <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }} (Grado: {{ s.grade_level?.name }})</option>
                </select>
            </div>

            <div>
                <label class="block text-sm text-slate-500 dark:text-slate-400 mb-2">Periodo Académico (Opcional - Para filtrar evaluaciones)</label>
                <select v-model="selectedPeriod" class="w-full bg-white/5 border border-slate-200 dark:border-slate-800 rounded-lg p-3 text-slate-900 dark:text-white focus:ring-2 focus:ring-slate-500 dark:focus:ring-slate-400 outline-none">
                    <option value="">Consolidado Anual</option>
                    <option v-for="p in periods" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </div>

            <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                <button 
                  @click="generateBulkPdfs" 
                  :disabled="!selectedSection || isGenerating"
                  class="w-full bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed text-slate-900 dark:text-white font-bold py-3 rounded-xl transition-colors flex justify-center items-center gap-2"
                >
                    <svg v-if="isGenerating" class="animate-spin h-5 w-5 text-slate-900 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ isGenerating ? 'Generando PDF Masivo...' : 'Generar e Imprimir PDFs' }}</span>
                </button>
            </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
