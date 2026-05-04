<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_proof', 2048)->nullable();
            $table->string('payment_reference', 191)->nullable();
            $table->timestamp('payment_verified_at')->nullable();
            $table->foreignId('verified_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
        });

        $driverName = Schema::getConnection()->getDriverName();

        if ($driverName === 'mysql' && Schema::hasColumn('orders', 'status')) {
            DB::statement('ALTER TABLE orders MODIFY COLUMN status VARCHAR(48) NOT NULL');
        }

        DB::table('orders')->where('status', 'pending')->update(['status' => 'pending_payment_verification']);
        DB::table('orders')->where('status', 'accepted')->update(['status' => 'accepted_by_restaurant']);
        DB::table('orders')->where('status', 'delivering')->update(['status' => 'on_the_way']);
        DB::table('orders')->where('status', 'completed')->update(['status' => 'delivered']);
        DB::table('orders')->where('status', 'cancelled')->update(['status' => 'payment_rejected']);

        DB::table('orders')
            ->whereIn('status', ['accepted_by_restaurant', 'preparing', 'on_the_way', 'delivered'])
            ->whereNull('payment_verified_at')
            ->update([
                'payment_verified_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);

        if ($driverName === 'mysql' && Schema::hasColumn('orders', 'status')) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(48) NOT NULL DEFAULT 'pending_payment_verification'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            DB::table('orders')->where('status', 'pending_payment_verification')->update(['status' => 'pending']);
            DB::table('orders')->where('status', 'payment_verified')->update(['status' => 'accepted']);
            DB::table('orders')->where('status', 'accepted_by_restaurant')->update(['status' => 'accepted']);
            DB::table('orders')->where('status', 'on_the_way')->update(['status' => 'delivering']);
            DB::table('orders')->where('status', 'delivered')->update(['status' => 'completed']);
            DB::table('orders')->where('status', 'payment_rejected')->update(['status' => 'cancelled']);
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['verified_by_admin_id']);
            $table->dropColumn(['payment_proof', 'payment_reference', 'payment_verified_at', 'verified_by_admin_id']);
        });

        $driverName = Schema::getConnection()->getDriverName();

        if ($driverName === 'mysql' && Schema::hasColumn('orders', 'status')) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','accepted','preparing','delivering','completed','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
