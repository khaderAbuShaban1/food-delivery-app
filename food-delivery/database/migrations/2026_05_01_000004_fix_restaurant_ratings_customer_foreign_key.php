<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('restaurant_ratings')) {
            return;
        }

        Schema::table('restaurant_ratings', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        Schema::table('restaurant_ratings', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('restaurant_ratings')) {
            return;
        }

        Schema::table('restaurant_ratings', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        Schema::table('restaurant_ratings', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();
        });
    }
};
