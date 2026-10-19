<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = Str::title($this->faker->unique()->words(3, true));

        return [
            'sku' => strtoupper(Str::random(3)).'-'.$this->faker->unique()->numberBetween(1000, 99999),
            'barcode' => $this->faker->unique()->ean13(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->numberBetween(20, 900) * 100,
            'min_stock' => 5,
            'is_active' => true,
        ];
    }

    /** Stock inicial sin pasar por movimientos (solo para tests y seeds). */
    public function withStock(int $stock): static
    {
        return $this->afterCreating(fn (Product $p) => $p->forceFill(['stock' => $stock])->save());
    }
}
