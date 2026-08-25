<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/axios'
import DashboardLayout from '../../layouts/DashboardLayout.vue'

const router = useRouter()

// ─── Estado del formulario ───────────────────────────────────────────────────
const form = ref({
  name:            '',
  last_name:       '',
  email:           '',
  document_number: '',
  phone:           '',
  address:         '',
  occupation:      '',
})

// ─── Estado del buscador de estudiantes ─────────────────────────────────────
const searchQuery      = ref('')
const searchResults    = ref([])
const selectedStudents = ref([])   // Array de objetos { id, name }
const isSearching      = ref(false)
const showDropdown     = ref(false)
let   debounceTimer    = null

// ─── Estado del envío ────────────────────────────────────────────────────────
const isSaving    = ref(false)
const successMsg  = ref('')
const errors      = ref({})
const globalError = ref('')

// ─── IDs ya seleccionados (para excluir del dropdown) ───────────────────────
const selectedIds = computed(() => new Set(selectedStudents.value.map(s => s.id)))

// ─── Debounce: busca estudiantes 300ms después de dejar de escribir ──────────
function onSearchInput() {
  showDropdown.value = false
  clearTimeout(debounceTimer)

  if (searchQuery.value.trim().length < 2) {
    searchResults.value = []
    return
  }

  debounceTimer = setTimeout(async () => {
    isSearching.value = true
    try {
      const res = await api.get('/students', { params: { search: searchQuery.value } })
      // La respuesta paginada viene en res.data.data
      searchResults.value = (res.data.data ?? res.data).filter(
        s => !selectedIds.value.has(s.id)
      )
      showDropdown.value = searchResults.value.length > 0
    } catch {
      searchResults.value = []
    } finally {
      isSearching.value = false
    }
  }, 300)
}

// ─── Agrega un estudiante al array de seleccionados ─────────────────────────
function selectStudent(student) {
  if (!selectedIds.value.has(student.id)) {
    selectedStudents.value.push({
      id:   student.id,
      name: student.user?.name ?? student.name ?? 'Sin nombre',
    })
  }
  searchQuery.value   = ''
  searchResults.value = []
  showDropdown.value  = false
}

// ─── Remueve un estudiante del array ────────────────────────────────────────
function removeStudent(id) {
  selectedStudents.value = selectedStudents.value.filter(s => s.id !== id)
}

// ─── Oculta el dropdown con delay para permitir el click ─────────────────────
function hideDropdown() {
  setTimeout(() => { showDropdown.value = false }, 200)
}

// ─── Limpia el formulario completo ──────────────────────────────────────────
function resetForm() {
  form.value = { name: '', last_name: '', email: '', document_number: '', phone: '', occupation: '' }
  selectedStudents.value = []
  searchQuery.value  = ''
  errors.value       = {}
  globalError.value  = ''
}

