# -*- coding: utf-8 -*-
import sys

with open('src/layouts/DashboardLayout.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Add Estructura Académica link
nav_link = """
            <router-link v-if="auth.user?.role?.name === 'Admin'" to="/academic-structure" custom v-slot="{ isActive, navigate }">
              <NavItem icon="folder" label="Estructura Académica" :active="isActive" @click="navigate(); isMobileClose()" />
            </router-link>
            
            <router-link v-if="auth.user?.role?.name === 'Admin'" to="/courses" custom v-slot="{ isActive,"""

content = content.replace("<router-link v-if=\"auth.user?.role?.name === 'Admin'\" to=\"/courses\" custom v-slot=\"{ isActive,", nav_link)

with open('src/layouts/DashboardLayout.vue', 'w', encoding='utf-8') as f:
    f.write(content)
