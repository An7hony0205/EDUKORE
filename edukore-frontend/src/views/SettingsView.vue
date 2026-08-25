<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const settings = ref({
  timezone: 'UTC',
  grading_scale: 'numeric_20',
  tax_percentage: 0,
  currency_default: 'USD',
  logo_url: null
})

const isLoading = ref(true)
const isSaving = ref(false)
const selectedFile = ref(null)
const previewUrl = ref(null)

onMounted(async () => {
  try {
    const res = await api.get('/settings')
    if (res.data) {
      Object.keys(res.data).forEach(key => {
        if (res.data[key] !== null && res.data[key] !== undefined) {
          settings.value[key] = res.data[key]
        }
      })
      if (settings.value.logo_url) {
        previewUrl.value = `http://localhost:8000/storage/${settings.value.logo_url}`
      }
    }
  } catch (err) {
    console.error('Error loading settings', err)
  } finally {
    isLoading.value = false
  }
})

const handleFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    selectedFile.value = file
    previewUrl.value = URL.createObjectURL(file)
  }
}

const saveSettings = async () => {
  isSaving.value = true
  try {
    const formData = new FormData()
    formData.append('_method', 'PUT')
    
    formData.append('timezone', settings.value.timezone || '')
    formData.append('grading_system', settings.value.grading_system || 'competency')
    
    if (settings.value.tax_percentage !== null && settings.value.tax_percentage !== undefined) {
      formData.append('tax_percentage', settings.value.tax_percentage)
    }
    formData.append('currency_default', settings.value.currency_default || '')
    
    if (selectedFile.value) {
      formData.append('logo', selectedFile.value)
    }

    const res = await api.post('/settings', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    settings.value = res.data
    
    alert('Configuración guardada exitosamente.')
  } catch (err) {
    console.error('Error saving settings', err)
    alert('Ocurrió un error al guardar.')
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <div>
        <h2 class="text-2xl font-bold dark:text-white text-slate-900">Configuración Institucional</h2>
        <p class="dark:text-slate-400 text-slate-500 text-sm mt-1">Personaliza la identidad visual y parámetros base de tu colegio.</p>
      </div>

      <div v-if="isLoading" class="flex justify-center p-12">
        <div class="w-8 h-8 rounded-full border-4 border-slate-900 dark:border-white border-t-transparent dark:border-t-transparent animate-spin"></div>
      </div>

      <form v-else @submit.prevent="saveSettings" class="space-y-6">
        
        <!-- Panel de Identidad -->
        <div class="p-6 rounded-2xl border dark:bg-brand-surface dark:border-brand-border bg-white border-slate-200">
          <h3 class="text-lg font-semibold dark:text-white text-slate-900 mb-6">Identidad Visual</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
              <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Logotipo de la Institución</label>
              <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl dark:bg-white/5 dark:border-brand-border bg-slate-100 border border-slate-200 flex items-center justify-center overflow-hidden">
                  <img v-if="previewUrl" :src="previewUrl" class="w-full h-full object-cover" />
                  <svg v-else class="w-8 h-8 dark:text-slate-400 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <input type="file" @change="handleFileChange" accept="image/*" class="text-sm dark:text-slate-400 text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold dark:file:bg-white/10 dark:file:text-white file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:hover:file:bg-white/20 transition-colors" />
              </div>
            </div>
          </div>
        </div>

        <!-- Panel Académico -->
        <div class="p-6 rounded-2xl border dark:bg-brand-surface dark:border-brand-border bg-white border-slate-200">
          <h3 class="text-lg font-semibold dark:text-white text-slate-900 mb-6">Parámetros Operativos</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Huso Horario</label>
              <select v-model="settings.timezone" class="w-full dark:bg-white/5 dark:border-brand-border dark:text-white bg-slate-50 border-slate-200 text-slate-900 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-slate-900 dark:focus:ring-white outline-none">
                <option value="UTC" class="dark:bg-slate-800 bg-white">UTC</option>
                <option value="America/Lima" class="dark:bg-slate-800 bg-white">America/Lima</option>
                <option value="America/Bogota" class="dark:bg-slate-800 bg-white">America/Bogota</option>
                <option value="America/Mexico_City" class="dark:bg-slate-800 bg-white">America/Mexico_City</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Modelo de Calificaciones Institucional</label>
              <select v-model="settings.grading_system" class="w-full dark:bg-white/5 dark:border-brand-border dark:text-white bg-slate-50 border-slate-200 text-slate-900 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-slate-900 dark:focus:ring-white outline-none">
                <option value="competency" class="dark:bg-slate-800 bg-white">Por Competencias CNEB / Literal (AD, A, B, C)</option>
                <option value="numeric" class="dark:bg-slate-800 bg-white">Vigesimal Numérico (0-20)</option>
              </select>
            </div>
          </div>
          <p class="mt-4 text-xs text-slate-500">Este cambio afectará cómo los docentes registran las calificaciones en sus cursos.</p>
        </div>

        <!-- Panel Financiero -->
        <div class="p-6 rounded-2xl border dark:bg-brand-surface dark:border-brand-border bg-white border-slate-200">
          <h3 class="text-lg font-semibold dark:text-white text-slate-900 mb-6">Parámetros Financieros</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Moneda Predeterminada</label>
              <select v-model="settings.currency_default" class="w-full dark:bg-white/5 dark:border-brand-border dark:text-white bg-slate-50 border-slate-200 text-slate-900 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-slate-900 dark:focus:ring-white outline-none">
                <option value="USD" class="dark:bg-slate-800 bg-white">Dólar Estadounidense (USD)</option>
                <option value="PEN" class="dark:bg-slate-800 bg-white">Sol Peruano (PEN)</option>
                <option value="MXN" class="dark:bg-slate-800 bg-white">Peso Mexicano (MXN)</option>
                <option value="COP" class="dark:bg-slate-800 bg-white">Peso Colombiano (COP)</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium dark:text-slate-300 text-slate-700 mb-2">Impuesto Base (%)</label>
              <input type="number" step="0.01" v-model="settings.tax_percentage" class="w-full dark:bg-white/5 dark:border-brand-border dark:text-white bg-slate-50 border-slate-200 text-slate-900 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-slate-900 dark:focus:ring-white outline-none" />
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <button type="submit" :disabled="isSaving" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-gray-100 dark:text-slate-900 disabled:opacity-50 text-white font-medium rounded-xl transition-colors flex items-center gap-2">
            <span v-if="isSaving" class="w-4 h-4 rounded-full border-2 border-white/50 dark:border-slate-900/50 border-t-white dark:border-t-slate-900 animate-spin"></span>
            Guardar Configuración
          </button>
        </div>

      </form>
    </div>
  </DashboardLayout>
</template>
