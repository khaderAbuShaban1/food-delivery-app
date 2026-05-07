<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        foreach (
            DB::table('payment_methods')
                ->whereNotNull('static_image')
                ->where('static_image', 'like', '%.svg')
                ->cursor() as $row
        ) {
            $updated = preg_replace('/\.svg$/', '.png', $row->static_image) ?? $row->static_image;
            if ($updated !== $row->static_image) {
                DB::table('payment_methods')->where('id', $row->id)->update(['static_image' => $updated]);
            }
        }
    }

    public function down(): void
    {
        // PNG logos are authoritative; no safe automatic revert.
    }
};
