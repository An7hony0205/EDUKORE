# -*- coding: utf-8 -*-
content = """<?php

namespace Database\\Seeders;

use App\\Models\\AcademicYear;
use App\\Models\\Level;
use App\\Models\\GradeLevel;
use App\\Models\\Section;
use App\\Models\\Tenant;
use Illuminate\\Database\\Seeder;
use Illuminate\\Support\\Str;

class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();

        $year = AcademicYear::create([
            'id' => Str::uuid(),
            'tenant_id' => $tenant->id,
            'year_name' => '2026',
            'start_date' => '2026-03-01',
            'end_date' => '2026-12-15',
            'is_active' => true,
        ]);

        $primaria = Level::create([
            'id' => Str::uuid(),
            'academic_year_id' => $year->id,
            'name' => 'Primaria',
        ]);
        
        $secundaria = Level::create([
            'id' => Str::uuid(),
            'academic_year_id' => $year->id,
            'name' => 'Secundaria',
        ]);

        $grado4 = GradeLevel::create([
            'id' => Str::uuid(),
            'level_id' => $secundaria->id,
            'name' => '4.º',
        ]);
        
        $grado5 = GradeLevel::create([
            'id' => Str::uuid(),
            'level_id' => $secundaria->id,
            'name' => '5.º',
        ]);

        foreach (['A', 'B', 'C', 'D'] as $s) {
            Section::create([
                'id' => Str::uuid(),
                'grade_level_id' => $grado4->id,
                'name' => $s,
                'capacity' => 30
            ]);
        }
        
        foreach (['A', 'B', 'C'] as $s) {
            Section::create([
                'id' => Str::uuid(),
                'grade_level_id' => $grado5->id,
                'name' => $s,
                'capacity' => 30
            ]);
        }
    }
}
"""
with open('database/seeders/AcademicStructureSeeder.php', 'w', encoding='utf-8') as f:
    f.write(content)
