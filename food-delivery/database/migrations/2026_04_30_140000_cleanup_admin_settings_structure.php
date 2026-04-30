<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_settings')) {
            DB::table('system_settings')
                ->where('group', 'general')
                ->whereIn('key', ['default_currency', 'timezone'])
                ->delete();
        }

        Schema::dropIfExists('rbac_role_permission');
        Schema::dropIfExists('rbac_permissions');
        Schema::dropIfExists('rbac_roles');
    }

    public function down(): void
    {
        // Intentionally left empty. RBAC has been removed from settings architecture.
    }
};
