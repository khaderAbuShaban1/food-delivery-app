<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            if (! Schema::hasColumn('drivers', 'national_id')) {
                $table->string('national_id')->nullable()->unique()->after('name');
            }
            if (! Schema::hasColumn('drivers', 'approval_status')) {
                $table->string('approval_status', 24)->default('pending')->after('password');
            }
            if (! Schema::hasColumn('drivers', 'profile_image')) {
                $table->string('profile_image')->nullable()->after('approval_status');
            }
            if (! Schema::hasColumn('drivers', 'vehicle_plate_number')) {
                $table->string('vehicle_plate_number')->nullable()->after('vehicle_type');
            }
            if (! Schema::hasColumn('drivers', 'city')) {
                $table->string('city')->nullable()->after('vehicle_plate_number');
            }
            if (! Schema::hasColumn('drivers', 'emergency_contact_number')) {
                $table->string('emergency_contact_number')->nullable()->after('city');
            }
            if (! Schema::hasColumn('drivers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('emergency_contact_number');
            }
            if (! Schema::hasColumn('drivers', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
        });

        DB::table('drivers')
            ->where(function ($query) {
                $query->whereNull('approval_status')
                    ->orWhere('approval_status', 'pending');
            })
            ->update([
                'approval_status' => 'approved',
                'approved_at' => DB::raw('COALESCE(approved_at, updated_at, created_at)'),
                'rejected_at' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            foreach ([
                'rejected_at',
                'approved_at',
                'emergency_contact_number',
                'city',
                'vehicle_plate_number',
                'profile_image',
                'approval_status',
                'national_id',
            ] as $column) {
                if (Schema::hasColumn('drivers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
