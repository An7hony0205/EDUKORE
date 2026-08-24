# -*- coding: utf-8 -*-
import sys
import re

with open('src/views/StudentProfile.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Add ref
content = content.replace("const attendanceData = ref(null)", "const attendanceData = ref(null)\nconst auditData = ref(null)")

# Modify loadTab
old_load_tab = re.search(r'const loadTab = async \(tab\) => \{.*?\n\}', content, re.DOTALL).group(0)
new_load_tab = old_load_tab.replace(
    'console.error("Failed to fetch attendance data", error)\n    }\n  }\n}',
    'console.error("Failed to fetch attendance data", error)\n    }\n  } else if (tab === \'audit\' && !auditData.value) {\n    try {\n      const res = await api.get(`/students/${route.params.id}/audit`)\n      auditData.value = res.data.data\n    } catch (error) {\n      console.error("Failed to fetch audit data", error)\n    }\n  }\n}'
)
content = content.replace(old_load_tab, new_load_tab)

# Add template
placeholder_old = """          <div v-else class="text-center py-12">
            <p class="text-slate-400">Módulo en construcción.</p>
          </div>"""

audit_tab_ui = """          <div v-else-if="activeTab === 'audit'">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium text-white">Auditoria y Log de Actividad</h3>
            </div>
            
            <div v-if="auditData === null" class="text-slate-500">Cargando registros...</div>
            <div v-else-if="auditData.length === 0" class="text-slate-400 bg-white/5 p-6 rounded-lg text-center border border-white/5">
              No hay registros de auditoria para este estudiante.
            </div>
            <div v-else class="space-y-4">
              <div v-for="log in auditData" :key="log.id" class="p-4 bg-white/5 rounded-xl border border-white/10">
                <div class="flex justify-between items-start mb-2">
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded text-xs font-medium uppercase" :class="{
                      'bg-emerald-500/10 text-emerald-400': log.event === 'created',
                      'bg-amber-500/10 text-amber-400': log.event === 'updated',
                      'bg-rose-500/10 text-rose-400': log.event === 'deleted'
                    }">{{ log.event }}</span>
                    <span class="text-white font-medium">{{ log.description }}</span>
                  </div>
                  <span class="text-xs text-slate-500">{{ new Date(log.created_at).toLocaleString() }}</span>
                </div>
                <div class="text-sm text-slate-400 mb-2">
                  Por: <span class="text-slate-300 font-medium">{{ log.causer?.name || 'Sistema' }}</span>
                </div>
                
                <div v-if="log.properties && Object.keys(log.properties).length > 0" class="mt-3 p-3 bg-black/20 rounded border border-white/5 overflow-x-auto">
                  <div v-if="log.properties.old" class="mb-2">
                    <div class="text-xs text-slate-500 mb-1 uppercase tracking-wider font-semibold">Valores Anteriores</div>
                    <pre class="text-xs text-rose-300 m-0">{{ JSON.stringify(log.properties.old, null, 2) }}</pre>
                  </div>
                  <div v-if="log.properties.attributes">
                    <div class="text-xs text-slate-500 mb-1 uppercase tracking-wider font-semibold">Nuevos Valores</div>
                    <pre class="text-xs text-emerald-300 m-0">{{ JSON.stringify(log.properties.attributes, null, 2) }}</pre>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-12">
            <p class="text-slate-400">Módulo en construcción.</p>
          </div>"""

content = content.replace(placeholder_old, audit_tab_ui)

with open('src/views/StudentProfile.vue', 'w', encoding='utf-8') as f:
    f.write(content)
