<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
        });

        $customers = DB::table('customers')->get();
        foreach ($customers as $customer) {
            $existingUser = DB::table('users')->where('email', $customer->email)->first();
            
            if ($existingUser) {
                DB::table('users')->where('id', $existingUser->id)->update([
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'updated_at' => $customer->updated_at,
                ]);
            } else {
                DB::table('users')->insert([
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'password' => $customer->password,
                    'role' => 'customer',
                    'address' => $customer->address,
                    'email_verified_at' => $customer->email_verified_at,
                    'created_at' => $customer->created_at,
                    'updated_at' => $customer->updated_at,
                ]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('customers');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        $users = DB::table('users')->where('role', 'customer')->get();
        foreach ($users as $user) {
            DB::table('customers')->insert([
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'password' => $user->password,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};