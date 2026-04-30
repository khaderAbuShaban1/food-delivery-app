<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','accepted','preparing','delivering','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE orders SET status = 'completed' WHERE status = 'cancelled'");
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','accepted','preparing','delivering','completed') NOT NULL DEFAULT 'pending'");
    }
};
