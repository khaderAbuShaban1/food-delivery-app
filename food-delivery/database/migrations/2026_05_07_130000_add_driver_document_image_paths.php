<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            if (! Schema::hasColumn('drivers', 'national_id_image')) {
                $table->string('national_id_image')->nullable()->after('profile_image');
            }

            if (! Schema::hasColumn('drivers', 'vehicle_image')) {
                $table->string('vehicle_image')->nullable()->after('national_id_image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            foreach (['vehicle_image', 'national_id_image'] as $column) {
                if (Schema::hasColumn('drivers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
