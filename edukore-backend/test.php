<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$years = \App\Models\AcademicYear::all();
foreach($years as $y) { echo $y->id . " - " . $y->year_name . "\n"; }
