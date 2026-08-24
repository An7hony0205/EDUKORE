<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
import api from '../../api/axios'
import * as feather from 'feather-icons'

const router = useRouter()

const loading = ref(true)
const error = ref('')
const structure = ref([])

// For accordion state
const expandedYears = ref({})
const expandedLevels = ref({})
const expandedGrades = ref({})

const toggleYear = (id) => expandedYears.value[id] = !expandedYears.value[id]
const toggleLevel = (id) => expandedLevels.value[id] = !expandedLevels.value[id]
const toggleGrade = (id) => expandedGrades.value[id] = !expandedGrades.value[id]

const activeYear = computed(() => {
  return structure.value.find(y => y.is_active) || structure.value[0] || null
})

onMounted(async () => {
  try {
    const response = await api.get('/academic-structure')
    structure.value = response.data
    
    // Auto-expand the active year
    if (activeYear.value) {
      expandedYears.value[activeYear.value.id] = true
    }
    
    // Auto-expand the first level of the active year to make it less empty
    if (activeYear.value?.levels?.length > 0) {
      expandedLevels.value[activeYear.value.levels[0].id] = true
    }
  } catch (err) {
    error.value = 'No se pudo cargar la estructura académica.'
  } finally {
    loading.value = false
    setTimeout(() => {
      feather.replace()
    }, 100)
  }
})

const viewSectionDetails = (sectionId) => {
  router.push(`/academic-structure/sections/${sectionId}`)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
}
</script>

<template>
  <DashboardLayout>
    <div class="p-8 max-w-5xl mx-auto">
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Estructura Académica</h1>
          <p class="text-slate-500 dark:text-slate-400">Visualiza y navega por la jerarquía académica de la institución.</p>
        </div>
      </div>

      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else-if="error" class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400">
        {{ error }}
      </div>

      <div v-else-if="structure.length === 0" class="text-center py-20 bg-white/5 border border-white/10 rounded-2xl">
        <i data-feather="layers" class="w-12 h-12 text-slate-500 mx-auto mb-4"></i>
        <h3 class="text-xl font-medium text-slate-900 dark:text-white mb-2">Estructura Vacía</h3>
        <p class="text-slate-500 dark:text-slate-400">No hay años académicos registrados en el sistema.</p>
      </div>

      <div v-else class="space-y-6">
        <!-- Year Card -->
        <div v-for="year in structure" :key="year.id" class="bg-white/5 border border-white/10 rounded-2xl overflow-hidden">
          <div 
            class="flex items-center justify-between p-6 cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5 transition-colors"
            @click="toggleYear(year.id)"
          >
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center">
                <i data-feather="calendar" class="w-6 h-6"></i>
              </div>
              <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                  {{ year.year_name }}
                  <span v-if="year.is_active" class="px-2 py-0.5 text-xs rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Activo</span>
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ formatDate(year.start_date) }} a {{ formatDate(year.end_date) }}</p>
              </div>
            </div>
            <i :data-feather="expandedYears[year.id] ? 'chevron-up' : 'chevron-down'" class="w-6 h-6 text-slate-500 dark:text-slate-400"></i>
          </div>

          <!-- Levels -->
          <div v-if="expandedYears[year.id]" class="border-t border-white/10 bg-black/20 p-6 space-y-4">
            <div v-if="!year.levels || year.levels.length === 0" class="text-slate-500 text-sm">
              No existen niveles configurados para este año académico. Los niveles se gestionan desde el módulo correspondiente.
            </div>
            
            <div v-for="level in year.levels" :key="level.id" class="border border-slate-200 dark:border-slate-700/50 bg-slate-50 dark:bg-slate-800/30 rounded-xl overflow-hidden">
              <div 
                class="flex items-center justify-between p-4 cursor-pointer hover:bg-slate-700/30 transition-colors"
                @click="toggleLevel(level.id)"
              >
                <div class="flex items-center gap-3">
                  <i data-feather="layers" class="w-5 h-5 text-indigo-400"></i>
                  <span class="font-medium text-slate-900 dark:text-white text-lg">{{ level.name }}</span>
                </div>
                <i :data-feather="expandedLevels[level.id] ? 'chevron-up' : 'chevron-down'" class="w-5 h-5 text-slate-500"></i>
              </div>

              <!-- Grades -->
              <div v-if="expandedLevels[level.id]" class="border-t border-slate-200 dark:border-slate-700/50 p-4 space-y-3 bg-black/40">
                <div v-if="!level.grade_levels || level.grade_levels.length === 0" class="text-slate-500 text-sm">
                  No existen grados configurados para este nivel.
                </div>

                <div v-for="grade in level.grade_levels" :key="grade.id" class="border border-slate-200 dark:border-slate-700/50 bg-slate-50 dark:bg-slate-50 dark:bg-slate-800/50 rounded-lg overflow-hidden">
                  <div 
                    class="flex items-center justify-between p-3 cursor-pointer hover:bg-slate-700/50 transition-colors"
                    @click="toggleGrade(grade.id)"
                  >
                    <div class="flex items-center gap-3 pl-2">
                      <i data-feather="corner-down-right" class="w-4 h-4 text-slate-500"></i>
                      <span class="font-medium text-slate-200">{{ grade.name }}</span>
                    </div>
                    <i :data-feather="expandedGrades[grade.id] ? 'chevron-up' : 'chevron-down'" class="w-4 h-4 text-slate-500"></i>
                  </div>

                  <!-- Sections Grid -->
                  <div v-if="expandedGrades[grade.id]" class="border-t border-slate-200 dark:border-slate-700/50 p-4 bg-black/60">
                    <div v-if="!grade.sections || grade.sections.length === 0" class="text-slate-500 text-sm">
                      No hay secciones configuradas para este grado.
                    </div>
                    
                    <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                      <button 
                        v-for="section in grade.sections" 
                        :key="section.id"
                        @click="viewSectionDetails(section.id)"
                        class="flex flex-col items-center justify-center p-3 rounded-lg border border-indigo-500/20 bg-indigo-500/5 hover:bg-indigo-500/20 hover:border-indigo-500/50 transition-all group"
                      >
                        <span class="text-2xl font-bold text-indigo-300 group-hover:text-indigo-200 mb-1">{{ section.name }}</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Ver Sección &rarr;</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
