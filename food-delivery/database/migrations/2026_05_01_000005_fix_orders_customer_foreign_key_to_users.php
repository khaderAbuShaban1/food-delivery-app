<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'customer_id')) {
            return;
        }

        $dbName = DB::getDatabaseName();
        $foreignExists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'orders')
            ->where('COLUMN_NAME', 'customer_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if ($foreignExists) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropForeign(['customer_id']);
                });
            } catch (\Throwable $e) {
                // Fallback for environments where automatic constraint-name resolution fails.
                DB::statement('ALTER TABLE orders DROP FOREIGN KEY orders_customer_id_foreign');
            }
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'customer_id')) {
            return;
        }

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });
        } catch (\Throwable $e) {
            DB::statement('ALTER TABLE orders DROP FOREIGN KEY orders_customer_id_foreign');
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();
        });
    }
};
