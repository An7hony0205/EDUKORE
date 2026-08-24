# -*- coding: utf-8 -*-
import os
import re

models_dir = 'app/Models'
models = ['AcademicYear', 'Level', 'GradeLevel', 'Section', 'Course', 'CourseAssignment', 'Student', 'Enrollment', 'User', 'TeacherProfile', 'AcademicPeriod', 'Evaluation', 'Grade', 'Attendance']

for model in models:
    file_path = os.path.join(models_dir, f"{model}.php")
    if os.path.exists(file_path):
        print(f"--- {model} ---")
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
            methods = re.findall(r'public function (\w+)\(\)\s*\{[^{}]*return \$this->(belongsTo|hasMany|hasOne|belongsToMany)\b', content)
            for m in methods:
                print(f"  {m[0]}() -> {m[1]}")
