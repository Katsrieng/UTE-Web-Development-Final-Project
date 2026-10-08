<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Demo accounts. All passwords are "password" - change them before any real use!
     * IDs 1-4 match the user_id values already used in PaymentSeeder.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Hotel Admin',    'email' => 'admin@hotel.com',     'role' => User::ROLE_ADMIN,    'phone' => '012 000 001'],
            ['name' => 'Front Desk',     'email' => 'staff@hotel.com',     'role' => User::ROLE_STAFF,    'phone' => '012 000 002'],
            ['name' => 'Sokha Customer', 'email' => 'customer1@hotel.com', 'role' => User::ROLE_CUSTOMER, 'phone' => '012 000 003'],
            ['name' => 'Dara Customer',  'email' => 'customer2@hotel.com', 'role' => User::ROLE_CUSTOMER, 'phone' => '012 000 004'], 
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                $data + ['password' => 'password', 'is_active' => true, 'email_verified_at' => now()]
            );
        }
    }
}
