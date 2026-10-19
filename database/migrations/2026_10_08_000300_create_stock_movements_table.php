<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // in | out | adjustment | sale | release
            $table->string('type', 20);
            $table->integer('quantity'); // con signo: positivo entra, negativo sale
            $table->unsignedInteger('stock_after');
            $table->string('reason')->nullable();
            $table->nullableMorphs('reference'); // p. ej. la orden que generó el movimiento
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
