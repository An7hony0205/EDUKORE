<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Académico</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .page-break { page-break-after: always; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; color: #333; }
        .student-info { margin-bottom: 20px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #777; }
    </style>
</head>
<body>
    @foreach ($studentsData as $student)
    <div class="{{ !$loop->last ? 'page-break' : '' }}">
        <div class="header">
            <h1>Libreta de Calificaciones</h1>
            <p>EduKore SaaS Oficial</p>
        </div>
        
        <div class="student-info">
            Estudiante: {{ $student['student_name'] }}
        </div>

        <table>
            <thead>
                <tr>
                    <th>Asignatura</th>
                    <th>Promedio Final</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($student['courses'] as $course)
                <tr>
                    <td>{{ $course['course_name'] }}</td>
                    <td>{{ $course['average'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <p style="margin-top: 50px;">
            ___________________________<br>
            Firma del Director
        </p>
    </div>
    @endforeach
</body>
</html>
