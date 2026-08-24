# -*- coding: utf-8 -*-
import sys
import re

with open('src/views/CoursesList.vue', 'r', encoding='utf-8') as f:
    content = f.read()

header = """    <div class="px-8 pt-8 pb-4 flex justify-between items-end">
      <div>
        <h1 class="text-3xl font-bold text-white mb-2">Cursos</h1>
        <p class="text-slate-400">Gestiona los cursos académicos</p>
      </div>
      <button @click="openModal" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors shadow-lg shadow-indigo-500/20 flex items-center gap-2 border border-indigo-500/50">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nuevo Curso
      </button>
    </div>
    
    <div class="px-8 pb-8">"""

content = content.replace("    <template #title>Cursos</template>\n    <template #subtitle>Gestiona los cursos académicos</template>\n    <template #actions>\n      <button @click=\"openModal\" class=\"px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition-colors shadow-lg shadow-indigo-500/20 flex items-center gap-2 border border-indigo-500/50\">\n        <svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\">\n          <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 4v16m8-8H4\" />\n        </svg>\n        Nuevo Curso\n      </button>\n    </template>\n\n    <div class=\"p-8\">", header)

with open('src/views/CoursesList.vue', 'w', encoding='utf-8') as f:
    f.write(content)
