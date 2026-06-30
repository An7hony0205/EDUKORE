<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api/axios'

const router = useRouter()
const loading = ref(true)
const children = ref([])

const loadData = async () => {
  try {
    const res = await api.get('/parent-portal/children')
    children.value = res.data.children
  } catch (error) {
    console.error("Failed to load children", error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-bold text-white">Mis Hijos</h2>
    </div>

    <div v-if="loading" class="flex justify-center p-12">
      <div class="w-8 h-8 rounded-full border-4 border-indigo-500 border-t-transparent animate-spin"></div>
    </div>

    <div v-else-if="children.length === 0" class="text-center p-12 bg-white/5 border border-white/10 rounded-2xl text-slate-400">
      No tienes hijos registrados en el sistema.
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="child in children" 
        :key="child.id"
        class="bg-white/5 border border-white/10 rounded-2xl p-6 backdrop-blur-md hover:bg-white/10 transition-colors cursor-pointer group flex flex-col items-center text-center"
        @click="router.push(`/parent/children/${child.id}`)"
      >
        <div class="w-20 h-20 bg-indigo-500/20 text-indigo-400 rounded-full flex items-center justify-center mb-4 border border-indigo-500/30">
            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </div>
        
        <h3 class="text-xl font-bold text-white group-hover:text-indigo-400 transition-colors">
          {{ child.user?.name }}
        </h3>
        <p class="text-slate-400 mt-1 text-sm">{{ child.user?.email }}</p>
        
        <div class="mt-6 pt-4 border-t border-white/10 w-full text-sm">
          <span class="text-indigo-400 font-medium group-hover:underline">Ver Calificaciones &rarr;</span>
        </div>
      </div>
    </div>
  </div>
</template>
