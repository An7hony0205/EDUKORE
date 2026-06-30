<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/axios'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const router = useRouter()
const submitting = ref(false)
const error = ref(null)

const form = ref({
  student: {
    name: '',
    email: '',
    enrollment_number: '',
    date_of_birth: ''
  },
  parents: [
    { name: '', email: '', phone: '', relationship: 'Padre' }
  ]
})

const addParent = () => {
  form.value.parents.push({ name: '', email: '', phone: '', relationship: 'Madre' })
}

const removeParent = (index) => {
  form.value.parents.splice(index, 1)
}

const submitForm = async () => {
  submitting.value = true
  error.value = null
  try {
    const res = await api.post('/students', form.value)
    router.push(`/student/${res.data.data.id}`)
  } catch (err) {
    console.error(err)
    error.value = err.response?.data?.message || 'Error al registrar estudiante'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <template #title>Registrar Estudiante</template>
    <div class="max-w-4xl mx-auto space-y-6">
      <div>
        <h2 class="text-2xl font-bold text-white flex items-center gap-2">
          Registro de Estudiante (Onboarding)
        </h2>
        <p class="text-slate-400 text-sm mt-1">Registra al alumno y a sus apoderados en un solo paso.</p>
      </div>

      <div v-if="error" class="bg-rose-500/10 border border-rose-500/50 text-rose-400 p-4 rounded-lg text-sm">
        {{ error }}
      </div>

      <form @submit.prevent="submitForm" class="space-y-8">
        <!-- Datos del Estudiante -->
        <div class="bg-brand-surface border border-brand-border rounded-2xl p-6">
          <h3 class="text-lg font-medium text-white mb-4">1. Datos del Alumno</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1">Nombre Completo</label>
              <input v-model="form.student.name" required type="text" class="w-full bg-white/5 border border-brand-border rounded-lg px-4 py-2 text-white focus:border-primary-500 focus:outline-none">
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1">Correo Institucional</label>
              <input v-model="form.student.email" required type="email" class="w-full bg-white/5 border border-brand-border rounded-lg px-4 py-2 text-white focus:border-primary-500 focus:outline-none">
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1">Código de Matrícula</label>
              <input v-model="form.student.enrollment_number" required type="text" class="w-full bg-white/5 border border-brand-border rounded-lg px-4 py-2 text-white focus:border-primary-500 focus:outline-none">
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-400 mb-1">Fecha de Nacimiento</label>
              <input v-model="form.student.date_of_birth" type="date" class="w-full bg-white/5 border border-brand-border rounded-lg px-4 py-2 text-slate-300 focus:border-primary-500 focus:outline-none [color-scheme:dark]">
            </div>
          </div>
        </div>

        <!-- Datos de los Apoderados -->
        <div class="bg-brand-surface border border-brand-border rounded-2xl p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-white">2. Datos de los Apoderados</h3>
            <button type="button" @click="addParent" class="text-primary-400 hover:text-primary-300 text-xs font-medium bg-primary-500/10 px-3 py-1.5 rounded-lg">+ Añadir Apoderado</button>
          </div>
          
          <div class="space-y-6">
            <div v-for="(parent, index) in form.parents" :key="index" class="p-4 bg-white/5 rounded-xl border border-brand-border relative">
              <button v-if="form.parents.length > 1" type="button" @click="removeParent(index)" class="absolute top-4 right-4 text-slate-500 hover:text-rose-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-slate-400 mb-1">Nombre Completo</label>
                  <input v-model="parent.name" required type="text" class="w-full bg-white/5 border border-brand-border rounded-lg px-4 py-2 text-white focus:border-primary-500 focus:outline-none">
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-400 mb-1">Parentesco</label>
                  <select v-model="parent.relationship" class="w-full bg-white/5 border border-brand-border rounded-lg px-4 py-2 text-white focus:border-primary-500 focus:outline-none appearance-none">
                    <option value="Padre" class="bg-brand-dark">Padre</option>
                    <option value="Madre" class="bg-brand-dark">Madre</option>
                    <option value="Tutor" class="bg-brand-dark">Tutor/a Legal</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-400 mb-1">Correo Electrónico</label>
                  <input v-model="parent.email" required type="email" class="w-full bg-white/5 border border-brand-border rounded-lg px-4 py-2 text-white focus:border-primary-500 focus:outline-none">
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-400 mb-1">Teléfono</label>
                  <input v-model="parent.phone" required type="text" class="w-full bg-white/5 border border-brand-border rounded-lg px-4 py-2 text-white focus:border-primary-500 focus:outline-none">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-4">
          <button type="button" @click="router.push('/students')" class="px-6 py-2 rounded-lg text-slate-400 hover:text-white transition-colors">Cancelar</button>
          <button type="submit" :disabled="submitting" class="bg-primary-600 hover:bg-primary-500 text-white px-8 py-2 rounded-lg font-medium transition-colors disabled:opacity-50">
            {{ submitting ? 'Registrando...' : 'Completar Registro' }}
          </button>
        </div>
      </form>
    </div>
  </DashboardLayout>
</template>
