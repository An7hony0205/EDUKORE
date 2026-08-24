# -*- coding: utf-8 -*-
import sys
import re

with open('src/router/index.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Add imports
import_str = "const CoursesList = () => import('../views/CoursesList.vue')\nconst AcademicStructureView = () => import('../views/Admin/AcademicStructureView.vue')\nconst SectionDetailView = () => import('../views/Admin/SectionDetailView.vue')"
content = content.replace("const CoursesList = () => import('../views/CoursesList.vue')", import_str)

# Add routes
route_str = """  {
    path: '/academic-structure',
    name: 'academic-structure',
    component: AcademicStructureView,
    meta: { requiresAuth: true },
  },
  {
    path: '/academic-structure/sections/:id',
    name: 'section-detail',
    component: SectionDetailView,
    meta: { requiresAuth: true },
  },
  {
    path: '/courses',"""
    
content = re.sub(r"  \{\s*path: '/courses',", route_str, content)

with open('src/router/index.js', 'w', encoding='utf-8') as f:
    f.write(content)
