<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Progreso</title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8.5px;
            margin: 0;
            padding: 0;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .title {
            font-weight: bold;
            font-size: 11px;
            margin: 0;
            padding: 5px 0;
            border: none;
        }
        .info-table {
            margin-bottom: 10px;
        }
        .info-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 9px;
        }
        .info-label {
            font-weight: bold;
            background-color: #e6e6e6;
            width: 15%;
        }
        .grades-table {
            margin-bottom: 15px;
        }
        .grades-table th, .grades-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
            font-size: 8.5px;
        }
        .grades-table th {
            background-color: #d9d9d9;
            font-weight: bold;
        }
        .area-col {
            text-align: left;
            font-weight: bold;
            background-color: #f2f2f2;
            vertical-align: middle;
        }
        .comp-col {
            text-align: left;
            vertical-align: middle;
        }
        .page-break {
            page-break-after: always;
        }
        .signature-section {
            margin-top: 40px;
            width: 100%;
        }
        .signature-box {
            width: 40%;
            display: inline-block;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin: 0 4%;
            font-size: 9px;
        }
        .legend-table {
            margin-top: 15px;
        }
        .legend-table th, .legend-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 8.5px;
        }
        .legend-table th {
            background-color: #f0f0f0;
        }
        .desc-col {
            text-align: left !important;
            font-size: 7.5px !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">INFORME DE PROGRESO DE LAS COMPETENCIAS DEL ESTUDIANTE - {{ date('Y') }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">DRE</td>
            <td>{{ $dre ?? '---' }}</td>
            <td class="info-label">UGEL</td>
            <td>{{ $ugel ?? '---' }}</td>
        </tr>
        <tr>
            <td class="info-label">Nivel</td>
            <td>{{ $section?->grade?->academicLevel?->name ?? 'Secundaria' }}</td>
            <td class="info-label">Código Modular</td>
            <td>{{ $modularCode ?? '---' }}</td>
        </tr>
        <tr>
            <td class="info-label">Institución Educativa</td>
            <td colspan="3">{{ $institutionName ?? '---' }}</td>
        </tr>
        <tr>
            <td class="info-label">Grado y Sección</td>
            <td>{{ $section?->grade?->name ?? '---' }} - "{{ $section?->name ?? '---' }}"</td>
            <td class="info-label">Docente / Tutor</td>
            <td>{{ $section?->tutor?->name ?? 'No asignado' }}</td>
        </tr>
        <tr>
            <td class="info-label">Estudiante</td>
            <td>{{ $student->user?->name ?? ($student->first_name . ' ' . $student->last_name) ?? '---' }}</td>
            <td class="info-label">DNI / Código</td>
            <td>{{ $student->document_number ?? $student->enrollment_number ?? '---' }}</td>
        </tr>
    </table>

    <table class="grades-table">
        <thead>
            <tr>
                <th rowspan="2" width="15%">ÁREA CURRICULAR</th>
                <th rowspan="2" width="30%">COMPETENCIAS EVALUADAS</th>
                <th colspan="4">CALIFICACIÓN POR BIMESTRE</th>
                <th rowspan="2" width="7%">NL FINAL</th>
                <th rowspan="2" width="28%">CONCLUSIÓN DESCRIPTIVA</th>
            </tr>
            <tr>
                <th width="5%">I</th>
                <th width="5%">II</th>
                <th width="5%">III</th>
                <th width="5%">IV</th>
            </tr>
        </thead>
        <tbody>
            @foreach($courses ?? [] as $course)
                @php
                    $competencies = $course->competencies ?? collect([]);
                    $rowspan = max(1, $competencies->count());
                @endphp
                <tr>
                    <td class="area-col" rowspan="{{ $rowspan }}">{{ $course->name ?? '---' }}</td>
                    
                    @if($competencies->count() > 0)
                        @php $firstComp = $competencies->first(); @endphp
                        <td class="comp-col">{{ $firstComp->name ?? '---' }}</td>
                        @php $finalEvalScore = null; $finalEvalDesc = null; @endphp
                        @for($i = 0; $i < 4; $i++)
                            @php 
                                $termId = $terms[$i]->id ?? null;
                                $eval = $termId && $evaluations ? $evaluations->where('competency_id', $firstComp->id)->where('term_id', $termId)->first() : null;
                                if ($eval) {
                                    $finalEvalScore = $eval->score_literal;
                                    $finalEvalDesc = $eval->descriptive_conclusion;
                                }
                            @endphp
                            <td><b>{{ $eval?->score_literal ?? '' }}</b></td>
                        @endfor
                        <td><b>{{ $finalEvalScore ?? '' }}</b></td>
                        <td class="desc-col">{{ $finalEvalDesc ?? '' }}</td>
                    @else
                        <td colspan="7">No hay competencias registradas</td>
                    @endif
                </tr>

                @if($competencies->count() > 1)
                    @foreach($competencies->skip(1) as $comp)
                        <tr>
                            <td class="comp-col">{{ $comp->name ?? '---' }}</td>
                            @php $finalEvalScore = null; $finalEvalDesc = null; @endphp
                            @for($i = 0; $i < 4; $i++)
                                @php 
                                    $termId = $terms[$i]->id ?? null;
                                    $eval = $termId && $evaluations ? $evaluations->where('competency_id', $comp->id)->where('term_id', $termId)->first() : null;
                                    if ($eval) {
                                        $finalEvalScore = $eval->score_literal;
                                        $finalEvalDesc = $eval->descriptive_conclusion;
                                    }
                                @endphp
                                <td><b>{{ $eval?->score_literal ?? '' }}</b></td>
                            @endfor
                            <td><b>{{ $finalEvalScore ?? '' }}</b></td>
                            <td class="desc-col">{{ $finalEvalDesc ?? '' }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <div class="title">COMPETENCIAS TRANSVERSALES</div>
    <table class="grades-table">
        <thead>
            <tr>
                <th rowspan="2" width="45%">COMPETENCIAS</th>
                <th colspan="4">CALIFICACIÓN POR BIMESTRE</th>
                <th rowspan="2" width="7%">NL FINAL</th>
                <th rowspan="2" width="28%">CONCLUSIÓN DESCRIPTIVA</th>
            </tr>
            <tr>
                <th width="5%">I</th>
                <th width="5%">II</th>
                <th width="5%">III</th>
                <th width="5%">IV</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="comp-col">Se desenvuelve en entornos virtuales generados por las TIC</td>
                <td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
            <tr>
                <td class="comp-col">Gestiona su aprendizaje de manera autónoma</td>
                <td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
        </tbody>
    </table>

    <div class="title" style="margin-top: 15px;">RESUMEN DE ASISTENCIA</div>
    <table class="grades-table" style="width: 80%; margin: 0 auto;">
        <thead>
            <tr>
                <th>Periodo</th>
                <th>Inasistencia Justificada</th>
                <th>Inasistencia Injustificada</th>
                <th>Tardanza Justificada</th>
                <th>Tardanza Injustificada</th>
            </tr>
        </thead>
        <tbody>
            @foreach(['b1' => 'I Bimestre', 'b2' => 'II Bimestre', 'b3' => 'III Bimestre', 'b4' => 'IV Bimestre'] as $key => $name)
                <tr>
                    <td>{{ $name }}</td>
                    <td>{{ $attendanceStats[$key]['justified_absences'] ?? 0 }}</td>
                    <td>{{ $attendanceStats[$key]['unjustified_absences'] ?? 0 }}</td>
                    <td>{{ $attendanceStats[$key]['justified_tardies'] ?? 0 }}</td>
                    <td>{{ $attendanceStats[$key]['unjustified_tardies'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="legend-table">
        <thead>
            <tr>
                <th colspan="2">ESCALA DE CALIFICACIÓN DE LOS APRENDIZAJES</th>
            </tr>
        </thead>
        <tbody>
            <tr><td width="5%" style="text-align: center; font-weight: bold;">AD</td><td><b>Logro Destacado:</b> Evidencia un nivel superior a lo esperado respecto a la competencia.</td></tr>
            <tr><td style="text-align: center; font-weight: bold;">A</td><td><b>Logro Esperado:</b> Evidencia el nivel esperado respecto a la competencia.</td></tr>
            <tr><td style="text-align: center; font-weight: bold;">B</td><td><b>En Proceso:</b> Próximo o cerca al nivel esperado respecto a la competencia.</td></tr>
            <tr><td style="text-align: center; font-weight: bold;">C</td><td><b>En Inicio:</b> Muestra un progreso mínimo en una competencia y requiere acompañamiento.</td></tr>
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <br/><br/><br/>
            _________________________________<br/>
            Firma y Sello de la Dirección
        </div>
        <div class="signature-box">
            <br/><br/><br/>
            _________________________________<br/>
            Firma del Docente Tutor
        </div>
    </div>
</body>
</html>
