<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api/axios'

const router = useRouter()
const query = ref('')
const results = ref([])
const isSearching = ref(false)
const showDropdown = ref(false)

let debounceTimeout = null

watch(query, (val) => {
  if (val.length < 3) {
    results.value = []
    showDropdown.value = false
    return
  }
  
  clearTimeout(debounceTimeout)
  debounceTimeout = setTimeout(async () => {
    isSearching.value = true
    try {
      const res = await api.get(`/search?q=${val}`)
      results.value = res.data
      showDropdown.value = true
    } catch (err) {
      console.error(err)
    } finally {
      isSearching.value = false
    }
  }, 300)
})

const handleSelect = (item) => {
  showDropdown.value = false
  query.value = ''
  
  if (item.type === 'student') {
    router.push(`/students/${item.id}`)
  }
  // more handlers as we expand
}

const hideDropdown = () => {
  setTimeout(() => showDropdown.value = false, 200)
}
</script>

<template>
  <div class="relative w-full max-w-md">
    <div class="relative">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
      </div>
      <input
        type="text"
        v-model="query"
        @blur="hideDropdown"
        @focus="query.length >= 3 && (showDropdown = true)"
        placeholder="Buscar estudiantes..."
        class="block w-full pl-10 pr-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl leading-5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-1 focus:border-slate-500 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 sm:text-sm transition-colors"
      />
      <div v-if="isSearching" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
        <svg class="animate-spin h-4 w-4 text-slate-900 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
    </div>

    <!-- Results Dropdown -->
    <div v-if="showDropdown && results.length > 0" class="absolute z-50 mt-1 w-full bg-slate-800 border border-brand-border rounded-xl shadow-lg overflow-hidden">
      <ul class="max-h-60 overflow-y-auto py-1 text-sm text-slate-500 dark:text-slate-400">
        <!-- Group by type (simplified for now) -->
        <li class="px-3 py-1.5 text-xs font-semibold text-slate-500 uppercase tracking-wider bg-slate-900/50">Estudiantes</li>
        <li 
          v-for="item in results.filter(r => r.type === 'student')" 
          :key="item.id"
          @click="handleSelect(item)"
          class="px-4 py-2 hover:bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 hover:text-white cursor-pointer transition-colors"
        >
          <div class="font-medium">{{ item.name }}</div>
          <div class="text-xs opacity-70">{{ item.meta }}</div>
        </li>
      </ul>
    </div>
    
    <!-- No results -->
    <div v-if="showDropdown && results.length === 0 && !isSearching" class="absolute z-50 mt-1 w-full bg-slate-800 border border-brand-border rounded-xl shadow-lg p-4 text-center text-sm text-slate-400">
      No se encontraron resultados para "{{ query }}"
    </div>
  </div>
</template>
