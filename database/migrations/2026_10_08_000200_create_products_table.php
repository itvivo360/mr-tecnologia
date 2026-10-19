<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 64)->unique();
            // Código de barras (EAN-13, UPC, Code128...). Único entre productos no eliminados.
            $table->string('barcode', 64)->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price'); // CLP, sin decimales
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('min_stock')->default(5);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'category_id']);
        });

        // Unicidad del código de barras solo entre productos vigentes, para poder
        // reasignar el código de un producto eliminado a uno nuevo.
        if (in_array(Schema::getConnection()->getDriverName(), ['pgsql', 'sqlite'])) {
            Schema::getConnection()->statement(
                'CREATE UNIQUE INDEX products_barcode_active_unique ON products (barcode) WHERE deleted_at IS NULL AND barcode IS NOT NULL'
            );
        } else {
            Schema::table('products', fn (Blueprint $table) => $table->unique('barcode'));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
