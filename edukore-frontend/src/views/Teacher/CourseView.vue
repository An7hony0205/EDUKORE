<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/axios'
import GradebookTable from '@/components/Academic/GradebookTable.vue'
import AttendanceTracker from '@/components/Academic/AttendanceTracker.vue'

const route = useRoute()
const courseId = route.params.id

const loading = ref(true)
const activeTab = ref('gradebook') // 'gradebook', 'attendance', 'settings'

const assignment = ref(null)
const evaluations = ref([])

const fetchCourseData = async () => {
  try {
    const res = await api.get(`/course-assignments/${courseId}/gradebook`)
    assignment.value = res.data.assignment
    evaluations.value = res.data.evaluations
  } catch (error) {
    console.error("Error loading course data", error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCourseData()
})
</script>

<template>
  <div class="h-full flex flex-col space-y-6">
    <!-- Header -->
    <div v-if="!loading && assignment" class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold text-white">{{ assignment.course?.name }}</h2>
        <p class="text-slate-400 mt-1">
          {{ assignment.section?.grade_level?.level?.name }} &bull; 
          {{ assignment.section?.grade_level?.name }} &bull; 
          Sección {{ assignment.section?.name }}
        </p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-brand-border">
      <nav class="-mb-px flex space-x-8">
        <button 
          @click="activeTab = 'gradebook'"
          :class="[
            activeTab === 'gradebook' 
              ? 'border-primary-500 text-primary-400' 
              : 'border-transparent text-slate-400 hover:text-slate-300 hover:border-slate-300',
            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Gradebook
        </button>
        <button 
          @click="activeTab = 'attendance'"
          :class="[
            activeTab === 'attendance' 
              ? 'border-primary-500 text-primary-400' 
              : 'border-transparent text-slate-400 hover:text-slate-300 hover:border-slate-300',
            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Asistencia
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="flex-grow">
      <div v-if="loading" class="flex justify-center p-12">
        <div class="w-8 h-8 rounded-full border-4 border-primary-500 border-t-transparent animate-spin"></div>
      </div>
      
      <div v-else-if="activeTab === 'gradebook'" class="bg-brand-surface border border-brand-border rounded-2xl p-6 overflow-hidden flex flex-col h-full">
        <GradebookTable 
          :assignment="assignment" 
          :evaluations="evaluations"
          @refresh="fetchCourseData"
        />
      </div>

      <div v-else-if="activeTab === 'attendance'" class="bg-brand-surface border border-brand-border rounded-2xl p-6 overflow-hidden flex flex-col h-full">
        <AttendanceTracker :assignment="assignment" />
      </div>
    </div>
  </div>
</template>
