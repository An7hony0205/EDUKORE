# -*- coding: utf-8 -*-
with open('src/views/Teacher/TeacherDashboard.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix encoding issue with 'Sección' that might have happened before
content = content.replace("SecciÃ³n", "Sección")

# Add student count
target = """<span class="font-medium text-slate-300">Sección {{ assignment.section?.name }}</span>
          </p>"""
replacement = """<span class="font-medium text-slate-300">Sección {{ assignment.section?.name }}</span>
          </p>
          <div class="mt-4 flex items-center gap-2 text-sm text-slate-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            {{ assignment.students_count }} estudiantes
          </div>"""

content = content.replace(target, replacement)

# Change text "Abrir Gradebook" to "Ver Curso"
content = content.replace("Abrir Gradebook &rarr;", "Ver Curso &rarr;")

with open('src/views/Teacher/TeacherDashboard.vue', 'w', encoding='utf-8') as f:
    f.write(content)
