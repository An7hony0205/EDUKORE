<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$years = \App\Models\AcademicYear::all();
foreach($years as $y) { 
    $levelsCount = \App\Models\Level::where('academic_year_id', $y->id)->count();
    $enrollsCount = \App\Models\Enrollment::where('academic_year_id', $y->id)->count();
    echo $y->id . " - Levels: $levelsCount - Enrollments: $enrollsCount\n"; 
}
