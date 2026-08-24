<template>
  <DashboardLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Docentes</h1>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Gestión de personal académico</p>
        </div>
        <button @click="openModal()" class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
          + Registrar docente
        </button>
      </div>
      <!-- Toolbar -->
      <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-xl p-4 flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="relative w-full sm:w-96">
          <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
          <input v-model="search" @keyup.enter="fetchTeachers(1)" type="text" placeholder="Buscar por nombre o DNI..." class="w-full bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-lg pl-10 pr-4 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500">
        </div>
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <label class="text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">Ordenar:</label>
          <select v-model="sort" @change="fetchTeachers(1)" class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500 w-full sm:w-auto">
            <option value="recent">Más recientes</option>
            <option value="oldest">Más antiguos</option>
          </select>
        </div>
      </div>
      <!-- Table -->
      <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-xl overflow-hidden relative min-h-[300px]">
        <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white dark:bg-slate-900/80 backdrop-blur-sm">
          <svg class="animate-spin h-8 w-8 text-slate-900 dark:text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
            <thead class="bg-slate-50 dark:bg-slate-50 dark:bg-slate-800/50 text-xs uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="px-6 py-4 font-semibold">Docente</th>
                <th class="px-6 py-4 font-semibold">DNI</th>
                <th class="px-6 py-4 font-semibold">Contacto</th>
                <th class="px-6 py-4 font-semibold">Estado</th>
                <th class="px-6 py-4 font-semibold">Fecha Registro</th>
                <th class="px-6 py-4 font-semibold text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-brand-border">
              <tr v-if="teachers.length === 0 && !isLoading">
                <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                  No se encontraron docentes registrados.
                </td>
              </tr>
              <tr v-for="teacher in teachers" :key="teacher.id" class="hover:bg-slate-50 dark:hover:bg-slate-50 dark:bg-slate-800/30 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-primary-900/50 flex items-center justify-center text-primary-400 font-bold border border-primary-500/20">
                      {{ teacher.name.charAt(0) }}
                    </div>
                    <span class="font-medium text-slate-900 dark:text-white">{{ teacher.name }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">{{ teacher.teacher_profile?.dni || '-' }}</td>
                <td class="px-6 py-4">
                  <div class="flex flex-col">
                    <span>{{ teacher.email }}</span>
                    <span class="text-xs text-slate-500">{{ teacher.teacher_profile?.phone || '-' }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span :class="[
                    'px-2.5 py-1 text-xs font-medium rounded-full border',
                    teacher.is_active 
                      ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                      : 'bg-red-500/10 text-red-400 border-red-500/20'
                  ]">
                    {{ teacher.is_active ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ formatDate(teacher.created_at) }}</td>
                <td class="px-6 py-4 text-right">
                  <button @click="openModal(teacher)" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white p-2" title="Editar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                  </button>
                  <button @click="toggleStatus(teacher)" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white p-2 ml-1" :title="teacher.is_active ? 'Desactivar' : 'Activar'">
                    <svg v-if="teacher.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                    <svg v-else class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30 flex items-center justify-between">
          <p class="text-sm text-slate-500 dark:text-slate-400">
            Mostrando <span class="font-medium text-slate-900 dark:text-white">{{ pagination.from }}</span> a <span class="font-medium text-slate-900 dark:text-white">{{ pagination.to }}</span> de <span class="font-medium text-slate-900 dark:text-white">{{ pagination.total }}</span> docentes
          </p>
          <div class="flex gap-2">
            <button 
              @click="fetchTeachers(pagination.current_page - 1)" 
              :disabled="pagination.current_page === 1"
              class="px-3 py-1 bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded text-sm text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:text-white disabled:opacity-50 transition-colors"
            >
              Anterior
            </button>
            <button 
              @click="fetchTeachers(pagination.current_page + 1)" 
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1 bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded text-sm text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:text-white disabled:opacity-50 transition-colors"
            >
              Siguiente
            </button>
          </div>
        </div>
      </div>
      <!-- Form Modal -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
        
        <div class="bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-2xl w-full max-w-2xl z-10 overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
          <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-50 dark:bg-slate-800/50">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ editMode ? 'Editar Docente' : 'Registrar Docente' }}</h3>
            <button @click="closeModal" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
          </div>
          
          <div class="p-6 overflow-y-auto flex-1">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Nombre Completo *</label>
                <input v-model="form.name" type="text" class="w-full bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500" placeholder="Ej. Juan Pérez">
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">DNI *</label>
                <input v-model="form.dni" type="text" class="w-full bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500" placeholder="Documento de identidad">
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Correo Electrónico *</label>
                <input v-model="form.email" type="email" class="w-full bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500" placeholder="juan@ejemplo.com">
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Teléfono</label>
                <input v-model="form.phone" type="text" class="w-full bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500" placeholder="Opcional">
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Dirección</label>
                <input v-model="form.address" type="text" class="w-full bg-white border-slate-300 border dark:bg-slate-800 dark:border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:focus:border-slate-500 dark:focus:ring-slate-500" placeholder="Opcional">
              </div>
              
              <div v-if="!editMode" class="md:col-span-2 bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-4 flex gap-3">
                <svg class="w-5 h-5 text-indigo-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="text-sm text-indigo-200">
                  El docente será registrado con el rol "Teacher". Se generará una contraseña aleatoria temporal automáticamente. Dile al docente que ingrese con su correo electrónico y use la opción "Olvidé mi contraseña" para establecer una propia, o comunícasela por un medio seguro si implementas una lógica de reseteo.
                </p>
              </div>
            </div>
          </div>
          
          <div class="p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3 shrink-0">
            <button @click="closeModal" type="button" class="px-5 py-2.5 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
              Cancelar
            </button>
            <button @click="submitForm" :disabled="isSubmitting" class="bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200 px-6 py-2.5 rounded-xl text-sm font-medium transition-colors disabled:opacity-50 flex items-center gap-2">
              <svg v-if="isSubmitting" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              {{ editMode ? 'Guardar Cambios' : 'Registrar Docente' }}
            </button>
          </div>
        </div>
      </div>
    </div> <!-- Contenedor space-y-6 cerrado correctamente -->
  </DashboardLayout>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api/axios'
import DashboardLayout from '../../layouts/DashboardLayout.vue'
const search = ref('')
const sort = ref('recent')
const teachers = ref([])
const pagination = ref({ current_page: 1, last_page: 1, from: 0, to: 0, total: 0 })
const isLoading = ref(false)
const showModal = ref(false)
const editMode = ref(false)
const isSubmitting = ref(false)
const selectedTeacherId = ref(null)
const form = ref({
  name: '',
  email: '',
  dni: '',
  phone: '',
  address: ''
})
const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
const fetchTeachers = async (page = 1) => {
  isLoading.value = true
  try {
    const res = await api.get(`/teachers?page=${page}&search=${search.value}&sort=${sort.value}`)
    teachers.value = res.data.data || res.data
    pagination.value = {
      current_page: res.data.current_page || 1,
      last_page: res.data.last_page || 1,
      from: res.data.from || 0,
      to: res.data.to || 0,
      total: res.data.total || teachers.value.length
    }
  } catch (error) {
    console.error("Error fetching teachers", error)
  } finally {
    isLoading.value = false
  }
}
const openModal = (teacher = null) => {
  if (teacher) {
    editMode.value = true
    selectedTeacherId.value = teacher.id
    form.value = {
      name: teacher.name,
      email: teacher.email,
      dni: teacher.teacher_profile?.dni || '',
      phone: teacher.teacher_profile?.phone || '',
      address: teacher.teacher_profile?.address || ''
    }
  } else {
    editMode.value = false
    selectedTeacherId.value = null
    form.value = {
      name: '',
      email: '',
      dni: '',
      phone: '',
      address: ''
    }
  }
  showModal.value = true
}
const closeModal = () => {
  showModal.value = false
}
const submitForm = async () => {
  if (!form.value.name || !form.value.email || !form.value.dni) {
    alert("Nombre, Correo y DNI son obligatorios.")
    return
  }
  isSubmitting.value = true
  try {
    if (editMode.value) {
      await api.put(`/teachers/${selectedTeacherId.value}`, form.value)
      alert("Información del docente actualizada correctamente.")
    } else {
      await api.post('/teachers', form.value)
      alert("Docente registrado correctamente.")
    }
    closeModal()
    fetchTeachers(pagination.value.current_page)
  } catch (error) {
    const msg = error.response?.data?.message || "Ocurrió un error al guardar."
    alert(msg)
  } finally {
    isSubmitting.value = false
  }
}
const toggleStatus = async (teacher) => {
  if (!confirm(`¿Estás seguro de que deseas ${teacher.is_active ? 'desactivar' : 'activar'} a este docente?`)) return
  
  try {
    const res = await api.patch(`/teachers/${teacher.id}/status`)
    alert(res.data.message || "Estado actualizado correctamente.")
    fetchTeachers(pagination.value.current_page)
  } catch (error) {
    alert(error.response?.data?.message || "Error al actualizar estado")
  }
}
onMounted(() => {
  fetchTeachers()
})
</script>
