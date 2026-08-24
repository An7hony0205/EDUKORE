# -*- coding: utf-8 -*-
import sys

with open('src/views/CourseAssignmentsView.vue', 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace("api.get('/courses')", "api.get('/courses?status=active')")

with open('src/views/CourseAssignmentsView.vue', 'w', encoding='utf-8') as f:
    f.write(content)
