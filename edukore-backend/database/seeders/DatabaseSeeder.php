<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenantId = (string) Str::uuid();
        $roleId = (string) Str::uuid();
        $userId = (string) Str::uuid();

        DB::table('tenants')->insert([
            'id' => $tenantId,
            'subdomain' => 'demo',
            'legal_name' => 'Colegio Demo',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('roles')->insert([
            'id' => $roleId,
            'tenant_id' => $tenantId,
            'role_name' => 'Docente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => $userId,
            'tenant_id' => $tenantId,
            'role_id' => $roleId,
            'name' => 'Profesor Prueba',
            'email' => 'profesor@demo.edu',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
