# -*- coding: utf-8 -*-
with open(r'c:\Users\Anthony\Desktop\EDUKORE\edukore-frontend\src\views\CourseAssignmentsView.vue', 'r', encoding='utf-8', errors='ignore') as f:
    text = f.read()

replacements = {
    'AsignaciÃ³n': 'Asignación',
    'AsignaciÃƒÂ³n': 'Asignación',
    'SecciÃ³n': 'Sección',
    'SecciÃƒÂ³n': 'Sección',
    'Nivel AcadÃ©mico': 'Nivel Académico',
    'Nivel AcadÃƒÂ©mico': 'Nivel Académico',
    'MiÃ©rcoles': 'Miércoles',
    'MiÃƒÂ©rcoles': 'Miércoles',
    'OcurriÃƒÆ’Ã‚Â³': 'Ocurrió',
    'OcurriÃ³': 'Ocurrió',
    'ÃƒÆ’Ã‚Âºnicamente': 'únicamente',
    'ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢': '→',
    'ÃƒÂ¢Ã¢â‚¬Â Ã¢â€šÂ¬': '─',
    'acadÃƒÆ’Ã‚Â©mica': 'académica',
    'acadÃ©mica': 'académica',
    'AsignaciÃƒÆ’Ã‚Â³n': 'Asignación',
    'SecciÃƒÆ’Ã‚Â³n': 'Sección',
    'AcadÃƒÆ’Ã‚Â©mico': 'Académico',
}

for k, v in replacements.items():
    text = text.replace(k, v)

with open(r'c:\Users\Anthony\Desktop\EDUKORE\edukore-frontend\src\views\CourseAssignmentsView.vue', 'w', encoding='utf-8', newline='\n') as f:
    f.write(text)
