import sys
import re

with open('src/router/index.js', 'r', encoding='utf-8') as f:
    content = f.read()

route_str = """  {
    path: '/courses/:id',
    name: 'course-detail',
    component: CourseDetailView,
    meta: { requiresAuth: true },
  },
  {
    path: '/courses',"""

content = re.sub(r"  \{\s*path: '/courses',", route_str, content)

with open('src/router/index.js', 'w', encoding='utf-8') as f:
    f.write(content)
