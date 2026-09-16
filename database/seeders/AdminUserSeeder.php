<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('INITIAL_ADMIN_EMAIL');
        $password = env('INITIAL_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('Administrador inicial omitido: define INITIAL_ADMIN_EMAIL e INITIAL_ADMIN_PASSWORD.');

            return;
        }

        $admin = User::firstOrCreate(['email' => $email], [
            'name' => env('INITIAL_ADMIN_NAME', 'Administrador inicial'),
            'password' => $password,
        ]);

        $admin->assignRole('Administrador');
    }
}
