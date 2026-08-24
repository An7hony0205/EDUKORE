<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useAcademicStore } from '../stores/academic'
import { useAuthStore } from '../stores/auth'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const academicStore = useAcademicStore()
const authStore = useAuthStore()

const searchQuery = ref('')
const filterStatus = ref('active')
const sortBy = ref('name_asc')

const isModalOpen = ref(false)
const isSubmitting = ref(false)
const editMode = ref(false)
const currentCourseId = ref(null)

const formData = ref({
  code: '',
  name: '',
  description: '',
  is_active: true
})
const formError = ref('')

const isAdmin = computed(() => authStore.user?.role?.name === 'Admin')

const fetchFilteredCourses = () => {
  academicStore.fetchCourses({
    search: searchQuery.value,
    status: filterStatus.value,
    sort: sortBy.value
  })
}

// Debounce search
let searchTimeout
watch(searchQuery, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchFilteredCourses()
  }, 300)
})

watch([filterStatus, sortBy], () => {
  fetchFilteredCourses()
})

const openModal = (course = null) => {
  if (course) {
    editMode.value = true
    currentCourseId.value = course.id
    formData.value = {
      code: course.code || '',
      name: course.name || '',
      description: course.description || '',
      is_active: course.is_active !== undefined ? course.is_active : true
    }
  } else {
    editMode.value = false
    currentCourseId.value = null
    formData.value = { code: '', name: '', description: '', is_active: true }
  }
  formError.value = ''
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

const submitCourse = async () => {
  if (!formData.value.name) {
    formError.value = 'El nombre es obligatorio.'
    return
  }
  
  isSubmitting.value = true
  formError.value = ''
  
  try {
    const payload = {
      code: formData.value.code ? formData.value.code.toUpperCase() : null,
      name: formData.value.name,
      description: formData.value.description,
      is_active: formData.value.is_active
    }
    
    if (editMode.value) {
      await academicStore.updateCourse(currentCourseId.value, payload)
    } else {
      await academicStore.createCourse(payload)
      fetchFilteredCourses()
    }
    closeModal()
  } catch (error) {
    formError.value = error.response?.data?.message || 'Error al guardar el curso. Revisa los datos (posible duplicado).'
  } finally {
    isSubmitting.value = false
  }
}

const toggleStatus = async (course) => {
  if (!confirm(`¿Estás seguro de ${course.is_active ? 'desactivar' : 'activar'} el curso ${course.name}?`)) return
  
  try {
    if (course.is_active) {
      await academicStore.deleteCourse(course.id) // delete means soft deactivate in backend
    } else {
      await academicStore.updateCourse(course.id, { is_active: true })
    }
    fetchFilteredCourses()
  } catch (error) {
    alert('Ocurrió un error al cambiar el estado del curso.')
  }
}

onMounted(() => {
  fetchFilteredCourses()
})
</script>

<template>
  <DashboardLayout>
    <div class="px-8 pt-8 pb-4 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Cursos</h1>
        <p class="text-slate-500 dark:text-slate-400">Gestiona las asignaturas académicas de tu institución</p>
      </div>
      <button v-if="isAdmin" @click="openModal()" class="px-5 py-2.5 bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 text-sm font-medium rounded-xl transition-colors shadow-lg shadow-indigo-500/20 flex items-center gap-2 border border-indigo-500/50 whitespace-nowrap">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nuevo Curso
      </button>
    </div>
    
    <div class="px-8 pb-8">
      
      <!-- Toolbar -->
      <div class="flex flex-col md:flex-row gap-4 mb-8 bg-white/5 p-4 rounded-xl border border-white/10">
        <div class="flex-1 relative">
          <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input v-model="searchQuery" type="text" placeholder="Buscar cursos por nombre o código..." class="w-full bg-black/20 border border-slate-200 dark:border-slate-700 rounded-lg pl-10 pr-4 py-2 text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors">
        </div>
        <div class="flex gap-2">
          <select v-model="filterStatus" class="bg-black/20 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition-colors">
            <option value="all">Todos los estados</option>
            <option value="active">Activos</option>
            <option value="inactive">Inactivos</option>
          </select>
          <select v-model="sortBy" class="bg-black/20 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition-colors">
            <option value="name_asc">Nombre (A-Z)</option>
            <option value="name_desc">Nombre (Z-A)</option>
            <option value="recent">Más recientes</option>
            <option value="oldest">Más antiguos</option>
          </select>
        </div>
      </div>

      <div v-if="academicStore.loading" class="flex justify-center items-center h-64">
        <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
      
      <div v-else-if="academicStore.error" class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400">
        {{ academicStore.error }}
      </div>

      <div v-else-if="academicStore.courses.length === 0" class="flex flex-col items-center justify-center py-20 px-4 text-center">
        <div class="w-24 h-24 mb-6 rounded-3xl flex items-center justify-center bg-white/5 border border-white/10 shadow-[0_0_50px_-12px_rgba(99,102,241,0.3)]">
          <svg class="w-12 h-12 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
        </div>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No se encontraron cursos</h3>
        <p class="text-slate-500 dark:text-slate-400 max-w-sm mb-6">Ajusta tus filtros o crea un nuevo curso para comenzar.</p>
        <button v-if="isAdmin" @click="openModal()" class="px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 text-slate-900 dark:text-white font-medium hover:from-indigo-400 hover:to-violet-500 transition-all shadow-[0_0_20px_-5px_rgba(99,102,241,0.5)]">
          Crear curso
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div 
          v-for="course in academicStore.courses" 
          :key="course.id"
          class="group relative rounded-2xl p-6 bg-white/5 border transition-all duration-300 overflow-hidden flex flex-col"
          :class="[course.is_active === false ? 'border-red-500/20 opacity-80' : 'border-white/10 hover:border-indigo-500/50 hover:bg-white/10']"
        >
          <div class="relative z-10 flex-1">
            <div class="flex justify-between items-start mb-4">
              <div class="flex items-center gap-2">
                <span v-if="course.code" class="px-3 py-1 text-xs font-medium rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/20">
                  {{ course.code }}
                </span>
                <span v-if="course.is_active === false" class="px-3 py-1 text-xs font-medium rounded-full bg-red-500/10 text-red-400 border border-red-500/20">
                  Inactivo
                </span>
              </div>
              <div v-if="isAdmin" class="flex items-center gap-2">
                <button @click="openModal(course)" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors" title="Editar curso">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>
                <button @click="toggleStatus(course)" :class="[course.is_active ? 'text-red-400 hover:text-red-300' : 'text-emerald-400 hover:text-emerald-300']" class="transition-colors" :title="course.is_active ? 'Desactivar curso' : 'Activar curso'">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                  </svg>
                </button>
              </div>
            </div>
            
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2 line-clamp-2" :class="{'text-slate-500 dark:text-slate-400': course.is_active === false}">{{ course.name }}</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 line-clamp-3">{{ course.description || 'Sin descripción' }}</p>
          </div>
          
          <div class="relative z-10 pt-4 border-t border-white/10 mt-auto flex items-center justify-between">
            <span class="text-xs text-slate-500">
              Creado: {{ new Date(course.created_at).toLocaleDateString() }}
            </span>
            <router-link :to="`/courses/${course.id}`" class="text-sm font-medium text-indigo-400 hover:text-indigo-300 transition-colors flex items-center gap-1">
              Ver detalles &rarr;
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Course Modal -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
      
      <div class="relative w-full max-w-md bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl overflow-hidden animate-fade-in-up">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-50 dark:bg-slate-800/50">
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ editMode ? 'Editar Curso' : 'Crear Nuevo Curso' }}</h3>
          <button @click="closeModal" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="p-6 space-y-4">
          <div v-if="formError" class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            {{ formError }}
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre <span class="text-red-500">*</span></label>
            <input v-model="formData.name" type="text" placeholder="Ej: Matemáticas Avanzadas" class="w-full bg-black/20 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Código del Curso</label>
            <input v-model="formData.code" type="text" placeholder="Ej: MAT-101" class="w-full bg-black/20 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors uppercase">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descripción</label>
            <textarea v-model="formData.description" rows="3" placeholder="Breve descripción del curso..." class="w-full bg-black/20 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors resize-none"></textarea>
          </div>

          <div v-if="editMode" class="flex items-center gap-2 pt-2">
            <input type="checkbox" id="is_active" v-model="formData.is_active" class="w-4 h-4 rounded border-slate-200 dark:border-slate-700 bg-black/20 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-slate-900">
            <label for="is_active" class="text-sm font-medium text-slate-700 dark:text-slate-300">Curso Activo</label>
          </div>
        </div>
        
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30 flex justify-end gap-3">
          <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:text-white transition-colors">
            Cancelar
          </button>
          <button @click="submitCourse" :disabled="isSubmitting" class="px-4 py-2 bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed text-slate-900 dark:text-white text-sm font-medium rounded-lg transition-colors shadow-lg shadow-indigo-500/20">
            <span v-if="isSubmitting">Guardando...</span>
            <span v-else>{{ editMode ? 'Actualizar Curso' : 'Crear Curso' }}</span>
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
