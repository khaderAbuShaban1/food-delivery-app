<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->decimal('commission_percentage', 5, 2)->default(10);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('final_amount', 12, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'unpaid'])->default('pending');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('restaurant_invoice_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_invoice_id')->constrained('restaurant_invoices')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['restaurant_invoice_id', 'order_id'], 'invoice_order_unique');
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_invoice_orders');
        Schema::dropIfExists('restaurant_invoices');
    }
};
