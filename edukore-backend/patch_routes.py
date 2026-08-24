# -*- coding: utf-8 -*-
import re

with open('routes/api.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Add route for academic-structure
import_line = "use App\Http\Controllers\AcademicPeriodController;\nuse App\Http\Controllers\AcademicStructureController;"
content = content.replace("use App\Http\Controllers\AcademicPeriodController;", import_line)

route_line = "Route::apiResource('sections', SectionController::class);\n        Route::get('academic-structure', [AcademicStructureController::class, 'index']);\n        Route::get('sections/{section}/details', [SectionController::class, 'details']);"
content = content.replace("Route::apiResource('sections', SectionController::class);", route_line)

with open('routes/api.php', 'w', encoding='utf-8') as f:
    f.write(content)
