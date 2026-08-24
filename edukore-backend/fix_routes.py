# -*- coding: utf-8 -*-
import sys

with open('routes/api.php', 'r', encoding='utf-8') as f:
    content = f.read()

old_route = "Route::apiResource('courses', CourseController::class);"
new_routes = """Route::get('courses', [CourseController::class, 'index']);
        Route::get('courses/{course}', [CourseController::class, 'show']);
        Route::post('courses', [CourseController::class, 'store'])->middleware('role:Admin');
        Route::put('courses/{course}', [CourseController::class, 'update'])->middleware('role:Admin');
        Route::patch('courses/{course}', [CourseController::class, 'update'])->middleware('role:Admin');
        Route::delete('courses/{course}', [CourseController::class, 'destroy'])->middleware('role:Admin');"""

content = content.replace(old_route, new_routes)

with open('routes/api.php', 'w', encoding='utf-8') as f:
    f.write(content)
