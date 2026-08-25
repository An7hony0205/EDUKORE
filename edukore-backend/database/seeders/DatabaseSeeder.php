<?php
namespace Database\Seeders;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        $tenant = Tenant::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'subdomain' => 'demo',
            'legal_name' => 'Colegio Demo',
            'is_active' => true,
        ]);
        
        $adminRole = Role::firstOrCreate(['name' => 'Admin'], ['id' => \Illuminate\Support\Str::uuid()]);
        $docenteRole = Role::firstOrCreate(['name' => 'Teacher'], ['id' => \Illuminate\Support\Str::uuid()]);
        
        $admin = User::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);
        $admin->assignRole('Admin');
    }
}
