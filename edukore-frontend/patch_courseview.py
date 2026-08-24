# -*- coding: utf-8 -*-
with open('src/views/Teacher/CourseView.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix encoding
content = content.replace("SecciÃ³n", "Sección")

# Add tabs
tabs_old = """    <!-- Tabs -->
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
        </button>"""

tabs_new = """    <!-- Tabs -->
    <div class="border-b border-brand-border overflow-x-auto">
      <nav class="-mb-px flex space-x-8">
        <button 
          @click="activeTab = 'students'"
          :class="[
            activeTab === 'students' 
              ? 'border-primary-500 text-primary-400' 
              : 'border-transparent text-slate-400 hover:text-slate-300 hover:border-slate-300',
            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Estudiantes
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
        <button 
          @click="activeTab = 'gradebook'"
          :class="[
            activeTab === 'gradebook' 
              ? 'border-primary-500 text-primary-400' 
              : 'border-transparent text-slate-400 hover:text-slate-300 hover:border-slate-300',
            'whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Evaluaciones y Calificaciones
        </button>"""

content = content.replace(tabs_old, tabs_new)

# Add students content
content_old = """      <div v-else-if="activeTab === 'gradebook'" class="bg-brand-surface border border-brand-border rounded-2xl p-6 overflow-hidden flex flex-col h-full">"""
content_new = """      <div v-else-if="activeTab === 'students'" class="bg-brand-surface border border-brand-border rounded-2xl p-6 overflow-hidden flex flex-col h-full">
        <h3 class="text-xl font-bold text-white mb-4">Lista de Estudiantes ({{ assignment.section?.enrollments?.length || 0 }})</h3>
        <div class="overflow-y-auto">
          <table class="min-w-full divide-y divide-brand-border">
            <thead class="bg-white/5">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Nombre</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Nº Matrícula</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-brand-border">
              <tr v-for="enrollment in assignment.section?.enrollments" :key="enrollment.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-200">
                  <div class="font-medium text-white">{{ enrollment.student?.user?.name }}</div>
                  <div class="text-slate-500 text-xs">{{ enrollment.student?.user?.email }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                  {{ enrollment.student?.enrollment_number || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-500/10 text-emerald-400">
                    {{ enrollment.status || 'Activo' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else-if="activeTab === 'gradebook'" class="bg-brand-surface border border-brand-border rounded-2xl p-6 overflow-hidden flex flex-col h-full">"""

content = content.replace(content_old, content_new)

# Default tab to 'students'
content = content.replace("const activeTab = ref('gradebook')", "const activeTab = ref('students')")

with open('src/views/Teacher/CourseView.vue', 'w', encoding='utf-8') as f:
    f.write(content)