// ─── Submit principal ────────────────────────────────────────────────────────
async function handleSubmit() {
  if (isSaving.value) return

  errors.value      = {}
  globalError.value = ''
  successMsg.value  = ''

  if (selectedStudents.value.length === 0) {
    globalError.value = 'Debes vincular al menos un estudiante antes de guardar.'
    return
  }

  isSaving.value = true

  try {
    await api.post('/parents', {
      ...form.value,
      student_ids: selectedStudents.value.map(s => s.id),
    })

    successMsg.value = 'Padre/Apoderado registrado exitosamente.'
    resetForm()

    // Redirige a la lista de familias tras 1.5 s
    setTimeout(() => router.push({ name: 'families' }), 1500)

  } catch (err) {
    if (err.validationErrors) {
      errors.value      = err.validationErrors.errors ?? {}
      globalError.value = err.validationErrors.message
    } else {
      globalError.value = err?.response?.data?.message ?? 'Ocurrió un error inesperado.'
    }
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="max-w-2xl mx-auto space-y-6">

      <!-- Header -->
      <div class="flex items-center gap-3">
        <button
          @click="router.back()"
          class="p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-white/5 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Registrar Padre / Apoderado</h1>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Complete los datos y vincule a sus hijos.</p>
        </div>
      </div>

      <!-- Mensaje de éxito -->
      <Transition name="fade">
        <div
          v-if="successMsg"
          class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-400 rounded-xl px-4 py-3 text-sm"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          {{ successMsg }}
        </div>
      </Transition>

      <!-- Mensaje de error global -->
      <Transition name="fade">
        <div
          v-if="globalError"
          class="flex items-center gap-3 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-700 dark:text-red-400 rounded-xl px-4 py-3 text-sm"
        >
          <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ globalError }}
        </div>
      </Transition>

      <form @submit.prevent="handleSubmit" class="space-y-5">

        <!-- ── Datos Personales ──────────────────────────────────────────────── -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 space-y-4">
          <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
            Datos del Padre / Apoderado
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Nombre -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Nombres <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                placeholder="Ej. María"
                :class="['w-full bg-white border text-slate-900 placeholder-slate-400 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-1 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500', errors.name ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500' : 'border-slate-300 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:focus:border-slate-500']"
              />
              <p v-if="errors.name" class="mt-1 text-xs text-red-500">{{ errors.name[0] }}</p>
            </div>

            <!-- Apellidos -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Apellidos <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.last_name"
                type="text"
                placeholder="Ej. González Pérez"
                :class="['w-full bg-white border text-slate-900 placeholder-slate-400 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-1 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500', errors.last_name ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500' : 'border-slate-300 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:focus:border-slate-500']"
              />
              <p v-if="errors.last_name" class="mt-1 text-xs text-red-500">{{ errors.last_name[0] }}</p>
            </div>

            <!-- Correo (opcional) -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Correo Electrónico <span class="text-slate-400 font-normal text-xs">(opcional)</span>
              </label>
              <input
                v-model="form.email"
                type="email"
                placeholder="Se generará automáticamente si se omite"
                :class="['w-full bg-white border text-slate-900 placeholder-slate-400 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-1 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500', errors.email ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500' : 'border-slate-300 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:focus:border-slate-500']"
              />
              <p v-if="errors.email" class="mt-1 text-xs text-red-500">{{ errors.email[0] }}</p>
            </div>

            <!-- DNI -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                DNI / Documento <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.document_number"
                type="text"
                placeholder="Ej. 12345678"
                :class="['w-full bg-white border text-slate-900 placeholder-slate-400 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-1 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500', errors.document_number ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500' : 'border-slate-300 focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:focus:border-slate-500']"
              />
              <p v-if="errors.document_number" class="mt-1 text-xs text-red-500">{{ errors.document_number[0] }}</p>
            </div>

            <!-- Teléfono (opcional) -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Teléfono</label>
              <input
                v-model="form.phone"
                type="tel"
                placeholder="Ej. +51 999 888 777"
                class="w-full bg-white border border-slate-300 text-slate-900 placeholder-slate-400 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500"
              />
            </div>

            <!-- Ocupación (opcional) -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Ocupación</label>
              <input
                v-model="form.occupation"
                type="text"
                placeholder="Ej. Ingeniera"
                class="w-full bg-white border border-slate-300 text-slate-900 placeholder-slate-400 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500"
              />
            </div>

            <!-- Dirección (opcional) -->
            <div class="sm:col-span-2">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Dirección</label>
              <input
                v-model="form.address"
                type="text"
                placeholder="Ej. Av. Los Álamos 456, Lima"
                class="w-full bg-white border border-slate-300 text-slate-900 placeholder-slate-400 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500"
              />
            </div>
          </div>
        </div>

        <!-- ── Vinculación de Hijos ───────────────────────────────────────────── -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 space-y-4">
          <div>
            <h2 class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              Estudiantes Vinculados <span class="text-red-500">*</span>
            </h2>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
              Busca al estudiante por nombre o código de matrícula.
            </p>
          </div>

          <!-- Buscador con dropdown flotante -->
          <div class="relative">
            <div class="relative">
              <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
              <svg
                v-if="isSearching"
                class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 animate-spin text-slate-400"
                fill="none" viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Escribe el nombre o código del estudiante..."
                @input="onSearchInput"
                @blur="hideDropdown"
                @focus="searchResults.length && (showDropdown = true)"
                class="w-full bg-white border border-slate-300 text-slate-900 placeholder-slate-400 rounded-lg pl-9 pr-9 py-2.5 text-sm focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:bg-slate-800 dark:border-slate-700 dark:text-white dark:placeholder-slate-500 dark:focus:border-slate-500"
              />
            </div>

            <!-- Dropdown de resultados -->
            <Transition name="slide-down">
              <ul
                v-if="showDropdown && searchResults.length"
                class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg overflow-hidden"
              >
                <li
                  v-for="student in searchResults"
                  :key="student.id"
                  @mousedown.prevent="selectStudent(student)"
                  class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors border-b border-slate-100 dark:border-slate-800 last:border-0"
                >
                  <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center shrink-0">
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300">
                      {{ (student.user?.name ?? student.name ?? '?')[0].toUpperCase() }}
                    </span>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                      {{ student.user?.name ?? student.name }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                      Matrícula: {{ student.enrollment_number ?? 'N/A' }}
                    </p>
                  </div>
                </li>
              </ul>
            </Transition>
          </div>

          <!-- Chips de estudiantes seleccionados -->
          <div v-if="selectedStudents.length" class="flex flex-wrap gap-2 pt-1">
            <span
              v-for="student in selectedStudents"
              :key="student.id"
              class="inline-flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-sm font-medium rounded-full px-3 py-1"
            >
              {{ student.name }}
              <button
                type="button"
                @click="removeStudent(student.id)"
                class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </span>
          </div>

          <p v-else class="text-xs text-slate-400 dark:text-slate-500 italic">
            Ningún estudiante vinculado todavía.
          </p>

          <p v-if="errors.student_ids" class="text-xs text-red-500">{{ errors.student_ids[0] }}</p>
        </div>

        <!-- ── Acciones ──────────────────────────────────────────────────────── -->
        <div class="flex items-center justify-end gap-3 pb-4">
          <button
            type="button"
            @click="router.back()"
            class="px-5 py-2.5 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="isSaving"
            class="px-6 py-2.5 bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 font-medium rounded-xl text-sm transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            <svg v-if="isSaving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            {{ isSaving ? 'Guardando...' : 'Registrar Apoderado' }}
          </button>
        </div>

      </form>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-down-enter-active, .slide-down-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.slide-down-enter-from, .slide-down-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
