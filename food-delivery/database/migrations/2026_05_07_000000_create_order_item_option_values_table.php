<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->unsignedBigInteger('option_group_id');
            $table->unsignedBigInteger('option_value_id');
            $table->string('group_name')->nullable();
            $table->string('value_name');
            $table->decimal('extra_price', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['order_item_id', 'option_group_id']);
            $table->index(['option_group_id', 'option_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_option_values');
    }
};

