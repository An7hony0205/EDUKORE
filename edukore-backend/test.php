<?php
require 'vendor/autoload.php';
\ = require_once 'bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Console\Kernel::class);
\->bootstrap();

try {
    \ = new Illuminate\Http\Request(); 
    \->merge(['name'=>'Test', 'email'=>'test@test.com', 'dni'=>'12345678']); 
    \ = new App\Http\Controllers\TeacherController(); 
    auth()->login(App\Models\User::role('admin')->first()); 
    echo \->store(\)->getContent();
} catch (\Exception \) { 
    echo 'Error: ' . \->getMessage(); 
}
