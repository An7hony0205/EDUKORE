# -*- coding: utf-8 -*-
import sys
import re

with open('bootstrap/app.php', 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
    "'module' => \App\Http\Middleware\CheckTenantModule::class,",
    "'module' => \App\Http\Middleware\CheckTenantModule::class,\n            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,"
)

with open('bootstrap/app.php', 'w', encoding='utf-8') as f:
    f.write(content)
