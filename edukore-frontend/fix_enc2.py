# -*- coding: utf-8 -*-
def fix_encoding(filepath):
    try:
        with open(filepath, 'r', encoding='windows-1252') as f:
            content = f.read()
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
    except Exception as e:
        print(e)

fix_encoding('src/views/Admin/AcademicStructureView.vue')
fix_encoding('src/views/Admin/SectionDetailView.vue')
