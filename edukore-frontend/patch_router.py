# -*- coding: utf-8 -*-
import sys

with open('src/router/index.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Add import
import_str = "const CoursesList = () => import('../views/CoursesList.vue')\nconst CourseDetailView = () => import('../views/CourseDetailView.vue')"
content = content.replace("const CoursesList = () => import('../views/CoursesList.vue')", import_str)

# Add route
route_str = """    {
      path: '/courses/:id',
      name: 'course-detail',
      component: CourseDetailView,
      meta: { requiresAuth: true },
    },
    {
      path: '/courses',"""
content = content.replace("    {\n      path: '/courses',", route_str)

with open('src/router/index.js', 'w', encoding='utf-8') as f:
    f.write(content)
