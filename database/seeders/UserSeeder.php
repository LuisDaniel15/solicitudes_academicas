<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Administrador', 'email' => 'admin@sigesu.test', 'role' => User::ROLE_ADMIN],
            ['name' => 'Ana Estudiante', 'email' => 'estudiante@sigesu.test', 'role' => User::ROLE_STUDENT],
            ['name' => 'Carlos Funcionario', 'email' => 'funcionario@sigesu.test', 'role' => User::ROLE_OFFICER],
            ['name' => 'Supervisor SIGESU', 'email' => 'supervisor@sigesu.test', 'role' => User::ROLE_SUPERVISOR],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [...$data, 'password' => 'password123', 'active' => true],
            );
        }
    }
}
