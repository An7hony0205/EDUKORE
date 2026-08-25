import sys
with open(r'c:\Users\Anthony\Desktop\EDUKORE\edukore-frontend\src\views\CourseAssignmentsView.vue', 'rb') as f:
    b = f.read()

b = b.replace(b'Asignaci\xc3\x83\xc2\xb3n', b'Asignaci\xc3\xb3n')
b = b.replace(b'Asignaci\xc3\x83\xc6\x92\xc3\x82\xc2\xb3n', b'Asignaci\xc3\xb3n')
b = b.replace(b'Secci\xc3\x83\xc2\xb3n', b'Secci\xc3\xb3n')
b = b.replace(b'Secci\xc3\x83\xc6\x92\xc3\x82\xc2\xb3n', b'Secci\xc3\xb3n')
b = b.replace(b'Acad\xc3\x83\xc2\xa9mico', b'Acad\xc3\xa9mico')
b = b.replace(b'Mi\xc3\x83\xc2\xa9rcoles', b'Mi\xc3\xa9rcoles')
b = b.replace(b'Ocurri\xc3\x83\xc2\xb3', b'Ocurri\xc3\xb3')
b = b.replace(b'Ocurri\xc3\x83\xc6\x92\xc3\x82\xc2\xb3', b'Ocurri\xc3\xb3')
b = b.replace(b'\xc3\x83\xc6\x92\xc3\x82\xc2\xbanicamente', b'\xc3\xbanicamente')
b = b.replace(b'acad\xc3\x83\xc2\xa9mica', b'acad\xc3\xa9mica')
b = b.replace(b'acad\xc3\x83\xc6\x92\xc3\x82\xc2\xa9mica', b'acad\xc3\xa9mica')
b = b.replace(b'\xc3\x83\xc2\xa2\xc3\xa2\xe2\x82\xac\xc2\xa0\xc3\xa2\xe2\x82\xac\xe2\x84\xa2', b'->')
b = b.replace(b'\xc3\x83\xc2\xa2\xc3\xa2\xe2\x82\xac\xc2\x9d\xc3\xa2\xe2\x80\x9a\xc2\xac', b'-')

with open(r'c:\Users\Anthony\Desktop\EDUKORE\edukore-frontend\src\views\CourseAssignmentsView.vue', 'wb') as f:
    f.write(b)
