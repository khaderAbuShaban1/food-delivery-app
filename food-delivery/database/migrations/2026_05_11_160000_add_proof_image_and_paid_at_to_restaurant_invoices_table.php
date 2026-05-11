<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_invoices', function (Blueprint $table) {
            $table->string('payment_proof_image')->nullable()->after('payment_proof');
            $table->timestamp('payment_paid_at')->nullable()->after('payment_proof_image');
        });

        if (Schema::hasColumn('restaurant_invoices', 'payment_proof')) {
            DB::table('restaurant_invoices')
                ->whereNotNull('payment_proof')
                ->update(['payment_proof_image' => DB::raw('payment_proof')]);
        }
    }

    public function down(): void
    {
        Schema::table('restaurant_invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_proof_image', 'payment_paid_at']);
        });
    }
};
