<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')->get();
        
        foreach ($users as $user) {
            if ($user->role === 'restaurant') {
                DB::table('restaurants')
                    ->where('id', 1)
                    ->update([
                        'email' => $user->email,
                        'password' => $user->password,
                        'phone' => $user->phone,
                    ]);
            } elseif ($user->role === 'customer') {
                DB::table('customers')->insert([
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'password' => $user->password,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]);
            } elseif ($user->role === 'driver') {
                DB::table('drivers')->insert([
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'password' => $user->password,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]);
            } elseif ($user->role === 'admin') {
                DB::table('admins')->insert([
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'password' => $user->password,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]);
            }
        }
    }

    public function down(): void
    {
    }
};