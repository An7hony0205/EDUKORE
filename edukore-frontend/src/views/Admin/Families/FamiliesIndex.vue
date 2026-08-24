<template>
  <DashboardLayout>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Familias</h1>
        <p class="text-sm text-gray-500 mt-1">GestiÃ³n de familias y apoderados del colegio</p>
      </div>
      <button
        @click="openCreateModal"
        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nueva Familia
      </button>
    </div>

    <!-- Estado de carga -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Estado vacÃ­o -->
    <div v-else-if="families.length === 0" class="text-center py-20 text-gray-500">
      <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
      <p class="text-lg font-medium">No hay familias registradas</p>
      <p class="text-sm mt-1">Crea la primera familia para comenzar.</p>
    </div>

    <!-- Tabla de familias -->
    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nombre</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Miembros</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Estudiantes</th>
            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="family in families"
            :key="family.id"
            class="hover:bg-gray-50 transition-colors"
          >
            <td class="px-6 py-4">
              <span class="font-medium text-gray-900">{{ family.name }}</span>
            </td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ family.members_count }} apoderado(s)
              </span>
            </td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                {{ family.students_count }} estudiante(s)
              </span>
            </td>
            <td class="px-6 py-4 text-right space-x-2">
              <router-link
                :to="{ name: 'family-detail', params: { id: family.id } }"
                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
              >
                Ver detalle
              </router-link>
              <button
                @click="confirmDelete(family)"
                class="text-red-500 hover:text-red-700 text-sm font-medium"
              >
                Eliminar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal: Crear Familia -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
      @click.self="showCreateModal = false"
    >
      <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Nueva Familia</h2>
        <form @submit.prevent="createFamily">
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la familia</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="Ej. Familia GarcÃ­a LÃ³pez"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            required
          />
          <p v-if="formError" class="text-red-500 text-xs mt-1">{{ formError }}</p>

          <div class="flex justify-end gap-3 mt-5">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="isSaving"
              class="px-4 py-2 text-sm text-slate-900 dark:text-white bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ isSaving ? 'Guardando...' : 'Crear Familia' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Confirmar EliminaciÃ³n -->
    <div
      v-if="familyToDelete"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
      @click.self="familyToDelete = null"
    >
      <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-sm">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Â¿Eliminar familia?</h2>
        <p class="text-sm text-gray-600 mb-5">
          Esta acciÃ³n eliminarÃ¡ la familia <strong>{{ familyToDelete.name }}</strong> y todas sus vinculaciones. No se puede deshacer.
        </p>
        <div class="flex justify-end gap-3">
          <button
            @click="familyToDelete = null"
            class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
          >
            Cancelar
          </button>
          <button
            @click="deleteFamily"
            :disabled="isSaving"
            class="px-4 py-2 text-sm text-slate-900 dark:text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50"
          >
            {{ isSaving ? 'Eliminando...' : 'SÃ­, eliminar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { ref, onMounted } from 'vue'
import api from '@/api/axios'

// â”€â”€ Estado â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const families       = ref([])
const isLoading      = ref(false)
const isSaving       = ref(false)
const showCreateModal = ref(false)
const familyToDelete = ref(null)
const form           = ref({ name: '' })
const formError      = ref('')

// â”€â”€ MÃ©todos â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
async function fetchFamilies() {
  isLoading.value = true
  try {
    const { data } = await api.get('/families')
    families.value = data
  } finally {
    isLoading.value = false
  }
}

function openCreateModal() {
  form.value    = { name: '' }
  formError.value = ''
  showCreateModal.value = true
}

async function createFamily() {
  formError.value = ''
  isSaving.value  = true
  try {
    await api.post('/families', form.value)
    showCreateModal.value = false
    await fetchFamilies()
  } catch (err) {
    formError.value = err.validationErrors?.errors?.name?.[0] ?? 'Error al crear la familia.'
  } finally {
    isSaving.value = false
  }
}

function confirmDelete(family) {
  familyToDelete.value = family
}

async function deleteFamily() {
  isSaving.value = true
  try {
    await api.delete(`/families/${familyToDelete.value.id}`)
    familyToDelete.value = null
    await fetchFamilies()
  } finally {
    isSaving.value = false
  }
}

onMounted(fetchFamilies)
</script>
