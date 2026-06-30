<script setup>
import DashboardLayout from '../layouts/DashboardLayout.vue'
</script>

<template>
  <DashboardLayout>
    <div class="p-8 space-y-6">
      <!-- Stats row -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard label="Estudiantes" value="—" icon="users" color="#6366f1" />
        <StatCard label="Cursos activos" value="—" icon="book-open" color="#8b5cf6" />
        <StatCard label="Tareas pendientes" value="—" icon="clipboard" color="#06b6d4" />
        <StatCard label="Tasa de finalización" value="—" icon="trending-up" color="#10b981" />
      </div>

      <!-- Placeholder content panel -->
      <div class="rounded-2xl p-6 border border-brand-border bg-brand-surface/30">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-2 h-2 rounded-full bg-primary-600"></div>
          <h3 class="text-sm font-semibold text-slate-200">Actividad reciente</h3>
        </div>
        <div class="space-y-3">
          <div
            v-for="i in 5"
            :key="i"
            class="flex items-center gap-4 rounded-xl px-4 py-3 bg-white/5"
          >
            <div class="w-8 h-8 rounded-lg shrink-0 bg-primary-600/15"></div>
            <div class="flex-1 space-y-1.5">
              <div class="h-2.5 rounded-full w-2/3 bg-white/10"></div>
              <div class="h-2 rounded-full w-1/3 bg-white/5"></div>
            </div>
            <div class="h-2 rounded-full w-16 shrink-0 bg-white/10"></div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script>
import { defineComponent, h } from 'vue'

const iconPaths = {
  users: '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
  'book-open': '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
  clipboard: '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>',
  'trending-up': '<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>',
}

function FeatherIcon(name, size = 16) {
  return h('svg', {
    xmlns: 'http://www.w3.org/2000/svg',
    width: size, height: size,
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    'stroke-width': '2',
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    innerHTML: iconPaths[name] ?? '',
  })
}

export default defineComponent({
  components: {
    StatCard: defineComponent({
      props: { label: String, value: String, icon: String, color: String },
      setup(props) {
        return () => h('div', {
          class: 'rounded-2xl p-5 border',
          style: 'background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.07);',
        }, [
          h('div', { class: 'flex items-center justify-between mb-3' }, [
            h('span', { class: 'text-xs font-semibold uppercase tracking-widest', style: 'color:#64748b;' }, props.label),
            h('div', {
              class: 'w-8 h-8 rounded-lg flex items-center justify-center',
              style: `background: ${props.color}22;`,
            }, [
              h('span', { style: `color:${props.color};` }, FeatherIcon(props.icon, 15)),
            ]),
          ]),
          h('p', { class: 'text-2xl font-bold', style: 'color:#f0f4ff;' }, props.value),
        ])
      },
    }),
  },
})
</script>
