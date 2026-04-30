<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE admins MODIFY COLUMN level VARCHAR(50) NOT NULL DEFAULT 'admin'");
        
        DB::table('admins')->insert([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'phone' => '0599000000',
            'password' => bcrypt('admin123'),
            'level' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "Admin inserted: admin@test.com / admin123\n";
    }

    public function down(): void
    {
        DB::table('admins')->where('email', 'admin@test.com')->delete();
    }
};