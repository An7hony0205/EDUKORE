# -*- coding: utf-8 -*-
import codecs

def convert_to_utf8(filepath):
    with codecs.open(filepath, 'r', 'utf-16') as f:
        content = f.read()
    with codecs.open(filepath, 'w', 'utf-8') as f:
        f.write(content)

convert_to_utf8('src/views/Admin/AcademicStructureView.vue')
convert_to_utf8('src/views/Admin/SectionDetailView.vue')
