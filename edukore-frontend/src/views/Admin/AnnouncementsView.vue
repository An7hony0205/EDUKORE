<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/axios'

const announcements = ref([])
const showModal = ref(false)
const form = ref({
  title: '',
  body: '',
  target_role: '',
  is_published: false
})

const fetchAnnouncements = async () => {
  const res = await api.get('/announcements')
  announcements.value = res.data
}

onMounted(fetchAnnouncements)

const saveAnnouncement = async () => {
  const payload = { ...form.value }
  if (payload.target_role === '') payload.target_role = null
  
  await api.post('/announcements', payload)
  showModal.value = false
  form.value = { title: '', body: '', target_role: '', is_published: false }
  fetchAnnouncements()
}

const deleteAnnouncement = async (id) => {
  if (!confirm('¿Eliminar anuncio?')) return
  await api.delete(`/announcements/${id}`)
  fetchAnnouncements()
}

const togglePublish = async (id, currentStatus) => {
  await api.put(`/announcements/${id}`, { is_published: !currentStatus })
  fetchAnnouncements()
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Anuncios y Circulares</h1>
        <p class="text-slate-400 text-sm">Gestiona la comunicación con estudiantes y apoderados</p>
      </div>
      <button @click="showModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
        + Nuevo Anuncio
      </button>
    </div>

    <!-- Lista -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-if="announcements.length === 0" class="col-span-full text-slate-400 p-8 text-center bg-brand-surface rounded-xl border border-brand-border">
        No hay anuncios creados.
      </div>
      
      <div v-for="ann in announcements" :key="ann.id" class="bg-brand-surface border border-brand-border rounded-xl p-5 shadow-lg flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between mb-2">
            <span :class="['text-[10px] font-bold px-2 py-1 rounded uppercase', ann.is_published ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-500/20 text-slate-400']">
              {{ ann.is_published ? 'Publicado' : 'Borrador' }}
            </span>
            <span class="text-xs font-semibold px-2 py-1 rounded bg-indigo-500/10 text-indigo-400">
              Público: {{ ann.target_role || 'Todos' }}
            </span>
          </div>
          <h3 class="text-lg font-bold text-white mb-1">{{ ann.title }}</h3>
          <p class="text-sm text-slate-300 line-clamp-3 mb-4">{{ ann.body }}</p>
        </div>
        
        <div class="flex items-center justify-between border-t border-brand-border pt-4 mt-auto">
          <p class="text-xs text-slate-500">Por: {{ ann.author?.name }}</p>
          <div class="flex gap-2">
            <button @click="togglePublish(ann.id, ann.is_published)" class="text-xs font-medium text-slate-300 hover:text-white px-3 py-1 bg-black/20 rounded border border-brand-border">
              {{ ann.is_published ? 'Ocultar' : 'Publicar' }}
            </button>
            <button @click="deleteAnnouncement(ann.id)" class="text-xs font-medium text-rose-400 hover:bg-rose-500/10 px-3 py-1 rounded border border-transparent hover:border-rose-500/20">
              Eliminar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Nuevo Anuncio -->
    <div v-if="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-brand-surface border border-brand-border w-full max-w-md rounded-2xl shadow-2xl p-6">
        <h2 class="text-xl font-bold text-white mb-4">Redactar Anuncio</h2>
        
        <div class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-400 mb-1">Título</label>
            <input v-model="form.title" type="text" class="w-full bg-brand-dark border border-brand-border rounded-lg px-3 py-2 text-white text-sm" placeholder="Ej: Suspensión de clases" />
          </div>
          
          <div>
            <label class="block text-xs font-medium text-slate-400 mb-1">Público Objetivo</label>
            <select v-model="form.target_role" class="w-full bg-brand-dark border border-brand-border rounded-lg px-3 py-2 text-white text-sm">
              <option value="">Toda la comunidad (Todos)</option>
              <option value="Parent">Solo Apoderados</option>
              <option value="Student">Solo Estudiantes</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-400 mb-1">Contenido</label>
            <textarea v-model="form.body" rows="4" class="w-full bg-brand-dark border border-brand-border rounded-lg px-3 py-2 text-white text-sm" placeholder="Escribe el cuerpo del mensaje..."></textarea>
          </div>
          
          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="form.is_published" id="ispub" class="rounded bg-brand-dark border-brand-border text-indigo-500" />
            <label for="ispub" class="text-sm text-slate-300">Publicar inmediatamente</label>
          </div>
        </div>

        <div class="mt-6 flex gap-3">
          <button @click="showModal = false" class="flex-1 px-4 py-2 bg-brand-dark border border-brand-border rounded-lg text-slate-300 hover:text-white text-sm font-medium">Cancelar</button>
          <button @click="saveAnnouncement" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">Guardar</button>
        </div>
      </div>
    </div>
  </div>
</template>
