<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import DashboardLayout from '../layouts/DashboardLayout.vue'
import AdminDashboard from './AdminDashboard.vue'
import TeacherDashboard from './TeacherDashboard.vue'

const auth = useAuthStore()

const currentRole = computed(() => {
  return auth.user?.role?.name || 'Student'
})
</script>

<template>
  <DashboardLayout>
    <template v-if="currentRole === 'Admin'">
      <AdminDashboard />
    </template>
    <template v-else-if="currentRole === 'Teacher'">
      <TeacherDashboard />
    </template>
    <template v-else>
      <div class="p-8 text-center text-slate-400">
        Bienvenido a EDUKORE.
      </div>
    </template>
  </DashboardLayout>
</template>


