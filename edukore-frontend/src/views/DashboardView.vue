<script setup>
import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import { watchEffect } from 'vue'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import AdminDashboard from './AdminDashboard.vue'
import TeacherDashboard from './Teacher/TeacherDashboard.vue'

const auth = useAuthStore()
const router = useRouter()

watchEffect(() => {
  if (auth.user) {
    if (auth.isStudent) {
      router.replace('/student-portal')
    } else if (auth.isParent) {
      router.replace('/parent-portal')
    }
  }
})
</script>

<template>
  <template v-if="auth.isAdmin">
    <DashboardLayout>
      <AdminDashboard />
    </DashboardLayout>
  </template>
  
  <template v-else-if="auth.isTeacher">
    <!-- TeacherDashboard ya incluye su propio DashboardLayout interno -->
    <TeacherDashboard />
  </template>
  
  <template v-else>
    <DashboardLayout>
      <div class="p-8 text-center text-slate-500 dark:text-slate-400">
        <span v-if="!auth.user">Cargando perfil...</span>
        <span v-else>Bienvenido a EDUKORE. Redirigiendo...</span>
      </div>
    </DashboardLayout>
  </template>
</template>
