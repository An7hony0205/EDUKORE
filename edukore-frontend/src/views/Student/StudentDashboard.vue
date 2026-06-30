<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api/axios'

const loading = ref(true)
const enrollments = ref([])

const loadData = async () => {
  try {
    const res = await api.get('/student-portal/grades')
    enrollments.value = res.data.enrollments
  } catch (error) {
    console.error("Failed to load student data", error)
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
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold text-white">Mi Portal Académico</h2>
    </div>

    <div v-if="loading" class="flex justify-center p-12">
      <div class="w-8 h-8 rounded-full border-4 border-indigo-500 border-t-transparent animate-spin"></div>
    </div>

    <div v-else-if="enrollments.length === 0" class="text-center p-12 bg-white/5 border border-white/10 rounded-2xl text-slate-400">
      No estás matriculado en ningún curso actualmente.
    </div>

    <div v-else class="space-y-6">
        <div 
            v-for="enrollment in enrollments" 
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
                <h4 class="text-sm font-medium text-slate-300 uppercase tracking-wider mb-4">Evaluaciones</h4>
                
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
