<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicLevel;
use App\Models\AcademicGrade;
use App\Models\AcademicSection;
use Illuminate\Support\Str;

class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            'Primaria' => [
                '1er Grado', '2do Grado', '3er Grado',
                '4to Grado', '5to Grado', '6to Grado',
            ],
            'Secundaria' => [
                '1er Año', '2do Año', '3er Año',
                '4to Año', '5to Año',
            ],
        ];

        foreach ($structure as $levelName => $gradeNames) {
            $level = AcademicLevel::firstOrCreate(
                ['name' => $levelName],
                ['id'   => (string) Str::uuid()]
            );

            foreach ($gradeNames as $gradeName) {
                $grade = AcademicGrade::firstOrCreate(
                    [
                        'academic_level_id' => $level->id,
                        'name'              => $gradeName,
                    ],
                    ['id' => (string) Str::uuid()]
                );

                // Sección "A" por defecto para cada grado
                AcademicSection::firstOrCreate(
                    [
                        'grade_id' => $grade->id,
                        'name'     => 'A',
                    ],
                    [
                        'id'           => (string) Str::uuid(),
                        'max_capacity' => 25,
                    ]
                );
            }
        }

        $this->command->info('✅ Estructura académica peruana creada exitosamente.');
    }
}
