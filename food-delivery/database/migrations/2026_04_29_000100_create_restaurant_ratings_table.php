<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('rating');
            $table->timestamps();

            $table->unique(['restaurant_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_ratings');
    }
};
