<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admins')->insert([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'phone' => '0599000000',
            'password' => Hash::make('admin123'),
            'level' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "Admin added to admins table: admin@test.com / admin123\n";
    }
}