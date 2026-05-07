<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menu_item_option_groups')) {
            Schema::create('menu_item_option_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
                $table->string('name');
                $table->enum('selection_type', ['single', 'multiple'])->default('single');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('menu_item_option_values')) {
            Schema::create('menu_item_option_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('option_group_id')->constrained('menu_item_option_groups')->cascadeOnDelete();
                $table->string('name');
                $table->decimal('extra_price', 10, 2)->default(0);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_option_values');
        Schema::dropIfExists('menu_item_option_groups');
    }
};
