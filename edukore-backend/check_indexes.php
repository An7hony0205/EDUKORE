<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pdo = DB::getPdo();

$tables = [
    'users' => ['tenant_id', 'id'],
    'families' => ['tenant_id', 'id'],
    'family_members' => ['user_id', 'family_id'],
    'family_students' => ['family_id', 'student_id'],
    'students' => ['user_id', 'id'],
    'attendance' => ['course_assignment_id', 'enrollment_id', 'date'],
    'enrollments' => ['student_id', 'section_id', 'tenant_id', 'academic_year_id'],
];

foreach ($tables as $table => $columns) {
    echo "\n=== $table ===\n";
    $stmt = $pdo->query("
        SELECT indexname, indexdef
        FROM pg_indexes
        WHERE tablename = '$table'
        ORDER BY indexname;
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "  [NONE]\n";
    }
    foreach ($rows as $row) {
        echo "  {$row['indexname']}: {$row['indexdef']}\n";
    }
}
