<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'UtopiaDemo2026!';

    public function run(): void
    {
        $users = [
            ['name' => 'Utopia Bay Demo Admin', 'email' => 'admin@utopiabay.test', 'role' => User::ROLE_ADMIN],
            ['name' => 'Utopia Bay Demo Manager', 'email' => 'manager@utopiabay.test', 'role' => User::ROLE_MANAGER],
            ['name' => 'Utopia Bay Front Desk', 'email' => 'staff@utopiabay.test', 'role' => User::ROLE_STAFF],
            ['name' => 'Utopia Bay Demo Guest', 'email' => 'customer@utopiabay.test', 'role' => User::ROLE_CUSTOMER],
            ['name' => 'Utopia Bay Stay Guest', 'email' => 'stay@utopiabay.test', 'role' => User::ROLE_CUSTOMER],
            ['name' => 'Utopia Bay Past Guest', 'email' => 'history@utopiabay.test', 'role' => User::ROLE_CUSTOMER],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(['email' => $data['email']], $data + [
                'password' => self::DEMO_PASSWORD,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
