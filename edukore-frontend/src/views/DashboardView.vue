<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import AdminDashboard from './AdminDashboard.vue'
import TeacherDashboard from './TeacherDashboard.vue'

const auth = useAuthStore()

const currentRole = computed(() => {
  return auth.user?.role?.name || 'student'
})
</script>

<template>
  <DashboardLayout>
    <template v-if="currentRole === 'admin'">
      <AdminDashboard />
    </template>
    <template v-else-if="currentRole === 'teacher'">
      <TeacherDashboard />
    </template>
    <template v-else>
      <div class="p-8 text-center text-slate-500 dark:text-slate-400">
        Bienvenido a EDUKORE.
      </div>
    </template>
  </DashboardLayout>
</template>


