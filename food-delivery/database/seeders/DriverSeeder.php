<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * Insert two drivers for testing / phpMyAdmin population.
     * Login (driver app): email + password below (plain text is hashed by the Driver model).
     */
    public function run(): void
    {
        $rows = [
            [
                'name' => 'أحمد السائق',
                'email' => 'driver1@test.com',
                'phone' => '+970599111111',
                'password' => 'password123',
                'is_available' => true,
                'vehicle_type' => 'دراجة نارية',
                'license_number' => 'DL-10001',
            ],
            [
                'name' => 'محمد السائق',
                'email' => 'driver2@test.com',
                'phone' => '+970599222222',
                'password' => 'password123',
                'is_available' => true,
                'vehicle_type' => 'دراجة نارية',
                'license_number' => 'DL-10002',
            ],
        ];

        foreach ($rows as $data) {
            Driver::updateOrCreate(
                ['email' => $data['email']],
                $data
            );
        }
    }
}
