<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_methods')) {
            return;
        }

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('bank_name')->nullable();
            $table->string('wallet_provider')->nullable();
            $table->string('account_holder_name');
            $table->string('account_number')->nullable();
            $table->string('phone_number');
            $table->string('static_image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
