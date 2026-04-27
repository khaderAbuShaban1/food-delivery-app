<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('email')->after('id');
            $table->string('password')->after('email');
            $table->string('phone')->nullable()->after('password');
        });
        
        Schema::table('restaurants', function (Blueprint $table) {
            $table->unique('email');
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->dropUnique(['email']);
            $table->dropColumn(['email', 'password', 'phone']);
        });
    }
};