<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$years = \App\Models\AcademicYear::all();
foreach($years as $y) { 
    $enrollsCount = \App\Models\Enrollment::where('academic_year_id', $y->id)->count();
    if ($enrollsCount == 0) {
        $y->delete();
        echo "Deleted unused year " . $y->id . "\n";
    }
}
