<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Boletín de Notas</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .student-info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Boletín de Calificaciones</h2>
        <p>EDUKORE Institución Educativa</p>
    </div>

    <div class="student-info">
        <strong>Estudiante:</strong> {{ $data['student_name'] }}<br>
        <strong>Email:</strong> {{ $data['student_email'] }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Curso</th>
                <th>Promedio Final</th>
                <th>Asistencias</th>
                <th>Faltas</th>
                <th>Tardanzas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['courses'] as $course)
                <tr>
                    <td>{{ $course['course_name'] }}</td>
                    <td>{{ $course['average'] }}</td>
                    <td>{{ $course['attendance']['present'] }}</td>
                    <td>{{ $course['attendance']['absent'] }}</td>
                    <td>{{ $course['attendance']['late'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
