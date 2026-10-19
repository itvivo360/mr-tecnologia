<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('number', 32)->unique();
            // pending | paid | failed | expired | cancelled
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('total');
            $table->string('currency', 3)->default('CLP');
            $table->string('payment_gateway', 30);
            $table->string('payment_reference')->nullable(); // id de preferencia
            $table->string('payment_id')->nullable()->unique(); // id del pago aprobado
            $table->string('payment_status')->nullable();
            // Se marca si un pago llega aprobado después de liberar el stock y no
            // hubo stock suficiente para volver a reservarlo.
            $table->boolean('needs_review')->default(false);
            $table->string('shipping_name')->nullable();
            $table->string('shipping_phone', 30)->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamp('reserved_until')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'reserved_until']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // Snapshot del producto al momento de la compra
            $table->string('name');
            $table->string('sku', 64);
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('line_total');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
