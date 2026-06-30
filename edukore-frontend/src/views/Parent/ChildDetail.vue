<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/axios'

const route = useRoute()
const router = useRouter()
const studentId = route.params.id

const loading = ref(true)
const student = ref(null)

const loadData = async () => {
  try {
    const res = await api.get(`/parent-portal/children/${studentId}`)
    student.value = res.data.student
  } catch (error) {
    console.error("Failed to load child data", error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})

const calculateAverage = (grades) => {
    if (!grades || grades.length === 0) return '-'
    let totalScore = 0
    let totalWeight = 0
    grades.forEach(g => {
        const weight = parseFloat(g.evaluation?.weight) || 100
        totalScore += (parseFloat(g.score) * (weight / 100))
        totalWeight += weight
    })
    if (totalWeight === 0) return '-'
    return (totalScore / (totalWeight / 100)).toFixed(2)
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center gap-4">
        <button @click="router.push('/parent/dashboard')" class="p-2 hover:bg-white/10 rounded-full text-slate-400 transition-colors">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </button>
        <h2 class="text-2xl font-bold text-white">Rendimiento: {{ student?.user?.name || '...' }}</h2>
    </div>

    <div v-if="loading" class="flex justify-center p-12">
      <div class="w-8 h-8 rounded-full border-4 border-indigo-500 border-t-transparent animate-spin"></div>
    </div>

    <div v-else-if="!student || student.enrollments.length === 0" class="text-center p-12 bg-white/5 border border-white/10 rounded-2xl text-slate-400">
      Este estudiante no tiene matrículas activas.
    </div>

    <div v-else class="space-y-6">
        <div 
            v-for="enrollment in student.enrollments" 
            :key="enrollment.id"
            class="bg-white/5 border border-white/10 rounded-2xl overflow-hidden"
        >
            <div class="p-6 bg-white/[0.02] border-b border-white/10 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-white">{{ enrollment.section?.course_assignments[0]?.course?.name || 'Curso' }}</h3>
                    <p class="text-slate-400 text-sm mt-1">Sección {{ enrollment.section?.name }}</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-slate-400 mb-1">Promedio</div>
                    <div class="text-2xl font-bold text-indigo-400">{{ calculateAverage(enrollment.grades) }}</div>
                </div>
            </div>

            <div class="p-6">
                <h4 class="text-sm font-medium text-slate-300 uppercase tracking-wider mb-4">Evaluaciones Públicas</h4>
                
                <div v-if="enrollment.grades && enrollment.grades.length > 0" class="space-y-3">
                    <div 
                        v-for="grade in enrollment.grades" 
                        :key="grade.id"
                        class="flex justify-between items-center p-3 rounded-lg bg-white/5 border border-white/5"
                    >
                        <div>
                            <div class="text-white font-medium">{{ grade.evaluation?.title }}</div>
                            <div class="text-xs text-slate-400">{{ grade.evaluation?.category }} &bull; Peso: {{ grade.evaluation?.weight }}%</div>
                        </div>
                        <div class="text-lg font-bold text-emerald-400">
                            {{ grade.score }}
                        </div>
                    </div>
                </div>
                <div v-else class="text-slate-500 text-sm">
                    Aún no hay calificaciones publicadas para este curso.
                </div>
            </div>
        </div>
    </div>
  </div>
</template>
