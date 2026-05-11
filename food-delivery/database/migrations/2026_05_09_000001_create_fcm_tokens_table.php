<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('token', 512)->unique();
            $table->string('platform', 24)->nullable();
            $table->string('device_id')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id', 'platform']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token', 512)->nullable()->after('remember_token');
            }
        });

        if (Schema::hasTable('drivers')) {
            Schema::table('drivers', function (Blueprint $table) {
                if (! Schema::hasColumn('drivers', 'fcm_token')) {
                    $table->string('fcm_token', 512)->nullable()->after('remember_token');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('drivers') && Schema::hasColumn('drivers', 'fcm_token')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->dropColumn('fcm_token');
            });
        }

        if (Schema::hasColumn('users', 'fcm_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('fcm_token');
            });
        }

        Schema::dropIfExists('fcm_tokens');
    }
};
