<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'mariana@reflorea.app'],
            [
                'uuid' => 'demo-user-001',
                'name' => 'Mariana Silva',
                'password' => Hash::make('demo123'),
                'phone' => '(11) 99999-9999',
                'cpf' => '123.456.789-00',
                'city' => 'São Paulo',
                'state' => 'SP',
                'eco_points' => 1560,
                'trees_planted' => 128,
                'profile_complete' => 80,
            ],
        );
    }
}
