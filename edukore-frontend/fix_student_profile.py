import sys

with open('src/views/StudentProfile.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add attendanceData
content = content.replace(
    "const financesData = ref(null)",
    "const financesData = ref(null)\nconst attendanceData = ref(null)"
)

# 2. Add fetch in loadTab
load_tab_old = """    } else if (tab === 'finances' && !financesData.value) {
      try {
        const res = await api.get(`/students/${route.params.id}/finances`)
        financesData.value = res.data
      } catch (error) {
        console.error("Failed to fetch finances data", error)
      }
    }"""
load_tab_new = """    } else if (tab === 'finances' && !financesData.value) {
      try {
        const res = await api.get(`/students/${route.params.id}/finances`)
        financesData.value = res.data
      } catch (error) {
        console.error("Failed to fetch finances data", error)
      }
    } else if (tab === 'attendance' && !attendanceData.value) {
      try {
        const res = await api.get(`/students/${route.params.id}/attendance`)
        attendanceData.value = res.data.data
      } catch (error) {
        console.error("Failed to fetch attendance data", error)
      }
    }"""
content = content.replace(load_tab_old, load_tab_new)

# 3. Add attendance tab UI
placeholder_old = """          <div v-else class="text-center py-12">
            <p class="text-slate-400">Módulo en construcción.</p>
          </div>"""
          
attendance_tab_ui = """          <div v-else-if="activeTab === 'attendance'">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium text-white">Registro de Asistencia</h3>
            </div>
            
            <div v-if="attendanceData === null" class="text-slate-500">Cargando...</div>
            <div v-else-if="attendanceData.length === 0" class="text-slate-400 bg-white/5 p-6 rounded-lg text-center border border-white/5">
              No hay registros de asistencia para este estudiante.
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-white/10 text-xs uppercase tracking-wider text-slate-400">
                    <th class="py-3 px-4 font-medium">Fecha</th>
                    <th class="py-3 px-4 font-medium">Curso</th>
                    <th class="py-3 px-4 font-medium">Estado</th>
                    <th class="py-3 px-4 font-medium">Notas</th>
                  </tr>
                </thead>
                <tbody class="text-sm">
                  <tr v-for="att in attendanceData" :key="att.id" class="border-b border-white/5 hover:bg-white/5 transition-colors">
                    <td class="py-3 px-4 text-slate-300">{{ att.date }}</td>
                    <td class="py-3 px-4 text-white font-medium">{{ att.course_assignment?.course?.name || 'Desconocido' }}</td>
                    <td class="py-3 px-4">
                      <span :class="{
                        'bg-emerald-500/10 text-emerald-400': att.status === 'Presente',
                        'bg-rose-500/10 text-rose-400': att.status === 'Ausente',
                        'bg-amber-500/10 text-amber-400': att.status === 'Tardanza',
                        'bg-blue-500/10 text-blue-400': att.status === 'Justificado'
                      }" class="px-2 py-1 rounded-full text-xs font-medium uppercase tracking-wider">
                        {{ att.status }}
                      </span>
                    </td>
                    <td class="py-3 px-4 text-slate-400">{{ att.notes || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <div v-else class="text-center py-12">
            <p class="text-slate-400">Módulo en construcción.</p>
          </div>"""
content = content.replace(placeholder_old, attendance_tab_ui)

# 4. Fix relationship_type
content = content.replace("parent.pivot.relationship", "parent.pivot.relationship_type")

with open('src/views/StudentProfile.vue', 'w', encoding='utf-8') as f:
    f.write(content)
