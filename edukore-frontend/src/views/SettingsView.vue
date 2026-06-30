<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/axios'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const settings = ref({
  theme_color: '#4f46e5',
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
    settings.value = res.data
    if (settings.value.logo_url) {
      previewUrl.value = `http://localhost:8000/storage/${settings.value.logo_url}`
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
    formData.append('theme_color', settings.value.theme_color)
    formData.append('timezone', settings.value.timezone)
    formData.append('grading_scale', settings.value.grading_scale)
    formData.append('tax_percentage', settings.value.tax_percentage)
    formData.append('currency_default', settings.value.currency_default)
    
    if (selectedFile.value) {
      formData.append('logo', selectedFile.value)
    }

    const res = await api.post('/settings', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    settings.value = res.data
    
    alert('Configuración guardada exitosamente. Se recomienda refrescar la página.')
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
        <h2 class="text-2xl font-bold text-white">Configuración Institucional</h2>
        <p class="text-slate-400 text-sm mt-1">Personaliza la identidad visual y parámetros base de tu colegio.</p>
      </div>

      <div v-if="isLoading" class="flex justify-center p-12">
        <div class="w-8 h-8 rounded-full border-4 border-primary-500 border-t-transparent animate-spin"></div>
      </div>

      <form v-else @submit.prevent="saveSettings" class="space-y-6">
        
        <!-- Panel de Identidad -->
        <div class="p-6 rounded-2xl border bg-brand-surface border-brand-border">
          <h3 class="text-lg font-semibold text-white mb-6">Identidad Visual</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">Logotipo de la Institución</label>
              <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl bg-white/5 border border-brand-border flex items-center justify-center overflow-hidden">
                  <img v-if="previewUrl" :src="previewUrl" class="w-full h-full object-cover" />
                  <svg v-else class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <input type="file" @change="handleFileChange" accept="image/*" class="text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-primary-500/20 file:text-primary-400 hover:file:bg-primary-500/30 transition-colors" />
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">Color Principal (Tema)</label>
              <div class="flex items-center gap-3">
                <input type="color" v-model="settings.theme_color" class="w-10 h-10 rounded cursor-pointer border-0 p-0 bg-transparent" />
                <input type="text" v-model="settings.theme_color" class="w-full bg-white/5 border border-brand-border rounded-xl px-4 py-2 text-white text-sm focus:ring-2 focus:ring-primary-500 outline-none" />
              </div>
            </div>
          </div>
        </div>

        <!-- Panel Académico -->
        <div class="p-6 rounded-2xl border bg-brand-surface border-brand-border">
          <h3 class="text-lg font-semibold text-white mb-6">Parámetros Operativos</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">Huso Horario</label>
              <select v-model="settings.timezone" class="w-full bg-white/5 border border-brand-border rounded-xl px-4 py-2 text-white text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <option value="UTC" class="bg-slate-800">UTC</option>
                <option value="America/Lima" class="bg-slate-800">America/Lima</option>
                <option value="America/Bogota" class="bg-slate-800">America/Bogota</option>
                <option value="America/Mexico_City" class="bg-slate-800">America/Mexico_City</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">Sistema de Calificación</label>
              <select v-model="settings.grading_scale" class="w-full bg-white/5 border border-brand-border rounded-xl px-4 py-2 text-white text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <option value="numeric_20" class="bg-slate-800">Numérico (0 - 20)</option>
                <option value="numeric_10" class="bg-slate-800">Numérico (0 - 10)</option>
                <option value="letters" class="bg-slate-800">Letras (A, B, C...)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Panel Financiero -->
        <div class="p-6 rounded-2xl border bg-brand-surface border-brand-border">
          <h3 class="text-lg font-semibold text-white mb-6">Parámetros Financieros</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">Moneda Predeterminada</label>
              <select v-model="settings.currency_default" class="w-full bg-white/5 border border-brand-border rounded-xl px-4 py-2 text-white text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                <option value="USD" class="bg-slate-800">Dólar Estadounidense (USD)</option>
                <option value="PEN" class="bg-slate-800">Sol Peruano (PEN)</option>
                <option value="MXN" class="bg-slate-800">Peso Mexicano (MXN)</option>
                <option value="COP" class="bg-slate-800">Peso Colombiano (COP)</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-300 mb-2">Impuesto Base (%)</label>
              <input type="number" step="0.01" v-model="settings.tax_percentage" class="w-full bg-white/5 border border-brand-border rounded-xl px-4 py-2 text-white text-sm focus:ring-2 focus:ring-primary-500 outline-none" />
            </div>
          </div>
        </div>

        <div class="flex justify-end">
          <button type="submit" :disabled="isSaving" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-500 disabled:opacity-50 text-white font-medium rounded-xl transition-colors flex items-center gap-2 shadow-[0_4px_20px_rgba(99,102,241,0.35)] hover:shadow-[0_6px_28px_rgba(99,102,241,0.55)]">
            <span v-if="isSaving" class="w-4 h-4 rounded-full border-2 border-white/50 border-t-white animate-spin"></span>
            Guardar Configuración
          </button>
        </div>

      </form>
    </div>
  </DashboardLayout>
</template>
