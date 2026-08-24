# -*- coding: utf-8 -*-
import sys
import re

with open('src/views/CoursesList.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace script
script_replacement = """<script setup>
import { ref, onMounted } from 'vue'
import { useAcademicStore } from '../stores/academic'
import DashboardLayout from '../layouts/DashboardLayout.vue'

const academicStore = useAcademicStore()

const isModalOpen = ref(false)
const isSubmitting = ref(false)
const formData = ref({
  code: '',
  name: '',
  description: ''
})
const formError = ref('')

const openModal = () => {
  formData.value = { code: '', name: '', description: '' }
  formError.value = ''
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

const submitCourse = async () => {
  if (!formData.value.name || !formData.value.code) {
    formError.value = 'El código y el nombre son obligatorios.'
    return
  }
  
  isSubmitting.value = true
  formError.value = ''
  
  try {
    await academicStore.createCourse({
      code: formData.value.code.toUpperCase(),
      name: formData.value.name,
      description: formData.value.description
    })
    closeModal()
  } catch (error) {
    formError.value = error.response?.data?.message || 'Error al crear el curso. Revisa los datos.'
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  academicStore.fetchCourses()
})
</script>"""

content = re.sub(r'<script setup>.*?</script>', script_replacement, content, flags=re.DOTALL)

# Add #actions block to template
header_actions = """    <template #title>Cursos</template>
    <template #subtitle>Gestiona los cursos académicos</template>
    <template #actions>
      <button @click="openModal" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors shadow-lg shadow-indigo-500/20 flex items-center gap-2 border border-indigo-500/50">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nuevo Curso
      </button>
    </template>"""

content = content.replace("    <template #title>Cursos</template>\n    <template #subtitle>Gestiona los cursos académicos</template>", header_actions)

# Add Modal at the end of the template
modal_ui = """    </div>

    <!-- Create Course Modal -->
    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
      
      <div class="relative w-full max-w-md bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl overflow-hidden animate-fade-in-up">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center bg-slate-800/50">
          <h3 class="text-lg font-bold text-white">Crear Nuevo Curso</h3>
          <button @click="closeModal" class="text-slate-400 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6 space-y-4">
          <div v-if="formError" class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            {{ formError }}
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1">Código del Curso</label>
            <input v-model="formData.code" type="text" placeholder="Ej: MAT-101" class="w-full bg-black/20 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors uppercase">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1">Nombre</label>
            <input v-model="formData.name" type="text" placeholder="Ej: Matemáticas Avanzadas" class="w-full bg-black/20 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-slate-300 mb-1">Descripción (Opcional)</label>
            <textarea v-model="formData.description" rows="3" placeholder="Breve descripción del curso..." class="w-full bg-black/20 border border-slate-700 rounded-lg px-4 py-2.5 text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors resize-none"></textarea>
          </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-slate-800 bg-slate-800/30 flex justify-end gap-3">
          <button @click="closeModal" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white transition-colors">
            Cancelar
          </button>
          <button @click="submitCourse" :disabled="isSubmitting" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors shadow-lg shadow-indigo-500/20">
            <span v-if="isSubmitting">Guardando...</span>
            <span v-else>Crear Curso</span>
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>"""

content = content.replace("    </div>\n  </DashboardLayout>", modal_ui)

# Update the empty state button to also open modal
content = content.replace(
    '<button class="mt-6 px-6 py-3 rounded-xl',
    '<button @click="openModal" class="mt-6 px-6 py-3 rounded-xl'
)

with open('src/views/CoursesList.vue', 'w', encoding='utf-8') as f:
    f.write(content)
