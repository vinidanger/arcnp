<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $password = Str::password(16);

        $admin = User::factory()->create([
            'name' => 'Administrador',
            'email' => 'arcndev@gmail.com',
            'password' => $password,
            'type' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('admin');

        $this->command->info("Admin criado: {$admin->email} / senha: {$password}");
    }
}
