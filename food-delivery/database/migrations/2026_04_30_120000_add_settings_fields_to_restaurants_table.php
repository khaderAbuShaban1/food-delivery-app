<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->decimal('minimum_order_amount', 10, 2)->nullable()->after('is_open');
            $table->boolean('delivery_available')->default(true)->after('minimum_order_amount');
            $table->json('working_hours')->nullable()->after('delivery_available');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'minimum_order_amount',
                'delivery_available',
                'working_hours',
            ]);
        });
    }
};
