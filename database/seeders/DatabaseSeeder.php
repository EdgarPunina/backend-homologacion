<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        if (app()->environment(['local', 'testing'])) {
            $user = User::firstOrCreate(['email' => 'test@example.com'], [
                'name' => 'Test User',
                'password' => 'password',
            ]);
            $user->assignRole('Estudiante');
        }

        $this->call(AdminUserSeeder::class);
    }
}
